<?php
/**
 * Student Course Manuals (Enhanced)
 * ---------------------------------
 * Displays all lab manuals for the student's branch and semester,
 * with pagination, search, filters, submission status, and detailed modals.
 *
 * Security: Uses prepared statements throughout.
 * UX: Real-time search, subject/semester filters, and detailed info popups.
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
// 2. FETCH STUDENT PROFILE (Branch & Semester)
// -----------------------------------------------------------------------------
$user_stmt = $conn->prepare("SELECT name, email, department, designation FROM users WHERE user_id = ?");
$user_stmt->bind_param("s", $enrollment);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$student_data = $user_result->fetch_assoc();
$user_stmt->close();

$student_name = $student_data['name'] ?? 'Student';
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
// 3. HANDLE PAGINATION, SEARCH & FILTER PARAMETERS
// -----------------------------------------------------------------------------
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search term (title or subject)
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Subject filter (from dropdown)
$subject_filter = isset($_GET['subject']) ? trim($_GET['subject']) : '';

// Semester filter (numeric, optional)
$semester_filter = isset($_GET['semester']) ? trim($_GET['semester']) : '';

// -----------------------------------------------------------------------------
// 4. BUILD THE MAIN QUERY WITH PREPARED STATEMENTS
// -----------------------------------------------------------------------------
// We join with users (faculty) to get the uploader's name
$sql = "SELECT m.*, u.name AS faculty_name
        FROM lab_manuals m
        LEFT JOIN users u ON m.faculty_id = u.user_id
        WHERE (m.department = ? OR m.department = 'All')
          AND (m.semester = ? OR m.semester = ? OR m.semester = 'All')";

$params = [$branch, $sem_num, $mapped_semester];
$types = "sss";

// Apply search filter (title or subject)
if (!empty($search_term)) {
    $sql .= " AND (m.title LIKE ? OR m.subject_name LIKE ?)";
    $like_term = "%" . $search_term . "%";
    $params[] = $like_term;
    $params[] = $like_term;
    $types .= "ss";
}

// Apply subject filter (exact match)
if (!empty($subject_filter)) {
    $sql .= " AND m.subject_name = ?";
    $params[] = $subject_filter;
    $types .= "s";
}

// Apply semester filter (if specific semester selected, e.g., '1' or 'Semester 1')
if (!empty($semester_filter)) {
    // allow numeric or full name
    if (is_numeric($semester_filter)) {
        $sem_condition = "m.semester = ? OR m.semester = CONCAT('Semester ', ?)";
        $sql .= " AND ($sem_condition)";
        $params[] = $semester_filter;
        $params[] = $semester_filter;
        $types .= "ss";
    } else {
        $sql .= " AND m.semester = ?";
        $params[] = $semester_filter;
        $types .= "s";
    }
}

// Order by most recent first
$sql .= " ORDER BY m.uploaded_at DESC";

// -----------------------------------------------------------------------------
// 5. COUNT TOTAL RECORDS FOR PAGINATION
// -----------------------------------------------------------------------------
$count_sql = str_replace(
    "SELECT m.*, u.name AS faculty_name",
    "SELECT COUNT(*) AS total",
    $sql
);
$count_stmt = $conn->prepare($count_sql);
if ($count_stmt) {
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_rows = $count_result->fetch_assoc()['total'] ?? 0;
    $count_stmt->close();
} else {
    $total_rows = 0;
}
$total_pages = ceil($total_rows / $limit);

// -----------------------------------------------------------------------------
// 6. FETCH PAGINATED DATA
// -----------------------------------------------------------------------------
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$manuals_stmt = $conn->prepare($sql);
if (!$manuals_stmt) {
    die("Query preparation failed: " . $conn->error);
}
$manuals_stmt->bind_param($types, ...$params);
$manuals_stmt->execute();
$manuals_result = $manuals_stmt->get_result();
$manuals_stmt->close();

// -----------------------------------------------------------------------------
// 7. FETCH DISTINCT SUBJECTS FOR FILTER DROPDOWN (from the student's manuals)
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
// 8. FETCH SUBMISSION STATUS FOR EACH SUBJECT (optional)
//    We'll get all subjects that the student has already submitted (status not null)
//    to show a "Submitted" badge on the manual card.
// -----------------------------------------------------------------------------
$submitted_subjects = [];
$sub_status_sql = "SELECT DISTINCT subject_name FROM student_submissions WHERE student_id = ?";
$sub_status_stmt = $conn->prepare($sub_status_sql);
$sub_status_stmt->bind_param("s", $enrollment);
$sub_status_stmt->execute();
$sub_status_result = $sub_status_stmt->get_result();
while ($row = $sub_status_result->fetch_assoc()) {
    $submitted_subjects[] = $row['subject_name'];
}
$sub_status_stmt->close();

// -----------------------------------------------------------------------------
// 9. FETCH STATISTICS FOR WIDGETS
// -----------------------------------------------------------------------------
// Total manuals available for this student
$stats_sql = "SELECT COUNT(*) AS total_manuals FROM lab_manuals 
              WHERE (department = ? OR department = 'All')
                AND (semester = ? OR semester = ? OR semester = 'All')";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("sss", $branch, $sem_num, $mapped_semester);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$total_manuals = $stats_result->fetch_assoc()['total_manuals'] ?? 0;
$stats_stmt->close();

// Upcoming deadlines (active)
$today = date('Y-m-d');
$deadline_sql = "SELECT subject_name, title, end_date FROM lab_manuals 
                 WHERE (department = ? OR department = 'All')
                   AND (semester = ? OR semester = ? OR semester = 'All')
                   AND end_date >= ? AND end_date != '0000-00-00'
                 ORDER BY end_date ASC LIMIT 5";
$deadline_stmt = $conn->prepare($deadline_sql);
$deadline_stmt->bind_param("ssss", $branch, $sem_num, $mapped_semester, $today);
$deadline_stmt->execute();
$deadlines_result = $deadline_stmt->get_result();
$deadline_stmt->close();

// Count of student's submissions (for stats widget)
$sub_count_sql = "SELECT COUNT(*) AS total_sub, 
                         SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
                         SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved
                  FROM student_submissions WHERE student_id = ?";
$sub_count_stmt = $conn->prepare($sub_count_sql);
$sub_count_stmt->bind_param("s", $enrollment);
$sub_count_stmt->execute();
$sub_count_result = $sub_count_stmt->get_result();
$sub_stats = $sub_count_result->fetch_assoc();
$total_sub = $sub_stats['total_sub'] ?? 0;
$pending_sub = $sub_stats['pending'] ?? 0;
$approved_sub = $sub_stats['approved'] ?? 0;
$sub_count_stmt->close();

// -----------------------------------------------------------------------------
// 10. HELPER FUNCTION FOR ACTIVE FILTER BUTTON
// -----------------------------------------------------------------------------
function isActiveFilter($value, $current) {
    return ($value === $current) ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Manuals – Enhanced | KDP</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           PREMIUM STYLING (extended from original)
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
        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }
        .blue-box {
            background: rgba(59,130,246,0.1);
            color: #3b82f6;
        }
        .box-title {
            font-size: 17px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
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

        /* TABLE */
        .table-custom th {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            border-bottom: 2px solid #e2e8f0;
            padding: 15px 10px;
            background: #ffffff;
        }
        .table-custom td {
            vertical-align: middle;
            font-size: 14px;
            font-weight: 600;
            padding: 15px 10px;
            color: var(--text-main);
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s;
        }
        .table-custom tbody tr:hover td {
            background-color: #f8fafc;
        }
        .table-custom tbody tr {
            cursor: pointer;
        }

        .badge-modern {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .bg-primary-soft {
            background: rgba(59,130,246,0.1);
            color: #1d4ed8;
        }
        .bg-success-soft {
            background: rgba(16,185,129,0.1);
            color: #059669;
        }
        .bg-danger-soft {
            background: rgba(239,68,68,0.1);
            color: #dc2626;
        }

        .btn-view {
            background: rgba(59,130,246,0.1);
            color: #2563eb;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition-bounce);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-view:hover {
            background: #2563eb;
            color: white;
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(37,99,235,0.2);
        }
        .btn-submit {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition-bounce);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-submit:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(16,185,129,0.3);
            color: white;
        }
        .btn-submit.disabled {
            opacity: 0.6;
            pointer-events: none;
            filter: grayscale(0.5);
        }

        /* WIDGET ITEMS */
        .widget-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            margin-bottom: 12px;
            transition: var(--transition-bounce);
        }
        .widget-item:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            transform: translateX(5px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }

        .guideline-list {
            padding-left: 15px;
            margin: 0;
            font-size: 13.5px;
            color: var(--text-muted);
            font-weight: 500;
            line-height: 1.8;
        }
        .guideline-list li {
            margin-bottom: 8px;
        }
        .guideline-list li i {
            color: var(--primary);
            font-size: 12px;
            margin-right: 5px;
        }

        .stat-mini-box {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 15px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            margin-bottom: 10px;
            transition: 0.2s;
        }
        .stat-mini-box:hover {
            background: white;
            border-color: #cbd5e1;
        }
        .stat-icon-mini {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
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

        /* MODAL */
        .modal-content {
            border-radius: var(--radius-xl);
            border: none;
        }
        .modal-header {
            border-bottom: 1px solid #e2e8f0;
        }
        .modal-footer {
            border-top: 1px solid #e2e8f0;
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
            <li onclick="window.location.href='Stdashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='my-manuals.php'"><i class="fas fa-book-open"></i> Course Manuals</li>
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

        <!-- PAGE HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4 mt-2 page-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box blue-box"><i class="fas fa-book-open"></i></div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);">Course Lab Manuals</h3>
                    <p class="text-muted fw-semibold small mb-0">
                        Download official lab manuals for <?php echo htmlspecialchars($mapped_semester); ?>.
                        <?php if (!empty($subject_filter)): ?>
                            <span class="badge bg-primary ms-2">Filter: <?php echo htmlspecialchars($subject_filter); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-3 py-2 fs-6" style="border-radius: 10px; font-weight: 700;">
                    <i class="fas fa-file-pdf me-1"></i> <?php echo $total_manuals; ?> Manuals
                </span>
            </div>
        </div>

        <!-- ============================================================
        SEARCH & FILTER BAR
        ============================================================ -->
        <div class="search-filter-bar">
            <form method="GET" action="" class="d-flex flex-wrap gap-2 w-100">
                <input type="hidden" name="page" value="1">

                <!-- Search -->
                <div class="flex-grow-1" style="min-width: 180px;">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="🔍 Search by title or subject..."
                           value="<?php echo htmlspecialchars($search_term); ?>">
                </div>

                <!-- Subject Filter -->
                <div style="min-width: 160px;">
                    <select name="subject" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Subjects</option>
                        <?php foreach ($subject_list as $sub): ?>
                            <option value="<?php echo htmlspecialchars($sub); ?>" <?php echo ($subject_filter == $sub) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sub); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Semester Filter (numeric) -->
                <div style="min-width: 130px;">
                    <select name="semester" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Semesters</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($semester_filter == $i) ? 'selected' : ''; ?>>
                                Semester <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-outline-primary btn-sm" style="border-radius:30px; font-weight:700;">
                    <i class="fas fa-filter"></i> Apply
                </button>
                <a href="my-manuals.php" class="btn btn-outline-secondary btn-sm" style="border-radius:30px; font-weight:700;">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </form>
        </div>

        <!-- ============================================================
        TWO COLUMN LAYOUT
        ============================================================ -->
        <div class="row g-4">

            <!-- LEFT: MANUALS TABLE -->
            <div class="col-lg-8">
                <div class="content-box h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                        <h5 class="box-title mb-0 border-0 pb-0"><i class="fas fa-folder-open text-primary me-2"></i> Available Manuals</h5>
                        <span class="badge bg-light text-dark fw-bold">
                            <?php echo $manuals_result->num_rows; ?> of <?php echo $total_rows; ?>
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom mb-0" id="manualTable">
                            <thead>
                                <tr>
                                    <th>Manual Details</th>
                                    <th>Dates &amp; Info</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($manuals_result && $manuals_result->num_rows > 0): ?>
                                    <?php while ($row = $manuals_result->fetch_assoc()):
                                        // Check if student already submitted for this subject
                                        $submitted = in_array($row['subject_name'], $submitted_subjects);
                                        // Determine deadline status
                                        $deadline_color = 'text-muted';
                                        $deadline_text = 'No Deadline';
                                        $deadline_extra = '';
                                        if (!empty($row['end_date']) && $row['end_date'] != '0000-00-00') {
                                            $deadline_ts = strtotime($row['end_date']);
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
                                        // File info
                                        $file_size = isset($row['file_size']) ? number_format($row['file_size'] / 1024, 1) . ' KB' : 'N/A';
                                        $file_name = basename($row['file_path']);
                                    ?>
                                        <tr class="manual-row" data-id="<?php echo $row['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($row['title']); ?>"
                                            data-subject="<?php echo htmlspecialchars($row['subject_name']); ?>"
                                            data-semester="<?php echo htmlspecialchars($row['semester']); ?>"
                                            data-file="<?php echo htmlspecialchars($row['file_path']); ?>"
                                            data-uploaded="<?php echo date('d M Y, h:i A', strtotime($row['uploaded_at'])); ?>"
                                            data-end="<?php echo !empty($row['end_date']) && $row['end_date'] != '0000-00-00' ? date('d M Y', strtotime($row['end_date'])) : 'N/A'; ?>"
                                            data-faculty="<?php echo htmlspecialchars($row['faculty_name'] ?? 'N/A'); ?>"
                                            data-size="<?php echo $file_size; ?>"
                                            data-description="<?php echo htmlspecialchars($row['description'] ?? ''); ?>"
                                            onclick="openManualDetail(this)">
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-danger text-white rounded p-2 text-center" style="width:45px; height:45px; display:flex; align-items:center; justify-content:center;">
                                                        <i class="fas fa-file-pdf fs-5"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold subject-title" style="font-size: 15px; color: var(--text-main);">
                                                            <?php echo htmlspecialchars($row['title']); ?>
                                                            <?php if ($submitted): ?>
                                                                <span class="badge bg-success-soft ms-2" style="font-size: 10px;">
                                                                    <i class="fas fa-check-circle"></i> Submitted
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="text-primary fw-bold mt-1" style="font-size: 12.5px;">
                                                            <?php echo htmlspecialchars($row['subject_name']); ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="far fa-user me-1"></i>
                                                            <?php echo htmlspecialchars($row['faculty_name'] ?? 'Unknown Faculty'); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-size: 12.5px; color: var(--text-main);" class="fw-semibold">
                                                    <i class="fas fa-upload text-muted me-1"></i> Added: <?php echo date('d M', strtotime($row['uploaded_at'])); ?><br>
                                                    <div class="mt-1">
                                                        <i class="fas fa-flag-checkered <?php echo $deadline_color; ?> me-1"></i>
                                                        <span class="<?php echo $deadline_color; ?>">Due: <?php echo $deadline_text; ?></span>
                                                    </div>
                                                    <?php if (!empty($row['semester']) && $row['semester'] != 'All'): ?>
                                                        <div class="mt-1">
                                                            <span class="badge bg-light text-dark fw-semibold" style="font-size: 10px;">
                                                                <i class="fas fa-graduation-cap me-1"></i> <?php echo htmlspecialchars($row['semester']); ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <?php
                                                    // Fix file path
                                                    $raw_path = $row['file_path'];
                                                    $pos = strpos($raw_path, 'uploads/');
                                                    $safe_pdf_path = ($pos !== false) ? '../' . substr($raw_path, $pos) : '../' . $raw_path;
                                                ?>
                                                <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" target="_blank" class="btn-view" title="View/Download PDF">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <?php if ($submitted): ?>
                                                    <a href="upload_manual.php?subject=<?php echo urlencode($row['subject_name']); ?>" class="btn-submit ms-2 disabled" title="Already submitted">
                                                        <i class="fas fa-check-circle me-1"></i> Submitted
                                                    </a>
                                                <?php else: ?>
                                                    <a href="upload_manual.php?subject=<?php echo urlencode($row['subject_name']); ?>" class="btn-submit ms-2" title="Submit Practical">
                                                        <i class="fas fa-upload me-1"></i> Submit
                                                    </a>
                                                <?php endif; ?>
                                                <!-- Detail trigger via row click (already set) -->
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            <i class="fas fa-box-open mb-3" style="font-size: 45px; opacity: 0.3; color: var(--primary);"></i><br>
                                            <h5 class="fw-bold text-dark mb-1">No Manuals Found</h5>
                                            <p class="small mb-0">
                                                <?php if (!empty($search_term) || !empty($subject_filter) || !empty($semester_filter)): ?>
                                                    No manuals match your filters. Try resetting them.
                                                <?php else: ?>
                                                    Your department hasn't uploaded any lab manuals for your semester yet.
                                                <?php endif; ?>
                                            </p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- PAGINATION -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_term); ?>&subject=<?php echo urlencode($subject_filter); ?>&semester=<?php echo urlencode($semester_filter); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_term); ?>&subject=<?php echo urlencode($subject_filter); ?>&semester=<?php echo urlencode($semester_filter); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_term); ?>&subject=<?php echo urlencode($subject_filter); ?>&semester=<?php echo urlencode($semester_filter); ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT: WIDGETS -->
            <div class="col-lg-4">

                <!-- STATS WIDGET -->
                <div class="content-box">
                    <h5 class="box-title"><i class="fas fa-chart-simple text-primary me-2"></i> Your Progress</h5>
                    <div class="stat-mini-box">
                        <div class="stat-icon-mini" style="background: rgba(59,130,246,0.1); color: #3b82f6;"><i class="fas fa-file-pdf"></i></div>
                        <div>
                            <h3 class="fw-bold text-dark mb-0" style="font-size: 18px;"><?php echo $total_manuals; ?></h3>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Total Manuals</small>
                        </div>
                    </div>
                    <div class="stat-mini-box">
                        <div class="stat-icon-mini" style="background: rgba(16,185,129,0.1); color: #10b981;"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <h3 class="fw-bold text-dark mb-0" style="font-size: 18px;"><?php echo $approved_sub; ?></h3>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Approved Submissions</small>
                        </div>
                    </div>
                    <div class="stat-mini-box">
                        <div class="stat-icon-mini" style="background: rgba(245,158,11,0.1); color: #f59e0b;"><i class="fas fa-clock"></i></div>
                        <div>
                            <h3 class="fw-bold text-dark mb-0" style="font-size: 18px;"><?php echo $pending_sub; ?></h3>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Pending Review</small>
                        </div>
                    </div>
                    <div class="stat-mini-box">
                        <div class="stat-icon-mini" style="background: rgba(139,92,246,0.1); color: #7c3aed;"><i class="fas fa-upload"></i></div>
                        <div>
                            <h3 class="fw-bold text-dark mb-0" style="font-size: 18px;"><?php echo $total_sub; ?></h3>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Total Submissions</small>
                        </div>
                    </div>
                </div>

                <!-- UPCOMING DEADLINES -->
                <div class="content-box mb-4">
                    <h5 class="box-title"><i class="fas fa-clock text-warning me-2"></i> Upcoming Deadlines</h5>
                    <?php if ($deadlines_result && $deadlines_result->num_rows > 0): ?>
                        <?php while ($dl = $deadlines_result->fetch_assoc()):
                            $dl_ts = strtotime($dl['end_date']);
                            $days_left = round(($dl_ts - time()) / (60 * 60 * 24));
                            $alert_class = ($days_left <= 2) ? 'text-danger' : 'text-success';
                        ?>
                        <div class="widget-item">
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 14px;"><?php echo htmlspecialchars($dl['subject_name']); ?></h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted fw-semibold"><?php echo htmlspecialchars($dl['title']); ?></small>
                                <span class="badge bg-light border <?php echo $alert_class; ?> fw-bold shadow-sm">
                                    <?php echo date('d M', $dl_ts); ?>
                                </span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-calendar-check text-success mb-2" style="font-size: 30px; opacity: 0.7;"></i>
                            <p class="text-muted small fw-semibold mb-0">No upcoming deadlines. Relax!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- GUIDELINES -->
                <div class="content-box" style="background: linear-gradient(135deg, rgba(67,56,202,0.03), rgba(59,130,246,0.05)); border: 1px solid rgba(67,56,202,0.1);">
                    <h5 class="box-title"><i class="fas fa-info-circle text-primary me-2"></i> Submission Guidelines</h5>
                    <ul class="list-unstyled guideline-list">
                        <li><i class="fas fa-check-circle"></i> Always download the official manual first.</li>
                        <li><i class="fas fa-check-circle"></i> Create a single PDF file for each practical.</li>
                        <li><i class="fas fa-check-circle"></i> File size ≤ <strong>5MB</strong>.</li>
                        <li><i class="fas fa-check-circle"></i> Name: <em>Enrollment_Subject_PracNo.pdf</em>.</li>
                        <li><i class="fas fa-check-circle"></i> Late submissions may be rejected.</li>
                    </ul>
                </div>

            </div>
        </div>
    </div>

    <!-- ============================================================
    DETAIL MODAL
    ============================================================ -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="detailModalLabel">
                        <i class="fas fa-file-alt text-primary me-2"></i> Manual Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailModalBody">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Title:</strong> <span id="modalTitle"></span></p>
                            <p><strong>Subject:</strong> <span id="modalSubject"></span></p>
                            <p><strong>Semester:</strong> <span id="modalSemester"></span></p>
                            <p><strong>Uploaded:</strong> <span id="modalUploaded"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Deadline:</strong> <span id="modalDeadline"></span></p>
                            <p><strong>Faculty:</strong> <span id="modalFaculty"></span></p>
                            <p><strong>File Size:</strong> <span id="modalSize"></span></p>
                            <p><strong>File Name:</strong> <span id="modalFile"></span></p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p><strong>Description:</strong></p>
                        <div id="modalDescription" class="p-3 bg-light rounded-3" style="font-weight:500; min-height:50px;"></div>
                    </div>
                    <div class="mt-3">
                        <a id="modalFileLink" href="#" target="_blank" class="btn btn-primary">
                            <i class="fas fa-file-pdf"></i> Open PDF
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
    SCRIPTS
    ============================================================ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ------------------------------------------------------------
        // 1. LIVE CLOCK
        // ------------------------------------------------------------
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

        // ------------------------------------------------------------
        // 2. MANUAL DETAIL MODAL (triggered by row click)
        // ------------------------------------------------------------
        function openManualDetail(row) {
            // Read data attributes from the row
            const title = row.getAttribute('data-title');
            const subject = row.getAttribute('data-subject');
            const semester = row.getAttribute('data-semester');
            const uploaded = row.getAttribute('data-uploaded');
            const deadline = row.getAttribute('data-end');
            const faculty = row.getAttribute('data-faculty');
            const size = row.getAttribute('data-size');
            const file = row.getAttribute('data-file');
            const description = row.getAttribute('data-description') || 'No description available.';

            // Build the correct file path
            let rawPath = file;
            let pos = rawPath.indexOf('uploads/');
            let safePath = (pos !== -1) ? '../' + rawPath.substring(pos) : '../' + rawPath;

            // Populate modal fields
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalSubject').textContent = subject;
            document.getElementById('modalSemester').textContent = semester;
            document.getElementById('modalUploaded').textContent = uploaded;
            document.getElementById('modalDeadline').textContent = deadline;
            document.getElementById('modalFaculty').textContent = faculty;
            document.getElementById('modalSize').textContent = size;
            document.getElementById('modalFile').textContent = file.split('/').pop();
            document.getElementById('modalDescription').innerHTML = description.replace(/\n/g, '<br>');
            document.getElementById('modalFileLink').href = safePath;

            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('detailModal'));
            modal.show();
        }

        // ------------------------------------------------------------
        // 3. LIVE SEARCH (client-side, though we already have server-side)
        //    Keep as additional real-time filter
        // ------------------------------------------------------------
        document.getElementById('manualSearch').addEventListener('keyup', function() {
            let input = this.value.toLowerCase();
            let rows = document.querySelectorAll('.manual-row');
            rows.forEach(row => {
                let title = row.querySelector('.subject-title').innerText.toLowerCase();
                let subject = row.querySelector('.text-primary').innerText.toLowerCase();
                if (title.includes(input) || subject.includes(input)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>