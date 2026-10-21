<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ==========================================
// EXPORT LOGIC (CSV / EXCEL DOWNLOADS)
// ==========================================
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $filter_dept = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : 'All';
    $filter_sem = isset($_GET['semester']) ? $conn->real_escape_string($_GET['semester']) : 'All';
    
    $filename = "KDP_" . ucfirst($export_type) . "_Report_" . date('Y-m-d') . ".csv";

    // Force browser to download file
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '";');
    $output = fopen('php://output', 'w');

    // Base where clause for filters
    $where_users = "1=1";
    if($filter_dept !== 'All') $where_users .= " AND department = '$filter_dept'";
    if($filter_sem !== 'All') $where_users .= " AND (semester = '$filter_sem' OR designation = '$filter_sem')";

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
        $res = $conn->query("SELECT user_id, name, email, department FROM users WHERE role='faculty' AND ($filter_dept = 'All' OR department = '$filter_dept') ORDER BY name ASC");
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

// ==========================================
// Fetch Admin Details for Profile Pill
// ==========================================
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data['name'] ?? 'System Administrator';
$name_parts = explode(' ', trim($admin_name));

// Get current filters for display
$current_dept = isset($_GET['department']) ? $conn->real_escape_string($_GET['department']) : 'All';
$current_sem = isset($_GET['semester']) ? $conn->real_escape_string($_GET['semester']) : 'All';

// Base queries for metrics
$base_users = "1=1";
$base_subs = "1=1";
if($current_dept !== 'All') {
    $base_users .= " AND department = '$current_dept'";
    $base_subs .= " AND u.department = '$current_dept'";
}
if($current_sem !== 'All') {
    $base_users .= " AND (semester = '$current_sem' OR designation = '$current_sem')";
    $base_subs .= " AND (u.semester = '$current_sem' OR u.designation = '$current_sem')";
}

// DATA GATHERING METRICS FOR REPORTS
$q_stu = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='student' AND $base_users");
$total_students = $q_stu ? $q_stu->fetch_assoc()['cnt'] : 0;

