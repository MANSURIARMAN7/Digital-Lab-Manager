<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$msg = "";

// ==========================================
// 1. ADD FACULTY LOGIC (Fixed Email Unique Error)
// ==========================================
if (isset($_POST['add_faculty'])) {
    $emp_id = $conn->real_escape_string(trim($_POST['emp_id']));
    $name = $conn->real_escape_string(trim($_POST['name']));
    $dept = $conn->real_escape_string(trim($_POST['department']));
    $designation = $conn->real_escape_string(trim($_POST['designation']));
    $email = trim($_POST['email']);
    
    // SMART FIX: If email is empty, set it to NULL to avoid UNIQUE constraint duplicate errors
    $email_val = empty($email) ? "NULL" : "'" . $conn->real_escape_string($email) . "'";
    $default_pass = "123456";

    // Duplicate Check
    $check = $conn->query("SELECT user_id FROM users WHERE user_id='$emp_id'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-exclamation-circle me-2'></i> <b>Duplicate Entry!</b> Employee ID '$emp_id' already exists.</div>";
    } else {
        $sql = "INSERT INTO users (user_id, name, email, password, role, department, designation) 
                VALUES ('$emp_id', '$name', $email_val, '$default_pass', 'faculty', '$dept', '$designation')";
        if ($conn->query($sql)) {
            $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i> Faculty <b>$name</b> added successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-times-circle me-2'></i> Database Error: " . $conn->error . "</div>";
        }
    }
}

// ==========================================
// 2. EDIT FACULTY LOGIC
// ==========================================
if (isset($_POST['edit_faculty'])) {
    $emp_id = $conn->real_escape_string(trim($_POST['edit_emp_id']));
    $name = $conn->real_escape_string(trim($_POST['edit_name']));
    $dept = $conn->real_escape_string(trim($_POST['edit_department']));
    $designation = $conn->real_escape_string(trim($_POST['edit_designation']));
    $email = trim($_POST['edit_email']);
    
    $email_val = empty($email) ? "NULL" : "'" . $conn->real_escape_string($email) . "'";
    
    $conn->query("UPDATE users SET name='$name', department='$dept', designation='$designation', email=$email_val WHERE user_id='$emp_id' AND role='faculty'");
    $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i> Faculty details updated successfully!</div>";
}

// ==========================================
// 3. SAFE DELETE LOGIC
// ==========================================
if (isset($_POST['delete_faculty_btn'])) {
    $del_id = $conn->real_escape_string($_POST['delete_id']);
    
    // Deleting faculty
    if($conn->query("DELETE FROM users WHERE user_id='$del_id' AND role='faculty'")) {
        $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-trash-alt me-2'></i> Faculty record securely deleted.</div>";
    } else {
        $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-exclamation-triangle me-2'></i> Error deleting record!</div>";
    }
}

// ==========================================
// 4. RESET PASSWORD LOGIC
// ==========================================
if (isset($_POST['reset_pass_btn'])) {
    $reset_id = $conn->real_escape_string($_POST['reset_id']);
    $conn->query("UPDATE users SET password='123456' WHERE user_id='$reset_id' AND role='faculty'");
    $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-key me-2'></i> Password for Employee '$reset_id' has been reset to <b>123456</b>.</div>";
}

// ==========================================
// 5. FETCH, FILTER, SEARCH & SORTING
// ==========================================
$where = "role='faculty'";

// Department Filter
$dept_filter = $_GET['dept'] ?? 'all';
if ($dept_filter !== 'all') {
    $safe_dept = $conn->real_escape_string($dept_filter);
    $where .= " AND department = '$safe_dept'";
}

// Search
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $safe_search = $conn->real_escape_string($search);
    $where .= " AND (name LIKE '%$safe_search%' OR user_id LIKE '%$safe_search%')";
}

// Sorting
$sort = $_GET['sort'] ?? 'name_asc';
$order_by = "name ASC";
if ($sort == 'name_desc') $order_by = "name DESC";
elseif ($sort == 'emp_asc') $order_by = "user_id ASC";
elseif ($sort == 'emp_desc') $order_by = "user_id DESC";

$faculty_list = $conn->query("SELECT * FROM users WHERE $where ORDER BY $order_by");
$total_faculty = $faculty_list->num_rows;

