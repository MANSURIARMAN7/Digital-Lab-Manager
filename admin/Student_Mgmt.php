<?php
include '../db.php';
include 'header.php'; // Header handles session check & admin validation

$msg = "";

// Handle Add Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $enrollment = $conn->real_escape_string($_POST['enrollment']);
    $name = $conn->real_escape_string($_POST['name']);
    $branch = $conn->real_escape_string($_POST['branch']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $password = 'std123'; // Default password

    $check = $conn->query("SELECT user_id FROM users WHERE user_id = '$enrollment'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger shadow-sm border-0 rounded-3'><i class='fas fa-exclamation-circle me-2'></i> Enrollment No. already exists!</div>";
    } else {
        $insert_query = "INSERT INTO users (user_id, name, password, role, department, designation, status) 
                         VALUES ('$enrollment', '$name', '$password', 'student', '$branch', '$semester', 'Active')";
        if ($conn->query($insert_query)) {
            $msg = "<div class='alert alert-success shadow-sm border-0 rounded-3'><i class='fas fa-check-circle me-2'></i> Student Added Successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm border-0 rounded-3'>Error: " . $conn->error . "</div>";
        }
    }
}

// Handle Delete Student
if (isset($_GET['delete'])) {
    $del_id = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM users WHERE user_id = '$del_id' AND role = 'student'");
    $_SESSION['success_message'] = "Student record deleted successfully.";
    echo "<script>window.location.href='Student_Mgmt.php';</script>";
    exit();
}

// Fetch All Students
$students = [];
$res = $conn->query("SELECT * FROM users WHERE role = 'student' ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
}
?>

<!-- HEADER TITLE & BUTTON -->
<div class="page-header mt-2 mb-4">
    <div>
        <h4 class="page-title">Student Management</h4>
        <p class="page-subtitle">Manage student admissions and academic branch details.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="fa-solid fa-plus me-1"></i> Add New Student
    </button>
</div>

<?php if($msg != "") echo $msg; ?>

<!-- DYNAMIC STUDENT CARDS -->
<div>
    <?php if(empty($students)): ?>
        <div class="text-center text-muted py-5 content-box border-dashed">
            <i class="fas fa-users mb-3" style="font-size: 40px; color: #cbd5e1;"></i>
            <h5>No students enrolled yet</h5>
            <p class="mb-0">Click 'Add New Student' to begin adding records.</p>
        </div>
    <?php else: foreach($students as $index => $std): ?>
        
        <div class="student-card" id="studentCard-<?php echo $std['user_id']; ?>">
            <div class="student-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3 flex-grow-1">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($std['name']); ?>&background=113460&color=fff" class="rounded-circle shadow-sm" width="48" height="48" alt="Avatar">
                    <div>
                        <h6 class="fw-bold mb-1 text-dark" style="font-size: 16px;"><?php echo htmlspecialchars($std['name']); ?></h6>
                        <small class="text-muted fw-medium d-flex gap-3">
                            <span><i class="fas fa-id-card me-1"></i> <?php echo htmlspecialchars($std['user_id']); ?></span>
                            <span><i class="fas fa-code-branch me-1"></i> <?php echo htmlspecialchars($std['department']); ?></span>
                            <span><i class="fas fa-graduation-cap me-1"></i> <?php echo htmlspecialchars($std['designation']); ?></span>
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status badge-active"><i class="fas fa-circle" style="font-size: 8px;"></i> Active</span>
                    <a href="?delete=<?php echo urlencode($std['user_id']); ?>" class="btn btn-sm btn-outline-danger px-3 py-2 fw-semibold border-0" onclick="return confirm('Are you sure you want to remove <?php echo htmlspecialchars($std['name']); ?>?');">
                        <i class="fa-solid fa-trash-can me-1"></i> Remove
                    </a>
                </div>
            </div>
        </div>

    <?php endforeach; endif; ?>
</div>

<!-- ADD STUDENT MODAL -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 mt-2 px-4">
                <h5 class="modal-title fw-bold text-dark">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">ENROLLMENT NO.</label>
                        <input type="text" name="enrollment" class="form-control" placeholder="e.g. 246310307055" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">FULL NAME</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Mansuri Arman" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">BRANCH</label>
                            <select name="branch" class="form-select">
                                <option value="Computer Engineering">Computer Engg.</option>
                                <option value="Mechanical Engineering">Mechanical Engg.</option>
                                <option value="Civil Engineering">Civil Engg.</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">SEMESTER</label>
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
                    <div>
                        <button type="submit" name="add_student" class="btn btn-primary w-100 py-2 fs-6">Save Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>