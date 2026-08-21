<?php
include '../db.php';
include 'header.php';

$msg = "";

// 2. Handle Add Subject
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_subject'])) {
    $sub_name = $conn->real_escape_string($_POST['subject_name']);
    $dept = $conn->real_escape_string($_POST['department']);
    $sem = $conn->real_escape_string($_POST['semester']);

    $check = $conn->query("SELECT id FROM subjects WHERE subject_name = '$sub_name' AND department = '$dept' AND semester = '$sem'");
    
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger shadow-sm border-0'><i class='fas fa-exclamation-circle me-2'></i> Subject already exists in this branch & semester!</div>";
    } else {
        $insert_query = "INSERT INTO subjects (subject_name, department, semester) VALUES ('$sub_name', '$dept', '$sem')";
        if ($conn->query($insert_query)) {
            $msg = "<div class='alert alert-success shadow-sm border-0'><i class='fas fa-check-circle me-2'></i> Subject Added Successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm border-0'>Error: " . $conn->error . "</div>";
        }
    }
}

// 3. Handle Delete Subject
if (isset($_GET['delete'])) {
    $del_id = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM subjects WHERE id = '$del_id'");
    $_SESSION['success_message'] = "Subject deleted successfully.";
    echo "<script>window.location.href='subject_mgmt.php';</script>";
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

<!-- HEADER TITLE -->
<div class="page-header mt-2 mb-4">
    <div>
        <h4 class="page-title">Subject Management</h4>
        <p class="page-subtitle">Add new curriculum subjects and filter them by branch and semester.</p>
    </div>
</div>

<?php if($msg != "") echo $msg; ?>

<div class="row g-4">
    <!-- ADD SUBJECT FORM -->
    <div class="col-md-4">
        <div class="content-box">
            <h6 class="fw-bold text-dark mb-4">Add New Subject</h6>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">SUBJECT NAME</label>
                    <input type="text" name="subject_name" class="form-control" placeholder="e.g. Data Structures" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">DEPARTMENT / BRANCH</label>
                    <select name="department" class="form-select">
                        <option value="Computer Engineering">Computer Engineering</option>
                        <option value="Electrical Engineering">Electrical Engineering</option>
                        <option value="Mechanical Engineering">Mechanical Engineering</option>
                        <option value="Civil Engineering">Civil Engineering</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label">SEMESTER</label>
                    <select name="semester" class="form-select">
                        <?php for($i=1; $i<=6; $i++): ?>
                            <option value="Semester <?php echo $i; ?>" <?php if($i == 3) echo 'selected'; ?>>Semester <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" name="add_subject" class="btn btn-primary w-100 py-2 fs-6">Add Subject</button>
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
                    <select name="department" class="form-select form-select-sm shadow-none" style="width: auto; padding: 6px 30px 6px 12px;" onchange="this.form.submit()">
                        <option value="Computer Engineering" <?php if($dept_filter == 'Computer Engineering') echo 'selected'; ?>>Computer Engineering</option>
                        <option value="Electrical Engineering" <?php if($dept_filter == 'Electrical Engineering') echo 'selected'; ?>>Electrical Engineering</option>
                        <option value="Mechanical Engineering" <?php if($dept_filter == 'Mechanical Engineering') echo 'selected'; ?>>Mechanical Engineering</option>
                        <option value="Civil Engineering" <?php if($dept_filter == 'Civil Engineering') echo 'selected'; ?>>Civil Engineering</option>
                    </select>
                    <select name="semester" class="form-select form-select-sm shadow-none" style="width: auto; padding: 6px 30px 6px 12px;" onchange="this.form.submit()">
                        <?php for($i=1; $i<=6; $i++): ?>
                            <option value="Semester <?php echo $i; ?>" <?php if($sem_filter == "Semester $i") echo 'selected'; ?>>Semester <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
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
                                    <div class="fw-medium" style="font-size: 13px; color: var(--text-main);"><?php echo htmlspecialchars($sub['department']); ?></div>
                                    <small class="text-muted" style="font-size: 11px;"><?php echo htmlspecialchars($sub['semester']); ?></small>
                                </td>
                                <td><span class="badge-status badge-active"><i class="fas fa-check" style="font-size:10px;"></i> Active</span></td>
                                <td class="text-end">
                                    <a href="?delete=<?php echo urlencode($sub['id']); ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Are you sure you want to remove this subject?');">
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

<?php include 'footer.php'; ?>