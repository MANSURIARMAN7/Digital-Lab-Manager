<?php
session_start();
// 🔗 Database Connection 
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ==========================================
// 🗑️ DELETE LOGIC (Prepared Statement + Physical Deletion)
// ==========================================
$message = "";
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];

    // Fetch file path safely using Prepared Statement
    $stmt_select = $conn->prepare("SELECT file_path FROM lab_manuals WHERE id = ?");
    $stmt_select->bind_param("i", $del_id);
    $stmt_select->execute();
    $res = $stmt_select->get_result();

    if ($res && $row = $res->fetch_assoc()) {
        if (!empty($row['file_path']) && file_exists($row['file_path'])) {
            @unlink($row['file_path']);
        }
    }
    $stmt_select->close();

    // Delete record from DB
    $stmt_del = $conn->prepare("DELETE FROM lab_manuals WHERE id = ?");
    $stmt_del->bind_param("i", $del_id);
    if ($stmt_del->execute()) {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible fade show' id='autoAlert'><i class='fas fa-check-circle me-1'></i> Manual deleted successfully!</div>";
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible fade show' id='autoAlert'><i class='fas fa-exclamation-triangle me-1'></i> Failed to delete manual.</div>";
    }
    $stmt_del->close();

    header("Location: Lab_Manuals.php");
    exit();
}

// Show session message if exists
if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg'];
    unset($_SESSION['msg']);
}

// Fetch Admin Details
$admin_id = $_SESSION['user_id'];
$admin_name = 'System Administrator';
$admin_stmt = $conn->prepare("SELECT name, department FROM users WHERE user_id = ?");
if ($admin_stmt) {
    $admin_stmt->bind_param("s", $admin_id);
    $admin_stmt->execute();
    $admin_res = $admin_stmt->get_result();
    if ($admin_data = $admin_res->fetch_assoc()) {
        $admin_name = $admin_data['name'] ?? 'System Administrator';
    }
    $admin_stmt->close();
}

