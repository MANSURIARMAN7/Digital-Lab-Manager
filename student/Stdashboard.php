<?php
/**
 * Student Dashboard (Enhanced)
 * -----------------------------
 * A comprehensive dashboard showing stats, pending tasks, recent submissions,
 * upcoming deadlines, and quick actions.
 *
 * Security: Uses prepared statements throughout.
 * UX: Pagination, search/filter for tasks, detailed stats, and interactive widgets.
 */

session_start();
include '../db.php';

// -----------------------------------------------------------------------------
// 1. SECURE LOGIN CHECK
// -----------------------------------------------------------------------------
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
$enrollment = (string) $_SESSION['user_id'];

// -----------------------------------------------------------------------------
// 2. FETCH STUDENT PROFILE
// -----------------------------------------------------------------------------
$user_stmt = $conn->prepare("SELECT name, email, department, designation FROM users WHERE user_id = ?");
$user_stmt->bind_param("s", $enrollment);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$student_data = $user_result->fetch_assoc();
$user_stmt->close();

$student_name = $student_data['name'] ?? 'Student';
$student_email = $student_data['email'] ?? '';
$branch = trim($student_data['department'] ?? 'Computer Engineering');
$raw_semester = trim($student_data['designation'] ?? '1');

// Extract numeric semester
preg_match('/\d+/', $raw_semester, $sem_matches);
$sem_num = $sem_matches[0] ?? '1';
$mapped_semester = "Semester " . $sem_num;

