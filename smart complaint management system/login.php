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
            $errors[] = 'Please enter both your email address and password.';
        } else {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role, account_status FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Invalid email address or password.';
            } elseif ($user['account_status'] !== 'active') {
                $errors[] = 'Your account has been deactivated by an administrator. Please contact support.';
            } else {
                // Prevent session fixation
                session_regenerate_id(true);
                $_SESSION['user_id']    = (int)$user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role'];

                if ($user['role'] === 'admin') {
                    setFlashMessage('success', 'Welcome back, ' . $user['name'] . '!');
                    header('Location: admin_dashboard.php');
                    exit;
                }

                setFlashMessage('success', 'Welcome back, ' . $user['name'] . '!');
                header('Location: user_dashboard.php');
                exit;
            }
        }
    }
}

$flash = getFlashMessage();
$pageTitle = 'User Login';
$bodyClass = 'auth-page';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="auth-wrapper">
    <div class="auth-container" style="margin: 0 auto;">
        <div class="auth-card">
            <div class="auth-header">
                <div class="brand-logo-circle">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                </div>
                <h1>User Sign In</h1>
                <p>Access your complaint dashboard and track updates</p>
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

            <form method="POST" action="login.php" novalidate>
                <?php echo csrfField(); ?>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address <span class="required-star">*</span></label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="Enter your registered email"
                           value="<?php echo e($email); ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password <span class="required-star">*</span></label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 8px;">
                    Sign In
                </button>
            </form>

            <div class="auth-footer">
                <p style="margin-bottom: 8px;">Don't have an account? <a href="register.php" style="font-weight: 700;">Register here</a></p>
                <p style="font-size: 0.85rem;">Are you an administrator? <a href="admin_login.php">Go to Admin Login &rarr;</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
