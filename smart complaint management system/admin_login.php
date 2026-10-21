<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token (CSRF). Please refresh and try again.';
    } else {
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $errors[] = 'Please enter both administrator email and password.';
        } else {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role, account_status FROM users WHERE email = ? AND role = 'admin' LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if (!$admin || !password_verify($password, $admin['password_hash'])) {
                $errors[] = 'Invalid administrator credentials.';
            } elseif ($admin['account_status'] !== 'active') {
                $errors[] = 'This administrator account is currently inactive.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']    = (int)$admin['id'];
                $_SESSION['user_name']  = $admin['name'];
                $_SESSION['user_email'] = $admin['email'];
                $_SESSION['user_role']  = 'admin';

                setFlashMessage('success', 'Logged into Administrator Control Panel.');
                header('Location: admin_dashboard.php');
                exit;
            }
        }
    }
}

$flash = getFlashMessage();
$pageTitle = 'Administrator Login';
$bodyClass = 'auth-page';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="auth-wrapper">
    <div class="auth-container" style="margin: 0 auto;">
        <div class="auth-card">
            <div class="auth-header">
                <div class="brand-logo-circle" style="background: linear-gradient(135deg, #4f46e5, #1e3a8a);">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <h1>Administrator Portal</h1>
                <p>Authorized access for complaint management & oversight</p>
            </div>

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
                            <div><?php echo e($err); ?></div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="alert-close">&times;</button>
                </div>
            <?php endif; ?>

            <form method="POST" action="admin_login.php" novalidate>
                <?php echo csrfField(); ?>

                <div class="form-group">
                    <label for="email" class="form-label">Administrator Email <span class="required-star">*</span></label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="Enter administrator email"
                           value="<?php echo e($email); ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password <span class="required-star">*</span></label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Enter administrator password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 8px;">
                    Login to Admin Panel
                </button>
            </form>

            <div class="auth-footer">
                <a href="login.php">&larr; Back to Standard User Login</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