$q_fac = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='faculty' " . ($current_dept !== 'All' ? "AND department='$current_dept'" : ""));
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
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        /* 🔥 PREMIUM MICROSOFT UNIFIED SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 13px; color: #bfdbfe; margin-top: 5px; font-weight: 500;} 
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

        .page-header { background: white; border: 1px solid #e2e8f0; padding: 25px; border-radius: 16px; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center;}
        .icon-box-blue { width: 50px; height: 50px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .icon-box-green { width: 50px; height: 50px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .icon-box-yellow { width: 50px; height: 50px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }

        .filter-box { background: linear-gradient(135deg, rgba(67, 56, 202, 0.03), rgba(59, 130, 246, 0.05)); border: 1px solid rgba(67, 56, 202, 0.1); padding: 20px; border-radius: 12px; margin-bottom: 30px; }
        
        .form-select { border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; font-weight: 600; font-size: 14px;}
        .form-select:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);}

        /* REPORT CARDS */
        .report-card { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); height: 100%; display: flex; flex-direction: column; justify-content: space-between; transition: var(--transition-bounce);}
        .report-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.05); }

        .btn-gradient { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; transition: var(--transition-bounce); }
        .btn-gradient:hover { background: #059669; color: white; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
        
        .btn-outline-modern { border: 1px solid #cbd5e1; background: white; color: var(--text-main); font-weight: 700; border-radius: 8px; transition: var(--transition-bounce); }
        .btn-outline-modern:hover { border-color: var(--primary); color: var(--primary); background: rgba(67, 56, 202, 0.05); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
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
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li class="active" onclick="window.location.href='Reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li onclick="window.location.href='Profile.php'"><i class="fas fa-user-shield"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            </div>
            
            <a href="dashboard.php" class="btn btn-outline-modern px-4 py-2"><i class="fas fa-arrow-left me-2"></i> Back to Dashboard</a>
        </div>

        <!-- PAGE HEADER & REPORT BUTTONS -->
        <div class="page-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box-blue"><i class="fas fa-chart-pie"></i></div>
                <div>
                    <h3 class="fw-bold mb-1">Reports & Analytics</h3>
                    <p class="mb-0 fw-medium opacity-75">Generate, view, and export comprehensive system reports from live database.</p>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-modern px-4 py-2"><i class="fas fa-print me-2"></i> Print View</button>
                <button onclick="window.location.href='Reports.php?export=master_excel&department=<?php echo urlencode($current_dept); ?>&semester=<?php echo urlencode($current_sem); ?>'" class="btn btn-gradient"><i class="fas fa-file-excel me-2"></i> Master Excel Download</button>
            </div>
        </div>

        <!-- FILTER BAR BOX -->
        <div class="filter-box">
            <form method="GET" action="Reports.php" class="row align-items-end g-3">
                <div class="col-md-5">
                    <label class="form-label fw-bold small text-primary text-uppercase letter-spacing-1 mb-2">Department/Branch</label>
                    <select name="department" class="form-select shadow-sm py-2">
                        <option value="All">All Departments</option>
                        <?php if($departments_list && $departments_list->num_rows > 0): ?>
                            <?php while($d = $departments_list->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($d['department']); ?>" <?php if($current_dept == $d['department']) echo 'selected'; ?>><?php echo htmlspecialchars($d['department']); ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label fw-bold small text-primary text-uppercase letter-spacing-1 mb-2">Semester / Batch</label>
                    <select name="semester" class="form-select shadow-sm py-2">
                        <option value="All">All Semesters</option>
                        <?php for($i=1; $i<=6; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php if($current_sem == $i) echo 'selected'; ?>>Semester <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100 py-2"><i class="fas fa-filter me-2"></i> Apply Filter</button>
                </div>
            </form>
        </div>

        <!-- EXPORT CARDS WITH LIVE METRICS -->
        <div class="row g-4 mb-4">
            
            <!-- CARD 1: STUDENTS -->
            <div class="col-md-4">
                <div class="report-card">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="icon-box-blue"><i class="fas fa-user-graduate"></i></div>
                            <div>
                                <h5 class="fw-bold text-dark mb-1">Student Reports</h5>
                                <span class="badge bg-primary px-2 py-1">Total Registered: <?php echo $total_students; ?></span>
                            </div>
                        </div>
                        <p class="text-muted small fw-semibold">Complete list of active students and enrollment statuses filtered by department and batch.</p>
                    </div>
                    
                    <div class="d-flex gap-2 mt-4 pt-4 border-top">
                        <a href="Student_Mgmt.php" class="btn btn-outline-modern flex-grow-1 text-center py-2"><i class="fas fa-eye me-1"></i> View</a>
                        <!-- Real CSV Download -->
                        <button onclick="window.location.href='Reports.php?export=students&department=<?php echo urlencode($current_dept); ?>&semester=<?php echo urlencode($current_sem); ?>'" class="btn btn-outline-modern flex-grow-1 text-success text-center py-2"><i class="fas fa-download me-1"></i> CSV</button>
                    </div>
                </div>
            </div>

            <!-- CARD 2: SUBMISSIONS -->
            <div class="col-md-4">
                <div class="report-card">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="icon-box-green"><i class="fas fa-file-pdf"></i></div>
                            <div>
                                <h5 class="fw-bold text-dark mb-1">Submission Status</h5>
                                <span class="badge bg-success px-2 py-1">Uploads: <?php echo $total_subs; ?></span> <span class="badge bg-warning text-dark px-2 py-1">Pending: <?php echo $pending_subs; ?></span>
                            </div>
                        </div>
                        <p class="text-muted small fw-semibold">Detailed statistics on pending, approved, and rejected manual submissions across all subjects.</p>
                    </div>
                    
                    <div class="d-flex gap-2 mt-4 pt-4 border-top">
                        <a href="Submissions.php" class="btn btn-outline-modern flex-grow-1 text-center py-2"><i class="fas fa-eye me-1"></i> View</a>
                        <!-- Real CSV Download -->
                        <button onclick="window.location.href='Reports.php?export=submissions&department=<?php echo urlencode($current_dept); ?>&semester=<?php echo urlencode($current_sem); ?>'" class="btn btn-outline-modern flex-grow-1 text-success text-center py-2"><i class="fas fa-download me-1"></i> CSV</button>
                    </div>
                </div>
            </div>

            <!-- CARD 3: FACULTY WORKLOAD -->
            <div class="col-md-4">
                <div class="report-card">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="icon-box-yellow"><i class="fas fa-chalkboard-teacher"></i></div>
                            <div>
                                <h5 class="fw-bold text-dark mb-1">Faculty Workload</h5>
                                <span class="badge bg-warning text-dark px-2 py-1">Active Faculty: <?php echo $total_faculty; ?></span>
                            </div>
                        </div>
                        <p class="text-muted small fw-semibold">Track faculty evaluation metrics and review tracking across departments.</p>
                    </div>
                    
                    <div class="d-flex gap-2 mt-4 pt-4 border-top">
                        <a href="faculty_mgmt.php" class="btn btn-outline-modern flex-grow-1 text-center py-2"><i class="fas fa-eye me-1"></i> View</a>
                        <!-- Real CSV Download -->
                        <button onclick="window.location.href='Reports.php?export=faculty&department=<?php echo urlencode($current_dept); ?>&semester=<?php echo urlencode($current_sem); ?>'" class="btn btn-outline-modern flex-grow-1 text-success text-center py-2"><i class="fas fa-download me-1"></i> CSV</button>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live Clock Script
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const clockEl = document.getElementById('liveClock');
            if(clockEl) clockEl.innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>