<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="public-navbar">
    <div class="container navbar-inner">
        <a href="index.php" class="brand-logo">
            <div class="brand-logo-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </div>
            <span>SmartComplaint</span>
        </a>

        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle Menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>

        <nav class="nav-links" id="publicNavLinks">
            <a href="index.php" class="nav-item <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Home</a>
            <a href="index.php#features" class="nav-item">Features</a>
            <a href="index.php#how-it-works" class="nav-item">How It Works</a>

            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <a href="admin_dashboard.php" class="btn btn-primary btn-sm">Admin Dashboard</a>
                <?php else: ?>
                    <a href="user_dashboard.php" class="btn btn-primary btn-sm">My Dashboard</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-outline btn-sm">Logout</a>
            <?php else: ?>
                <a href="login.php" class="nav-item <?php echo $currentPage === 'login.php' ? 'active' : ''; ?>">User Login</a>
                <a href="admin_login.php" class="nav-item <?php echo $currentPage === 'admin_login.php' ? 'active' : ''; ?>">Admin Portal</a>
                <a href="register.php" class="btn btn-primary btn-sm">Register Now</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
