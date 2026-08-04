<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Agar user login nahi hai, YA uska role 'admin' nahi hai
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    // Usko wapas login page par phek do
    header("Location: ../login.php");
    exit();
}

// Load users database
$users_file = '../users.json';
$users = [];
if (file_exists($users_file)) {
    $users = json_decode(file_get_contents($users_file), true);
    if (!is_array($users)) {
        $users = [];
    }
}

// Get current filename to highlight active page in sidebar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Lab Manual & ERP System - Admin Dashboard</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        :root {
            --sidebar-bg: #0b0f19;
            --sidebar-hover: rgba(59, 130, 246, 0.12);
            --sidebar-active-bg: #3b82f6;
            --sidebar-active-color: #ffffff;
            --sidebar-text-muted: #94a3b8;
            --body-bg: #f4f6fa;
            --card-radius: 16px;
            --primary-color: #3b82f6;
            --card-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.04), 0 1px 3px rgba(0, 0, 0, 0.02);
            --card-hover-shadow: 0 20px 40px -15px rgba(59, 130, 246, 0.08), 0 4px 12px rgba(0, 0, 0, 0.03);
            --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: var(--body-bg);
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            min-height: 100vh;
            color: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            transition: var(--transition-smooth);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar .brand {
            padding: 24px 20px;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar ul.nav-links {
            list-style: none;
            padding: 20px 14px;
            margin: 0;
        }

        .sidebar ul.nav-links li {
            margin-bottom: 6px;
        }

        .sidebar ul.nav-links a {
            color: var(--sidebar-text-muted);
            text-decoration: none;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.88rem;
            transition: var(--transition-smooth);
            cursor: pointer;
        }

        .sidebar ul.nav-links a i {
            font-size: 1.1rem;
            transition: var(--transition-smooth);
        }

        .sidebar ul.nav-links a:hover {
            background-color: var(--sidebar-hover);
            color: #ffffff;
        }

        .sidebar ul.nav-links a.active {
            background-color: var(--sidebar-active-bg);
            color: var(--sidebar-active-color);
            box-shadow: 0 8px 20px -6px rgba(59, 130, 246, 0.5);
        }

        .sidebar ul.nav-links a.active i {
            color: #ffffff;
        }

        /* Main Wrapper */
        .main-wrapper {
            margin-left: 260px;
            padding: 30px;
            transition: var(--transition-smooth);
        }

        /* Top Bar */
        .top-bar {
            background: #ffffff;
            padding: 16px 24px;
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(226, 232, 240, 0.8);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-bar .form-control {
            font-size: 0.9rem;
            padding: 10px 16px;
            border-radius: 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: var(--transition-smooth);
        }

        .top-bar .form-control:focus {
            background-color: #ffffff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        /* Card Styling */
        .stat-card {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: var(--card-radius);
            padding: 24px;
            box-shadow: var(--card-shadow);
            transition: var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--card-hover-shadow);
            border-color: rgba(59, 130, 246, 0.3);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            transition: var(--transition-smooth);
        }

        .content-card {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 28px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(226, 232, 240, 0.8);
            margin-bottom: 30px;
            transition: var(--transition-smooth);
        }

        /* Tables styling */
        .table {
            vertical-align: middle;
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 16px 20px;
            border-bottom: 2px solid #e2e8f0;
            border-top: none;
        }

        .table tbody td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 0.88rem;
        }

        .table-responsive {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        /* Chart Center Text Overlay */
        .chart-container {
            position: relative;
            height: 240px;
            width: 100%;
        }

        .chart-center-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            pointer-events: none;
        }

        .chart-center-text .number {
            font-size: 1.6rem;
            font-weight: 800;
            color: #0f172a;
        }

        /* Status Badges */
        .status-badge, .badge-status {
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid transparent;
        }

        .badge-active, .badge-approved {
            background-color: rgba(34, 197, 94, 0.08);
            color: #16a34a;
            border-color: rgba(34, 197, 94, 0.15);
        }

        .badge-pending {
            background-color: rgba(245, 158, 11, 0.08);
            color: #d97706;
            border-color: rgba(245, 158, 11, 0.15);
        }

        .badge-inactive, .badge-rejected {
            background-color: rgba(239, 68, 68, 0.08);
            color: #dc2626;
            border-color: rgba(239, 68, 68, 0.15);
        }

        /* --- Nested Student Cards UI --- */
        .student-card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(226, 232, 240, 0.8);
            overflow: hidden;
            margin-bottom: 20px;
            transition: var(--transition-smooth);
        }

        .student-card:hover {
            box-shadow: var(--card-hover-shadow);
            border-color: rgba(59, 130, 246, 0.2);
        }

        .student-header {
            padding: 20px 24px;
            background: #ffffff;
            cursor: pointer;
            transition: var(--transition-smooth);
        }

        .student-header:hover {
            background-color: #f8fafc;
        }

        .subject-list-container {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 20px 24px;
        }

        .subject-item {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: var(--transition-smooth);
        }

        .subject-item:hover {
            border-color: rgba(59, 130, 246, 0.2);
            transform: translateX(4px);
        }

        .subject-item:last-child {
            margin-bottom: 0;
        }

        .status-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            transition: var(--transition-smooth);
        }

        .status-icon.approved { background-color: rgba(34, 197, 94, 0.1); color: #16a34a; }
        .status-icon.not-submitted { background-color: rgba(239, 68, 68, 0.1); color: #dc2626; }
        .status-icon.pending { background-color: rgba(245, 158, 11, 0.1); color: #d97706; }

        /* Modal & Form Control Polish */
        .modal-content {
            border-radius: 18px;
            border: none;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.12) !important;
        }

        .modal-header {
            border-bottom: 1px solid #f1f5f9;
            padding: 22px 28px;
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
        }

        .modal-footer {
            border-top: 1px solid #f1f5f9;
            padding: 18px 28px;
            border-bottom-left-radius: 18px;
            border-bottom-right-radius: 18px;
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 10px 16px;
            border: 1px solid #e2e8f0;
            font-size: 0.88rem;
            color: #334155;
            transition: var(--transition-smooth);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
        }

        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 6px;
        }

        /* Buttons & Badges Styling */
        .btn {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: var(--transition-smooth);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            box-shadow: 0 4px 12px -2px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover, .btn-primary:focus {
            background-color: #2563eb;
            border-color: #2563eb;
            box-shadow: 0 6px 16px -2px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background-color: #f1f5f9;
            border-color: #f1f5f9;
            color: #475569;
        }

        .btn-secondary:hover {
            background-color: #e2e8f0;
            border-color: #e2e8f0;
            color: #1e293b;
        }

        /* Dropdowns Styling */
        .dropdown-menu {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.08) !important;
            padding: 8px;
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 8px 12px;
            font-weight: 500;
            color: #475569;
            transition: var(--transition-smooth);
        }

        .dropdown-item:hover {
            background-color: #f8fafc;
            color: #0f172a;
        }

        /* Scrollbar customization */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="brand">
            <img src="college-logo.png"
                 alt="College Logo"
                 style="width: 42px; height: 42px; object-fit: contain;">
            <i class="fa-solid fa-microscope text-primary fs-4"></i>
            <span>DIGITAL LAB MANUAL</span>
        </div>

        <ul class="nav-links">
            <li>
                <a href="dashboard.php" class="<?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
                </a>
            </li>

            <li>
                <a href="Student Mgmt.php" class="<?php echo ($current_page === 'Student Mgmt.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-graduate"></i> Student Mgmt
                </a>
            </li>

            <li>
                <a href="Faculty Mgmt.php" class="<?php echo ($current_page === 'Faculty Mgmt.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-chalkboard-user"></i> Faculty Mgmt
                </a>
            </li>

            <li>
                <a href="Subject Mgmt.php" class="<?php echo ($current_page === 'Subject Mgmt.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-book-open"></i> Subject Mgmt
                </a>
            </li>

            <li>
                <a href="Lab Manuals.php" class="<?php echo ($current_page === 'Lab Manuals.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-code"></i> Lab Manuals
                </a>
            </li>

            <li>
                <a href="Submissions.php" class="<?php echo ($current_page === 'Submissions.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-upload"></i> Submissions
                </a>
            </li>

            <li>
                <a href="Review & Marks.php" class="<?php echo ($current_page === 'Review & Marks.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-circle-check"></i> Review & Marks
                </a>
            </li>

            <li>
                <a href="Reports.php" class="<?php echo ($current_page === 'Reports.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-invoice"></i> Reports
                </a>
            </li>

            <li>
                <a href="Expense Mgmt.php" class="<?php echo ($current_page === 'Expense Mgmt.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-wallet"></i> Expense Mgmt
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">

        <!-- Alert notifications -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-left: 5px solid #22c55e !important;">
                <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-left: 5px solid #ef4444 !important;">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Top Navbar -->
        <div class="top-bar d-flex justify-content-between align-items-center">
            <div style="width: 280px;">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0">
                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                    </span>
                    <input type="text"
                           class="form-control bg-light border-0"
                           placeholder="Search globally...">
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <i class="fa-regular fa-bell fs-5 text-secondary cursor-pointer"></i>

                <div class="d-flex align-items-center gap-2">
                    <img src="https://ui-avatars.com/api/?name=Admin+Manager&background=2563eb&color=fff"
                         class="rounded-circle"
                         width="36"
                         alt="User">

                    <div>
                        <div class="fw-semibold text-dark" style="font-size: 0.88rem;">
                            System Administrator
                        </div>
                        <small class="text-muted d-block" style="font-size: 0.72rem; margin-top: -3px;">
                            University Tech
                        </small>
                    </div>
                </div>
            </div>
        </div>
