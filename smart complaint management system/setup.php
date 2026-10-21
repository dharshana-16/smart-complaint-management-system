<?php
/**
 * Smart Complaint Management System
 * Automatic Database Setup & First Admin Initializer (setup.php)
 *
 * This script safely creates the database `smart_complaint_db`, imports the tables,
 * and sets up the initial administrator and sample user accounts with fresh bcrypt hashes.
 */

define('DB_HOST_SETUP', 'localhost');
define('DB_USER_SETUP', 'root');
define('DB_PASS_SETUP', '');
define('DB_NAME_SETUP', 'smart_complaint_db');

$message = '';
$status = '';
$isCli = (php_sapi_name() === 'cli');

function initializeDatabase(): array {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST_SETUP . ";charset=utf8mb4",
            DB_USER_SETUP,
            DB_PASS_SETUP,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) {
            return ['success' => false, 'message' => 'database.sql file not found in project root.'];
        }

        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);

        // Generate fresh bcrypt hashes using PHP's password_hash() to guarantee compatibility
        $adminHash = password_hash('Admin@12345', PASSWORD_DEFAULT);
        $userHash  = password_hash('User@12345', PASSWORD_DEFAULT);

        $pdo->exec("USE `" . DB_NAME_SETUP . "`");

        $stmtAdmin = $pdo->prepare("UPDATE users SET password_hash = ? WHERE role = 'admin'");
        $stmtAdmin->execute([$adminHash]);

        $stmtUser = $pdo->prepare("UPDATE users SET password_hash = ? WHERE role = 'user'");
        $stmtUser->execute([$userHash]);

        // Also ensure database.sql contains valid bcrypt hashes for direct phpMyAdmin imports
        if (is_writable($sqlFile)) {
            $updatedSql = preg_replace(
                "/'admin@smartcomplaint\.com', '\+1-555-0100', '[^']+'/",
                "'admin@smartcomplaint.com', '+1-555-0100', '" . $adminHash . "'",
                $sql
            );
            $updatedSql = preg_replace(
                "/'(john|sarah|david)@example\.com', '(\+1-555-[0-9]+)', '[^']+'/",
                "'$1@example.com', '$2', '" . $userHash . "'",
                $updatedSql
            );
            if ($updatedSql) {
                file_put_contents($sqlFile, $updatedSql);
            }
        }

        return [
            'success' => true,
            'message' => 'Database "smart_complaint_db" and all tables were created and seeded successfully!',
            'admin_hash' => $adminHash,
            'user_hash' => $userHash
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Database Setup Error: ' . $e->getMessage()
        ];
    }
}

if ($isCli || $_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['auto'])) {
    $result = initializeDatabase();
    if ($isCli) {
        echo ($result['success'] ? "[SUCCESS] " : "[ERROR] ") . $result['message'] . PHP_EOL;
        exit($result['success'] ? 0 : 1);
    }
    $status = $result['success'] ? 'success' : 'danger';
    $message = $result['message'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Smart Complaint Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container" style="max-width: 620px;">
        <div class="auth-card">
            <div class="auth-header">
                <div class="brand-logo-circle">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <h1>Database Setup Wizard</h1>
                <p>Initialize or reset the <code>smart_complaint_db</code> MySQL database</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($status); ?>">
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px; margin-bottom: 20px; font-size: 0.93rem;">
                <h3 style="margin-top: 0; color: #1e3a8a; font-size: 1rem;">Default Test Credentials Created:</h3>
                <ul style="margin: 8px 0 0; padding-left: 20px; line-height: 1.7;">
                    <li><strong>Administrator:</strong> <code>admin@smartcomplaint.com</code> / Password: <code>Admin@12345</code></li>
                    <li><strong>Standard User 1:</strong> <code>john@example.com</code> / Password: <code>User@12345</code></li>
                    <li><strong>Standard User 2:</strong> <code>sarah@example.com</code> / Password: <code>User@12345</code></li>
                </ul>
            </div>

            <form method="POST" action="setup.php">
                <button type="submit" class="btn btn-primary btn-block">
                    Initialize / Reset Database Now
                </button>
            </form>

            <div class="auth-footer" style="margin-top: 20px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <a href="index.php" class="btn btn-outline btn-sm">Go to Home Page</a>
                <a href="login.php" class="btn btn-outline btn-sm">User Login</a>
                <a href="admin_login.php" class="btn btn-outline btn-sm">Admin Login</a>
            </div>
        </div>
    </div>
</body>
</html>
