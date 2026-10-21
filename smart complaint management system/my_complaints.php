<?php
require_once __DIR__ . '/includes/auth.php';
requireUser();

$userId = (int)$_SESSION['user_id'];
$pdo = getDBConnection();

// Filters & Search
$statusFilter   = trim($_GET['status'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');
$searchKeyword  = trim($_GET['q'] ?? '');

$sql = "SELECT id, title, category, location, priority, status, created_at, updated_at
        FROM complaints
        WHERE user_id = :user_id";
$params = [':user_id' => $userId];

if ($statusFilter !== '') {
    $sql .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

if ($categoryFilter !== '') {
    $sql .= " AND category = :category";
    $params[':category'] = $categoryFilter;
}

if ($searchKeyword !== '') {
    $sql .= " AND (title LIKE :kw OR description LIKE :kw OR location LIKE :kw)";
    $params[':kw'] = '%' . $searchKeyword . '%';
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$categories = getComplaintCategories();
$flash = getFlashMessage();
$pageTitle = 'My Complaints';
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
                <h1 class="page-heading">My Submitted Complaints</h1>
            </div>
            <div class="topbar-right">
                <a href="submit_complaint.php" class="btn btn-primary btn-sm">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>New Complaint</span>
                </a>
            </div>
        </header>

        <div class="content-body">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>" data-auto-dismiss="true">
                    <span><?php echo e($flash['message']); ?></span>
                    <button type="button" class="alert-close">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Search and Filter Bar -->
            <div class="filter-bar">
                <form method="GET" action="my_complaints.php" class="filter-grid">
                    <div>
                        <label class="form-label" style="font-size: 0.82rem;">Search Keywords</label>
                        <input type="text" name="q" class="form-control" placeholder="Search title or location..." value="<?php echo e($searchKeyword); ?>">
                    </div>

                    <div>
                        <label class="form-label" style="font-size: 0.82rem;">Status Filter</label>
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="Submitted" <?php echo $statusFilter === 'Submitted' ? 'selected' : ''; ?>>Submitted</option>
                            <option value="Under Review" <?php echo $statusFilter === 'Under Review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="In Progress" <?php echo $statusFilter === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Resolved" <?php echo $statusFilter === 'Resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="Rejected" <?php echo $statusFilter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" style="font-size: 0.82rem;">Category Filter</label>
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo e($cat); ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>><?php echo e($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                        <a href="my_complaints.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Complaints Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <span>Complaints List (<?php echo count($complaints); ?>)</span>
                    </h2>
                </div>

                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Complaint ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Date Filed</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($complaints)): ?>
                                <tr>
                                    <td colspan="8" class="empty-state">
                                        <p>No complaints match your current filter criteria.</p>
                                        <?php if ($statusFilter || $categoryFilter || $searchKeyword): ?>
                                            <a href="my_complaints.php" class="btn btn-secondary btn-sm" style="margin-top: 8px;">Clear Filters</a>
                                        <?php else: ?>
                                            <a href="submit_complaint.php" class="btn btn-primary btn-sm" style="margin-top: 8px;">Submit a Complaint</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($complaints as $c): ?>
                                    <tr>
                                        <td><strong><?php echo e(formatComplaintId($c['id'])); ?></strong></td>
                                        <td style="font-weight: 600; max-width: 240px;">
                                            <a href="complaint_details.php?id=<?php echo (int)$c['id']; ?>">
                                                <?php echo e($c['title']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo e($c['category']); ?></td>
                                        <td style="color: var(--text-muted); font-size: 0.88rem;"><?php echo e($c['location']); ?></td>
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
                                        <td style="white-space: nowrap;"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="complaint_details.php?id=<?php echo (int)$c['id']; ?>" class="btn btn-outline btn-sm">
                                                Details
                                            </a>

                                            <?php if ($c['status'] === 'Submitted'): ?>
                                                <a href="edit_complaint.php?id=<?php echo (int)$c['id']; ?>" class="btn btn-secondary btn-sm" title="Edit Complaint">
                                                    Edit
                                                </a>
                                            <?php endif; ?>
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
