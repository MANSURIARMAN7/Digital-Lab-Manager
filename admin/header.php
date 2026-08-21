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
    <title>Digital Lab Manager - Admin Panel</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="css/admin.css">
    <!-- Chart.js CDN (used in some pages) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <div class="brand-icon">
                <i class="fas fa-microscope"></i>
            </div>
            <div class="sidebar-title">
                <h2>DIGITAL LAB<br>MANAGER</h2>
            </div>
        </div>
        <ul class="nav-links">
            <li>
                <a href="dashboard.php" class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="Student_Mgmt.php" class="<?php echo ($current_page === 'Student_Mgmt.php' || $current_page === 'Student Mgmt.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i> Student Mgmt
                </a>
            </li>
            <li>
                <a href="faculty_mgmt.php" class="<?php echo ($current_page === 'faculty_mgmt.php' || $current_page === 'Faculty Mgmt.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt
                </a>
            </li>
            <li>
                <a href="subject_mgmt.php" class="<?php echo ($current_page === 'subject_mgmt.php' || $current_page === 'Subject Mgmt.php') ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i> Subject Mgmt
                </a>
            </li>
            <li>
                <a href="Lab_Manuals.php" class="<?php echo ($current_page === 'Lab_Manuals.php' || $current_page === 'Lab Manuals.php') ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i> Lab Manuals
                </a>
            </li>
            <li>
                <a href="Submissions.php" class="<?php echo ($current_page === 'Submissions.php') ? 'active' : ''; ?>">
                    <i class="fas fa-folder-open"></i> Submissions
                </a>
            </li>
            <li>
                <a href="Review & Marks.php" class="<?php echo ($current_page === 'Review & Marks.php') ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Review & Marks
                </a>
            </li>
            <li>
                <a href="Reports.php" class="<?php echo ($current_page === 'Reports.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            <li class="mt-auto">
                <a href="../logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <!-- TOPBAR -->
        <div class="topbar">
            <div class="search-box">
                <i class="fas fa-search text-muted"></i>
                <input type="text" placeholder="Search globally...">
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="notif-badge">
                    <i class="far fa-bell"></i>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">AD</div>
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 13.5px; line-height: 1.2;">System Admin</div>
                        <div class="text-muted" style="font-size: 11.5px;">KDP University</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Alerts Handling -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-left: 5px solid #10b981;">
                <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-left: 5px solid #ef4444;">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Dynamic Content Starts Here -->
