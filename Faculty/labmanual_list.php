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

// 🔥 SMART DB FIX: Add Rubric Columns dynamically if they don't exist
$check_rubric = $conn->query("SHOW COLUMNS FROM student_submissions LIKE 'mark_reg'");
if ($check_rubric && $check_rubric->num_rows == 0) {
    @$conn->query("ALTER TABLE student_submissions ADD COLUMN mark_reg INT DEFAULT 0 AFTER marks");
    @$conn->query("ALTER TABLE student_submissions ADD COLUMN mark_und INT DEFAULT 0 AFTER mark_reg");
    @$conn->query("ALTER TABLE student_submissions ADD COLUMN mark_obs INT DEFAULT 0 AFTER mark_und");
    @$conn->query("ALTER TABLE student_submissions ADD COLUMN mark_viva INT DEFAULT 0 AFTER mark_obs");
}

$msg = "";

// ==========================================
// 🚀 EVALUATION & SUBMIT LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Reset Marks
    if (isset($_POST['action_type']) && $_POST['action_type'] == 'single_grade' && $_POST['action'] == 'Reset') {
        $sub_id = intval($_POST['submission_id']);
        if($conn->query("UPDATE student_submissions SET status='Pending', marks=0, mark_reg=0, mark_und=0, mark_obs=0, mark_viva=0, feedback=NULL WHERE submission_id=$sub_id")){
            $msg = "<div class='alert-custom alert-warning'><i class='fas fa-undo me-2'></i> Evaluation Reset Successfully!</div>";
        }
    }
    
    // 2. Grade/Reject Submission
    elseif (isset($_POST['action_type']) && $_POST['action_type'] == 'rubric_grade') {
        $sub_id = intval($_POST['submission_id']);
        $action = $_POST['action'];
        $remark = $conn->real_escape_string(trim($_POST['remark']));
        
        // 🔥 FIX: IF REJECTED, MARKS MUST BE ZERO
        if ($action == 'Reject') {
            $reg = 0; $und = 0; $obs = 0; $viva = 0; $total = 0;
            $status = 'Rejected';
        } else {
            $reg = isset($_POST['mark_reg']) ? intval($_POST['mark_reg']) : 0;
            $und = isset($_POST['mark_und']) ? intval($_POST['mark_und']) : 0;
            $obs = isset($_POST['mark_obs']) ? intval($_POST['mark_obs']) : 0;
            $viva = isset($_POST['mark_viva']) ? intval($_POST['mark_viva']) : 0;
            $total = $reg + $und + $obs + $viva;
            $status = 'Approved';
        }
        
        // Save full rubric breakdown
        if($conn->query("UPDATE student_submissions SET status='$status', marks=$total, mark_reg=$reg, mark_und=$und, mark_obs=$obs, mark_viva=$viva, feedback='$remark' WHERE submission_id=$sub_id")){
            $msg = "<div class='alert-custom alert-success'><i class='fas fa-check-circle me-2'></i> Grade Saved: $total/20 ($status)</div>";
        }
    }
    
    // 3. Bulk Approve
    elseif (isset($_POST['action_type']) && $_POST['action_type'] == 'bulk_approve') {
        $b_sub = $conn->real_escape_string($_POST['bulk_subject']);
        if($conn->query("UPDATE student_submissions SET status='Approved', marks=20, mark_reg=5, mark_und=5, mark_obs=5, mark_viva=5, feedback='Auto Approved by Faculty' WHERE subject_name='$b_sub' AND status='Pending'")){
            $msg = "<div class='alert-custom alert-success'><i class='fas fa-check-double me-2'></i> Bulk Approval Successful!</div>";
        }
    }
    
    // 4. Export CSV
    elseif (isset($_POST['export_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="Marks_Report.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Student Name', 'Enrollment No.', 'Subject', 'Practical No', 'Status', 'Regularity', 'Understanding', 'Observation', 'Viva', 'Total Marks', 'Feedback']);
        
        $e_sub = $conn->real_escape_string($_POST['export_subject']);
        $q = "SELECT s.*, u.name as student_name, u.email as student_enrollment FROM student_submissions s JOIN users u ON s.student_id = u.user_id WHERE s.subject_name='$e_sub' ORDER BY u.email ASC";
        $res = $conn->query($q);
        
        if($res) {
            while($row = $res->fetch_assoc()) {
                fputcsv($out, [$row['student_name'], $row['student_enrollment'], $row['subject_name'], $row['practical_no'], $row['status'], $row['mark_reg'], $row['mark_und'], $row['mark_obs'], $row['mark_viva'], $row['marks'], $row['feedback']]);
            }
        }
        fclose($out);
        exit();
    }
}

