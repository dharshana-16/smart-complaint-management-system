<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Optionally fetch live statistics if the database is already set up
$stats = [
    'total' => 0,
    'resolved' => 0,
    'in_progress' => 0,
    'users' => 0
];

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdoTemp = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stats['total'] = (int)$pdoTemp->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
    $stats['resolved'] = (int)$pdoTemp->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn();
    $stats['in_progress'] = (int)$pdoTemp->query("SELECT COUNT(*) FROM complaints WHERE status IN ('Under Review', 'In Progress')")->fetchColumn();
    $stats['users'] = (int)$pdoTemp->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
} catch (Exception $e) {
    // Fallback display numbers before DB initialization
    $stats = ['total' => 5, 'resolved' => 1, 'in_progress' => 2, 'users' => 3];
}
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container hero-grid">
        <div>
            <div class="hero-badge">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <span>Fast, Transparent & Accountable Issue Resolution</span>
            </div>
            <h1 class="hero-title">Smart Complaint Management System</h1>
            <p class="hero-subtitle">
                Submit grievances effortlessly, attach photographic evidence, track real-time resolution progress, and receive official administrative responses in one unified platform.
            </p>
            <div class="hero-buttons">
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="admin_dashboard.php" class="btn btn-white btn-lg">Go to Admin Dashboard</a>
                        <a href="manage_complaints.php" class="btn btn-outline btn-lg" style="border-color:#93c5fd;color:#ffffff;">Manage Complaints</a>
                    <?php else: ?>
                        <a href="submit_complaint.php" class="btn btn-white btn-lg">Submit a New Complaint</a>
                        <a href="user_dashboard.php" class="btn btn-outline btn-lg" style="border-color:#93c5fd;color:#ffffff;">View My Dashboard</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="register.php" class="btn btn-white btn-lg">Get Started — Register</a>
                    <a href="login.php" class="btn btn-outline btn-lg" style="border-color:#93c5fd;color:#ffffff;">Track Existing Complaint</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero-visual-card">
            <div class="hero-stat-row">
                <div class="hero-stat-box">
                    <h4><?php echo number_format($stats['total']); ?></h4>
                    <p>Total Registered Complaints</p>
                </div>
                <div class="hero-stat-box">
                    <h4><?php echo number_format($stats['resolved']); ?></h4>
                    <p>Resolved Successfully</p>
                </div>
                <div class="hero-stat-box">
                    <h4><?php echo number_format($stats['in_progress']); ?></h4>
                    <p>Active Investigations</p>
                </div>
                <div class="hero-stat-box">
                    <h4>24/7</h4>
                    <p>Online Portal Access</p>
                </div>
            </div>

            <div class="hero-step-preview">
                <div style="font-size:0.8rem;text-transform:uppercase;letter-spacing:0.06em;color:#93c5fd;font-weight:700;margin-bottom:8px;">
                    Live Status Lifecycle
                </div>
                <div class="hero-step-item">
                    <span class="hero-step-dot" style="background:#38bdf8;"></span>
                    <span><strong>Submitted</strong> &rarr; Instant reference number assigned</span>
                </div>
                <div class="hero-step-item">
                    <span class="hero-step-dot" style="background:#facc15;"></span>
                    <span><strong>Under Review & In Progress</strong> &rarr; Priority assigned by Admin</span>
                </div>
                <div class="hero-step-item">
                    <span class="hero-step-dot" style="background:#4ade80;"></span>
                    <span><strong>Resolved</strong> &rarr; Official resolution & audit trail logged</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Key Features Section -->
<section class="section section-white" id="features">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Platform Capabilities</span>
            <h2>Built for Citizens & Administrators</h2>
            <p>Everything needed to register, prioritize, investigate, and resolve issues with complete transparency.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </div>
                <h3>Easy Complaint Filing</h3>
                <p>Submit detailed complaints categorized by department, specify exact locations, and attach supporting photos up to 3MB.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                </div>
                <h3>Real-Time Status Tracking</h3>
                <p>Follow your complaint through every stage: Submitted, Under Review, In Progress, Resolved, or Rejected with timestamps.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <h3>Official Admin Responses</h3>
                <p>Receive direct, documented responses and status remarks from authorized administrators on your complaint timeline.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <h3>Priority & Audit Logs</h3>
                <p>Administrators can assign Low, Medium, High, or Urgent priority levels and generate comprehensive analytical reports.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="section section-muted" id="how-it-works">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Simple 4-Step Process</span>
            <h2>How the System Works</h2>
            <p>From initial submission to final resolution in four structured steps.</p>
        </div>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h4>Create Your Account</h4>
                <p>Register securely with your name, email address, and phone number in under a minute.</p>
            </div>

            <div class="step-card">
                <div class="step-number">2</div>
                <h4>Submit Complaint</h4>
                <p>Provide the complaint title, category, location, detailed description, and optional photo evidence.</p>
            </div>

            <div class="step-card">
                <div class="step-number">3</div>
                <h4>Admin Review & Action</h4>
                <p>Administrators review the issue, assign priority, dispatch teams, and post progress updates.</p>
            </div>

            <div class="step-card">
                <div class="step-number">4</div>
                <h4>Track to Resolution</h4>
                <p>Monitor live status updates and read official resolution notes from your personal dashboard.</p>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="public-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <h3>Smart Complaint Management System</h3>
                <p>A secure, responsive full-stack web application built with HTML5, CSS3, Vanilla JavaScript, Core PHP (PDO), and MySQL.</p>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="register.php">User Registration</a></li>
                    <li><a href="login.php">User Login</a></li>
                    <li><a href="admin_login.php">Administrator Login</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4>Portal Access</h4>
                <ul>
                    <li><a href="user_dashboard.php">User Dashboard</a></li>
                    <li><a href="submit_complaint.php">Submit a Complaint</a></li>
                    <li><a href="admin_dashboard.php">Admin Control Panel</a></li>
                    <li><a href="reports.php">Summary Reports</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo e(APP_NAME); ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
