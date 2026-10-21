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

// Fetch faculties for the dropdown (Format: Name (ID))
$faculties_query = $conn->query("SELECT user_id, name FROM users WHERE role='faculty' ORDER BY name ASC");
$faculty_list = [];
if($faculties_query && $faculties_query->num_rows > 0) {
    while($fac = $faculties_query->fetch_assoc()){
        $faculty_list[] = $fac['name'] . " (" . $fac['user_id'] . ")";
    }
}

// ==========================================
// 1. ADD SUBJECT LOGIC
// ==========================================
if (isset($_POST['add_subject'])) {
    $sub_code = $conn->real_escape_string(trim($_POST['subject_code']));
    $sub_name = $conn->real_escape_string(trim($_POST['subject_name']));
    $dept = $conn->real_escape_string(trim($_POST['department']));
    $sem = $conn->real_escape_string(trim($_POST['semester']));
    $status = "Active"; 
    
    // Handle Multiple Faculties
    $selected_faculties = isset($_POST['faculty_name']) ? implode(' / ', $_POST['faculty_name']) : '';
    $faculty_val = $conn->real_escape_string($selected_faculties);

    // Duplicate Check
    $check = $conn->query("SELECT subject_code FROM subjects WHERE subject_code='$sub_code'");
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-exclamation-circle me-2'></i> <b>Duplicate Entry!</b> Subject Code '$sub_code' already exists.</div>";
    } else {
        $sql = "INSERT INTO subjects (subject_code, subject_name, semester, department, status, faculty_name) 
                VALUES ('$sub_code', '$sub_name', '$sem', '$dept', '$status', '$faculty_val')";
        if ($conn->query($sql)) {
            $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i> Subject <b>$sub_name</b> added successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-times-circle me-2'></i> Failed to add subject.</div>";
        }
    }
}

// ==========================================
// 2. EDIT SUBJECT LOGIC (Assign / Unassign Faculty)
// ==========================================
if (isset($_POST['edit_subject'])) {
    $id = (int)$_POST['edit_id'];
    $sub_code = $conn->real_escape_string(trim($_POST['edit_subject_code']));
    $sub_name = $conn->real_escape_string(trim($_POST['edit_subject_name']));
    $dept = $conn->real_escape_string(trim($_POST['edit_department']));
    $sem = $conn->real_escape_string(trim($_POST['edit_semester']));
    
    // Handle Multiple Faculties (If none selected, it will be empty)
    $selected_faculties = isset($_POST['edit_faculty_name']) ? implode(' / ', $_POST['edit_faculty_name']) : '';
    $faculty_val = $conn->real_escape_string($selected_faculties);
    
    if($conn->query("UPDATE subjects SET subject_code='$sub_code', subject_name='$sub_name', department='$dept', semester='$sem', faculty_name='$faculty_val' WHERE id=$id")){
        $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i> Subject details updated successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-times-circle me-2'></i> Error updating subject details.</div>";
    }
}

// ==========================================
// 3. SAFE DELETE LOGIC
// ==========================================
if (isset($_POST['delete_subject_btn'])) {
    $del_id = (int)$_POST['delete_id'];
    
    // Try to delete cleanly, catch any foreign key query errors silently
    if($conn->query("DELETE FROM subjects WHERE id=$del_id")) {
        $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-trash-alt me-2'></i> Subject securely deleted.</div>";
    } else {
        // Custom user-friendly message instead of raw SQL error
        $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-exclamation-triangle me-2'></i> <b>Cannot Delete Subject:</b> This subject is currently linked to uploaded Lab Manuals or Student Submissions. Please remove those related records first.</div>";
    }
}

// ==========================================
// 4. FETCH, FILTER, SEARCH & SORTING
// ==========================================
$where = "1=1";

$dept_filter = $_GET['dept'] ?? 'all';
if ($dept_filter !== 'all') {
    $safe_dept = $conn->real_escape_string($dept_filter);
    $where .= " AND department = '$safe_dept'";
}

$sem_filter = $_GET['sem'] ?? 'all';
if ($sem_filter !== 'all') {
    $safe_sem = $conn->real_escape_string($sem_filter);
    $where .= " AND semester = '$safe_sem'";
}

