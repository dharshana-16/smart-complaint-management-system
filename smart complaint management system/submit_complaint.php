<?php
require_once __DIR__ . '/includes/auth.php';
requireUser();

$errors = [];
$formData = [
    'title'       => '',
    'category'    => '',
    'location'    => '',
    'description' => ''
];

$categories = getComplaintCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token (CSRF). Please refresh the page and try again.';
    } else {
        $formData['title']       = trim($_POST['title'] ?? '');
        $formData['category']    = trim($_POST['category'] ?? '');
        $formData['location']    = trim($_POST['location'] ?? '');
        $formData['description'] = trim($_POST['description'] ?? '');

        // Validations
        if ($formData['title'] === '' || strlen($formData['title']) < 5 || strlen($formData['title']) > 200) {
            $errors[] = 'Complaint Title is required (between 5 and 200 characters).';
        }

        if ($formData['category'] === '' || !in_array($formData['category'], $categories)) {
            $errors[] = 'Please select a valid complaint category.';
        }

        if ($formData['location'] === '' || strlen($formData['location']) < 3 || strlen($formData['location']) > 200) {
            $errors[] = 'Specific Location is required (e.g., Block A, 2nd Floor, Room 204).';
        }

        if ($formData['description'] === '' || strlen($formData['description']) < 15) {
            $errors[] = 'Detailed Description is required (at least 15 characters).';
        }

        // File upload handling
        $imagePath = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = handleComplaintImageUpload($_FILES['attachment']);
            if (!$uploadResult['success']) {
                $errors[] = $uploadResult['error'];
            } else {
                $imagePath = $uploadResult['filename'];
            }
        }

        // Save complaint to database
        if (empty($errors)) {
            $pdo = getDBConnection();
            $pdo->beginTransaction();

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO complaints (user_id, title, description, category, location, image_path, priority, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'Medium', 'Submitted')
                ");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $formData['title'],
                    $formData['description'],
                    $formData['category'],
                    $formData['location'],
                    $imagePath
                ]);

                $complaintId = (int)$pdo->lastInsertId();

                // Record initial complaint history
                $stmtHist = $pdo->prepare("
                    INSERT INTO complaint_history (complaint_id, changed_by, old_status, new_status, remarks)
                    VALUES (?, ?, NULL, 'Submitted', 'Complaint submitted by citizen.')
                ");
                $stmtHist->execute([
                    $complaintId,
                    $_SESSION['user_id']
                ]);

                $pdo->commit();

                setFlashMessage('success', 'Complaint ' . formatComplaintId($complaintId) . ' has been submitted successfully.');
                header('Location: complaint_details.php?id=' . $complaintId);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Complaint Submission Error: " . $e->getMessage());
                $errors[] = 'An error occurred while saving your complaint. Please try again.';
            }
        }
    }
}

$pageTitle = 'Submit Complaint';
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
                <h1 class="page-heading">Submit a Complaint</h1>
            </div>
            <div class="topbar-right">
                <span class="role-pill role-pill-user">Citizen Account</span>
            </div>
        </header>

        <div class="content-body">
            <div class="card" style="max-width: 860px; margin: 0 auto;">
                <div class="card-header">
                    <h2 class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Complaint Details Form</span>
                    </h2>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">All fields marked with <span class="required-star">*</span> are mandatory</span>
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

                    <form method="POST" action="submit_complaint.php" enctype="multipart/form-data" novalidate>
                        <?php echo csrfField(); ?>

                        <div class="form-group">
                            <label for="title" class="form-label">Complaint Title <span class="required-star">*</span></label>
                            <input type="text" id="title" name="title" class="form-control"
                                   placeholder="Brief summary of the issue (e.g., Streetlight not working on 5th cross)"
                                   value="<?php echo e($formData['title']); ?>" required>
                            <span class="form-hint">A concise heading that describes the nature of your grievance.</span>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="category" class="form-label">Category / Department <span class="required-star">*</span></label>
                                <select id="category" name="category" class="form-control" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo e($cat); ?>" <?php echo $formData['category'] === $cat ? 'selected' : ''; ?>>
                                            <?php echo e($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="location" class="form-label">Specific Location / Landmark <span class="required-star">*</span></label>
                                <input type="text" id="location" name="location" class="form-control"
                                       placeholder="e.g., Building C, 3rd Floor, Room 302"
                                       value="<?php echo e($formData['location']); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Detailed Description <span class="required-star">*</span></label>
                            <textarea id="description" name="description" class="form-control" rows="5"
                                      placeholder="Provide complete details including when it started, severity, and any impact..." required><?php echo e($formData['description']); ?></textarea>
                            <span class="form-hint">Minimum 15 characters. Be as specific as possible to assist quick resolution.</span>
                        </div>

                        <div class="form-group">
                            <label for="complaintImageInput" class="form-label">Supporting Photo Evidence <span style="font-weight: normal; color: var(--text-muted);">(Optional)</span></label>
                            <input type="file" id="complaintImageInput" name="attachment" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                            <span class="form-hint">Allowed formats: JPG, PNG, WEBP, GIF (Max size: 3MB).</span>
                            <span class="form-error" id="imageUploadError"></span>

                            <div id="imagePreviewContainer" style="display: none; margin-top: 14px;">
                                <div style="font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted);">Image Preview:</div>
                                <div class="attachment-preview">
                                    <img id="imagePreviewElement" src="#" alt="Preview">
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px; margin-top: 24px;">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Submit Complaint
                            </button>
                            <a href="user_dashboard.php" class="btn btn-outline btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
