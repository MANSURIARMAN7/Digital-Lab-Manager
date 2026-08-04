<?php
session_start();
include '../db.php'; // 🔥 LIVE MySQL CONNECTION

// Agar user login nahi hai, YA uska role 'admin' nahi hai
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'System Administrator';

// ==========================================
// 🔥 HANDLE FACULTY CRUD ACTIONS (MySQL)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'add_faculty') {
        $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
        $user_id = $conn->real_escape_string(trim($_POST['user_id'] ?? ''));
        $password = $conn->real_escape_string(trim($_POST['password'] ?? 'faculty123'));
        $designation = $conn->real_escape_string(trim($_POST['designation'] ?? 'Assistant Professor'));
        $department = $conn->real_escape_string(trim($_POST['department'] ?? 'CE'));
        $email = $conn->real_escape_string(trim($_POST['email'] ?? ($user_id . '@uni.edu')));
        $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
        $status = $conn->real_escape_string(trim($_POST['status'] ?? 'active'));
        $joined_date = $conn->real_escape_string(trim($_POST['joined_date'] ?? date('M Y')));
        
        $subjects_str = trim($_POST['subjects'] ?? '');
        $subjects_arr = array_filter(array_map('trim', explode(',', $subjects_str)));
        $subjects_json = $conn->real_escape_string(json_encode($subjects_arr)); // JSON to save in MySQL
        
        if (empty($name) || empty($user_id)) {
            $_SESSION['error_message'] = "Name and Employee ID are required!";
        } else {
            // Check if user already exists
            $check = $conn->query("SELECT * FROM users WHERE user_id = '$user_id'");
            if ($check->num_rows > 0) {
                $_SESSION['error_message'] = "A user with Employee ID '$user_id' already exists!";
            } else {
                $sql = "INSERT INTO users (user_id, password, name, role, designation, department, email, phone, status, joined_date, subjects) 
                        VALUES ('$user_id', '$password', '$name', 'faculty', '$designation', '$department', '$email', '$phone', '$status', '$joined_date', '$subjects_json')";
                
                if ($conn->query($sql)) {
                    $_SESSION['success_message'] = "Faculty '$name' added successfully!";
                } else {
                    $_SESSION['error_message'] = "Database Error: " . $conn->error;
                }
            }
        }
        header("Location: adminpanel.php?tab=faculty-tab");
        exit();
    }
    
    if ($_POST['action'] === 'edit_faculty') {
        $original_user_id = $conn->real_escape_string(trim($_POST['original_user_id'] ?? ''));
        $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
        $user_id = $conn->real_escape_string(trim($_POST['user_id'] ?? ''));
        $password = $conn->real_escape_string(trim($_POST['password'] ?? ''));
        $designation = $conn->real_escape_string(trim($_POST['designation'] ?? 'Assistant Professor'));
        $department = $conn->real_escape_string(trim($_POST['department'] ?? 'CE'));
        $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
        $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
        $status = $conn->real_escape_string(trim($_POST['status'] ?? 'active'));
        $joined_date = $conn->real_escape_string(trim($_POST['joined_date'] ?? ''));
        
        $subjects_str = trim($_POST['subjects'] ?? '');
        $subjects_arr = array_filter(array_map('trim', explode(',', $subjects_str)));
        $subjects_json = $conn->real_escape_string(json_encode($subjects_arr));
        
        if (empty($name) || empty($user_id) || empty($original_user_id)) {
            $_SESSION['error_message'] = "Name and Employee ID are required!";
        } else {
            $pass_query = !empty($password) ? ", password='$password'" : "";
            $sql = "UPDATE users SET 
                    name='$name', user_id='$user_id', designation='$designation', department='$department', 
                    email='$email', phone='$phone', status='$status', joined_date='$joined_date', subjects='$subjects_json' 
                    $pass_query 
                    WHERE user_id='$original_user_id' AND role='faculty'";
                    
            if ($conn->query($sql)) {
                $_SESSION['success_message'] = "Faculty '$name' updated successfully!";
            } else {
                $_SESSION['error_message'] = "Update failed: " . $conn->error;
            }
        }
        header("Location: adminpanel.php?tab=faculty-tab");
        exit();
    }
    
    if ($_POST['action'] === 'delete_faculty') {
        $user_id = $conn->real_escape_string(trim($_POST['user_id'] ?? ''));
        if (empty($user_id)) {
            $_SESSION['error_message'] = "User ID is required to delete!";
        } else {
            $sql = "DELETE FROM users WHERE user_id='$user_id' AND role='faculty'";
            if ($conn->query($sql)) {
                $_SESSION['success_message'] = "Faculty member removed successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to delete: " . $conn->error;
            }
        }
        header("Location: adminpanel.php?tab=faculty-tab");
        exit();
    }
}

