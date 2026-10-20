<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$msg = "";
$admin_id = $_SESSION['user_id'];

// ==========================================
// 🚀 ADD FACULTY
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_faculty'])) {
    $fac_id = $conn->real_escape_string($_POST['user_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $dept = $conn->real_escape_string($_POST['department']);
    $designation = $conn->real_escape_string($_POST['designation']);
    $password = "Fac@" . rand(1000, 9999); 
    
    $check = $conn->query("SELECT user_id FROM users WHERE user_id='$fac_id' OR email='$email'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger shadow-sm' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i> Faculty ID or Email already exists!</div>";
    } else {
        $sql = "INSERT INTO users (user_id, name, email, password, role, department, designation) 
                VALUES ('$fac_id', '$name', '$email', '$password', 'faculty', '$dept', '$designation')";
        if ($conn->query($sql)) {
            $msg = "<div class='alert alert-success shadow-sm' style='border-radius:10px;'><i class='fas fa-check-circle me-2'></i> Faculty added successfully! Default Password: $password</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm' style='border-radius:10px;'><i class='fas fa-times-circle me-2'></i> Error: " . $conn->error . "</div>";
        }
    }
}

// ==========================================
// 🗑️ DELETE FACULTY
// ==========================================
if (isset($_GET['delete'])) {
    $del_id = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM users WHERE user_id='$del_id' AND role='faculty'");
    header("Location: faculty_mgmt.php");
    exit();
}

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$query = "SELECT * FROM users WHERE role='faculty'";
if (!empty($search)) {
    $query .= " AND (name LIKE '%$search%' OR user_id LIKE '%$search%' OR department LIKE '%$search%')";
}
$query .= " ORDER BY created_at DESC";
$faculty = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Management - KDP Admin</title>
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
        .form-control { border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 600; padding: 10px 15px;}
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); background: white;}
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
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li class="active" onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Profile.php'"><i class="fas fa-user-shield"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <h3 class="fw-bold m-0">Faculty Management</h3>
            <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addFacultyModal"><i class="fas fa-user-plus me-2"></i> Add Faculty</button>
        </div>
        <?php echo $msg; ?>

        <div class="content-box p-0 overflow-hidden">
            <div class="d-flex justify-content-between align-items-center p-4 border-bottom">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-chalkboard-teacher text-primary me-2"></i> Registered Faculty</h5>
                <form method="GET" class="search-group">
                    <input type="text" name="search" placeholder="Search ID or Name..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Employee ID</th>
                            <th>Faculty Name</th>
                            <th>Department & Role</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($faculty->num_rows > 0): ?>
                            <?php while($row = $faculty->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 text-primary fw-bold"><?php echo htmlspecialchars($row['user_id']); ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div class="text-muted small fw-semibold"><?php echo htmlspecialchars($row['email']); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['department']); ?></div>
                                        <div class="text-muted small fw-semibold"><?php echo htmlspecialchars($row['designation']); ?></div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="?delete=<?php echo $row['user_id']; ?>" class="btn-action" onclick="return confirm('Delete this faculty?');"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted fw-bold">No faculty found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Faculty Modal -->
    <div class="modal fade" id="addFacultyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i> Register Faculty</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3"><label class="form-label small fw-bold text-muted text-uppercase">Employee ID</label><input type="text" name="user_id" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label small fw-bold text-muted text-uppercase">Full Name (e.g. Prof. A. B. Patel)</label><input type="text" name="name" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label small fw-bold text-muted text-uppercase">Email ID</label><input type="email" name="email" class="form-control" required></div>
                        <div class="mb-3"><label class="form-label small fw-bold text-muted text-uppercase">Department</label><input type="text" name="department" class="form-control" value="Computer Engineering" required></div>
                        <div class="mb-3"><label class="form-label small fw-bold text-muted text-uppercase">Designation</label><input type="text" name="designation" class="form-control" value="Lecturer" required></div>
                    </div>
                    <div class="modal-footer bg-light border-0"><button type="submit" name="add_faculty" class="btn btn-primary fw-bold px-4">Register</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>