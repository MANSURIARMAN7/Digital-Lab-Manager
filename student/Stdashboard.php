<?php
session_start();
include '../db.php';

// 1. Check Login
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$enrollment = $conn->real_escape_string((string)$_SESSION['user_id']);

// 2. Fetch Student Profile
$user_query = $conn->query("SELECT name, department, designation FROM users WHERE user_id = '$enrollment'");
$student_data = $user_query->fetch_assoc();

$student_name = $student_data['name'] ?? 'Student';
$branch = trim($student_data['department'] ?? 'Computer Engineering');
$semester = trim($student_data['designation'] ?? '1');

// 🔥 SMART MAPPING LOGIC (Admin '1' or 'CE' dale toh bhi match ho jayega)
preg_match('/\d+/', $semester, $sem_matches);
$sem_num = $sem_matches[0] ?? $semester;

$branch_parts = explode(' ', $branch);
$branch_keyword = $conn->real_escape_string($branch_parts[0]); 

// Generate Initials for Profile Avatar
$name_parts = explode(' ', trim($student_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

// 3. Stats Calculation
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

// 4. Action Required (Pending Tasks) - Hides already submitted manuals!
$todo_query = "
    SELECT * FROM lab_manuals 
    WHERE (branch LIKE '%$branch_keyword%' OR branch = 'All' OR branch = '$branch') 
    AND (semester = '$sem_num' OR semester = '$semester' OR semester = 'All') 
    AND CONCAT(subject_name, '-', practical_no) NOT IN (
        SELECT CONCAT(subject_name, '-', practical_no) 
        FROM student_submissions 
        WHERE student_id = '$enrollment'
    )
    ORDER BY id DESC LIMIT 10
";
$assigned_tasks = $conn->query($todo_query);

// 5. Recent Submissions 
$recent_query = "SELECT * FROM student_submissions WHERE student_id = '$enrollment' ORDER BY submitted_at DESC LIMIT 5";
$recent_subs = $conn->query($recent_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - K.D. Polytechnic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-width: 260px; --bg-color: #f4f7f6; --sidebar-bg: #1b365d; --primary-blue: #3b82f6; }
        body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        
        .sidebar { width: var(--sidebar-width); background-color: var(--sidebar-bg); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar-header { padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 15px; }
        .sidebar-header img { width: 80px; height: 80px; object-fit: contain; margin-bottom: 15px; background: white; border-radius: 50%; padding: 5px; }
        .sidebar-title { font-size: 18px; font-weight: 700; margin: 0 0 5px 0; letter-spacing: 0.5px; }
        .sidebar-subtitle { font-size: 13px; color: #94a3b8; font-weight: 500; }
        
        .nav-links { list-style: none; padding: 0 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 14px 20px; margin: 5px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 500; color: #cbd5e1; transition: 0.2s; }
        .nav-links li:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-links li.active { background-color: var(--primary-blue); color: white; box-shadow: 0 4px 10px rgba(59,130,246,0.3); }

        .main { flex: 1; overflow-y: auto; padding: 30px 40px; }
        .top-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 35px; }
        .header-title h2 { font-size: 26px; font-weight: 700; color: #0f172a; margin: 0 0 5px 0; }
        .header-title p { font-size: 14px; color: #64748b; margin: 0; }
        
        .profile-pill { background: white; border-radius: 50px; padding: 6px 8px 6px 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }
        .profile-text { text-align: right; line-height: 1.2; }
        .profile-text .welcome { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
        .profile-text .name { font-size: 14px; font-weight: 700; color: #0f172a; margin: 3px 0; }
        .profile-text .enrollment { font-size: 11.5px; font-weight: 600; color: var(--primary-blue); }
        .profile-avatar { width: 42px; height: 42px; background-color: var(--primary-blue); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; }

        .context-box { background: white; border-radius: 12px; padding: 15px 25px; margin-bottom: 30px; display: flex; gap: 40px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .context-item { display: flex; flex-direction: column; }
        .context-item span { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 5px; }
        .context-item strong { font-size: 14px; font-weight: 600; color: #334155; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 35px; }
        .stat-card { background: white; border-radius: 12px; padding: 25px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 8px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; position: relative; cursor: pointer; transition: 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }
        .stat-card::before { content: ''; position: absolute; left: 0; top: 15px; bottom: 15px; width: 4px; border-radius: 0 4px 4px 0; }
        .card-blue::before { background-color: #3b82f6; }
        .card-yellow::before { background-color: #f59e0b; }
        .card-green::before { background-color: #10b981; }
        .card-red::before { background-color: #ef4444; }
        .stat-info h6 { font-size: 13.5px; font-weight: 600; color: #64748b; margin: 0 0 5px 0; }
        .stat-info h2 { font-size: 28px; font-weight: 700; color: #0f172a; margin: 0; line-height: 1; }
        .stat-icon { font-size: 32px; color: #e2e8f0; }

        .row-panels { display: grid; grid-template-columns: 5fr 5fr; gap: 25px; }
        .panel { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; }
        .panel-title { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }

        .task-list { display: flex; flex-direction: column; gap: 12px; }
        .task-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; }
        .task-item h6 { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0 0 4px 0; }
        .task-item p { font-size: 12px; color: #64748b; margin: 0; }
        .btn-upload { background: white; color: #3b82f6; border: 1px solid #3b82f6; padding: 6px 15px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; transition: 0.2s; }
        .btn-upload:hover { background: #3b82f6; color: white; }
        .btn-view { background: white; color: #10b981; border: 1px solid #10b981; padding: 5px 12px; border-radius: 6px; font-size: 11.5px; font-weight: 600; text-decoration: none;}
        .btn-view:hover { background: #10b981; color: white; }

        .table-custom th { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
        .table-custom td { font-size: 13px; color: #334155; padding: 12px 0; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        .badge-status { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .status-Pending { background: rgba(245,158,11,0.1); color: #d97706; }
        .status-Approved { background: rgba(16,185,129,0.1); color: #059669; }
        .status-Rejected { background: rgba(239,68,68,0.1); color: #dc2626; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/college-logo.png" alt="KDP Logo"> 
            <h2 class="sidebar-title">K.D. Polytechnic</h2>
            <div class="sidebar-subtitle">Student Portal</div>
        </div>
        <ul class="nav-links">
            <li class="active" onclick="window.location.href='Stdashboard.php'"><i class="fas fa-home" style="width: 20px;"></i> Dashboard</li>
            <li onclick="window.location.href='my-manuals.php'"><i class="fas fa-book" style="width: 20px;"></i> My Submissions</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle" style="width: 20px;"></i> Profile</li>
            <li onclick="window.location.href='history.php'"><i class="fas fa-history" style="width: 20px;"></i> History</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #fca5a5;"><i class="fas fa-sign-out-alt" style="width: 20px;"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="top-header">
            <div class="header-title">
                <h2>Student Dashboard</h2>
                <p>Manage your practical submissions and track progress.</p>
            </div>
            
            <div class="profile-pill">
                <div class="profile-text">
                    <div class="welcome">WELCOME BACK,</div>
                    <div class="name"><?php echo htmlspecialchars($student_name); ?></div>
                    <div class="enrollment"><?php echo htmlspecialchars($enrollment); ?></div>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </div>
        </div>

        <div class="context-box">
            <div class="context-item">
                <span><i class="fas fa-code-branch me-1"></i> Current Branch</span>
                <strong><?php echo htmlspecialchars($branch); ?></strong>
            </div>
            <div class="context-item">
                <span><i class="fas fa-layer-group me-1"></i> Semester</span>
                <strong><?php echo htmlspecialchars($semester); ?></strong>
            </div>
            <div class="context-item">
                <span><i class="fas fa-calendar-alt me-1"></i> Academic Year</span>
                <strong>2025-26</strong>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card card-blue" onclick="window.location.href='my-manuals.php'">
                <div class="stat-info"><h6>Total Submissions</h6><h2><?php echo $total_sub; ?></h2></div>
                <div class="stat-icon"><i class="fas fa-file-pdf"></i></div>
            </div>
            <div class="stat-card card-yellow" onclick="window.location.href='my-manuals.php'">
                <div class="stat-info"><h6>Pending Review</h6><h2><?php echo $pending; ?></h2></div>
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-card card-green" onclick="window.location.href='my-manuals.php'">
                <div class="stat-info"><h6>Approved</h6><h2><?php echo $approved; ?></h2></div>
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            </div>
            <div class="stat-card card-red" onclick="window.location.href='my-manuals.php'">
                <div class="stat-info"><h6>Rejected</h6><h2><?php echo $rejected; ?></h2></div>
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            </div>
        </div>

        <div class="row-panels">
            <div class="panel">
                <div class="panel-title">Action Required (Pending Practical)</div>
                <div class="task-list">
                    <?php if(isset($assigned_tasks) && $assigned_tasks && $assigned_tasks->num_rows > 0): ?>
                        <?php while($task = $assigned_tasks->fetch_assoc()): ?>
                            <div class="task-item">
                                <div>
                                    <h6><?php echo htmlspecialchars($task['practical_no']); ?>: <?php echo htmlspecialchars($task['title']); ?></h6>
                                    <p><?php echo htmlspecialchars($task['subject_name']); ?> • Due: <strong class="text-danger"><?php echo !empty($task['end_date']) && $task['end_date'] != '0000-00-00' ? date('d M', strtotime($task['end_date'])) : 'No Deadline'; ?></strong></p>
                                </div>
                                <a href="upload_manual.php?subject=<?php echo urlencode($task['subject_name']); ?>&prac=<?php echo urlencode($task['practical_no']); ?>" class="btn-upload">Submit</a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle text-success mb-3" style="font-size: 38px; opacity: 0.8;"></i>
                            <h6 class="fw-bold text-dark mb-1">All Caught Up!</h6>
                            <p class="text-muted small mb-0">You have no pending assignments right now.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel">
                <div class="panel-title">Recent Submissions</div>
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Practical Info</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent_subs && $recent_subs->num_rows > 0): ?>
                                <?php while($sub = $recent_subs->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight:600; color:#0f172a; margin-bottom:2px;">
                                                <?php echo htmlspecialchars($sub['practical_no'] ?? 'Manual Submission'); ?>
                                            </div>
                                            <small class="text-muted" style="font-size: 11px;"><?php echo htmlspecialchars($sub['subject_name'] ?? ''); ?></small>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($sub['submitted_at'])); ?></td>
                                        <td>
                                            <span class="badge-status status-<?php echo $sub['status']; ?>"><?php echo $sub['status']; ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?php echo htmlspecialchars($sub['file_path']); ?>" target="_blank" class="btn-view" title="View PDF">
                                                <i class="fas fa-eye me-1"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-5">
                                        <i class="fas fa-folder-open mb-2" style="font-size: 28px; opacity: 0.3;"></i><br>
                                        <span class="small">No recent submissions found.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>










// =====================