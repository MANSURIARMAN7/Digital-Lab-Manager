<?php
session_start();
include '../db.php';

// 1. Admin Login Check
//  ========
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ==========================================
// 📥 EXPORT LOGIC (CSV / EXCEL DOWNLOADS)
// ==========================================
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $filter_dept = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : 'all';
    $filter_sem = isset($_GET['semester']) ? $conn->real_escape_string($_GET['semester']) : 'all';
    
    $filename = "KDP_" . ucfirst($export_type) . "_Report_" . date('Y-m-d') . ".csv";
    
    // Force browser to download file
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');
    
    // Base Where clause for filters
    $where_users = "1=1";
    if($filter_dept !== 'all') $where_users .= " AND department = '$filter_dept'";
    if($filter_sem !== 'all') $where_users .= " AND (semester = '$filter_sem' OR designation = '$filter_sem')"; 

    if ($export_type === 'students') {
        fputcsv($output, array('Student ID', 'Full Name', 'Enrollment / Email', 'Branch', 'Semester', 'Class', 'Batch'));
        $res = $conn->query("SELECT user_id, name, email, department, semester, class_name, batch FROM users WHERE role='student' AND $where_users ORDER BY name ASC");
        if($res) { while ($row = $res->fetch_assoc()) fputcsv($output, $row); }
    } 
    elseif ($export_type === 'submissions') {
        fputcsv($output, array('Submission ID', 'Student Name', 'Enrollment', 'Subject', 'Status', 'Marks', 'Submitted Date'));
        $res = $conn->query("SELECT s.submission_id, u.name, u.email, s.subject_name, s.status, s.marks, s.submitted_at FROM student_submissions s JOIN users u ON s.student_id = u.user_id WHERE $where_users ORDER BY s.submitted_at DESC");
        if($res) { while ($row = $res->fetch_assoc()) fputcsv($output, $row); }
    } 
    elseif ($export_type === 'faculty') {
        fputcsv($output, array('Faculty ID', 'Name', 'Email', 'Department'));
        $res = $conn->query("SELECT user_id, name, email, department FROM users WHERE role='faculty' AND ($filter_dept = 'all' OR department = '$filter_dept') ORDER BY name ASC");
        if($res) { while ($row = $res->fetch_assoc()) fputcsv($output, $row); }
    } 
    elseif ($export_type === 'master_excel') {
        fputcsv($output, array('Student Name', 'Enrollment', 'Branch', 'Semester', 'Subject', 'Status', 'Marks', 'Date'));
        $res = $conn->query("SELECT u.name, u.email, u.department, COALESCE(NULLIF(u.semester, ''), u.designation) as semester, s.subject_name, s.status, s.marks, s.submitted_at FROM student_submissions s JOIN users u ON s.student_id = u.user_id WHERE $where_users ORDER BY s.submitted_at DESC");
        if($res) { while ($row = $res->fetch_assoc()) fputcsv($output, $row); }
    }
    
    fclose($output);
    exit(); // Stop script after download
}


// Fetch Admin Details for Profile Pill
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query ? $admin_query->fetch_assoc() : null;
$admin_name = $admin_data['name'] ?? 'System Administrator';

// Get Current Filters for Display
$current_dept = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : 'all';
$current_sem = isset($_GET['semester']) ? $conn->real_escape_string($_GET['semester']) : 'all';

// Base queries for metrics
$base_users = "1=1";
$base_subs = "1=1";
if($current_dept !== 'all') { 
    $base_users .= " AND department = '$current_dept'"; 
    $base_subs .= " AND u.department = '$current_dept'"; 
}
if($current_sem !== 'all') { 
    $base_users .= " AND (semester = '$current_sem' OR designation = '$current_sem')"; 
    $base_subs .= " AND (u.semester = '$current_sem' OR u.designation = '$current_sem')"; 
}

// ==========================================
// 📊 SAFE DATABASE METRICS FOR REPORTS
// ==========================================
$q_stu = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='student' AND $base_users");
$total_students = $q_stu ? $q_stu->fetch_assoc()['cnt'] : 0;

$q_fac = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='faculty' " . ($current_dept !== 'all' ? "AND department='$current_dept'" : ""));
$total_faculty = $q_fac ? $q_fac->fetch_assoc()['cnt'] : 0;

$q_sub1 = $conn->query("SELECT COUNT(*) as cnt FROM student_submissions s JOIN users u ON s.student_id = u.user_id WHERE $base_subs");
$total_subs = $q_sub1 ? $q_sub1->fetch_assoc()['cnt'] : 0;

