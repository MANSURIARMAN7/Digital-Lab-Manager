<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$msg = "";
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name FROM users WHERE user_id = '$admin_id'");
$admin_name = $admin_query->fetch_assoc()['name'] ?? 'Admin';

$name_parts = explode(' ', trim($admin_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) { $initials .= strtoupper(substr(end($name_parts), 0, 1)); }

// ==========================================
// 🚀 ADD SINGLE STUDENT
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $enrollment = $conn->real_escape_string($_POST['user_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $branch = $conn->real_escape_string($_POST['department']);
    $sem = $conn->real_escape_string($_POST['designation']);
    $password = "Kdp@" . substr($enrollment, -4); 
    
    $check = $conn->query("SELECT user_id FROM users WHERE user_id='$enrollment' OR email='$email'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger shadow-sm' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i> Student ID or Email already exists!</div>";
    } else {
        $sql = "INSERT INTO users (user_id, name, email, password, role, department, designation) 
                VALUES ('$enrollment', '$name', '$email', '$password', 'student', '$branch', '$sem')";
        if ($conn->query($sql)) {
            $msg = "<div class='alert alert-success shadow-sm' style='border-radius:10px;'><i class='fas fa-check-circle me-2'></i> Student added successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm' style='border-radius:10px;'><i class='fas fa-times-circle me-2'></i> Error: " . $conn->error . "</div>";
        }
    }
}

// ==========================================
// 🚀 BULK CSV IMPORT
// ==========================================
if (isset($_POST['import_csv'])) {
    if ($_FILES['csv_file']['name']) {
        $filename = explode(".", $_FILES['csv_file']['name']);
        if (end($filename) == "csv") {
            $handle = fopen($_FILES['csv_file']['tmp_name'], "r");
            fgetcsv($handle); // Skip header row
            
            $success_count = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $enrollment = $conn->real_escape_string($data[0]);
                $name = $conn->real_escape_string($data[1]);
                $email = $conn->real_escape_string($data[2]);
                $branch = $conn->real_escape_string($data[3]);
                $sem = $conn->real_escape_string($data[4]);
                $password = "Kdp@" . substr($enrollment, -4);
                
                $check = $conn->query("SELECT user_id FROM users WHERE user_id='$enrollment' OR email='$email'");
                if ($check->num_rows == 0) {
                    $sql = "INSERT INTO users (user_id, name, email, password, role, department, designation) 
                            VALUES ('$enrollment', '$name', '$email', '$password', 'student', '$branch', '$sem')";
                    if($conn->query($sql)) $success_count++;
                }
            }
            fclose($handle);
            $msg = "<div class='alert alert-success shadow-sm' style='border-radius:10px;'><i class='fas fa-check-circle me-2'></i> Successfully imported $success_count students!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i> Please upload a valid CSV file.</div>";
        }
    }
}

// ==========================================
// 🗑️ DELETE STUDENT
// ==========================================
if (isset($_GET['delete'])) {
    $del_id = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM users WHERE user_id='$del_id' AND role='student'");
    header("Location: Student_Mgmt.php");
    exit();
}

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$query = "SELECT * FROM users WHERE role='student'";
if (!empty($search)) {
    $query .= " AND (name LIKE '%$search%' OR user_id LIKE '%$search%' OR department LIKE '%$search%')";
}
$query .= " ORDER BY created_at DESC";
$students = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Management - KDP Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-width: 270px; --primary: #4338ca; --bg-body: #f8fafc; --surface: #ffffff; --text-main: #0f172a; --text-muted: #64748b; --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 13px; color: #bfdbfe; margin-top: 5px; font-weight: 500; letter-spacing: 0.5px;} 
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}
        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .content-box { background: var(--surface); border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; }
        .btn-action { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; transition: 0.2s; }
        .btn-action:hover { background: #dc2626; color: white; transform: translateY(-2px); }
        .search-group { display: flex; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; background: #f8fafc; }
        .search-group input { border: none; background: transparent; padding: 10px 15px; outline: none; font-size: 14px; font-weight: 600;}
        .search-group button { border: none; background: var(--primary); color: white; padding: 0 20px; }
        .modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .modal-header { background: linear-gradient(135deg, #1e3a8a, #4338ca); color: white; border-radius: 16px 16px 0 0; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 600; padding: 10px 15px;}
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); background: white;}
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
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
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Profile.php'"><i class="fas fa-user-shield"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <h3 class="fw-bold m-0">Student Management</h3>
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addCsvModal"><i class="fas fa-file-csv me-2"></i> Bulk Import</button>
                <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fas fa-user-plus me-2"></i> Add Student</button>
            </div>
        </div>
        <?php echo $msg; ?>

        <div class="content-box p-0 overflow-hidden">
            <div class="d-flex justify-content-between align-items-center p-4 border-bottom">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-users text-primary me-2"></i> Enrolled Students</h5>
                <form method="GET" class="search-group">
                    <input type="text" name="search" placeholder="Search ID or Name..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Enrollment No.</th>
                            <th>Student Details</th>
                            <th>Branch & Sem</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($students->num_rows > 0): ?>
                            <?php while($row = $students->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 text-primary fw-bold"><?php echo htmlspecialchars($row['user_id']); ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div class="text-muted small fw-semibold"><?php echo htmlspecialchars($row['email']); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['department']); ?></div>
                                        <div class="text-muted small fw-semibold">Sem <?php echo htmlspecialchars($row['designation']); ?></div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="?delete=<?php echo $row['user_id']; ?>" class="btn-action" onclick="return confirm('Delete this student?');"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted fw-bold">No students found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modals (Add Student & CSV)FOR STUDENTS & FAC -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i> Register Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3"><label class="form-label small fw-bold text-muted text-uppercase">Enrollment No</label><input type="text" name="user_id" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label small fw-bold text-muted text-uppercase">Full Name</label><input type="text" name="name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label small fw-bold text-muted text-uppercase">Email</label><input type="email" name="email" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label small fw-bold text-muted text-uppercase">Branch</label><input type="text" name="department" class="form-control" value="Computer Engineering" required></div>
                        <div class="mb-3"><label class="form-label small fw-bold text-muted text-uppercase">Semester</label><input type="number" name="designation" class="form-control" min="1" max="6" required></div>
                    </div>
                    <div class="modal-footer bg-light border-0"><button type="submit" name="add_student" class="btn btn-primary fw-bold px-4">Register</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addCsvModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-file-csv me-2"></i> Bulk Import Students</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body p-4 text-center">
                        <i class="fas fa-upload text-primary mb-3" style="font-size: 40px;"></i>
                        <div class="mb-3"><input type="file" name="csv_file" class="form-control" accept=".csv" required></div>
                        <small class="text-muted fw-semibold">CSV Format: EnrollmentNo, Name, Email, Branch, Semester</small>
                    </div>
                    <div class="modal-footer bg-light border-0"><button type="submit" name="import_csv" class="btn btn-success fw-bold px-4">Upload CSV</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>