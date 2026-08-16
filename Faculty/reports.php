<?php
session_start();
include '../db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$faculty_name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'M.C.THAKOR';

// ==========================================
// 🚀 NEW LOGIC: PURE DATABASE DRIVEN FILTERS
// ==========================================

// 1. Fetch Branches from Database
$available_branches = [];
$branch_res = $conn->query("SELECT DISTINCT department FROM subjects WHERE department IS NOT NULL AND department != ''");
if($branch_res) {
    while($r = $branch_res->fetch_assoc()) {
        $available_branches[] = $r['department'];
    }
}
if(empty($available_branches)) $available_branches = ['Computer Engineering'];
$selected_branch = isset($_GET['branch']) ? $_GET['branch'] : $available_branches[0];

// 2. Semesters (Fixed 1 to 6)
$available_semesters = ['Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Semester 5', 'Semester 6'];
$selected_sem = isset($_GET['sem']) ? $_GET['sem'] : 'Semester 1';

// 3. Fetch Subjects ONLY for Selected Branch & Semester
$available_subjects = [];
$safe_br = $conn->real_escape_string($selected_branch);
$safe_sm = $conn->real_escape_string($selected_sem);

$sub_query = "SELECT subject_name FROM subjects WHERE department = '$safe_br' AND semester = '$safe_sm'";
$sub_res = $conn->query($sub_query);
if(!$sub_res) { $sub_res = $conn->query("SELECT subject_name FROM subjects WHERE department = '$safe_br' AND sem = '$safe_sm'"); }
if ($sub_res) {
    while($r = $sub_res->fetch_assoc()) {
        $available_subjects[] = $r['subject_name'];
    }
}

$selected_subject = isset($_GET['subject']) ? $_GET['subject'] : (!empty($available_subjects) ? $available_subjects[0] : '');
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// ==========================================
// FETCH EVALUATED REPORTS
// ==========================================
$reports = [];
$total_eval = 0; $passed = 0; $rejected = 0;

