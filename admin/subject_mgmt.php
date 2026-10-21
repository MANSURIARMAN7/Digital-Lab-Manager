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

// Fetch faculties for the dropdown (Format: Name (ID))
$faculties_query = $conn->query("SELECT user_id, name FROM users WHERE role='faculty' ORDER BY name ASC");
$faculty_list = [];
if($faculties_query && $faculties_query->num_rows > 0) {
    while($fac = $faculties_query->fetch_assoc()){
        $faculty_list[] = $fac['name'] . " (" . $fac['user_id'] . ")";
    }
}

// Ensure subjects table exists
$conn->query("CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(100) DEFAULT NULL,
    subject_name VARCHAR(255) NOT NULL,
    department VARCHAR(255) NOT NULL,
    semester INT NOT NULL,
    faculty_name VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$message = "";

// ➕ ADD NEW SUBJECT LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    $subject_code = $conn->real_escape_string($_POST['subject_code']);
    $subject_name = $conn->real_escape_string($_POST['subject_name']);
    $department = $conn->real_escape_string($_POST['department']);
    $semester = (int)$_POST['semester'];
    $faculty_name = $conn->real_escape_string($_POST['faculty_name']);

    $sql = "INSERT INTO subjects (subject_code, subject_name, department, semester, faculty_name) 
            VALUES ('$subject_code', '$subject_name', '$department', '$semester', '$faculty_name')";
    
    if ($conn->query($sql)) {
        $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm' style='border-radius:10px;' role='alert'><i class='fas fa-check-circle me-2'></i>Subject added and assigned successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        // SMART FALLBACK IF subject_code IS MISSING
        if(strpos($conn->error, "subject_code") !== false) {
            $conn->query("ALTER TABLE subjects ADD COLUMN subject_code VARCHAR(100) NULL AFTER id");
            if($conn->query($sql)) {
                $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm' style='border-radius:10px;' role='alert'><i class='fas fa-check-circle me-2'></i>Subject added and assigned successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm' style='border-radius:10px;' role='alert'>Error: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm' style='border-radius:10px;' role='alert'>Error: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
}

// 🗑️ DELETE SUBJECT LOGIC
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM subjects WHERE id='$del_id'");
    header("Location: subject_mgmt.php");
    exit();
}

// Fetch Faculty list for dropdown
$faculty_list = $conn->query("SELECT name FROM users WHERE role = 'faculty' ORDER BY name ASC");

// 🔍 SEARCH & FETCH SUBJECTS LIST
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
    <title>Subject Management - KDP Admin</title>
    
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
            --shadow-float: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            --radius-xl: 16px;
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover, .nav-links li.active { color: #ffffff; background: rgba(255,255,255,0.2); }
        .nav-links li.active { font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid white;}
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 10px 22px 10px 28px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: var(--primary); font-weight: 800; text-transform: uppercase; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 17px; color: var(--text-main); font-weight: 900;}
        .profile-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.4);}

        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 25px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        .content-box:hover { box-shadow: var(--shadow-float); }
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;}

        .btn-gradient { background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; border: none; font-weight: 800; padding: 12px 20px; border-radius: 8px; transition: var(--transition-bounce); display: inline-flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 0.5px;}
        .btn-gradient:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(79, 70, 229, 0.3); color: white; }
        
        .form-label { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;}
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 12px 15px; font-size: 14.5px; font-weight: 600; background: #f8fafc;}
        .form-control:focus, .form-select:focus { background: white; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); }

        .search-wrapper { display: flex; height: 42px; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; background: #f8fafc;}
        .search-wrapper input { border: none; background: transparent; padding: 0 15px; font-weight: 600; font-size: 13.5px; width: 100%; outline: none; }
        .search-wrapper button { border: none; background: var(--primary); color: white; padding: 0 20px; font-weight: bold; cursor: pointer; transition: 0.2s;}
        .search-wrapper button:hover { background: var(--primary-hover); }

        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); padding: 15px 10px; background: #f8fafc; border-bottom: 2px solid #e2e8f0;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; }
        .table-custom tbody tr:hover td { background-color: #f8fafc; }
        
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;}

        .btn-action { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px;}
        .btn-action:hover { background: #dc2626; color: white; transform: translateY(-2px); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        @media print {
            .content-box { border: none !important; box-shadow: none !important; padding: 0 !important; }
            .sidebar, .topbar, .add-form-col, .action-col, .btn-action { display: none !important; }
            .main { padding: 0 !important; overflow: visible !important; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li class="active" onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Profile.php'"><i class="fas fa-user-shield"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- UNIFORM TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            </div>
            
            <a href="Profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">System Admin</span>
                    <h4 class="profile-name">
                        <?php 
                            $disp_name = htmlspecialchars($admin_name);
                            echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : $disp_name;
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </a>
        </div>

        <?php echo $message; ?>

        <!-- PAGE HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 55px; height: 55px; border-radius: 14px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                    <i class="fas fa-book-open"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 26px; color: var(--text-main);">Subject Management</h3>
                    <p class="text-muted fw-semibold small mb-0">Manage curriculum subjects, semester codes, and assign faculty.</p>
                </div>
            </div>
            
            <?php if(!empty($search_query)): ?>
                <a href="subject_mgmt.php" class="btn btn-outline-danger fw-bold" style="border-radius: 8px; padding: 10px 20px;">
                    <i class="fas fa-times me-2"></i> Clear Search
                </a>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            
            <!-- LEFT COLUMN: ADD SUBJECT FORM -->
            <div class="col-lg-4 add-form-col">
                <div class="content-box h-100">
                    <h5 class="box-title"><i class="fas fa-plus-circle text-primary me-2"></i> Add New Subject</h5>
                    
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Subject Code</label>
                            <input type="text" name="subject_code" class="form-control" required placeholder="e.g. 3350701">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subject Name</label>
                            <input type="text" name="subject_name" class="form-control" required placeholder="e.g. Database Management">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select name="department" class="form-select" required>
                                <option value="" selected disabled>Select Branch</option>
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="IT Engineering">IT Engineering</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select" required>
                                <option value="" selected disabled>Select Semester</option>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                                <option value="3">Semester 3</option>
                                <option value="4">Semester 4</option>
                                <option value="5">Semester 5</option>
                                <option value="6">Semester 6</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Assign Faculty</label>
                            <select name="faculty_name" class="form-select">
                                <option value="">Select Faculty (Optional)</option>
                                <?php if($faculty_list && $faculty_list->num_rows > 0): ?>
                                    <?php while($fac = $faculty_list->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($fac['name']); ?>"><?php echo htmlspecialchars($fac['name']); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <button type="submit" name="add_subject" class="btn-gradient w-100 justify-content-center">
                            <i class="fas fa-save me-2"></i> Save Subject
                        </button>
                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN: SUBJECTS TABLE -->
            <div class="col-lg-8">
                <div class="content-box h-100 p-0 overflow-hidden">
                    <div class="d-flex justify-content-between align-items-center p-4 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="box-title mb-0 border-0 pb-0"><i class="fas fa-list text-success me-2"></i> Active Subjects List</h5>
                            <span class="badge bg-primary rounded-pill px-2 py-1 ms-2"><?php echo $subjects_list->num_rows; ?> Total</span>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <form method="GET" class="m-0" style="width: 200px;">
                                <div class="search-wrapper">
                                    <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>">
                                    <button type="submit"><i class="fas fa-search"></i></button>
                                </div>
                            </form>
                            <button class="btn btn-outline-dark fw-bold border-1 border-secondary" onclick="exportTableToCSV('Subject_List.csv')" title="Export to Excel">
                                <i class="fas fa-file-csv"></i>
                            </button>
                            <button class="btn btn-outline-dark fw-bold border-1 border-secondary" onclick="window.print()" title="Print List">
                                <i class="fas fa-print"></i>
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom mb-0" id="subjectTable">
                            <thead>
                                <tr>
                                    <th class="ps-4">Subject Details</th>
                                    <th>Department</th>
                                    <th>Semester</th>
                                    <th>Assigned Faculty</th>
                                    <th class="text-end pe-4 action-col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($subjects_list && $subjects_list->num_rows > 0): ?>
                                    <?php while($row = $subjects_list->fetch_assoc()): 
                                        $sub_id = $row['id'] ?? $row['id']; 
                                    ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark" style="font-size: 14.5px;"><?php echo htmlspecialchars($row['subject_name'] ?? ''); ?></div>
                                                <div class="text-muted fw-bold mt-1" style="font-size: 11.5px; letter-spacing: 0.5px;"><i class="fas fa-hashtag me-1"></i> <?php echo htmlspecialchars($row['subject_code'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td>
                                                <div class="text-muted fw-bold" style="font-size: 13px;"><?php echo htmlspecialchars($row['department'] ?? 'Computer Engineering'); ?></div>
                                            </td>
                                            <td><span class="badge-modern" style="background: rgba(59,130,246,0.1); color: #2563eb;">Sem <?php echo htmlspecialchars($row['semester'] ?? '1'); ?></span></td>
                                            <td>
                                                <div class="fw-bold" style="color: var(--primary); font-size: 13.5px;">
                                                    <?php echo !empty($row['faculty_name']) ? htmlspecialchars($row['faculty_name']) : "<span class='text-muted fst-italic fw-normal'>Not Assigned</span>"; ?>
                                                </div>
                                            </td>
                                            <td class="text-end pe-4 action-col">
                                                <a href="subject_mgmt.php?delete_id=<?php echo $sub_id; ?>" 
                                                   class="btn-action" 
                                                   title="Delete Subject"
                                                   onclick="return confirm('Are you sure you want to delete this subject?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="fas fa-search mb-3" style="font-size: 40px; color: #cbd5e1;"></i><br>
                                            <span class="fw-bold fs-5 text-dark">No Subjects Found.</span><br>
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

    <!-- 2. Edit Subject Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-dark text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i> Edit Subject Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Code <span class="text-danger">*</span></label>
                                <input type="text" name="edit_subject_code" id="edit_subject_code" class="form-control" required>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Subject Name <span class="text-danger">*</span></label>
                                <input type="text" name="edit_subject_name" id="edit_subject_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Department <span class="text-danger">*</span></label>
                                <select name="edit_department" id="edit_department" class="form-select" required>
                                    <option value="Computer Engineering">Computer Engineering</option>
                                    <option value="IT Engineering">IT Engineering</option>
                                    <option value="Civil Engineering">Civil Engineering</option>
                                    <option value="Mechanical Engineering">Mechanical Engineering</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Sem <span class="text-danger">*</span></label>
                                <select name="edit_semester" id="edit_semester" class="form-select" required>
                                    <?php for($i=1; $i<=6; $i++) echo "<option value='$i'>$i</option>"; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Assign Faculty <span class="text-primary">(Hold Ctrl/Cmd to unselect or select)</span></label>
                            <select name="edit_faculty_name[]" id="edit_faculty_name" class="form-select" multiple>
                                <?php foreach($faculty_list as $f): ?>
                                    <option value="<?php echo htmlspecialchars($f); ?>"><?php echo htmlspecialchars($f); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted fst-italic">Select none to unassign all faculties.</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 rounded-bottom-4">
                        <button type="submit" name="edit_subject" class="btn btn-dark fw-bold px-4 w-100">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-body p-4 text-center">
                    <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size: 50px;"></i>
                    <h4 class="fw-bold text-dark mb-2">Delete Subject?</h4>
                    <p class="text-muted fw-semibold mb-4">You are about to delete <strong id="delete_subject_name" class="text-danger"></strong>. Make sure it has no active records.</p>
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="delete_id" id="delete_id_input">
                        <button type="button" class="btn btn-light fw-bold w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_subject_btn" class="btn btn-danger fw-bold w-50">Yes, Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        function exportTableToCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("#subjectTable tr");
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                // Skip the last column (Action)
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