// 🔥 GET LIVE STATS FOR DASHBOARD
$total_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'] ?? 0;
$total_faculty = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='faculty'")->fetch_assoc()['c'] ?? 0;
$total_pending = $conn->query("SELECT COUNT(*) as c FROM submissions WHERE status='Pending'")->fetch_assoc()['c'] ?? 0;
$total_rejected = $conn->query("SELECT COUNT(*) as c FROM submissions WHERE status='Rejected'")->fetch_assoc()['c'] ?? 0;

// Fetch all faculties for the table
$faculty_users = [];
$fac_res = $conn->query("SELECT * FROM users WHERE role='faculty' ORDER BY id DESC");
if ($fac_res) {
    while($row = $fac_res->fetch_assoc()) {
        $row['subjects'] = json_decode($row['subjects'], true) ?: [];
        $faculty_users[] = $row;
    }
}
$faculty_count = count($faculty_users);
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
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-hover: #2563eb;
            --body-bg: #f8fafc;
            --card-radius: 12px;
        }

        body {
            background-color: var(--body-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
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
            transition: all 0.3s;
        }

        .sidebar .brand {
            padding: 20px;
            font-size: 1.1rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar ul.nav-links {
            list-style: none;
            padding: 15px 10px;
            margin: 0;
        }

        .sidebar ul.nav-links li {
            margin-bottom: 3px;
        }

        .sidebar ul.nav-links a {
            color: #94a3b8;
            text-decoration: none;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .sidebar ul.nav-links a:hover, 
        .sidebar ul.nav-links a.active {
            background-color: var(--sidebar-hover);
            color: #ffffff;
        }

        /* Main Wrapper */
        .main-wrapper {
            margin-left: 260px;
            padding: 25px;
        }

        /* Top Bar */
        .top-bar {
            background: #ffffff;
            padding: 12px 24px;
            border-radius: var(--card-radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .stat-card {
            background: #ffffff;
            border: none;
            border-radius: var(--card-radius);
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .content-card {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 22px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 25px;
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
            transform: translate(-50%, -60%);
            text-align: center;
            pointer-events: none;
        }

        .chart-center-text .number {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0f172a;
        }

        /* Status Badges */
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-active, .badge-approved {
            background-color: #dcfce7;
            color: #15803d;
        }

        .badge-pending {
            background-color: #fef3c7;
            color: #d97706;
        }

        .badge-inactive, .badge-rejected {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .tab-content-section {
            display: none;
        }

        .tab-content-section.active {
            display: block;
        }

        /* --- Nested Student Cards UI --- */
        .student-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 18px;
        }

        .student-header {
            padding: 16px 20px;
            background: #ffffff;
            cursor: pointer;
            transition: background 0.2s;
        }

        .student-header:hover {
            background-color: #f8fafc;
        }

        .subject-list-container {
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 15px 20px;
        }

        .subject-item {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .subject-item:last-child {
            margin-bottom: 0;
        }

        .status-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
        }

        .status-icon.approved { background-color: #dcfce7; color: #16a34a; }
        .status-icon.not-submitted { background-color: #fee2e2; color: #dc2626; }
        .status-icon.pending { background-color: #fef3c7; color: #d97706; }

        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.78rem;
        }
    </style>
</head>

<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">

        <div class="brand">
            <i class="fa-solid fa-microscope text-primary fs-4"></i>
            <span>DIGITAL LAB MANUAL</span>
        </div>

        <ul class="nav-links">
            <li>
                <a class="nav-item active" onclick="switchTab('dashboard-tab', this)">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
                </a>
            </li>

            <li>
                <a class="nav-item" onclick="switchTab('student-tab', this)">
                    <i class="fa-solid fa-user-graduate"></i> Student Mgmt
                </a>
            </li>

            <li>
                <a class="nav-item" onclick="switchTab('faculty-tab', this)">
                    <i class="fa-solid fa-chalkboard-user"></i> Faculty Mgmt
                </a>
            </li>

            <li>
                <a class="nav-item" onclick="switchTab('subject-tab', this)">
                    <i class="fa-solid fa-book-open"></i> Subject Mgmt
                </a>
            </li>

            <li>
                <a class="nav-item" onclick="switchTab('lab-tab', this)">
                    <i class="fa-solid fa-file-code"></i> Lab Manuals
                </a>
            </li>

            <li>
                <a class="nav-item" onclick="switchTab('submission-tab', this)">
                    <i class="fa-solid fa-upload"></i> Submissions
                </a>
            </li>

            <li>
                <a class="nav-item" onclick="switchTab('review-tab', this)">
                    <i class="fa-solid fa-circle-check"></i> Review & Marks
                </a>
            </li>

            <li>
                <a class="nav-item" onclick="switchTab('reports-tab', this)">
                    <i class="fa-solid fa-file-invoice"></i> Reports
                </a>
            </li>

            <li>
                <a class="nav-item" onclick="switchTab('expense-tab', this)">
                    <i class="fa-solid fa-wallet"></i> Expense Mgmt
                </a>
            </li>
            
            <li class="mt-4">
                <a class="nav-item text-danger" onclick="window.location.href='../logout.php'">
                    <i class="fa-solid fa-sign-out-alt"></i> Logout
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
                    <input type="text" class="form-control bg-light border-0" placeholder="Search globally...">
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">

                <i class="fa-regular fa-bell fs-5 text-secondary cursor-pointer"></i>

                <div class="d-flex align-items-center gap-2">

                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_name); ?>&background=2563eb&color=fff"
                         class="rounded-circle" width="36" alt="User">

                    <div>
                        <div class="fw-semibold text-dark" style="font-size: 0.88rem;">
                            <?php echo htmlspecialchars($admin_name); ?>
                        </div>
                        <small class="text-muted d-block" style="font-size: 0.72rem; margin-top: -3px;">
                            System Administrator
                        </small>
                    </div>

                </div>
            </div>
        </div>


        <!-- ==================== 1. MAIN DASHBOARD TAB ==================== -->

        <div id="dashboard-tab" class="tab-content-section active">

            <h4 class="fw-bold text-dark mb-4">
                Digital Lab Manager Dashboard
            </h4>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small">Total Students</span>
                                <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo $total_students; ?></h3>
                            </div>
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-user-graduate"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small">Active Faculty</span>
                                <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo $total_faculty; ?></h3>
                            </div>
                            <div class="stat-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-chalkboard-user"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small">Pending Reviews</span>
                                <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo $total_pending; ?></h3>
                            </div>
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-danger">Rejected Submissions</span>
                                <h3 class="fw-bold text-dark mb-0 mt-1"><?php echo $total_rejected; ?></h3>
                            </div>
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-xmark"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STATIC DEMO CHARTS (Kept as per your design) -->
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="content-card">
                        <h5 class="fw-bold text-dark mb-3">Submission Breakdown</h5>
                        <div class="chart-container">
                            <canvas id="submissionsDoughnut"></canvas>
                            <div class="chart-center-text">
                                <div class="number">1,250</div>
                                <div class="text-muted small">Submissions</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="content-card">
                        <h5 class="fw-bold text-dark mb-3">Recent Student Manual Submissions</h5>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Subject</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Pathan Rehan Khan (CE)</td>
                                        <td>DS Lab</td>
                                        <td>Today, 10:30 AM</td>
                                        <td><span class="status-badge badge-pending">Pending</span></td>
                                    </tr>
                                    <tr>
                                        <td>Belim Hamza (CE)</td>
                                        <td>RDBMS Lab</td>
                                        <td>Yesterday</td>
                                        <td><span class="status-badge badge-approved">Approved</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== 2. STUDENT MANAGEMENT TAB (STATIC DEMO) ==================== -->
        <div id="student-tab" class="tab-content-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold text-dark mb-0">👨‍🎓 Student Management & Lab Manual Tracker</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fa-solid fa-plus me-1"></i> Add New Student
                </button>
            </div>
            
            <div class="alert alert-info border-0 shadow-sm"><i class="fa-solid fa-info-circle me-2"></i> Student data fetch will be connected to DB in next step!</div>
        </div>


        <!-- ==================== 3. FACULTY MANAGEMENT TAB (LIVE DB) ==================== -->
        <div id="faculty-tab" class="tab-content-section">
            
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold text-dark mb-1">👨‍🏫 Faculty & Staff Management</h4>
                    <p class="text-muted small mb-0">Manage teaching staff, lab assistants, and department roles.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-light border shadow-sm text-secondary" onclick="exportFacultyCSV()">
                        <i class="fa-solid fa-file-export me-1"></i> Export CSV
                    </button>
                    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                        <i class="fa-solid fa-plus me-1"></i> Add New Faculty
                    </button>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="content-card border-0 shadow-sm">
                
                <!-- Advanced Search & Filters -->
                <div class="row g-3 align-items-center mb-4">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 py-2">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" id="facultySearchInput" class="form-control bg-light border-0 shadow-none py-2" placeholder="Search by name, employee ID, or email..." oninput="filterFacultyTable()">
                        </div>
                    </div>
                    <div class="col-md-7 d-flex justify-content-md-end gap-2">
                        <select id="facultyDeptFilter" class="form-select bg-light border-0 shadow-none w-auto text-muted font-sm py-2" onchange="filterFacultyTable()">
                            <option value="">All Departments</option>
                            <option value="CE">Computer Engineering (CE)</option>
                            <option value="IT">Information Tech. (IT)</option>
                            <option value="ME">Mechanical Eng. (ME)</option>
                        </select>
                        <select id="facultyStatusFilter" class="form-select bg-light border-0 shadow-none w-auto text-muted font-sm py-2" onchange="filterFacultyTable()">
                            <option value="">Status: All</option>
                            <option value="active">🟢 Active</option>
                            <option value="leave">🟡 On Leave</option>
                            <option value="inactive">🔴 Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Professional Table -->
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="table-light text-muted">
                            <tr>
                                <th class="fw-semibold pb-3">Faculty Profile</th>
                                <th class="fw-semibold pb-3">Emp ID</th>
                                <th class="fw-semibold pb-3">Designation / Dept.</th>
                                <th class="fw-semibold pb-3">Contact Information</th>
                                <th class="fw-semibold pb-3">Status</th>
                                <th class="fw-semibold text-end pb-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="facultyTableBody" class="border-top-0">
                            <?php
                            if ($faculty_count === 0):
                            ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-users-slash display-6 mb-3 d-block text-secondary opacity-50"></i>
                                        <h6 class="fw-bold mb-1">No Faculty Members Found</h6>
                                        <p class="small text-muted mb-0">Click "Add New Faculty" to register a new teaching staff member.</p>
                                    </td>
                                </tr>
                            <?php
                            else:
                                foreach ($faculty_users as $f):
                                    $f_name = $f['name'] ?? 'Unknown';
                                    $f_id = $f['user_id'] ?? '';
                                    $f_designation = $f['designation'] ?? 'Assistant Professor';
                                    $f_dept = $f['department'] ?? 'CE';
                                    $f_email = $f['email'] ?? ($f_id . '@uni.edu');
                                    $f_phone = $f['phone'] ?? '+91 98765 43210';
                                    $f_status = $f['status'] ?? 'active';
                                    $f_joined = $f['joined_date'] ?? 'Aug 2020';
                                    $f_subjects = isset($f['subjects']) ? implode(', ', $f['subjects']) : '';
                                    
                                    // Status Badge styling
                                    $status_badge_class = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                                    $status_dot_class = 'bg-success';
                                    $status_text = 'Active';
                                    
                                    if ($f_status === 'leave') {
                                        $status_badge_class = 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25';
                                        $status_dot_class = 'bg-warning';
                                        $status_text = 'On Leave';
                                    } elseif ($f_status === 'inactive') {
                                        $status_badge_class = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                                        $status_dot_class = 'bg-danger';
                                        $status_text = 'Inactive';
                                    }
                            ?>
                                    <tr data-name="<?php echo htmlspecialchars(strtolower($f_name)); ?>"
                                        data-empid="<?php echo htmlspecialchars(strtolower($f_id)); ?>"
                                        data-email="<?php echo htmlspecialchars(strtolower($f_email)); ?>"
                                        data-dept="<?php echo htmlspecialchars($f_dept); ?>"
                                        data-status="<?php echo htmlspecialchars($f_status); ?>">
                                        <td class="py-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="position-relative">
                                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($f_name); ?>&background=0f172a&color=fff&bold=true" class="rounded-circle shadow-sm" width="45" alt="Faculty">
                                                    <span class="position-absolute bottom-0 end-0 p-1 <?php echo $status_dot_class; ?> border border-white rounded-circle"></span>
                                                </div>
                                                <div>
                                                    <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($f_name); ?></h6>
                                                    <small class="text-primary fw-medium">Joined: <?php echo htmlspecialchars($f_joined); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1"><?php echo htmlspecialchars($f_id); ?></span></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($f_designation); ?></div>
                                            <small class="text-muted">
                                                <?php
                                                if ($f_dept === 'CE') echo 'Computer Engineering (CE)';
                                                elseif ($f_dept === 'IT') echo 'Information Tech. (IT)';
                                                elseif ($f_dept === 'ME') echo 'Mechanical Eng. (ME)';
                                                else echo htmlspecialchars($f_dept);
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <a href="mailto:<?php echo htmlspecialchars($f_email); ?>" class="text-decoration-none text-muted small"><i class="fa-solid fa-envelope me-2 text-secondary"></i><?php echo htmlspecialchars($f_email); ?></a>
                                                <a href="tel:<?php echo htmlspecialchars($f_phone); ?>" class="text-decoration-none text-muted small"><i class="fa-solid fa-phone me-2 text-secondary"></i><?php echo htmlspecialchars($f_phone); ?></a>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $status_badge_class; ?> px-3 py-2 rounded-pill"><?php echo $status_text; ?></span>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light text-muted border-0 shadow-none" type="button" data-bs-toggle="dropdown">
                                                    <i class="fa-solid fa-ellipsis-vertical px-1"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                    <li>
                                                        <a class="dropdown-item small" href="#" onclick="openEditFacultyModal(<?php echo htmlspecialchars(json_encode([
                                                            'name' => $f_name,
                                                            'user_id' => $f_id,
                                                            'password' => $f['password'] ?? '',
                                                            'designation' => $f_designation,
                                                            'department' => $f_dept,
                                                            'email' => $f_email,
                                                            'phone' => $f_phone,
                                                            'status' => $f_status,
                                                            'joined_date' => $f_joined,
                                                            'subjects' => $f_subjects
                                                        ])); ?>); return false;">
                                                            <i class="fa-regular fa-pen-to-square me-2 text-muted"></i> Edit Details
                                                        </a>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item small text-danger" href="#" onclick="deleteFaculty('<?php echo htmlspecialchars($f_id); ?>', '<?php echo htmlspecialchars($f_name); ?>'); return false;">
                                                            <i class="fa-solid fa-trash-can me-2"></i> Remove Faculty
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                            <?php
                                endforeach;
                            endif;
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <p class="text-muted small mb-0" id="facultyShowingCount">Showing <strong><?php echo $faculty_count; ?></strong> entries</p>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled"><a class="page-link text-muted border-0" href="#">Previous</a></li>
                            <li class="page-item active"><a class="page-link border-0 shadow-sm" href="#">1</a></li>
                            <li class="page-item disabled"><a class="page-link text-muted border-0" href="#">Next</a></li>
                        </ul>
                    </nav>
                </div>

            </div>
        </div>

        <!-- OTHER TABS ... (Kept empty/static for now to focus on Faculty functionality) -->
        <div id="subject-tab" class="tab-content-section"><h4 class="fw-bold text-dark mb-4">📚 Subject Management</h4><div class="alert alert-info border-0 shadow-sm">Coming Soon</div></div>
        <div id="lab-tab" class="tab-content-section"><h4 class="fw-bold text-dark mb-4">📄 Lab Manuals</h4><div class="alert alert-info border-0 shadow-sm">Coming Soon</div></div>
        <div id="submission-tab" class="tab-content-section"><h4 class="fw-bold text-dark mb-4">📤 Submissions</h4><div class="alert alert-info border-0 shadow-sm">Coming Soon</div></div>
        <div id="review-tab" class="tab-content-section"><h4 class="fw-bold text-dark mb-4">✅ Review & Evaluation</h4><div class="alert alert-info border-0 shadow-sm">Coming Soon</div></div>
        <div id="reports-tab" class="tab-content-section"><h4 class="fw-bold text-dark mb-4">📊 Reports & Analytics</h4><div class="alert alert-info border-0 shadow-sm">Coming Soon</div></div>
        <div id="expense-tab" class="tab-content-section"><h4 class="fw-bold text-dark mb-4">💰 Expense Mgmt</h4><div class="alert alert-info border-0 shadow-sm">Coming Soon</div></div>

        <!-- ADD FACULTY MODAL -->
        <div class="modal fade" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold" id="addFacultyModalLabel"><i class="fa-solid fa-chalkboard-user me-2"></i>Add New Faculty</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="add_faculty">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Full Name</label>
                                <input type="text" name="name" class="form-control shadow-none" placeholder="e.g. Dr. Amit Patel" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted">Employee ID / Username</label>
                                    <input type="text" name="user_id" class="form-control shadow-none" placeholder="e.g. FAC-04" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted">Password</label>
                                    <input type="password" name="password" class="form-control shadow-none" placeholder="Default: faculty123" value="faculty123" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted">Designation</label>
                                    <select name="designation" class="form-select shadow-none">
                                        <option value="Head of Department">Head of Department</option>
                                        <option value="Assistant Professor" selected>Assistant Professor</option>
                                        <option value="Associate Professor">Associate Professor</option>
                                        <option value="Senior Lab Assistant">Senior Lab Assistant</option>
                                        <option value="Lab Assistant">Lab Assistant</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted">Department</label>
                                    <select name="department" class="form-select shadow-none">
                                        <option value="CE">Computer Engineering (CE)</option>
                                        <option value="IT">Information Tech. (IT)</option>
                                        <option value="ME">Mechanical Eng. (ME)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Email Address</label>
                                <input type="email" name="email" class="form-control shadow-none" placeholder="e.g. amit.patel@uni.edu">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Phone Number</label>
                                <input type="tel" name="phone" class="form-control shadow-none" placeholder="e.g. +91 98765 43210">
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted">Status</label>
                                    <select name="status" class="form-select shadow-none">
                                        <option value="active" selected>Active</option>
                                        <option value="leave">On Leave</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted">Joined Date</label>
                                    <input type="text" name="joined_date" class="form-control shadow-none" placeholder="e.g. Aug 2026" value="<?php echo date('M Y'); ?>">
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-semibold text-muted">Assigned Subjects (Comma Separated)</label>
                                <input type="text" name="subjects" class="form-control shadow-none" placeholder="e.g. Database Systems, Web Development">
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-top-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary shadow-sm"><i class="fa-solid fa-save me-1"></i>Save Faculty</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- EDIT FACULTY MODAL -->
        <div class="modal fade" id="editFacultyModal" tabindex="-1" aria-labelledby="editFacultyModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold" id="editFacultyModalLabel"><i class="fa-solid fa-user-pen me-2"></i>Edit Faculty Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="edit_faculty">
                        <input type="hidden" id="edit_original_user_id" name="original_user_id">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Full Name</label>
                                <input type="text" id="edit_name" name="name" class="form-control shadow-none" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted">Employee ID / Username</label>
                                    <input type="text" id="edit_user_id" name="user_id" class="form-control shadow-none" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted">Password (Leave blank to keep same)</label>
                                    <input type="password" name="password" class="form-control shadow-none" placeholder="Enter new password">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted">Designation</label>
                                    <select id="edit_designation" name="designation" class="form-select shadow-none">
                                        <option value="Head of Department">Head of Department</option>
                                        <option value="Assistant Professor">Assistant Professor</option>
                                        <option value="Associate Professor">Associate Professor</option>
                                        <option value="Senior Lab Assistant">Senior Lab Assistant</option>
                                        <option value="Lab Assistant">Lab Assistant</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted">Department</label>
                                    <select id="edit_department" name="department" class="form-select shadow-none">
                                        <option value="CE">Computer Engineering (CE)</option>
                                        <option value="IT">Information Tech. (IT)</option>
                                        <option value="ME">Mechanical Eng. (ME)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Email Address</label>
                                <input type="email" id="edit_email" name="email" class="form-control shadow-none">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Phone Number</label>
                                <input type="tel" id="edit_phone" name="phone" class="form-control shadow-none">
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted">Status</label>
                                    <select id="edit_status" name="status" class="form-select shadow-none">
                                        <option value="active">Active</option>
                                        <option value="leave">On Leave</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold text-muted">Joined Date</label>
                                    <input type="text" id="edit_joined_date" name="joined_date" class="form-control shadow-none">
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-semibold text-muted">Assigned Subjects (Comma Separated)</label>
                                <input type="text" id="edit_subjects" name="subjects" class="form-control shadow-none">
                            </div>
                        </div>
                        <div class="modal-footer bg-light border-top-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-success shadow-sm"><i class="fa-solid fa-check me-1"></i>Update Faculty</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap JS & Chart Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Sidebar Navigation Switcher Function
        function switchTab(tabId, element) {
            document.querySelectorAll('.tab-content-section').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.sidebar .nav-links a').forEach(nav => nav.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            element.classList.add('active');
        }

        // Dynamic Faculty Real-time search & filter
        function filterFacultyTable() {
            const searchVal = document.getElementById('facultySearchInput').value.toLowerCase().trim();
            const deptVal = document.getElementById('facultyDeptFilter').value;
            const statusVal = document.getElementById('facultyStatusFilter').value;
            
            const rows = document.querySelectorAll('#facultyTableBody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                if (row.id === 'facultyNoResultsRow' || (row.cells.length === 1 && row.cells[0].colSpan === 6)) {
                    return;
                }
                
                const name = row.getAttribute('data-name') || '';
                const empid = row.getAttribute('data-empid') || '';
                const email = row.getAttribute('data-email') || '';
                const dept = row.getAttribute('data-dept') || '';
                const status = row.getAttribute('data-status') || '';
                
                const matchesSearch = name.includes(searchVal) || empid.includes(searchVal) || email.includes(searchVal);
                const matchesDept = !deptVal || dept === deptVal;
                const matchesStatus = !statusVal || status === statusVal;
                
                if (matchesSearch && matchesDept && matchesStatus) {
                    row.style.setProperty('display', '', 'important');
                    visibleCount++;
                } else {
                    row.style.setProperty('display', 'none', 'important');
                }
            });
            
            let noResultsRow = document.getElementById('facultyNoResultsRow');
            if (visibleCount === 0) {
                if (!noResultsRow) {
                    noResultsRow = document.createElement('tr');
                    noResultsRow.id = 'facultyNoResultsRow';
                    noResultsRow.innerHTML = `
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-magnifying-glass-minus display-6 mb-3 d-block text-secondary opacity-50"></i>
                            <h6 class="fw-bold mb-1">No Matching Results</h6>
                            <p class="small text-muted mb-0">Try adjusting your search keywords or filters.</p>
                        </td>
                    `;
                    document.getElementById('facultyTableBody').appendChild(noResultsRow);
                } else {
                    noResultsRow.style.display = '';
                }
            } else if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }
            
            const countText = document.getElementById('facultyShowingCount');
            if (countText) countText.innerHTML = `Showing <strong>${visibleCount}</strong> entries`;
        }

        // Open Edit Faculty Modal & pre-fill the form
        function openEditFacultyModal(f) {
            document.getElementById('edit_original_user_id').value = f.user_id;
            document.getElementById('edit_name').value = f.name;
            document.getElementById('edit_user_id').value = f.user_id;
            document.getElementById('edit_designation').value = f.designation;
            document.getElementById('edit_department').value = f.department;
            document.getElementById('edit_email').value = f.email;
            document.getElementById('edit_phone').value = f.phone;
            document.getElementById('edit_status').value = f.status;
            document.getElementById('edit_joined_date').value = f.joined_date;
            document.getElementById('edit_subjects').value = f.subjects;
            
            const editModal = new bootstrap.Modal(document.getElementById('editFacultyModal'));
            editModal.show();
        }

        // Submit form POST request to remove faculty member
        function deleteFaculty(userId, name) {
            if (confirm(`Are you sure you want to remove ${name} from the faculty registry? This action is permanent.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_faculty';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'user_id';
                idInput.value = userId;
                
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Generate and download a CSV
        function exportFacultyCSV() {
            const rows = document.querySelectorAll('#facultyTableBody tr');
            let csvContent = "data:text/csv;charset=utf-8,Name,Employee ID,Designation,Department,Email,Phone,Status,Joined Date,Subjects\n";
            
            let exportCount = 0;
            rows.forEach(row => {
                if (row.id === 'facultyNoResultsRow' || (row.cells.length === 1 && row.cells[0].colSpan === 6)) return;
                if (row.style.display === 'none') return;
                
                const name = row.querySelector('h6').textContent.trim();
                const empid = row.cells[1].querySelector('span').textContent.trim();
                const designation = row.cells[2].querySelector('.fw-semibold').textContent.trim();
                const dept = row.getAttribute('data-dept');
                const email = row.querySelector('a[href^="mailto:"]').textContent.trim();
                const phone = row.querySelector('a[href^="tel:"]').textContent.trim();
                const status = row.getAttribute('data-status');
                const joined = row.querySelector('small.text-primary').textContent.replace('Joined: ', '').trim();
                
                const editLink = row.querySelector('a[onclick*="openEditFacultyModal"]');
                let subjects = "";
                if (editLink) {
                    const match = editLink.getAttribute('onclick').match(/openEditFacultyModal\((.*)\);/);
                    if (match && match[1]) {
                        try {
                            const escapedJson = match[1].replace(/&quot;/g, '"');
                            const data = JSON.parse(escapedJson);
                            subjects = data.subjects || "";
                        } catch (e) {}
                    }
                }
                
                const rowData = [
                    `"${name.replace(/"/g, '""')}"`,
                    `"${empid.replace(/"/g, '""')}"`,
                    `"${designation.replace(/"/g, '""')}"`,
                    `"${dept.replace(/"/g, '""')}"`,
                    `"${email.replace(/"/g, '""')}"`,
                    `"${phone.replace(/"/g, '""')}"`,
                    `"${status.replace(/"/g, '""')}"`,
                    `"${joined.replace(/"/g, '""')}"`,
                    `"${subjects.replace(/"/g, '""')}"`
                ];
                csvContent += rowData.join(",") + "\n";
                exportCount++;
            });
            
            if (exportCount === 0) {
                alert("No faculty records found to export.");
                return;
            }
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "faculty_directory.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Active tab persistence & loading check
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab');
            if (activeTab) {
                const navLink = document.querySelector(`.sidebar .nav-links a[onclick*="${activeTab}"]`);
                if (navLink) {
                    switchTab(activeTab, navLink);
                }
            }
        });

        // Initialize Doughnut Chart
        const ctx = document.getElementById('submissionsDoughnut').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending', 'Rejected'],
                datasets: [{
                    data: [650, 400, 200],
                    backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>