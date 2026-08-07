<?php
session_start();
include '../db.php'; // 🔥 Live Database Connection

// 1. Check Login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$faculty_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Faculty';

// 2. Fetch Subjects from MySQL
$raw_subjects = [];
$sub_query = "SELECT subjects FROM users WHERE user_id = '$faculty_id'";
$sub_result = $conn->query($sub_query);

if ($sub_result && $sub_result->num_rows > 0) {
    $row = $sub_result->fetch_assoc();
    if (!empty($row['subjects'])) {
        $decoded = json_decode($row['subjects'], true);
        if (is_array($decoded)) {
            $raw_subjects = $decoded;
        } else {
            $raw_subjects = array_map('trim', explode(',', $row['subjects']));
        }
    }
}

// 🔥 3. SMART LOGIC: Fix Semesters 1 to 6 (GTU Diploma Structure)
$faculty_semesters = [
    "Semester 1", "Semester 2", "Semester 3", 
    "Semester 4", "Semester 5", "Semester 6"
];

// Sabhi semesters ke liye pehle se khali array bana diya
$grouped_subjects = [
    "Semester 1" => [], "Semester 2" => [], "Semester 3" => [], 
    "Semester 4" => [], "Semester 5" => [], "Semester 6" => [],
    "Other Subjects" => []
];

foreach($raw_subjects as $sub) {
    $sub = is_array($sub) ? (isset($sub['name']) ? $sub['name'] : '') : $sub;
    
    // Check if string contains format "Sem 5 - Web Development"
    if(preg_match('/Sem\s*(\d+)\s*[-:]\s*(.*)/i', $sub, $matches)) {
        $sem = "Semester " . $matches[1];
        $subject_name = trim($matches[2]);
        
        // Agar galti se koi Sem 7 daal de, toh usko bhi list mein add kar lega
        if(!in_array($sem, $faculty_semesters)) {
            $faculty_semesters[] = $sem;
        }
    } else {
        $sem = "Other Subjects"; // Agar bina sem ke daala ho
        $subject_name = trim($sub);
        if(!in_array($sem, $faculty_semesters)) {
            $faculty_semesters[] = $sem;
        }
    }
    // Subject ko uske correct semester mein daal do
    $grouped_subjects[$sem][] = $subject_name;
}

// 4. Handle Selected Semester & Subject via URL (?sem=X&subject=Y)
$selected_sem = isset($_GET['sem']) && in_array($_GET['sem'], $faculty_semesters) 
                ? $_GET['sem'] 
                : 'Semester 1'; // Default hamesha Sem 1 khulega

$available_subjects = isset($grouped_subjects[$selected_sem]) ? $grouped_subjects[$selected_sem] : [];

$selected_subject = isset($_GET['subject']) && in_array($_GET['subject'], $available_subjects)
                    ? $_GET['subject'] 
                    : (count($available_subjects) > 0 ? $available_subjects[0] : '');

$safe_sub = $conn->real_escape_string($selected_subject);

// 5. LIVE DASHBOARD STATS (Sirf selected subject ke students aayenge)
$total_students = 0; $pending = 0; $approved = 0; $rejected = 0;

if(!empty($safe_sub)) {
    $stats_query = "SELECT status, COUNT(*) as count FROM submissions WHERE subject = '$safe_sub' GROUP BY status";
    $stats_result = $conn->query($stats_query);

    if ($stats_result) {
        while ($stat = $stats_result->fetch_assoc()) {
            $total_students += $stat['count'];
            $status_lower = strtolower($stat['status']);
            if ($status_lower == 'pending') $pending = $stat['count'];
            if ($status_lower == 'approved') $approved = $stat['count'];
            if ($status_lower == 'rejected') $rejected = $stat['count'];
        }
    }
}

