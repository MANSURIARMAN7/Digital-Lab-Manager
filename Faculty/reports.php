<?php
session_start();
include '../db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}
$faculty_id = $_SESSION['user_id'];
$faculty_name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Faculty';

// Generate Initials for Profile Avatar
$name_parts = explode(' ', trim($faculty_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

// ==========================================
// 📥 EXPORT TO CSV LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_csv'])) {
    $e_sub = $conn->real_escape_string($_POST['export_subject']);
    $e_status = $conn->real_escape_string($_POST['export_status']);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$e_sub.'_TermWork_Report.csv"');
    $out = fopen('php://output', 'w');
    
    // CSV Header
    fputcsv($out, ['Enrollment No.', 'Student Name', 'Subject', 'Practical No', 'Status', 'Regularity', 'Understanding', 'Observation', 'Viva', 'Total Marks', 'Remarks']);
    
    $q = "SELECT s.*, u.name as student_name, u.email as student_enrollment 
          FROM student_submissions s 
          JOIN users u ON s.student_id = u.user_id 
          WHERE s.subject_name='$e_sub' AND s.status != 'Pending'";
          
    if ($e_status !== 'All') {
        $q .= " AND s.status = '$e_status'";
    }
    $q .= " ORDER BY u.email ASC";
    
    $res = $conn->query($q);
    if($res) {
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

// 🧠 SMART MAPPING LOGIC
$available_branches = [];
$branch_res = $conn->query("SELECT DISTINCT department FROM subjects WHERE department IS NOT NULL AND department != ''");
if($branch_res && $branch_res->num_rows > 0) {
    while($r = $branch_res->fetch_assoc()) { $available_branches[] = $r['department']; }
}
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
if ($sub_res && $sub_res->num_rows > 0) {
    while($r = $sub_res->fetch_assoc()) { $available_subjects[] = $r['subject_name']; }
}
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
    
    $r = $conn->query($q);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $total_eval++;
            if($row['status'] == 'Approved') $passed++;
            if($row['status'] == 'Rejected') $rejected++;
            
            if ($selected_status == 'All' || $row['status'] == $selected_status) {
                $reports[] = $row;
            }
        }
    }
}

