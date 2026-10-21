<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$userInitials = strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1));
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="dashboardSidebar">
    <div class="sidebar-brand">
        <div class="brand-logo-icon" style="width:36px;height:36px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
        </div>
        <div>
            <div class="sidebar-brand-title">SmartComplaint</div>
            <span class="sidebar-brand-subtitle">User Portal</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Main Menu</div>

        <a href="user_dashboard.php" class="sidebar-link <?php echo $currentPage === 'user_dashboard.php' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            <span>Dashboard</span>
        </a>

        <a href="submit_complaint.php" class="sidebar-link <?php echo $currentPage === 'submit_complaint.php' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="16"></line>
                <line x1="8" y1="12" x2="16" y2="12"></line>
            </svg>
            <span>Submit Complaint</span>
        </a>

        <a href="my_complaints.php" class="sidebar-link <?php echo in_array($currentPage, ['my_complaints.php', 'complaint_details.php', 'edit_complaint.php']) ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
            </svg>
            <span>My Complaints</span>
        </a>

        <div class="sidebar-section-label" style="margin-top: 16px;">Account</div>

        <a href="profile.php" class="sidebar-link <?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span>My Profile</span>
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
            <div class="user-avatar"><?php echo e($userInitials); ?></div>
            <div class="user-meta">
                <div class="user-meta-name"><?php echo e($_SESSION['user_name'] ?? 'User'); ?></div>
                <div class="user-meta-role"><?php echo e($_SESSION['user_email'] ?? ''); ?></div>
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
