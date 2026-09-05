<?php
// ========================================================================
// 0. AJAX API: DYNAMIC SUBJECT FETCHING (Runs in background without reload)
// ========================================================================
if (isset($_GET['ajax_subjects'])) {
    include '../db.php';
    $branch = $conn->real_escape_string($_GET['branch']);
    $semester = $conn->real_escape_string($_GET['semester']);
    
    $query = "SELECT DISTINCT subject_name FROM subjects WHERE department = '$branch' AND semester = '$semester' ORDER BY subject_name ASC";
    $res = $conn->query($query);
    
    echo '<option value="" selected disabled>-- Select Subject --</option>';
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            echo '<option value="' . htmlspecialchars($row['subject_name']) . '">' . htmlspecialchars($row['subject_name']) . '</option>';
        }
    } else {
        echo '<option value="" disabled>❌ No Subjects Found</option>';
    }
    exit();
}
// ====================================================================

session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data['name'] ?? 'Admin';

// ==========================================
// 🛠️ SMART FALLBACK & DB AUTO-FIX (Includes end_date)
// ==========================================
$conn->query("CREATE TABLE IF NOT EXISTS lab_manuals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    semester VARCHAR(50) NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    end_date DATE NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Check and Add missing columns automatically
$check_cols = $conn->query("SHOW COLUMNS FROM lab_manuals LIKE 'end_date'");
if ($check_cols && $check_cols->num_rows == 0) {
    @$conn->query("ALTER TABLE lab_manuals ADD COLUMN department VARCHAR(100) NULL AFTER title");
    @$conn->query("ALTER TABLE lab_manuals ADD COLUMN semester VARCHAR(50) NULL AFTER department");
    @$conn->query("ALTER TABLE lab_manuals ADD COLUMN end_date DATE NULL AFTER file_path");
}

$message = "";

// ==========================================
// 🚀 UPLOAD NEW LAB MANUAL LOGIC (With Deadline)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_manual'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $department = $conn->real_escape_string($_POST['department']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $subject_name = $conn->real_escape_string($_POST['subject_name']);
    $end_date = !empty($_POST['end_date']) ? $conn->real_escape_string($_POST['end_date']) : NULL;
    $end_date_val = $end_date ? "'$end_date'" : "NULL";
    
    // FILE UPLOAD HANDLING
    if (isset($_FILES['manual_file']) && $_FILES['manual_file']['error'] == 0) {
        $upload_dir = '../uploads/lab_manuals/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['manual_file']['name']));
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['manual_file']['tmp_name'], $target_file)) {
            $db_path = 'uploads/lab_manuals/' . $file_name; 
            
            $sql = "INSERT INTO lab_manuals (title, department, semester, subject_name, file_path, end_date) 
                    VALUES ('$title', '$department', '$semester', '$subject_name', '$db_path', $end_date_val)";
                    
            if ($conn->query($sql)) {
                $message = "<div class='alert alert-success alert-dismissible fade show mb-4' style='border-radius:10px; font-weight:600;'><i class='fas fa-check-circle me-2'></i>Lab Manual Uploaded Successfully with Deadline!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $message = "<div class='alert alert-danger alert-dismissible fade show mb-4' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i>Database Error: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show mb-4' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i>Failed to move uploaded file. Check folder permissions.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } else {
        $message = "<div class='alert alert-danger alert-dismissible fade show mb-4' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i>Please select a valid file to upload.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// 🗑️ DELETE LAB MANUAL LOGIC
// ==========================================
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    
    $get_file = $conn->query("SELECT file_path FROM lab_manuals WHERE id='$del_id'");
    if($get_file && $get_file->num_rows > 0) {
        $file_data = $get_file->fetch_assoc();
        $full_path = '../' . $file_data['file_path'];
        if(file_exists($full_path)) {
            unlink($full_path); 
        }
    }
    
    $conn->query("DELETE FROM lab_manuals WHERE id='$del_id'");
    header("Location: Lab_Manuals.php");
    exit();
}

// FETCH SEARCH RESULTS
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql_search = "";
if (!empty($search_query)) {
    $safe_search = $conn->real_escape_string($search_query);
    $sql_search = " WHERE title LIKE '%$safe_search%' OR subject_name LIKE '%$safe_search%' OR department LIKE '%$safe_search%' ";
}

$manuals_list = $conn->query("SELECT * FROM lab_manuals $sql_search ORDER BY uploaded_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Manuals - Admin Portal</title>
    
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
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);}

        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 30px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        .content-box:hover { box-shadow: var(--shadow-float); }
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 15px; }
        .icon-box { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 26px; }
        .blue-box { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }

        .btn-gradient { background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; border: none; font-weight: 700; padding: 12px 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); transition: var(--transition-bounce); }
        .btn-gradient:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4); color: white; }
        .btn-outline-modern { background: white; color: var(--text-main); font-weight: 700; padding: 10px 18px; border-radius: 10px; border: 1px solid #cbd5e1; transition: var(--transition-bounce); }
        .btn-outline-modern:hover { background: #f8fafc; transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }

        input.form-control, select.form-select { border-radius: 10px; padding: 12px; border: 1px solid #cbd5e1; font-weight: 500; font-size: 14px; transition: var(--transition-bounce); }
        input.form-control:focus, select.form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); transform: scale(1.01); }

        .search-modern { border-radius: 10px 0 0 10px !important; border: 1px solid #cbd5e1; font-weight: 500; font-size: 14px; padding: 10px 18px;}
        .search-modern:focus { box-shadow: none; border-color: var(--primary); }
        .btn-search { border-radius: 0 10px 10px 0 !important; background: var(--primary); color: white; padding: 0 20px; font-weight: 700; border: none; transition: var(--transition-bounce);}
        .btn-search:hover { background: var(--primary-hover); }

        .table-custom th { background: transparent; font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; }
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; transition: background-color 0.2s; }
        .table-custom tbody tr:hover td { background-color: #f8fafc; }
        
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .bg-primary-soft { background: rgba(59, 130, 246, 0.1); color: #1d4ed8; }
        
        .btn-action-view { background: rgba(16, 185, 129, 0.1); color: #059669; border: none; padding: 8px 12px; border-radius: 8px; transition: var(--transition-bounce); margin-right: 5px; text-decoration: none;}
        .btn-action-view:hover { background: #059669; color: white; transform: scale(1.05); }
        .btn-action-delete { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: none; padding: 8px 12px; border-radius: 8px; transition: var(--transition-bounce); }
        .btn-action-delete:hover { background: #ef4444; color: white; transform: scale(1.05); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        @media print {
            body { background: white !important; }
            .sidebar, .topbar, .page-header, .add-form-col, .action-col, .btn-action-delete { display: none !important; }
            .main { padding: 0 !important; margin: 0 !important; animation: none !important; height: auto !important; overflow: visible !important; }
            .table-col { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
            .content-box { border: none !important; box-shadow: none !important; padding: 0 !important; }
        }
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
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li class="active" onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-pdf"></i> Lab Manuals</li>
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
                
                <form action="" method="GET" class="d-flex shadow-sm" style="border-radius: 10px;">
                    <input type="text" name="search" class="form-control search-modern px-4" placeholder="Search manuals..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 250px;">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                </form>

                <button class="btn-outline-modern ms-3" onclick="window.print()" title="Print List">
                    <i class="fas fa-print text-primary me-2"></i> Print List
                </button>
                
                <button class="btn-outline-modern ms-2" onclick="exportTableToCSV('Lab_Manuals_List.csv')" title="Download Excel File">
                    <i class="fas fa-file-excel text-success me-2"></i> Export
                </button>
            </div>
            
            <a href="Profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">K.D. Polytechnic</span>
                    <h4 class="profile-name">
                        <?php 
                            $name_parts = explode(' ', $admin_name);
                            echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : 'Admin';
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar">AD</div>
            </a>
        </div>

        <?php echo $message; // Upload Alerts ?>

        <!-- PAGE HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-5 mt-2 page-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box blue-box"><i class="fas fa-file-pdf"></i></div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);">Lab Manuals Repository</h3>
                    <p class="text-muted fw-semibold small mb-0">Upload official lab manuals, set deadlines, and manage repository.</p>
                </div>
            </div>
            <?php if(!empty($search_query)): ?>
            <a href="Lab_Manuals.php" class="btn btn-outline-danger fw-bold" style="border-radius: 10px; padding: 10px 20px;">
                <i class="fas fa-times me-1"></i> Clear Search
            </a>
            <?php endif; ?>
        </div>

        <!-- TWO COLUMN LAYOUT -->
        <div class="row g-4">
            
            <!-- LEFT COLUMN: UPLOAD FORM -->
            <div class="col-md-4 add-form-col">
                <div class="content-box">
                    <h5 class="box-title"><i class="fas fa-cloud-upload-alt text-primary me-2"></i> Upload Lab Manual</h5>
                    <hr class="mb-4" style="border-color: #e2e8f0;">
                    
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Manual Title</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. DBMS Practical 1-10">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Branch / Department</label>
                            <select name="department" id="department" class="form-select" required onchange="fetchSubjects()">
                                <option value="" selected disabled>Select Branch</option>
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="IT Engineering">IT Engineering</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Semester</label>
                            <select name="semester" id="semester" class="form-select" required onchange="fetchSubjects()">
                                <option value="" selected disabled>Select Semester</option>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                                <option value="3">Semester 3</option>
                                <option value="4">Semester 4</option>
                                <option value="5">Semester 5</option>
                                <option value="6">Semester 6</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Subject</label>
                            <select name="subject_name" id="subject_name" class="form-select" required>
                                <option value="" selected disabled>-- Select Branch & Sem First --</option>
                            </select>
                        </div>

                        <!-- 📅 DEADLINE FIELD ADDED -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Submission Deadline</label>
                            <input type="date" name="end_date" class="form-control" style="padding: 10px 12px;">
                            <small class="text-muted" style="font-size: 11px;">Optional. Leave blank if no strict due date.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Upload File (PDF/DOC)</label>
                            <input type="file" name="manual_file" class="form-control" required accept=".pdf,.doc,.docx" style="padding: 9px 12px;">
                        </div>
                        
                        <button type="submit" name="upload_manual" class="btn-gradient w-100 mt-2">
                            <i class="fas fa-upload me-2"></i> Upload Manual
                        </button>
                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN: MANUALS TABLE -->
            <div class="col-md-8 table-col">
                <div class="content-box h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="box-title mb-0"><i class="fas fa-folder-open text-success me-2"></i> Uploaded Manuals</h5>
                        <span class="badge bg-primary text-white action-col" style="border-radius: 20px; padding: 5px 12px;">Total: <?php echo $manuals_list ? $manuals_list->num_rows : 0; ?></span>
                    </div>
                    <hr class="mb-4 action-col" style="border-color: #e2e8f0;">
                    
                    <div class="table-responsive">
                        <table class="table table-custom mb-0" id="manualTable">
                            <thead>
                                <tr>
                                    <th>Manual Details</th>
                                    <th>Subject & Dept</th>
                                    <th>Important Dates</th>
                                    <th class="text-end action-col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($manuals_list && $manuals_list->num_rows > 0): ?>
                                    <?php while($row = $manuals_list->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-danger text-white rounded p-2 text-center" style="width:40px; height:40px;">
                                                        <i class="fas fa-file-pdf fs-5"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold" style="font-size: 14.5px; color: var(--text-main);"><?php echo htmlspecialchars($row['title']); ?></div>
                                                        <span class="badge-modern bg-primary-soft mt-1">Sem <?php echo htmlspecialchars($row['semester'] ?? 'N/A'); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold" style="font-size: 13.5px; color: var(--primary);"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                                                <div class="text-muted fw-semibold mt-1" style="font-size: 11.5px;"><?php echo htmlspecialchars($row['department'] ?? 'General'); ?></div>
                                            </td>
                                            <td>
                                                <div style="font-size: 12px; color: var(--text-main);" class="fw-semibold">
                                                    <i class="fas fa-upload text-muted me-1"></i> <?php echo date('d M Y', strtotime($row['uploaded_at'])); ?><br>
                                                    <i class="fas fa-flag-checkered text-danger me-1 mt-1"></i> 
                                                    <?php echo (!empty($row['end_date']) && $row['end_date'] != '0000-00-00') ? date('d M Y', strtotime($row['end_date'])) : '<span class="text-muted">No Deadline</span>'; ?>
                                                </div>
                                            </td>
                                            <td class="text-end action-col">
                                                <a href="../<?php echo $row['file_path']; ?>" target="_blank" class="btn-action-view" title="View PDF">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="Lab_Manuals.php?delete_id=<?php echo $row['id']; ?>" 
                                                   class="btn-action-delete" 
                                                   title="Delete Manual"
                                                   onclick="return confirm('Are you sure you want to delete this manual? The file will be permanently removed.');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5 action-col">
                                            <i class="fas fa-box-open mb-3" style="font-size: 40px; color: #cbd5e1;"></i><br>
                                            <span class="fw-bold fs-5 text-dark">No manuals uploaded yet.</span><br>
                                            <small class="fw-semibold">Use the form on the left to upload lab manuals.</small>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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

        function fetchSubjects() {
            var branch = document.getElementById('department').value;
            var semester = document.getElementById('semester').value;
            var subjectDropdown = document.getElementById('subject_name');

            if(branch && semester) {
                subjectDropdown.innerHTML = '<option value="">Loading subjects...</option>';
                
                fetch('Lab_Manuals.php?ajax_subjects=1&branch=' + encodeURIComponent(branch) + '&semester=' + encodeURIComponent(semester))
                .then(response => response.text())
                .then(data => {
                    subjectDropdown.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error fetching subjects:', error);
                    subjectDropdown.innerHTML = '<option value="">Error loading subjects</option>';
                });
            } else {
                subjectDropdown.innerHTML = '<option value="" selected disabled>-- Select Branch & Sem First --</option>';
            }
        }

        function exportTableToCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("#manualTable tr");
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                
                for (var j = 0; j < cols.length - 1; j++) {
                    var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " - ");
                    row.push('"' + data + '"');
                }
                csv.push(row.join(","));
            }

            var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
            var downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }
    </script>
</body>
</html>