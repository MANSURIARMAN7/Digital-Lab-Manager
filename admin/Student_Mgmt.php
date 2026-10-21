<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch Admin Details for Profile Pill
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data['name'] ?? 'System Administrator';

// Generate Initials
$name_parts = explode(' ', trim($admin_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

$message = "";

// ==========================================
// ➕ ADD SINGLE STUDENT LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $department = $conn->real_escape_string($_POST['department']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $class_name = $conn->real_escape_string($_POST['class_name']);
    $batch = $conn->real_escape_string($_POST['batch']);

    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if($check->num_rows > 0) {
        $message = "<div class='alert alert-danger alert-dismissible fade show' style='border-radius:10px;' role='alert'>Student with this Enrollment/Email already exists!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $sql = "INSERT INTO users (name, email, password, role, department, semester, class_name, batch) 
                VALUES ('$name', '$email', '$password', 'student', '$department', '$semester', '$class_name', '$batch')";
        if ($conn->query($sql)) {
            $message = "<div class='alert alert-success alert-dismissible fade show' style='border-radius:10px;' role='alert'>Student Added Successfully to <strong>$semester - Class $class_name ($batch)</strong>!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show' style='border-radius:10px;' role='alert'>Error: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// ==========================================
// 📂 BULK IMPORT LOGIC (CSV FILE UPLOAD)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_import'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        $success_count = 0;
        $duplicate_count = 0;
        
        fgetcsv($handle); // Skip header row
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if(count($data) >= 7) {
                $name = $conn->real_escape_string($data[0]);
                $email = $conn->real_escape_string($data[1]);
                $password = password_hash($data[2], PASSWORD_DEFAULT);
                $department = $conn->real_escape_string($data[3]);
                $semester = $conn->real_escape_string($data[4]);
                $class_name = $conn->real_escape_string($data[5]);
                $batch = $conn->real_escape_string($data[6]);
                
                $check = $conn->query("SELECT user_id FROM users WHERE email='$email'");
                if($check->num_rows == 0) {
                    $sql = "INSERT INTO users (name, email, password, role, department, semester, class_name, batch) 
                            VALUES ('$name', '$email', '$password', 'student', '$department', '$semester', '$class_name', '$batch')";
                    if($conn->query($sql)) {
                        $success_count++;
                    }
                } else {
                    $duplicate_count++;
                }
            }
        }
        fclose($handle);
        $message = "<div class='alert alert-success alert-dismissible fade show' style='border-radius:10px;' role='alert'><i class='fas fa-check-circle me-2'></i>Bulk Import Complete: <strong>$success_count</strong> Students Added, <strong>$duplicate_count</strong> Duplicates Skipped.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $message = "<div class='alert alert-danger alert-dismissible fade show' style='border-radius:10px;' role='alert'>Please upload a valid CSV file.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// 📊 LIVE DB COUNTS (BY YEAR)
// ==========================================
$yr1_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student' AND (semester IN ('Semester 1', 'Semester 2', '1', '2') OR designation IN ('Semester 1', 'Semester 2', '1', '2'))");
$yr1_count = ($yr1_res) ? $yr1_res->fetch_assoc()['total'] : 0;

$yr2_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student' AND (semester IN ('Semester 3', 'Semester 4', '3', '4') OR designation IN ('Semester 3', 'Semester 4', '3', '4'))");
$yr2_count = ($yr2_res) ? $yr2_res->fetch_assoc()['total'] : 0;

$yr3_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student' AND (semester IN ('Semester 5', 'Semester 6', '5', '6') OR designation IN ('Semester 5', 'Semester 6', '5', '6'))");
$yr3_count = ($yr3_res) ? $yr3_res->fetch_assoc()['total'] : 0;

// ==========================================
// 📢 LIVE NOTICE BOARD
// ==========================================
$live_notices = $conn->query("
    SELECT s.subject_name, s.status, u.name, COALESCE(NULLIF(u.semester, ''), u.designation) AS semester 
    FROM student_submissions s
    JOIN users u ON s.student_id = u.user_id 
    ORDER BY s.submitted_at DESC LIMIT 4
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - KDP Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --sidebar-width: 270px; 
            --primary: #4338ca; 
            --primary-hover: #3730a3;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --shadow-float: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            --radius-xl: 16px;
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        /* 🔥 PREMIUM BLUE SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover, .nav-links li.active { color: #ffffff; background: rgba(255,255,255,0.2); }
        .nav-links li.active { font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid white;}
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* 🌐 TOPBAR */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 10px 22px 10px 28px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: var(--primary); font-weight: 800; text-transform: uppercase; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 17px; color: var(--text-main); font-weight: 900;}
        .profile-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.4);}

        /* 📦 CONTENT CARDS */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 30px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        .content-box:hover { box-shadow: var(--shadow-float); }
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }

        .stat-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 25px; display: flex; justify-content: space-between; align-items: center; transition: var(--transition-bounce); cursor: pointer; text-decoration: none; color: inherit; margin-bottom: 15px;}
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-float); border-color: var(--primary); background: #ffffff; }
        .stat-card-title { font-size: 14px; font-weight: 800; color: var(--text-main); margin-bottom: 5px; }
        .stat-card-value { font-size: 26px; font-weight: 900; color: var(--primary); }
        .icon-box { width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        
        .blue-box { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .green-box { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .yellow-box { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

        /* 🚀 BUTTONS & INPUTS */
        .btn-gradient { background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; border: none; font-weight: 800; padding: 12px 20px; border-radius: 8px; transition: var(--transition-bounce); display: inline-flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px;}
        .btn-gradient:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(79, 70, 229, 0.3); color: white; }
        
        .search-wrapper { display: flex; height: 45px; border-radius: 10px; overflow: hidden; border: 1px solid #cbd5e1; background: #f8fafc; transition: var(--transition-bounce);}
        .search-wrapper:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); background: #ffffff;}
        .search-wrapper input { border: none; background: transparent; padding: 0 15px; font-weight: 600; font-size: 14px; width: 100%; outline: none; }
        .search-wrapper button { border: none; background: var(--primary); color: white; padding: 0 25px; font-weight: bold; transition: 0.2s; cursor: pointer; }
        .search-wrapper button:hover { background: var(--primary-hover); }

        /* MODALS */
        .modal-content { border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .modal-header-custom { background: linear-gradient(135deg, #1e3a8a, #4338ca); padding: 18px 25px; color: white; display: flex; justify-content: space-between; align-items: center;}
        .modal-header-custom h5 { margin: 0; font-size: 18px; font-weight: 800; }
        .btn-close-custom { background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer;}
        
        .form-label { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;}
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 12px 15px; font-size: 14.5px; font-weight: 600; background: #f8fafc;}
        .form-control:focus, .form-select:focus { background: white; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); }

        /* NOTICE BOARD */
        .notice-alert { padding: 15px; border-radius: 12px; display: flex; gap: 15px; margin-bottom: 12px; align-items: flex-start; border: 1px solid; background: #f8fafc;}
        .notice-info { border-color: #cbd5e1; }
        .notice-warning { border-color: #fde68a; background: #fffbeb;}
        .notice-success { border-color: #a7f3d0; background: #ecfdf5;}
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Profile.php'"><i class="fas fa-user-shield"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- UNIFORM TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            </div>
            
            <a href="Profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">System Admin</span>
                    <h4 class="profile-name">
                        <?php 
                            $disp_name = htmlspecialchars($admin_name);
                            echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : $disp_name;
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </a>
        </div>

        <?php echo $message; ?>

        <!-- PAGE HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
            <div>
                <h3 class="fw-bold mb-1" style="font-size: 26px; color: var(--text-main);"><i class="fas fa-users-cog text-primary me-2"></i> Student Management</h3>
                <p class="text-muted fw-semibold small mb-0">Track and manage student enrollment and branch details.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-dark fw-bold border-1" data-bs-toggle="modal" data-bs-target="#bulkImportModal" style="border-radius: 8px; padding: 10px 20px;">
                    <i class="fas fa-file-csv me-2 text-success"></i> Bulk Import
                </button>
                <button class="btn-gradient" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus"></i> Add New Student
                </button>
            </div>
        </div>

        <div class="row g-4">
            <!-- LEFT COLUMN: YEAR CARDS -->
            <div class="col-md-7">
                <div class="content-box h-100">
                    <h5 class="box-title"><i class="fas fa-layer-group text-primary me-2"></i> Manage Students by Year</h5>
                    
                    <a href="view_students.php?year=1" class="stat-card">
                        <div>
                            <div class="stat-card-title">Manage Sem 1 & 2</div>
                            <div class="text-muted fw-bold small">1st Year Students</div>
                            <div class="stat-card-value mt-2"><i class="fas fa-user-check me-1 fs-5 text-primary"></i> <?php echo $yr1_count; ?></div>
                        </div>
                        <div class="icon-box blue-box"><i class="fas fa-user-graduate"></i></div>
                    </a>

                    <a href="view_students.php?year=2" class="stat-card">
                        <div>
                            <div class="stat-card-title">Manage Sem 3 & 4</div>
                            <div class="text-muted fw-bold small">2nd Year Students</div>
                            <div class="stat-card-value mt-2"><i class="fas fa-user-check me-1 fs-5 text-success"></i> <?php echo $yr2_count; ?></div>
                        </div>
                        <div class="icon-box green-box"><i class="fas fa-user-friends"></i></div>
                    </a>

                    <a href="view_students.php?year=3" class="stat-card">
                        <div>
                            <div class="stat-card-title">Manage Sem 5 & 6</div>
                            <div class="text-muted fw-bold small">3rd Year Students</div>
                            <div class="stat-card-value mt-2"><i class="fas fa-user-check me-1 fs-5 text-warning"></i> <?php echo $yr3_count; ?></div>
                        </div>
                        <div class="icon-box yellow-box"><i class="fas fa-user-cog"></i></div>
                    </a>
                </div>
            </div>

            <!-- RIGHT COLUMN: SEARCH & NOTICES -->
            <div class="col-md-5">
                
                <div class="content-box mb-4">
                    <h5 class="box-title"><i class="fas fa-search text-primary me-2"></i> Quick Search</h5>
                    <form action="view_students.php" method="GET" class="m-0">
                        <div class="search-wrapper">
                            <input type="text" name="search" placeholder="Enrollment No. or Name..." required>
                            <button type="submit"><i class="fas fa-arrow-right"></i></button>
                        </div>
                        <small class="text-muted mt-2 d-block fw-semibold"><i class="fas fa-info-circle me-1"></i> Directly find any student from any year.</small>
                    </form>
                </div>

                <div class="content-box">
                    <h5 class="box-title"><i class="fas fa-bell text-warning me-2"></i> Notice Board (Live)</h5>
                    
                    <?php if($live_notices && $live_notices->num_rows > 0): ?>
                        <?php 
                        $colors = ['notice-warning', 'notice-info', 'notice-success'];
                        $icons = ['fa-exclamation-triangle', 'fa-info-circle', 'fa-check-circle'];
                        $i = 0;
                        while($notice = $live_notices->fetch_assoc()): 
                            $colorClass = $colors[$i % 3];
                            $iconClass = $icons[$i % 3];
                        ?>
                        <div class="notice-alert <?php echo $colorClass; ?>">
                            <i class="fas <?php echo $iconClass; ?> fs-4 mt-1"></i>
                            <div>
                                <h6 class="fw-bold mb-1" style="font-size: 14px;"><?php echo htmlspecialchars($notice['subject_name']); ?></h6>
                                <p class="mb-0 text-muted fw-semibold" style="font-size: 12px;"><?php echo htmlspecialchars($notice['name']); ?> (<?php echo htmlspecialchars($notice['semester']); ?>) - <span class="fw-bold text-dark"><?php echo $notice['status']; ?></span></p>
                            </div>
                        </div>
                        <?php $i++; endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle text-muted mb-2" style="font-size:30px;"></i>
                            <h6 class="fw-bold text-dark mb-0">System Update</h6>
                            <p class="text-muted small fw-semibold">No recent submissions found.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div>

<<<<<<< HEAD
    <!-- BULK IMPORT MODAL -->
    <div class="modal fade" id="bulkImportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-custom">
                    <h5 class="mb-0"><i class="fas fa-file-csv me-2"></i> Bulk Import Students</h5>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="alert alert-info border-info border-2 rounded-3 mb-4">
                        <h6 class="fw-bold mb-2"><i class="fas fa-info-circle me-1"></i> CSV Format Required:</h6>
                        <small class="d-block mb-1">Col 1: <strong>Name</strong> | Col 2: <strong>Email</strong> | Col 3: <strong>Pass</strong> | Col 4: <strong>Dept</strong> | Col 5: <strong>Sem</strong> | Col 6: <strong>Class</strong> | Col 7: <strong>Batch</strong></small>
                        <small class="text-danger fw-bold d-block mt-2"><i class="fas fa-exclamation-triangle"></i> Row 1 must be headers and will be skipped.</small>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="form-label">Upload CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>
                        <button type="submit" name="bulk_import" class="btn-gradient w-100 justify-content-center">
                            <i class="fas fa-cloud-upload-alt me-2"></i> Start Import Process
                        </button>
                    </form>
                </div>
=======
    <!-- MODALS -->

    <!-- 1. Add Student Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i> Add New Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="alert alert-info small fw-bold mb-3"><i class="fas fa-info-circle me-1"></i> Default password will be <b>123456</b></div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Enrollment No. <span class="text-danger">*</span></label>
                            <input type="text" name="enrollment" class="form-control" required placeholder="e.g. 226310307001">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="Student Name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Department <span class="text-danger">*</span></label>
                            <select name="department" class="form-select" required>
                                <option value="" disabled selected>-- Select --</option>
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="IT Engineering">IT Engineering</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Sem <span class="text-danger">*</span></label>
                                <select name="semester" class="form-select" required>
                                    <option value="" disabled selected>-</option>
                                    <option value="1">1</option><option value="2">2</option><option value="3">3</option>
                                    <option value="4">4</option><option value="5">5</option><option value="6">6</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Batch <span class="text-danger">*</span></label>
                                <select name="batch" class="form-select" required>
                                    <option value="" disabled selected>-- Select Batch --</option>
                                    <option value="A1">A1</option><option value="A2">A2</option><option value="A3">A3</option>
                                    <option value="B1">B1</option><option value="B2">B2</option><option value="B3">B3</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="email" name="email" class="form-control" placeholder="student@example.com">
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 rounded-bottom-4">
                        <button type="submit" name="add_student" class="btn btn-primary fw-bold px-4 w-100">Save Student</button>
                    </div>
                </form>
>>>>>>> pr-arman-to-main
            </div>
        </div>
    </div>

<<<<<<< HEAD
    <!-- ADD STUDENT MODAL -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-custom">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i> Add New Student</h5>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
                </div>
                <form action="" method="POST" class="p-4 bg-white">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Arman Mansuri" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Enrollment No / Email</label>
                            <input type="text" name="email" class="form-control" placeholder="e.g. 226170307085" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Branch / Department</label>
                            <select name="department" class="form-select" required>
                                <option value="" selected disabled>Select Branch</option>
                                <option value="Computer Engineering">Computer Engg.</option>
                                <option value="IT Engineering">IT Engg.</option>
                                <option value="Civil Engineering">Civil Engg.</option>
                                <option value="Mechanical Engineering">Mechanical Engg.</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select" required>
                                <option value="" selected disabled>Select Semester</option>
                                <option value="Semester 1">Semester 1 (1st Year)</option>
                                <option value="Semester 2">Semester 2 (1st Year)</option>
                                <option value="Semester 3">Semester 3 (2nd Year)</option>
                                <option value="Semester 4">Semester 4 (2nd Year)</option>
                                <option value="Semester 5">Semester 5 (3rd Year)</option>
                                <option value="Semester 6">Semester 6 (3rd Year)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Class</label>
                            <select name="class_name" class="form-select" required>
                                <option value="" selected disabled>Select Class</option>
                                <option value="A">Class A</option>
                                <option value="B">Class B</option>
                                <option value="C">Class C</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batch</label>
                            <select name="batch" class="form-select" required>
                                <option value="" selected disabled>Select Batch</option>
                                <option value="A1">Batch A1</option>
                                <option value="A2">Batch A2</option>
                                <option value="B1">Batch B1</option>
                                <option value="B2">Batch B2</option>
                                <option value="C1">Batch C1</option>
                                <option value="C2">Batch C2</option>
                            </select>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label">Login Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Default Password" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_student" class="btn-gradient w-100 justify-content-center">
                        <i class="fas fa-save me-2"></i> Save Student Record
                    </button>
=======
    <!-- 2. Edit Student Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-dark text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2"></i> Edit Student Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Enrollment No. (Locked)</label>
                            <input type="text" name="edit_enrollment" id="edit_enrollment" class="form-control bg-light" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="edit_name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Department <span class="text-danger">*</span></label>
                            <select name="edit_department" id="edit_department" class="form-select" required>
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="IT Engineering">IT Engineering</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Sem <span class="text-danger">*</span></label>
                                <select name="edit_semester" id="edit_semester" class="form-select" required>
                                    <option value="1">1</option><option value="2">2</option><option value="3">3</option>
                                    <option value="4">4</option><option value="5">5</option><option value="6">6</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Batch <span class="text-danger">*</span></label>
                                <select name="edit_batch" id="edit_batch" class="form-select" required>
                                    <option value="A1">A1</option><option value="A2">A2</option><option value="A3">A3</option>
                                    <option value="B1">B1</option><option value="B2">B2</option><option value="B3">B3</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="email" name="edit_email" id="edit_email" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 rounded-bottom-4">
                        <button type="submit" name="edit_student" class="btn btn-dark fw-bold px-4 w-100">Update Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. Bulk Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-success text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-csv me-2"></i> Bulk Import Students</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <div class="alert alert-warning small fw-bold mb-4">
                            <i class="fas fa-info-circle me-1"></i> Ensure CSV columns exactly match:<br>
                            <strong>Enrollment, Name, Branch, Semester, Batch, Email</strong><br>
                            <em>(Duplicates safely skipped. Password set to 123456)</em>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Upload CSV File <span class="text-danger">*</span></label>
                            <input type="file" name="csv_file" class="form-control p-2" accept=".csv" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 rounded-bottom-4">
                        <button type="submit" name="import_csv" class="btn btn-success fw-bold px-4 w-100">Run Import Process</button>
                    </div>
>>>>>>> pr-arman-to-main
                </form>
            </div>
        </div>
    </div>

    <!-- 4. Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-body p-4 text-center">
                    <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size: 50px;"></i>
                    <h4 class="fw-bold text-dark mb-2">Delete Student?</h4>
                    <p class="text-muted fw-semibold mb-4">You are about to delete <strong id="delete_student_name" class="text-danger"></strong>. All their lab submissions will also be wiped out. This action cannot be undone.</p>
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="delete_id" id="delete_id_input">
                        <button type="button" class="btn btn-light fw-bold w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_student_btn" class="btn btn-danger fw-bold w-50">Yes, Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Reset Password Modal -->
    <div class="modal fade" id="resetModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-body p-4 text-center">
                    <i class="fas fa-key text-warning mb-3" style="font-size: 50px;"></i>
                    <h4 class="fw-bold text-dark mb-2">Reset Password</h4>
                    <p class="text-muted fw-semibold mb-4">Reset password to <b>123456</b> for <strong id="reset_student_name" class="text-primary"></strong>?</p>
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="reset_id" id="reset_id_input">
                        <button type="button" class="btn btn-light fw-bold w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reset_pass_btn" class="btn btn-warning text-dark fw-bold w-50">Yes, Reset</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<<<<<<< HEAD
    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();
=======
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#studentTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [10, 25, 50, 100],
                "searching": false, // PHP custom search handled
                "ordering": false,  // PHP custom sorting handled
                "info": true,
                "language": {
                    "lengthMenu": "Display _MENU_ records"
                }
            });
        });

        function openEditModal(enroll, name, dept, sem, batch, email) {
            document.getElementById('edit_enrollment').value = enroll;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_department').value = dept;
            document.getElementById('edit_semester').value = sem;
            document.getElementById('edit_batch').value = batch;
            document.getElementById('edit_email').value = email;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function openDeleteModal(enroll, name) {
            document.getElementById('delete_id_input').value = enroll;
            document.getElementById('delete_student_name').innerText = name + " (" + enroll + ")";
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        function openResetModal(enroll, name) {
            document.getElementById('reset_id_input').value = enroll;
            document.getElementById('reset_student_name').innerText = name + " (" + enroll + ")";
            new bootstrap.Modal(document.getElementById('resetModal')).show();
        }
>>>>>>> pr-arman-to-main
    </script>
</body>
</html>