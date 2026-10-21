<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$currentUserId = (int)$_SESSION['user_id'];
$isAdminUser   = isAdmin();
$complaintId   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($complaintId <= 0) {
    setFlashMessage('danger', 'Invalid complaint ID specified.');
    header('Location: ' . ($isAdminUser ? 'manage_complaints.php' : 'my_complaints.php'));
    exit;
}

$pdo = getDBConnection();

// Fetch complaint along with submitter user details
$stmt = $pdo->prepare("
    SELECT c.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone, u.account_status AS user_status
    FROM complaints c
    INNER JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
    LIMIT 1
");
$stmt->execute([$complaintId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlashMessage('danger', 'The requested complaint was not found.');
    header('Location: ' . ($isAdminUser ? 'manage_complaints.php' : 'my_complaints.php'));
    exit;
}

// Prevent Insecure Direct Object Reference (IDOR): standard users can only view their own complaints
if (!$isAdminUser && (int)$complaint['user_id'] !== $currentUserId) {
    setFlashMessage('danger', 'Access denied. You can only view complaints submitted by your account.');
    header('Location: my_complaints.php');
    exit;
}

$errors = [];

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token (CSRF). Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. User Action: Withdraw Complaint (allowed if Submitted or Under Review)
        if ($action === 'withdraw_complaint' && !$isAdminUser && (int)$complaint['user_id'] === $currentUserId) {
            if (in_array($complaint['status'], ['Submitted', 'Under Review'], true)) {
                // Delete uploaded file if exists
                if (!empty($complaint['image_path'])) {
                    $fullPath = __DIR__ . '/' . $complaint['image_path'];
                    if (is_file($fullPath)) {
                        @unlink($fullPath);
                    }
                }

                $stmtDel = $pdo->prepare("DELETE FROM complaints WHERE id = ? AND user_id = ?");
                $stmtDel->execute([$complaintId, $currentUserId]);

                setFlashMessage('info', 'Complaint ' . formatComplaintId($complaintId) . ' has been withdrawn and removed.');
                header('Location: my_complaints.php');
                exit;
            } else {
                $errors[] = 'This complaint can no longer be withdrawn because it is already ' . $complaint['status'] . '.';
            }
        }

        // 2. Admin Action: Update Status & Priority
        if ($action === 'update_status_priority' && $isAdminUser) {
            $newStatus   = trim($_POST['status'] ?? '');
            $newPriority = trim($_POST['priority'] ?? '');
            $remarks     = trim($_POST['remarks'] ?? '');

            $validStatuses   = ['Submitted', 'Under Review', 'In Progress', 'Resolved', 'Rejected'];
            $validPriorities = ['Low', 'Medium', 'High', 'Urgent'];

            if (!in_array($newStatus, $validStatuses, true)) {
                $errors[] = 'Invalid status value selected.';
            }
            if (!in_array($newPriority, $validPriorities, true)) {
                $errors[] = 'Invalid priority value selected.';
            }

            if (empty($errors)) {
                $oldStatus   = $complaint['status'];
                $oldPriority = $complaint['priority'];

                if ($oldStatus === $newStatus && $oldPriority === $newPriority && $remarks === '') {
                    $errors[] = 'No changes were detected in status, priority, or remarks.';
                } else {
                    $pdo->beginTransaction();
                    try {
                        $stmtUpd = $pdo->prepare("UPDATE complaints SET status = ?, priority = ? WHERE id = ?");
                        $stmtUpd->execute([$newStatus, $newPriority, $complaintId]);

                        $autoRemark = $remarks;
                        if ($oldPriority !== $newPriority) {
                            $priorityNote = "Priority changed from {$oldPriority} to {$newPriority}.";
                            $autoRemark = $autoRemark !== '' ? $priorityNote . ' ' . $autoRemark : $priorityNote;
                        }
                        if ($autoRemark === '') {
                            $autoRemark = "Status updated from {$oldStatus} to {$newStatus} by administrator.";
                        }

                        $stmtHist = $pdo->prepare("
                            INSERT INTO complaint_history (complaint_id, changed_by, old_status, new_status, remarks)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmtHist->execute([$complaintId, $currentUserId, $oldStatus, $newStatus, $autoRemark]);

                        $pdo->commit();

                        setFlashMessage('success', 'Complaint status and priority have been updated.');
                        header('Location: complaint_details.php?id=' . $complaintId);
                        exit;
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        error_log("Admin Update Error: " . $e->getMessage());
                        $errors[] = 'Database error occurred while updating status.';
                    }
                }
            }
        }

        // 3. Admin Action: Add Official Response
        if ($action === 'add_response' && $isAdminUser) {
            $responseText = trim($_POST['response'] ?? '');
            if ($responseText === '' || strlen($responseText) < 5) {
                $errors[] = 'Please enter a meaningful official response (at least 5 characters).';
            } else {
                $stmtResp = $pdo->prepare("
                    INSERT INTO complaint_responses (complaint_id, admin_id, response)
                    VALUES (?, ?, ?)
                ");
                $stmtResp->execute([$complaintId, $currentUserId, $responseText]);

                setFlashMessage('success', 'Official response posted successfully.');
                header('Location: complaint_details.php?id=' . $complaintId);
                exit;
            }
        }
    }
}

// Fetch Official Admin Responses
$stmtResponses = $pdo->prepare("
    SELECT r.*, u.name AS admin_name
    FROM complaint_responses r
    LEFT JOIN users u ON r.admin_id = u.id
    WHERE r.complaint_id = ?
    ORDER BY r.created_at DESC
");
$stmtResponses->execute([$complaintId]);
$responses = $stmtResponses->fetchAll();

// Fetch Complaint History Timeline
$stmtHistory = $pdo->prepare("
    SELECT h.*, u.name AS actor_name, u.role AS actor_role
    FROM complaint_history h
    LEFT JOIN users u ON h.changed_by = u.id
    WHERE h.complaint_id = ?
    ORDER BY h.created_at DESC, h.id DESC
");
$stmtHistory->execute([$complaintId]);
$historyList = $stmtHistory->fetchAll();

// Stepper Helper
$stepsOrder = ['Submitted', 'Under Review', 'In Progress', 'Resolved'];
$currentStatus = $complaint['status'];
$currentStepIndex = array_search($currentStatus, $stepsOrder, true);

$flash = getFlashMessage();
$pageTitle = 'Complaint ' . formatComplaintId($complaintId);
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php
    if ($isAdminUser) {
        require_once __DIR__ . '/includes/admin_sidebar.php';
    } else {
        require_once __DIR__ . '/includes/user_sidebar.php';
    }
    ?>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle-btn" id="sidebarToggleBtn" aria-label="Toggle navigation">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <h1 class="page-heading">Complaint <?php echo e(formatComplaintId($complaintId)); ?></h1>
            </div>
            <div class="topbar-right">
                <a href="<?php echo $isAdminUser ? 'manage_complaints.php' : 'my_complaints.php'; ?>" class="btn btn-outline btn-sm">
                    &larr; Back to List
                </a>
            </div>
        </header>

        <div class="content-body">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>" data-auto-dismiss="true">
                    <span><?php echo e($flash['message']); ?></span>
                    <button type="button" class="alert-close">&times;</button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <div>
                        <?php foreach ($errors as $err): ?>
                            <div>&bull; <?php echo e($err); ?></div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="alert-close">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Status Progress Stepper -->
            <div class="status-stepper">
                <?php if ($currentStatus === 'Rejected'): ?>
                    <div class="step-node completed">
                        <div class="step-circle">&#10003;</div>
                        <div class="step-label">Submitted</div>
                    </div>
                    <div class="step-node rejected">
                        <div class="step-circle">&times;</div>
                        <div class="step-label" style="color: #dc2626;">Rejected / Closed</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($stepsOrder as $idx => $stepName): ?>
                        <?php
                        $nodeClass = '';
                        if ($currentStepIndex !== false) {
                            if ($idx < $currentStepIndex || $currentStatus === 'Resolved') {
                                $nodeClass = 'completed';
                            } elseif ($idx === $currentStepIndex) {
                                $nodeClass = 'current';
                            }
                        }
                        ?>
                        <div class="step-node <?php echo $nodeClass; ?>">
                            <div class="step-circle">
                                <?php echo ($nodeClass === 'completed') ? '&#10003;' : ($idx + 1); ?>
                            </div>
                            <div class="step-label"><?php echo e($stepName); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="details-layout">
                <!-- Left Column: Complaint Information & Official Responses -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 700;">
                                    <?php echo e(formatComplaintId($complaintId)); ?> &bull; Filed on <?php echo date('M d, Y \a\t h:i A', strtotime($complaint['created_at'])); ?>
                                </span>
                                <h2 style="font-size: 1.3rem; color: var(--text-main); margin-top: 4px;">
                                    <?php echo e($complaint['title']); ?>
                                </h2>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <span class="badge <?php echo e(getPriorityBadgeClass($complaint['priority'])); ?>">
                                    Priority: <?php echo e($complaint['priority']); ?>
                                </span>
                                <span class="badge <?php echo e(getStatusBadgeClass($complaint['status'])); ?>">
                                    <?php echo e($complaint['status']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="meta-list">
                                <div class="meta-item">
                                    <label>Category / Department</label>
                                    <span><?php echo e($complaint['category']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <label>Location / Landmark</label>
                                    <span><?php echo e($complaint['location']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <label>Submitted By</label>
                                    <span><?php echo e($complaint['user_name']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <label>Last Updated</label>
                                    <span><?php echo date('M d, Y h:i A', strtotime($complaint['updated_at'])); ?></span>
                                </div>
                                <?php if ($isAdminUser): ?>
                                    <div class="meta-item">
                                        <label>User Email</label>
                                        <span><?php echo e($complaint['user_email']); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <label>User Phone</label>
                                        <span><?php echo e($complaint['user_phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <h3 style="font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); margin-bottom: 8px;">
                                Detailed Description
                            </h3>
                            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; white-space: pre-line; color: var(--text-main);">
                                <?php echo e($complaint['description']); ?>
                            </div>

                            <?php if (!empty($complaint['image_path'])): ?>
                                <div style="margin-top: 20px;">
                                    <h3 style="font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); margin-bottom: 8px;">
                                        Attached Evidence Photo
                                    </h3>
                                    <div class="attachment-preview">
                                        <a href="<?php echo e($complaint['image_path']); ?>" target="_blank" title="Click to open full size image">
                                            <img src="<?php echo e($complaint['image_path']); ?>" alt="Complaint Attachment">
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- User Edit / Withdraw Controls -->
                            <?php if (!$isAdminUser && (int)$complaint['user_id'] === $currentUserId): ?>
                                <?php if (in_array($complaint['status'], ['Submitted', 'Under Review'], true)): ?>
                                    <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                                        <span style="font-size: 0.85rem; color: var(--text-muted);">
                                            <?php if ($complaint['status'] === 'Submitted'): ?>
                                                You can edit or withdraw this complaint before it enters active resolution.
                                            <?php else: ?>
                                                This complaint is under review. You may still withdraw it if resolved externally.
                                            <?php endif; ?>
                                        </span>
                                        <div style="display: flex; gap: 10px;">
                                            <?php if ($complaint['status'] === 'Submitted'): ?>
                                                <a href="edit_complaint.php?id=<?php echo $complaintId; ?>" class="btn btn-secondary btn-sm">
                                                    Edit Complaint
                                                </a>
                                            <?php endif; ?>

                                            <form method="POST" action="complaint_details.php?id=<?php echo $complaintId; ?>"
                                                  data-confirm="Are you sure you want to permanently withdraw and delete this complaint?">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="withdraw_complaint">
                                                <button type="submit" class="btn btn-danger btn-sm">Withdraw Complaint</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Official Administrator Responses Card -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                </svg>
                                <span>Official Administrator Responses (<?php echo count($responses); ?>)</span>
                            </h2>
                        </div>
                        <div class="card-body">
                            <?php if (empty($responses)): ?>
                                <div class="empty-state" style="padding: 24px;">
                                    <p>No official responses have been posted for this complaint yet.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($responses as $resp): ?>
                                    <div class="response-item">
                                        <div class="response-header">
                                            <span>
                                                &#128737; <?php echo e($resp['admin_name'] ?? 'Administrator'); ?> (Official Response)
                                            </span>
                                            <span><?php echo date('M d, Y h:i A', strtotime($resp['created_at'])); ?></span>
                                        </div>
                                        <div style="color: var(--text-main); white-space: pre-line; font-size: 0.95rem;">
                                            <?php echo e($resp['response']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if ($isAdminUser): ?>
                                <form method="POST" action="complaint_details.php?id=<?php echo $complaintId; ?>" style="margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--border-color);">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="add_response">
                                    <div class="form-group">
                                        <label for="response" class="form-label">Post New Official Response to User <span class="required-star">*</span></label>
                                        <textarea id="response" name="response" class="form-control" rows="3"
                                                  placeholder="Write an official update, resolution summary, or instructions for the user..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        Post Official Response
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Admin Action Controls & Status Audit History -->
                <div>
                    <?php if ($isAdminUser): ?>
                        <div class="card" style="border-top: 4px solid var(--primary-600);">
                            <div class="card-header">
                                <h2 class="card-title">Update Status & Priority</h2>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="complaint_details.php?id=<?php echo $complaintId; ?>">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="update_status_priority">

                                    <div class="form-group">
                                        <label for="status" class="form-label">Complaint Status</label>
                                        <select id="status" name="status" class="form-control" required>
                                            <?php foreach (['Submitted', 'Under Review', 'In Progress', 'Resolved', 'Rejected'] as $st): ?>
                                                <option value="<?php echo e($st); ?>" <?php echo $complaint['status'] === $st ? 'selected' : ''; ?>>
                                                    <?php echo e($st); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="priority" class="form-label">Priority Level</label>
                                        <select id="priority" name="priority" class="form-control" required>
                                            <?php foreach (['Low', 'Medium', 'High', 'Urgent'] as $pr): ?>
                                                <option value="<?php echo e($pr); ?>" <?php echo $complaint['priority'] === $pr ? 'selected' : ''; ?>>
                                                    <?php echo e($pr); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="remarks" class="form-label">Status Change Remarks / Audit Note</label>
                                        <textarea id="remarks" name="remarks" class="form-control" rows="3"
                                                  placeholder="Optional note explaining this status or priority update..."></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-block">
                                        Save Status & Priority
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Complaint Status History Timeline -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span>Complaint Audit History</span>
                            </h2>
                        </div>
                        <div class="card-body">
                            <?php if (empty($historyList)): ?>
                                <p style="color: var(--text-muted); font-size: 0.9rem;">No history records available.</p>
                            <?php else: ?>
                                <div class="timeline">
                                    <?php foreach ($historyList as $hist): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-dot"></div>
                                            <div class="timeline-content">
                                                <div class="timeline-header">
                                                    <strong>
                                                        <?php if ($hist['old_status'] && $hist['old_status'] !== $hist['new_status']): ?>
                                                            <?php echo e($hist['old_status']); ?> &rarr; <?php echo e($hist['new_status']); ?>
                                                        <?php else: ?>
                                                            <?php echo e($hist['new_status']); ?>
                                                        <?php endif; ?>
                                                    </strong>
                                                    <span><?php echo date('M d, Y h:i A', strtotime($hist['created_at'])); ?></span>
                                                </div>
                                                <?php if (!empty($hist['remarks'])): ?>
                                                    <p style="font-size: 0.9rem; color: var(--text-body); margin-bottom: 4px;">
                                                        <?php echo e($hist['remarks']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <div style="font-size: 0.78rem; color: var(--text-muted);">
                                                    By: <?php echo e($hist['actor_name'] ?? 'System'); ?>
                                                    <?php if (!empty($hist['actor_role'])): ?>
                                                        (<?php echo ucfirst(e($hist['actor_role'])); ?>)
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
