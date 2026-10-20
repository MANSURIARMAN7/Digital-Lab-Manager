<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch Admin Details
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data['name'] ?? 'System Administrator';

// Get Parameters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_sem = isset($_GET['sem']) ? $_GET['sem'] : 'all';

// 🚀 FIXED: Check if it is a Global Search (Search exists, but Year is missing)
$is_global_search = !isset($_GET['year']) && !empty($search_query);

if ($is_global_search) {
    // === GLOBAL SEARCH MODE ===
    $year = 0; 
    $year_title = "Global Search Results";
    $theme_color = "#4f46e5"; // Indigo Color for Global Search
    $sql_sem_condition = "1=1"; // Match ALL Semesters
} else {
    // === NORMAL YEAR FILTER MODE ===
    $year = isset($_GET['year']) ? (int)$_GET['year'] : 1;

    // 🚀 FIXED: Robust Semester Checking
    if ($year == 1) {
        $sem_labels = ['Semester 1', 'Semester 2'];
        $sem_values = "'Semester 1', '1', 'Semester 2', '2'";
        $sem1_values = "'Semester 1', '1'";
        $sem2_values = "'Semester 2', '2'";
        $year_title = "1st Year Students";
        $theme_color = "#3b82f6"; // Blue
    } elseif ($year == 2) {
        $sem_labels = ['Semester 3', 'Semester 4'];
        $sem_values = "'Semester 3', '3', 'Semester 4', '4'";
        $sem1_values = "'Semester 3', '3'";
        $sem2_values = "'Semester 4', '4'";
        $year_title = "2nd Year Students";
        $theme_color = "#10b981"; // Green
    } elseif ($year == 3) {
        $sem_labels = ['Semester 5', 'Semester 6'];
        $sem_values = "'Semester 5', '5', 'Semester 6', '6'";
        $sem1_values = "'Semester 5', '5'";
        $sem2_values = "'Semester 6', '6'";
        $year_title = "3rd Year Students";
        $theme_color = "#f59e0b"; // Yellow
    } else {
        $year = 1;
        $sem_labels = ['Semester 1', 'Semester 2'];
        $sem_values = "'Semester 1', '1', 'Semester 2', '2'";
        $sem1_values = "'Semester 1', '1'";
        $sem2_values = "'Semester 2', '2'";
        $year_title = "1st Year Students";
        $theme_color = "#3b82f6";
    }

    if ($filter_sem == $sem_labels[0]) {
        $sql_sem_condition = "(semester IN ($sem1_values) OR designation IN ($sem1_values))";
    } elseif ($filter_sem == $sem_labels[1]) {
        $sql_sem_condition = "(semester IN ($sem2_values) OR designation IN ($sem2_values))";
    } else {
        $sql_sem_condition = "(semester IN ($sem_values) OR designation IN ($sem_values))";
    }
}

