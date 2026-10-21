<?php
require_once __DIR__ . '/includes/auth.php';

// Redirect already logged-in users
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin_dashboard.php' : 'user_dashboard.php'));
    exit;
}

$errors = [];
$formData = [
    'name'  => '',
    'email' => '',
    'phone' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token (CSRF). Please refresh the page and try again.';
    } else {
        $formData['name']  = trim($_POST['name'] ?? '');
        $formData['email'] = strtolower(trim($_POST['email'] ?? ''));
        $formData['phone'] = trim($_POST['phone'] ?? '');
        $password          = $_POST['password'] ?? '';
        $confirmPassword   = $_POST['confirm_password'] ?? '';

        // Server-side validation
        if ($formData['name'] === '' || strlen($formData['name']) < 2 || strlen($formData['name']) > 100) {
            $errors[] = 'Full Name is required and must be between 2 and 100 characters.';
        }

        if ($formData['email'] === '' || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($formData['phone'] === '' || !preg_match('/^[0-9+\-\s()]{7,25}$/', $formData['phone'])) {
            $errors[] = 'Please enter a valid phone number (7 to 25 digits).';
        }

        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        // Check duplicate email
        if (empty($errors)) {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$formData['email']]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email address is already registered.';
            }
        }

        // Insert new user
        if (empty($errors)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, phone, password_hash, role, account_status)
                VALUES (?, ?, ?, ?, 'user', 'active')
            ");
            $stmt->execute([
                $formData['name'],
                $formData['email'],
                $formData['phone'],
                $passwordHash
            ]);

            setFlashMessage('success', 'Account registered successfully! You can now log in.');
            header('Location: login.php');
            exit;
        }
    }
}

$pageTitle = 'Create Account';
$bodyClass = 'auth-page';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="auth-wrapper">
    <div class="auth-container" style="max-width: 520px; margin: 0 auto;">
        <div class="auth-card">
            <div class="auth-header">
                <div class="brand-logo-circle">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                </div>
                <h1>Create User Account</h1>
                <p>Register to submit and track your complaints online</p>
            </div>

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

            <div class="alert alert-danger" id="clientValidationError" style="display: none;">
                <span></span>
            </div>

            <form method="POST" action="register.php" id="registerForm" novalidate>
                <?php echo csrfField(); ?>

                <div class="form-group">
                    <label for="name" class="form-label">Full Name <span class="required-star">*</span></label>
                    <input type="text" id="name" name="name" class="form-control"
                           placeholder="e.g., John Miller"
                           value="<?php echo e($formData['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address <span class="required-star">*</span></label>
                    <input type="email" id="email" name="email" class="form-control"
                           placeholder="e.g., john@example.com"
                           value="<?php echo e($formData['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">Phone Number <span class="required-star">*</span></label>
                    <input type="tel" id="phone" name="phone" class="form-control"
                           placeholder="e.g., +1-555-0142"
                           value="<?php echo e($formData['phone']); ?>" required>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="password" class="form-label">Password <span class="required-star">*</span></label>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Min. 6 characters" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="required-star">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                               placeholder="Re-enter password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 6px;">
                    Create Account
                </button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="login.php" style="font-weight: 700;">Sign in here</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
