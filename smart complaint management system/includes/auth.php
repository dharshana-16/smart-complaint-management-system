<?php
/**
 * Smart Complaint Management System
 * Authentication, Session Handling, CSRF Protection, and Helper Functions
 */

require_once __DIR__ . '/../config/database.php';

// Configure secure session parameters before starting the session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Generate or retrieve CSRF token for the current session
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden CSRF input field for forms
 */
function csrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify submitted CSRF token
 */
function verifyCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set a flash message in session
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash message
 */
function getFlashMessage(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if any user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if current user is an administrator
 */
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if current user is a standard user
 */
function isRegularUser(): bool {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user';
}

/**
 * Require standard user login (or verify active status in database)
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlashMessage('warning', 'Please log in to access this page.');
        header('Location: login.php');
        exit;
    }

    // Verify account is still active in DB
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT account_status, role, name, email FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || $user['account_status'] !== 'active') {
            session_unset();
            session_destroy();
            session_start();
            setFlashMessage('danger', 'Your account has been deactivated. Please contact the administrator.');
            header('Location: login.php');
            exit;
        }

        // Keep session info fresh
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
    } catch (PDOException $e) {
        error_log("Auth Check Error: " . $e->getMessage());
    }
}

/**
 * Require regular user role for user-specific pages
 */
function requireUser(): void {
    requireLogin();
    if (isAdmin()) {
        header('Location: admin_dashboard.php');
        exit;
    }
}

/**
 * Require administrator privileges
 */
function requireAdmin(): void {
    if (!isLoggedIn()) {
        setFlashMessage('warning', 'Administrator login is required to access that area.');
        header('Location: admin_login.php');
        exit;
    }

    requireLogin();

    if (!isAdmin()) {
        setFlashMessage('danger', 'Access denied. Unauthorized permissions.');
        header('Location: user_dashboard.php');
        exit;
    }
}

/**
 * Safe HTML escaping helper
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format Complaint ID nicely (e.g., #CMP-00012)
 */
function formatComplaintId(int $id): string {
    return '#CMP-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

/**
 * Get CSS badge class for complaint status
 */
function getStatusBadgeClass(string $status): string {
    switch ($status) {
        case 'Submitted':
            return 'badge-submitted';
        case 'Under Review':
            return 'badge-review';
        case 'In Progress':
            return 'badge-progress';
        case 'Resolved':
            return 'badge-resolved';
        case 'Rejected':
            return 'badge-rejected';
        default:
            return 'badge-submitted';
    }
}

/**
 * Get CSS badge class for complaint priority
 */
function getPriorityBadgeClass(string $priority): string {
    switch ($priority) {
        case 'Low':
            return 'badge-priority-low';
        case 'Medium':
            return 'badge-priority-medium';
        case 'High':
            return 'badge-priority-high';
        case 'Urgent':
            return 'badge-priority-urgent';
        default:
            return 'badge-priority-medium';
    }
}

/**
 * Get predefined list of complaint categories
 */
function getComplaintCategories(): array {
    return [
        'Infrastructure & Facilities',
        'IT & Network Services',
        'Water & Sanitation',
        'Electricity & Power',
        'Billing & Finance',
        'Security & Safety',
        'Customer Service',
        'Environmental Issue',
        'Other'
    ];
}

/**
 * Handle secure image upload for complaints
 *
 * @param array $file $_FILES['attachment']
 * @return array ['success' => bool, 'filename' => ?string, 'error' => ?string]
 */
function handleComplaintImageUpload(array $file): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'filename' => null, 'error' => 'Invalid file upload parameters.'];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null, 'error' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'File upload failed with error code ' . $file['error'] . '.'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'filename' => null, 'error' => 'Image size exceeds the 3MB maximum limit.'];
    }

    // Verify actual MIME type using finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $allowedMimeTypes = [
        'jpg'  => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif'
    ];

    $ext = array_search($mimeType, $allowedMimeTypes, true);
    if ($ext === false) {
        return ['success' => false, 'filename' => null, 'error' => 'Invalid file format. Only JPG, PNG, WEBP, and GIF images are allowed.'];
    }

    // Also verify image dimensions to ensure it is a valid image
    if (@getimagesize($file['tmp_name']) === false) {
        return ['success' => false, 'filename' => null, 'error' => 'Uploaded file is not a valid image.'];
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $safeFilename = 'cmp_' . bin2hex(random_bytes(10)) . '_' . time() . '.' . $ext;
    $destination = UPLOAD_DIR . $safeFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'filename' => null, 'error' => 'Failed to save uploaded image on the server.'];
    }

    return ['success' => true, 'filename' => 'uploads/' . $safeFilename, 'error' => null];
}
?>
