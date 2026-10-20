<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$msg = "";
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name FROM users WHERE user_id = '$admin_id'");
$admin_name = $admin_query->fetch_assoc()['name'] ?? 'Admin';

$name_parts = explode(' ', trim($admin_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) { $initials .= strtoupper(substr(end($name_parts), 0, 1)); }

// ==========================================
// 🚀 ADD NEW SUBJECT
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_subject'])) {
    $sub_code = $conn->real_escape_string($_POST['subject_code']);
    $sub_name = $conn->real_escape_string($_POST['subject_name']);
    $sem = (int)$_POST['semester'];
    $dept = $conn->real_escape_string($_POST['department']);
    $faculty = $conn->real_escape_string($_POST['faculty_name']);

    $check = $conn->query("SELECT * FROM subjects WHERE subject_code='$sub_code' OR (subject_name='$sub_name' AND semester='$sem')");
    
    if ($check->num_rows > 0) {
        $msg = "<div class='alert alert-danger shadow-sm' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i> Subject already exists!</div>";
    } else {
        $sql = "INSERT INTO subjects (subject_code, subject_name, semester, department, faculty_name) 
                VALUES ('$sub_code', '$sub_name', '$sem', '$dept', '$faculty')";
        if ($conn->query($sql)) {
            $msg = "<div class='alert alert-success shadow-sm' style='border-radius:10px;'><i class='fas fa-check-circle me-2'></i> Subject mapped successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm' style='border-radius:10px;'><i class='fas fa-times-circle me-2'></i> Error: " . $conn->error . "</div>";
        }
    }
}

// ==========================================
// 🗑️ DELETE SUBJECT
// ==========================================
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM subjects WHERE id='$del_id'");
    header("Location: subject_mgmt.php");
    exit();
}

// Fetch Faculty List for Dropdown
$faculty_list = $conn->query("SELECT name FROM users WHERE role='faculty' ORDER BY name ASC");

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$query = "SELECT * FROM subjects";
if (!empty($search)) {
    $query .= " WHERE subject_name LIKE '%$search%' OR subject_code LIKE '%$search%' OR faculty_name LIKE '%$search%'";
}
$query .= " ORDER BY semester ASC, subject_name ASC";
$subjects = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subject Management - KDP Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-width: 270px; --primary: #4338ca; --bg-body: #f8fafc; --surface: #ffffff; --text-main: #0f172a; --text-muted: #64748b; --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 13px; color: #bfdbfe; margin-top: 5px; font-weight: 500; letter-spacing: 0.5px;} 
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}
        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .content-box { background: var(--surface); border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; }
        .btn-action { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; transition: 0.2s; }
        .btn-action:hover { background: #dc2626; color: white; transform: translateY(-2px); }
        .search-group { display: flex; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; background: #f8fafc; }
        .search-group input { border: none; background: transparent; padding: 10px 15px; outline: none; font-size: 14px; font-weight: 600;}
        .search-group button { border: none; background: var(--primary); color: white; padding: 0 20px; }
        .modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .modal-header { background: linear-gradient(135deg, #1e3a8a, #4338ca); color: white; border-radius: 16px 16px 0 0; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 600; padding: 10px 15px;}
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); background: white;}
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Admin Portal</div>
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

    <div class="main">
        <div class="topbar">
            <h3 class="fw-bold m-0">Subject Management & Mapping</h3>
            <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#addSubjectModal"><i class="fas fa-plus-circle me-2"></i> Add Subject</button>
        </div>
        <?php echo $msg; ?>

        <div class="content-box p-0 overflow-hidden">
            <div class="d-flex justify-content-between align-items-center p-4 border-bottom">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-sitemap text-primary me-2"></i> Curriculum Mapping</h5>
                <form method="GET" class="search-group">
                    <input type="text" name="search" placeholder="Search Subject or Faculty..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Sub Code</th>
                            <th>Subject Name</th>
                            <th>Department & Sem</th>
                            <th>Assigned Faculty</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($subjects->num_rows > 0): ?>
                            <?php while($row = $subjects->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 text-primary fw-bold"><?php echo htmlspecialchars($row['subject_code']); ?></td>
                                    <td><div class="fw-bold text-dark"><?php echo htmlspecialchars($row['subject_name']); ?></div></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['department']); ?></div>
                                        <div class="text-muted small fw-semibold">Sem <?php echo htmlspecialchars($row['semester']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border p-2"><i class="fas fa-chalkboard-teacher text-success me-1"></i> <?php echo htmlspecialchars($row['faculty_name']); ?></span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="?delete=<?php echo $row['id']; ?>" class="btn-action" onclick="return confirm('Delete this subject? This affects mapping!');"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted fw-bold">No subjects mapped yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i> Map New Subject</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3"><label class="form-label small fw-bold text-muted text-uppercase">Subject Code</label><input type="text" name="subject_code" class="form-control" placeholder="e.g. 4300001" required></div>
                        <div class="mb-3"><label class="form-label small fw-bold text-muted text-uppercase">Subject Name</label><input type="text" name="subject_name" class="form-control" required></div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label small fw-bold text-muted text-uppercase">Department</label><input type="text" name="department" class="form-control" value="Computer Engineering" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label small fw-bold text-muted text-uppercase">Semester</label><input type="number" name="semester" class="form-control" min="1" max="6" required></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Assign Faculty</label>
                            <select name="faculty_name" class="form-select" required>
                                <option value="" disabled selected>-- Select Faculty --</option>
                                <?php if($faculty_list->num_rows > 0): ?>
                                    <?php while($f = $faculty_list->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($f['name']); ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0"><button type="submit" name="add_subject" class="btn btn-primary fw-bold px-4">Save Mapping</button></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>