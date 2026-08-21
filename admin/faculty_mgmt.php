<?php
include '../db.php';
include 'header.php';

$msg = "";

// 2. Handle Add Faculty
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_faculty'])) {
    $fac_id = $conn->real_escape_string($_POST['user_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $password = $conn->real_escape_string($_POST['password']);
    $dept = $conn->real_escape_string($_POST['department']);
    $designation = $conn->real_escape_string($_POST['designation']);

    $check = $conn->query("SELECT user_id FROM users WHERE user_id = '$fac_id'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger shadow-sm border-0'><i class='fas fa-exclamation-circle me-2'></i> Faculty ID already exists!</div>";
    } else {
        $insert_query = "INSERT INTO users (user_id, name, password, role, department, designation, status) 
                         VALUES ('$fac_id', '$name', '$password', 'faculty', '$dept', '$designation', 'Active')";
        if ($conn->query($insert_query)) {
            $msg = "<div class='alert alert-success shadow-sm border-0'><i class='fas fa-check-circle me-2'></i> Faculty Added Successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm border-0'>Error: " . $conn->error . "</div>";
        }
    }
}

// 3. Handle Delete Faculty
if (isset($_GET['delete'])) {
    $del_id = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM users WHERE user_id = '$del_id' AND role = 'faculty'");
    $_SESSION['success_message'] = "Faculty removed successfully.";
    echo "<script>window.location.href='faculty_mgmt.php';</script>";
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

<!-- HEADER TITLE -->
<div class="page-header mt-2 mb-4">
    <div>
        <h4 class="page-title">Faculty Management</h4>
        <p class="page-subtitle">Add new teaching staff and manage their portal access.</p>
    </div>
</div>

<?php if($msg != "") echo $msg; ?>

<div class="row g-4">
    <!-- ADD FACULTY FORM -->
    <div class="col-md-4">
        <div class="content-box">
            <h6 class="fw-bold text-dark mb-4">Add New Faculty</h6>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">FACULTY ID / EMP CODE</label>
                    <input type="text" name="user_id" class="form-control" placeholder="e.g. FAC001" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">FULL NAME</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Rahul Sir" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">PASSWORD</label>
                    <input type="text" name="password" class="form-control" value="kdp123" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">DEPARTMENT</label>
                    <select name="department" class="form-select">
                        <option>Computer Engineering</option>
                        <option>Mechanical Engineering</option>
                        <option>Civil Engineering</option>
                        <option>Electrical Engineering</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="form-label">DESIGNATION</label>
                    <input type="text" name="designation" class="form-control" value="Assistant Professor">
                </div>
                <button type="submit" name="add_faculty" class="btn btn-primary w-100 py-2 fs-6">Add Faculty Member</button>
            </form>
        </div>
    </div>

    <!-- FACULTY LIST TABLE -->
    <div class="col-md-8">
        <div class="content-box h-100">
            <h6 class="fw-bold text-dark mb-3">Active Teaching Staff</h6>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Emp ID</th>
                            <th>Name & Designation</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($faculties)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No faculty added yet.</td></tr>
                        <?php else: foreach($faculties as $fac): ?>
                            <tr>
                                <td class="fw-semibold text-primary"><?php echo htmlspecialchars($fac['user_id']); ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($fac['name']); ?></div>
                                    <small class="text-muted" style="font-size: 12px;"><?php echo htmlspecialchars($fac['designation']); ?></small>
                                </td>
                                <td class="text-muted" style="font-size: 13px;"><?php echo htmlspecialchars($fac['department']); ?></td>
                                <td><span class="badge-status badge-active"><i class="fas fa-check-circle me-1" style="font-size:10px;"></i><?php echo $fac['status']; ?></span></td>
                                <td class="text-end">
                                    <a href="?delete=<?php echo urlencode($fac['user_id']); ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Are you sure you want to delete this faculty member?');">
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