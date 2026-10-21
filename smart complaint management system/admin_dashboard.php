<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pdo = getDBConnection();

// Fetch overall complaint statistics
$stmtStats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status IN ('Submitted', 'Under Review') THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected,
        SUM(CASE WHEN priority = 'Urgent' AND status NOT IN ('Resolved', 'Rejected') THEN 1 ELSE 0 END) AS urgent_open
    FROM complaints
");
$stats = $stmtStats->fetch() ?: [
    'total' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0, 'rejected' => 0, 'urgent_open' => 0
];

// Fetch total users count
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

// Fetch recent 8 complaints with user details
$stmtRecent = $pdo->query("
    SELECT c.id, c.title, c.category, c.priority, c.status, c.created_at, u.name AS user_name, u.email AS user_email
    FROM complaints c
    INNER JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 8
");
$recentComplaints = $stmtRecent->fetchAll();

// Fetch complaints count by category
$stmtCat = $pdo->query("
    SELECT category, COUNT(*) AS cnt
    FROM complaints
    GROUP BY category
    ORDER BY cnt DESC
");
$categoryBreakdown = $stmtCat->fetchAll();

$flash = getFlashMessage();
$pageTitle = 'Admin Dashboard';
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
                <h1 class="page-heading">Administrator Dashboard</h1>
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

            <!-- Core 4 Summary KPI Cards Required -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <p>Total Complaints</p>
                        <h3><?php echo (int)$stats['total']; ?></h3>
                    </div>
                    <div class="stat-icon stat-icon-blue">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <p>Pending (Submitted / Review)</p>
                        <h3><?php echo (int)$stats['pending']; ?></h3>
                    </div>
                    <div class="stat-icon stat-icon-amber">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <p>Complaints In Progress</p>
                        <h3><?php echo (int)$stats['in_progress']; ?></h3>
                    </div>
                    <div class="stat-icon stat-icon-sky">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <p>Resolved Complaints</p>
                        <h3><?php echo (int)$stats['resolved']; ?></h3>
                    </div>
                    <div class="stat-icon stat-icon-green">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Secondary Operational Strip -->
            <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); margin-top: -8px;">
                <div class="stat-card" style="padding: 16px 22px;">
                    <div class="stat-info">
                        <p>Open Urgent Priority Issues</p>
                        <h3 style="font-size: 1.45rem; color: #dc2626;"><?php echo (int)$stats['urgent_open']; ?></h3>
                    </div>
                    <a href="manage_complaints.php?priority=Urgent" class="btn btn-outline btn-sm">Filter Urgent</a>
                </div>

                <div class="stat-card" style="padding: 16px 22px;">
                    <div class="stat-info">
                        <p>Registered Citizen Users</p>
                        <h3 style="font-size: 1.45rem;"><?php echo $totalUsers; ?></h3>
                    </div>
                    <a href="manage_users.php" class="btn btn-outline btn-sm">Manage Users</a>
                </div>

                <div class="stat-card" style="padding: 16px 22px;">
                    <div class="stat-info">
                        <p>Analytical Summary Reports</p>
                        <h3 style="font-size: 1.1rem; color: var(--primary-700);">Metrics & Export</h3>
                    </div>
                    <a href="reports.php" class="btn btn-primary btn-sm">Open Reports</a>
                </div>
            </div>

            <div class="details-layout" style="grid-template-columns: 1.8fr 1fr;">
                <!-- Latest Complaints Table -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Recent Citizen Complaints</h2>
                        <a href="manage_complaints.php" class="btn btn-outline btn-sm">View & Filter All &rarr;</a>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title & Citizen</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentComplaints)): ?>
                                    <tr>
                                        <td colspan="6" class="empty-state">No complaints found in the system.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentComplaints as $c): ?>
                                        <tr>
                                            <td><strong><?php echo e(formatComplaintId($c['id'])); ?></strong></td>
                                            <td>
                                                <div style="font-weight: 600;">
                                                    <a href="complaint_details.php?id=<?php echo (int)$c['id']; ?>">
                                                        <?php echo e($c['title']); ?>
                                                    </a>
                                                </div>
                                                <div style="font-size: 0.8rem; color: var(--text-muted);">
                                                    By <?php echo e($c['user_name']); ?> &bull; <?php echo date('M d, Y', strtotime($c['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td><?php echo e($c['category']); ?></td>
                                            <td>
                                                <span class="badge <?php echo e(getPriorityBadgeClass($c['priority'])); ?>">
                                                    <?php echo e($c['priority']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo e(getStatusBadgeClass($c['status'])); ?>">
                                                    <?php echo e($c['status']); ?>
                                                </span>
                                            </td>
                                            <td style="text-align: right;">
                                                <a href="complaint_details.php?id=<?php echo (int)$c['id']; ?>" class="btn btn-primary btn-sm">
                                                    Manage
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Category Breakdown Card -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Complaints by Category</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categoryBreakdown)): ?>
                            <p style="color: var(--text-muted);">No category data available.</p>
                        <?php else: ?>
                            <?php
                            $maxTotal = max(1, (int)$stats['total']);
                            foreach ($categoryBreakdown as $row):
                                $pct = round(((int)$row['cnt'] / $maxTotal) * 100);
                            ?>
                                <div style="margin-bottom: 16px;">
                                    <div style="display: flex; justify-content: space-between; font-size: 0.88rem; font-weight: 600;">
                                        <span><?php echo e($row['category']); ?></span>
                                        <span><?php echo (int)$row['cnt']; ?> (<?php echo $pct; ?>%)</span>
                                    </div>
                                    <div class="progress-bar-wrap">
                                        <div class="progress-bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
