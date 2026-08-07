<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$msg = "";

// 2. Handle Add Faculty
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_faculty'])) {
    $fac_id = $conn->real_escape_string($_POST['user_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $password = $conn->real_escape_string($_POST['password']);
    $dept = $conn->real_escape_string($_POST['department']);
    $designation = $conn->real_escape_string($_POST['designation']);

    // Check if ID already exists
    $check = $conn->query("SELECT user_id FROM users WHERE user_id = '$fac_id'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger'>Faculty ID already exists!</div>";
    } else {
        $insert_query = "INSERT INTO users (user_id, name, password, role, department, designation, status) 
                         VALUES ('$fac_id', '$name', '$password', 'faculty', '$dept', '$designation', 'Active')";
        if ($conn->query($insert_query)) {
            $msg = "<div class='alert alert-success'>Faculty Added Successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    }
}

// 3. Handle Delete Faculty
if (isset($_GET['delete'])) {
    $del_id = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM users WHERE user_id = '$del_id' AND role = 'faculty'");
    header("Location: faculty_mgmt.php");
    exit();
}

// 4. Fetch All Faculty
$faculties = [];
$res = $conn->query("SELECT * FROM users WHERE role = 'faculty' ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $faculties[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management - Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        
        /* SIDEBAR (Matching Admin UI) */
        .sidebar { width: 260px; background-color: #0f172a; color: #ffffff; display: flex; flex-direction: column; padding: 25px 0; z-index: 10; }
        .sidebar-logo-container { text-align: center; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; gap: 10px;}
        .sidebar-logo-container img { width: 40px; }
        .sidebar-title h2 { font-size: 18px; font-weight: 700; margin: 0; text-align: left; line-height: 1.2; }
        .nav-links { list-style: none; padding: 0; flex-grow: 1; margin-top: 20px; }
        .nav-links li { padding: 12px 25px; margin: 5px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 500; color: #94a3b8; transition: 0.3s; }
        .nav-links li:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-links li.active { background: #3b82f6; color: white; }
        
        .main { flex: 1; padding: 30px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .header h2 { font-weight: 700; color: #0f172a; }
        
        .card-custom { background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 25px; }
        
        .table-custom th { background: #f1f5f9; font-size: 13px; text-transform: uppercase; color: #475569; }
        .table-custom td { vertical-align: middle; font-size: 14.5px; }
        .badge-status { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <!-- Add your logo path here -->
            <div class="sidebar-title">
                <h2>DIGITAL LAB<br>MANUAL</h2>
            </div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-chart-pie"></i> Dashboard</li>
            <li onclick="window.location.href='student_mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li class="active" onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li><i class="fas fa-book"></i> Subject Mgmt</li>
            <li><i class="fas fa-file-alt"></i> Lab Manuals</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <div class="header">
            <h2>Faculty Management</h2>
        </div>

        <?php echo $msg; ?>

        <div class="row">
            <!-- ADD FACULTY FORM -->
            <div class="col-md-4">
                <div class="card-custom">
                    <h5 class="mb-4 fw-bold">Add New Faculty</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size: 12px;">FACULTY ID / EMP CODE</label>
                            <input type="text" name="user_id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size: 12px;">FULL NAME</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size: 12px;">PASSWORD</label>
                            <input type="text" name="password" class="form-control" value="kdp123" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size: 12px;">DEPARTMENT</label>
                            <select name="department" class="form-select">
                                <option>Computer Engineering</option>
                                <option>Mechanical Engineering</option>
                                <option>Civil Engineering</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold" style="font-size: 12px;">DESIGNATION</label>
                            <input type="text" name="designation" class="form-control" value="Assistant Professor">
                        </div>
                        <button type="submit" name="add_faculty" class="btn btn-primary w-100 fw-bold">Add Faculty</button>
                    </form>
                </div>
            </div>

            <!-- FACULTY LIST TABLE -->
            <div class="col-md-8">
                <div class="card-custom">
                    <h5 class="mb-4 fw-bold">Active Faculties</h5>
                    <table class="table table-hover table-custom">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Department</th>
                               <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($faculties)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No faculty added yet.</td></tr>
                            <?php else: foreach($faculties as $fac): ?>
                                <tr>
                                    <td class="fw-bold text-primary"><?php echo htmlspecialchars($fac['user_id']); ?></td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($fac['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($fac['designation']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($fac['department']); ?></td>
                                    <td><span class="badge bg-success badge-status"><?php echo $fac['status']; ?></span></td>
                                    <td>
                                        <!-- Delete Button -->
                                        <a href="?delete=<?php echo urlencode($fac['user_id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this faculty account?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>