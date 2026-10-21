<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$pdo = getDBConnection();

// Date range and category filters for summary reports
$startDate = trim($_GET['start_date'] ?? '');
$endDate   = trim($_GET['end_date'] ?? '');
$category  = trim($_GET['category'] ?? '');

$whereClause = "WHERE 1=1";
$params = [];

if ($startDate !== '') {
    $whereClause .= " AND DATE(c.created_at) >= :start_date";
    $params[':start_date'] = $startDate;
}

if ($endDate !== '') {
    $whereClause .= " AND DATE(c.created_at) <= :end_date";
    $params[':end_date'] = $endDate;
}

if ($category !== '') {
    $whereClause .= " AND c.category = :category";
    $params[':category'] = $category;
}

// 1. Overall Summary KPIs for Filtered Period
$stmtSummary = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN c.status = 'Submitted' THEN 1 ELSE 0 END) AS submitted,
        SUM(CASE WHEN c.status = 'Under Review' THEN 1 ELSE 0 END) AS under_review,
        SUM(CASE WHEN c.status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN c.status = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
        SUM(CASE WHEN c.status = 'Rejected' THEN 1 ELSE 0 END) AS rejected
    FROM complaints c
    $whereClause
");
$stmtSummary->execute($params);
$summary = $stmtSummary->fetch() ?: [
    'total' => 0, 'submitted' => 0, 'under_review' => 0, 'in_progress' => 0, 'resolved' => 0, 'rejected' => 0
];

$totalCount = (int)$summary['total'];
$resolutionRate = $totalCount > 0 ? round(((int)$summary['resolved'] / $totalCount) * 100, 1) : 0;

// 2. Priority Breakdown
$stmtPriority = $pdo->prepare("
    SELECT c.priority, COUNT(*) AS count
    FROM complaints c
    $whereClause
    GROUP BY c.priority
    ORDER BY FIELD(c.priority, 'Urgent', 'High', 'Medium', 'Low')
");
$stmtPriority->execute($params);
$priorityReport = $stmtPriority->fetchAll();

// 3. Category Breakdown with Resolved Counts
$stmtCat = $pdo->prepare("
    SELECT
        c.category,
        COUNT(*) AS total,
        SUM(CASE WHEN c.status = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
        SUM(CASE WHEN c.status IN ('Submitted', 'Under Review', 'In Progress') THEN 1 ELSE 0 END) AS active
    FROM complaints c
    $whereClause
    GROUP BY c.category
    ORDER BY total DESC
");
$stmtCat->execute($params);
$categoryReport = $stmtCat->fetchAll();

// 4. Detailed Filtered List for Report Printout
$stmtList = $pdo->prepare("
    SELECT c.id, c.title, c.category, c.location, c.priority, c.status, c.created_at, c.updated_at, u.name AS user_name
    FROM complaints c
    INNER JOIN users u ON c.user_id = u.id
    $whereClause
    ORDER BY c.created_at DESC
");
$stmtList->execute($params);
$reportComplaints = $stmtList->fetchAll();

$categories = getComplaintCategories();
$pageTitle = 'Summary Reports';
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
                <h1 class="page-heading">Complaint Summary Reports</h1>
            </div>
            <div class="topbar-right">
                <button type="button" onclick="window.print()" class="btn btn-primary btn-sm no-print">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    <span>Print / Save PDF Report</span>
                </button>
            </div>
        </header>

        <div class="content-body">
            <!-- Report Filter Bar -->
            <div class="filter-bar no-print">
                <form method="GET" action="reports.php" class="filter-grid">
                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo e($startDate); ?>">
                    </div>

                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo e($endDate); ?>">
                    </div>

                    <div>
                        <label class="form-label" style="font-size: 0.8rem;">Category Filter</label>
                        <select name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo e($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                    <?php echo e($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary btn-block">Generate Report</button>
                        <a href="reports.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Summary Metrics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <p>Total Complaints Analyzed</p>
                        <h3><?php echo $totalCount; ?></h3>
                    </div>
                    <div class="stat-icon stat-icon-blue">
                        <strong>100%</strong>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <p>Resolution Rate</p>
                        <h3><?php echo $resolutionRate; ?>%</h3>
                    </div>
                    <div class="stat-icon stat-icon-green">
                        <strong><?php echo (int)$summary['resolved']; ?></strong>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <p>Open / Active Cases</p>
                        <h3><?php echo (int)$summary['submitted'] + (int)$summary['under_review'] + (int)$summary['in_progress']; ?></h3>
                    </div>
                    <div class="stat-icon stat-icon-amber">
                        <strong>Open</strong>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <p>Rejected Cases</p>
                        <h3><?php echo (int)$summary['rejected']; ?></h3>
                    </div>
                    <div class="stat-icon stat-icon-red">
                        <strong>Closed</strong>
                    </div>
                </div>
            </div>

            <div class="details-layout" style="grid-template-columns: 1fr 1fr;">
                <!-- Status Distribution Table -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Distribution by Status</h2>
                    </div>
                    <div class="card-body">
                        <?php
                        $statusRows = [
                            ['label' => 'Submitted',    'count' => (int)$summary['submitted'],    'badge' => 'badge-submitted'],
                            ['label' => 'Under Review', 'count' => (int)$summary['under_review'], 'badge' => 'badge-review'],
                            ['label' => 'In Progress',  'count' => (int)$summary['in_progress'],  'badge' => 'badge-progress'],
                            ['label' => 'Resolved',     'count' => (int)$summary['resolved'],     'badge' => 'badge-resolved'],
                            ['label' => 'Rejected',     'count' => (int)$summary['rejected'],     'badge' => 'badge-rejected'],
                        ];
                        ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statusRows as $sr): ?>
                                    <?php $pct = $totalCount > 0 ? round(($sr['count'] / $totalCount) * 100, 1) : 0; ?>
                                    <tr>
                                        <td><span class="badge <?php echo $sr['badge']; ?>"><?php echo e($sr['label']); ?></span></td>
                                        <td><strong><?php echo $sr['count']; ?></strong></td>
                                        <td><?php echo $pct; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Priority Distribution Table -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Distribution by Priority</h2>
                    </div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Priority Level</th>
                                    <th>Complaints</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($priorityReport)): ?>
                                    <tr><td colspan="3" class="empty-state" style="padding: 20px;">No data available.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($priorityReport as $pr): ?>
                                        <?php $pct = $totalCount > 0 ? round(((int)$pr['count'] / $totalCount) * 100, 1) : 0; ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?php echo e(getPriorityBadgeClass($pr['priority'])); ?>">
                                                    <?php echo e($pr['priority']); ?>
                                                </span>
                                            </td>
                                            <td><strong><?php echo (int)$pr['count']; ?></strong></td>
                                            <td><?php echo $pct; ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Category Summary Report -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Department / Category Performance Summary</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category / Department</th>
                                <th>Total Received</th>
                                <th>Active (Open)</th>
                                <th>Resolved</th>
                                <th>Resolution Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categoryReport)): ?>
                                <tr><td colspan="5" class="empty-state">No category statistics found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($categoryReport as $catRow): ?>
                                    <?php
                                    $catTotal = (int)$catRow['total'];
                                    $catRes   = (int)$catRow['resolved'];
                                    $catRate  = $catTotal > 0 ? round(($catRes / $catTotal) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td style="font-weight: 600;"><?php echo e($catRow['category']); ?></td>
                                        <td><?php echo $catTotal; ?></td>
                                        <td><?php echo (int)$catRow['active']; ?></td>
                                        <td><?php echo $catRes; ?></td>
                                        <td style="min-width: 160px;">
                                            <div style="display:flex;align-items:center;gap:8px;">
                                                <span style="font-weight:700;font-size:0.85rem;"><?php echo $catRate; ?>%</span>
                                                <div class="progress-bar-wrap" style="margin-top:0;">
                                                    <div class="progress-bar-fill" style="width: <?php echo $catRate; ?>%;"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detailed Complaint Log for Report -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Detailed Complaint Log (<?php echo count($reportComplaints); ?> Records)</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Submitted By</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Created Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reportComplaints)): ?>
                                <tr><td colspan="7" class="empty-state">No records found for this report period.</td></tr>
                            <?php else: ?>
                                <?php foreach ($reportComplaints as $rc): ?>
                                    <tr>
                                        <td><strong><?php echo e(formatComplaintId($rc['id'])); ?></strong></td>
                                        <td>
                                            <a href="complaint_details.php?id=<?php echo (int)$rc['id']; ?>">
                                                <?php echo e($rc['title']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo e($rc['user_name']); ?></td>
                                        <td><?php echo e($rc['category']); ?></td>
                                        <td>
                                            <span class="badge <?php echo e(getPriorityBadgeClass($rc['priority'])); ?>">
                                                <?php echo e($rc['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo e(getStatusBadgeClass($rc['status'])); ?>">
                                                <?php echo e($rc['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($rc['created_at'])); ?></td>
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