// Fetch unique departments for filter dropdown
$dept_query = $conn->query("SELECT DISTINCT department FROM users WHERE role='faculty' AND department != ''");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Management - Admin Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --sidebar-width: 270px;
            --primary: #4338ca;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* FIX: Uniform body styling to prevent scrollbar shifting */
        * { box-sizing: border-box; }
        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        /* SIDEBAR (Perfectly Uniform across all pages) */
        .sidebar { width: var(--sidebar-width); min-width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}

        /* MAIN CONTENT - scrollbar fixed to avoid width shift */
        .main { flex: 1; padding: 30px 45px; overflow-y: scroll; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* TOPBAR & PROFILE PILL */
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .profile-pill { display: flex; align-items: center; background: linear-gradient(135deg, #1e3a8a, #4338ca); padding: 12px 25px 12px 30px; border-radius: 50px; cursor: pointer; text-decoration: none; color: white; transition: var(--transition-bounce); box-shadow: 0 10px 20px rgba(67, 56, 202, 0.2); border: 2px solid rgba(255,255,255,0.1);}
        .profile-pill:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(67, 56, 202, 0.3); border-color: rgba(255,255,255,0.3);}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: #bfdbfe; font-weight: 800; text-transform: uppercase; margin-bottom: 2px; letter-spacing: 0.5px;}
        .profile-name { margin: 0; font-size: 18px; color: white; font-weight: 900;}
        .profile-avatar { background: white; color: var(--primary); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);}

        /* HEADER & TOOLBAR */
        .page-header { background: white; border-radius: 16px; padding: 20px 25px; margin-bottom: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;}
        
        .toolbar { background: linear-gradient(135deg, rgba(67, 56, 202, 0.03), rgba(59, 130, 246, 0.05)); border: 1px solid rgba(67, 56, 202, 0.1); border-radius: 16px; padding: 20px; margin-bottom: 25px; }
        .form-select, .form-control { border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 14px;}
        .form-select:focus, .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);}

        /* TABLE */
        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; }
        
        .emp-link { color: var(--primary); font-weight: 800; text-decoration: none; cursor: pointer; transition: 0.2s;}
        .emp-link:hover { color: #1e3a8a; text-decoration: underline; }

        .btn-action-delete { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: none; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; transition: 0.2s; }
        .btn-action-delete:hover { background: #dc2626; color: white; transform: translateY(-2px); }
        
        .btn-action-reset { background: rgba(245, 158, 11, 0.1); color: #d97706; border: none; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; transition: 0.2s; }
        .btn-action-reset:hover { background: #d97706; color: white; transform: translateY(-2px); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <!-- UNIFORM SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
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

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- UNIFORM TOPBAR -->
        <div class="topbar">
            <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            
            <a href="Profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Welcome Back,</span>
                    <h4 class="profile-name">Head of Department</h4>
                </div>
                <div class="profile-avatar"><i class="fas fa-user-tie"></i></div>
            </a>
        </div>
        
        <?php echo $msg; ?>

        <!-- HEADER -->
        <div class="page-header">
            <div>
                <h3 class="fw-bold text-dark mb-1"><i class="fas fa-chalkboard-teacher text-primary me-2"></i> Faculty Management</h3>
                <p class="text-muted fw-semibold small mb-0">Manage faculty records and assigned departments.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary fw-bold px-4" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-user-plus me-2"></i> Add Faculty</button>
            </div>
        </div>

        <!-- TOOLBAR (Filters & Search) -->
        <div class="toolbar">
            <form method="GET" action="faculty_mgmt.php" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-primary text-uppercase">Department Filter</label>
                    <select name="dept" class="form-select bg-white">
                        <option value="all" <?php echo $dept_filter == 'all' ? 'selected' : ''; ?>>All Departments</option>
                        <?php if($dept_query && $dept_query->num_rows > 0): ?>
                            <?php while($d = $dept_query->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($d['department']); ?>" <?php echo $dept_filter == $d['department'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['department']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-primary text-uppercase">Sort By</label>
                    <select name="sort" class="form-select bg-white">
                        <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                        <option value="emp_asc" <?php echo $sort == 'emp_asc' ? 'selected' : ''; ?>>Employee ID (Ascending)</option>
                        <option value="emp_desc" <?php echo $sort == 'emp_desc' ? 'selected' : ''; ?>>Employee ID (Descending)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-primary text-uppercase">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search ID or Name..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100">Apply</button>
                    <a href="faculty_mgmt.php" class="btn btn-outline-secondary fw-bold w-100">Clear</a>
                </div>
            </form>
        </div>

        <!-- DATA TABLE -->
        <div class="content-box">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0">Registered Faculty</h5>
                <span class="badge bg-primary px-3 py-2 rounded-pill">Total Faculty: <?php echo $total_faculty; ?></span>
            </div>
            
            <div class="table-responsive">
                <table class="table table-custom w-100" id="facultyTable">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Faculty Name & Email</th>
                            <th>Department & Role</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($faculty_list->num_rows > 0): ?>
                            <?php while($row = $faculty_list->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="#" class="emp-link" onclick="openEditModal('<?php echo $row['user_id']; ?>', '<?php echo htmlspecialchars(addslashes($row['name'])); ?>', '<?php echo htmlspecialchars($row['department']); ?>', '<?php echo htmlspecialchars(addslashes($row['designation'])); ?>', '<?php echo htmlspecialchars(addslashes($row['email'])); ?>')">
                                            <?php echo htmlspecialchars($row['user_id']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($row['email'] ?: 'No Email'); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($row['department']); ?></div>
                                        <span class="badge bg-light text-dark border border-secondary mt-1"><?php echo htmlspecialchars($row['designation'] ?: 'Faculty Member'); ?></span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn-action-reset me-1" onclick="openResetModal('<?php echo $row['user_id']; ?>', '<?php echo htmlspecialchars(addslashes($row['name'])); ?>')" title="Reset Password to 123456">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <button type="button" class="btn-action-delete" onclick="openDeleteModal('<?php echo $row['user_id']; ?>', '<?php echo htmlspecialchars(addslashes($row['name'])); ?>')" title="Delete Faculty">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted fw-bold">No faculty records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODALS -->

    <!-- 1. Add Faculty Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i> Add New Faculty</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="alert alert-info small fw-bold mb-3"><i class="fas fa-info-circle me-1"></i> Default login password: <b>123456</b></div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" name="emp_id" class="form-control" required placeholder="e.g. FAC001">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="Dr. / Prof. Name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Email Address <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="email" name="email" class="form-control" placeholder="faculty@example.com">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Department <span class="text-danger">*</span></label>
                                <select name="department" class="form-select" required>
                                    <option value="" disabled selected>-- Select --</option>
                                    <option value="Computer Engineering">Computer Engineering</option>
                                    <option value="IT Engineering">IT Engineering</option>
                                    <option value="Civil Engineering">Civil Engineering</option>
                                    <option value="Mechanical Engineering">Mechanical Engineering</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Designation</label>
                                <input type="text" name="designation" class="form-control" placeholder="e.g. Senior Lecturer">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 rounded-bottom-4">
                        <button type="submit" name="add_faculty" class="btn btn-primary fw-bold px-4 w-100">Save Faculty</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. Edit Faculty Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-dark text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2"></i> Edit Faculty Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Employee ID (Locked)</label>
                            <input type="text" name="edit_emp_id" id="edit_emp_id" class="form-control bg-light" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="edit_name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Email Address</label>
                            <input type="email" name="edit_email" id="edit_email" class="form-control">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Department <span class="text-danger">*</span></label>
                                <select name="edit_department" id="edit_department" class="form-select" required>
                                    <option value="Computer Engineering">Computer Engineering</option>
                                    <option value="IT Engineering">IT Engineering</option>
                                    <option value="Civil Engineering">Civil Engineering</option>
                                    <option value="Mechanical Engineering">Mechanical Engineering</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Designation</label>
                                <input type="text" name="edit_designation" id="edit_designation" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 rounded-bottom-4">
                        <button type="submit" name="edit_faculty" class="btn btn-dark fw-bold px-4 w-100">Update Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-body p-4 text-center">
                    <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size: 50px;"></i>
                    <h4 class="fw-bold text-dark mb-2">Delete Faculty?</h4>
                    <p class="text-muted fw-semibold mb-4">You are about to delete <strong id="delete_faculty_name" class="text-danger"></strong>. Make sure they are not assigned to active subjects. This action cannot be undone.</p>
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="delete_id" id="delete_id_input">
                        <button type="button" class="btn btn-light fw-bold w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_faculty_btn" class="btn btn-danger fw-bold w-50">Yes, Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Reset Password Modal -->
    <div class="modal fade" id="resetModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-body p-4 text-center">
                    <i class="fas fa-key text-warning mb-3" style="font-size: 50px;"></i>
                    <h4 class="fw-bold text-dark mb-2">Reset Password</h4>
                    <p class="text-muted fw-semibold mb-4">Reset password to <b>123456</b> for <strong id="reset_faculty_name" class="text-primary"></strong>?</p>
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="reset_id" id="reset_id_input">
                        <button type="button" class="btn btn-light fw-bold w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="reset_pass_btn" class="btn btn-warning text-dark fw-bold w-50">Yes, Reset</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#facultyTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [10, 25, 50, 100],
                "searching": false, 
                "ordering": false,  
                "info": true,
                "language": {
                    "lengthMenu": "Display _MENU_ records"
                }
            });
        });

        function openEditModal(emp_id, name, dept, designation, email) {
            document.getElementById('edit_emp_id').value = emp_id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_department').value = dept;
            document.getElementById('edit_designation').value = designation;
            document.getElementById('edit_email').value = email;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function openDeleteModal(emp_id, name) {
            document.getElementById('delete_id_input').value = emp_id;
            document.getElementById('delete_faculty_name').innerText = name + " (" + emp_id + ")";
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        function openResetModal(emp_id, name) {
            document.getElementById('reset_id_input').value = emp_id;
            document.getElementById('reset_faculty_name').innerText = name + " (" + emp_id + ")";
            new bootstrap.Modal(document.getElementById('resetModal')).show();
        }

        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const clockEl = document.getElementById('liveClock');
            if(clockEl) clockEl.innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>