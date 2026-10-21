<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch Admin details
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data['name'] ?? 'System Administrator';

// Set Parameters
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_sem = isset($_GET['sem']) ? $_GET['sem'] : 'All';

// FIXED: Check if it is a Global search (Search exists, but Year is missing)
$is_global_search = !isset($_GET['year']) && !empty($search_query);

if ($is_global_search) {
    // --- GLOBAL SEARCH MODE ---
    $year = 0;
    $year_title = "Global Search Results";
    $theme_color = "#3b82f6"; // Indigo color for Global Search
    $sql_sem_condition = "1=1"; // Match All Semesters
} else {
    // --- NORMAL YEAR FILTER MODE ---
    $year = isset($_GET['year']) ? (int)$_GET['year'] : 1;

    // FIXED: Robust Semester Checking
    if ($year == 1) {
        $sem_labels = ['Semester 1', 'Semester 2'];
        $sem_values = ['1', '2', 'Semester 1', 'Semester 2', '1', '2'];
        $year_title = "1st Year Students";
        $theme_color = "#3b82f6"; // Blue
    } elseif ($year == 2) {
        $sem_labels = ['Semester 3', 'Semester 4'];
        $sem_values = ['3', '4', 'Semester 3', 'Semester 4', '3', '4'];
        $year_title = "2nd Year Students";
        $theme_color = "#10b981"; // Green
    } elseif ($year == 3) {
        $sem_labels = ['Semester 5', 'Semester 6'];
        $sem_values = ['5', '6', 'Semester 5', 'Semester 6', '5', '6'];
        $year_title = "3rd Year Students";
        $theme_color = "#f59e0b"; // Yellow
    } else {
        $year = 1;
        $sem_labels = ['Semester 1', 'Semester 2'];
        $sem_values = ['1', '2', 'Semester 1', 'Semester 2', '1', '2'];
        $year_title = "1st Year Students";
        $theme_color = "#3b82f6";
    }

    if ($filter_sem == $sem_labels[0]) {
        $sql_sem_condition = "(semester IN ($sem_values) OR designation IN ($sem_values))";
    } elseif ($filter_sem == $sem_labels[1]) {
        $sql_sem_condition = "(semester IN ($sem_values) OR designation IN ($sem_values))";
    } else {
        $sql_sem_condition = "(semester IN ($sem_values) OR designation IN ($sem_values))";
    }
}

