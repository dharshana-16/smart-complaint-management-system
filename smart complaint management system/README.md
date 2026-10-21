# Smart Complaint Management System

A complete, responsive, full-stack web application built with **HTML5, CSS3, Vanilla JavaScript, Core PHP (PDO), and MySQL** for **XAMPP Apache**.

---

## 1. Project Folder Structure

Located at `C:\xampp\htdocs\SmartComplaintManagement`:

```text
SmartComplaintManagement/
├── config/
│   └── database.php          # PDO database connection & app configuration
├── includes/
│   ├── auth.php              # Session management, CSRF protection, RBAC & helpers
│   ├── header.php            # Shared HTML head & metadata
│   ├── footer.php            # Shared HTML footer & JS includes
│   ├── navbar.php            # Public responsive navigation bar
│   ├── user_sidebar.php      # Citizen dashboard sidebar navigation
│   └── admin_sidebar.php     # Administrator sidebar navigation
├── css/
│   └── style.css             # Complete responsive blue-and-white theme stylesheet
├── js/
│   └── main.js               # Form validation, image preview, mobile menu & alerts
├── uploads/
│   └── .htaccess             # Prevents script execution in uploads directory
├── database.sql              # Complete MySQL database schema & sample data
├── setup.php                 # One-click automatic database setup wizard
├── index.php                 # Public landing page
├── register.php              # Citizen registration page
├── login.php                 # Citizen login page
├── admin_login.php           # Administrator login page
├── logout.php                # Secure session logout
├── user_dashboard.php        # Citizen dashboard & metrics
├── submit_complaint.php      # New complaint submission with photo upload
├── my_complaints.php         # Citizen complaint list with search & filters
├── edit_complaint.php        # Edit complaint (when status is Submitted)
├── complaint_details.php     # Full complaint details, responses & status timeline
├── profile.php               # Profile & password management
├── admin_dashboard.php       # Administrator KPI dashboard
├── manage_complaints.php     # Admin complaint search, filter & quick update
├── manage_users.php          # Admin user management & admin provisioning
└── reports.php               # Summary analytics & printable reports
```

---

## 2. Quick Setup Instructions (XAMPP)

1. Open **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Open your browser and visit:
   - **Automatic Setup:** `http://localhost/SmartComplaintManagement/setup.php` (click **Initialize / Reset Database Now**)
   - **Or via phpMyAdmin:** Open `http://localhost/phpmyadmin`, click **Import**, select `database.sql`, and click **Import**.
3. Open the application at:
   - `http://localhost/SmartComplaintManagement/`

---

## 3. Default Test Accounts (Sample Data)

| Role | Email | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin@smartcomplaint.com` | `Admin@12345` |
| **Citizen User 1** | `john@example.com` | `User@12345` |
| **Citizen User 2** | `sarah@example.com` | `User@12345` |
| **Deactivated User** | `david@example.com` | `User@12345` |
