<?php
include '../db.php';
include 'header.php';

$msg = "";

// Handle Add Faculty
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_faculty'])) {
    $fac_id      = $conn->real_escape_string($_POST['user_id']);
    $name        = $conn->real_escape_string($_POST['name']);
    $password    = $conn->real_escape_string($_POST['password']);
    $dept        = $conn->real_escape_string($_POST['department']);
    $designation = $conn->real_escape_string($_POST['designation']);

    $check = $conn->query("SELECT user_id FROM users WHERE user_id = '$fac_id'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger'><i class='fas fa-triangle-exclamation'></i> Faculty ID already exists!</div>";
    } else {
        $insert_query = "INSERT INTO users (user_id, name, password, role, department, designation, status) 
                         VALUES ('$fac_id', '$name', '$password', 'faculty', '$dept', '$designation', 'Active')";
        if ($conn->query($insert_query)) {
            $msg = "<div class='alert alert-success'><i class='fas fa-circle-check'></i> Faculty Added Successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger'><i class='fas fa-triangle-exclamation'></i> Error: " . $conn->error . "</div>";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $del_id = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM users WHERE user_id = '$del_id' AND role = 'faculty'");
    $_SESSION['success_message'] = "Faculty removed successfully.";
    echo "<script>window.location.href='faculty_mgmt.php';</script>";
    exit();
}

// Fetch All Faculty
$faculties = [];
$res = $conn->query("SELECT * FROM users WHERE role = 'faculty' ORDER BY id DESC");
if ($res) { while ($row = $res->fetch_assoc()) { $faculties[] = $row; } }
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Faculty Management</h1>
        <p class="page-subtitle">Add teaching staff and manage their portal access credentials.</p>
    </div>
</div>

<?php if($msg != "") echo $msg; ?>

<div class="row g-4">
    <!-- ADD FACULTY FORM -->
    <div class="col-md-4">
        <div class="content-box">
            <div class="section-title"><i class="fas fa-user-plus"></i> Add New Faculty</div>
            <form method="POST" style="display: grid; gap: 14px;">
                <div>
                    <label class="form-label">Faculty ID / Employee Code</label>
                    <div class="input-with-icon">
                        <i class="fas fa-id-badge"></i>
                        <input type="text" name="user_id" class="form-control" placeholder="e.g. FAC-003" required>
                    </div>
                </div>
                <div>
                    <label class="form-label">Full Name</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Dr. Rahul Mehta" required>
                    </div>
                </div>
                <div>
                    <label class="form-label">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="text" name="password" class="form-control" value="kdp123" required>
                    </div>
                </div>
                <div>
                    <label class="form-label">Department</label>
                    <select name="department" class="form-select">
                        <option>Computer Engineering</option>
                        <option>Mechanical Engineering</option>
                        <option>Civil Engineering</option>
                        <option>Electrical Engineering</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Designation</label>
                    <div class="input-with-icon">
                        <i class="fas fa-briefcase"></i>
                        <input type="text" name="designation" class="form-control" value="Assistant Professor">
                    </div>
                </div>
                <button type="submit" name="add_faculty" class="btn btn-primary btn-w-full" style="margin-top: 4px;">
                    <i class="fas fa-plus"></i> Add Faculty Member
                </button>
            </form>
        </div>
    </div>

    <!-- FACULTY TABLE -->
    <div class="col-md-8">
        <div class="content-box" style="padding: 0; overflow: hidden;">
            <div style="padding: 18px 22px; border-bottom: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center;">
                <div class="section-title mb-0"><i class="fas fa-chalkboard-user"></i> Teaching Staff</div>
                <span class="badge-status badge-info"><?php echo count($faculties); ?> Members</span>
            </div>

            <?php if(empty($faculties)): ?>
                <div class="empty-state" style="border: none;">
                    <div class="empty-state-icon"><i class="fas fa-chalkboard-user"></i></div>
                    <h5>No Faculty Added Yet</h5>
                    <p>Use the form to add your first faculty member.</p>
                </div>
            <?php else: ?>
                <table class="table-custom" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="padding-left: 22px;">Faculty</th>
                            <th>Employee ID</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th class="text-end" style="padding-right: 22px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($faculties as $fac): ?>
                        <tr>
                            <td style="padding-left: 22px;">
                                <div style="display: flex; align-items: center; gap: 11px;">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fac['name']); ?>&background=1e3a5f&color=fff&bold=true&size=48"
                                         style="width: 38px; height: 38px; border-radius: 10px;" alt="">
                                    <div>
                                        <div style="font-weight: 600; font-size: 14px;"><?php echo htmlspecialchars($fac['name']); ?></div>
                                        <div style="font-size: 11.5px; color: var(--text-muted);"><?php echo htmlspecialchars($fac['designation']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-family: monospace; font-size: 12.5px; font-weight: 600; color: #6366f1; background: #f5f3ff; padding: 4px 10px; border-radius: 6px;">
                                    <?php echo htmlspecialchars($fac['user_id']); ?>
                                </span>
                            </td>
                            <td style="color: var(--text-secondary); font-size: 13px;"><?php echo htmlspecialchars($fac['department']); ?></td>
                            <td><span class="badge-status badge-active"><i class="fas fa-circle" style="font-size: 6px;"></i> Active</span></td>
                            <td class="text-end" style="padding-right: 22px;">
                                <a href="?delete=<?php echo urlencode($fac['user_id']); ?>"
                                   class="btn btn-outline-danger btn-sm"
                                   onclick="return confirm('Remove <?php echo htmlspecialchars($fac['name']); ?> from the system?');">
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