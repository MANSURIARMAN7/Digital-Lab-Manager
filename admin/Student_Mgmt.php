<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$msg = "";

// 2. Handle Add Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $enrollment = $conn->real_escape_string($_POST['enrollment']);
    $name = $conn->real_escape_string($_POST['name']);
    $branch = $conn->real_escape_string($_POST['branch']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $password = 'std123'; // Default password for students

    // Check if Enrollment already exists
    $check = $conn->query("SELECT user_id FROM users WHERE user_id = '$enrollment'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger shadow-sm border-0' style='border-radius: 10px;'><i class='fas fa-exclamation-circle me-2'></i> Enrollment No. already exists!</div>";
    } else {
        // We use designation column to store semester for students to save space
        $insert_query = "INSERT INTO users (user_id, name, password, role, department, designation, status) 
                         VALUES ('$enrollment', '$name', '$password', 'student', '$branch', '$semester', 'Active')";
        if ($conn->query($insert_query)) {
            $msg = "<div class='alert alert-success shadow-sm border-0' style='border-radius: 10px;'><i class='fas fa-check-circle me-2'></i> Student Added Successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm border-0' style='border-radius: 10px;'>Error: " . $conn->error . "</div>";
        }
    }
}

// 3. Handle Delete Student
if (isset($_GET['delete'])) {
    $del_id = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM users WHERE user_id = '$del_id' AND role = 'student'");
    header("Location: Student_Mgmt.php");
    exit();
}

// 4. Fetch All Students
$students = [];
$res = $conn->query("SELECT * FROM users WHERE role = 'student' ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - Digital Lab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
        
        /* STUDENT CARD STYLES */
        .student-card { background: white; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 15px; }
        .student-header { padding: 15px 25px; background: #fff; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; }
        .student-header:hover { background: #f8fafc; }
        
        .form-control, .form-select { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 15px; font-size: 14px; box-shadow: none; }
        .form-control:focus, .form-select:focus { border-color: #3b82f6; background-color: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
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
            <li class="active" onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
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
                <input type="text" placeholder="Search students by name or enrollment...">
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="user-profile">
                    <div class="user-avatar">AM</div>
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 13.5px; line-height: 1.2;">Prof. M. C. Thakor</div>
                        <div class="text-muted" style="font-size: 11.5px;">University Tech</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- HEADER TITLE & BUTTON -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h4 class="fw-bold text-dark mb-1">👨‍🎓 Student Management</h4>
                <p class="text-muted small mb-0">Manage student admissions and academic branch details.</p>
            </div>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="fa-solid fa-plus me-1"></i> Add New Student
            </button>
        </div>

        <?php if($msg != "") echo $msg; ?>

        <!-- DYNAMIC STUDENT CARDS -->
        <div>
            <?php if(empty($students)): ?>
                <div class="text-center text-muted py-5" style="background: white; border-radius: 12px; border: 1px dashed #cbd5e1;">
                    <i class="fas fa-users mb-2" style="font-size: 30px; color: #94a3b8;"></i>
                    <p>No students enrolled yet. Click 'Add New Student' to begin.</p>
                </div>
            <?php else: foreach($students as $index => $std): ?>
                
                <div class="student-card" id="studentCard-<?php echo $std['user_id']; ?>">
                    <div class="student-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3 flex-grow-1">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($std['name']); ?>&background=random&color=fff" class="rounded-circle" width="45" alt="Avatar">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 15px;"><?php echo htmlspecialchars($std['name']); ?></h6>
                                <small class="text-muted fw-semibold">
                                    Enrollment: <?php echo htmlspecialchars($std['user_id']); ?> | 
                                    Branch: <?php echo htmlspecialchars($std['department']); ?> | 
                                    Sem: <?php echo htmlspecialchars($std['designation']); ?>
                                </small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 border border-success border-opacity-25">Status: Active</span>
                            <a href="?delete=<?php echo urlencode($std['user_id']); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Kya aap <?php echo htmlspecialchars($std['name']); ?> ka admission sach mein cancel karna chahte hain?');">
                                <i class="fa-solid fa-user-minus me-1"></i> Cancel Admission
                            </a>
                        </div>
                    </div>
                </div>

            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- ADD STUDENT MODAL -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size:11px; letter-spacing: 0.5px;">ENROLLMENT NO.</label>
                            <input type="text" name="enrollment" class="form-control" placeholder="e.g. 246310307055" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size:11px; letter-spacing: 0.5px;">FULL NAME</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Mansuri Arman" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-bold" style="font-size:11px; letter-spacing: 0.5px;">BRANCH</label>
                                <select name="branch" class="form-select">
                                    <option value="Computer Engineering">Computer Engg.</option>
                                    <option value="Mechanical Engineering">Mechanical Engg.</option>
                                    <option value="Civil Engineering">Civil Engg.</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted fw-bold" style="font-size:11px; letter-spacing: 0.5px;">SEMESTER</label>
                                <select name="semester" class="form-select">
                                    <option value="Semester 1">Semester 1</option>
                                    <option value="Semester 2">Semester 2</option>
                                    <option value="Semester 3">Semester 3</option>
                                    <option value="Semester 4">Semester 4</option>
                                    <option value="Semester 5">Semester 5</option>
                                    <option value="Semester 6">Semester 6</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" name="add_student" class="btn btn-primary w-100 fw-bold py-2" style="border-radius: 8px;">Save Student</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>