// ==========================================
// 🧠 SMART MAPPING LOGIC
// ==========================================
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
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'All';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// FETCH SUBMISSIONS
$submissions_data = [];
$t_total = 0; $t_pending = 0; $t_approved = 0; $t_rejected = 0;

if (!empty($selected_subject)) {
    $safe_sub = $conn->real_escape_string($selected_subject);
    $q = "SELECT s.*, u.name as student_name, u.email as student_enrollment FROM student_submissions s JOIN users u ON s.student_id = u.user_id WHERE s.subject_name = '$safe_sub'";
    
    // Quick query for stats before filtering
    $stat_q = $conn->query("SELECT status FROM student_submissions WHERE subject_name = '$safe_sub'");
    if($stat_q) {
        while($st = $stat_q->fetch_assoc()){
            $t_total++;
            if($st['status'] == 'Pending') $t_pending++;
            if($st['status'] == 'Approved') $t_approved++;
            if($st['status'] == 'Rejected') $t_rejected++;
        }
    }

    if ($status_filter !== 'All') { $q .= " AND s.status = '" . $conn->real_escape_string($status_filter) . "'"; }
    if (!empty($search_query)) {
        $safe_search = $conn->real_escape_string($search_query);
        $q .= " AND (u.name LIKE '%$safe_search%' OR u.email LIKE '%$safe_search%')";
    }
    $q .= " ORDER BY s.submitted_at DESC";
    
    $res = $conn->query($q);
    if ($res) { while($row = $res->fetch_assoc()) { $submissions_data[] = $row; } }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluate Manuals - KDP Faculty</title>
    
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
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; overflow-y: auto; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* 🌐 TOPBAR */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 10px 22px 10px 28px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 17px; color: var(--text-main); font-weight: 900; letter-spacing: 0.3px;}
        .profile-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.4);}

        /* 📊 MINI STATS RIBBON */
        .stats-ribbon { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap;}
        .stat-badge { flex: 1; min-width: 150px; background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.01);}
        .stat-badge i { font-size: 24px; }
        .stat-badge .info h4 { margin: 0; font-size: 22px; font-weight: 800; line-height: 1;}
        .stat-badge .info span { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted);}

        /* 🎛️ SMART FILTER PANEL (Search Bar Alignment Fixed) */
        .filter-panel { background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #cbd5e1; box-shadow: 0 4px 15px rgba(0,0,0,0.02); margin-bottom: 25px; }
        .filter-panel label { color: var(--primary); font-size: 11px; margin-bottom: 6px; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; display: block;}
        .filter-panel select { width: 100%; padding: 0 15px; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; font-weight: 600; font-size: 13.5px; transition: var(--transition-bounce);}
        .filter-panel select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); background: #ffffff;}
        
        /* 🔍 SEARCH BAR FIX */
        .search-wrapper { display: flex; height: 42px; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; background: #f8fafc; transition: var(--transition-bounce);}
        .search-wrapper:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); background: #ffffff;}
        .search-wrapper input { border: none; background: transparent; padding: 0 15px; font-weight: 600; font-size: 13.5px; width: 100%; outline: none; }
        .search-wrapper button { border: none; background: var(--primary); color: white; padding: 0 20px; font-weight: bold; transition: 0.2s; cursor: pointer; }
        .search-wrapper button:hover { background: var(--primary-hover); }

        /* TABLE */
        .content-box { background: var(--surface); border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden;}
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; }
        .table-custom tbody tr:hover td { background-color: #f8fafc; }
        
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-Pending { background: #fef3c7; color: #d97706; border: 1px solid #fde68a;}
        .status-Approved { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0;}
        .status-Rejected { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;}

        .btn-action { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; transition: var(--transition-bounce); display: inline-flex; align-items: center; gap: 6px;}
        .btn-action:hover { background: #2563eb; color: white; transform: translateY(-2px); }
        .btn-grade { background: linear-gradient(135deg, #f59e0b, #d97706); color: white;}
        .btn-grade:hover { background: #b45309; color: white; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);}
        .btn-score { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); }
        .btn-score:hover { background: #059669; color: white; }

        .alert-custom { padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-size: 13.5px; font-weight: 700; display: flex; align-items: center;}
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        /* 🌟 MODALS STYLING (Standardized Bootstrap Modals) */
        .modal-content { border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .modal-header-custom { background: linear-gradient(135deg, #1e3a8a, #4338ca); padding: 18px 25px; color: white; display: flex; justify-content: space-between; align-items: center;}
        .modal-header-custom h5 { margin: 0; font-size: 18px; font-weight: 800; }
        .btn-close-custom { background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s;}
        .btn-close-custom:hover { background: rgba(255,255,255,0.4); }

        .rubric-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; margin-bottom: 15px;}
        .rubric-input { border: 2px solid #cbd5e1; border-radius: 8px; font-weight: 800; color: var(--primary); text-align: center; padding: 8px;}
        .rubric-input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);}
        
        .score-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; font-weight: 600;}
        .score-item:last-child { border-bottom: none; }

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
            <div class="sidebar-subtitle">Faculty Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='labmanual_list.php'"><i class="fas fa-check-double"></i> Review & Evaluate</li>
            <li onclick="window.location.href='reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
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

        <?php if($msg != "") echo $msg; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1" style="font-size: 26px;">Evaluate Manuals</h3>
                <p class="text-muted small fw-semibold mb-0">Grade student submissions using the 20-marks rubric system.</p>
            </div>
            <div class="d-flex gap-2">
                <form method="POST">
                    <input type="hidden" name="action_type" value="bulk_approve">
                    <input type="hidden" name="bulk_subject" value="<?php echo htmlspecialchars($selected_subject); ?>">
                    <button type="submit" class="btn btn-success fw-bold px-3 py-2 shadow-sm" style="border-radius: 8px;" onclick="return confirm('Approve ALL pending submissions?');">
                        <i class="fas fa-check-double me-1"></i> Bulk Approve
                    </button>
                </form>
                <form method="POST">
                    <input type="hidden" name="export_csv" value="1">
                    <input type="hidden" name="export_subject" value="<?php echo htmlspecialchars($selected_subject); ?>">
                    <button type="submit" class="btn btn-outline-primary fw-bold px-3 py-2 bg-white" style="border-radius: 8px;">
                        <i class="fas fa-file-csv me-1"></i> Export Data
                    </button>
                </form>
            </div>
        </div>

        <div class="stats-ribbon">
            <div class="stat-badge border-start border-4 border-primary"><i class="fas fa-folder-open text-primary"></i><div class="info"><h4><?php echo $t_total; ?></h4><span>Total Received</span></div></div>
            <div class="stat-badge border-start border-4 border-warning"><i class="fas fa-clock text-warning"></i><div class="info"><h4><?php echo $t_pending; ?></h4><span>Pending Review</span></div></div>
            <div class="stat-badge border-start border-4 border-success"><i class="fas fa-check-circle text-success"></i><div class="info"><h4><?php echo $t_approved; ?></h4><span>Approved</span></div></div>
            <div class="stat-badge border-start border-4 border-danger"><i class="fas fa-times-circle text-danger"></i><div class="info"><h4><?php echo $t_rejected; ?></h4><span>Needs Redo</span></div></div>
        </div>

        <!-- 🎛️ FIXED FILTER PANEL (Search Bar Alignment Resolved) -->
        <div class="filter-panel">
            <form method="GET" id="filterForm" class="row align-items-end g-3">
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
                <div class="col-md-3">
                    <label><i class="fas fa-book me-1"></i> Subject</label>
                    <select name="subject" class="form-select" onchange="this.form.submit()">
                        <?php if(empty($available_subjects)) echo "<option value=''>No Subjects</option>"; ?>
                        <?php foreach($available_subjects as $sub) { echo "<option value='".htmlspecialchars($sub)."' ".($selected_subject==$sub?'selected':'').">".htmlspecialchars($sub)."</option>"; } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label><i class="fas fa-filter me-1"></i> Status</label>
                    <select name="status_filter" class="form-select" onchange="this.form.submit()">
                        <option value="All" <?php if($status_filter=='All') echo 'selected';?>>All Status</option>
                        <option value="Pending" <?php if($status_filter=='Pending') echo 'selected';?>>Pending</option>
                        <option value="Approved" <?php if($status_filter=='Approved') echo 'selected';?>>Approved</option>
                        <option value="Rejected" <?php if($status_filter=='Rejected') echo 'selected';?>>Rejected</option>
                    </select>
                </div>
                
                <!-- 🔍 FIXED SEARCH BAR ALIGNMENT -->
                <div class="col-md-3">
                    <label><i class="fas fa-search me-1"></i> Search Student</label>
                    <div class="search-wrapper">
                        <input type="text" name="search" placeholder="Name or Enroll No..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
            </form>
        </div>

        <div class="content-box p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Student Information</th>
                            <th>Practical No.</th>
                            <th>Current Status</th>
                            <th>Marks</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($submissions_data)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-clipboard-check fa-3x mb-3 opacity-25 text-primary"></i><br>
                                    <h5 class="fw-bold text-dark mb-1">Queue is Clear</h5>
                                    <p class="small mb-0">No submissions found matching your filters.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($submissions_data as $row): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark" style="font-size:14px;"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                    <small class="text-primary fw-bold" style="font-size:11px; letter-spacing:0.5px;"><?php echo htmlspecialchars($row['student_enrollment']); ?></small>
                                </td>
                                <td>
                                    <span class="bg-light border px-2 py-1 rounded fw-bold text-dark" style="font-size:12px;">
                                        <i class="fas fa-file-code text-muted me-1"></i> <?php echo htmlspecialchars($row['practical_no']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-modern status-<?php echo htmlspecialchars($row['status']); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="<?php echo ($row['status'] == 'Approved') ? 'text-success' : 'text-muted'; ?>" style="font-size:15px;">
                                        <?php echo ($row['status'] == 'Pending') ? '--' : $row['marks'] . ' <small class="text-muted fw-normal">/ 20</small>'; ?>
                                    </strong>
                                </td>
                                <td class="text-end pe-4">
                                    <?php 
                                        $raw_path = $row['file_path'];
                                        $pos = strpos($raw_path, 'uploads/');
                                        $safe_pdf_path = ($pos !== false) ? '../' . substr($raw_path, $pos) : '../' . $raw_path;
                                        
                                        $rubric_data = json_encode([
                                            'reg' => $row['mark_reg'], 'und' => $row['mark_und'],
                                            'obs' => $row['mark_obs'], 'viva' => $row['mark_viva'],
                                            'total' => $row['marks'], 'feedback' => $row['feedback']
                                        ]);
                                    ?>
                                    
                                    <!-- 👀 FIXED: OPEN PDF IN MODAL INSTEAD OF NEW TAB -->
                                    <button type="button" class="btn-action me-2" onclick="viewDocument('<?php echo htmlspecialchars($safe_pdf_path); ?>')">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    
                                    <?php if($row['status'] == 'Pending'): ?>
                                        <button type="button" class="btn-action btn-grade shadow-sm" onclick="openGradeModal(<?php echo $row['submission_id']; ?>, '<?php echo addslashes($row['student_name']); ?>', '<?php echo addslashes($row['practical_no']); ?>')">
                                            <i class="fas fa-star me-1"></i> Grade
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn-action btn-score me-1 shadow-sm" onclick='viewScorecard(<?php echo htmlspecialchars($rubric_data, ENT_QUOTES, 'UTF-8'); ?>, "<?php echo addslashes($row['student_name']); ?>")'>
                                            <i class="fas fa-chart-bar me-1"></i> Score
                                        </button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action_type" value="single_grade">
                                            <input type="hidden" name="action" value="Reset">
                                            <input type="hidden" name="submission_id" value="<?php echo $row['submission_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1" onclick="return confirm('Are you sure you want to reset this evaluation?');" title="Reset Marks">
                                                <i class="fas fa-undo-alt"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 🌟 MODAL 1: VIEW PDF INLINE -->
    <div class="modal fade" id="pdfModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" style="height: 85vh;">
                <div class="modal-header-custom">
                    <h5 class="mb-0"><i class="fas fa-file-pdf me-2"></i> Document Viewer</h5>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body p-0" style="background: #e2e8f0; height: 100%;">
                    <iframe id="pdfIframe" src="" width="100%" height="100%" style="border:none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- 🌟 MODAL 2: GRADE SUBMISSION (RUBRIC) -->
    <div class="modal fade" id="gradeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-custom">
                    <div>
                        <h5 class="mb-1"><i class="fas fa-clipboard-list me-2"></i> Rubric Evaluation</h5>
                        <small style="color: #bfdbfe;" id="modal_practical_no">Practical Details</small>
                    </div>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
                </div>
                
                <form method="POST" class="p-4 bg-white">
                    <input type="hidden" name="action_type" value="rubric_grade">
                    <input type="hidden" name="submission_id" id="modal_sub_id">
                    
                    <div class="d-flex align-items-center gap-3 mb-4 bg-light p-3 border rounded-3">
                        <div class="bg-primary text-white rounded-circle d-flex justify-content-center align-items-center" style="width:40px; height:40px; font-weight:bold;"><i class="fas fa-user"></i></div>
                        <div>
                            <small class="text-muted fw-bold text-uppercase" style="font-size:10px;">Evaluating Student</small>
                            <div id="modal_student_name" class="fw-bold text-dark" style="font-size:15px;">Student Name</div>
                        </div>
                    </div>

                    <div class="bg-light border p-3 rounded-3 mb-3">
                        <div class="row g-3">
                            <div class="col-6"><label class="form-label small fw-bold text-muted mb-1">Regularity (0-5)</label><input type="number" name="mark_reg" class="form-control text-center fw-bold text-primary" min="0" max="5" value="5" required></div>
                            <div class="col-6"><label class="form-label small fw-bold text-muted mb-1">Understanding (0-5)</label><input type="number" name="mark_und" class="form-control text-center fw-bold text-primary" min="0" max="5" value="5" required></div>
                            <div class="col-6"><label class="form-label small fw-bold text-muted mb-1">Observation (0-5)</label><input type="number" name="mark_obs" class="form-control text-center fw-bold text-primary" min="0" max="5" value="5" required></div>
                            <div class="col-6"><label class="form-label small fw-bold text-muted mb-1">Viva (0-5)</label><input type="number" name="mark_viva" class="form-control text-center fw-bold text-primary" min="0" max="5" value="5" required></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-dark mb-2">Faculty Remark (Optional)</label>
                        <input type="text" name="remark" class="form-control bg-light" placeholder="e.g. Good logic, improve indentation...">
                    </div>

                    <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-2">
                        <button type="button" class="btn text-muted fw-bold" data-bs-dismiss="modal">Cancel</button>
                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="Reject" class="btn btn-outline-danger fw-bold px-4">Reject & Redo</button>
                            <button type="submit" name="action" value="Approve" class="btn btn-success fw-bold px-4">Save & Approve</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 🌟 MODAL 3: SCORECARD PREVIEW -->
    <div class="modal fade" id="scoreModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header-custom" style="background: linear-gradient(135deg, #059669, #10b981);">
                    <h5 class="mb-0"><i class="fas fa-award me-2"></i> Final Scorecard</h5>
                    <button type="button" class="btn-close-custom" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="text-center mb-4"><h5 id="score_student_name" class="fw-bold text-dark mb-1">Student Name</h5></div>
                    <div class="bg-light border rounded-3 p-3 mb-4">
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><span class="text-muted fw-bold">Regularity</span><span id="score_reg" class="text-primary fw-bold"></span></div>
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><span class="text-muted fw-bold">Understanding</span><span id="score_und" class="text-primary fw-bold"></span></div>
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><span class="text-muted fw-bold">Observation</span><span id="score_obs" class="text-primary fw-bold"></span></div>
                        <div class="d-flex justify-content-between"><span class="text-muted fw-bold">Viva / Quiz</span><span id="score_viva" class="text-primary fw-bold"></span></div>
                    </div>
                    <div class="d-flex justify-content-between bg-dark text-white p-3 rounded-3 mb-3">
                        <span class="text-uppercase fw-bold">Total Marks</span><h4 id="score_total" class="mb-0 fw-bold text-warning"></h4>
                    </div>
                    <div class="p-3 bg-light border-start border-4 border-info rounded"><span class="text-muted small fw-bold d-block mb-1">Faculty Remark:</span><div id="score_remark" class="fst-italic text-dark"></div></div>
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

        function viewDocument(url) {
            document.getElementById('pdfIframe').src = url;
            new bootstrap.Modal(document.getElementById('pdfModal')).show();
        }

        function openGradeModal(subId, studentName, pracNo) {
            document.getElementById('modal_sub_id').value = subId;
            document.getElementById('modal_student_name').innerText = studentName;
            document.getElementById('modal_practical_no').innerText = "Manual: " + pracNo;
            new bootstrap.Modal(document.getElementById('gradeModal')).show();
        }

        function viewScorecard(marksData, studentName) {
            document.getElementById('score_student_name').innerText = studentName;
            document.getElementById('score_reg').innerText = marksData.reg + " / 5";
            document.getElementById('score_und').innerText = marksData.und + " / 5";
            document.getElementById('score_obs').innerText = marksData.obs + " / 5";
            document.getElementById('score_viva').innerText = marksData.viva + " / 5";
            document.getElementById('score_total').innerText = marksData.total + " / 20";
            document.getElementById('score_remark').innerText = marksData.feedback ? '"' + marksData.feedback + '"' : 'No remarks provided.';
            new bootstrap.Modal(document.getElementById('scoreModal')).show();
        }
    </script>
</body>
</html>