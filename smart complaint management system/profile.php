<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = (int)$_SESSION['user_id'];
$isAdminUser = isAdmin();
$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT id, name, email, phone, password_hash, role, account_status, created_at FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token (CSRF). Please try again.';
    } else {
        $formType = $_POST['form_type'] ?? '';

        // 1. Update Profile Information
        if ($formType === 'update_profile') {
            $name  = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if ($name === '' || strlen($name) < 2 || strlen($name) > 100) {
                $errors[] = 'Full Name must be between 2 and 100 characters.';
            }

            if ($phone === '' || !preg_match('/^[0-9+\-\s()]{7,25}$/', $phone)) {
                $errors[] = 'Please enter a valid phone number.';
            }

            if (empty($errors)) {
                $stmtUpd = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
                $stmtUpd->execute([$name, $phone, $userId]);

                $_SESSION['user_name'] = $name;
                setFlashMessage('success', 'Your profile details have been updated successfully.');
                header('Location: profile.php');
                exit;
            }
        }

        // 2. Change Password
        if ($formType === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword     = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (!password_verify($currentPassword, $user['password_hash'])) {
                $errors[] = 'Your current password was entered incorrectly.';
            } elseif (strlen($newPassword) < 6) {
                $errors[] = 'New password must be at least 6 characters long.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New password and confirmation do not match.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmtPwd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmtPwd->execute([$newHash, $userId]);

                setFlashMessage('success', 'Your password has been changed securely.');
                header('Location: profile.php');
                exit;
            }
        }
    }
}

$flash = getFlashMessage();
$pageTitle = 'My Profile';
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
                <h1 class="page-heading">Account Profile & Security</h1>
            </div>
            <div class="topbar-right">
                <span class="role-pill <?php echo $isAdminUser ? 'role-pill-admin' : 'role-pill-user'; ?>">
                    <?php echo $isAdminUser ? 'Administrator' : 'Citizen Account'; ?>
                </span>
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

            <div class="details-layout" style="grid-template-columns: 1fr 1fr;">
                <!-- Update Personal Information -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Personal Information</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="profile.php">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="form_type" value="update_profile">

                            <div class="form-group">
                                <label class="form-label">Email Address (Read-Only)</label>
                                <input type="email" class="form-control" value="<?php echo e($user['email']); ?>" disabled style="background: #f1f5f9; cursor: not-allowed;">
                                <span class="form-hint">Email address serves as your unique login identifier.</span>
                            </div>

                            <div class="form-group">
                                <label for="name" class="form-label">Full Name <span class="required-star">*</span></label>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo e($user['name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number <span class="required-star">*</span></label>
                                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo e($user['phone']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Member Since</label>
                                <input type="text" class="form-control" value="<?php echo date('F d, Y', strtotime($user['created_at'])); ?>" disabled style="background: #f1f5f9;">
                            </div>

                            <button type="submit" class="btn btn-primary">Save Profile Changes</button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Change Password</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="profile.php">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="form_type" value="change_password">

                            <div class="form-group">
                                <label for="current_password" class="form-label">Current Password <span class="required-star">*</span></label>
                                <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Enter your current password" required>
                            </div>

                            <div class="form-group">
                                <label for="new_password" class="form-label">New Password <span class="required-star">*</span></label>
                                <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimum 6 characters" required>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm New Password <span class="required-star">*</span></label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
