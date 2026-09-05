<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch Admin Details for Profile
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data['name'] ?? 'System Administrator';

$message = "";

// ==========================================
// 🚀 ADD SINGLE STUDENT LOGIC
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
            $message = "<div class='alert alert-success alert-dismissible fade show' style='border-radius:10px;' role='alert'>Student Added Successfully to $semester - Class $class_name ($batch)!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show' style='border-radius:10px;' role='alert'>Error: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// ==========================================
// 🚀 BULK IMPORT LOGIC (CSV FILE UPLOAD)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bulk_import'])) {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        $success_count = 0;
        $duplicate_count = 0;

        fgetcsv($handle); // Skip header

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
    ORDER BY s.submitted_at DESC LIMIT 3
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Admin</title>
    
    <!-- Bootstrap, FontAwesome & PREMIUM GOOGLE FONT -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- 🚀 PREMIUM MODERN UI CSS -->
    <style>
        :root { 
            --sidebar-width: 270px; 
            --primary: #4338ca; 
            --primary-hover: #3730a3;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --shadow-float: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            --radius-xl: 16px;
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { 
            background-color: var(--bg-body); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main);
        }
        
        /* 🔥 PREMIUM BLUE SIDEBAR */
        .sidebar { 
            width: var(--sidebar-width); 
            background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%);
            color: #ffffff; display: flex; flex-direction: column; z-index: 10; overflow-y: auto; 
            box-shadow: 4px 0 24px rgba(0,0,0,0.08);
        }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0; letter-spacing: 0.5px; color: #ffffff;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { 
            padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; 
            font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); border-left: 3px solid transparent;
        }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .nav-links li.active { 
            background: rgba(255, 255, 255, 0.2); 
            color: #ffffff; border-left: 4px solid #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        /* ✨ MAIN CONTENT ANIMATION */
        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* 🌈 TOPBAR & PROFILE PILL */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { 
            display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; 
            border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; 
            transition: var(--transition-bounce); box-shadow: var(--shadow-float);
        }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);}

        /* 🏆 FLOATING STAT CARDS (Year Cards) */
        .stat-card { 
            background: var(--surface); border-radius: var(--radius-xl); padding: 26px; border: 1px solid rgba(226, 232, 240, 0.8); 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; transition: var(--transition-bounce); cursor: pointer; margin-bottom: 20px;
        }
        .stat-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-float); border-color: #cbd5e1; }
        .stat-card-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; color: var(--text-muted); }
        .stat-card-value { font-size: 24px; font-weight: 800; color: var(--text-main); margin-top: 6px; margin-bottom: 0; line-height: 1.2; }
        
        .icon-box { width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: var(--transition-bounce); }
        .stat-card:hover .icon-box { transform: scale(1.1) rotate(5deg); }
        
        .blue-box { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .green-box { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .yellow-box { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

        /* 📦 CONTENT BOXES */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 30px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        .content-box:hover { box-shadow: var(--shadow-float); }
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 15px; }

        /* 🚀 PREMIUM BUTTONS */
        .btn-gradient { 
            background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; border: none; font-weight: 700; padding: 10px 20px; border-radius: 10px; 
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); transition: var(--transition-bounce);
        }
        .btn-gradient:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4); color: white; }
        
        .btn-outline-modern {
            background: white; color: var(--text-main); font-weight: 700; padding: 10px 20px; border-radius: 10px; border: 1px solid #cbd5e1; transition: var(--transition-bounce);
        }
        .btn-outline-modern:hover { background: #f8fafc; transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }

        /* 🔔 NOTICES */
        .notice-alert { 
            padding: 16px 20px; border-radius: 12px; display: flex; gap: 15px; margin-bottom: 15px; align-items: flex-start; 
            border: 1px solid rgba(226, 232, 240, 0.8); transition: var(--transition-bounce); background: var(--surface);
        }
        .notice-alert:hover { transform: translateX(5px); box-shadow: var(--shadow-float); }
        .notice-icon { font-size: 20px; margin-top: 2px; }
        .notice-warning { background: rgba(245, 158, 11, 0.05); border-color: rgba(245, 158, 11, 0.2); color: #b45309; }
        .notice-info { background: rgba(59, 130, 246, 0.05); border-color: rgba(59, 130, 246, 0.2); color: #1d4ed8; }
        .notice-success { background: rgba(16, 185, 129, 0.05); border-color: rgba(16, 185, 129, 0.2); color: #047857; }

        /* SEARCH INPUT */
        .search-modern { border-radius: 10px 0 0 10px !important; border: 1px solid #cbd5e1; font-weight: 500; font-size: 14px;}
        .search-modern:focus { box-shadow: none; border-color: var(--primary); }
        .btn-search { border-radius: 0 10px 10px 0 !important; background: var(--primary); color: white; padding: 0 20px; font-weight: 700; border: none; transition: var(--transition-bounce);}
        .btn-search:hover { background: var(--primary-hover); }

        /* SCROLLBAR */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-pdf"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-inbox"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-double"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge">
                    <i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span>
                </div>
                
                <!-- GLOBAL SEARCH FOR TOPBAR -->
                <form action="view_students.php" method="GET" class="d-flex shadow-sm" style="border-radius: 10px;">
                    <input type="text" name="search" class="form-control search-modern px-4" placeholder="Search globally..." required style="width: 250px;">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                </form>
            </div>
            
            <a href="Profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">K.D. Polytechnic</span>
                    <h4 class="profile-name">
                        <?php 
                            $name_parts = explode(' ', $admin_name);
                            echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : 'Admin';
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar">AD</div>
            </a>
        </div>

        <?php echo $message; ?>

        <!-- PAGE HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-5 mt-2">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box blue-box" style="font-size: 26px; width: 60px; height: 60px;"><i class="fas fa-users-cog"></i></div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);">Student Management</h3>
                    <p class="text-muted fw-semibold small mb-0">Track and manage student laboratory manuals and branch details.</p>
                </div>
            </div>
            <div class="d-flex gap-3">
                <button class="btn-outline-modern" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
                    <i class="fas fa-file-csv me-2 text-success"></i> Bulk Import
                </button>
                <button class="btn-gradient" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class="fas fa-plus me-2"></i> Add New Student
                </button>
            </div>
        </div>

        <div class="row g-4">
            <!-- LEFT COLUMN: YEAR CARDS -->
            <div class="col-md-7">
                <h5 class="fw-bold mb-4" style="color: var(--text-main);">Manage Students by Year</h5>

                <div class="stat-card" onclick="window.location.href='view_students.php?year=1'">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-card-title">Manage Sem 1 & 2</div>
                            <div class="stat-card-value">1st Year Students</div>
                            <div class="mt-2 fw-bold" style="color: #3b82f6; font-size: 13px;"><i class="fas fa-user-check me-1"></i> <?php echo $yr1_count; ?> Students Enrolled</div>
                        </div>
                        <div class="icon-box blue-box"><i class="fas fa-users"></i></div>
                    </div>
                </div>

                <div class="stat-card" onclick="window.location.href='view_students.php?year=2'">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-card-title">Manage Sem 3 & 4</div>
                            <div class="stat-card-value">2nd Year Students</div>
                            <div class="mt-2 fw-bold" style="color: #10b981; font-size: 13px;"><i class="fas fa-user-check me-1"></i> <?php echo $yr2_count; ?> Students Enrolled</div>
                        </div>
                        <div class="icon-box green-box"><i class="fas fa-user-friends"></i></div>
                    </div>
                </div>

                <div class="stat-card" onclick="window.location.href='view_students.php?year=3'">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-card-title">Manage Sem 5 & 6</div>
                            <div class="stat-card-value">3rd Year Students</div>
                            <div class="mt-2 fw-bold" style="color: #f59e0b; font-size: 13px;"><i class="fas fa-user-check me-1"></i> <?php echo $yr3_count; ?> Students Enrolled</div>
                        </div>
                        <div class="icon-box yellow-box"><i class="fas fa-users-cog"></i></div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: SEARCH & NOTICES -->
            <div class="col-md-5">
                <h5 class="fw-bold mb-4" style="color: var(--text-main);">Quick Actions & Updates</h5>
                
                <div class="content-box">
                    <h6 class="box-title"><i class="fas fa-search text-primary me-2"></i> Quick Search</h6>
                    <form action="view_students.php" method="GET" class="d-flex mt-3 shadow-sm" style="border-radius: 10px;">
                        <input type="text" name="search" class="form-control search-modern" placeholder="Enrollment No. or Name..." required>
                        <button type="submit" class="btn-search"><i class="fas fa-arrow-right"></i></button>
                    </form>
                    <small class="text-muted mt-3 d-block fw-semibold">Directly find any student from any year.</small>
                </div>

                <div class="mt-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-bell text-warning me-2"></i> Notice Board (Live)</h6>
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
                                <i class="fas <?php echo $iconClass; ?> notice-icon"></i>
                                <div>
                                    <h6 class="fw-bold mb-1" style="font-size: 14px;">New: <?php echo htmlspecialchars($notice['subject_name']); ?></h6>
                                    <p class="mb-0 text-muted fw-semibold" style="font-size: 12.5px;"><?php echo htmlspecialchars($notice['name']); ?> (<?php echo htmlspecialchars($notice['semester']); ?>) - <?php echo $notice['status']; ?></p>
                                </div>
                            </div>
                        <?php $i++; endwhile; ?>
                    <?php else: ?>
                        <div class="notice-alert notice-info">
                            <i class="fas fa-info-circle notice-icon"></i>
                            <div>
                                <h6 class="fw-bold mb-1" style="font-size: 14px;">System Update</h6>
                                <p class="mb-0 text-muted fw-semibold" style="font-size: 12.5px;">No recent submissions found.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- BULK IMPORT MODAL -->
    <div class="modal fade" id="bulkImportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                <div class="modal-header" style="background: var(--bg-body); border-bottom: 1px solid #e2e8f0; padding: 20px 25px;">
                    <h5 class="modal-title fw-bold" style="color: var(--text-main);"><i class="fas fa-file-csv text-success me-2"></i> Bulk Import Students</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info" style="font-size: 13px; border-left: 4px solid #0ea5e9; border-radius: 10px; font-weight: 600;">
                        <strong><i class="fas fa-info-circle me-1"></i> CSV Format Required:</strong><br>
                        Col 1: Name | Col 2: Email | Col 3: Pass | Col 4: Dept | Col 5: Sem | Col 6: Class | Col 7: Batch<br>
                        <em class="text-muted mt-1 d-block">(Row 1 must be headers and will be skipped).</em>
                    </div>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-4 mt-3">
                            <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Upload CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required style="border-radius: 10px; padding: 12px;">
                        </div>
                        <button type="submit" name="bulk_import" class="btn-gradient w-100">
                            <i class="fas fa-cloud-upload-alt me-2"></i> Start Import Process
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ADD STUDENT MODAL -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                <div class="modal-header" style="background: var(--bg-body); border-bottom: 1px solid #e2e8f0; padding: 20px 25px;">
                    <h5 class="modal-title fw-bold" style="color: var(--text-main);"><i class="fas fa-user-plus text-primary me-2"></i> Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Full Name</label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. Arman Mansuri" style="border-radius: 10px; padding: 12px;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Enrollment No / Email</label>
                                <input type="text" name="email" class="form-control" required placeholder="e.g. 236170307001" style="border-radius: 10px; padding: 12px;">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Branch / Department</label>
                                <select name="department" class="form-select" required style="border-radius: 10px; padding: 12px;">
                                    <option value="" selected disabled>Select Branch</option>
                                    <option value="Computer Engineering">Computer Engg.</option>
                                    <option value="IT Engineering">IT Engg.</option>
                                    <option value="Civil Engineering">Civil Engg.</option>
                                    <option value="Mechanical Engineering">Mechanical Engg.</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Semester</label>
                                <select name="semester" class="form-select" required style="border-radius: 10px; padding: 12px;">
                                    <option value="" selected disabled>Select Semester</option>
                                    <option value="Semester 1">Semester 1 (1st Year)</option>
                                    <option value="Semester 2">Semester 2 (1st Year)</option>
                                    <option value="Semester 3">Semester 3 (2nd Year)</option>
                                    <option value="Semester 4">Semester 4 (2nd Year)</option>
                                    <option value="Semester 5">Semester 5 (3rd Year)</option>
                                    <option value="Semester 6">Semester 6 (3rd Year)</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Class</label>
                                <select name="class_name" class="form-select" required style="border-radius: 10px; padding: 12px;">
                                    <option value="" selected disabled>Select Class</option>
                                    <option value="A">Class A</option>
                                    <option value="B">Class B</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Batch</label>
                                <select name="batch" class="form-select" required style="border-radius: 10px; padding: 12px;">
                                    <option value="" selected disabled>Select Batch</option>
                                    <option value="A1">Batch A1</option>
                                    <option value="A2">Batch A2</option>
                                    <option value="B1">Batch B1</option>
                                    <option value="B2">Batch B2</option>
                                    <option value="B3">Batch B3</option>
                                    <option value="B4">Batch B4</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Login Password</label>
                                <input type="password" name="password" class="form-control" required placeholder="Default Password" style="border-radius: 10px; padding: 12px;">
                            </div>
                        </div>
                        <button type="submit" name="add_student" class="btn-gradient w-100 mt-2">
                            <i class="fas fa-save me-2"></i> Save Student Record
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>