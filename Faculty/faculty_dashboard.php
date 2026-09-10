<?php
session_start();
include '../db.php';

// 1. Secure Faculty Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];

// Fetch Faculty Details
$fac_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$faculty_id'");
$fac_data = $fac_query ? $fac_query->fetch_assoc() : null;

$faculty_name = $fac_data['name'] ?? (isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Faculty');
$faculty_dept = $fac_data['department'] ?? 'Computer Engineering';

// Generate Initials for Profile Avatar
$name_parts = explode(' ', trim($faculty_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

$fac_name_safe = $conn->real_escape_string($faculty_name);

// ==========================================
// 🧠 SMART MAPPING: Fetch ONLY branches where this faculty teaches
// ==========================================
$available_branches = [];
$branch_res = $conn->query("SELECT DISTINCT department FROM subjects WHERE faculty_name LIKE '%$fac_name_safe%' AND department IS NOT NULL AND department != ''");
if($branch_res && $branch_res->num_rows > 0) {
    while($r = $branch_res->fetch_assoc()) { $available_branches[] = $r['department']; }
}
if(empty($available_branches)) $available_branches = [$faculty_dept]; // Fallback to their own department
$selected_branch = isset($_GET['branch']) ? $conn->real_escape_string($_GET['branch']) : $available_branches[0];

// ==========================================
// 🧠 SMART MAPPING: Fetch ONLY semesters where this faculty teaches in the selected branch
// ==========================================
$available_semesters = [];
$sem_res = $conn->query("SELECT DISTINCT semester FROM subjects WHERE faculty_name LIKE '%$fac_name_safe%' AND department = '$selected_branch' ORDER BY semester ASC");
if($sem_res && $sem_res->num_rows > 0) {
    while($r = $sem_res->fetch_assoc()) { $available_semesters[] = "Semester " . $r['semester']; }
}
if(empty($available_semesters)) $available_semesters = ['Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Semester 5', 'Semester 6'];
$selected_sem = isset($_GET['sem']) ? $_GET['sem'] : $available_semesters[0];
$sem_number = (int) str_replace('Semester ', '', $selected_sem);

// ==========================================
// 🧠 SMART MAPPING: Fetch Subjects
// ==========================================
$available_subjects = [];
$sub_query = "SELECT subject_name FROM subjects WHERE faculty_name LIKE '%$fac_name_safe%' AND department = '$selected_branch' AND semester = '$sem_number'";
$sub_res = $conn->query($sub_query);
if ($sub_res && $sub_res->num_rows > 0) {
    while($r = $sub_res->fetch_assoc()) { $available_subjects[] = $r['subject_name']; }
}
$selected_subject = isset($_GET['subject']) ? $_GET['subject'] : (!empty($available_subjects) ? $available_subjects[0] : '');

// ==========================================
// 📊 STATS CALCULATION
// ==========================================
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

// Workload Progress (Total graded vs pending for this subject)
$graded_count = $approved + $rejected;
$workload_percent = ($total_sub > 0) ? round(($graded_count / $total_sub) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - KDP</title>
    
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

        /* 🌐 TOPBAR */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        .security-badge { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 10px; padding: 10px 18px; font-weight: 700; font-size: 13px; }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);}

        /* 🌟 WELCOME BANNER */
        .welcome-banner { background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: var(--radius-xl); padding: 35px; color: white; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;}
        .welcome-banner::before { content: ''; position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.05); border-radius: 50%; }
        .welcome-text-area { position: relative; z-index: 2; }
        .welcome-icon { font-size: 80px; opacity: 0.1; position: absolute; right: 40px; top: 20px; z-index: 1;}

        /* 📦 CONTENT CARDS */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 25px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        .content-box:hover { box-shadow: var(--shadow-float); }
        .box-title { font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }

        /* FILTER DROPDOWNS */
        .dropdown-section { background: white; padding: 20px 25px; border-radius: 12px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid rgba(226, 232, 240, 0.8); flex-wrap: wrap;}
        .dropdown-box { flex: 1; min-width: 200px; }
        .dropdown-box label { color: var(--primary); font-size: 11px; margin-bottom: 6px; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; display: block;}
        .dropdown-box select { width: 100%; padding: 12px 15px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; color: var(--text-main); font-weight: 600; transition: var(--transition-bounce); cursor: pointer;}
        .dropdown-box select:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(67, 56, 202, 0.1); background: #ffffff;}

        /* STATS GRID */
        .stat-card { background: var(--surface); border-radius: 16px; padding: 25px; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); cursor: pointer; position: relative; overflow: hidden; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-float); border-color: #cbd5e1; }
        .stat-card::after { content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 4px; }
        .card-blue::after { background: #3b82f6; }
        .card-yellow::after { background: #f59e0b; }
        .card-green::after { background: #10b981; }
        .card-red::after { background: #ef4444; }
        .stat-info h6 { font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 8px 0; }
        .stat-info h2 { font-size: 32px; font-weight: 800; color: var(--text-main); margin: 0; }
        .stat-icon { width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; }

        /* PROGRESS WIDGET */
        .progress-container { background: #e2e8f0; height: 10px; border-radius: 10px; overflow: hidden; margin-top: 15px; margin-bottom: 10px;}
        .progress-bar-custom { background: linear-gradient(90deg, #10b981, #34d399); height: 100%; border-radius: 10px; transition: width 1s ease-in-out; }

        /* TABLE */
        .table-custom th { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #ffffff;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; }
        .table-custom tbody tr:hover td { background-color: #f8fafc; }
        
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-Pending { background: #fef3c7; color: #d97706; }
        .status-Approved { background: #d1fae5; color: #059669; }
        .status-Rejected { background: #fee2e2; color: #dc2626; }

        .btn-view { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 700; text-decoration: none; transition: var(--transition-bounce); display: inline-flex; align-items: center; gap: 8px;}
        .btn-view:hover { background: #2563eb; color: white; transform: scale(1.05); }

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
            <div class="sidebar-subtitle">Faculty Portal</div>
        </div>
        <ul class="nav-links">
            <li class="active" onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='labmanual_list.php'"><i class="fas fa-check-double"></i> Review & Evaluate</li>
            <li onclick="window.location.href='reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
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
                <div class="security-badge d-none d-md-flex align-items-center">
                    <i class="fas fa-shield-check me-2"></i> Authorized Faculty Access
                </div>
            </div>
            
            <!-- 🌟 UPDATED PROFILE PILL -->
            <a href="profile.php" class="profile-pill" style="padding: 10px 22px 10px 28px;">
                <div class="profile-text">
                    <span class="profile-welcome" style="font-size: 11px;">Welcome Back,</span>
                    <h4 class="profile-name" style="font-size: 17px; font-weight: 900; letter-spacing: 0.3px;">
                        <?php 
                            $disp_name = htmlspecialchars($faculty_name);
                            if(stripos($disp_name, 'Prof') === false && stripos($disp_name, 'Dr') === false) {
                                echo "Prof. " . $disp_name;
                            } else {
                                echo $disp_name;
                            }
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar" style="width: 48px; height: 48px; font-size: 16px; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.4);"><?php echo $initials; ?></div>
            </a>
        </div>

        <!-- 🌟 WELCOME BANNER -->
        <div class="welcome-banner">
            <div class="welcome-text-area">
                <h2 class="fw-bold mb-2" style="font-size: 28px;">Hello, <?php echo htmlspecialchars($faculty_name); ?>! 👋</h2>
                <p class="mb-0" style="color: #cbd5e1; font-weight: 500; font-size: 15px;">You have <strong><?php echo $pending; ?> pending submissions</strong> awaiting your evaluation in <?php echo htmlspecialchars($selected_subject ?: 'your subjects'); ?>.</p>
            </div>
            <i class="fas fa-chalkboard-teacher welcome-icon"></i>
        </div>

        <!-- 🎛️ SMART FILTER DROPDOWNS -->
        <form method="GET" id="dashboardFilterForm" class="dropdown-section mb-4">
            <div class="dropdown-box">
                <label><i class="fas fa-building me-1"></i> Teaching Branch</label>
                <select name="branch" onchange="document.getElementById('dashboardFilterForm').submit();">
                    <?php if(empty($available_branches)) echo "<option value=''>No Branches Found</option>"; ?>
                    <?php foreach($available_branches as $b) { ?>
                        <option value="<?php echo htmlspecialchars($b); ?>" <?php if($selected_branch == $b) echo 'selected'; ?>><?php echo htmlspecialchars($b); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="dropdown-box">
                <label><i class="fas fa-layer-group me-1"></i> Active Semester</label>
                <select name="sem" onchange="document.getElementById('dashboardFilterForm').submit();">
                    <?php foreach($available_semesters as $s) { ?>
                        <option value="<?php echo htmlspecialchars($s); ?>" <?php if($selected_sem == $s) echo 'selected'; ?>><?php echo htmlspecialchars($s); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="dropdown-box">
                <label><i class="fas fa-book me-1"></i> Select Subject</label>
                <select name="subject" onchange="document.getElementById('dashboardFilterForm').submit();">
                    <?php if(empty($available_subjects)) echo "<option value=''>No Subjects Assigned</option>"; ?>
                    <?php foreach($available_subjects as $sub) { ?>
                        <option value="<?php echo htmlspecialchars($sub); ?>" <?php if($selected_subject == $sub) echo 'selected'; ?>><?php echo htmlspecialchars($sub); ?></option>
                    <?php } ?>
                </select>
            </div>
        </form>

        <div class="row g-4">
            
            <!-- LEFT COLUMN: STATS & RECENT TABLE (Takes 65% width) -->
            <div class="col-lg-8">
                
                <!-- 📊 STATS GRID -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-6">
                        <div class="stat-card card-blue" onclick="window.location.href='labmanual_list.php?branch=<?php echo urlencode($selected_branch); ?>&sem=<?php echo urlencode($selected_sem); ?>&subject=<?php echo urlencode($selected_subject); ?>'">
                            <div class="stat-info"><h6>Total</h6><h2><?php echo $total_sub; ?></h2></div>
                            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-folder"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card card-yellow" onclick="window.location.href='labmanual_list.php?branch=<?php echo urlencode($selected_branch); ?>&sem=<?php echo urlencode($selected_sem); ?>&subject=<?php echo urlencode($selected_subject); ?>&status=Pending'">
                            <div class="stat-info"><h6>Pending</h6><h2><?php echo $pending; ?></h2></div>
                            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card card-green" onclick="window.location.href='labmanual_list.php?branch=<?php echo urlencode($selected_branch); ?>&sem=<?php echo urlencode($selected_sem); ?>&subject=<?php echo urlencode($selected_subject); ?>&status=Approved'">
                            <div class="stat-info"><h6>Approved</h6><h2><?php echo $approved; ?></h2></div>
                            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-check-circle"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stat-card card-red" onclick="window.location.href='labmanual_list.php?branch=<?php echo urlencode($selected_branch); ?>&sem=<?php echo urlencode($selected_sem); ?>&subject=<?php echo urlencode($selected_subject); ?>&status=Rejected'">
                            <div class="stat-info"><h6>Rejected</h6><h2><?php echo $rejected; ?></h2></div>
                            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-times-circle"></i></div>
                        </div>
                    </div>
                </div>

                <!-- 📋 RECENT SUBMISSIONS TABLE -->
                <div class="content-box">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="box-title mb-0 border-0"><i class="fas fa-history text-primary me-2"></i> Recent Submissions</h5>
                        <a href="labmanual_list.php?subject=<?php echo urlencode($selected_subject); ?>" class="btn btn-sm btn-outline-primary fw-bold" style="border-radius: 8px;">View All</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Student Details</th>
                                    <th>Status</th>
                                    <th>Marks</th>
                                </tr>
                            </thead>
                            <tbody>
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
                                            echo "<td>
                                                    <div class='fw-bold text-dark' style='font-size:14px;'>".htmlspecialchars($r['student_name'])."</div>
                                                    <div class='text-muted small mt-1'>".htmlspecialchars($r['student_enrollment'])."</div>
                                                  </td>";
                                            echo "<td><span class='badge-modern status-{$r['status']}'>".htmlspecialchars($r['status'])."</span></td>";
                                            echo "<td><strong class='".($r['status'] == 'Approved' ? 'text-success' : 'text-muted')."'>".($r['status'] == 'Pending' ? '--' : $r['marks'] . ' / 20')."</strong></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' class='text-center py-4 text-muted'><i class='fa-solid fa-folder-open mb-2 fs-3 opacity-50'></i><br>No recent submissions found.</td></tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='3' class='text-center py-4 text-muted'><i class='fa-solid fa-folder-open mb-2 fs-3 opacity-50'></i><br>No subject selected.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: WIDGETS (Takes 35% width) -->
            <div class="col-lg-4">
                
                <!-- 🎯 GRADING WORKLOAD WIDGET -->
                <div class="content-box mb-4">
                    <h5 class="box-title"><i class="fas fa-tasks text-success me-2"></i> Grading Progress</h5>
                    
                    <div class="text-center mb-3">
                        <h2 class="fw-bold mb-0" style="color: var(--text-main); font-size: 36px;"><?php echo $workload_percent; ?>%</h2>
                        <span class="text-muted small fw-bold text-uppercase">Evaluated in <?php echo htmlspecialchars($selected_subject ?: 'Subject'); ?></span>
                    </div>

                    <div class="progress-container">
                        <div class="progress-bar-custom" style="width: <?php echo $workload_percent; ?>%;"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-3 text-muted small fw-semibold">
                        <span><i class="fas fa-check-circle text-success me-1"></i> <?php echo $graded_count; ?> Done</span>
                        <span><i class="fas fa-clock text-warning me-1"></i> <?php echo $pending; ?> Remaining</span>
                    </div>
                </div>

                <!-- ⚡ QUICK ACTIONS WIDGET -->
                <div class="content-box" style="background: linear-gradient(135deg, rgba(67, 56, 202, 0.03), rgba(59, 130, 246, 0.05)); border: 1px solid rgba(67, 56, 202, 0.1);">
                    <h5 class="box-title"><i class="fas fa-bolt text-warning me-2"></i> Quick Actions</h5>
                    
                    <div class="d-grid gap-3">
                        <button onclick="window.location.href='labmanual_list.php?status=Pending'" class="btn btn-primary fw-bold p-3 text-start d-flex justify-content-between align-items-center" style="border-radius: 10px; background: var(--primary); border: none; box-shadow: 0 4px 10px rgba(67, 56, 202, 0.3);">
                            <span><i class="fas fa-pen-nib me-2"></i> Grade Pending Manuals</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                        
                        <button onclick="window.location.href='reports.php'" class="btn btn-light fw-bold p-3 text-start d-flex justify-content-between align-items-center" style="border-radius: 10px; border: 1px solid #cbd5e1; color: var(--text-main);">
                            <span><i class="fas fa-download text-success me-2"></i> Download Final Report</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </button>
                    </div>
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
    </script>
</body>
</html>