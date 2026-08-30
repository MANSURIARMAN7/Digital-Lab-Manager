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

// ==========================================
// 🚀 ADD NEW STUDENT LOGIC (BACKEND)
// ==========================================
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']); 
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $department = $conn->real_escape_string($_POST['department']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $class_name = $conn->real_escape_string($_POST['class_name']);
    $batch = $conn->real_escape_string($_POST['batch']);

    // Check if email/enrollment already exists
    $check = $conn->query("SELECT * FROM users WHERE email='$email'");
    if($check->num_rows > 0) {
        $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Student with this Enrollment/Email already exists!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        // Insert Query with class_name and batch
        $sql = "INSERT INTO users (name, email, password, role, department, semester, class_name, batch) 
                VALUES ('$name', '$email', '$password', 'student', '$department', '$semester', '$class_name', '$batch')";
        if ($conn->query($sql)) {
            $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Student Added Successfully to $semester - Class $class_name ($batch)!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Error: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// ==========================================
// 📊 LIVE DB COUNTS (BY YEAR) - FIXED! 🐛🔨
// ==========================================
// Ab yeh 'semester' aur 'designation' dono column check karega taaki 178 students count ho jayein
$yr1_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student' AND (semester IN ('Semester 1', 'Semester 2', '1', '2') OR designation IN ('Semester 1', 'Semester 2', '1', '2'))");
$yr1_count = ($yr1_res) ? $yr1_res->fetch_assoc()['total'] : 0;

$yr2_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student' AND (semester IN ('Semester 3', 'Semester 4', '3', '4') OR designation IN ('Semester 3', 'Semester 4', '3', '4'))");
$yr2_count = ($yr2_res) ? $yr2_res->fetch_assoc()['total'] : 0;

$yr3_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student' AND (semester IN ('Semester 5', 'Semester 6', '5', '6') OR designation IN ('Semester 5', 'Semester 6', '5', '6'))");
$yr3_count = ($yr3_res) ? $yr3_res->fetch_assoc()['total'] : 0;

// ==========================================
// 📢 LIVE NOTICE BOARD - FIXED TABLE NAME! 🐛🔨
// ==========================================
// Table name 'submissions' ki jagah 'student_submissions' kar diya
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-width: 260px; --bg-color: #f8fafc; --sidebar-bg: #1a365d; --accent-blue: #2563eb; }
        body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        .sidebar { width: var(--sidebar-width); background-color: var(--sidebar-bg); color: #ffffff; display: flex; flex-direction: column; z-index: 10; overflow-y: auto; }
        .sidebar-logo-container { padding: 30px 20px 20px 20px; display: flex; flex-direction: column; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; }
        .sidebar-logo-container img { width: 90px; height: 90px; object-fit: contain; margin-bottom: 15px; border-radius: 50%; padding: 5px; background: rgba(255,255,255,0.1); }
        .sidebar-title h2 { font-size: 18px; font-weight: 700; margin: 0; line-height: 1.2; letter-spacing: 0.5px; color: #fff;}
        .sidebar-subtitle { font-size: 13px; color: #94a3b8; margin-top: 5px; font-weight: 500;}
        .nav-links { list-style: none; padding: 20px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 12px 20px; margin: 5px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 500; color: #a0aec0; transition: all 0.3s ease; }
        .nav-links li:hover { color: white; background: rgba(255,255,255,0.08); }
        .nav-links li.active { background: var(--accent-blue); color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4); font-weight: 600; }
        .main { flex: 1; padding: 30px 40px; overflow-y: auto; }
        .topbar { background: transparent; padding: 0 0 10px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px;}
        .search-box { background: #fff; border-radius: 8px; padding: 10px 15px; display: flex; align-items: center; gap: 10px; width: 350px; border: 1px solid #e2e8f0; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .search-box input { border: none; background: transparent; outline: none; font-size: 14px; width: 100%; color: #334155; }
        .profile-pill { display: flex; align-items: center; background-color: #ffffff; padding: 6px 16px 6px 20px; border-radius: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; cursor: pointer; text-decoration: none; color: inherit; transition: all 0.2s;}
        .profile-text { text-align: right; margin-right: 15px; }
        .profile-welcome { display: block; font-size: 9.5px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 14px; color: #1e293b; font-weight: 700; }
        .profile-avatar { width: 42px; height: 42px; background-color: var(--accent-blue); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; box-shadow: 0 3px 8px rgba(37, 99, 235, 0.4); letter-spacing: 1px;}
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .btn-primary-custom { background: var(--accent-blue); color: white; padding: 10px 20px; border-radius: 8px; font-weight: 600; border: none; box-shadow: 0 4px 10px rgba(37,99,235,0.2); transition: 0.2s; }
        .btn-primary-custom:hover { background: #1d4ed8; transform: translateY(-2px); color: white;}
        .year-card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); position: relative; overflow: hidden; cursor: pointer; transition: 0.2s;}
        .year-card:hover { transform: translateX(5px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .year-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 6px; }
        .yr1-border::before { background: var(--accent-blue); }
        .yr2-border::before { background: #10b981; }
        .yr3-border::before { background: #f59e0b; }
        .card-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .yr1-icon { background: rgba(37,99,235,0.1); color: var(--accent-blue); }
        .yr2-icon { background: rgba(16,185,129,0.1); color: #10b981; }
        .yr3-icon { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .panel-box { background: white; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); margin-bottom: 25px;}
        .search-btn { background: var(--accent-blue); color: white; border: none; border-radius: 0 8px 8px 0; padding: 0 20px; }
        .notice-alert { padding: 15px 20px; border-radius: 10px; display: flex; gap: 15px; margin-bottom: 15px; align-items: flex-start; }
        .notice-icon { font-size: 18px; margin-top: 2px; }
        .notice-warning { background: #fef3c7; color: #92400e; }
        .notice-info { background: #e0f2fe; color: #075985; }
        .notice-success { background: #dcfce3; color: #166534; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-alt"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-folder-open"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-circle"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar mb-4">
            <div class="search-box">
                <i class="fas fa-search text-muted"></i>
                <input type="text" placeholder="Search globally...">
            </div>
            <div class="d-flex align-items-center gap-4">
                <div class="position-relative" style="cursor: pointer; padding: 8px; background: white; border-radius: 8px; border: 1px solid #e2e8f0;" onclick="window.location.href='Submissions.php'">
                    <i class="far fa-bell text-secondary fs-5"></i>
                </div>
                <a href="Profile.php" class="profile-pill">
                    <div class="profile-text">
                        <span class="profile-welcome">Welcome Back,</span>
                        <h4 class="profile-name">
                            <?php 
                                $name_parts = explode(' ', $admin_name);
                                echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : 'Admin';
                            ?>
                        </h4>
                    </div>
                    <div class="profile-avatar">HOD</div>
                </a>
            </div>
        </div>

        <?php echo $message; ?>

        <div class="page-header">
            <div class="d-flex align-items-center gap-3">
                <div style="font-size: 28px;">👨‍🎓</div>
                <div>
                    <h3 class="fw-bold text-dark mb-1" style="font-size: 22px;">Student Management & Tracker</h3>
                    <p class="text-muted small mb-0">Track student laboratory manuals, academic branch details, and submission progress.</p>
                </div>
            </div>
            <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="fas fa-plus me-2"></i> Add New Student
            </button>
        </div>

        <div class="row">
            <div class="col-md-7">
                <h5 class="fw-bold mb-3" style="color: #1e293b;">Manage Students by Year</h5>

                <!-- CARDS LINKED TO VIEW_STUDENTS.PHP -->
                <div class="year-card yr1-border" onclick="window.location.href='view_students.php?year=1'">
                    <div>
                        <small class="text-muted fw-semibold">Manage Sem 1 & 2</small>
                        <h4 class="fw-bold text-dark mb-0 mt-1">1st Year Students</h4>
                        <small class="text-primary fw-bold"><?php echo $yr1_count; ?> Students Enrolled</small>
                    </div>
                    <div class="card-icon yr1-icon"><i class="fas fa-users"></i></div>
                </div>

                <div class="year-card yr2-border" onclick="window.location.href='view_students.php?year=2'">
                    <div>
                        <small class="text-muted fw-semibold">Manage Sem 3 & 4</small>
                        <h4 class="fw-bold text-dark mb-0 mt-1">2nd Year Students</h4>
                        <small class="text-success fw-bold"><?php echo $yr2_count; ?> Students Enrolled</small>
                    </div>
                    <div class="card-icon yr2-icon"><i class="fas fa-user-friends"></i></div>
                </div>

                <div class="year-card yr3-border" onclick="window.location.href='view_students.php?year=3'">
                    <div>
                        <small class="text-muted fw-semibold">Manage Sem 5 & 6</small>
                        <h4 class="fw-bold text-dark mb-0 mt-1">3rd Year Students</h4>
                        <small class="text-warning fw-bold" style="color: #d97706 !important;"><?php echo $yr3_count; ?> Students Enrolled</small>
                    </div>
                    <div class="card-icon yr3-icon"><i class="fas fa-users-cog"></i></div>
                </div>
            </div>

            <div class="col-md-5">
                <h5 class="fw-bold mb-3" style="color: #1e293b;">Quick Actions & Updates</h5>
                <div class="panel-box mb-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-search text-primary me-2"></i> Quick Student Search</h6>
                    <form action="search_student.php" method="GET" class="d-flex">
                        <input type="text" name="query" class="form-control bg-light" placeholder="Enter Enrollment No..." style="border-radius: 8px 0 0 8px; border: 1px solid #e2e8f0; border-right: none;" required>
                        <button type="submit" class="search-btn">Search</button>
                    </form>
                    <small class="text-muted mt-2 d-block">Directly find any student from any year.</small>
                </div>

                <h6 class="fw-bold mb-3"><i class="fas fa-bullhorn text-warning me-2"></i> Notice Board (Live)</h6>
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
                                <h6 class="fw-bold mb-1" style="font-size: 14px;">New Submission: <?php echo htmlspecialchars($notice['subject_name']); ?></h6>
                                <p class="mb-0" style="font-size: 13px;"><?php echo htmlspecialchars($notice['name']); ?> (<?php echo htmlspecialchars($notice['semester']); ?>) - <?php echo $notice['status']; ?></p>
                            </div>
                        </div>
                    <?php $i++; endwhile; ?>
                <?php else: ?>
                    <div class="notice-alert notice-info">
                        <i class="fas fa-info-circle notice-icon"></i>
                        <div>
                            <h6 class="fw-bold mb-1" style="font-size: 14px;">System Update</h6>
                            <p class="mb-0" style="font-size: 13px;">No recent submissions found in the database.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ADD STUDENT MODAL -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 12px; border: none;">
                <div class="modal-header" style="background: var(--bg-color); border-bottom: 1px solid #e2e8f0;">
                    <h5 class="modal-title fw-bold" style="color: #1e293b;"><i class="fas fa-user-plus text-primary me-2"></i> Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Full Name</label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. Arman Mansuri">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Enrollment No / Email</label>
                                <input type="text" name="email" class="form-control" required placeholder="e.g. 236170307001">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Branch / Department</label>
                                <select name="department" class="form-select" required>
                                    <option value="" selected disabled>Select Branch</option>
                                    <option value="Computer Engineering">Computer Engg.</option>
                                    <option value="IT Engineering">IT Engg.</option>
                                    <option value="Civil Engineering">Civil Engg.</option>
                                    <option value="Mechanical Engineering">Mechanical Engg.</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Semester</label>
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
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Class</label>
                                <select name="class_name" class="form-select" required>
                                    <option value="" selected disabled>Select Class</option>
                                    <option value="A">Class A</option>
                                    <option value="B">Class B</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Batch</label>
                                <select name="batch" class="form-select" required>
                                    <option value="" selected disabled>Select Batch</option>
                                    <option value="A1">Batch A1</option>
                                    <option value="A2">Batch A2</option>
                                    <option value="B1">Batch B1</option>
                                    <option value="B2">Batch B2</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Login Password</label>
                                <input type="password" name="password" class="form-control" required placeholder="Default Password">
                            </div>
                        </div>
                        <button type="submit" name="add_student" class="btn btn-primary w-100 fw-bold" style="padding: 10px; border-radius: 8px;">
                            <i class="fas fa-save me-1"></i> Save Student Record
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