// 🗑️ DELETE STUDENT LOGIC
$msg = "";
if (isset($_GET['delete_id'])) {
    $del_id = $conn->real_escape_string($_GET['delete_id']);
    if ($conn->query("DELETE FROM users WHERE user_id='$del_id' AND role='student'")) {
        $msg = "<div class='alert alert-success alert-dismissible fade show mb-4 shadow-sm' style='border-radius: 10px; font-weight: 600;'><i class='fas fa-check-circle me-2'></i> Student successfully removed from the system.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show mb-4 shadow-sm' style='border-radius: 10px; font-weight: 600;'><i class='fas fa-exclamation-triangle me-2'></i> Error deleting student.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// 🔍 SEARCH CONDITION
$sql_search_condition = "";
if (!empty($search_query)) {
    $safe_search = $conn->real_escape_string($search_query);
    $sql_search_condition = " AND (name LIKE '%$safe_search%' OR email LIKE '%$safe_search%' OR user_id LIKE '%$safe_search%')";
}

// Final Query Execution
$sql = "SELECT * FROM users WHERE role='student' AND ($sql_sem_condition) $sql_search_condition ORDER BY name ASC";
$students = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($year_title); ?> - Admin Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 270px;
            --primary: #4338ca;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        /* 🔥 PREMIUM MICROSOFT UNIFIED SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 13px; color: #bfdbfe; margin-top: 5px; font-weight: 500;} 
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .page-header { background: <?php echo $theme_color; ?>; color: white; padding: 30px; border-radius: 16px; margin-bottom: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 20px;}
        .header-icon { width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px; }

        .filter-panel { background: white; padding: 20px; border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;}
        
        .search-wrapper { position: relative; width: 300px; }
        .search-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-wrapper input { width: 100%; padding: 10px 15px 10px 40px; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 600; font-size: 14px;}
        .search-wrapper input:focus { border-color: var(--primary); outline: none; background: white; box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);}

        .content-box { background: var(--surface); border-radius: 16px; padding: 0; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 25px;}
        
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; }
        
        .btn-delete { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; transition: 0.2s; }
        .btn-delete:hover { background: #dc2626; color: white; transform: translateY(-2px); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
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
            <li onclick="window.location.href='Profile.php'"><i class="fas fa-user-shield"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- HEADER -->
        <div class="page-header">
            <div class="header-icon"><i class="fas <?php echo $is_global_search ? 'fa-globe' : 'fa-user-graduate'; ?>"></i></div>
            <div>
                <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($year_title); ?></h3>
                <p class="mb-0 fw-medium opacity-75">View and manage student records</p>
            </div>
            <div class="ms-auto">
                <a href="Student_Mgmt.php" class="btn btn-light fw-bold text-dark px-4 py-2" style="border-radius: 10px;"><i class="fas fa-arrow-left me-2"></i> Back to Mgmt</a>
            </div>
        </div>

        <?php echo $msg; ?>

        <!-- FILTER PANEL -->
        <div class="filter-panel">
            <?php if (!$is_global_search): ?>
                <form method="GET" class="d-flex align-items-center gap-2 m-0">
                    <input type="hidden" name="year" value="<?php echo $year; ?>">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                    <label class="fw-bold text-muted small text-uppercase me-2"><i class="fas fa-filter me-1"></i> Filter by:</label>
                    <select name="sem" class="form-select fw-bold bg-light" style="width: 200px;" onchange="this.form.submit()">
                        <option value="All" <?php echo ($filter_sem == 'All') ? 'selected' : ''; ?>>All Semesters</option>
                        <option value="<?php echo $sem_labels[0]; ?>" <?php echo ($filter_sem == $sem_labels[0]) ? 'selected' : ''; ?>><?php echo $sem_labels[0]; ?></option>
                        <option value="<?php echo $sem_labels[1]; ?>" <?php echo ($filter_sem == $sem_labels[1]) ? 'selected' : ''; ?>><?php echo $sem_labels[1]; ?></option>
                    </select>
                </form>
            <?php else: ?>
                <div class="text-primary fw-bold"><i class="fas fa-globe ms-2"></i> Global Database Search Active</div>
            <?php endif; ?>

            <!-- LIVE SEARCH FORM -->
            <form method="GET" class="m-0">
                <?php if (!$is_global_search): ?>
                    <input type="hidden" name="year" value="<?php echo $year; ?>">
                    <input type="hidden" name="sem" value="<?php echo htmlspecialchars($filter_sem); ?>">
                <?php endif; ?>
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" placeholder="Search by Name, Email or ID..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
            </form>
        </div>

        <!-- TABLE SECTION -->
        <div class="content-box">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4 py-3">Student Information</th>
                            <th class="py-3">Branch & Semester</th>
                            <th class="text-end pe-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($students && $students->num_rows > 0): ?>
                            <?php while($row = $students->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark" style="font-size: 14.5px;"><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div class="text-primary fw-bold mt-1" style="font-size: 12px; letter-spacing: 0.5px;"><i class="fas fa-id-badge me-1"></i> <?php echo htmlspecialchars($row['user_id']); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 14px;">
                                            <?php 
                                                $dept = explode(' ', $row['department']);
                                                echo htmlspecialchars($dept[0]) . " Engineering";
                                            ?>
                                        </div>
                                        <div class="text-muted fw-bold small mt-1">
                                            <i class="fas fa-layer-group me-1"></i> Sem: <?php echo htmlspecialchars(empty($row['semester']) ? $row['designation'] : $row['semester']); ?>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="?search=<?php echo urlencode($search_query); ?>&year=<?php echo $year; ?>&sem=<?php echo urlencode($filter_sem); ?>&delete_id=<?php echo $row['user_id']; ?>" 
                                           class="btn-delete" 
                                           onclick="return confirm('Are you sure you want to remove this student? This action cannot be undone.');" title="Remove Student">
                                            <i class="fas fa-trash-alt"></i> Remove
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">
                                    <i class="fas fa-search mb-3" style="font-size: 40px; opacity: 0.3; color: var(--primary);"></i><br>
                                    <span class="fw-bold text-dark fs-5">No matching students found</span><br>
                                    <span class="small opacity-75">Try checking your search spelling or adjusting the semester filter.</span>
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