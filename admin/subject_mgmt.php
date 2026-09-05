<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch Admin Details for Profile
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data['name'] ?? 'System Administrator';

// Ensure subjects table exists with faculty_name and subject_code columns
$conn->query("CREATE TABLE IF NOT EXISTS subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(100) DEFAULT NULL,
    subject_name VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    semester INT NOT NULL,
    faculty_name VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$message = "";

// ==========================================
// 🚀 ADD NEW SUBJECT LOGIC (With Auto-Column Creation)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    $subject_code = $conn->real_escape_string($_POST['subject_code']);
    $subject_name = $conn->real_escape_string($_POST['subject_name']);
    $department = $conn->real_escape_string($_POST['department']);
    $semester = (int)$_POST['semester'];
    $faculty_name = $conn->real_escape_string($_POST['faculty_name']);
    
    $sql = "INSERT INTO subjects (subject_code, subject_name, department, semester, faculty_name) 
            VALUES ('$subject_code', '$subject_name', '$department', '$semester', '$faculty_name')";
            
    if ($conn->query($sql)) {
        $message = "<div class='alert alert-success alert-dismissible fade show mb-4' style='border-radius:10px; font-weight:600;' role='alert'><i class='fas fa-check-circle me-2'></i>Subject added and assigned successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        // SMART FALLBACK: If subject_code column is missing in old DB, create it and retry!
        if(strpos($conn->error, "subject_code") !== false) {
            $conn->query("ALTER TABLE subjects ADD COLUMN subject_code VARCHAR(100) NULL AFTER subject_id");
            if($conn->query($sql)) {
                $message = "<div class='alert alert-success alert-dismissible fade show mb-4' style='border-radius:10px; font-weight:600;' role='alert'><i class='fas fa-check-circle me-2'></i>Subject added successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $message = "<div class='alert alert-danger alert-dismissible fade show mb-4' style='border-radius:10px;' role='alert'>Error: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show mb-4' style='border-radius:10px;' role='alert'>Error: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// ==========================================
// 🗑️ DELETE SUBJECT LOGIC
// ==========================================
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM subjects WHERE subject_id='$del_id'");
    header("Location: subject_mgmt.php");
    exit();
}

// Fetch Faculty list for dropdown assignment
$faculty_list = $conn->query("SELECT name FROM users WHERE role = 'faculty' ORDER BY name ASC");

// ==========================================
// 🔍 SEARCH & FETCH SUBJECTS LIST
// ==========================================
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql_search = "";

if (!empty($search_query)) {
    $safe_search = $conn->real_escape_string($search_query);
    $sql_search = " WHERE subject_code LIKE '%$safe_search%' OR subject_name LIKE '%$safe_search%' OR department LIKE '%$safe_search%' OR faculty_name LIKE '%$safe_search%' ";
}

$subjects_list = $conn->query("SELECT * FROM subjects $sql_search ORDER BY semester ASC, subject_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management - Admin</title>
    
    <!-- Bootstrap, FontAwesome & PREMIUM GOOGLE FONT -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- 🚀 PREMIUM MODERN UI CSS -->
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

        body { 
            background-color: var(--bg-body); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main);
        }
        
        /* 🔥 PREMIUM BLUE SIDEBAR */
        .sidebar { 
            width: var(--sidebar-width); 
            background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%);
            color: #ffffff; display: flex; flex-direction: column; z-index: 10; overflow-y: auto; 
            box-shadow: 4px 0 24px rgba(0,0,0,0.08);
        }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0; letter-spacing: 0.5px; color: #ffffff;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { 
            padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; 
            font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); border-left: 3px solid transparent;
        }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .nav-links li.active { 
            background: rgba(255, 255, 255, 0.2); 
            color: #ffffff; border-left: 4px solid #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        /* ✨ MAIN CONTENT ANIMATION */
        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* 🌈 TOPBAR & PROFILE PILL */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { 
            display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; 
            border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; 
            transition: var(--transition-bounce); box-shadow: var(--shadow-float);
        }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);}

        /* 📦 CONTENT BOXES */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 30px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        .content-box:hover { box-shadow: var(--shadow-float); }
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 15px; }
        
        .icon-box { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 26px; }
        .blue-box { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }

        /* 🚀 PREMIUM BUTTONS & INPUTS */
        .btn-gradient { 
            background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; border: none; font-weight: 700; padding: 12px 20px; border-radius: 10px; 
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); transition: var(--transition-bounce);
        }
        .btn-gradient:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4); color: white; }
        
        .btn-outline-modern {
            background: white; color: var(--text-main); font-weight: 700; padding: 10px 18px; border-radius: 10px; border: 1px solid #cbd5e1; transition: var(--transition-bounce);
        }
        .btn-outline-modern:hover { background: #f8fafc; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }

        input.form-control, select.form-select { border-radius: 10px; padding: 12px; border: 1px solid #cbd5e1; font-weight: 500; font-size: 14px; transition: var(--transition-bounce); }
        input.form-control:focus, select.form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); transform: scale(1.01); }

        /* SEARCH INPUT */
        .search-modern { border-radius: 10px 0 0 10px !important; border: 1px solid #cbd5e1; font-weight: 500; font-size: 14px; padding: 10px 18px;}
        .search-modern:focus { box-shadow: none; border-color: var(--primary); }
        .btn-search { border-radius: 0 10px 10px 0 !important; background: var(--primary); color: white; padding: 0 20px; font-weight: 700; border: none; transition: var(--transition-bounce);}
        .btn-search:hover { background: var(--primary-hover); }

        /* 📋 SLEEK TABLES */
        .table-custom th { background: transparent; font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; }
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; transition: background-color 0.2s; }
        .table-custom tbody tr:hover td { background-color: #f8fafc; }
        
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .bg-primary-soft { background: rgba(59, 130, 246, 0.1); color: #1d4ed8; }
        
        .btn-action-delete { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: none; padding: 8px 12px; border-radius: 8px; transition: var(--transition-bounce); }
        .btn-action-delete:hover { background: #ef4444; color: white; transform: scale(1.05); }

        /* SCROLLBAR */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* 🖨️ PRINT MEDIA QUERY - Sirf Table Print Hogi */
        @media print {
            body { background: white !important; }
            .sidebar, .topbar, .page-header, .add-form-col, .action-col, .btn-action-delete { display: none !important; }
            .main { padding: 0 !important; margin: 0 !important; animation: none !important; height: auto !important; overflow: visible !important; }
            .table-col { width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important; }
            .content-box { border: none !important; box-shadow: none !important; padding: 0 !important; }
            .box-title { text-align: center; font-size: 24px; margin-bottom: 20px; }
            .table-custom th { color: #000; border-bottom: 2px solid #000; }
            .table-custom td { color: #000; border-bottom: 1px solid #ccc; }
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
            <li class="active" onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
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
                
                <!-- 🛠️ SEARCH FORM -->
                <form action="" method="GET" class="d-flex shadow-sm" style="border-radius: 10px;">
                    <input type="text" name="search" class="form-control search-modern px-4" placeholder="Search subject, code or faculty..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 280px;">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                </form>

                <!-- 🖨️ PRINT ROSTER BUTTON -->
                <button class="btn-outline-modern ms-3" onclick="window.print()" title="Print Subject List">
                    <i class="fas fa-print text-primary me-2"></i> Print List
                </button>

                <!-- 📊 EXPORT TO EXCEL BUTTON -->
                <button class="btn-outline-modern ms-2" onclick="exportTableToCSV('Subject_List.csv')" title="Download Excel File">
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

        <?php echo $message; // Success / Error Alerts ?>

        <!-- PAGE HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-5 mt-2 page-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box blue-box"><i class="fas fa-book-open"></i></div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);">Subject Management</h3>
                    <p class="text-muted fw-semibold small mb-0">Manage curriculum subjects, semester codes, and assign faculty.</p>
                </div>
            </div>
            
            <?php if(!empty($search_query)): ?>
            <a href="subject_mgmt.php" class="btn btn-outline-danger fw-bold" style="border-radius: 10px; padding: 10px 20px;">
                <i class="fas fa-times me-1"></i> Clear Search
            </a>
            <?php endif; ?>
        </div>

        <!-- TWO COLUMN LAYOUT -->
        <div class="row g-4">
            
            <!-- LEFT COLUMN: ADD SUBJECT FORM -->
            <div class="col-md-4 add-form-col">
                <div class="content-box">
                    <h5 class="box-title"><i class="fas fa-plus-circle text-primary me-2"></i> Add New Subject</h5>
                    <hr class="mb-4" style="border-color: #e2e8f0;">
                    
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Subject Code</label>
                            <input type="text" name="subject_code" class="form-control" required placeholder="e.g. 3330701">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Subject Name</label>
                            <input type="text" name="subject_name" class="form-control" required placeholder="e.g. Database Management">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Department</label>
                            <select name="department" class="form-select" required>
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="IT Engineering">IT Engineering</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Semester</label>
                            <select name="semester" class="form-select" required>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                                <option value="3">Semester 3</option>
                                <option value="4">Semester 4</option>
                                <option value="5" selected>Semester 5</option>
                                <option value="6">Semester 6</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Assign Faculty</label>
                            <select name="faculty_name" class="form-select">
                                <option value="">Select Faculty (Optional)</option>
                                <?php if($faculty_list && $faculty_list->num_rows > 0): ?>
                                    <?php while($fac = $faculty_list->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($fac['name']); ?>"><?php echo htmlspecialchars($fac['name']); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <button type="submit" name="add_subject" class="btn-gradient w-100 mt-2">
                            <i class="fas fa-save me-2"></i> Save Subject
                        </button>
                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN: SUBJECTS TABLE -->
            <div class="col-md-8 table-col">
                <div class="content-box h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="box-title mb-0"><i class="fas fa-list text-success me-2"></i> Active Subjects List</h5>
                        <span class="badge bg-primary text-white action-col" style="border-radius: 20px; padding: 5px 12px;">Total: <?php echo $subjects_list->num_rows; ?></span>
                    </div>
                    <hr class="mb-4 action-col" style="border-color: #e2e8f0;">
                    
                    <div class="table-responsive">
                        <table class="table table-custom mb-0" id="subjectTable">
                            <thead>
                                <tr>
                                    <th>Subject Details</th>
                                    <th>Department</th>
                                    <th>Semester</th>
                                    <th>Assigned Faculty</th>
                                    <th class="text-end action-col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($subjects_list && $subjects_list->num_rows > 0): ?>
                                    <?php while($row = $subjects_list->fetch_assoc()): 
                                        $sub_id = $row['subject_id'] ?? ($row['id'] ?? 0);
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold" style="font-size: 14.5px; color: var(--text-main);"><?php echo htmlspecialchars($row['subject_name'] ?? ''); ?></div>
                                                <div class="text-muted fw-semibold mt-1" style="font-size: 11.5px; letter-spacing: 0.5px;"><i class="fas fa-hashtag me-1"></i><?php echo htmlspecialchars($row['subject_code'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td>
                                                <div class="text-muted fw-semibold" style="font-size: 13px;"><?php echo htmlspecialchars($row['department'] ?? 'Computer Engineering'); ?></div>
                                            </td>
                                            <td>
                                                <span class="badge-modern bg-primary-soft">Sem <?php echo htmlspecialchars($row['semester'] ?? '1'); ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold" style="color: var(--primary); font-size: 13.5px;">
                                                    <?php echo !empty($row['faculty_name']) ? htmlspecialchars($row['faculty_name']) : '<span class="text-muted fst-italic fw-normal">Not Assigned</span>'; ?>
                                                </span>
                                            </td>
                                            <td class="text-end action-col">
                                                <a href="subject_mgmt.php?delete_id=<?php echo $sub_id; ?>" 
                                                   class="btn-action-delete text-decoration-none" 
                                                   title="Delete Subject"
                                                   onclick="return confirm('Are you sure you want to delete this subject?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5 action-col">
                                            <i class="fas fa-search mb-3" style="font-size: 40px; color: #cbd5e1;"></i><br>
                                            <span class="fw-bold fs-5 text-dark">No subjects found.</span><br>
                                            <small class="fw-semibold">Try a different search or use the form on the left to add subjects.</small>
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
        // 1. Live Clock Script
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // 2. EXPORT TO CSV / EXCEL SCRIPT
        function exportTableToCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("#subjectTable tr");
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                
                // Skip the last column (Action column) which is index 4
                for (var j = 0; j < cols.length - 1; j++) {
                    var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " - ");
                    row.push('"' + data + '"');
                }
                csv.push(row.join(","));
            }

            // Download logic
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