// 6. FETCH SUBMISSIONS (Sirf selected subject ke liye)
$recent_submissions = [];
if(!empty($safe_sub)) {
    $recent_query = "
        SELECT s.*, u.name 
        FROM submissions s 
        LEFT JOIN users u ON s.enrollment = u.user_id 
        WHERE s.subject = '$safe_sub' 
        ORDER BY s.id DESC LIMIT 10
    ";
    $recent_result = $conn->query($recent_query);

    if ($recent_result) {
        while ($row = $recent_result->fetch_assoc()) {
            if (empty($row['name'])) $row['name'] = "Student (" . $row['enrollment'] . ")";
            $recent_submissions[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - KDP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f1f5f9; display: flex; height: 100vh; overflow: hidden; }
        
        /* 🔵 SIDEBAR */
        .sidebar { width: 260px; background-color: #113460; color: #ffffff; display: flex; flex-direction: column; padding: 25px 0; box-shadow: 4px 0 10px rgba(0,0,0,0.1); z-index: 10; }
        .sidebar-logo-container { text-align: center; margin-bottom: 20px; }
        .logo-wrapper { width: 90px; height: 90px; background: #ffffff; border-radius: 50%; margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; overflow: hidden; box-shadow: 0 0 15px rgba(255,255,255,0.15); border: 3px solid rgba(255,255,255,0.2); }
        .sidebar-logo { width: 105%; height: auto; }
        .sidebar-title h2 { font-size: 19px; font-weight: 600; letter-spacing: 0.5px; }
        .sidebar-title p { font-size: 13px; color: #94a3b8; margin-top: 2px;}
        .sidebar-divider { height: 1px; background: #1e4b85; margin: 15px 20px; }
        
        .nav-links { list-style: none; padding: 0; flex-grow: 1; margin-top: 10px; }
        .nav-links li { padding: 15px 25px; margin: 5px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 15px; transition: 0.3s; color: #cbd5e1; }
        .nav-links li:hover { background: #1e4b85; color: white; }
        .nav-links li.active { background: #2563eb; color: white; box-shadow: 0 4px 10px rgba(37,99,235,0.3); }
        .nav-links li i { font-size: 18px; }
        .logout-btn { color: #fca5a5 !important; margin-top: auto; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.1) !important; color: #ef4444 !important; }

        /* 🔵 MAIN CONTENT */
        .main { flex: 1; padding: 30px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-left h2 { color: #0f172a; font-size: 26px; font-weight: 700; }
        .header-left p { color: #64748b; font-size: 14px; margin-top: 5px; }
        
        .header-profile { display: flex; align-items: center; gap: 15px; background: #ffffff; padding: 8px 10px 8px 20px; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; cursor: pointer; transition: all 0.3s ease; }
        .header-profile:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.08); transform: translateY(-2px); }
        .profile-text { display: flex; flex-direction: column; text-align: right; }
        .welcome-text { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .faculty-name { font-size: 15px; color: #0f172a; font-weight: 700; }
        .profile-avatar { width: 42px; height: 42px; border-radius: 50%; border: 2px solid #2563eb; object-fit: cover; }

        /* 🔥 DUAL DROPDOWN SECTION FOR SEMESTER & SUBJECT */
        .dropdown-section { background: white; padding: 20px 25px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .dropdown-box { display: flex; flex-direction: column; width: 250px; }
        .dropdown-box label { color: #64748b; font-size: 12px; margin-bottom: 6px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
        .dropdown-box select { padding: 10px 15px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; color: #113460; font-weight: 600; cursor: pointer; transition: 0.2s;}
        .dropdown-box select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        
        .cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 22px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.04); border-left: 5px solid #2563eb; transition: transform 0.2s;}
        .card:hover { transform: translateY(-5px); }
        .card h3 { color: #64748b; font-size: 14px; margin-bottom: 5px; font-weight: 600;}
        .card p { color: #0f172a; font-size: 26px; font-weight: 700; }
        .card i { font-size: 35px; color: #e2e8f0; }

        .table-section { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background: #f8fafc; color: #64748b; padding: 14px 12px; text-align: left; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-weight: 700; letter-spacing: 0.5px;}
        td { padding: 16px 12px; border-bottom: 1px solid #e2e8f0; color: #1e293b; font-size: 14.5px; font-weight: 500;}
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; letter-spacing: 0.5px; }
        .badge.pending { background: #fef3c7; color: #d97706; }
        .badge.approved { background: #d1fae5; color: #059669; }
        .badge.rejected { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <div class="logo-wrapper">
                <img src="../assets/images/KDP-Logo.png" alt="KDP Logo" class="sidebar-logo">
            </div>
            <div class="sidebar-title">
                <h2>K.D. Polytechnic</h2>
                <p>Faculty Portal</p>
            </div>
        </div>
        <div class="sidebar-divider"></div>
        <ul class="nav-links">
            <li class="active" onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='labmanual_list.php'"><i class="fas fa-book"></i> Lab Manuals</li>
            <li onclick="window.location.href='reports.php'"><i class="fas fa-file-alt"></i> Reports</li>
            <li class="logout-btn" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <div class="header">
            <div class="header-left">
                <h2>Faculty Dashboard</h2>
                <p>Manage student submissions and track progress.</p>
            </div>
            
            <div class="header-profile" onclick="window.location.href='profile.php'" title="View & Edit Profile">
                <div class="profile-text">
                    <span class="welcome-text">Welcome Back,</span>
                    <span class="faculty-name"><?php echo htmlspecialchars($faculty_name); ?></span>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($faculty_name); ?>&background=2563eb&color=fff&rounded=true&bold=true" alt="Profile" class="profile-avatar">
            </div>
        </div>

        <!-- 🔥 DUAL FILTER MENU FOR SEMESTER & SUBJECT -->
        <div class="dropdown-section">
            <div class="dropdown-box">
                <label><i class="fa-solid fa-layer-group me-1"></i> Choose Semester</label>
                <select id="semSelect" onchange="window.location.href='?sem=' + encodeURIComponent(this.value)">
                    <?php foreach($faculty_semesters as $sem) { ?>
                        <option value="<?php echo htmlspecialchars($sem); ?>" <?php if($selected_sem == $sem) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($sem); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            
            <div class="dropdown-box">
                <label><i class="fa-solid fa-book-open me-1"></i> Select Subject</label>
                <select id="subSelect" onchange="if(this.value !== '') window.location.href='?sem=<?php echo urlencode($selected_sem); ?>&subject=' + encodeURIComponent(this.value)">
                    <?php if(empty($available_subjects)) { ?>
                        <option value="">No Subjects Assigned</option>
                    <?php } else { 
                        foreach($available_subjects as $sub) { ?>
                        <option value="<?php echo htmlspecialchars($sub); ?>" <?php if($selected_subject == $sub) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($sub); ?>
                        </option>
                    <?php } } ?>
                </select>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div class="cards">
            <div class="card" style="border-color: #3b82f6;"><div><h3>Total Submissions</h3><p><?php echo $total_students; ?></p></div><i class="fas fa-file-pdf"></i></div>
            <div class="card" style="border-color: #f59e0b;"><div><h3>Pending</h3><p><?php echo $pending; ?></p></div><i class="fas fa-clock"></i></div>
            <div class="card" style="border-color: #10b981;"><div><h3>Approved</h3><p><?php echo $approved; ?></p></div><i class="fas fa-check-circle"></i></div>
            <div class="card" style="border-color: #ef4444;"><div><h3>Rejected</h3><p><?php echo $rejected; ?></p></div><i class="fas fa-times-circle"></i></div>
        </div>

        <!-- TABLE SECTION -->
        <div class="table-section">
            <h3 style="color: #0f172a; margin-bottom: 10px;">Recent Submissions (<?php echo empty($selected_subject) ? 'No Subject' : htmlspecialchars($selected_subject); ?>)</h3>
            <table>
                <tr>
                    <th>Student Name</th>
                    <th>Enrollment No.</th>
                    <th>Status</th>
                    <th>Marks</th>
                </tr>
                <?php if (count($recent_submissions) == 0) { ?>
                    <tr><td colspan='4' style='text-align:center; padding: 20px; color: #64748b;'><i class="fa-solid fa-folder-open me-2"></i> No recent submissions found for this subject.</td></tr>
                <?php } else { 
                    foreach ($recent_submissions as $row) { 
                        $status_class = strtolower($row['status']); 
                ?>
                    <tr>
                        <td><i class="fa-solid fa-user-graduate text-muted me-2"></i> <?php echo htmlspecialchars($row['name']); ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['enrollment']); ?></span></td>        
                        <td><span class='badge <?php echo $status_class; ?>'><?php echo htmlspecialchars($row['status']); ?></span></td>
                        <!-- 🔥 UPDATED TO /20 HERE -->
                        <td style="color:#2563eb; font-weight:bold;">
                            <?php echo (isset($row['marks']) && $row['marks'] != '') ? htmlspecialchars($row['marks']) . '/20' : '-'; ?>
                        </td>
                    </tr>
                <?php } } ?>
            </table>
        </div>
    </div>
</body>
</html>