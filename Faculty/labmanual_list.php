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

// 2. Fetch Subjects directly from MySQL (Handling Semesters if available)
$faculty_subjects = [];
$sub_query = "SELECT subjects FROM users WHERE user_id = '$faculty_id'";
$sub_result = $conn->query($sub_query);

if ($sub_result && $sub_result->num_rows > 0) {
    $row = $sub_result->fetch_assoc();
    if (!empty($row['subjects'])) {
        $decoded = json_decode($row['subjects'], true);
        if (is_array($decoded)) {
            $faculty_subjects = $decoded;
        } else {
            $faculty_subjects = array_map('trim', explode(',', $row['subjects']));
        }
    }
}

if (empty($faculty_subjects)) {
    $faculty_subjects = ["Web Development", "Database Management"];
}

$default_sub = is_array($faculty_subjects[0]) ? (isset($faculty_subjects[0]['name']) ? $faculty_subjects[0]['name'] : $faculty_subjects[0]) : $faculty_subjects[0];
$selected_subject = isset($_GET['subject']) ? trim($_GET['subject']) : $default_sub;
$safe_sub = $conn->real_escape_string($selected_subject);

// 3. 🔥 LIVE FETCH SUBMISSIONS
$submissions = [];
$total_uploads = 0;
$pending_review = 0;
$graded = 0;

$query = "
    SELECT s.*, u.name 
    FROM submissions s 
    LEFT JOIN users u ON s.enrollment = u.user_id 
    WHERE s.subject = '$safe_sub' 
    ORDER BY s.id DESC
