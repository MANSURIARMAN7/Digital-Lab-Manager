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

// Determine which year to show (Default to 1st Year)
$year = isset($_GET['year']) ? (int)$_GET['year'] : 1;

// Get Filtered Semester & Search Query
$filter_sem = isset($_GET['sem']) ? $_GET['sem'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Year and Semester Configuration
if ($year == 1) {
    $allowed_sems = ['Semester 1', 'Semester 2'];
    $year_title = "1st Year Students";
    $theme_color = "#2563eb"; 
} elseif ($year == 2) {
    $allowed_sems = ['Semester 3', 'Semester 4'];
    $year_title = "2nd Year Students";
    $theme_color = "#10b981"; 
} elseif ($year == 3) {
    $allowed_sems = ['Semester 5', 'Semester 6'];
    $year_title = "3rd Year Students";
    $theme_color = "#f59e0b"; 
} else {
    $year = 1;
    $allowed_sems = ['Semester 1', 'Semester 2'];
    $year_title = "1st Year Students";
    $theme_color = "#2563eb";
}

// ==========================================
// 🗑️ DELETE STUDENT LOGIC
// ==========================================
$msg = "";
if (isset($_GET['delete_id'])) {
    $del_id = $conn->real_escape_string($_GET['delete_id']);
    if ($conn->query("DELETE FROM users WHERE user_id='$del_id' AND role='student'")) {
        $msg = "<div class='alert alert-success alert-dismissible fade show' style='border-radius: 8px;'>Student successfully removed from the system.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show'>Error deleting student.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// 🗄️ FETCH STUDENTS WITH SEM & SEARCH QUERY
// ==========================================
if ($filter_sem !== 'all' && in_array($filter_sem, $allowed_sems)) {
    $sql_sem_condition = "semester = '" . $conn->real_escape_string($filter_sem) . "'";
} else {
    $sql_sem_condition = "semester IN ('" . $allowed_sems[0] . "', '" . $allowed_sems[1] . "')";
}

// Add Search condition if user typed something
$sql_search_condition = "";
if (!empty($search_query)) {
    $safe_search = $conn->real_escape_string($search_query);
    $sql_search_condition = " AND (name LIKE '%$safe_search%' OR email LIKE '%$safe_search%')";
}

$sql = "SELECT * FROM users WHERE role='student' AND ($sql_sem_condition) $sql_search_condition ORDER BY class_name ASC, batch ASC, name ASC";
$students = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $year_title; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --sidebar-width: 260px; --bg-color: #f4f7fe; --sidebar-bg: #1a365d; --accent-blue: #2563eb; }
        body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        .sidebar { width: var(--sidebar-width); background-color: var(--sidebar-bg); color: #ffffff; display: flex; flex-direction: column; z-index: 10; overflow-y: auto; }
        .sidebar-logo-container { padding: 30px 20px 20px 20px; display: flex; flex-direction: column; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; }
        .sidebar-logo-container img { width: 90px; height: 90px; object-fit: contain; margin-bottom: 15px; border-radius: 50%; padding: 5px; background: rgba(255,255,255,0.1); }
        .sidebar-title h2 { font-size: 18px; font-weight: 700; margin: 0; line-height: 1.2; letter-spacing: 0.5px; color: #fff;}
        .sidebar-subtitle { font-size: 13px; color: #94a3b8; margin-top: 5px; font-weight: 500;}
        .nav-links { list-style: none; padding: 20px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 12px 20px; margin: 5px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 500; color: #a0aec0; transition: all 0.3s ease; }
        .nav-links li:hover { color: white; background: rgba(255,255,255,0.08); }
        .nav-links li.active { background: var(--accent-blue); color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4); font-weight: 600; }
        .main { flex: 1; padding: 30px 40px; overflow-y: auto; }
        
        .topbar { background: transparent; padding: 0 0 10px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px;}
        .profile-pill { display: flex; align-items: center; background-color: #ffffff; padding: 6px 16px 6px 20px; border-radius: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; cursor: pointer; text-decoration: none; color: inherit; transition: all 0.2s;}
        .profile-text { text-align: right; margin-right: 15px; }
        .profile-welcome { display: block; font-size: 9.5px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 14px; color: #1e293b; font-weight: 700; }
        .profile-avatar { width: 42px; height: 42px; background-color: var(--accent-blue); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; box-shadow: 0 3px 8px rgba(37, 99, 235, 0.4); letter-spacing: 1px;}

        /* TABLE STYLE */
        .content-box { background: white; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
        .table-custom th { background: #f8fafc; font-size: 13px; font-weight: 700; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; padding: 15px; }
        .table-custom td { vertical-align: middle; font-size: 14.5px; padding: 15px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .badge-class { padding: 5px 12px; border-radius: 6px; font-size: 12.5px; font-weight: 700; background: #f1f5f9; border: 1px solid #e2e8f0; color: #475569;}
        .badge-batch { padding: 5px 12px; border-radius: 6px; font-size: 12.5px; font-weight: 700; background: rgba(37,99,235,0.1); border: 1px solid rgba(37,99,235,0.2); color: #2563eb;}
        .btn-delete { border-radius: 6px; padding: 5px 12px; font-weight: 600; font-size: 13px; transition: 0.2s;}
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-alt"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-folder-open"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-circle"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li onclick="window.location.href='Expense Mgmt.php'"><i class="fas fa-wallet"></i> Expense Mgmt</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        
        <div class="topbar mb-4">
            <div>
                <a href="Student_Mgmt.php" class="btn btn-sm btn-light" style="font-weight: 600; border: 1px solid #e2e8f0; padding: 8px 15px; border-radius: 8px;">
                    <i class="fas fa-arrow-left me-2"></i> Back to Management
                </a>
            </div>
            
            <a href="Profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Welcome Back,</span>
                    <h4 class="profile-name">
                        <?php 
                            $name_parts = explode(' ', $admin_name);
                            echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : 'Admin';
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar">HOD</div>
            </a>
        </div>

        <?php echo $msg; ?>

        <!-- HEADER & SEARCH + FILTER BAR -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-1" style="color: <?php echo $theme_color; ?> !important; font-size: 24px;">
                    <i class="fas fa-users me-2"></i> <?php echo $year_title; ?>
                </h3>
                <p class="text-muted small mb-0">Search, filter, or manage students enrolled in this year.</p>
            </div>
            
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- LIVE SEARCH FORM -->
                <form method="GET" action="view_students.php" class="d-flex">
                    <input type="hidden" name="year" value="<?php echo $year; ?>">
                    <input type="hidden" name="sem" value="<?php echo $filter_sem; ?>">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search name or ID..." value="<?php echo htmlspecialchars($search_query); ?>" style="border-radius: 8px 0 0 8px; border: 2px solid #e2e8f0; font-size: 14px;">
                        <button type="submit" class="btn btn-primary" style="border-radius: 0 8px 8px 0;"><i class="fas fa-search"></i></button>
                    </div>
                </form>

                <!-- SEMESTER FILTER -->
                <form method="GET" action="view_students.php">
                    <input type="hidden" name="year" value="<?php echo $year; ?>">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                    <select name="sem" class="form-select fw-bold text-primary" style="border-radius: 8px; border: 2px solid #e2e8f0; cursor:pointer;" onchange="this.form.submit()">
                        <option value="all" <?php echo ($filter_sem == 'all') ? 'selected' : ''; ?>>All Semesters</option>
                        <option value="<?php echo $allowed_sems[0]; ?>" <?php echo ($filter_sem == $allowed_sems[0]) ? 'selected' : ''; ?>><?php echo $allowed_sems[0]; ?></option>
                        <option value="<?php echo $allowed_sems[1]; ?>" <?php echo ($filter_sem == $allowed_sems[1]) ? 'selected' : ''; ?>><?php echo $allowed_sems[1]; ?></option>
                    </select>
                </form>
            </div>
        </div>

        <!-- TABLE SECTION -->
        <div class="content-box">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Enrollment No / Email</th>
                            <th>Branch</th>
                            <th>Semester</th>
                            <th>Class</th>
                            <th>Batch</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($students && $students->num_rows > 0): ?>
                            <?php while($row = $students->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="text-secondary fw-medium"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td>
                                        <?php 
                                            $dept = explode(' ', $row['department']);
                                            echo htmlspecialchars($dept[0]) . " Engg."; 
                                        ?>
                                    </td>
                                    <td class="fw-semibold text-primary"><?php echo htmlspecialchars($row['semester']); ?></td>
                                    <td><span class="badge-class">Class <?php echo !empty($row['class_name']) ? htmlspecialchars($row['class_name']) : 'N/A'; ?></span></td>
                                    <td><span class="badge-batch">Batch <?php echo !empty($row['batch']) ? htmlspecialchars($row['batch']) : 'N/A'; ?></span></td>
                                    <td class="text-end">
                                        <a href="view_students.php?year=<?php echo $year; ?>&sem=<?php echo $filter_sem; ?>&search=<?php echo urlencode($search_query); ?>&delete_id=<?php echo $row['user_id']; ?>" 
                                           class="btn btn-outline-danger btn-delete" 
                                           onclick="return confirm('Are you sure you want to remove this student?');">
                                            <i class="fas fa-trash-alt me-1"></i> Remove
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-search mb-3" style="font-size: 40px; color: #cbd5e1;"></i><br>
                                    <span class="fw-medium fs-5">No matching students found.</span><br>
                                    <small>Try checking your search spelling or clearing filters.</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>