// ==========================================
// 🗑️ DELETE STUDENT LOGIC
// ==========================================
$msg = "";
if (isset($_GET['delete_id'])) {
    $del_id = $conn->real_escape_string($_GET['delete_id']);
    if ($conn->query("DELETE FROM users WHERE user_id='$del_id' AND role='student'")) {
        $msg = "<div class='alert alert-success alert-dismissible fade show mb-4 shadow-sm' style='border-radius: 10px; font-weight: 600;'><i class='fas fa-check-circle me-2'></i> Student successfully removed from the system.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show mb-4 shadow-sm' style='border-radius: 10px; font-weight: 600;'><i class='fas fa-exclamation-triangle me-2'></i> Error deleting student.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// Add Search condition
$sql_search_condition = "";
if (!empty($search_query)) {
    $safe_search = $conn->real_escape_string($search_query);
    $sql_search_condition = " AND (name LIKE '%$safe_search%' OR email LIKE '%$safe_search%')";
}

// Final Query Execution
$sql = "SELECT * FROM users WHERE role='student' AND ($sql_sem_condition) $sql_search_condition ORDER BY class_name ASC, batch ASC, name ASC";
$students = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $year_title; ?> - KDP Admin</title>
    
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

        /* 🌐 TOPBAR (PREMIUM ADMIN PROFILE) */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 10px 22px 10px 28px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 17px; color: var(--text-main); font-weight: 900; letter-spacing: 0.3px;}
        .profile-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.4); letter-spacing: 1px;}

        /* 📦 CONTENT CARDS */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 25px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        .content-box:hover { box-shadow: var(--shadow-float); }
        .icon-box { width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white;}
        
        /* 🎛️ SMART FILTER PANEL & SEARCH */
        .filter-panel { background: #ffffff; padding: 20px 25px; border-radius: 16px; border: 1px solid #cbd5e1; box-shadow: 0 4px 15px rgba(0,0,0,0.02); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;}
        .filter-panel select.form-select { height: 45px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; color: var(--text-main); font-weight: 600; font-size: 14px; transition: var(--transition-bounce); box-shadow: none; cursor: pointer; min-width: 180px;}
        .filter-panel select.form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); background: #ffffff; }
        
        .search-wrapper { display: flex; height: 45px; border-radius: 10px; overflow: hidden; border: 1px solid #cbd5e1; background: #f8fafc; transition: var(--transition-bounce); min-width: 300px;}
        .search-wrapper:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); background: #ffffff;}
        .search-wrapper input { border: none; background: transparent; padding: 0 15px; font-weight: 600; font-size: 14px; width: 100%; outline: none; }
        .search-wrapper button { border: none; background: var(--primary); color: white; padding: 0 25px; font-weight: bold; transition: 0.2s; cursor: pointer; }
        .search-wrapper button:hover { background: var(--primary-hover); }

        /* TABLE */
        .table-custom th { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 18px 15px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14.5px; font-weight: 600; padding: 18px 15px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; transition: background-color 0.2s;}
        .table-custom tbody tr:hover td { background-color: #f8fafc; }
        
        .badge-class { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 800; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569;}
        .badge-batch { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 800; background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.2); color: #2563eb;}
        
        .btn-delete { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.2); padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 800; transition: var(--transition-bounce); display: inline-flex; align-items: center; gap: 6px; text-decoration: none;}
        .btn-delete:hover { background: #dc2626; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); }

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
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-pdf"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-inbox"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-double"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
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
                <a href="Student_Mgmt.php" class="btn btn-outline-secondary fw-bold" style="border-radius: 10px; padding: 8px 18px; border: 1px solid #cbd5e1;">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </a>
            </div>
            
            <div class="profile-pill" style="cursor: default;">
                <div class="profile-text">
                    <span class="profile-welcome">System Admin</span>
                    <h4 class="profile-name">
                        <?php 
                            $name_parts = explode(' ', $admin_name);
                            echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : htmlspecialchars($admin_name);
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar">AD</div>
            </div>
        </div>

        <?php echo $msg; ?>

        <!-- HEADER SECTION -->
        <div class="d-flex justify-content-between align-items-center mb-4 mt-2 page-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box" style="background: <?php echo $theme_color; ?>; box-shadow: 0 4px 15px <?php echo $theme_color; ?>40;">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);"><?php echo $year_title; ?></h3>
                    <p class="text-muted fw-semibold small mb-0">
                        <?php echo $is_global_search ? "Showing results from all semesters across the database." : "Search, filter, or manage students enrolled in this year."; ?>
                    </p>
                </div>
            </div>
            
            <?php if($is_global_search): ?>
                <a href="view_students.php?year=1" class="btn btn-outline-danger fw-bold px-4" style="border-radius: 10px; border-width: 2px;">
                    <i class="fas fa-times me-2"></i> Clear Search
                </a>
            <?php endif; ?>
        </div>

        <!-- 🎛️ SMART FILTER PANEL -->
        <div class="filter-panel">
            
            <!-- SEMESTER FILTER (Hidden in Global Search Mode) -->
            <?php if(!$is_global_search): ?>
            <div class="d-flex align-items-center gap-2">
                <label class="text-muted fw-bold small text-uppercase letter-spacing-1 mb-0"><i class="fas fa-filter me-1"></i> Filter By:</label>
                <form method="GET" action="view_students.php" class="m-0">
                    <input type="hidden" name="year" value="<?php echo $year; ?>">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                    <select name="sem" class="form-select text-primary" onchange="this.form.submit()">
                        <option value="all" <?php echo ($filter_sem == 'all') ? 'selected' : ''; ?>>All Semesters</option>
                        <option value="<?php echo $sem_labels[0]; ?>" <?php echo ($filter_sem == $sem_labels[0]) ? 'selected' : ''; ?>><?php echo $sem_labels[0]; ?></option>
                        <option value="<?php echo $sem_labels[1]; ?>" <?php echo ($filter_sem == $sem_labels[1]) ? 'selected' : ''; ?>><?php echo $sem_labels[1]; ?></option>
                    </select>
                </form>
            </div>
            <?php else: ?>
            <div class="text-primary fw-bold"><i class="fas fa-globe me-2"></i> Global Database Search Active</div>
            <?php endif; ?>

            <!-- LIVE SEARCH FORM FOR STUDENT-->
            <form method="GET" action="view_students.php" class="m-0">
                <?php if(!$is_global_search): ?>
                    <input type="hidden" name="year" value="<?php echo $year; ?>">
                    <input type="hidden" name="sem" value="<?php echo $filter_sem; ?>">
                <?php endif; ?>
                <div class="search-wrapper">
                    <input type="text" name="search" placeholder="Search by name or enrollment ID..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>

        <!-- 📋 TABLE SECTION FOR STUDENT -->
        <div class="content-box p-0">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="padding-left: 25px;">Student Information</th>
                            <th>Branch & Semester</th>
                            <th>Class & Batch</th>
                            <th class="text-end" style="padding-right: 25px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($students && $students->num_rows > 0): ?>
                            <?php while($row = $students->fetch_assoc()): ?>
                                <tr>
                                    <td style="padding-left: 25px;">
                                        <div class="fw-bold text-dark" style="font-size: 15px;"><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div class="text-primary fw-bold mt-1" style="font-size: 11.5px; letter-spacing: 0.5px;"><?php echo htmlspecialchars($row['email']); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 14px;">
                                            <?php 
                                                $dept = explode(' ', $row['department']);
                                                echo htmlspecialchars($dept[0]) . " Engineering"; 
                                            ?>
                                        </div>
                                        <div class="text-muted fw-bold small mt-1">
                                            <i class="fas fa-layer-group me-1"></i> <?php echo htmlspecialchars(!empty($row['semester']) ? $row['semester'] : $row['designation']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge-class"><i class="fas fa-chalkboard text-muted me-1"></i> Class <?php echo !empty($row['class_name']) ? htmlspecialchars($row['class_name']) : 'N/A'; ?></span>
                                            <span class="badge-batch"><i class="fas fa-users text-primary me-1"></i> Batch <?php echo !empty($row['batch']) ? htmlspecialchars($row['batch']) : 'N/A'; ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end" style="padding-right: 25px;">
                                        <a href="view_students.php?<?php echo $is_global_search ? "search=".urlencode($search_query) : "year=$year&sem=$filter_sem&search=".urlencode($search_query); ?>&delete_id=<?php echo $row['user_id']; ?>" 
                                           class="btn-delete" 
                                           onclick="return confirm('Are you sure you want to completely remove this student? This action cannot be undone.');">
                                            <i class="fas fa-trash-alt"></i> Remove
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="fas fa-search mb-3" style="font-size: 45px; opacity: 0.3; color: var(--primary);"></i><br>
                                    <h5 class="fw-bold text-dark mb-1">No matching students found</h5>
                                    <p class="small mb-0">Try checking your search spelling or adjusting the semester filter.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live Clock Script
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