// Generate initials for avatar
$name_parts = explode(' ', trim($student_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

// -----------------------------------------------------------------------------
// 3. STATISTICS (using prepared statements)
// -----------------------------------------------------------------------------
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
                AVG(CASE WHEN status = 'Approved' AND marks IS NOT NULL THEN marks ELSE NULL END) as avg_marks
              FROM student_submissions 
              WHERE student_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("s", $enrollment);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

$total_sub   = $stats['total'] ?? 0;
$approved    = $stats['approved'] ?? 0;
$pending     = $stats['pending'] ?? 0;
$rejected    = $stats['rejected'] ?? 0;
$avg_marks   = $stats['avg_marks'] ? number_format($stats['avg_marks'], 1) : 'N/A';

// -----------------------------------------------------------------------------
// 4. TOTAL ASSIGNED MANUALS (for progress calculation)
// -----------------------------------------------------------------------------
$assigned_sql = "SELECT COUNT(*) as cnt FROM lab_manuals 
                 WHERE (department = ? OR department = 'All')
                   AND (semester = ? OR semester = ? OR semester = 'All')";
$assigned_stmt = $conn->prepare($assigned_sql);
$assigned_stmt->bind_param("sss", $branch, $sem_num, $mapped_semester);
$assigned_stmt->execute();
$assigned_result = $assigned_stmt->get_result();
$total_assigned = $assigned_result->fetch_assoc()['cnt'] ?? 0;
$assigned_stmt->close();

$completion_percentage = ($total_assigned > 0) ? round(($total_sub / $total_assigned) * 100) : 0;
if ($completion_percentage > 100) $completion_percentage = 100;

// Progress bar color logic
$progress_color_start = '#ef4444';
$progress_color_end = '#f87171';
$progress_shadow = 'rgba(239, 68, 68, 0.5)';
if ($completion_percentage >= 75) {
    $progress_color_start = '#10b981';
    $progress_color_end = '#34d399';
    $progress_shadow = 'rgba(16, 185, 129, 0.5)';
} elseif ($completion_percentage >= 40) {
    $progress_color_start = '#f59e0b';
    $progress_color_end = '#fbbf24';
    $progress_shadow = 'rgba(245, 158, 11, 0.5)';
}

// -----------------------------------------------------------------------------
// 5. PENDING TASKS (Action Required) with Pagination & Search
// -----------------------------------------------------------------------------
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$subject_filter = isset($_GET['subject']) ? trim($_GET['subject']) : '';

// Build the query for pending tasks (manuals not yet submitted)
$todo_sql = "SELECT * FROM lab_manuals 
             WHERE (department = ? OR department = 'All')
               AND (semester = ? OR semester = ? OR semester = 'All')
               AND id NOT IN (
                   SELECT manual_id 
                   FROM student_submissions 
                   WHERE student_id = ?
               )";
$params = [$branch, $sem_num, $mapped_semester, $enrollment];
$types = "ssss";

if (!empty($search_term)) {
    $todo_sql .= " AND (title LIKE ? OR subject_name LIKE ?)";
    $like = "%$search_term%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}
if (!empty($subject_filter)) {
    $todo_sql .= " AND subject_name = ?";
    $params[] = $subject_filter;
    $types .= "s";
}
$todo_sql .= " ORDER BY id DESC";

// Count total pending tasks
$count_sql = str_replace("SELECT *", "SELECT COUNT(*) AS total", $todo_sql);
$count_stmt = $conn->prepare($count_sql);
if ($count_stmt) {
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_pending = $count_result->fetch_assoc()['total'] ?? 0;
    $count_stmt->close();
} else {
    $total_pending = 0;
}
$total_pages = ceil($total_pending / $limit);

// Fetch paginated pending tasks
$todo_sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$todo_stmt = $conn->prepare($todo_sql);
if ($todo_stmt) {
    $todo_stmt->bind_param($types, ...$params);
    $todo_stmt->execute();
    $assigned_tasks = $todo_stmt->get_result();
    $todo_stmt->close();
} else {
    $assigned_tasks = false;
}

// -----------------------------------------------------------------------------
// 6. RECENT SUBMISSIONS (last 5)
// -----------------------------------------------------------------------------
$recent_sql = "SELECT * FROM student_submissions 
               WHERE student_id = ? 
               ORDER BY submitted_at DESC LIMIT 5";
$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param("s", $enrollment);
$recent_stmt->execute();
$recent_subs = $recent_stmt->get_result();
$recent_stmt->close();

// -----------------------------------------------------------------------------
// 7. UPCOMING DEADLINES (from lab_manuals)
// -----------------------------------------------------------------------------
$today = date('Y-m-d');
$deadline_sql = "SELECT subject_name, title, end_date FROM lab_manuals 
                 WHERE (department = ? OR department = 'All')
                   AND (semester = ? OR semester = ? OR semester = 'All')
                   AND end_date >= ? AND end_date != '0000-00-00'
                 ORDER BY end_date ASC LIMIT 4";
$deadline_stmt = $conn->prepare($deadline_sql);
$deadline_stmt->bind_param("ssss", $branch, $sem_num, $mapped_semester, $today);
$deadline_stmt->execute();
$upcoming_deadlines = $deadline_stmt->get_result();
$deadline_stmt->close();

// -----------------------------------------------------------------------------
// 8. FETCH DISTINCT SUBJECTS FOR FILTER DROPDOWN (from pending tasks)
// -----------------------------------------------------------------------------
$subj_sql = "SELECT DISTINCT subject_name FROM lab_manuals 
             WHERE (department = ? OR department = 'All')
               AND (semester = ? OR semester = ? OR semester = 'All')
             ORDER BY subject_name";
$subj_stmt = $conn->prepare($subj_sql);
$subj_stmt->bind_param("sss", $branch, $sem_num, $mapped_semester);
$subj_stmt->execute();
$subj_result = $subj_stmt->get_result();
$subject_list = [];
while ($row = $subj_result->fetch_assoc()) {
    $subject_list[] = $row['subject_name'];
}
$subj_stmt->close();

// -----------------------------------------------------------------------------
// 9. HELPER: Active filter class
// -----------------------------------------------------------------------------
function isActive($value, $current) {
    return ($value === $current) ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard – Enhanced | KDP</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           PREMIUM STYLING (extended)
           ============================================================ */
        :root {
            --sidebar-width: 270px;
            --primary: #4338ca;
            --primary-hover: #3730a3;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --shadow-float: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.01);
            --radius-xl: 16px;
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            height: 100vh;
            overflow: hidden;
            margin: 0;
            color: var(--text-main);
        }

        /* SIDEBAR (unchanged) */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            z-index: 10;
            overflow-y: auto;
            box-shadow: 4px 0 24px rgba(0,0,0,0.08);
        }
        .sidebar-logo-container {
            padding: 35px 20px 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }
        .sidebar-logo-container img {
            width: 85px;
            height: 85px;
            margin-bottom: 15px;
            border-radius: 50%;
            padding: 4px;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.4);
        }
        .sidebar-title h2 {
            font-size: 19px;
            font-weight: 800;
            margin: 0;
            letter-spacing: 0.5px;
            color: #ffffff;
        }
        .sidebar-subtitle {
            font-size: 12px;
            color: #bfdbfe;
            margin-top: 5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .nav-links {
            list-style: none;
            padding: 25px 15px;
            margin: 0;
            flex-grow: 1;
        }
        .nav-links li {
            padding: 13px 20px;
            margin: 8px 0;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 14.5px;
            font-weight: 600;
            color: #dbeafe;
            transition: var(--transition-bounce);
            border-left: 3px solid transparent;
        }
        .nav-links li:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .nav-links li.active {
            background: rgba(255,255,255,0.2);
            color: #ffffff;
            border-left: 4px solid #ffffff;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .nav-links li i {
            font-size: 18px;
        }
        .nav-links li.mt-auto {
            color: #fca5a5 !important;
        }

        .main {
            flex: 1;
            padding: 30px 45px;
            overflow-y: auto;
            height: 100vh;
            animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes fadeUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .topbar {
            padding: 0 0 15px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .clock-badge {
            background: var(--surface);
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 18px;
            color: #475569;
            font-weight: 700;
            font-size: 13px;
            box-shadow: var(--shadow-float);
        }
        .security-badge {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 700;
            font-size: 13px;
        }
        .profile-pill {
            display: flex;
            align-items: center;
            background-color: var(--surface);
            padding: 8px 18px 8px 24px;
            border-radius: 50px;
            border: 1px solid rgba(226,232,240,0.8);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            transition: var(--transition-bounce);
            box-shadow: var(--shadow-float);
        }
        .profile-pill:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1);
            border-color: #cbd5e1;
        }
        .profile-text {
            text-align: right;
            margin-right: 18px;
        }
        .profile-welcome {
            display: block;
            font-size: 10px;
            color: var(--primary);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .profile-name {
            margin: 0;
            font-size: 15px;
            color: var(--text-main);
            font-weight: 800;
        }
        .profile-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #4f46e5, #3730a3);
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(79,70,229,0.3);
            letter-spacing: 1px;
        }

        /* WELCOME BANNER */
        .welcome-banner {
            background: linear-gradient(135deg, #4f46e5, #3b82f6);
            border-radius: var(--radius-xl);
            padding: 40px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(79,70,229,0.25);
            margin-bottom: 30px;
        }
        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 250px;
            height: 250px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .progress-glass {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 14px;
            padding: 20px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            margin-top: 20px;
        }
        .progress-container {
            background: rgba(0,0,0,0.15);
            height: 12px;
            border-radius: 20px;
            overflow: hidden;
            margin-top: 8px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }
        .progress-bar-custom {
            height: 100%;
            border-radius: 20px;
            transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .progress-bar-custom::after {
            content: "";
            position: absolute;
            top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* STAT CARDS */
        .stat-card {
            background: var(--surface);
            border-radius: 16px;
            padding: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(226,232,240,0.8);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            transition: var(--transition-bounce);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-float);
            border-color: #cbd5e1;
        }
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }
        .card-blue::after { background: #3b82f6; }
        .card-yellow::after { background: #f59e0b; }
        .card-green::after { background: #10b981; }
        .card-red::after { background: #ef4444; }
        .stat-info h6 {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 8px 0;
        }
        .stat-info h2 {
            font-size: 32px;
            font-weight: 800;
            color: var(--text-main);
            margin: 0;
        }
        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        /* CONTENT BOXES */
        .content-box {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: 30px;
            border: 1px solid rgba(226,232,240,0.8);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            transition: var(--transition-bounce);
            margin-bottom: 25px;
        }
        .content-box:hover {
            box-shadow: var(--shadow-float);
        }
        .box-title {
            font-size: 17px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
        }

        /* TASK ITEMS */
        .task-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 18px;
            border: 1px solid #e2e8f0;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition-bounce);
        }
        .task-item:hover {
            background: #ffffff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transform: translateX(5px);
            border-color: #cbd5e1;
        }
        .btn-upload {
            background: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition-bounce);
        }
        .btn-upload:hover {
            background: var(--primary-hover);
            color: white;
            transform: scale(1.05);
        }

        /* TABLE */
        .table-custom th {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            border-bottom: 2px solid #e2e8f0;
            padding: 15px 10px;
        }
        .table-custom td {
            vertical-align: middle;
            font-size: 14px;
            font-weight: 600;
            padding: 15px 10px;
            color: var(--text-main);
            border-bottom: 1px solid #f1f5f9;
        }
        .badge-modern {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-Pending { background: rgba(245,158,11,0.1); color: #d97706; }
        .status-Approved { background: rgba(16,185,129,0.1); color: #059669; }
        .status-Rejected { background: rgba(239,68,68,0.1); color: #dc2626; }
        .btn-view {
            background: rgba(16,185,129,0.1);
            color: #059669;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition-bounce);
        }
        .btn-view:hover {
            background: #059669;
            color: white;
            transform: scale(1.05);
        }

        /* PAGINATION */
        .pagination .page-link {
            color: var(--primary);
            font-weight: 600;
        }
        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        /* SEARCH & FILTER BAR */
        .search-filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }
        .search-filter-bar .form-control,
        .search-filter-bar .form-select {
            border-radius: 30px;
            border: 1px solid #cbd5e1;
            font-weight: 500;
            padding: 8px 18px;
            background: #f8fafc;
        }
        .search-filter-bar .form-control:focus,
        .search-filter-bar .form-select:focus {
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: none;
        }
        .search-filter-bar .btn-outline-primary {
            border-radius: 30px;
            font-weight: 700;
        }
        .search-filter-bar .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
        }

        /* SCROLLBAR */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>
</head>
<body>

    <!-- ============================================================
    SIDEBAR (unchanged)
    ============================================================ -->
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

    <!-- ============================================================
    MAIN CONTENT
    ============================================================ -->
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

        <!-- ============================================================
        WELCOME BANNER WITH PROGRESS
        ============================================================ -->
        <div class="welcome-banner">
            <div class="row align-items-center relative" style="z-index: 2;">
                <div class="col-lg-8 col-md-12">
                    <h2 class="fw-bold mb-2" style="font-size: 32px;">Welcome back, <?php echo htmlspecialchars($name_parts[0]); ?>! 👋</h2>
                    <p class="mb-2" style="color: #e0e7ff; font-weight: 500; font-size: 15px;">
                        You are currently enrolled in <strong><?php echo htmlspecialchars($mapped_semester); ?></strong> of <strong><?php echo htmlspecialchars($branch); ?></strong>.
                    </p>
                    <div class="progress-glass">
                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <div>
                                <span class="d-block small fw-bold text-uppercase mb-1" style="letter-spacing: 1px; color: #f8fafc;">Term-Work Completion</span>
                                <span class="small" style="color: #e2e8f0; font-weight: 600;">
                                    <i class="fas fa-tasks me-1 text-warning"></i> <?php echo $total_sub; ?> of <?php echo $total_assigned; ?> Manuals Submitted
                                </span>
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

        <!-- ============================================================
        STATS GRID (clickable)
        ============================================================ -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card card-blue" onclick="window.location.href='history.php'">
                    <div class="stat-info"><h6>Total Uploads</h6><h2><?php echo $total_sub; ?></h2></div>
                    <div class="stat-icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;"><i class="fas fa-cloud-upload-alt"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-yellow" onclick="window.location.href='history.php?status=Pending'">
                    <div class="stat-info"><h6>Pending Review</h6><h2><?php echo $pending; ?></h2></div>
                    <div class="stat-icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-green" onclick="window.location.href='history.php?status=Approved'">
                    <div class="stat-info"><h6>Approved</h6><h2><?php echo $approved; ?></h2></div>
                    <div class="stat-icon" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-red" onclick="window.location.href='history.php?status=Rejected'">
                    <div class="stat-info"><h6>Rejected</h6><h2><?php echo $rejected; ?></h2></div>
                    <div class="stat-icon" style="background: rgba(239,68,68,0.1); color: #ef4444;"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
        </div>

        <!-- ============================================================
        TWO COLUMN LAYOUT
        ============================================================ -->
        <div class="row g-4">

            <!-- LEFT: PENDING TASKS (with Search & Pagination) -->
            <div class="col-lg-7">
                <div class="content-box h-100">
                    <h5 class="box-title"><i class="fas fa-exclamation-circle text-warning me-2"></i> Action Required (Pending Practicals)</h5>

                    <!-- Search & Filter Bar -->
                    <div class="search-filter-bar">
                        <form method="GET" action="" class="d-flex flex-wrap gap-2 w-100">
                            <input type="hidden" name="page" value="1">
                            <div class="flex-grow-1" style="min-width: 160px;">
                                <input type="text" name="search" class="form-control form-control-sm"
                                       placeholder="🔍 Search by title/subject..."
                                       value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                            <div style="min-width: 140px;">
                                <select name="subject" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="">All Subjects</option>
                                    <?php foreach ($subject_list as $sub): ?>
                                        <option value="<?php echo htmlspecialchars($sub); ?>" <?php echo ($subject_filter == $sub) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sub); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm" style="border-radius:30px; font-weight:700;">
                                <i class="fas fa-filter"></i> Apply
                            </button>
                            <a href="Stdashboard.php" class="btn btn-outline-secondary btn-sm" style="border-radius:30px; font-weight:700;">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        </form>
                    </div>

                    <!-- Task List -->
                    <?php if ($assigned_tasks && $assigned_tasks->num_rows > 0): ?>
                        <?php while ($task = $assigned_tasks->fetch_assoc()):
                            $deadline_text = 'No Deadline';
                            $deadline_color = 'text-muted';
                            if (!empty($task['end_date']) && $task['end_date'] != '0000-00-00') {
                                $deadline_ts = strtotime($task['end_date']);
                                $today_ts = time();
                                $diff_days = round(($deadline_ts - $today_ts) / (60 * 60 * 24));
                                $deadline_text = date('d M Y', $deadline_ts);
                                if ($diff_days < 0) {
                                    $deadline_color = 'text-danger';
                                    $deadline_text .= ' (Overdue)';
                                } elseif ($diff_days <= 2) {
                                    $deadline_color = 'text-danger';
                                    $deadline_text .= ' (Ending Soon)';
                                } else {
                                    $deadline_color = 'text-success';
                                }
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

                        <!-- Pagination for Pending Tasks -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_term); ?>&subject=<?php echo urlencode($subject_filter); ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_term); ?>&subject=<?php echo urlencode($subject_filter); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_term); ?>&subject=<?php echo urlencode($subject_filter); ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle text-success mb-3" style="font-size: 50px; opacity: 0.8;"></i>
                            <h5 class="fw-bold text-dark mb-1">All Caught Up!</h5>
                            <p class="text-muted fw-semibold mb-0">
                                <?php if (!empty($search_term) || !empty($subject_filter)): ?>
                                    No pending tasks match your filters. Try resetting.
                                <?php else: ?>
                                    You have no pending lab manuals to submit right now.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT: RECENT SUBMISSIONS + DEADLINES + QUICK LINKS -->
            <div class="col-lg-5">

                <!-- Recent Submissions (with feedback) -->
                <div class="content-box mb-4">
                    <h5 class="box-title"><i class="fas fa-history text-primary me-2"></i> Recent Submissions</h5>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_subs && $recent_subs->num_rows > 0): ?>
                                    <?php while ($sub = $recent_subs->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight:700; color:#0f172a; font-size:13.5px; margin-bottom:2px;">
                                                    <?php echo htmlspecialchars($sub['subject_name'] ?? ''); ?>
                                                </div>
                                                <small class="text-muted" style="font-size: 11px;">
                                                    <?php echo htmlspecialchars($sub['practical_no']); ?>
                                                    <?php if (!empty($sub['remarks'])): ?>
                                                        <i class="fas fa-comment-dots ms-1" title="<?php echo htmlspecialchars($sub['remarks']); ?>"></i>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge-modern status-<?php echo $sub['status']; ?>"><?php echo $sub['status']; ?></span>
                                                <?php if ($sub['status'] == 'Approved' && !empty($sub['marks'])): ?>
                                                    <span class="badge bg-success fw-bold ms-1" style="font-size:10px;"><?php echo $sub['marks']; ?>/20</span>
                                                <?php endif; ?>
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

                <!-- Upcoming Deadlines Widget -->
                <div class="content-box mb-4">
                    <h5 class="box-title"><i class="fas fa-clock text-warning me-2"></i> Upcoming Deadlines</h5>
                    <?php if ($upcoming_deadlines && $upcoming_deadlines->num_rows > 0): ?>
                        <?php while ($dl = $upcoming_deadlines->fetch_assoc()):
                            $dl_ts = strtotime($dl['end_date']);
                            $days_left = round(($dl_ts - time()) / (60 * 60 * 24));
                            $alert_class = ($days_left <= 2) ? 'text-danger' : 'text-success';
                        ?>
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <h6 class="fw-bold text-dark mb-0" style="font-size: 14px;"><?php echo htmlspecialchars($dl['subject_name']); ?></h6>
                                    <small class="text-muted fw-semibold"><?php echo htmlspecialchars($dl['title']); ?></small>
                                </div>
                                <span class="badge bg-light border <?php echo $alert_class; ?> fw-bold shadow-sm">
                                    <?php echo date('d M', $dl_ts); ?>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-calendar-check text-success mb-2" style="font-size: 30px; opacity: 0.7;"></i>
                            <p class="text-muted small fw-semibold mb-0">No upcoming deadlines.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Links / Announcements -->
                <div class="content-box" style="background: linear-gradient(135deg, rgba(67,56,202,0.03), rgba(59,130,246,0.05)); border: 1px solid rgba(67,56,202,0.1);">
                    <h5 class="box-title"><i class="fas fa-bolt text-warning me-2"></i> Quick Links</h5>
                    <div class="d-grid gap-2">
                        <a href="https://www.kdppatan.ac.in/" target="_blank" class="btn btn-light border fw-bold text-start p-3 d-flex justify-content-between align-items-center" style="border-radius: 10px; transition: 0.2s;" onmouseover="this.style.background='#ffffff'; this.style.transform='translateX(5px)';" onmouseout="this.style.background=''; this.style.transform='translateX(0)';">
                            <span><i class="fas fa-globe text-primary me-2"></i> KDP Official Website</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                        <a href="#" class="btn btn-light border fw-bold text-start p-3 d-flex justify-content-between align-items-center" style="border-radius: 10px; transition: 0.2s;" onmouseover="this.style.background='#ffffff'; this.style.transform='translateX(5px)';" onmouseout="this.style.background=''; this.style.transform='translateX(0)';">
                            <span><i class="fas fa-calendar-alt text-success me-2"></i> Academic Calendar</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                        <div class="alert alert-info mb-0 mt-2" role="alert" style="border-radius: 10px; font-size: 13px; font-weight:500;">
                            <i class="fas fa-bullhorn me-2"></i> <strong>Notice:</strong> Last date for practical submission is 25th Dec 2025.
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- ============================================================
    SCRIPTS
    ============================================================ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live Clock
        function updateClock() {
            const now = new Date();
            const options = {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>