// ==========================================
// 🚀 UPLOAD / ADD NEW LAB MANUAL LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_manual'])) {
    $title        = trim($_POST['title']);
    $subject_name = trim($_POST['subject_name']);
    $semester     = (int)$_POST['semester'];
    $branch       = trim($_POST['branch']);
    $practical_no = trim($_POST['practical_no']);
    $end_date     = trim($_POST['end_date']);

    if (isset($_FILES['manual_file']) && $_FILES['manual_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['manual_file']['name'];
        $file_tmp  = $_FILES['manual_file']['tmp_name'];
        $file_size = $_FILES['manual_file']['size'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // MIME Type Check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file_tmp);
        finfo_close($finfo);

        $max_size = 10 * 1024 * 1024; // 10MB Limit

        if ($file_ext === 'pdf' && $mime === 'application/pdf') {
            if ($file_size <= $max_size) {
                $upload_dir = '../uploads/manuals/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Clean unique filename
                $clean_title   = preg_replace("/[^a-zA-Z0-9]+/", "_", $title);
                $new_file_name = time() . '_' . strtolower($clean_title) . '.pdf';
                $destination   = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $destination)) {
                    // Safe Insert Query using Prepared Statement
                    $stmt_ins = $conn->prepare("INSERT INTO lab_manuals (title, subject_name, semester, branch, practical_no, end_date, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt_ins->bind_param("ssissss", $title, $subject_name, $semester, $branch, $practical_no, $end_date, $destination);

                    if ($stmt_ins->execute()) {
                        $message = "<div class='alert alert-success alert-dismissible fade show' id='autoAlert'><i class='fas fa-check-circle me-1'></i> Lab Manual published successfully!</div>";
                    } else {
                        $message = "<div class='alert alert-danger alert-dismissible fade show' id='autoAlert'><i class='fas fa-exclamation-triangle me-1'></i> Database Error: " . htmlspecialchars($conn->error) . "</div>";
                    }
                    $stmt_ins->close();
                } else {
                    $message = "<div class='alert alert-danger alert-dismissible fade show' id='autoAlert'>Failed to move file to server. Check folder permissions.</div>";
                }
            } else {
                $message = "<div class='alert alert-warning alert-dismissible fade show' id='autoAlert'>File size exceeds maximum limit of 10MB.</div>";
            }
        } else {
            $message = "<div class='alert alert-warning alert-dismissible fade show' id='autoAlert'>Invalid format! Only PDF files are allowed.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger alert-dismissible fade show' id='autoAlert'>Please select a valid PDF file to upload.</div>";
    }
}

// Fetch Subjects & Manuals
$subjects_list = $conn->query("SELECT DISTINCT subject_name, semester FROM subjects ORDER BY semester ASC, subject_name ASC");
$manuals_list  = $conn->query("SELECT * FROM lab_manuals ORDER BY uploaded_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Manuals Management - Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root { --sidebar-width: 260px; --bg-color: #f4f7fe; --sidebar-bg: #1a365d; --accent-blue: #2563eb; }
        body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        .sidebar { width: var(--sidebar-width); background-color: var(--sidebar-bg); color: #ffffff; display: flex; flex-direction: column; z-index: 10; overflow-y: auto; }
        .sidebar-logo-container { padding: 30px 20px 20px 20px; display: flex; flex-direction: column; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; }
        .sidebar-logo-container img { width: 90px; height: 90px; object-fit: contain; margin-bottom: 15px; border-radius: 50%; padding: 5px; background: rgba(255,255,255,0.1); }
        .sidebar-title h2 { font-size: 18px; font-weight: 700; margin: 0; color: #fff;}
        .sidebar-subtitle { font-size: 13px; color: #94a3b8; margin-top: 5px; font-weight: 500;}
        .nav-links { list-style: none; padding: 20px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 12px 20px; margin: 5px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 500; color: #a0aec0; transition: all 0.3s ease; }
        .nav-links li:hover { color: white; background: rgba(255,255,255,0.08); }
        .nav-links li.active { background: var(--accent-blue); color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4); font-weight: 600; }

        .main { flex: 1; padding: 30px 40px; overflow-y: auto; }

        .topbar { background: transparent; display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px;}
        .search-box { background: #fff; border-radius: 8px; padding: 10px 15px; display: flex; align-items: center; gap: 10px; width: 350px; border: 1px solid #e2e8f0; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .search-box input { border: none; background: transparent; outline: none; font-size: 14px; width: 100%; color: #334155; }

        .profile-pill { display: flex; align-items: center; background-color: #ffffff; padding: 6px 16px 6px 20px; border-radius: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; cursor: pointer; text-decoration: none; color: inherit; transition: all 0.2s;}
        .profile-text { text-align: right; margin-right: 15px; }
        .profile-welcome { display: block; font-size: 9.5px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 14px; color: #1e293b; font-weight: 700; }
        .profile-avatar { width: 42px; height: 42px; background-color: var(--accent-blue); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; }

        .content-box { background: white; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
        .table-custom th { background: #f8fafc; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; padding: 14px; }
        .table-custom td { vertical-align: middle; font-size: 14px; padding: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .badge-sem { background: rgba(37,99,235,0.1); color: var(--accent-blue); border: 1px solid rgba(37,99,235,0.2); padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
            <li class="active" onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-alt"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-folder-open"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-circle"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <!-- 🔍 LIVE SEARCH INPUT -->
            <div class="search-box">
                <i class="fas fa-search text-muted"></i>
                <input type="text" id="searchInput" placeholder="Search manuals by name or subject..." onkeyup="filterTable()">
            </div>

            <div class="d-flex align-items-center gap-4">
                <a href="Profile.php" class="profile-pill">
                    <div class="profile-text">
                        <span class="profile-welcome">Welcome Back,</span>
                        <h4 class="profile-name"><?php echo htmlspecialchars($admin_name); ?></h4>
                    </div>
                    <div class="profile-avatar"><i class="fas fa-user-shield"></i></div>
                </a>
            </div>
        </div>

        <!-- ⏳ AUTO-DISMISSING ALERT -->
        <div id="alertContainer">
            <?php echo $message; ?>
        </div>

        <div class="mb-4 mt-2">
            <h3 class="fw-bold text-dark mb-1" style="font-size: 24px;">Lab Manuals Management</h3>
            <p class="text-muted small mb-0">Upload and manage official practical lab manuals.</p>
        </div>

        <!-- TWO COLUMN LAYOUT -->
        <div class="row g-4">
            <!-- LEFT: UPLOAD FORM -->
            <div class="col-md-4">
                <div class="content-box">
                    <h5 class="fw-bold text-dark mb-3" style="font-size: 16px;"><i class="fas fa-upload text-primary me-2"></i> Upload Lab Manual</h5>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Manual Title</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. Basic Math Practical">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Select Subject</label>
                            <select name="subject_name" class="form-select" required>
                                <option value="">Choose Subject...</option>
                                <?php if($subjects_list && $subjects_list->num_rows > 0): ?>
                                    <?php while($sub = $subjects_list->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($sub['subject_name']); ?>">
                                            <?php echo htmlspecialchars($sub['subject_name']); ?> (Sem <?php echo $sub['semester']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Semester</label>
                            <select name="semester" class="form-select" required>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                                <option value="3">Semester 3</option>
                                <option value="4">Semester 4</option>
                                <option value="5">Semester 5</option>
                                <option value="6">Semester 6</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Branch</label>
                            <select name="branch" class="form-select" required>
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="Information Technology">Information Technology</option>
                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Practical No.</label>
                            <input type="text" name="practical_no" class="form-control" required placeholder="e.g. PR.1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Due Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">Upload PDF File <span class="text-muted fw-normal">(Max 10MB)</span></label>
                            <input type="file" name="manual_file" class="form-control" accept=".pdf,application/pdf" required>
                        </div>

                        <button type="submit" name="add_manual" class="btn btn-primary w-100 fw-bold py-2">
                            <i class="fas fa-cloud-upload-alt me-1"></i> Publish Manual
                        </button>
                    </form>
                </div>
            </div>

            <!-- RIGHT: DATATABLE WITH SEARCH -->
            <div class="col-md-8">
                <div class="content-box">
                    <h5 class="fw-bold text-dark mb-3" style="font-size: 16px;"><i class="fas fa-list text-success me-2"></i> Published Manuals</h5>

                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-custom mb-0" id="manualsTable">
                            <thead style="position: sticky; top: 0; background: white; z-index: 1;">
                                <tr>
                                    <th>Manual Info</th>
                                    <th>Branch / Sem</th>
                                    <th>Deadline</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <?php if($manuals_list && $manuals_list->num_rows > 0): ?>
                                    <?php while($row = $manuals_list->fetch_assoc()): ?>
                                        <tr class="manual-row">
                                            <td>
                                                <div class="fw-bold text-dark search-target"><?php echo htmlspecialchars($row['title']); ?></div>
                                                <small class="text-primary search-target"><?php echo htmlspecialchars($row['subject_name']); ?> (<?php echo htmlspecialchars($row['practical_no']); ?>)</small>
                                            </td>
                                            <td>
                                                <span class="badge-sem search-target"><?php echo htmlspecialchars($row['branch']); ?> - Sem <?php echo htmlspecialchars($row['semester']); ?></span>
                                            </td>
                                            <td>
                                                <small class="text-danger fw-bold"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y', strtotime($row['end_date'])); ?></small>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-outline-primary btn-sm me-1" title="View PDF">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="Lab_Manuals.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this lab manual?');" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr id="noDataRow">
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <i class="fas fa-folder-open mb-2" style="font-size: 32px; color: #cbd5e1;"></i><br>
                                            <span>No lab manuals uploaded yet.</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 1. AUTO-DISMISS ALERT
        setTimeout(function() {
            let alertMsg = document.getElementById('autoAlert');
            if(alertMsg) {
                alertMsg.style.transition = "opacity 0.5s ease";
                alertMsg.style.opacity = "0";
                setTimeout(() => alertMsg.remove(), 500);
            }
        }, 3500);

        // 2. ENHANCED LIVE TABLE SEARCH
        function filterTable() {
            let input = document.getElementById("searchInput").value.toLowerCase().trim();
            let rows = document.getElementsByClassName("manual-row");
            let visibleCount = 0;

            for (let i = 0; i < rows.length; i++) {
                let text = rows[i].textContent.toLowerCase();
                if (text.includes(input)) {
                    rows[i].style.display = "";
                    visibleCount++;
                } else {
                    rows[i].style.display = "none";
                }
            }

            // Show dynamic "No search result" row if nothing matches
            let noMatchRow = document.getElementById("noSearchMatchRow");
            if (visibleCount === 0 && rows.length > 0) {
                if (!noMatchRow) {
                    let tbody = document.getElementById("tableBody");
                    noMatchRow = document.createElement("tr");
                    noMatchRow.id = "noSearchMatchRow";
                    noMatchRow.innerHTML = `<td colspan="4" class="text-center text-muted py-4"><i class="fas fa-search me-2"></i>No manuals matched your search.</td>`;
                    tbody.appendChild(noMatchRow);
                }
            } else if (noMatchRow) {
                noMatchRow.remove();
            }
        }
    </script>
</body>
</html>
