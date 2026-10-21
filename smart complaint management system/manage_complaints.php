<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pdo = getDBConnection();

// Handle Quick Status/Priority Update from Management Table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_update'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlashMessage('danger', 'Invalid security token (CSRF).');
    } else {
        $complaintId = (int)($_POST['complaint_id'] ?? 0);
        $newStatus   = trim($_POST['status'] ?? '');
        $newPriority = trim($_POST['priority'] ?? '');

        $validStatuses   = ['Submitted', 'Under Review', 'In Progress', 'Resolved', 'Rejected'];
        $validPriorities = ['Low', 'Medium', 'High', 'Urgent'];

        if ($complaintId > 0 && in_array($newStatus, $validStatuses, true) && in_array($newPriority, $validPriorities, true)) {
            $stmtCur = $pdo->prepare("SELECT status, priority FROM complaints WHERE id = ? LIMIT 1");
            $stmtCur->execute([$complaintId]);
            $current = $stmtCur->fetch();

            if ($current && ($current['status'] !== $newStatus || $current['priority'] !== $newPriority)) {
                $pdo->beginTransaction();
                try {
                    $stmtUpd = $pdo->prepare("UPDATE complaints SET status = ?, priority = ? WHERE id = ?");
                    $stmtUpd->execute([$newStatus, $newPriority, $complaintId]);

                    $remark = "Quick update by Admin: Status [{$current['status']} -> {$newStatus}], Priority [{$current['priority']} -> {$newPriority}].";
                    $stmtHist = $pdo->prepare("
                        INSERT INTO complaint_history (complaint_id, changed_by, old_status, new_status, remarks)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmtHist->execute([$complaintId, $_SESSION['user_id'], $current['status'], $newStatus, $remark]);

                    $pdo->commit();
                    setFlashMessage('success', 'Complaint ' . formatComplaintId($complaintId) . ' updated successfully.');
                } catch (Exception $e) {
                    $pdo->rollBack();
                    setFlashMessage('danger', 'Failed to update complaint.');
                }
            }
        }
    }
    header('Location: manage_complaints.php?' . http_build_query($_GET));
    exit;
}

