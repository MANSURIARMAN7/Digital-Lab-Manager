<?php
ob_start();
session_start();
include '../db.php';

<<<<<<< HEAD
=======
// 1. Faculty Login Check
>>>>>>> pr-arman-to-main
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}
<<<<<<< HEAD
$faculty_id = $_SESSION['user_id'];
$faculty_name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Faculty';

$name_parts = explode(' ', trim($faculty_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) { $initials .= strtoupper(substr(end($name_parts), 0, 1)); }

// ==========================================
// 📂 EXPORT TO CSV LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_csv'])) {
    $e_sub = $conn->real_escape_string($_POST['export_subject']);
    $e_status = $conn->real_escape_string($_POST['export_status']);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$e_sub.'_Termwork_Report.csv"');
    $out = fopen('php://output', 'w');
    
    // CSV Header
    fputcsv($out, ['Enrollment No.', 'Student Name', 'Subject', 'Practical No', 'Status', 'Regularity', 'Understanding', 'Observation', 'Viva', 'Total Marks', 'Remarks']);
    
    $q = "SELECT s.*, u.name as student_name, u.email as student_enrollment 
          FROM student_submissions s 
          JOIN users u ON s.student_id = u.user_id 
          WHERE s.subject_name = '$e_sub'";
          
    if ($e_status !== 'All') { $q .= " AND s.status = '$e_status'"; }
    $q .= " ORDER BY u.email ASC";
    
    $res = $conn->query($q);
    if ($res) {
        while($row = $res->fetch_assoc()) {
            fputcsv($out, [
                $row['student_enrollment'],
                $row['student_name'],
                $row['subject_name'],
                $row['practical_no'],
                $row['status'],
                $row['mark_reg'], $row['mark_und'], $row['mark_obs'], $row['mark_viva'],
                $row['marks'],
                $row['feedback']
            ]);
        }
    }
    fclose($out);
    exit();
}

// ==========================================
// 🧠 SMART MAPPING LOGIC
// ==========================================
$available_branches = [];
$branch_res = $conn->query("SELECT DISTINCT department FROM subjects WHERE department IS NOT NULL AND department != ''");
if($branch_res && $branch_res->num_rows > 0) { while($r = $branch_res->fetch_assoc()) { $available_branches[] = $r['department']; } }
if(empty($available_branches)) $available_branches = ['Computer Engineering'];
$selected_branch = isset($_GET['branch']) ? $_GET['branch'] : $available_branches[0];

$available_semesters = ['Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Semester 5', 'Semester 6'];
$selected_sem = isset($_GET['sem']) ? $_GET['sem'] : 'Semester 1';
$sem_number = (int) str_replace('Semester ', '', $selected_sem);

$available_subjects = [];
$safe_br = $conn->real_escape_string($selected_branch);
$fac_name_safe = $conn->real_escape_string($faculty_name);

$sub_query = "SELECT subject_name FROM subjects WHERE faculty_name LIKE '%$fac_name_safe%' AND department = '$safe_br' AND semester = '$sem_number'";
$sub_res = $conn->query($sub_query);
if ($sub_res && $sub_res->num_rows > 0) { while($r = $sub_res->fetch_assoc()) { $available_subjects[] = $r['subject_name']; } }
$selected_subject = isset($_GET['subject']) ? $_GET['subject'] : (!empty($available_subjects) ? $available_subjects[0] : '');
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_status = isset($_GET['status']) ? $_GET['status'] : 'All';

// FETCH REPORTS DATA
$reports = [];
$total_eval = 0; $passed = 0; $rejected = 0;

if (!empty($selected_subject)) {
    $safe_sub = $conn->real_escape_string($selected_subject);
    $q = "SELECT s.submission_id, s.practical_no, s.student_id, u.email as student_enrollment, u.name as student_name, s.status, s.marks, s.mark_reg, s.mark_und, s.mark_obs, s.mark_viva, s.feedback 
          FROM student_submissions s JOIN users u ON s.student_id = u.user_id 
          WHERE s.subject_name = '$safe_sub' AND s.status != 'Pending'";
          
    if (!empty($search_query)) {
        $safe_search = $conn->real_escape_string($search_query);
        $q .= " AND (u.name LIKE '%$safe_search%' OR u.email LIKE '%$safe_search%')";
    }
    
    $q .= " ORDER BY u.email ASC";
    
    $res = $conn->query($q);
    if($res){
        while ($r = $res->fetch_assoc()) {
            $total_eval++;
            if($r['status'] == 'Approved') $passed++;
            if($r['status'] == 'Rejected') $rejected++;
            
            if ($selected_status == 'All' || $r['status'] == $selected_status) {
                $reports[] = $r;
            }
        }
    }
}

