<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$msg = "";

// 2. Handle Add Subject
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_subject'])) {
    $sub_name = $conn->real_escape_string($_POST['subject_name']);
    $dept = $conn->real_escape_string($_POST['department']);
    $sem = $conn->real_escape_string($_POST['semester']);

    // Check if subject already exists in that semester and branch
    $check = $conn->query("SELECT id FROM subjects WHERE subject_name = '$sub_name' AND department = '$dept' AND semester = '$sem'");
    
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger shadow-sm border-0' style='border-radius: 10px;'><i class='fas fa-exclamation-circle me-2'></i> Subject already exists in this branch & semester!</div>";
    } else {
        $insert_query = "INSERT INTO subjects (subject_name, department, semester) VALUES ('$sub_name', '$dept', '$sem')";
        if ($conn->query($insert_query)) {
            $msg = "<div class='alert alert-success shadow-sm border-0' style='border-radius: 10px;'><i class='fas fa-check-circle me-2'></i> Subject Added Successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm border-0' style='border-radius: 10px;'>Error: " . $conn->error . "</div>";
        }
    }
}

// 3. Handle Delete Subject
if (isset($_GET['delete'])) {
    $del_id = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM subjects WHERE id = '$del_id'");
    header("Location: subject_mgmt.php");
    exit();
}

// 4. Fetch Subjects based on filters
$dept_filter = isset($_GET['department']) ? $_GET['department'] : 'Computer Engineering';
$sem_filter = isset($_GET['semester']) ? $_GET['semester'] : 'Semester 3';

$safe_dept = $conn->real_escape_string($dept_filter);
$safe_sem = $conn->real_escape_string($sem_filter);

