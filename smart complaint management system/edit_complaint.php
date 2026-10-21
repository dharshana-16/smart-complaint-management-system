<?php
require_once __DIR__ . '/includes/auth.php';
requireUser();

$userId = (int)$_SESSION['user_id'];
$complaintId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pdo = getDBConnection();

// Fetch complaint with ownership check
$stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$complaintId, $userId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlashMessage('danger', 'Complaint not found or you do not have permission to view it.');
    header('Location: my_complaints.php');
    exit;
}

// User can only edit if status is 'Submitted'
if ($complaint['status'] !== 'Submitted') {
    setFlashMessage('warning', 'Complaints that are under review, in progress, resolved, or rejected cannot be edited.');
    header('Location: complaint_details.php?id=' . $complaintId);
    exit;
}

$errors = [];
$categories = getComplaintCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token (CSRF).';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $category    = trim($_POST['category'] ?? '');
        $location    = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title === '' || strlen($title) < 5 || strlen($title) > 200) {
            $errors[] = 'Complaint Title is required (between 5 and 200 characters).';
        }

        if ($category === '' || !in_array($category, $categories)) {
            $errors[] = 'Please select a valid category.';
        }

        if ($location === '' || strlen($location) < 3 || strlen($location) > 200) {
            $errors[] = 'Specific location is required.';
        }

        if ($description === '' || strlen($description) < 15) {
            $errors[] = 'Description must be at least 15 characters.';
        }

        $imagePath = $complaint['image_path'];
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = handleComplaintImageUpload($_FILES['attachment']);
            if (!$uploadResult['success']) {
                $errors[] = $uploadResult['error'];
            } else {
                $imagePath = $uploadResult['filename'];
            }
        }

        if (empty($errors)) {
            $pdo->beginTransaction();
            try {
                $stmtUpdate = $pdo->prepare("
                    UPDATE complaints
                    SET title = ?, category = ?, location = ?, description = ?, image_path = ?
                    WHERE id = ? AND user_id = ?
                ");
                $stmtUpdate->execute([$title, $category, $location, $description, $imagePath, $complaintId, $userId]);

                // Record history
                $stmtHist = $pdo->prepare("
                    INSERT INTO complaint_history (complaint_id, changed_by, old_status, new_status, remarks)
                    VALUES (?, ?, 'Submitted', 'Submitted', 'Complaint details edited by user.')
                ");
                $stmtHist->execute([$complaintId, $userId]);

                $pdo->commit();

                setFlashMessage('success', 'Complaint details updated successfully.');
                header('Location: complaint_details.php?id=' . $complaintId);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Update complaint error: " . $e->getMessage());
                $errors[] = 'Failed to update complaint. Please try again.';
            }
        }
    }
}

$pageTitle = 'Edit Complaint ' . formatComplaintId($complaintId);
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/includes/user_sidebar.php'; ?>

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
                <h1 class="page-heading">Edit Complaint <?php echo e(formatComplaintId($complaintId)); ?></h1>
            </div>
            <div class="topbar-right">
                <a href="complaint_details.php?id=<?php echo $complaintId; ?>" class="btn btn-outline btn-sm">&larr; Back to Details</a>
            </div>
        </header>

        <div class="content-body">
            <div class="card" style="max-width: 860px; margin: 0 auto;">
                <div class="card-header">
                    <h2 class="card-title">Modify Complaint Information</h2>
                    <span class="badge badge-submitted">Status: Submitted</span>
                </div>

                <div class="card-body">
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

                    <form method="POST" action="edit_complaint.php?id=<?php echo $complaintId; ?>" enctype="multipart/form-data" novalidate>
                        <?php echo csrfField(); ?>

                        <div class="form-group">
                            <label for="title" class="form-label">Complaint Title <span class="required-star">*</span></label>
                            <input type="text" id="title" name="title" class="form-control"
                                   value="<?php echo e($complaint['title']); ?>" required>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="category" class="form-label">Category / Department <span class="required-star">*</span></label>
                                <select id="category" name="category" class="form-control" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo e($cat); ?>" <?php echo $complaint['category'] === $cat ? 'selected' : ''; ?>>
                                            <?php echo e($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="location" class="form-label">Location / Landmark <span class="required-star">*</span></label>
                                <input type="text" id="location" name="location" class="form-control"
                                       value="<?php echo e($complaint['location']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Detailed Description <span class="required-star">*</span></label>
                            <textarea id="description" name="description" class="form-control" rows="5" required><?php echo e($complaint['description']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="complaintImageInput" class="form-label">Update Photo Attachment</label>
                            <?php if (!empty($complaint['image_path'])): ?>
                                <div style="margin-bottom: 10px;">
                                    <span style="font-size:0.85rem;color:var(--text-muted);">Current Photo:</span><br>
                                    <img src="<?php echo e($complaint['image_path']); ?>" style="max-height: 120px; border-radius: 6px; margin-top: 4px;" alt="Current Attachment">
                                </div>
                            <?php endif; ?>
                            <input type="file" id="complaintImageInput" name="attachment" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                            <span class="form-hint">Choose a new file to replace the existing photo (optional).</span>
                            <span class="form-error" id="imageUploadError"></span>

                            <div id="imagePreviewContainer" style="display: none; margin-top: 14px;">
                                <div class="attachment-preview">
                                    <img id="imagePreviewElement" src="#" alt="New Preview">
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px; margin-top: 24px;">
                            <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
                            <a href="complaint_details.php?id=<?php echo $complaintId; ?>" class="btn btn-outline btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