// Calculate Pass Percentage
$pass_percentage = ($total_eval > 0) ? round(($passed / $total_eval) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        
        /* 🔥 SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover, .nav-links li.active { color: #ffffff; background: rgba(255,255,255,0.2); }
        .nav-links li.active { font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid white; }
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* 🌐 TOPBAR */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 10px 22px 10px 28px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); text-decoration: none; color: inherit; box-shadow: var(--shadow-float); transition: var(--transition-bounce);}
        .profile-pill:hover { transform: translateY(-3px); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 17px; color: var(--text-main); font-weight: 900; letter-spacing: 0.3px;}
        .profile-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.4);}

        /* 🎛️ SMART FILTER PANEL - 100% MATCHED TO LAB MANUALS LIST */
        .filter-panel { background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 4px 15px rgba(0,0,0,0.02); margin-bottom: 25px; }
        .filter-panel label { color: var(--primary); font-size: 11px; margin-bottom: 8px; text-transform: uppercase; font-weight: 800; display: block;}
        .filter-panel .form-select { width: 100%; height: 45px; padding: 0 15px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; color: var(--text-main); font-weight: 600; font-size: 13.5px; transition: var(--transition-bounce); box-shadow: none;}
        .filter-panel .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); background: #ffffff; }
        
        .search-wrapper { display: flex; height: 45px; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; background: #f8fafc; transition: var(--transition-bounce);}
        .search-wrapper:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); background: #ffffff;}
        .search-wrapper input { border: none; background: transparent; padding: 0 15px; font-weight: 600; font-size: 13.5px; width: 100%; outline: none; }
        .search-wrapper button { border: none; background: var(--primary); color: white; padding: 0 20px; font-weight: bold; transition: 0.2s; cursor: pointer; }
        .search-wrapper button:hover { background: var(--primary-hover); }

        /* 📊 CLICKABLE STATS RIBBON (FILTERS) */
        .stats-ribbon { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap;}
        .stat-badge { flex: 1; min-width: 150px; background: white; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: var(--transition-bounce); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
        .stat-badge:hover { transform: translateY(-4px); box-shadow: var(--shadow-float); }
        .stat-badge.active { border: 2px solid var(--primary); box-shadow: 0 4px 15px rgba(67, 56, 202, 0.15); background: #f8fafc;}
        
        .stat-badge .info span { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 5px;}
        .stat-badge .info h4 { margin: 0; font-size: 26px; font-weight: 900; color: var(--text-main); line-height: 1;}
        .stat-badge .icon-box { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }

        /* TABLE */
        .content-box { background: var(--surface); border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden;}
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 18px 15px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 18px 15px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; }
        .table-custom tbody tr:hover td { background-color: #f8fafc; }
        
        .badge-modern { padding: 6px 14px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-Approved { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0;}
        .status-Rejected { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;}

        .btn-action { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 800; transition: var(--transition-bounce); display: inline-flex; align-items: center; gap: 6px;}
        .btn-action:hover { background: #2563eb; color: white; transform: translateY(-2px); }

        /* 🌟 MODALS STYLING */
        .rubric-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); z-index: 9999; align-items: center; justify-content: center; }
        .rubric-modal-overlay.active { display: flex; }
        .rubric-modal { background: white; border-radius: 20px; width: 100%; max-width: 450px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2);}
        .modal-header-custom { padding: 20px 25px; color: white; display: flex; justify-content: space-between; align-items: center;}

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* ========================================================
           🖨️ SMART PRINT VIEW - Official Document Style
           ======================================================== */
        @media print {
            .sidebar, .topbar, .filter-panel, .stats-ribbon, .btn, form, .modal { display: none !important; }
            body, .main { background: white !important; margin: 0; padding: 0; color: #000; height: auto; overflow: visible;}
            .content-box { border: none; box-shadow: none; }
            
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
            .print-header h2 { margin: 0; font-size: 24px; font-weight: bold; text-transform: uppercase; }
            .print-header h4 { margin: 5px 0; font-size: 16px; color: #333; }
            .print-header p { margin: 0; font-size: 12px; color: #555; }
            
            .table-custom { width: 100%; border-collapse: collapse; }
            .table-custom th, .table-custom td { border: 1px solid #000 !important; padding: 8px !important; font-size: 12px !important; color: #000 !important; }
            .table-custom th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }
            .table-custom td .badge-modern { border: none !important; padding: 0 !important; font-size: 12px !important; }
            .hide-on-print { display: none !important; }
        }
    </style>
</head>
<body>
    
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Faculty Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='labmanual_list.php'"><i class="fas fa-check-double"></i> Review & Evaluate</li>
            <li class="active" onclick="window.location.href='reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- PRINT ONLY HEADER (Hidden on Screen) -->
        <div class="print-header" style="display: none;">
            <h2>K.D. Polytechnic, Patan</h2>
            <h4>Term Work Evaluation Report</h4>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($selected_branch); ?> | <strong>Semester:</strong> <?php echo htmlspecialchars($selected_sem); ?> | <strong>Subject:</strong> <?php echo htmlspecialchars($selected_subject); ?></p>
            <p><strong>Evaluated By:</strong> <?php echo htmlspecialchars($faculty_name); ?> | <strong>Date:</strong> <?php echo date('d M Y'); ?></p>
        </div>

        <!-- TOPBAR -->
        <div class="topbar">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
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

        <!-- 🎛️ FIXED FILTER PANEL (EXACTLY MATCHING IMAGE 1) -->
        <div class="filter-panel">
            <form method="GET" id="filterForm" class="row align-items-end g-3">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($selected_status); ?>">
                
                <div class="col-md-2">
                    <label><i class="fas fa-building me-1"></i> Branch</label>
                    <select name="branch" class="form-select" onchange="this.form.submit()">
                        <?php foreach($available_branches as $b) { echo "<option value='".htmlspecialchars($b)."' ".($selected_branch==$b?'selected':'').">".htmlspecialchars($b)."</option>"; } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label><i class="fas fa-layer-group me-1"></i> Semester</label>
                    <select name="sem" class="form-select" onchange="this.form.submit()">
                        <?php foreach($available_semesters as $s) { echo "<option value='".htmlspecialchars($s)."' ".($selected_sem==$s?'selected':'').">".htmlspecialchars($s)."</option>"; } ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label><i class="fas fa-book me-1"></i> Subject</label>
                    <select name="subject" class="form-select" onchange="this.form.submit()">
                        <?php if(empty($available_subjects)) echo "<option value=''>No Subjects</option>"; ?>
                        <?php foreach($available_subjects as $sub) { echo "<option value='".htmlspecialchars($sub)."' ".($selected_subject==$sub?'selected':'').">".htmlspecialchars($sub)."</option>"; } ?>
                    </select>
                </div>
                
                <!-- 🔍 SEARCH BAR FIX -->
                <div class="col-md-4">
                    <label><i class="fas fa-search me-1"></i> Search Enrollment</label>
                    <div class="search-wrapper">
                        <input type="text" name="search" placeholder="Name or Enroll No..." value="<?php echo htmlspecialchars($search_query); ?>" onchange="this.form.submit()">
                        <button type="button" onclick="this.form.submit()"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 📊 CLICKABLE STATS RIBBON -->
        <div class="stats-ribbon">
            <div class="stat-badge <?php echo ($selected_status == 'All') ? 'active' : ''; ?>" onclick="window.location.href='reports.php?branch=<?php echo urlencode($selected_branch); ?>&sem=<?php echo urlencode($selected_sem); ?>&subject=<?php echo urlencode($selected_subject); ?>&search=<?php echo urlencode($search_query); ?>&status=All'">
                <div class="info"><span>Total Evaluated</span><h4><?php echo $total_eval; ?></h4></div>
                <div class="icon-box" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-users"></i></div>
            </div>
            
            <div class="stat-badge <?php echo ($selected_status == 'Approved') ? 'active' : ''; ?>" onclick="window.location.href='reports.php?branch=<?php echo urlencode($selected_branch); ?>&sem=<?php echo urlencode($selected_sem); ?>&subject=<?php echo urlencode($selected_subject); ?>&search=<?php echo urlencode($search_query); ?>&status=Approved'">
                <div class="info"><span>Passed (Approved)</span><h4 class="text-success"><?php echo $passed; ?></h4></div>
                <div class="icon-box" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-check-circle"></i></div>
            </div>
            
            <div class="stat-badge <?php echo ($selected_status == 'Rejected') ? 'active' : ''; ?>" onclick="window.location.href='reports.php?branch=<?php echo urlencode($selected_branch); ?>&sem=<?php echo urlencode($selected_sem); ?>&subject=<?php echo urlencode($selected_subject); ?>&search=<?php echo urlencode($search_query); ?>&status=Rejected'">
                <div class="info"><span>Needs Revision</span><h4 class="text-danger"><?php echo $rejected; ?></h4></div>
                <div class="icon-box" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-exclamation-circle"></i></div>
            </div>
            
            <div class="stat-badge" style="cursor: default; border-bottom: 4px solid var(--primary);">
                <div class="info"><span>Pass Percentage</span><h4><?php echo $pass_percentage; ?>%</h4></div>
                <div class="icon-box" style="background: rgba(15, 23, 42, 0.05); color: #0f172a;"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>

        <!-- 📋 REPORTS TABLE -->
        <div class="content-box p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Enrollment No.</th>
                            <th style="width: 30%;">Student Name</th>
                            <th style="width: 15%;">Practical No.</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 15%;">Total Marks</th>
                            <th style="width: 10%;" class="text-end pe-4 hide-on-print">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-file-excel fa-3x mb-3 opacity-25"></i><br>
                                    <h5 class="fw-bold text-dark mb-1">No Records Found</h5>
                                    <p class="small mb-0">No evaluated reports available for this selection.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($reports as $r): ?>
                            <tr>
                                <td class="fw-bold" style="color:#64748b; font-size: 13px;"><?php echo htmlspecialchars($r['student_enrollment']); ?></td>
                                <td><div class="fw-bold text-dark"><?php echo htmlspecialchars($r['student_name']); ?></div></td>
                                <td><span class="text-muted fw-bold small"><i class="fas fa-file-code me-1"></i> <?php echo htmlspecialchars($r['practical_no']); ?></span></td>
                                <td>
                                    <span class="badge-modern status-<?php echo htmlspecialchars($r['status']); ?>">
                                        <?php echo htmlspecialchars($r['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="<?php echo ($r['status'] == 'Approved') ? 'text-success' : 'text-danger'; ?>" style="font-size:16px;">
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
                                        <i class="fas fa-bars"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 🌟 MODAL: SCORECARD PREVIEW -->
    <div id="scoreModalOverlay" class="rubric-modal-overlay">
        <div class="rubric-modal">
            <div class="modal-header-custom" id="score_header_bg" style="background: linear-gradient(135deg, #059669, #10b981);">
                <h5 class="mb-0" id="score_title"><i class="fas fa-award me-2"></i> Report Scorecard</h5>
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
                remarkBox.classList.add('border-danger');
                remarkBox.classList.remove('border-info');
            } else {
                headerBg.style.background = 'linear-gradient(135deg, #059669, #10b981)';
                totalBox.classList.remove('text-danger');
                totalBox.classList.add('text-warning');
                remarkBox.classList.add('border-info');
                remarkBox.classList.remove('border-danger');
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
        }
    </script>
</body>
</html>