$subjects = [];
$res = $conn->query("SELECT * FROM subjects WHERE department = '$safe_dept' AND semester = '$safe_sem' ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $subjects[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management - Digital Lab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-width: 260px; --bg-color: #f8fafc; }
        body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        
        /* SIDEBAR */
        .sidebar { width: var(--sidebar-width); background-color: #0f172a; color: #ffffff; display: flex; flex-direction: column; padding: 20px 0; z-index: 10; overflow-y: auto; }
        .sidebar-logo-container { padding: 0 20px 20px 20px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar-title h2 { font-size: 15px; font-weight: 700; margin: 0; line-height: 1.2; letter-spacing: 0.5px; }
        .nav-links { list-style: none; padding: 15px 15px 0 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 11px 16px; margin: 4px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 14px; font-size: 14px; font-weight: 500; color: #94a3b8; transition: 0.2s ease-in-out; }
        .nav-links li:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-links li.active { background: #3b82f6; color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }

        /* MAIN CONTENT AREA */
        .main { flex: 1; padding: 25px 35px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; }
        
        /* TOP BAR */
        .topbar { background: white; padding: 12px 25px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .search-box { background: #f8fafc; border-radius: 8px; padding: 6px 15px; display: flex; align-items: center; gap: 10px; width: 350px; border: 1px solid #e2e8f0; }
        .search-box input { border: none; background: transparent; outline: none; font-size: 14px; width: 100%; color: #334155; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 38px; height: 38px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; }
        
        /* CONTENT BOXES */
        .content-box { background: white; border-radius: 14px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        
        /* CUSTOM FORM INPUTS */
        .form-control, .form-select { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 15px; font-size: 14px; box-shadow: none; }
        .form-control:focus, .form-select:focus { border-color: #3b82f6; background-color: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        
        /* TABLE STYLING */
        .table-custom th { background: transparent; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; }
        .table-custom td { vertical-align: middle; font-size: 14px; padding: 14px 0; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .table-custom tr:last-child td { border-bottom: none; }
        
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; background: rgba(59,130,246,0.1); color: #3b82f6; }
        
        .btn-delete { background: rgba(239,68,68,0.1); color: #dc2626; border: none; padding: 6px 12px; border-radius: 6px; transition: 0.2s; }
        .btn-delete:hover { background: #dc2626; color: white; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <div style="background: #3b82f6; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">DL</div>
            <div class="sidebar-title"><h2>DIGITAL LAB<br>MANUAL</h2></div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-chart-pie"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li class="active" onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-alt"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-folder-open"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-circle"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li onclick="window.location.href='Expense Mgmt.php'"><i class="fas fa-wallet"></i> Expense Mgmt</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <!-- TOPBAR -->
        <div class="topbar">
            <div class="search-box">
                <i class="fas fa-search text-muted"></i>
                <input type="text" placeholder="Search subjects...">
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="user-profile">
                    <div class="user-avatar">AM</div>
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 13.5px; line-height: 1.2;">System Administrator</div>
                        <div class="text-muted" style="font-size: 11.5px;">University Tech</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- HEADER TITLE -->
        <div class="mb-2">
            <h4 class="fw-bold text-dark mb-1">Subject Management</h4>
            <p class="text-muted small mb-0">Add new curriculum subjects and filter them by branch and semester.</p>
        </div>

        <?php if($msg != "") echo $msg; ?>

        <div class="row g-4">
            <!-- ADD SUBJECT FORM -->
            <div class="col-md-4">
                <div class="content-box">
                    <h6 class="fw-bold text-dark mb-4">Add New Subject</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">SUBJECT NAME</label>
                            <input type="text" name="subject_name" class="form-control" placeholder="e.g. Data Structures" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">DEPARTMENT / BRANCH</label>
                            <select name="department" class="form-select">
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="Electrical Engineering">Electrical Engineering</option>
                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">SEMESTER</label>
                            <select name="semester" class="form-select">
                                <?php for($i=1; $i<=6; $i++): ?>
                                    <option value="Semester <?php echo $i; ?>" <?php if($i == 3) echo 'selected'; ?>>Semester <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_subject" class="btn btn-primary w-100 fw-bold py-2" style="border-radius: 8px;">Add Subject</button>
                    </form>
                </div>
            </div>

            <!-- SUBJECTS LIST TABLE -->
            <div class="col-md-8">
                <div class="content-box h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="fw-bold text-dark mb-0">Curriculum Subjects</h6>
                        
                        <!-- FILTER FORM -->
                        <form method="GET" class="d-flex gap-2">
                            <select name="department" class="form-select form-select-sm shadow-none" style="width: auto; font-size: 12px; padding: 6px 30px 6px 12px;" onchange="this.form.submit()">
                                <option value="Computer Engineering" <?php if($dept_filter == 'Computer Engineering') echo 'selected'; ?>>Computer Engineering</option>
                                <option value="Electrical Engineering" <?php if($dept_filter == 'Electrical Engineering') echo 'selected'; ?>>Electrical Engineering</option>
                                <option value="Mechanical Engineering" <?php if($dept_filter == 'Mechanical Engineering') echo 'selected'; ?>>Mechanical Engineering</option>
                                <option value="Civil Engineering" <?php if($dept_filter == 'Civil Engineering') echo 'selected'; ?>>Civil Engineering</option>
                            </select>
                            <select name="semester" class="form-select form-select-sm shadow-none" style="width: auto; font-size: 12px; padding: 6px 30px 6px 12px;" onchange="this.form.submit()">
                                <?php for($i=1; $i<=6; $i++): ?>
                                    <option value="Semester <?php echo $i; ?>" <?php if($sem_filter == "Semester $i") echo 'selected'; ?>>Semester <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </form>
                    </div>

                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Subject Name</th>
                                <th>Department & Sem</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($subjects)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No subjects found for this selection.</td></tr>
                            <?php else: foreach($subjects as $index => $sub): ?>
                                <tr>
                                    <td class="text-muted fw-semibold"><?php echo $index + 1; ?></td>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                                    <td>
                                        <div style="font-size: 13px; color: #475569;"><?php echo htmlspecialchars($sub['department']); ?></div>
                                        <small class="text-muted" style="font-size: 11px;"><?php echo htmlspecialchars($sub['semester']); ?></small>
                                    </td>
                                    <td><span class="badge-status">Active</span></td>
                                    <td class="text-end">
                                        <a href="?delete=<?php echo urlencode($sub['id']); ?>" class="btn-delete text-decoration-none" onclick="return confirm('Kya aap yeh subject remove karna chahte hain?');">
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