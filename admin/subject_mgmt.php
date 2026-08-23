<?php
include '../db.php';
include 'header.php';

$msg = "";

// Handle Add Subject
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_subject'])) {
    $sub_name = $conn->real_escape_string($_POST['subject_name']);
    $dept     = $conn->real_escape_string($_POST['department']);
    $sem      = $conn->real_escape_string($_POST['semester']);

    $check = $conn->query("SELECT id FROM subjects WHERE subject_name = '$sub_name' AND department = '$dept' AND semester = '$sem'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger'><i class='fas fa-triangle-exclamation'></i> Subject already exists in this branch &amp; semester!</div>";
    } else {
        if ($conn->query("INSERT INTO subjects (subject_name, department, semester) VALUES ('$sub_name', '$dept', '$sem')")) {
            $msg = "<div class='alert alert-success'><i class='fas fa-circle-check'></i> Subject Added Successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger'><i class='fas fa-triangle-exclamation'></i> Error: " . $conn->error . "</div>";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $conn->query("DELETE FROM subjects WHERE id = '" . $conn->real_escape_string($_GET['delete']) . "'");
    $_SESSION['success_message'] = "Subject deleted successfully.";
    echo "<script>window.location.href='subject_mgmt.php';</script>";
    exit();
}

// Fetch Subjects
$dept_filter = isset($_GET['department']) ? $_GET['department'] : 'Computer Engineering';
$sem_filter  = isset($_GET['semester'])   ? $_GET['semester']   : 'Semester 3';
$safe_dept   = $conn->real_escape_string($dept_filter);
$safe_sem    = $conn->real_escape_string($sem_filter);

$subjects = [];
$res = $conn->query("SELECT * FROM subjects WHERE department = '$safe_dept' AND semester = '$safe_sem' ORDER BY id DESC");
if ($res) { while ($row = $res->fetch_assoc()) { $subjects[] = $row; } }

// Total count
$total_sub_res = $conn->query("SELECT COUNT(*) as total FROM subjects");
$total_subjects = ($total_sub_res) ? $total_sub_res->fetch_assoc()['total'] : 0;
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Subject Management</h1>
        <p class="page-subtitle">Add curriculum subjects and filter them by branch and semester.</p>
    </div>
    <span class="badge-status badge-info" style="padding: 8px 16px; font-size: 13px;">
        <i class="fas fa-book-open me-1"></i> <?php echo $total_subjects; ?> Total Subjects
    </span>
</div>

<?php if($msg != "") echo $msg; ?>

<div class="row g-4">
    <!-- FORM -->
    <div class="col-md-4">
        <div class="content-box">
            <div class="section-title"><i class="fas fa-plus-circle"></i> Add New Subject</div>
            <form method="POST" style="display: grid; gap: 14px;">
                <div>
                    <label class="form-label">Subject Name</label>
                    <div class="input-with-icon">
                        <i class="fas fa-book"></i>
                        <input type="text" name="subject_name" class="form-control" placeholder="e.g. Data Structures" required>
                    </div>
                </div>
                <div>
                    <label class="form-label">Department / Branch</label>
                    <select name="department" class="form-select">
                        <option value="Computer Engineering">Computer Engineering</option>
                        <option value="Electrical Engineering">Electrical Engineering</option>
                        <option value="Mechanical Engineering">Mechanical Engineering</option>
                        <option value="Civil Engineering">Civil Engineering</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-select">
                        <?php for($i=1; $i<=6; $i++): ?>
                            <option value="Semester <?php echo $i; ?>" <?php if($i==3) echo 'selected'; ?>>Semester <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" name="add_subject" class="btn btn-primary btn-w-full" style="margin-top: 4px;">
                    <i class="fas fa-plus"></i> Add Subject
                </button>
            </form>
        </div>
    </div>

    <!-- TABLE -->
    <div class="col-md-8">
        <div class="content-box" style="padding: 0; overflow: hidden;">
            <!-- Header with filters -->
            <div style="padding: 16px 22px; border-bottom: 1px solid var(--card-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div class="section-title mb-0"><i class="fas fa-table-list"></i> Curriculum Subjects</div>
                <form method="GET" style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <select name="department" class="form-select" style="width: auto; font-size: 12.5px; padding: 7px 32px 7px 12px;" onchange="this.form.submit()">
                        <?php
                        $depts = ['Computer Engineering', 'Electrical Engineering', 'Mechanical Engineering', 'Civil Engineering'];
                        foreach($depts as $d): ?>
                            <option value="<?php echo $d; ?>" <?php if($dept_filter == $d) echo 'selected'; ?>><?php echo $d; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="semester" class="form-select" style="width: auto; font-size: 12.5px; padding: 7px 32px 7px 12px;" onchange="this.form.submit()">
                        <?php for($i=1; $i<=6; $i++): ?>
                            <option value="Semester <?php echo $i; ?>" <?php if($sem_filter == "Semester $i") echo 'selected'; ?>>Sem <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>

            <?php if(empty($subjects)): ?>
                <div class="empty-state" style="border: none;">
                    <div class="empty-state-icon"><i class="fas fa-book-open"></i></div>
                    <h5>No Subjects Found</h5>
                    <p>No subjects for <strong><?php echo htmlspecialchars($dept_filter); ?></strong> – <?php echo htmlspecialchars($sem_filter); ?></p>
                </div>
            <?php else: ?>
                <table class="table-custom" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="padding-left: 22px;">#</th>
                            <th>Subject Name</th>
                            <th>Department</th>
                            <th>Semester</th>
                            <th>Status</th>
                            <th class="text-end" style="padding-right: 22px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($subjects as $i => $sub): ?>
                        <tr>
                            <td style="padding-left: 22px; color: var(--text-muted); font-size: 12px;"><?php echo $i + 1; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 34px; height: 34px; background: var(--blue-light); border-radius: 9px; display: flex; align-items: center; justify-content: center; color: var(--brand-accent); font-size: 14px;">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <span style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($sub['subject_name']); ?></span>
                                </div>
                            </td>
                            <td style="font-size: 13px; color: var(--text-secondary);"><?php echo htmlspecialchars($sub['department']); ?></td>
                            <td>
                                <span style="background: var(--purple-light); color: #6d28d9; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 6px;">
                                    <?php echo htmlspecialchars($sub['semester']); ?>
                                </span>
                            </td>
                            <td><span class="badge-status badge-active"><i class="fas fa-circle" style="font-size: 6px;"></i> Active</span></td>
                            <td class="text-end" style="padding-right: 22px;">
                                <a href="?delete=<?php echo urlencode($sub['id']); ?>&department=<?php echo urlencode($dept_filter); ?>&semester=<?php echo urlencode($sem_filter); ?>"
                                   class="btn btn-outline-danger btn-sm"
                                   onclick="return confirm('Remove this subject?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>