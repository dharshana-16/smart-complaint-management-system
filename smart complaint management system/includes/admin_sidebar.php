<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$adminInitials = strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1));
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="dashboardSidebar">
    <div class="sidebar-brand">
        <div class="brand-logo-icon" style="width:36px;height:36px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
            </svg>
        </div>
        <div>
            <div class="sidebar-brand-title">SmartComplaint</div>
            <span class="sidebar-brand-subtitle">Admin Control Center</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Administration</div>

        <a href="admin_dashboard.php" class="sidebar-link <?php echo $currentPage === 'admin_dashboard.php' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            <span>Admin Dashboard</span>
        </a>

        <a href="manage_complaints.php" class="sidebar-link <?php echo in_array($currentPage, ['manage_complaints.php', 'complaint_details.php']) ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
            <span>Manage Complaints</span>
        </a>

        <a href="manage_users.php" class="sidebar-link <?php echo $currentPage === 'manage_users.php' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <span>Manage Users</span>
        </a>

        <a href="reports.php" class="sidebar-link <?php echo $currentPage === 'reports.php' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <span>Summary Reports</span>
        </a>

        <div class="sidebar-section-label" style="margin-top: 16px;">Account & Site</div>

        <a href="profile.php" class="sidebar-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span>Admin Profile</span>
        </a>

        <a href="index.php" class="sidebar-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <span>Public Home</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user-info">
            <div class="user-avatar" style="background:#7c3aed;"><?php echo e($adminInitials); ?></div>
            <div class="user-meta">
                <div class="user-meta-name"><?php echo e($_SESSION['user_name'] ?? 'Administrator'); ?></div>
                <div class="user-meta-role">Administrator</div>
            </div>
        </div>
        <a href="logout.php" class="btn btn-danger btn-sm btn-block">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            <span>Sign Out</span>
        </a>
    </div>
</aside>