// Calculate Pass Percentage
$pass_percentage = ($total_eval > 0) ? round(($passed / $total_eval) * 100) : 0;
=======

$faculty_id = $_SESSION['user_id'];

// 2. Fetch Logged-in Faculty Details
$fac_query = $conn->query("SELECT name, designation FROM users WHERE user_id = '$faculty_id'");
$fac_data = $fac_query->fetch_assoc();
$faculty_name = $fac_data['name'];
$designation = !empty($fac_data['designation']) ? $fac_data['designation'] : 'Faculty Member';

$safe_id = strtolower($conn->real_escape_string($faculty_id));
$safe_name = strtolower($conn->real_escape_string($faculty_name));

// ==========================================
// FILTERS LOGIC (Date filters removed for clean UI)
// ==========================================
$f_subject = $_GET['subject'] ?? '';
$f_semester = $_GET['semester'] ?? '';

// Build Filter Queries
$filter_manuals = " WHERE faculty_id = '$faculty_id'";
$filter_subs = " WHERE lm.faculty_id = '$faculty_id'";

if (!empty($f_subject)) {
    $safe_sub = $conn->real_escape_string($f_subject);
    $filter_manuals .= " AND subject_code = '$safe_sub'";
    $filter_subs .= " AND lm.subject_code = '$safe_sub'";
}
if (!empty($f_semester)) {
    $safe_sem = $conn->real_escape_string($f_semester);
    $filter_manuals .= " AND semester = '$safe_sem'";
    $filter_subs .= " AND lm.semester = '$safe_sem'";
}

// ==========================================
// EXPORT TO CSV LOGIC
// ==========================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Faculty_Report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, array('Student Name', 'Enrollment No', 'Subject', 'Practical', 'Submission Date', 'Status', 'Total Marks (/20)'));
    
    // Fetch Data
    $export_query = $conn->query("
        SELECT u.name as student_name, u.user_id as enrollment_no, lm.subject_name, lm.practical_no, ss.submitted_at, ss.status, ss.total_marks 
        FROM student_submissions ss 
        JOIN lab_manuals lm ON ss.manual_id = lm.id 
        JOIN users u ON ss.student_id = u.user_id 
        $filter_subs 
        ORDER BY ss.submitted_at DESC
    ");
    
    while ($row = $export_query->fetch_assoc()) {
        fputcsv($output, array(
            $row['student_name'], 
            $row['enrollment_no'], 
            $row['subject_name'], 
            $row['practical_no'], 
            date('d M Y, h:i A', strtotime($row['submitted_at'])), 
            $row['status'], 
            ($row['status'] == 'Graded' ? $row['total_marks'] : '-')
        ));
    }
    fclose($output);
    exit();
}

// ==========================================
// FETCH SUMMARY STATISTICS (SAFE METHOD)
// ==========================================
$uploaded_manuals = 0;
$q_up = $conn->query("SELECT COUNT(*) as c FROM lab_manuals $filter_manuals");
if ($q_up) { $uploaded_manuals = $q_up->fetch_assoc()['c']; }

$total_submissions = 0;
$q_tot = $conn->query("SELECT COUNT(*) as c FROM student_submissions ss JOIN lab_manuals lm ON ss.manual_id = lm.id $filter_subs");
if ($q_tot) { $total_submissions = $q_tot->fetch_assoc()['c']; }

$pending_reviews = 0;
$q_pen = $conn->query("SELECT COUNT(*) as c FROM student_submissions ss JOIN lab_manuals lm ON ss.manual_id = lm.id $filter_subs AND ss.status = 'Submitted'");
if ($q_pen) { $pending_reviews = $q_pen->fetch_assoc()['c']; }

$reviewed_submissions = 0;
$q_rev = $conn->query("SELECT COUNT(*) as c FROM student_submissions ss JOIN lab_manuals lm ON ss.manual_id = lm.id $filter_subs AND ss.status IN ('Graded', 'Resubmit')");
if ($q_rev) { $reviewed_submissions = $q_rev->fetch_assoc()['c']; }

// ==========================================
// FETCH ASSIGNED SUBJECTS FOR DROPDOWN (Added 'semester' column here)
// ==========================================
$assigned_subjects = $conn->query("SELECT subject_code, subject_name, semester FROM subjects WHERE (LOWER(faculty_name) LIKE '%$safe_id%' OR LOWER(faculty_name) LIKE '%$safe_name%') AND status='Active'");

