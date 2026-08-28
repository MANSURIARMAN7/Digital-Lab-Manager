<?php
session_start();
include '../db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}
$faculty_id = $_SESSION['user_id'];
$faculty_name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Faculty';

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

$total_sub = 0; $pending = 0; $approved = 0; $rejected = 0;
if (!empty($selected_subject)) {
    $safe_sub = $conn->real_escape_string($selected_subject);
    $stat_sql = "SELECT status FROM student_submissions WHERE subject_name = '$safe_sub'";
    $stat_res = $conn->query($stat_sql);
    if ($stat_res) {
        while ($r = $stat_res->fetch_assoc()) {
            $total_sub++;
            if ($r['status'] == 'Pending') $pending++;
            if ($r['status'] == 'Approved') $approved++;
            if ($r['status'] == 'Rejected') $rejected++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Dashboard - KDP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ================= UNIFIED SIDEBAR & BODY CSS ================= */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f1f5f9; display: flex; height: 100vh; overflow: hidden; }
        
        .sidebar { width: 260px; min-width: 260px; background-color: #113460; color: #ffffff; display: flex; flex-direction: column; padding: 25px 0; box-shadow: 4px 0 10px rgba(0,0,0,0.1); z-index: 10; flex-shrink: 0;}
        .sidebar-logo-container { text-align: center; margin-bottom: 20px; }
        .logo-wrapper { width: 90px; height: 90px; background: #ffffff; border-radius: 50%; margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; overflow: hidden; box-shadow: 0 0 15px rgba(255,255,255,0.15); border: 3px solid rgba(255,255,255,0.2); }
        .sidebar-logo { width: 105%; height: auto; }
        .sidebar-title h2 { font-size: 19px; font-weight: 600; letter-spacing: 0.5px; margin: 0; }
        .sidebar-title p { font-size: 13px; color: #94a3b8; margin: 2px 0 0 0;}
        .sidebar-divider { height: 1px; background: #1e4b85; margin: 15px 20px; }
        
        .nav-links { list-style: none; padding: 0; flex-grow: 1; margin: 10px 0 0 0; }
        .nav-links li { padding: 15px 25px; margin: 5px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 15px; transition: 0.3s; color: #cbd5e1; }
        .nav-links li:hover { background: #1e4b85; color: white; }
        .nav-links li.active { background: #2563eb; color: white; box-shadow: 0 4px 10px rgba(37,99,235,0.3); }
        .nav-links li i { font-size: 18px; width: 20px; text-align: center; }
        .logout-btn { color: #fca5a5 !important; margin-top: auto; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.1) !important; color: #ef4444 !important; }
        
        .main { flex: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column; }
        /* =============================================================== */

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-left h2 { color: #0f172a; font-size: 26px; font-weight: 700; margin: 0; }
        .header-left p { color: #64748b; font-size: 14px; margin: 5px 0 0 0; }
        .header-profile { display: flex; align-items: center; gap: 15px; background: #ffffff; padding: 8px 10px 8px 20px; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; cursor: pointer; text-decoration:none; }
        .profile-text { display: flex; flex-direction: column; text-align: right; }
        .welcome-text { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; margin: 0;}
        .faculty-name { font-size: 15px; color: #0f172a; font-weight: 700; margin: 0;}
        .profile-avatar { width: 42px; height: 42px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 2px solid #2563eb; }

        .dropdown-section { background: white; padding: 20px 25px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .dropdown-box { display: flex; flex-direction: column; flex: 1; }
        .dropdown-box label { color: #64748b; font-size: 11px; margin-bottom: 6px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
        .dropdown-box select { padding: 10px 15px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; color: #113460; font-weight: 600; transition: 0.2s; width: 100%;}
        
        .cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 22px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.04); border-left: 5px solid #2563eb; }
        .card h3 { color: #64748b; font-size: 14px; margin-bottom: 5px; font-weight: 600;}
        .card p { color: #0f172a; font-size: 26px; font-weight: 700; }
        .card i { font-size: 35px; color: #e2e8f0; }

        .table-section { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f8fafc; color: #64748b; padding: 14px 12px; text-align: left; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-weight: 700; }
        td { padding: 16px 12px; border-bottom: 1px solid #e2e8f0; color: #1e293b; font-size: 14.5px; font-weight: 500;}
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .pending { background: #fef3c7; color: #d97706; }
        .approved { background: #d1fae5; color: #059669; }
        .rejected { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <div class="logo-wrapper">
                <img src="../assets/images/KDP-Logo.png" class="sidebar-logo" onerror="this.src='../assets/images/college-logo.png'">
            </div>
            <div class="sidebar-title">
                <h2>K.D. Polytechnic</h2>
                <p>Faculty Portal</p>
            </div>
        </div>
        <div class="sidebar-divider"></div>
        <ul class="nav-links">
            <li class="active" onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='labmanual_list.php'"><i class="fas fa-check-circle"></i> Review & Evaluate</li>
            <li onclick="window.location.href='reports.php'"><i class="fas fa-file-alt"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
            <li class="logout-btn" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>    
        </ul>
    </div>

    <div class="main">
        <div class="header">
            <div class="header-left">
                <h2>Faculty Dashboard</h2>
                <p>Manage student submissions and track progress.</p>
            </div>
            <a href="profile.php" class="header-profile">
                <div class="profile-text">
                    <span class="welcome-text">WELCOME BACK,</span>
                    <span class="faculty-name"><?php echo $faculty_name; ?></span>
                </div>
                <div class="profile-avatar"><?php echo strtoupper(substr($faculty_name, 0, 1)); ?></div>
            </a>
        </div>

        <form method="GET" id="dashboardFilterForm" class="dropdown-section">
            <div class="dropdown-box">
                <label><i class="fa-solid fa-code-branch me-1"></i> Choose Branch</label>
                <select name="branch" onchange="document.getElementById('dashboardFilterForm').submit();">
                    <?php foreach($available_branches as $b) { ?>
                        <option value="<?php echo $b; ?>" <?php if($selected_branch == $b) echo 'selected'; ?>><?php echo $b; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="dropdown-box">
                <label><i class="fa-solid fa-layer-group me-1"></i> Choose Semester</label>
                <select name="sem" onchange="document.getElementById('dashboardFilterForm').submit();">
                    <?php foreach($available_semesters as $s) { ?>
                        <option value="<?php echo $s; ?>" <?php if($selected_sem == $s) echo 'selected'; ?>><?php echo $s; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="dropdown-box">
                <label><i class="fa-solid fa-book me-1"></i> Select Subject</label>
                <select name="subject" onchange="document.getElementById('dashboardFilterForm').submit();">
                    <?php if(empty($available_subjects)) echo "<option value=''>No Subjects Found</option>"; ?>
                    <?php foreach($available_subjects as $sub) { ?>
                        <option value="<?php echo htmlspecialchars($sub); ?>" <?php if($selected_subject == $sub) echo 'selected'; ?>><?php echo htmlspecialchars($sub); ?></option>
                    <?php } ?>
                </select>
            </div>
        </form>

        <div class="cards">
            <div class="card" style="border-left-color: #3b82f6;">
                <div><h3>Total Submissions</h3><p><?php echo $total_sub; ?></p></div>
                <i class="fa-solid fa-file-pdf"></i>
            </div>
            <div class="card" style="border-left-color: #f59e0b;">
                <div><h3>Pending</h3><p><?php echo $pending; ?></p></div>
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="card" style="border-left-color: #10b981;">
                <div><h3>Approved</h3><p><?php echo $approved; ?></p></div>
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="card" style="border-left-color: #ef4444;">
                <div><h3>Rejected</h3><p><?php echo $rejected; ?></p></div>
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
        </div>
        
        <div class="table-section">
            <h3 style="color:#0f172a; font-size:16px; margin-bottom:15px;">Recent Submissions</h3>
            <table>
                <tr>
                    <th>Student Name</th>
                    <th>Enrollment No.</th>
                    <th>Status</th>
                    <th>Marks</th>
                </tr>
                <?php
                if (!empty($selected_subject)) {
                    $rec_sql = "SELECT s.*, u.name as student_name, u.email as student_enrollment 
                                FROM student_submissions s 
                                JOIN users u ON s.student_id = u.user_id 
                                WHERE s.subject_name = '$safe_sub' 
                                ORDER BY s.submitted_at DESC LIMIT 5";
                    
                    $rec_res = $conn->query($rec_sql);
                    
                    if ($rec_res && $rec_res->num_rows > 0) {
                        while ($r = $rec_res->fetch_assoc()) {
                            $st_cls = strtolower($r['status']);
                            echo "<tr>";
                            echo "<td><strong>".htmlspecialchars($r['student_name'])."</strong></td>";
                            echo "<td>".htmlspecialchars($r['student_enrollment'])."</td>";
                            echo "<td><span class='badge {$st_cls}'>".htmlspecialchars($r['status'])."</span></td>";
                            echo "<td><strong>".($r['status'] == 'Pending' ? '--' : $r['marks'] . ' / 20')."</strong></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center; padding:30px; color:#94a3b8;'><i class='fa-solid fa-folder-open mb-2 fs-3'></i><br>No recent submissions found for this selection.</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center; padding:30px; color:#94a3b8;'><i class='fa-solid fa-folder-open mb-2 fs-3'></i><br>No subject selected.</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>
</body>
</html>