$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $safe_search = $conn->real_escape_string($search);
    $where .= " AND (subject_name LIKE '%$safe_search%' OR subject_code LIKE '%$safe_search%' OR faculty_name LIKE '%$safe_search%')";
}

$sort = $_GET['sort'] ?? 'code_asc';
$order_by = "subject_code ASC";
if ($sort == 'code_desc') $order_by = "subject_code DESC";
elseif ($sort == 'name_asc') $order_by = "subject_name ASC";
elseif ($sort == 'name_desc') $order_by = "subject_name DESC";

$subjects = $conn->query("SELECT * FROM subjects WHERE $where ORDER BY $order_by");
$total_subjects = $subjects ? $subjects->num_rows : 0;
$active_sub_count = $conn->query("SELECT COUNT(*) as c FROM subjects WHERE status='Active'")->fetch_assoc()['c'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management - Admin Portal</title>
    
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

        /* Uniform body styling */
        * { box-sizing: border-box; }
        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        /* UNIFORM SIDEBAR */
        .sidebar { width: var(--sidebar-width); min-width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}

        /* MAIN CONTENT - Scroll fixed */
        .main { flex: 1; padding: 30px 45px; overflow-y: scroll; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* UNIFORM TOPBAR & PROFILE PILL */
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .profile-pill { display: flex; align-items: center; background: linear-gradient(135deg, #1e3a8a, #4338ca); padding: 12px 25px 12px 30px; border-radius: 50px; cursor: pointer; text-decoration: none; color: white; transition: var(--transition-bounce); box-shadow: 0 10px 20px rgba(67, 56, 202, 0.2); border: 2px solid rgba(255,255,255,0.1);}
        .profile-pill:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(67, 56, 202, 0.3); border-color: rgba(255,255,255,0.3);}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: #bfdbfe; font-weight: 800; text-transform: uppercase; margin-bottom: 2px; letter-spacing: 0.5px;}
        .profile-name { margin: 0; font-size: 18px; color: white; font-weight: 900;}
        .profile-avatar { background: white; color: var(--primary); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);}

        .toolbar { background: linear-gradient(135deg, rgba(67, 56, 202, 0.03), rgba(59, 130, 246, 0.05)); border: 1px solid rgba(67, 56, 202, 0.1); border-radius: 16px; padding: 20px; margin-bottom: 25px; }
        .form-select, .form-control { border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 14px;}
        .form-select:focus, .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);}
        
        /* Multiple Select Styles */
        select[multiple] { min-height: 100px; }
        select[multiple] option { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; cursor: pointer; }
        select[multiple] option:checked { background-color: var(--primary) !important; color: white !important; font-weight: bold; }

        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; }
        
        .sub-code { color: var(--primary); font-weight: 800; }
        
        /* Faculty Chips */
        .faculty-chips { display: flex; flex-wrap: wrap; gap: 5px; }
        .faculty-chip { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 6px; }

        .btn-action-edit { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; transition: 0.2s; }
        .btn-action-edit:hover { background: #2563eb; color: white; transform: translateY(-2px); }
        .btn-action-delete { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: none; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; transition: 0.2s; }
        .btn-action-delete:hover { background: #dc2626; color: white; transform: translateY(-2px); }

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
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li class="active" onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
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

        <!-- HEADER & COUNTS -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h3 class="fw-bold text-dark mb-1"><i class="fas fa-book-open text-primary me-2"></i> Subject Management & Mapping</h3>
                <p class="text-muted fw-semibold small mb-0">Manage academic subjects and faculty assignments.</p>
            </div>
            <div class="col-md-6 d-flex justify-content-end gap-3 align-items-center">
                <div class="bg-white border rounded-3 px-3 py-2 shadow-sm text-center">
                    <span class="d-block text-uppercase text-muted fw-bold" style="font-size: 10px;">Total Subjects</span>
                    <span class="fs-5 fw-black text-primary"><?php echo $total_subjects; ?></span>
                </div>
                <div class="bg-white border rounded-3 px-3 py-2 shadow-sm text-center">
                    <span class="d-block text-uppercase text-muted fw-bold" style="font-size: 10px;">Active</span>
                    <span class="fs-5 fw-black text-success"><?php echo $active_sub_count; ?></span>
                </div>
                <button class="btn btn-primary fw-bold px-4 h-100 shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus me-2"></i> Add Subject</button>
            </div>
        </div>

        <!-- TOOLBAR (Filters & Search) -->
        <div class="toolbar">
            <form method="GET" action="subject_mgmt.php" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-primary text-uppercase">Department Filter</label>
                    <select name="dept" class="form-select bg-white">
                        <option value="all" <?php echo $dept_filter == 'all' ? 'selected' : ''; ?>>All Departments</option>
                        <option value="Computer Engineering" <?php echo $dept_filter == 'Computer Engineering' ? 'selected' : ''; ?>>Computer Engg</option>
                        <option value="IT Engineering" <?php echo $dept_filter == 'IT Engineering' ? 'selected' : ''; ?>>IT Engg</option>
                        <option value="Civil Engineering" <?php echo $dept_filter == 'Civil Engineering' ? 'selected' : ''; ?>>Civil Engg</option>
                        <option value="Mechanical Engineering" <?php echo $dept_filter == 'Mechanical Engineering' ? 'selected' : ''; ?>>Mechanical Engg</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-primary text-uppercase">Semester</label>
                    <select name="sem" class="form-select bg-white">
                        <option value="all" <?php echo $sem_filter == 'all' ? 'selected' : ''; ?>>All Sem</option>
                        <?php for($i=1; $i<=6; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $sem_filter == strval($i) ? 'selected' : ''; ?>>Sem <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-primary text-uppercase">Sort By</label>
                    <select name="sort" class="form-select bg-white">
                        <option value="code_asc" <?php echo $sort == 'code_asc' ? 'selected' : ''; ?>>Subject Code (Asc)</option>
                        <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Subject Name (A-Z)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-primary text-uppercase">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Code, Subject or Faculty..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary fw-bold px-3">Apply</button>
                        <a href="subject_mgmt.php" class="btn btn-outline-secondary fw-bold px-3">Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- DATA TABLE -->
        <div class="content-box">
            <div class="table-responsive">
                <table class="table table-custom w-100" id="subjectTable">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Department & Sem</th>
                            <th>Assigned Faculty</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($subjects && $subjects->num_rows > 0): ?>
                            <?php while($row = $subjects->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="sub-code"><?php echo htmlspecialchars($row['subject_code']); ?></span></td>
                                    <td><div class="fw-bold text-dark"><?php echo htmlspecialchars($row['subject_name']); ?></div></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($row['department']); ?></div>
                                        <span class="badge bg-light text-dark border border-secondary mt-1">Semester <?php echo htmlspecialchars($row['semester']); ?></span>
                                    </td>
                                    <td style="max-width: 250px;">
                                        <div class="faculty-chips">
                                            <?php 
                                            if(!empty($row['faculty_name'])) {
                                                $facs = explode(' / ', $row['faculty_name']);
                                                foreach($facs as $f) {
                                                    echo '<span class="faculty-chip"><i class="fas fa-user-tie me-1 text-primary"></i>'.htmlspecialchars(trim($f)).'</span>';
                                                }
                                            } else {
                                                echo '<span class="text-muted small fst-italic">Not Assigned</span>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn-action-edit me-1" onclick="openEditModal(<?php echo $row['id']; ?>, '<?php echo addslashes($row['subject_code']); ?>', '<?php echo addslashes($row['subject_name']); ?>', '<?php echo addslashes($row['department']); ?>', '<?php echo addslashes($row['semester']); ?>', '<?php echo addslashes($row['faculty_name']); ?>')" title="Edit Subject">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn-action-delete" onclick="openDeleteModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['subject_name'])); ?>')" title="Delete Subject">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODALS -->

    <!-- 1. Add Subject Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i> Add New Subject</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Code <span class="text-danger">*</span></label>
                                <input type="text" name="subject_code" class="form-control" required placeholder="e.g. DI01000021">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Subject Name <span class="text-danger">*</span></label>
                                <input type="text" name="subject_name" class="form-control" required placeholder="e.g. Mathematics-I">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Department <span class="text-danger">*</span></label>
                                <select name="department" class="form-select" required>
                                    <option value="" disabled selected>-- Select --</option>
                                    <option value="Computer Engineering">Computer Engineering</option>
                                    <option value="IT Engineering">IT Engineering</option>
                                    <option value="Civil Engineering">Civil Engineering</option>
                                    <option value="Mechanical Engineering">Mechanical Engineering</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Sem <span class="text-danger">*</span></label>
                                <select name="semester" class="form-select" required>
                                    <option value="" disabled selected>-</option>
                                    <?php for($i=1; $i<=6; $i++) echo "<option value='$i'>$i</option>"; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Assign Faculty <span class="text-primary">(Hold Ctrl/Cmd to select multiple)</span></label>
                            <select name="faculty_name[]" class="form-select" multiple>
                                <?php foreach($faculty_list as $f): ?>
                                    <option value="<?php echo htmlspecialchars($f); ?>"><?php echo htmlspecialchars($f); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 rounded-bottom-4">
                        <button type="submit" name="add_subject" class="btn btn-primary fw-bold px-4 w-100">Save Subject & Mapping</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. Edit Subject Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-dark text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i> Edit Subject Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="edit_id" id="edit_id">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Code <span class="text-danger">*</span></label>
                                <input type="text" name="edit_subject_code" id="edit_subject_code" class="form-control" required>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Subject Name <span class="text-danger">*</span></label>
                                <input type="text" name="edit_subject_name" id="edit_subject_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Department <span class="text-danger">*</span></label>
                                <select name="edit_department" id="edit_department" class="form-select" required>
                                    <option value="Computer Engineering">Computer Engineering</option>
                                    <option value="IT Engineering">IT Engineering</option>
                                    <option value="Civil Engineering">Civil Engineering</option>
                                    <option value="Mechanical Engineering">Mechanical Engineering</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Sem <span class="text-danger">*</span></label>
                                <select name="edit_semester" id="edit_semester" class="form-select" required>
                                    <?php for($i=1; $i<=6; $i++) echo "<option value='$i'>$i</option>"; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Assign Faculty <span class="text-primary">(Hold Ctrl/Cmd to unselect or select)</span></label>
                            <select name="edit_faculty_name[]" id="edit_faculty_name" class="form-select" multiple>
                                <?php foreach($faculty_list as $f): ?>
                                    <option value="<?php echo htmlspecialchars($f); ?>"><?php echo htmlspecialchars($f); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted fst-italic">Select none to unassign all faculties.</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 rounded-bottom-4">
                        <button type="submit" name="edit_subject" class="btn btn-dark fw-bold px-4 w-100">Update Subject</button>
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
                    <h4 class="fw-bold text-dark mb-2">Delete Subject?</h4>
                    <p class="text-muted fw-semibold mb-4">You are about to delete <strong id="delete_subject_name" class="text-danger"></strong>. Make sure it has no active records.</p>
                    <form method="POST" class="d-flex gap-2">
                        <input type="hidden" name="delete_id" id="delete_id_input">
                        <button type="button" class="btn btn-light fw-bold w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_subject_btn" class="btn btn-danger fw-bold w-50">Yes, Delete</button>
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
            $('#subjectTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [10, 25, 50, 100],
                "searching": false, 
                "ordering": false,  
                "info": true,
                "language": {
                    "lengthMenu": "Display _MENU_ records",
                    "emptyTable": "<div class='text-center py-5 text-muted fw-bold'><i class='fas fa-book-open fs-1 opacity-25 mb-3 d-block'></i> No subjects found matching your criteria.</div>"
                }
            });
        });

        function openEditModal(id, code, name, dept, sem, faculty_str) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_subject_code').value = code;
            document.getElementById('edit_subject_name').value = name;
            document.getElementById('edit_department').value = dept;
            document.getElementById('edit_semester').value = sem;
            
            // Handle Multiple Faculty Selection (Unselect capabilities included)
            let facSelect = document.getElementById('edit_faculty_name');
            let assignedFaculties = faculty_str ? faculty_str.split(' / ') : [];
            
            for (let i = 0; i < facSelect.options.length; i++) {
                if (assignedFaculties.includes(facSelect.options[i].value)) {
                    facSelect.options[i].selected = true;
                } else {
                    facSelect.options[i].selected = false;
                }
            }
            
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function openDeleteModal(id, name) {
            document.getElementById('delete_id_input').value = id;
            document.getElementById('delete_subject_name').innerText = name;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
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