// ==========================================
// SUBJECT-WISE REPORT DATA
// ==========================================
$subject_wise_data = $conn->query("
    SELECT lm.subject_code, lm.subject_name, 
           COUNT(DISTINCT lm.id) as total_manuals,
           COUNT(ss.id) as total_subs,
           SUM(CASE WHEN ss.status = 'Submitted' THEN 1 ELSE 0 END) as pending,
           SUM(CASE WHEN ss.status IN ('Graded', 'Resubmit') THEN 1 ELSE 0 END) as reviewed
    FROM lab_manuals lm
    LEFT JOIN student_submissions ss ON lm.id = ss.manual_id
    WHERE lm.faculty_id = '$faculty_id'
    GROUP BY lm.subject_code
");

// ==========================================
// DETAILED SUBMISSIONS TABLE
// ==========================================
$detailed_subs = $conn->query("
    SELECT ss.*, u.name as student_name, u.user_id as enrollment_no, lm.subject_name, lm.subject_code, lm.practical_no 
    FROM student_submissions ss 
    JOIN lab_manuals lm ON ss.manual_id = lm.id 
    JOIN users u ON ss.student_id = u.user_id 
    $filter_subs 
    ORDER BY ss.submitted_at DESC LIMIT 50
");
>>>>>>> pr-arman-to-main
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>Term Work Reports - KDP Faculty</title>
    
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
            --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
=======
    <title>Reports & Analytics - Faculty Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --sidebar-width: 270px; --primary: #4338ca; --bg-body: #f8fafc; --surface: #ffffff; --text-main: #0f172a; --text-muted: #64748b; --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        * { box-sizing: border-box; }
        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        .sidebar { width: var(--sidebar-width); min-width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
>>>>>>> pr-arman-to-main
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
<<<<<<< HEAD
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.2); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; border-left: 4px solid white;}
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 10px 22px 10px 28px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); text-decoration: none; color: inherit; }
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: var(--primary); font-weight: 800; text-transform: uppercase; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 17px; color: var(--text-main); font-weight: 900;}
        .profile-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; }

        /* FILTER PANEL */
        .filter-panel { background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 25px; }
        .filter-panel label { color: var(--primary); font-size: 11px; margin-bottom: 6px; text-transform: uppercase; font-weight: 800; display: block;}
        .filter-panel select, .filter-panel input.form-control { width: 100%; padding: 0 15px; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 600; font-size: 13.5px;}
        .filter-panel select:focus, .filter-panel input.form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); background: #ffffff;}
        
        .search-wrapper { display: flex; height: 42px; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; background: #f8fafc;}
        .search-wrapper input { border: none; background: transparent; padding: 0 15px; font-weight: 600; font-size: 13.5px; width: 100%; outline: none; }
        .search-wrapper button { border: none; background: var(--primary); color: white; padding: 0 20px; font-weight: bold; cursor: pointer; transition: 0.2s;}
        
        .stats-ribbon { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap;}
        .stat-badge { flex: 1; min-width: 150px; background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; cursor: pointer; transition: 0.2s;}
        .stat-badge:hover, .stat-badge.active { border-color: var(--primary); box-shadow: 0 4px 10px rgba(0,0,0,0.05); transform: translateY(-3px);}
        .stat-badge i { font-size: 24px; }
        .stat-badge .info h4 { margin: 0; font-size: 22px; font-weight: 800; line-height: 1;}
        .stat-badge .info span { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted);}

        .content-box { background: var(--surface); border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden;}
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); padding: 15px 10px; background: #f8fafc; border-bottom: 2px solid #e2e8f0;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; }
        
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .status-Approved { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0;}
        .status-Rejected { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;}

        .btn-action { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px;}

        /* MODALS STYLING */
        .rubric-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); z-index: 9999; align-items: center; justify-content: center; }
        .rubric-modal-overlay.active { display: flex; }
        .rubric-modal { background: white; border-radius: 16px; width: 450px; max-width: 90%; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .modal-header-custom { padding: 18px 25px; color: white; display: flex; justify-content: space-between; align-items: center;}
        .btn-close-custom { background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer;}
        
=======
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}

        .main { flex: 1; padding: 30px 45px; overflow-y: scroll; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .profile-pill { display: flex; align-items: center; background: linear-gradient(135deg, #1e3a8a, #4338ca); padding: 12px 25px 12px 30px; border-radius: 50px; cursor: pointer; text-decoration: none; color: white; transition: var(--transition-bounce); box-shadow: 0 10px 20px rgba(67, 56, 202, 0.2); border: 2px solid rgba(255,255,255,0.1);}
        
        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
        
        .stat-card { background: var(--bg-body); border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px;}
        .stat-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-info h4 { font-size: 24px; font-weight: 900; margin: 0; color: var(--text-main);}
        .stat-info span { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); }

        .table-custom th { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 12px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 13.5px; font-weight: 600; padding: 12px 10px; border-bottom: 1px solid #f1f5f9; }

>>>>>>> pr-arman-to-main
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* PRINT MEDIA STYLING */
        @media print {
            .filter-panel, .stats-ribbon, .topbar, .nav-links, .btn-action { display: none !important; }
            body, .main { background: white !important; margin: 0; padding: 0 !important; overflow: visible !important; height: auto !important;}
            .content-box { border: none; box-shadow: none; margin: 0; }
            .sidebar { display: block; text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; width: 100% !important; background: transparent !important; color: #000 !important; box-shadow: none !important;}
            .sidebar-logo-container { padding: 0 !important;}
            .sidebar-logo-container img { filter: invert(1); } /* Dark text */
            .sidebar-title h2 { color: #000 !important; }
            .table-custom th, .table-custom td { padding: 8px !important; font-size: 12px !important; color: #000 !important; }
            .table-custom th { border-bottom: 2px solid #000 !important; }
            .badge-modern { background: transparent !important; color: #000 !important; border: none !important; padding: 0 !important; }
            .print-header { display: block !important; margin-bottom: 20px; }
            .hide-on-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
        </div>
<<<<<<< HEAD
        <ul class="nav-links hide-on-print">
            <li onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='manage_manuals.php'"><i class="fas fa-file-pdf"></i> Upload Manuals</li>
            <li onclick="window.location.href='labmanual_list.php'"><i class="fas fa-check-double"></i> Review & Evaluate</li>
=======
        <ul class="nav-links">
            <li onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='upload_manual.php'"><i class="fas fa-file-upload"></i> Upload Manuals</li>
            <li onclick="window.location.href='review_submissions.php'"><i class="fas fa-check-double"></i> Review Submissions</li>
>>>>>>> pr-arman-to-main
            <li class="active" onclick="window.location.href='reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> My Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
<<<<<<< HEAD
        
        <!-- PRINT ONLY HEADER (Hidden on Screen) -->
        <div class="print-header" style="display: none;">
            <h4 class="fw-bold">K.D. Polytechnic, Patan</h4>
            <h5 class="mb-3">Term Work Evaluation Report</h5>
            <p><strong>Branch:</strong> <?php echo htmlspecialchars($selected_branch); ?> | <strong>Semester:</strong> <?php echo htmlspecialchars($selected_sem); ?> | <strong>Subject:</strong> <?php echo htmlspecialchars($selected_subject); ?></p>
            <p><strong>Evaluated By:</strong> <?php echo htmlspecialchars($faculty_name); ?> | <strong>Date:</strong> <?php echo date("d M Y"); ?></p>
        </div>

        <!-- TOPBAR -->
        <div class="topbar hide-on-print">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            </div>
            
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Welcome Back,</span>
                    <h4 class="profile-name">
                        <?php 
                            $disp_name = htmlspecialchars($faculty_name);
                            if(stripos($disp_name, 'Prof') === false && stripos($disp_name, 'Dr') === false) { echo "Prof. " . $disp_name; } else { echo $disp_name; }
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </a>
        </div>

        <!-- PAGE HEADER & ACTION BUTTONS -->
        <div class="d-flex justify-content-between align-items-center mb-4 hide-on-print">
            <div>
                <h3 class="fw-bold text-dark mb-1" style="font-size: 26px;">Term Work Reports</h3>
                <p class="text-muted small fw-semibold mb-0">View, filter, and export evaluated student submissions.</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-dark fw-bold px-4 py-2" style="border-radius: 8px;">
                    <i class="fas fa-print me-2"></i> Print Report
                </button>
                <form method="POST">
                    <input type="hidden" name="export_csv" value="1">
                    <input type="hidden" name="export_subject" value="<?php echo htmlspecialchars($selected_subject); ?>">
                    <input type="hidden" name="export_status" value="<?php echo htmlspecialchars($selected_status); ?>">
                    <button type="submit" class="btn btn-success fw-bold px-4 py-2 shadow-sm" style="border-radius: 8px;">
                        <i class="fas fa-file-excel me-2"></i> Export CSV
                    </button>
                </form>
            </div>
        </div>

        <!-- 🎛️ FILTER PANEL -->
        <div class="filter-panel hide-on-print">
            <form method="GET" id="filterForm" class="row align-items-end g-3">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($selected_status); ?>">
                
                <div class="col-md-3">
                    <label><i class="fas fa-building me-1"></i> Branch</label>
                    <select name="branch" class="form-select" onchange="this.form.submit()">
                        <?php foreach($available_branches as $b) { echo "<option value='".htmlspecialchars($b)."' ".($selected_branch==$b ? 'selected':'').">".htmlspecialchars($b)."</option>"; } ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label><i class="fas fa-layer-group me-1"></i> Semester</label>
                    <select name="sem" class="form-select" onchange="this.form.submit()">
                        <?php foreach($available_semesters as $s) { echo "<option value='".htmlspecialchars($s)."' ".($selected_sem==$s ? 'selected':'').">".htmlspecialchars($s)."</option>"; } ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label><i class="fas fa-book me-1"></i> Subject</label>
                    <select name="subject" class="form-select" onchange="this.form.submit()">
                        <?php if(empty($available_subjects)) echo "<option value=''>No Subjects</option>"; ?>
                        <?php foreach($available_subjects as $sub) { echo "<option value='".htmlspecialchars($sub)."' ".($selected_subject==$sub ? 'selected':'').">".htmlspecialchars($sub)."</option>"; } ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label><i class="fas fa-search me-1"></i> Search Enrollment/Name</label>
                    <div class="search-wrapper">
                        <input type="text" name="search" placeholder="Name or Enroll No..." value="<?php echo htmlspecialchars($search_query); ?>" onchange="this.form.submit()">
                        <button type="button" onclick="this.form.submit()"><i class="fas fa-search"></i></button>
=======
        <div class="topbar">
            <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome" style="color: #bfdbfe; font-size: 11px; font-weight: 800; display: block; margin-bottom: 2px;"><?php echo htmlspecialchars($designation); ?></span>
                    <h4 class="profile-name" style="color: white; margin: 0; font-size: 17px; font-weight: 900;"><?php echo htmlspecialchars($faculty_name); ?></h4>
                </div>
                <div class="profile-avatar" style="background: white; color: #4338ca; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;"><i class="fas fa-user-tie"></i></div>
            </a>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1"><i class="fas fa-chart-line text-primary me-2"></i> Reports & Analytics</h3>
                <p class="text-muted fw-semibold small mb-0">Track lab manual activity and student practical submissions.</p>
            </div>
            <button onclick="exportToCSV()" class="btn btn-dark fw-bold px-4 py-2 shadow-sm"><i class="fas fa-file-excel text-success me-2"></i> Export Report</button>
        </div>

        <!-- FILTERS -->
        <div class="content-box">
            <form method="GET" class="row g-3 align-items-end" id="filterForm">
                <div class="col-md-5">
                    <label class="form-label fw-bold small text-muted text-uppercase">Subject</label>
                    <select name="subject" id="subjectFilter" class="form-select border-secondary-subtle" onchange="autoFillSemester()">
                        <option value="" data-sem="">All Subjects</option>
                        <?php if($assigned_subjects && $assigned_subjects->num_rows > 0): ?>
                            <?php while($sub = $assigned_subjects->fetch_assoc()): ?>
                                <!-- Added data-sem attribute for JavaScript -->
                                <option value="<?php echo htmlspecialchars($sub['subject_code']); ?>" data-sem="<?php echo $sub['semester']; ?>" <?php echo ($f_subject == $sub['subject_code']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sub['subject_code'] . " - " . $sub['subject_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Semester</label>
                    <select name="semester" id="semesterFilter" class="form-select border-secondary-subtle">
                        <option value="">All</option>
                        <option value="1" <?php echo ($f_semester == '1') ? 'selected' : ''; ?>>Semester 1</option>
                        <option value="2" <?php echo ($f_semester == '2') ? 'selected' : ''; ?>>Semester 2</option>
                        <option value="3" <?php echo ($f_semester == '3') ? 'selected' : ''; ?>>Semester 3</option>
                        <option value="4" <?php echo ($f_semester == '4') ? 'selected' : ''; ?>>Semester 4</option>
                        <option value="5" <?php echo ($f_semester == '5') ? 'selected' : ''; ?>>Semester 5</option>
                        <option value="6" <?php echo ($f_semester == '6') ? 'selected' : ''; ?>>Semester 6</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100"><i class="fas fa-filter"></i> Apply Filter</button>
                    <a href="reports.php" class="btn btn-light border fw-bold w-100"><i class="fas fa-sync-alt"></i> Reset</a>
                </div>
            </form>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card border-primary border-start border-4">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-inbox"></i></div>
                    <div class="stat-info"><span>Total Submissions</span><h4><?php echo $total_submissions; ?></h4></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-warning border-start border-4">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                    <div class="stat-info"><span>Pending Reviews</span><h4><?php echo $pending_reviews; ?></h4></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-success border-start border-4">
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-double"></i></div>
                    <div class="stat-info"><span>Reviewed</span><h4><?php echo $reviewed_submissions; ?></h4></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-info border-start border-4">
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-file-pdf"></i></div>
                    <div class="stat-info"><span>Uploaded Manuals</span><h4><?php echo $uploaded_manuals; ?></h4></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- OVERVIEW CHART -->
            <div class="col-lg-4">
                <div class="content-box h-100 d-flex flex-column">
                    <h6 class="fw-bold mb-4 text-dark"><i class="fas fa-chart-pie text-primary me-2"></i> Submission Overview</h6>
                    <div class="flex-grow-1 position-relative" style="min-height: 250px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- SUBJECT-WISE REPORT -->
            <div class="col-lg-8">
                <div class="content-box h-100">
                    <h6 class="fw-bold mb-4 text-dark"><i class="fas fa-book-open text-primary me-2"></i> Subject-wise Report</h6>
                    <div class="table-responsive">
                        <table class="table table-custom text-center">
                            <thead class="text-start">
                                <tr>
                                    <th>Subject</th>
                                    <th class="text-center">Manuals Uploaded</th>
                                    <th class="text-center">Submissions</th>
                                    <th class="text-center text-success">Reviewed</th>
                                    <th class="text-center text-warning">Pending</th>
                                </tr>
                            </thead>
                            <tbody class="text-start">
                                <?php if($subject_wise_data && $subject_wise_data->num_rows > 0): ?>
                                    <?php while($s_row = $subject_wise_data->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-primary"><?php echo htmlspecialchars($s_row['subject_code']); ?></div>
                                                <div class="small text-muted text-truncate" style="max-width:200px;"><?php echo htmlspecialchars($s_row['subject_name']); ?></div>
                                            </td>
                                            <td class="text-center"><span class="badge bg-light text-dark border"><?php echo $s_row['total_manuals']; ?></span></td>
                                            <td class="text-center fw-bold"><?php echo $s_row['total_subs']; ?></td>
                                            <td class="text-center text-success fw-bold"><?php echo $s_row['reviewed']; ?></td>
                                            <td class="text-center text-warning fw-bold"><?php echo $s_row['pending']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No data available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
>>>>>>> pr-arman-to-main
                    </div>
                </div>
            </form>
        </div>

        <!-- 📊 CLICKABLE STATS RIBBON -->
        <div class="stats-ribbon hide-on-print">
            <div class="stat-badge <?php echo ($selected_status == 'All') ? 'active' : ''; ?>" onclick="window.location.href='reports.php?branch=<?php echo urlencode($selected_branch); ?>&sem=<?php echo urlencode($selected_sem); ?>&subject=<?php echo urlencode($selected_subject); ?>&search=<?php echo urlencode($search_query); ?>&status=All'">
                <div class="info"><h4><?php echo $total_eval; ?></h4><span>Total Evaluated</span></div>
                <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-badge <?php echo ($selected_status == 'Approved') ? 'active' : ''; ?>" onclick="window.location.href='reports.php?branch=<?php echo urlencode($selected_branch); ?>&sem=<?php echo urlencode($selected_sem); ?>&subject=<?php echo urlencode($selected_subject); ?>&search=<?php echo urlencode($search_query); ?>&status=Approved'">
                <div class="info"><h4><?php echo $passed; ?></h4><span class="text-success">Approved (Passed)</span></div>
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-check-circle"></i></div>
            </div>
            <div class="stat-badge <?php echo ($selected_status == 'Rejected') ? 'active' : ''; ?>" onclick="window.location.href='reports.php?branch=<?php echo urlencode($selected_branch); ?>&sem=<?php echo urlencode($selected_sem); ?>&subject=<?php echo urlencode($selected_subject); ?>&search=<?php echo urlencode($search_query); ?>&status=Rejected'">
                <div class="info"><h4><?php echo $rejected; ?></h4><span class="text-danger">Needs Revision (Rejected)</span></div>
                <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-exclamation-circle"></i></div>
            </div>
            <div class="stat-badge" style="cursor: default; border-bottom: 4px solid var(--primary);">
                <div class="info"><h4 class="text-primary"><?php echo $pass_percentage; ?>%</h4><span>Pass Percentage</span></div>
                <div class="stat-icon" style="background: rgba(15, 23, 42, 0.05); color: #0f172a;"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>

<<<<<<< HEAD
        <!-- 📋 REPORTS TABLE -->
        <div class="content-box p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 15%; padding-left: 20px;">Enrollment No.</th>
                            <th style="width: 25%;">Student Name</th>
                            <th style="width: 15%;">Practical No.</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 15%;">Total Marks</th>
                            <th style="width: 15%;" class="text-end pe-4 hide-on-print">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-file-excel fa-3x mb-3 opacity-25"></i><br>
                                    <h5 class="fw-bold text-dark mb-1">No Records Found</h5>
                                    <small class="mb-0">No evaluated reports available for this selection.</small>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reports as $r): ?>
                                <tr>
                                    <td class="fw-bold ps-4" style="color: #475569; font-size: 13px;"><?php echo htmlspecialchars($r['student_enrollment']); ?></td>
                                    <td><div class="fw-bold text-dark"><?php echo htmlspecialchars($r['student_name']); ?></div></td>
                                    <td><span class="text-muted fw-bold small"><i class="fas fa-file-code me-1"></i> <?php echo htmlspecialchars($r['practical_no']); ?></span></td>
                                    <td>
                                        <span class="badge-modern status-<?php echo htmlspecialchars($r['status']); ?>">
                                            <?php echo htmlspecialchars($r['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="<?php echo ($r['status'] == 'Approved') ? 'text-success' : 'text-danger'; ?>" style="font-size:14px;">
                                            <?php echo $r['marks']; ?> <small class="text-muted fw-normal" style="font-size: 12px;">/ 20</small>
                                        </strong>
                                    </td>
                                    <td class="text-end pe-4 hide-on-print">
                                        <?php
                                            $rubric_data = json_encode([
                                                'reg' => $r['mark_reg'], 'und' => $r['mark_und'],
                                                'obs' => $r['mark_obs'], 'viva' => $r['mark_viva'],
                                                'total' => $r['marks'], 'feedback' => $r['feedback'],
                                                'prac' => $r['practical_no'], 'status' => $r['status']
                                            ]);
                                        ?>
                                        <button type="button" class="btn-action shadow-sm" onclick='viewScorecard(<?php echo htmlspecialchars($rubric_data, ENT_QUOTES, 'UTF-8'); ?>, "<?php echo addslashes($r['student_name']); ?>")'>
                                            <i class="fas fa-eye me-1"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
=======
        <!-- DETAILED REPORT TABLE -->
        <div class="content-box">
            <h6 class="fw-bold mb-4 text-dark"><i class="fas fa-list-alt text-primary me-2"></i> Detailed Report (Recent 50 Submissions)</h6>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Student & ID</th>
                            <th>Subject & Practical</th>
                            <th>Submission Date</th>
                            <th>Review Status</th>
                            <th>Total Marks</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($detailed_subs && $detailed_subs->num_rows > 0): ?>
                            <?php while($d_row = $detailed_subs->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($d_row['student_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($d_row['enrollment_no']); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($d_row['subject_code']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($d_row['practical_no']); ?></div>
                                    </td>
                                    <td><?php echo date('d M Y, h:i A', strtotime($d_row['submitted_at'])); ?></td>
                                    <td>
                                        <?php 
                                            if($d_row['status'] == 'Submitted') echo '<span class="badge bg-warning text-dark">Pending</span>';
                                            elseif($d_row['status'] == 'Graded') echo '<span class="badge bg-success">Reviewed</span>';
                                            else echo '<span class="badge bg-danger">Resubmit</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if($d_row['status'] == 'Graded'): ?>
                                            <span class="fw-bold text-success"><?php echo $d_row['total_marks']; ?>/20</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="review_submissions.php?subject_code=<?php echo urlencode($d_row['subject_code']); ?>&manual_id=<?php echo $d_row['manual_id']; ?>" class="btn btn-sm btn-outline-primary fw-bold">Review Page <i class="fas fa-arrow-right ms-1"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted fw-bold">No records found matching filters.</td></tr>
>>>>>>> pr-arman-to-main
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL: SCORECARD PREVIEW -->
    <div id="scoreModalOverlay" class="rubric-modal-overlay">
        <div class="rubric-modal">
            <div class="modal-header-custom" id="score_header_bg" style="background: linear-gradient(135deg, #059669, #10b981);">
                <h5 class="mb-0"><i class="fas fa-award me-2"></i> Report Scorecard</h5>
                <button type="button" class="btn-close-custom" onclick="closeModal('scoreModalOverlay')"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="p-4 bg-white">
                <div class="text-center mb-4">
                    <h5 id="score_student_name" class="fw-bold text-dark mb-1">Student Name</h5>
                    <p id="score_prac_no" class="text-muted small fw-bold mb-0">Practical Details</p>
                </div>
                
                <div class="bg-light border rounded-3 p-3 mb-4">
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><span class="text-muted fw-bold">Regularity</span><span id="score_reg" class="text-primary fw-bold"></span></div>
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><span class="text-muted fw-bold">Understanding</span><span id="score_und" class="text-primary fw-bold"></span></div>
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><span class="text-muted fw-bold">Observation</span><span id="score_obs" class="text-primary fw-bold"></span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted fw-bold">Viva / Quiz</span><span id="score_viva" class="text-primary fw-bold"></span></div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center bg-dark text-white p-3 rounded-3 mb-3 shadow-sm">
                    <span class="text-uppercase fw-bold" style="letter-spacing: 1px;">Total Marks</span>
                    <h4 id="score_total" class="mb-0 fw-bold"></h4>
                </div>
                
                <div class="p-3 bg-light border-start border-4 rounded" id="score_remark_box">
                    <span class="text-muted small fw-bold d-block mb-1">Faculty Remark:</span>
                    <div id="score_remark" class="fst-italic text-dark"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
<<<<<<< HEAD
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        function viewScorecard(marksData, studentName) {
            document.getElementById('score_student_name').innerText = studentName;
            document.getElementById('score_prac_no').innerText = "Manual: " + marksData.prac;
            document.getElementById('score_reg').innerText = marksData.reg + " / 5";
            document.getElementById('score_und').innerText = marksData.und + " / 5";
            document.getElementById('score_obs').innerText = marksData.obs + " / 5";
            document.getElementById('score_viva').innerText = marksData.viva + " / 5";
            
            let totalBox = document.getElementById('score_total');
            let headerBg = document.getElementById('score_header_bg');
            let remarkBox = document.getElementById('score_remark_box');
            
            totalBox.innerText = marksData.total + " / 20";
            
            if (marksData.status === 'Rejected') {
                headerBg.style.background = 'linear-gradient(135deg, #dc2626, #ef4444)';
                totalBox.classList.remove('text-warning');
                totalBox.classList.add('text-danger');
                remarkBox.classList.remove('border-info');
                remarkBox.classList.add('border-danger');
            } else {
                headerBg.style.background = 'linear-gradient(135deg, #059669, #10b981)';
                totalBox.classList.remove('text-danger');
                totalBox.classList.add('text-warning');
                remarkBox.classList.remove('border-danger');
                remarkBox.classList.add('border-info');
            }

            let remarkText = document.getElementById('score_remark');
            if(marksData.feedback && marksData.feedback.trim() !== '') {
                remarkText.innerText = '"' + marksData.feedback + '"';
            } else {
                remarkText.innerHTML = '<span class="text-muted">No specific remarks provided.</span>';
            }

            document.getElementById('scoreModalOverlay').classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
=======
        // Automatic Semester Selection Logic
        function autoFillSemester() {
            var subjSelect = document.getElementById('subjectFilter');
            var semSelect = document.getElementById('semesterFilter');
            var selectedOption = subjSelect.options[subjSelect.selectedIndex];
            var semValue = selectedOption.getAttribute('data-sem');

            if(semValue) {
                semSelect.value = semValue;
            } else {
                semSelect.value = ""; // Reset if "All Subjects" is selected
            }
        }

        // Clock
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const clockEl = document.getElementById('liveClock');
            if(clockEl) clockEl.innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Chart.js Setup
        const ctx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Reviewed', 'Pending'],
                datasets: [{
                    data: [<?php echo $reviewed_submissions; ?>, <?php echo $pending_reviews; ?>],
                    backgroundColor: ['#10b981', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { family: "'Plus Jakarta Sans', sans-serif", weight: 'bold' } } }
                },
                cutout: '75%'
            }
        });

        // Export Function
        function exportToCSV() {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('export', 'csv');
            window.location.href = 'reports.php?' + urlParams.toString();
>>>>>>> pr-arman-to-main
        }
    </script>
</body>
</html>