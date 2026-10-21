<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pdo = getDBConnection();
$currentAdminId = (int)$_SESSION['user_id'];
$errors = [];

// Handle POST Actions: Toggle User Account Status OR Create Admin Account
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid security token (CSRF). Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. Activate / Deactivate User Account
        if ($action === 'toggle_status') {
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            $newStatus    = $_POST['new_status'] ?? '';

            if ($targetUserId === $currentAdminId) {
                $errors[] = 'You cannot deactivate your own active administrator account.';
            } elseif (!in_array($newStatus, ['active', 'inactive'], true)) {
                $errors[] = 'Invalid account status specified.';
            } else {
                $stmtUpd = $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?");
                $stmtUpd->execute([$newStatus, $targetUserId]);

                setFlashMessage('success', 'User account status updated to ' . strtoupper($newStatus) . '.');
                header('Location: manage_users.php');
                exit;
            }
        }

        // 2. Create New Administrator Account safely
        if ($action === 'create_admin') {
            $name     = trim($_POST['name'] ?? '');
            $email    = strtolower(trim($_POST['email'] ?? ''));
            $phone    = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($name === '' || strlen($name) < 2) {
                $errors[] = 'Administrator Name is required.';
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid Email Address is required.';
            }
            if ($phone === '' || !preg_match('/^[0-9+\-\s()]{7,25}$/', $phone)) {
                $errors[] = 'Valid Phone Number is required.';
            }
            if (strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters long.';
            }

            if (empty($errors)) {
                $stmtChk = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmtChk->execute([$email]);
                if ($stmtChk->fetch()) {
                    $errors[] = 'A user with this email address already exists.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmtIns = $pdo->prepare("
                        INSERT INTO users (name, email, phone, password_hash, role, account_status)
                        VALUES (?, ?, ?, ?, 'admin', 'active')
                    ");
                    $stmtIns->execute([$name, $email, $phone, $hash]);

                    setFlashMessage('success', 'New administrator account (' . $email . ') created successfully.');
                    header('Location: manage_users.php');
                    exit;
                }
            }
        }
    }
}

// Filters
$searchQuery  = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$roleFilter   = trim($_GET['role'] ?? '');

$sql = "
    SELECT u.id, u.name, u.email, u.phone, u.role, u.account_status, u.created_at,
           COUNT(c.id) AS complaint_count
    FROM users u
    LEFT JOIN complaints c ON u.id = c.user_id
    WHERE 1=1
";
$params = [];

if ($searchQuery !== '') {
    $sql .= " AND (u.name LIKE :kw OR u.email LIKE :kw OR u.phone LIKE :kw)";
    $params[':kw'] = '%' . $searchQuery . '%';
}

if ($statusFilter !== '') {
    $sql .= " AND u.account_status = :status";
    $params[':status'] = $statusFilter;
}

if ($roleFilter !== '') {
    $sql .= " AND u.role = :role";
    $params[':role'] = $roleFilter;
}

$sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$flash = getFlashMessage();
$pageTitle = 'Manage Users';
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/includes/admin_sidebar.php'; ?>

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
                <h1 class="page-heading">User & Account Management</h1>
            </div>
            <div class="topbar-right">
                <span class="role-pill role-pill-admin">Administrator</span>
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

            <!-- Search & Filter Bar -->
            <div class="filter-bar">
                <form method="GET" action="manage_users.php" class="filter-grid">
                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Search Name, Email, or Phone</label>
                        <input type="text" name="q" class="form-control" placeholder="Search users..." value="<?php echo e($searchQuery); ?>">
                    </div>

                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Role</label>
                        <select name="role" class="form-control">
                            <option value="">All Roles</option>
                            <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>>Standard User</option>
                            <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Account Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive / Deactivated</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        <a href="manage_users.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Registered Accounts (<?php echo count($users); ?>)</h2>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Email Address</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Complaints</th>
                                <th>Account Status</th>
                                <th>Joined On</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="9" class="empty-state">No user accounts match the filter criteria.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td>#<?php echo (int)$u['id']; ?></td>
                                        <td style="font-weight: 600;"><?php echo e($u['name']); ?></td>
                                        <td><?php echo e($u['email']); ?></td>
                                        <td><?php echo e($u['phone']); ?></td>
                                        <td>
                                            <span class="role-pill <?php echo $u['role'] === 'admin' ? 'role-pill-admin' : 'role-pill-user'; ?>">
                                                <?php echo ucfirst(e($u['role'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($u['role'] === 'user'): ?>
                                                <a href="manage_complaints.php?q=<?php echo urlencode($u['email']); ?>">
                                                    <?php echo (int)$u['complaint_count']; ?> complaints
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted);">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($u['account_status'] === 'active'): ?>
                                                <span class="badge badge-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-inactive">Deactivated</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                        <td style="text-align: right;">
                                            <?php if ((int)$u['id'] === $currentAdminId): ?>
                                                <span style="font-size: 0.8rem; color: var(--text-muted);">Current Admin</span>
                                            <?php else: ?>
                                                <?php if ($u['account_status'] === 'active'): ?>
                                                    <form method="POST" action="manage_users.php" style="display:inline;"
                                                          data-confirm="Are you sure you want to deactivate <?php echo e($u['name']); ?>'s account? They will not be able to log in.">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                                        <input type="hidden" name="new_status" value="inactive">
                                                        <button type="submit" class="btn btn-danger btn-sm">Deactivate</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="manage_users.php" style="display:inline;">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="action" value="toggle_status">
                                                        <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                                        <input type="hidden" name="new_status" value="active">
                                                        <button type="submit" class="btn btn-success btn-sm">Activate</button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Safe Provisioning of New Administrator Account -->
            <div class="card" style="max-width: 720px;">
                <div class="card-header">
                    <h2 class="card-title">Provision New Administrator Account</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="manage_users.php">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="create_admin">

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Administrator Full Name <span class="required-star">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g., Jane Doe" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address <span class="required-star">*</span></label>
                                <input type="email" name="email" class="form-control" placeholder="e.g., jane.admin@smartcomplaint.com" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number <span class="required-star">*</span></label>
                                <input type="tel" name="phone" class="form-control" placeholder="e.g., +1-555-0199" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Initial Password <span class="required-star">*</span></label>
                                <input type="password" name="password" class="form-control" placeholder="Min. 6 characters" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Administrator</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
