<?php
include '../db.php';
include 'header.php';

$msg = "";

// Handle Add Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $enrollment = $conn->real_escape_string($_POST['enrollment']);
    $name = $conn->real_escape_string($_POST['name']);
    $branch = $conn->real_escape_string($_POST['branch']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $password = 'std123';

    $check = $conn->query("SELECT user_id FROM users WHERE user_id = '$enrollment'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger'><i class='fas fa-triangle-exclamation'></i> Enrollment No. already exists!</div>";
    } else {
        $insert_query = "INSERT INTO users (user_id, name, password, role, department, designation, status) 
                         VALUES ('$enrollment', '$name', '$password', 'student', '$branch', '$semester', 'Active')";
        if ($conn->query($insert_query)) {
            $msg = "<div class='alert alert-success'><i class='fas fa-circle-check'></i> Student Added Successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger'><i class='fas fa-triangle-exclamation'></i> Error: " . $conn->error . "</div>";
        }
    }
}

// Handle Delete
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
if ($res) { while ($row = $res->fetch_assoc()) { $students[] = $row; } }
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Student Management</h1>
        <p class="page-subtitle">Manage student admissions and academic branch records.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal" id="addStudentBtn">
        <i class="fas fa-plus"></i> Add New Student
    </button>
</div>

<?php if($msg != "") echo $msg; ?>

<!-- STATS ROW -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; max-width: 600px;">
    <div style="background: #fff; border-radius: 12px; border: 1px solid var(--card-border); padding: 16px 18px; box-shadow: var(--card-shadow);">
        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Total</div>
        <div style="font-size: 26px; font-weight: 800; color: var(--text-primary);"><?php echo count($students); ?></div>
    </div>
    <div style="background: #fff; border-radius: 12px; border: 1px solid var(--card-border); padding: 16px 18px; box-shadow: var(--card-shadow);">
        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Active</div>
        <div style="font-size: 26px; font-weight: 800; color: #10b981;"><?php echo count($students); ?></div>
    </div>
    <div style="background: #fff; border-radius: 12px; border: 1px solid var(--card-border); padding: 16px 18px; box-shadow: var(--card-shadow);">
        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Branches</div>
        <div style="font-size: 26px; font-weight: 800; color: #3b82f6;"><?php echo count(array_unique(array_column($students, 'department'))); ?></div>
    </div>
</div>

<!-- STUDENT LIST -->
<div class="content-box" style="padding: 0; overflow: hidden;">
    <div style="padding: 18px 22px; border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center;">
        <div class="section-title mb-0"><i class="fas fa-users"></i> Enrolled Students</div>
        <div style="font-size: 12.5px; color: var(--text-muted);"><?php echo count($students); ?> records found</div>
    </div>

    <?php if(empty($students)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-user-graduate"></i></div>
            <h5>No Students Enrolled Yet</h5>
            <p>Click 'Add New Student' to begin adding records.</p>
        </div>
    <?php else: ?>
        <table class="table-custom" style="margin: 0;">
            <thead>
                <tr>
                    <th style="padding-left: 22px;">#</th>
                    <th>Student</th>
                    <th>Enrollment No.</th>
                    <th>Branch</th>
                    <th>Semester</th>
                    <th>Status</th>
                    <th class="text-end" style="padding-right: 22px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($students as $i => $std): ?>
                <tr>
                    <td style="padding-left: 22px; color: var(--text-muted); font-size: 12px;"><?php echo $i + 1; ?></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 11px;">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($std['name']); ?>&background=0f172a&color=fff&bold=true&size=48"
                                 style="width: 38px; height: 38px; border-radius: 10px;" alt="">
                            <div>
                                <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($std['name']); ?></div>
                                <div style="font-size: 11.5px; color: var(--text-muted);">Default pass: std123</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="font-family: monospace; font-size: 13px; font-weight: 600; color: var(--brand-accent); background: var(--blue-light); padding: 4px 10px; border-radius: 6px;">
                            <?php echo htmlspecialchars($std['user_id']); ?>
                        </span>
                    </td>
                    <td style="color: var(--text-secondary); font-size: 13px;"><?php echo htmlspecialchars($std['department'] ?? '–'); ?></td>
                    <td style="color: var(--text-secondary); font-size: 13px;"><?php echo htmlspecialchars($std['designation'] ?? '–'); ?></td>
                    <td><span class="badge-status badge-active"><i class="fas fa-circle" style="font-size: 6px;"></i> Active</span></td>
                    <td class="text-end" style="padding-right: 22px;">
                        <a href="?delete=<?php echo urlencode($std['user_id']); ?>" 
                           class="btn btn-outline-danger btn-sm"
                           onclick="return confirm('Remove <?php echo htmlspecialchars($std['name']); ?> from the system?');">
                            <i class="fas fa-trash"></i> Remove
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- ADD STUDENT MODAL -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Add New Student</h5>
                    <p style="font-size: 12.5px; color: var(--text-muted); margin: 2px 0 0;">Default password: <code>std123</code></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addStudentForm">
                    <div style="display: grid; gap: 16px;">
                        <div>
                            <label class="form-label">Enrollment No.</label>
                            <div class="input-with-icon">
                                <i class="fas fa-id-card"></i>
                                <input type="text" name="enrollment" class="form-control" placeholder="e.g. 246310307055" required>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Full Name</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Mansuri Arman" required>
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <div>
                                <label class="form-label">Branch</label>
                                <select name="branch" class="form-select">
                                    <option value="Computer Engineering">Computer Engineering</option>
                                    <option value="Mechanical Engineering">Mechanical Engineering</option>
                                    <option value="Civil Engineering">Civil Engineering</option>
                                    <option value="Electrical Engineering">Electrical Engineering</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Semester</label>
                                <select name="semester" class="form-select">
                                    <?php for($i=1; $i<=6; $i++): ?>
                                        <option value="Semester <?php echo $i; ?>">Semester <?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="add_student" class="btn btn-primary btn-w-full" style="margin-top: 4px;">
                            <i class="fas fa-user-plus"></i> Save Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>