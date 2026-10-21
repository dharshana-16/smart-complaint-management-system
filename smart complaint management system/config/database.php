<?php
/**
 * Smart Complaint Management System
 * Database Connection Configuration (PDO)
 *
 * Configuration Instructions:
 * 1. Make sure Apache and MySQL are running in XAMPP Control Panel.
 * 2. Import database.sql into phpMyAdmin (creates 'smart_complaint_db').
 * 3. Default XAMPP credentials are DB_USER = 'root' and DB_PASS = ''.
 *    Update these constants below if your MySQL server uses a custom password.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_complaint_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP MySQL password is empty
define('DB_CHARSET', 'utf8mb4');

// Application Constants
define('APP_NAME', 'Smart Complaint Management System');
define('APP_URL', 'http://localhost/SmartComplaintManagement');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 3 * 1024 * 1024); // 3 MB max upload size

/**
 * Returns a singleton PDO database connection instance.
 *
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on SQL errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return associative arrays
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error internally and show user-friendly message without leaking sensitive credentials
            error_log("Database Connection Error: " . $e->getMessage());
            die('<div style="font-family:Segoe UI,sans-serif;max-width:650px;margin:60px auto;padding:30px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:10px;">
                <h2 style="margin-top:0;">Database Connection Error</h2>
                <p>Could not connect to the <strong>' . htmlspecialchars(DB_NAME) . '</strong> database.</p>
                <p><strong>How to fix this:</strong></p>
                <ol>
                    <li>Ensure <strong>MySQL</strong> is started in your XAMPP Control Panel.</li>
                    <li>Open <a href="http://localhost/SmartComplaintManagement/setup.php" style="color:#842029;font-weight:bold;">Automatic Database Setup (setup.php)</a> or import <code>database.sql</code> via <a href="http://localhost/phpmyadmin" target="_blank" style="color:#842029;font-weight:bold;">phpMyAdmin</a>.</li>
                    <li>Verify your credentials in <code>config/database.php</code>.</li>
                </ol>
            </div>');
        }
    }

    return $pdo;
}
?>
