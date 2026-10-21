<?php
require_once __DIR__ . '/includes/auth.php';
requireUser();

$userId = (int)$_SESSION['user_id'];
$pdo = getDBConnection();

// Fetch summary metrics for current user
$stmtStats = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'Submitted' THEN 1 ELSE 0 END) AS submitted,
        SUM(CASE WHEN status IN ('Under Review', 'In Progress') THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected
    FROM complaints
    WHERE user_id = ?
");
$stmtStats->execute([$userId]);
$metrics = $stmtStats->fetch() ?: [
    'total' => 0,
    'submitted' => 0,
    'in_progress' => 0,
    'resolved' => 0,
    'rejected' => 0
];

// Fetch recent 5 complaints
$stmtRecent = $pdo->prepare("
    SELECT id, title, category, location, priority, status, created_at
    FROM complaints
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$stmtRecent->execute([$userId]);
$recentComplaints = $stmtRecent->fetchAll();

$flash = getFlashMessage();
$pageTitle = 'User Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/includes/user_sidebar.php'; ?>

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
                <h1 class="page-heading">User Dashboard</h1>
            </div>
            <div class="topbar-right">
                <span class="role-pill role-pill-user">Citizen Account</span>
            </div>
        </header>

        <div class="content-body">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>" data-auto-dismiss="true">
                    <span><?php echo e($flash['message']); ?></span>
                    <button type="button" class="alert-close">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Metrics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <p>Total Complaints</p>
                        <h3><?php echo (int)$metrics['total']; ?></h3>
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
                        <p>Newly Submitted</p>
                        <h3><?php echo (int)$metrics['submitted']; ?></h3>
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
                        <p>In Progress / Review</p>
                        <h3><?php echo (int)$metrics['in_progress']; ?></h3>
                    </div>
                    <div class="stat-icon stat-icon-sky">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <p>Resolved</p>
                        <h3><?php echo (int)$metrics['resolved']; ?></h3>
                    </div>
                    <div class="stat-icon stat-icon-green">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Action Prompt Banner -->
            <div class="card" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-color: #bfdbfe;">
                <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h2 style="font-size: 1.22rem; color: #1e3a8a; margin-bottom: 4px;">Encountered an issue or civic grievance?</h2>
                        <p style="color: #3b82f6; font-size: 0.95rem; margin: 0;">Submit a new complaint with location details and photos for prompt administrative action.</p>
                    </div>
                    <a href="submit_complaint.php" class="btn btn-primary btn-lg">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span>Submit New Complaint</span>
                    </a>
                </div>
            </div>

            <!-- Recent Complaints Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>Recent Complaints</span>
                    </h2>
                    <a href="my_complaints.php" class="btn btn-outline btn-sm">View All Complaints &rarr;</a>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Complaint ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Submitted On</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentComplaints)): ?>
                                <tr>
                                    <td colspan="7" class="empty-state">
                                        <p>You have not submitted any complaints yet.</p>
                                        <a href="submit_complaint.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">Submit Your First Complaint</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentComplaints as $c): ?>
                                    <tr>
                                        <td><strong><?php echo e(formatComplaintId($c['id'])); ?></strong></td>
                                        <td style="max-width: 260px; font-weight: 600;">
                                            <a href="complaint_details.php?id=<?php echo (int)$c['id']; ?>">
                                                <?php echo e($c['title']); ?>
                                            </a>
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
                                        <td><?php echo date('M d, Y h:i A', strtotime($c['created_at'])); ?></td>
                                        <td style="text-align: right;">
                                            <a href="complaint_details.php?id=<?php echo (int)$c['id']; ?>" class="btn btn-outline btn-sm">
                                                View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