if (!empty($selected_subject)) {
    $safe_sub = $conn->real_escape_string($selected_subject);
    
    // Status 'Pending' wali file reports mein print nahi hogi
    $q = "SELECT s.student_id, u.name as student_name, s.status, s.marks 
          FROM submissions s JOIN users u ON s.student_id = u.user_id 
          WHERE s.subject_name = '$safe_sub' AND u.designation = '$safe_sm' AND u.department = '$safe_br' AND s.status != 'Pending'";
          
    if (!empty($search_query)) {
        $q .= " AND (u.name LIKE '%".$conn->real_escape_string($search_query)."%' OR s.student_id LIKE '%".$conn->real_escape_string($search_query)."%')";
    }
    
    $r = $conn->query($q);
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $reports[] = $row;
            $total_eval++;
            if($row['status'] == 'Approved') $passed++;
            if($row['status'] == 'Rejected') $rejected++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Term Work Reports - KDP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f1f5f9; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background-color: #113460; color: #ffffff; display: flex; flex-direction: column; padding: 25px 0; z-index: 10; }
        .sidebar-logo-container { text-align: center; margin-bottom: 20px; }
        .logo-wrapper { width: 90px; height: 90px; background: #ffffff; border-radius: 50%; margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 3px solid rgba(255,255,255,0.2); }
        .sidebar-logo { width: 105%; height: auto; }
        .sidebar-title h2 { font-size: 19px; font-weight: 600; margin:0;}
        .sidebar-title p { font-size: 13px; color: #94a3b8; margin:2px 0 0 0;}
        .sidebar-divider { height: 1px; background: #1e4b85; margin: 15px 20px; }
        
        .nav-links { list-style: none; padding: 0; flex-grow: 1; margin: 10px 0 0 0; }
        .nav-links li { padding: 15px 25px; margin: 5px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 15px; transition: 0.3s; color: #cbd5e1; }
        .nav-links li:hover { background: #1e4b85; color: white; }
        .nav-links li.active { background: #2563eb; color: white; box-shadow: 0 4px 10px rgba(37,99,235,0.3);}
        .nav-links li i { font-size: 18px; width: 20px; text-align: center;}
        
        .main { flex: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column;}
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-left h2 { color: #0f172a; font-size: 26px; font-weight: 700; margin:0;}
        .header-left p { color: #64748b; font-size: 14px; margin: 5px 0 0 0; }
        .header-profile { display: flex; align-items: center; gap: 15px; background: #ffffff; padding: 8px 10px 8px 20px; border-radius: 50px; border: 1px solid #e2e8f0; text-decoration:none; }
        
        .content-card { background: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; border: 1px solid #e2e8f0; }
        
        .stat-card { background:white; padding:20px; border-radius:12px; border:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex:1;}
        .stat-title { font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;}
        .stat-val { font-size:26px; font-weight:700; color:#0f172a;}
        .stat-icon { width:40px; height:40px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:18px;}
        
        .table-custom th { background: #f8fafc; color: #64748b; padding: 14px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-top:none; border-bottom:2px solid #e2e8f0;}
        .table-custom td { vertical-align: middle; padding: 16px 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; font-weight: 500;}
        
        @media print {
            .sidebar, .header, .filter-area, .btn-print { display: none !important; }
            .main { padding: 0; }
            .print-area { display: block; }
            .content-card { border:none; box-shadow:none; padding:0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <div class="logo-wrapper"><img src="../assets/images/KDP-Logo.png" class="sidebar-logo" onerror="this.src='../assets/images/college-logo.png'"></div>
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2><p>Faculty Portal</p></div>
        </div>
        <div class="sidebar-divider"></div>
        <ul class="nav-links">
            <li onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='labmanual_list.php'"><i class="fas fa-book"></i> Lab Manuals</li>
            <li class="active" onclick="window.location.href='reports.php'"><i class="fas fa-file-alt"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
            <li onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>    
        </ul>
    </div>

    <div class="main">
        <div class="header">
            <div class="header-left">
                <h2>Term Work Reports</h2>
                <p>View, search and print evaluated student reports.</p>
            </div>
            <a href="profile.php" class="header-profile">
                <div style="text-align:right;"><div style="font-size:11px; color:#64748b; font-weight:600;">WELCOME BACK,</div><div style="font-size:15px; color:#0f172a; font-weight:700; margin:0;"><?php echo $faculty_name; ?></div></div>
                <div style="width:42px; height:42px; border-radius:50%; background:#2563eb; color:white; display:flex; align-items:center; justify-content:center; font-weight:bold; border:2px solid #2563eb;"><?php echo strtoupper(substr($faculty_name, 0, 1)); ?>.</div>
            </a>
        </div>

        <div class="content-card filter-area">
            <form method="GET" id="filterForm" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label style="font-size:11px; font-weight:700; color:#64748b; margin-bottom:6px;">BRANCH</label>
                    <select name="branch" class="form-select" onchange="document.getElementById('filterForm').submit();" style="border-radius:8px; background:#f8fafc; font-weight:500;">
                        <?php foreach($available_branches as $b) { echo "<option value='$b' ".($selected_branch==$b?'selected':'').">$b</option>"; } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label style="font-size:11px; font-weight:700; color:#64748b; margin-bottom:6px;">SEMESTER</label>
                    <select name="sem" class="form-select" onchange="document.getElementById('filterForm').submit();" style="border-radius:8px; background:#f8fafc; font-weight:500;">
                        <?php foreach($available_semesters as $s) { echo "<option value='$s' ".($selected_sem==$s?'selected':'').">$s</option>"; } ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label style="font-size:11px; font-weight:700; color:#64748b; margin-bottom:6px;">SUBJECT</label>
                    <select name="subject" class="form-select" onchange="document.getElementById('filterForm').submit();" style="border-radius:8px; background:#f8fafc; font-weight:500;">
                        <?php if(empty($available_subjects)) echo "<option value=''>No Subjects Found</option>"; ?>
                        <?php foreach($available_subjects as $sub) { echo "<option value='".htmlspecialchars($sub)."' ".($selected_subject==$sub?'selected':'').">".htmlspecialchars($sub)."</option>"; } ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label style="font-size:11px; font-weight:700; color:#64748b; margin-bottom:6px;">SEARCH ENROLLMENT</label>
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>" onchange="document.getElementById('filterForm').submit();" style="border-radius:8px; background:#f8fafc; font-weight:500;">
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" onclick="window.print()" class="btn btn-print" style="background:#059669; color:white; font-weight:600; padding:9px 18px; border-radius:8px; border:none;"><i class="fa-solid fa-print"></i> Print</button>
                </div>
            </form>
        </div>

        <div class="d-flex gap-4 mb-4 filter-area">
            <div class="stat-card">
                <div><div class="stat-title">Total Evaluated</div><div class="stat-val"><?php echo $total_eval; ?></div></div>
                <div class="stat-icon" style="background:#eff6ff; color:#3b82f6;"><i class="fa-solid fa-users"></i></div>
            </div>
            <div class="stat-card">
                <div><div class="stat-title">Passed (Approved)</div><div class="stat-val"><?php echo $passed; ?></div></div>
                <div class="stat-icon" style="background:#d1fae5; color:#059669;"><i class="fa-solid fa-circle-check"></i></div>
            </div>
            <div class="stat-card">
                <div><div class="stat-title">Needs Revision (Rejected)</div><div class="stat-val"><?php echo $rejected; ?></div></div>
                <div class="stat-icon" style="background:#fee2e2; color:#dc2626;"><i class="fa-solid fa-circle-exclamation"></i></div>
            </div>
        </div>

        <div class="content-card print-area flex-grow-1 pt-4">
            <div class="text-center mb-4">
                <h3 style="font-weight:700; color:#0f172a; margin-bottom:5px;">K.D. Polytechnic - Term Work Report</h3>
                <p style="color:#64748b; font-weight:500;">Branch: <?php echo htmlspecialchars($selected_branch); ?> | Semester: <?php echo htmlspecialchars($selected_sem); ?> | Subject: <?php echo htmlspecialchars($selected_subject); ?></p>
            </div>
            <table class="table table-custom">
                <thead><tr><th style="width:20%;">Enrollment No.</th><th style="width:40%;">Student Name</th><th style="width:20%; text-align:center;">Status</th><th style="width:20%; text-align:center;">Marks (Out of 20)</th></tr></thead>
                <tbody>
                    <?php if(empty($reports)) { echo "<tr><td colspan='4' class='text-center py-5 text-muted'><i class='fa-solid fa-file-excel fs-1 mb-3' style='color:#e2e8f0;'></i><br>No report data available for this subject.</td></tr>"; } else {
                        foreach($reports as $r) {
                            $color = $r['status'] == 'Approved' ? '#059669' : '#dc2626';
                            echo "<tr>
                                    <td style='color:#64748b;'>".htmlspecialchars($r['student_id'])."</td>
                                    <td class='fw-bold text-dark'>".htmlspecialchars($r['student_name'])."</td>
                                    <td class='text-center fw-bold' style='color:$color;'>".htmlspecialchars($r['status'])."</td>
                                    <td class='text-center fw-bold fs-6'>".htmlspecialchars($r['marks'])."</td>
                                  </tr>";
                        }
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>