<?php
session_start();
include '../db.php';

// 1. Check Login & Security
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$enrollment = $conn->real_escape_string((string)$_SESSION['user_id']);

// 2. Fetch Student Profile
$user_query = $conn->query("SELECT name, email, department, designation FROM users WHERE user_id = '$enrollment'");
$student_data = $user_query->fetch_assoc();

$student_name = $student_data['name'] ?? 'Student';
$student_email = $student_data['email'] ?? '';
$branch = trim($student_data['department'] ?? 'Computer Engineering');
$raw_semester = trim($student_data['designation'] ?? '1'); 

// 🔥 SMART MAPPING LOGIC
preg_match('/\d+/', $raw_semester, $sem_matches);
$sem_num = $sem_matches[0] ?? '1';
$mapped_semester = "Semester " . $sem_num; 

// Generate Initials for Profile Avatar
$name_parts = explode(' ', trim($student_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

// 3. Stats Calculation (From student_submissions table)
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM student_submissions 
    WHERE student_id = '$enrollment'
");
$stats = $stats_query->fetch_assoc();

$total_sub = $stats['total'] ?? 0;
$approved = $stats['approved'] ?? 0;
$pending = $stats['pending'] ?? 0;
$rejected = $stats['rejected'] ?? 0;

// 📊 4. ACADEMIC PROGRESS CALCULATION
$total_assigned_query = $conn->query("
    SELECT COUNT(*) as cnt FROM lab_manuals 
    WHERE department = '$branch' AND (semester = '$sem_num' OR semester = '$mapped_semester')
");
$total_assigned = $total_assigned_query ? $total_assigned_query->fetch_assoc()['cnt'] : 0;
$completion_percentage = ($total_assigned > 0) ? round(($total_sub / $total_assigned) * 100) : 0;
if($completion_percentage > 100) $completion_percentage = 100;

// SMART COLOR LOGIC FOR PROGRESS BAR
$progress_color_start = '#ef4444'; // Red
$progress_color_end = '#f87171';
$progress_shadow = 'rgba(239, 68, 68, 0.5)';

if($completion_percentage >= 75) {
    $progress_color_start = '#10b981'; // Green
    $progress_color_end = '#34d399';
    $progress_shadow = 'rgba(16, 185, 129, 0.5)';
} elseif ($completion_percentage >= 40) {
    $progress_color_start = '#f59e0b'; // Yellow
    $progress_color_end = '#fbbf24';
    $progress_shadow = 'rgba(245, 158, 11, 0.5)';
}

// 🎯 5. ACTION REQUIRED (Pending Tasks)
$todo_query = "
    SELECT * FROM lab_manuals 
    WHERE department = '$branch' AND (semester = '$sem_num' OR semester = '$mapped_semester')
    AND id NOT IN (
        SELECT manual_id 
        FROM student_submissions 
        WHERE student_id = '$enrollment'
    )
    ORDER BY id DESC LIMIT 5
";
$assigned_tasks = $conn->query($todo_query);

// 6. Recent Submissions 
$recent_query = "SELECT * FROM student_submissions WHERE student_id = '$enrollment' ORDER BY submitted_at DESC LIMIT 5";
$recent_subs = $conn->query($recent_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - KDP</title>
    
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
        .security-badge { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 10px; padding: 10px 18px; font-weight: 700; font-size: 13px; }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3); letter-spacing: 1px;}

        /* 🌟 PREMIUM WELCOME BANNER & PROGRESS BAR */
        .welcome-banner { background: linear-gradient(135deg, #4f46e5, #3b82f6); border-radius: var(--radius-xl); padding: 40px; color: white; position: relative; overflow: hidden; box-shadow: 0 15px 35px rgba(79, 70, 229, 0.25); margin-bottom: 30px; }
        .welcome-banner::before { content: ''; position: absolute; top: -50px; right: -50px; width: 250px; height: 250px; background: rgba(255,255,255,0.1); border-radius: 50%; }
        
        .progress-glass {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 14px;
            padding: 20px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-top: 20px;
        }

        .progress-container { background: rgba(0, 0, 0, 0.15); height: 12px; border-radius: 20px; overflow: hidden; margin-top: 8px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
        
        .progress-bar-custom { 
            height: 100%; 
            border-radius: 20px; 
            transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1); 
            position: relative;
            overflow: hidden;
        }
        
        /* SHIMMER GLOW ANIMATION ON PROGRESS BAR */
        .progress-bar-custom::after {
            content: "";
            position: absolute;
            top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }

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

        /* 📦 CONTENT BOXES */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 30px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        .content-box:hover { box-shadow: var(--shadow-float); }
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }

        /* TASK LIST */
        .task-item { background: #f8fafc; border-radius: 12px; padding: 18px; border: 1px solid #e2e8f0; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; transition: var(--transition-bounce); }
        .task-item:hover { background: #ffffff; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transform: translateX(5px); border-color: #cbd5e1; }
        .btn-upload { background: var(--primary); color: white; padding: 8px 20px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: var(--transition-bounce); }
        .btn-upload:hover { background: var(--primary-hover); color: white; transform: scale(1.05); }

        /* TABLE */
        .table-custom th { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; }
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; }
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-Pending { background: rgba(245,158,11,0.1); color: #d97706; }
        .status-Approved { background: rgba(16,185,129,0.1); color: #059669; }
        .status-Rejected { background: rgba(239,68,68,0.1); color: #dc2626; }
        .btn-view { background: rgba(16, 185, 129, 0.1); color: #059669; border: none; padding: 8px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; text-decoration: none; transition: var(--transition-bounce); }
        .btn-view:hover { background: #059669; color: white; transform: scale(1.05); }

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
            <div class="sidebar-subtitle">Student Portal</div>
        </div>
        <ul class="nav-links">
            <li class="active" onclick="window.location.href='Stdashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='my-manuals.php'"><i class="fas fa-book-open"></i> Course Manuals</li>
            <li onclick="window.location.href='history.php'"><i class="fas fa-history"></i> My Submissions</li>
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
                    <i class="fas fa-shield-check me-2"></i> Secure Session Verified
                </div>
            </div>
            
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Logged in as</span>
                    <h4 class="profile-name">
                        <?php 
                            echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : $student_name;
                        ?>
                    </h4>
                    <span style="font-size:11px; font-weight:700; color:var(--primary);"><?php echo htmlspecialchars($enrollment); ?></span>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </a>
        </div>

        <!-- 🌟 WELCOME BANNER WITH GLASSMORPHISM PROGRESS -->
        <div class="welcome-banner">
            <div class="row align-items-center relative" style="z-index: 2;">
                <div class="col-lg-8 col-md-12">
                    <h2 class="fw-bold mb-2" style="font-size: 32px;">Welcome back, <?php echo htmlspecialchars($name_parts[0]); ?>! 👋</h2>
                    <p class="mb-2" style="color: #e0e7ff; font-weight: 500; font-size: 15px;">You are currently enrolled in <strong><?php echo htmlspecialchars($mapped_semester); ?></strong> of <strong><?php echo htmlspecialchars($branch); ?></strong>.</p>
                    
                    <!-- ADVANCED PROGRESS BAR -->
                    <div class="progress-glass">
                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <div>
                                <span class="d-block small fw-bold text-uppercase mb-1" style="letter-spacing: 1px; color: #f8fafc;">Term-Work Completion</span>
                                <span class="small" style="color: #e2e8f0; font-weight: 600;"><i class="fas fa-tasks me-1 text-warning"></i> <?php echo $total_sub; ?> of <?php echo $total_assigned; ?> Manuals Submitted</span>
                            </div>
                            <h3 class="fw-bold mb-0" style="font-size: 34px; line-height: 1;"><?php echo $completion_percentage; ?>%</h3>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar-custom" 
                                 style="width: <?php echo $completion_percentage; ?>%; 
                                        background: linear-gradient(90deg, <?php echo $progress_color_start; ?>, <?php echo $progress_color_end; ?>);
                                        box-shadow: 0 0 12px <?php echo $progress_shadow; ?>;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-end d-none d-lg-block">
                    <i class="fas fa-user-graduate" style="font-size: 130px; opacity: 0.15; transform: rotate(-10deg);"></i>
                </div>
            </div>
        </div>

        <!-- 📊 STATS GRID -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card card-blue" onclick="window.location.href='history.php'">
                    <div class="stat-info"><h6>Total Uploads</h6><h2><?php echo $total_sub; ?></h2></div>
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-cloud-upload-alt"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-yellow" onclick="window.location.href='history.php'">
                    <div class="stat-info"><h6>Pending Review</h6><h2><?php echo $pending; ?></h2></div>
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-green" onclick="window.location.href='history.php'">
                    <div class="stat-info"><h6>Approved</h6><h2><?php echo $approved; ?></h2></div>
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-red" onclick="window.location.href='history.php'">
                    <div class="stat-info"><h6>Rejected</h6><h2><?php echo $rejected; ?></h2></div>
                    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- 📋 LEFT: PENDING TASKS -->
            <div class="col-md-7">
                <div class="content-box h-100">
                    <h5 class="box-title"><i class="fas fa-exclamation-circle text-warning me-2"></i> Action Required (Pending Practicals)</h5>
                    
                    <?php if(isset($assigned_tasks) && $assigned_tasks && $assigned_tasks->num_rows > 0): ?>
                        <?php while($task = $assigned_tasks->fetch_assoc()): 
                            $deadline_text = 'No Deadline';
                            $deadline_color = 'text-muted';
                            if (!empty($task['end_date']) && $task['end_date'] != '0000-00-00') {
                                $deadline_date = strtotime($task['end_date']);
                                $today = time();
                                $diff_days = round(($deadline_date - $today) / (60 * 60 * 24));
                                $deadline_text = date('d M Y', $deadline_date);
                                if ($diff_days < 0) { $deadline_color = 'text-danger'; $deadline_text .= ' (Overdue)'; }
                                elseif ($diff_days <= 2) { $deadline_color = 'text-danger'; $deadline_text .= ' (Ending Soon)'; }
                                else { $deadline_color = 'text-success'; }
                            }
                        ?>
                            <div class="task-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width: 45px; height: 45px; background: #e0e7ff; color: #4f46e5; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 800;">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1" style="font-size: 15px;"><?php echo htmlspecialchars($task['title']); ?></h6>
                                        <p class="text-muted small fw-semibold mb-0">
                                            <?php echo htmlspecialchars($task['subject_name']); ?> • Due: <strong class="<?php echo $deadline_color; ?>"><?php echo $deadline_text; ?></strong>
                                        </p>
                                    </div>
                                </div>
                                <a href="upload_manual.php?manual_id=<?php echo $task['id']; ?>&subject=<?php echo urlencode($task['subject_name']); ?>" class="btn-upload shadow-sm">
                                    <i class="fas fa-upload me-1"></i> Submit
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle text-success mb-3" style="font-size: 50px; opacity: 0.8;"></i>
                            <h5 class="fw-bold text-dark mb-1">All Caught Up!</h5>
                            <p class="text-muted fw-semibold mb-0">You have no pending lab manuals to submit right now.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 🕒 RIGHT: RECENT & QUICK LINKS -->
            <div class="col-md-5">
                <!-- Recent Submissions -->
                <div class="content-box mb-4">
                    <h5 class="box-title"><i class="fas fa-history text-primary me-2"></i> Recent Submissions</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Subject & Title</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($recent_subs && $recent_subs->num_rows > 0): ?>
                                    <?php while($sub = $recent_subs->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight:700; color:#0f172a; font-size:13.5px; margin-bottom:2px;">
                                                    <?php echo htmlspecialchars($sub['subject_name'] ?? ''); ?>
                                                </div>
                                                <small class="text-primary fw-bold" style="font-size: 11px;">Manual</small>
                                            </td>
                                            <td>
                                                <span class="badge-modern status-<?php echo $sub['status']; ?>"><?php echo $sub['status']; ?></span>
                                            </td>
                                            <td class="text-end">
                                                <?php 
                                                    $raw_path = $sub['file_path'];
                                                    $pos = strpos($raw_path, 'uploads/');
                                                    $safe_pdf_path = ($pos !== false) ? '../' . substr($raw_path, $pos) : '../' . $raw_path;
                                                ?>
                                                <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" target="_blank" class="btn-view" title="View PDF">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">
                                            <small class="fw-semibold">No recent submissions found.</small>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                

                <!-- Quick Actions / Announcements -->
                <div class="content-box" style="background: linear-gradient(135deg, rgba(67, 56, 202, 0.03), rgba(59, 130, 246, 0.05)); border: 1px solid rgba(67, 56, 202, 0.1);">
                    <h5 class="box-title"><i class="fas fa-bolt text-warning me-2"></i> Quick Links</h5>
                    <div class="d-grid gap-2">
                        <button onclick="window.open('https://www.kdppatan.ac.in/', '_blank')" class="btn btn-light border fw-bold text-start p-3 d-flex justify-content-between align-items-center" style="border-radius: 10px; transition: 0.2s;" onmouseover="this.style.background='#ffffff'; this.style.transform='translateX(5px)';" onmouseout="this.style.background=''; this.style.transform='translateX(0)';">
                            <span><i class="fas fa-globe text-primary me-2"></i> KDP Official Website</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </button>
                        <button class="btn btn-light border fw-bold text-start p-3 d-flex justify-content-between align-items-center" style="border-radius: 10px; transition: 0.2s;" onmouseover="this.style.background='#ffffff'; this.style.transform='translateX(5px)';" onmouseout="this.style.background=''; this.style.transform='translateX(0)';">
                            <span><i class="fas fa-calendar-alt text-success me-2"></i> Academic Calendar</span>
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