$q_sub2 = $conn->query("SELECT COUNT(*) as cnt FROM student_submissions s JOIN users u ON s.student_id = u.user_id WHERE s.status='Pending' AND $base_subs");
$pending_subs = $q_sub2 ? $q_sub2->fetch_assoc()['cnt'] : 0;

$q_sub3 = $conn->query("SELECT COUNT(*) as cnt FROM student_submissions s JOIN users u ON s.student_id = u.user_id WHERE s.status='Approved' AND $base_subs");
$approved_subs = $q_sub3 ? $q_sub3->fetch_assoc()['cnt'] : 0;

// Fetch Departments for Filters
$departments_list = $conn->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin Portal</title>
    
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
            --shadow-float: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            --radius-xl: 16px;
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        /* 🔥 PREMIUM BLUE SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; overflow-y: auto; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0; letter-spacing: 0.5px; color: #ffffff;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); border-left: 3px solid transparent; }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; border-left: 4px solid #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);}

        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 30px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); }
        .icon-box { width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .blue-box { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .green-box { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .yellow-box { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

        /* REPORT CARDS */
        .report-card { background: var(--surface); border-radius: var(--radius-xl); padding: 25px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); height: 100%; display: flex; flex-direction: column; justify-content: space-between; transition: var(--transition-bounce); }
        .report-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-float); border-color: #cbd5e1; }
        .report-card:hover .icon-box { transform: scale(1.1) rotate(5deg); transition: var(--transition-bounce); }

        /* BUTTONS & INPUTS */
        .btn-gradient { background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; border: none; font-weight: 700; padding: 12px 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); transition: var(--transition-bounce); }
        .btn-gradient:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4); color: white; }
        .btn-outline-modern { background: white; color: var(--text-main); font-weight: 700; padding: 10px 18px; border-radius: 10px; border: 1px solid #cbd5e1; transition: var(--transition-bounce); }
        .btn-outline-modern:hover { background: #f8fafc; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }

        select.form-select { border-radius: 10px; padding: 12px; border: 1px solid #cbd5e1; font-weight: 500; font-size: 14px; transition: var(--transition-bounce); }
        select.form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); transform: scale(1.01); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* 🖨️ SMART PRINT VIEW */
        @media print {
            .sidebar, .topbar, .filter-box, .btn { display: none !important; }
            .main { padding: 0 !important; margin: 0 !important; animation: none !important; }
            body { background: white !important; }
            .report-card { border: 2px solid #ccc !important; box-shadow: none !important; margin-bottom: 20px; }
        }
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
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-pdf"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-inbox"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-double"></i> Review & Marks</li>
            <li class="active" onclick="window.location.href='Reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
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
                <div class="profile-avatar">AD</div>
            </a>
        </div>

        <!-- PAGE HEADER & EXPORT BUTTONS -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 page-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box blue-box"><i class="fas fa-chart-pie"></i></div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);">Reports & Analytics</h3>
                    <p class="text-muted fw-semibold small mb-0">Generate, view, and export comprehensive system reports from live database.</p>
                </div>
            </div>
            
            <div class="d-flex gap-3">
                <button onclick="window.print()" class="btn-outline-modern" title="Print Current View">
                    <i class="fas fa-print text-primary me-2"></i> Print View
                </button>
                <button onclick="window.location.href='Reports.php?export=master_excel&department=<?php echo urlencode($current_dept); ?>&semester=<?php echo urlencode($current_sem); ?>'" class="btn-gradient" title="Download Complete Excel">
                    <i class="fas fa-file-excel me-2"></i> Master Excel
                </button>
            </div>
        </div>

        <!-- FILTER BAR BOX -->
        <div class="content-box mb-4 py-3 px-4 filter-box" style="background: linear-gradient(135deg, rgba(67, 56, 202, 0.03), rgba(59, 130, 246, 0.05)); border: 1px solid rgba(67, 56, 202, 0.1);">
            <form method="GET" action="Reports.php" class="row align-items-end g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-primary text-uppercase letter-spacing-1 mb-2">Department</label>
                    <select name="department" class="form-select fw-bold text-dark shadow-sm">
                        <option value="all">All Departments</option>
                        <?php if($departments_list && $departments_list->num_rows > 0): ?>
                            <?php while($d = $departments_list->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($d['department']); ?>" <?php if($current_dept == $d['department']) echo 'selected'; ?>><?php echo htmlspecialchars($d['department']); ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-primary text-uppercase letter-spacing-1 mb-2">Semester / Batch</label>
                    <select name="semester" class="form-select fw-bold text-dark shadow-sm">
                        <option value="all">All Semesters</option>
                        <?php for($i=1; $i<=6; $i++): ?>
                            <option value="Semester <?php echo $i; ?>" <?php if($current_sem == "Semester $i") echo 'selected'; ?>>Semester <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <button type="submit" class="btn-gradient w-100 py-2">
                        <i class="fas fa-filter me-2"></i> Apply Filter Analytics
                    </button>
                </div>
            </form>
        </div>

        <!-- THREE REPORT CARDS WITH LIVE METRICS -->
        <div class="row g-4 mb-4">
            
            <!-- CARD 1: STUDENTS -->
            <div class="col-md-4">
                <div class="report-card">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="icon-box blue-box"><i class="fas fa-user-graduate"></i></div>
                            <div>
                                <h5 class="fw-bold text-dark mb-1" style="font-size: 17px;">Student Report</h5>
                                <span class="badge bg-primary px-2 py-1" style="font-size: 11px;">Total Registered: <?php echo $total_students; ?></span>
                            </div>
                        </div>
                        <p class="text-muted small fw-semibold">Complete list of active students and enrollment statuses filtered by department and batch.</p>
                    </div>
                    <div class="d-flex gap-2 mt-4 pt-4 border-top">
                        <a href="Student_Mgmt.php" class="btn btn-sm btn-outline-modern w-50 text-center"><i class="fas fa-eye me-1"></i> View</a>
                        <!-- Real CSV Download -->
                        <button onclick="window.location.href='Reports.php?export=students&department=<?php echo urlencode($current_dept); ?>&semester=<?php echo urlencode($current_sem); ?>'" class="btn btn-sm btn-outline-modern w-50 text-success border-success text-center"><i class="fas fa-download me-1"></i> CSV</button>
                    </div>
                </div>
            </div>

            <!-- CARD 2: SUBMISSIONS -->
            <div class="col-md-4">
                <div class="report-card">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="icon-box green-box"><i class="fas fa-file-pdf"></i></div>
                            <div>
                                <h5 class="fw-bold text-dark mb-1" style="font-size: 17px;">Submission Status</h5>
                                <span class="badge bg-success px-2 py-1" style="font-size: 11px;">Uploads: <?php echo $total_subs; ?> (<?php echo $pending_subs; ?> Pen)</span>
                            </div>
                        </div>
                        <p class="text-muted small fw-semibold">Detailed statistics on pending, approved, and rejected manual submissions across all subjects.</p>
                    </div>
                    <div class="d-flex gap-2 mt-4 pt-4 border-top">
                        <a href="Submissions.php" class="btn btn-sm btn-outline-modern w-50 text-center"><i class="fas fa-eye me-1"></i> View</a>
                        <!-- Real CSV Download -->
                        <button onclick="window.location.href='Reports.php?export=submissions&department=<?php echo urlencode($current_dept); ?>&semester=<?php echo urlencode($current_sem); ?>'" class="btn btn-sm btn-outline-modern w-50 text-success border-success text-center"><i class="fas fa-download me-1"></i> CSV</button>
                    </div>
                </div>
            </div>

            <!-- CARD 3: FACULTY WORKLOAD -->
            <div class="col-md-4">
                <div class="report-card">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="icon-box yellow-box"><i class="fas fa-chalkboard-teacher"></i></div>
                            <div>
                                <h5 class="fw-bold text-dark mb-1" style="font-size: 17px;">Faculty Workload</h5>
                                <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 11px;">Active Faculty: <?php echo $total_faculty; ?></span>
                            </div>
                        </div>
                        <p class="text-muted small fw-semibold">Track faculty evaluation metrics and review tracking across departments.</p>
                    </div>
                    <div class="d-flex gap-2 mt-4 pt-4 border-top">
                        <a href="faculty_mgmt.php" class="btn btn-sm btn-outline-modern w-50 text-center"><i class="fas fa-eye me-1"></i> View</a>
                        <!-- Real CSV Download -->
                        <button onclick="window.location.href='Reports.php?export=faculty&department=<?php echo urlencode($current_dept); ?>&semester=<?php echo urlencode($current_sem); ?>'" class="btn btn-sm btn-outline-modern w-50 text-success border-success text-center"><i class="fas fa-download me-1"></i> CSV</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Live Clock Script
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