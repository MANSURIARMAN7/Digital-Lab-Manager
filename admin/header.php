<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get current filename to highlight active page in sidebar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Digital Lab Manager - Admin Panel">
    <title>Digital Lab Manager – Admin</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="css/admin.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <!-- ========== SIDEBAR ========== -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <div class="brand-icon" style="background: transparent; box-shadow: none; width: auto; height: 45px;">
                <img src="logo_kdp.png" alt="Logo" style="height: 100%; width: auto; max-width: 100%; object-fit: contain;">
            </div>
            <div class="sidebar-title">
                <h2>Digital Lab<br>Manager</h2>
            </div>
        </div>

        <ul class="nav-links">
            <span class="nav-section-label">Overview</span>
            <li>
                <a href="dashboard.php" class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie nav-icon"></i>
                    Dashboard
                </a>
            </li>

            <span class="nav-section-label">Management</span>
            <li>
                <a href="Student_Mgmt.php" class="<?php echo ($current_page === 'Student_Mgmt.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate nav-icon"></i>
                    Student Mgmt
                </a>
            </li>
            <li>
                <a href="faculty_mgmt.php" class="<?php echo ($current_page === 'faculty_mgmt.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-user nav-icon"></i>
                    Faculty Mgmt
                </a>
            </li>
            <li>
                <a href="subject_mgmt.php" class="<?php echo ($current_page === 'subject_mgmt.php') ? 'active' : ''; ?>">
                    <i class="fas fa-book-open nav-icon"></i>
                    Subject Mgmt
                </a>
            </li>
            <li>
                <a href="Lab_Manuals.php" class="<?php echo ($current_page === 'Lab_Manuals.php') ? 'active' : ''; ?>">
                    <i class="fas fa-file-lines nav-icon"></i>
                    Lab Manuals
                </a>
            </li>

            <span class="nav-section-label">Academic</span>
            <li>
                <a href="Submissions.php" class="<?php echo ($current_page === 'Submissions.php') ? 'active' : ''; ?>">
                    <i class="fas fa-folder-open nav-icon"></i>
                    Submissions
                </a>
            </li>
            <li>
                <a href="Review & Marks.php" class="<?php echo ($current_page === 'Review & Marks.php') ? 'active' : ''; ?>">
                    <i class="fas fa-circle-check nav-icon"></i>
                    Review & Marks
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <ul class="nav-links" style="padding: 0;">
                <li>
                    <a href="../logout.php" class="logout-link">
                        <i class="fas fa-right-from-bracket nav-icon"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- ========== MAIN ========== -->
    <div class="main">

        <!-- TOP BAR -->
        <div class="topbar">
            <div class="search-box">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" placeholder="Search anything...">
            </div>
            <div class="topbar-right">
                <div class="notif-badge">
                    <i class="far fa-bell"></i>
                    <span class="dot"></span>
                </div>
                <div class="topbar-divider"></div>
                <div class="user-profile">
                    <div class="user-avatar">AD</div>
                    <div class="user-info">
                        <div class="user-name">System Admin</div>
                        <div class="user-role">KDP University</div>
                    </div>
                    <i class="fas fa-chevron-down" style="font-size: 11px; color: var(--text-muted); margin-left: 2px;"></i>
                </div>
            </div>
        </div>

        <!-- CONTENT AREA -->
        <div class="content-area">

            <!-- Session Alerts -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-circle-check"></i>
                    <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-triangle-exclamation"></i>
                    <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Dynamic Content Starts Here -->
