<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch Admin Details for Profile Pill
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query ? $admin_query->fetch_assoc() : null;
$admin_name = $admin_data['name'] ?? 'System Administrator';

// Ensure subjects table exists with faculty_name column
$conn->query("CREATE TABLE IF NOT EXISTS subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_name VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    semester INT NOT NULL,
    faculty_name VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ==========================================
// 🚀 ADD NEW SUBJECT LOGIC
// ==========================================
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    $subject_name = $conn->real_escape_string($_POST['subject_name']);
    $department = $conn->real_escape_string($_POST['department']);
    $semester = (int)$_POST['semester'];
    $faculty_name = $conn->real_escape_string($_POST['faculty_name']);
    
    $sql = "INSERT INTO subjects (subject_name, department, semester, faculty_name) 
            VALUES ('$subject_name', '$department', '$semester', '$faculty_name')";
            
    if ($conn->query($sql)) {
        $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Subject added and assigned successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Error: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
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

// Fetch Subjects from Database
$subjects_list = $conn->query("SELECT * FROM subjects ORDER BY semester ASC, subject_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management - Admin Portal</title>
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
        .search-box { background: #fff; border-radius: 8px; padding: 10px 15px; display: flex; align-items: center; gap: 10px; width: 350px; border: 1px solid #e2e8f0; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .search-box input { border: none; background: transparent; outline: none; font-size: 14px; width: 100%; color: #334155; }
        
        .profile-pill { display: flex; align-items: center; background-color: #ffffff; padding: 6px 16px 6px 20px; border-radius: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; cursor: pointer; text-decoration: none; color: inherit; transition: all 0.2s;}
        .profile-text { text-align: right; margin-right: 15px; }
        .profile-welcome { display: block; font-size: 9.5px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 14px; color: #1e293b; font-weight: 700; }
        .profile-avatar { width: 42px; height: 42px; background-color: var(--accent-blue); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; box-shadow: 0 3px 8px rgba(37, 99, 235, 0.4); letter-spacing: 1px;}

        .content-box { background: white; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
        .table-custom th { background: #f8fafc; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; padding: 14px; }
        .table-custom td { vertical-align: middle; font-size: 14px; padding: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .badge-sem { background: rgba(37,99,235,0.1); color: var(--accent-blue); border: 1px solid rgba(37,99,235,0.2); padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600; }
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
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li class="active" onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-alt"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-folder-open"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-circle"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- TOPBAR -->
        <div class="topbar mb-3">
            <div class="search-box">
                <i class="fas fa-search text-muted"></i>
                <input type="text" placeholder="Search subjects...">
            </div>
            
            <div class="d-flex align-items-center gap-4">
                <div class="position-relative" style="cursor: pointer; padding: 8px; background: white; border-radius: 8px; border: 1px solid #e2e8f0;" onclick="window.location.href='Submissions.php'">
                    <i class="far fa-bell text-secondary fs-5"></i>
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
        </div>

        <?php echo $message; ?>

        <!-- PAGE HEADER -->
        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1" style="font-size: 24px;">Subject Management</h3>
            <p class="text-muted small mb-0">Manage curriculum subjects, semesters, and assign respective faculty members.</p>
        </div>

        <!-- TWO COLUMN LAYOUT -->
        <div class="row g-4">
            <!-- LEFT COLUMN: ADD SUBJECT FORM -->
            <div class="col-md-4">
                <div class="content-box">
                    <h5 class="fw-bold text-dark mb-3" style="font-size: 16px;"><i class="fas fa-plus-circle text-primary me-2"></i> Add New Subject</h5>
                    
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Subject Name</label>
                            <input type="text" name="subject_name" class="form-control" required placeholder="e.g. Database Management System">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Department</label>
                            <select name="department" class="form-select" required>
                                <option value="Computer Engineering">Computer Engineering</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Semester</label>
                            <select name="semester" class="form-select" required>
                                <option value="1" selected>Semester 1</option>
                                <option value="2">Semester 2</option>
                                <option value="3">Semester 3</option>
                                <option value="4">Semester 4</option>
                                <option value="5">Semester 5</option>
                                <option value="6">Semester 6</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">Assign Faculty</label>
                            <select name="faculty_name" class="form-control">
                                <option value="">Select Faculty (Optional)</option>
                                <?php if($faculty_list && $faculty_list->num_rows > 0): ?>
                                    <?php while($fac = $faculty_list->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($fac['name']); ?>"><?php echo htmlspecialchars($fac['name']); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <button type="submit" name="add_subject" class="btn btn-primary w-100 fw-bold py-2" style="background: var(--accent-blue); border-radius: 8px;">
                            <i class="fas fa-save me-1"></i> Save Subject
                        </button>
                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN: SUBJECTS TABLE -->
            <div class="col-md-8">
                <div class="content-box">
                    <h5 class="fw-bold text-dark mb-3" style="font-size: 16px;"><i class="fas fa-list text-success me-2"></i> Active Subjects List</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Subject Name</th>
                                    <th>Department</th>
                                    <th>Semester</th>
                                    <th>Assigned Faculty</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($subjects_list && $subjects_list->num_rows > 0): ?>
                                    <?php while($row = $subjects_list->fetch_assoc()): 
                                        $sub_id = $row['subject_id'] ?? ($row['id'] ?? 0);
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['subject_name'] ?? ''); ?></div>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($row['department'] ?? 'Computer Engineering'); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge-sem">Sem <?php echo htmlspecialchars($row['semester'] ?? '1'); ?></span>
                                            </td>
                                            <td>
                                                <span class="text-primary fw-medium" style="font-size: 13.5px;"><?php echo !empty($row['faculty_name']) ? htmlspecialchars($row['faculty_name']) : '<span class="text-muted fst-italic">Not Assigned</span>'; ?></span>
                                            </td>
                                            <td class="text-end">
                                                <a href="subject_mgmt.php?delete_id=<?php echo $sub_id; ?>" 
                                                   class="btn btn-outline-danger btn-sm" 
                                                   style="border-radius: 6px; padding: 4px 10px;"
                                                   onclick="return confirm('Delete this subject?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="fas fa-book mb-2" style="font-size: 32px; color: #cbd5e1;"></i><br>
                                            <span>No subjects found in database. Use the form on the left to add.</span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>