// Search & Filter Parameters
$searchKeyword  = trim($_GET['q'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$priorityFilter = trim($_GET['priority'] ?? '');
$statusFilter   = trim($_GET['status'] ?? '');
$dateFrom       = trim($_GET['date_from'] ?? '');
$dateTo         = trim($_GET['date_to'] ?? '');

$sql = "
    SELECT c.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
    FROM complaints c
    INNER JOIN users u ON c.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($searchKeyword !== '') {
    $sql .= " AND (c.title LIKE :kw OR c.description LIKE :kw OR c.location LIKE :kw OR u.name LIKE :kw OR u.email LIKE :kw)";
    $params[':kw'] = '%' . $searchKeyword . '%';
}

if ($categoryFilter !== '') {
    $sql .= " AND c.category = :category";
    $params[':category'] = $categoryFilter;
}

if ($priorityFilter !== '') {
    $sql .= " AND c.priority = :priority";
    $params[':priority'] = $priorityFilter;
}

if ($statusFilter !== '') {
    $sql .= " AND c.status = :status";
    $params[':status'] = $statusFilter;
}

if ($dateFrom !== '') {
    $sql .= " AND DATE(c.created_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $sql .= " AND DATE(c.created_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$categories = getComplaintCategories();
$flash = getFlashMessage();
$pageTitle = 'Manage Complaints';
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>

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
                <h1 class="page-heading">Manage All Complaints</h1>
            </div>
            <div class="topbar-right">
                <span class="role-pill role-pill-admin">Administrator</span>
            </div>
        </header>

        <div class="content-body">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>" data-auto-dismiss="true">
                    <span><?php echo e($flash['message']); ?></span>
                    <button type="button" class="alert-close">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Multi-Criteria Search & Filter Bar -->
            <div class="filter-bar">
                <form method="GET" action="manage_complaints.php">
                    <div class="filter-grid" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));">
                        <div>
                            <label class="form-label" style="font-size: 0.8rem;">Search Keyword</label>
                            <input type="text" name="q" class="form-control" placeholder="Title, user, location..." value="<?php echo e($searchKeyword); ?>">
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.8rem;">Category</label>
                            <select name="category" class="form-control">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo e($cat); ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>>
                                        <?php echo e($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.8rem;">Priority</label>
                            <select name="priority" class="form-control">
                                <option value="">All Priorities</option>
                                <?php foreach (['Low', 'Medium', 'High', 'Urgent'] as $pr): ?>
                                    <option value="<?php echo e($pr); ?>" <?php echo $priorityFilter === $pr ? 'selected' : ''; ?>>
                                        <?php echo e($pr); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.8rem;">Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <?php foreach (['Submitted', 'Under Review', 'In Progress', 'Resolved', 'Rejected'] as $st): ?>
                                    <option value="<?php echo e($st); ?>" <?php echo $statusFilter === $st ? 'selected' : ''; ?>>
                                        <?php echo e($st); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.8rem;">From Date</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo e($dateFrom); ?>">
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.8rem;">To Date</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo e($dateTo); ?>">
                        </div>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px;">
                        <a href="manage_complaints.php" class="btn btn-secondary btn-sm">Reset Filters</a>
                        <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                    </div>
                </form>
            </div>

            <!-- Complaints Management Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <span>Matching Complaints (<?php echo count($complaints); ?>)</span>
                    </h2>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Complaint & Location</th>
                                <th>Citizen Info</th>
                                <th>Category</th>
                                <th>Date Filed</th>
                                <th>Quick Priority & Status Update</th>
                                <th style="text-align: right;">Full Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($complaints)): ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <p>No complaints matched your search and filter settings.</p>
                                        <a href="manage_complaints.php" class="btn btn-secondary btn-sm" style="margin-top: 8px;">Clear All Filters</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($complaints as $c): ?>
                                    <tr>
                                        <td><strong><?php echo e(formatComplaintId($c['id'])); ?></strong></td>
                                        <td style="max-width: 240px;">
                                            <div style="font-weight: 600;">
                                                <a href="complaint_details.php?id=<?php echo (int)$c['id']; ?>">
                                                    <?php echo e($c['title']); ?>
                                                </a>
                                            </div>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                                &#128205; <?php echo e($c['location']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo e($c['user_name']); ?></div>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo e($c['user_email']); ?></div>
                                        </td>
                                        <td><?php echo e($c['category']); ?></td>
                                        <td style="white-space: nowrap; font-size: 0.85rem;">
                                            <?php echo date('M d, Y', strtotime($c['created_at'])); ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="manage_complaints.php?<?php echo e(http_build_query($_GET)); ?>"
                                                  style="display: flex; align-items: center; gap: 6px;">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="quick_update" value="1">
                                                <input type="hidden" name="complaint_id" value="<?php echo (int)$c['id']; ?>">

                                                <select name="priority" class="form-control" style="padding: 5px 8px; font-size: 0.82rem; width: auto;">
                                                    <?php foreach (['Low', 'Medium', 'High', 'Urgent'] as $pr): ?>
                                                        <option value="<?php echo e($pr); ?>" <?php echo $c['priority'] === $pr ? 'selected' : ''; ?>>
                                                            <?php echo e($pr); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <select name="status" class="form-control" style="padding: 5px 8px; font-size: 0.82rem; width: auto;">
                                                    <?php foreach (['Submitted', 'Under Review', 'In Progress', 'Resolved', 'Rejected'] as $st): ?>
                                                        <option value="<?php echo e($st); ?>" <?php echo $c['status'] === $st ? 'selected' : ''; ?>>
                                                            <?php echo e($st); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <button type="submit" class="btn btn-secondary btn-sm" title="Save Priority & Status">
                                                    Save
                                                </button>
                                            </form>
                                        </td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="complaint_details.php?id=<?php echo (int)$c['id']; ?>" class="btn btn-primary btn-sm">
                                                View / Respond
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
