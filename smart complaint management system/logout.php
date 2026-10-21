<?php
require_once __DIR__ . '/includes/auth.php';

// Clear all session variables
$_SESSION = [];

// Delete the session cookie if used
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Start fresh session to set flash message
session_start();
setFlashMessage('info', 'You have been securely logged out.');
header('Location: login.php');
exit;
?>