";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (empty($row['name'])) {
            $row['name'] = "Student (" . $row['enrollment'] . ")";
        }
        $submissions[] = $row; 
        
        // Calculate Quick Stats
        $total_uploads++;
        if (strtolower($row['status']) == 'pending') {
            $pending_review++;
        } else {
            $graded++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Manuals - KDP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f1f5f9; display: flex; height: 100vh; overflow: hidden; }
        
        /* 🔵 SIDEBAR (Same as Dashboard) */
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
        
        /* 🔥 HEADER & PROFILE */
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .header-left h2 { color: #0f172a; font-size: 26px; font-weight: 700; }
        .header-left p { color: #64748b; font-size: 14px; margin-top: 5px; }
        .header-profile { display: flex; align-items: center; gap: 15px; background: #ffffff; padding: 8px 10px 8px 20px; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; cursor: pointer; transition: all 0.3s ease; }
        .header-profile:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.06); transform: translateY(-2px); }
        .profile-text { display: flex; flex-direction: column; text-align: right; }
        .welcome-text { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .faculty-name { font-size: 15px; color: #0f172a; font-weight: 700; }
        .profile-avatar { width: 42px; height: 42px; border-radius: 50%; border: 2px solid #2563eb; object-fit: cover; }

        /* 🔥 NEW TOOLBAR (Subject + Search) */
        .toolbar { background: white; padding: 20px 25px; border-radius: 12px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; }
        .subject-selector { display: flex; align-items: center; gap: 15px; }
        .subject-selector h3 { color: #1e293b; font-size: 15px; margin: 0; display: flex; align-items: center; gap: 8px; }
        .subject-selector select { padding: 10px 15px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; min-width: 280px; background: #f8fafc; color: #2563eb; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.3s; }
        .subject-selector select:hover { border-color: #94a3b8; }
        
        .search-box { position: relative; width: 300px; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-box input { width: 100%; padding: 10px 15px 10px 40px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; font-size: 14px; background: #f8fafc; transition: 0.3s; }
        .search-box input:focus { border-color: #3b82f6; background: white; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

        /* 🔥 QUICK STATS BAR */
        .quick-stats { display: flex; gap: 20px; margin-bottom: 25px; }
        .stat-badge { flex: 1; background: white; padding: 15px 20px; border-radius: 10px; display: flex; align-items: center; justify-content: space-between; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .stat-badge span { color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-badge h4 { color: #0f172a; font-size: 22px; font-weight: 700; margin-top: 4px; }
        .stat-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        
        /* Table Styles */
        .table-section { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; color: #64748b; padding: 14px 12px; text-align: left; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-weight: 700; letter-spacing: 0.5px;}
        td { padding: 16px 12px; border-bottom: 1px solid #e2e8f0; color: #1e293b; font-size: 14.5px; font-weight: 500;}
        tr:hover td { background: #f8fafc; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; letter-spacing: 0.5px; }
        .badge.pending { background: #fef3c7; color: #d97706; }
        .badge.approved { background: #d1fae5; color: #059669; }
        .badge.rejected { background: #fee2e2; color: #dc2626; }
        .btn-view { background: #eff6ff; color: #2563eb; padding: 8px 15px; border: 1px solid #bfdbfe; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 6px;}
        .btn-view:hover { background: #2563eb; color: white; }
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
            <li onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='labmanual_list.php'"><i class="fas fa-book"></i> Lab Manuals</li>
            <li onclick="window.location.href='reports.php'"><i class="fas fa-file-alt"></i> Reports</li>
            <li class="logout-btn" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <div class="header">
            <div class="header-left">
                <h2>Review Lab Manuals</h2>
                <p>Evaluate student submissions and provide feedback.</p>
            </div>
            
            <div class="header-profile">
                <div class="profile-text">
                    <span class="welcome-text">Welcome Back,</span>
                    <span class="faculty-name"><?php echo htmlspecialchars($faculty_name); ?></span>
                </div>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($faculty_name); ?>&background=2563eb&color=fff&rounded=true&bold=true" alt="Profile" class="profile-avatar">
            </div>
        </div>

        <!-- 🔥 SMART TOOLBAR -->
        <div class="toolbar">
            <div class="subject-selector">
                <h3><i class="fas fa-layer-group" style="color: #64748b;"></i> Subject:</h3>
                <select onchange="window.location.href='?subject=' + encodeURIComponent(this.value)">
                    <?php foreach($faculty_subjects as $sub) { 
                        // Logic to show Semester if available (e.g. "Sem 5 - Web Dev")
                        $sub_name = is_array($sub) ? (isset($sub['name']) ? $sub['name'] : '') : $sub;
                        $sem_label = is_array($sub) && isset($sub['sem']) ? "Sem " . $sub['sem'] . " - " : "";
                        
                        if (!empty($sub_name)) {
                    ?>
                        <option value="<?php echo htmlspecialchars($sub_name); ?>" <?php if($selected_subject == $sub_name) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($sem_label . $sub_name); ?>
                        </option>
                    <?php } } ?>
                </select>
            </div>
            
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Search student name or enrollment...">
            </div>
        </div>

        <!-- 🔥 QUICK STATS -->
        <div class="quick-stats">
            <div class="stat-badge">
                <div><span>Total Uploads</span><h4><?php echo $total_uploads; ?></h4></div>
                <div class="stat-icon" style="background: #eff6ff; color: #3b82f6;"><i class="fas fa-file-upload"></i></div>
            </div>
            <div class="stat-badge">
                <div><span>Pending Review</span><h4><?php echo $pending_review; ?></h4></div>
                <div class="stat-icon" style="background: #fef3c7; color: #d97706;"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-badge">
                <div><span>Graded / Reviewed</span><h4><?php echo $graded; ?></h4></div>
                <div class="stat-icon" style="background: #d1fae5; color: #059669;"><i class="fas fa-check-double"></i></div>
            </div>
        </div>

        <!-- TABLE SECTION -->
        <div class="table-section">
            <table id="submissionsTable">
                <tr>
                    <th>Student Name</th>
                    <th>Enrollment No.</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php if (count($submissions) == 0) { ?>
                    <tr><td colspan='4' style='text-align:center; padding: 30px; color: #64748b;'><i class="fas fa-folder-open" style="font-size: 24px; margin-bottom: 10px; display: block; color: #cbd5e1;"></i>No manuals uploaded yet for this subject.</td></tr>
                <?php } else { 
                    foreach ($submissions as $row) { 
                        $status_class = strtolower($row['status']); 
                ?>
                    <tr>
                        <td style="font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['enrollment']); ?></td>
                        <td><span class='badge <?php echo $status_class; ?>'><?php echo htmlspecialchars($row['status']); ?></span></td>
                        <td>
                            <?php if (!empty($row['file_path'])) { ?>
                                <button class="btn-view" onclick="window.open('../<?php echo htmlspecialchars($row['file_path']); ?>', '_blank')">
                                    <i class="fas fa-external-link-alt"></i> View PDF
                                </button>
                            <?php } else { ?>
                                <span style="color: #94a3b8; font-size: 13px; font-style: italic;">No attachment</span>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } } ?>
            </table>
        </div>
    </div>

    <!-- Live Search JavaScript -->
    <script>
        function searchTable() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let table = document.getElementById("submissionsTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) { // Skip header row
                let tdName = tr[i].getElementsByTagName("td")[0];
                let tdEnroll = tr[i].getElementsByTagName("td")[1];
                if (tdName || tdEnroll) {
                    let textName = tdName.textContent || tdName.innerText;
                    let textEnroll = tdEnroll.textContent || tdEnroll.innerText;
                    if (textName.toLowerCase().indexOf(input) > -1 || textEnroll.toLowerCase().indexOf(input) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }       
            }
        }
    </script>
</body>
</html>