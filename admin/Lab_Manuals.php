<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$msg = "";

// 2. Handle Upload Manual
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_manual'])) {
    $subject = $conn->real_escape_string($_POST['subject_name']);
    $prac_no = $conn->real_escape_string($_POST['practical_no']);
    $title = $conn->real_escape_string($_POST['title']);
    
    // File Upload Logic
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true); // Create folder if not exists
    }
    
    $file_name = time() . "_" . basename($_FILES["manual_file"]["name"]);
    $target_file = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if ($file_type != "pdf") {
        $msg = "<div class='alert alert-danger shadow-sm border-0' style='border-radius: 10px;'><i class='fas fa-exclamation-circle me-2'></i> Sirf PDF files allow hain!</div>";
    } else {
        if (move_uploaded_file($_FILES["manual_file"]["tmp_name"], $target_file)) {
            $insert_query = "INSERT INTO lab_manuals (subject_name, practical_no, title, file_path) VALUES ('$subject', '$prac_no', '$title', '$target_file')";
            if ($conn->query($insert_query)) {
                $msg = "<div class='alert alert-success shadow-sm border-0' style='border-radius: 10px;'><i class='fas fa-check-circle me-2'></i> Lab Manual Uploaded Successfully!</div>";
            }
        } else {
            $msg = "<div class='alert alert-danger shadow-sm border-0' style='border-radius: 10px;'>File upload mein error aayi!</div>";
        }
    }
}

// 3. Handle Delete Manual
if (isset($_GET['delete']) && isset($_GET['file'])) {
    $del_id = $conn->real_escape_string($_GET['delete']);
    $file_path = $_GET['file'];
    
    // Delete file from folder
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    // Delete from database
    $conn->query("DELETE FROM lab_manuals WHERE id = '$del_id'");
    header("Location: Lab_Manuals.php");
    exit();
}

// 4. Fetch Active Subjects for Dropdown
$subjects_list = [];
$sub_res = $conn->query("SELECT DISTINCT subject_name FROM subjects ORDER BY subject_name ASC");
if ($sub_res) {
    while ($r = $sub_res->fetch_assoc()) {
        $subjects_list[] = $r['subject_name'];
    }
}

// 5. Fetch Uploaded Manuals
$manuals = [];
$filter_sub = isset($_GET['filter_subject']) ? $conn->real_escape_string($_GET['filter_subject']) : '';
$query = "SELECT * FROM lab_manuals";
if ($filter_sub != "") {
    $query .= " WHERE subject_name = '$filter_sub'";
}
$query .= " ORDER BY uploaded_at DESC";

$res = $conn->query($query);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $manuals[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Manuals - Digital Lab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-width: 260px; --bg-color: #f8fafc; }
        body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        
        /* SIDEBAR */
        .sidebar { width: var(--sidebar-width); background-color: #0f172a; color: #ffffff; display: flex; flex-direction: column; padding: 20px 0; z-index: 10; overflow-y: auto; }
        .sidebar-logo-container { padding: 0 20px 20px 20px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar-title h2 { font-size: 15px; font-weight: 700; margin: 0; line-height: 1.2; letter-spacing: 0.5px; }
        .nav-links { list-style: none; padding: 15px 15px 0 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 11px 16px; margin: 4px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 14px; font-size: 14px; font-weight: 500; color: #94a3b8; transition: 0.2s ease-in-out; }
        .nav-links li:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-links li.active { background: #3b82f6; color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }

        /* MAIN CONTENT AREA */
        .main { flex: 1; padding: 25px 35px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; }
        
        /* TOP BAR */
        .topbar { background: white; padding: 12px 25px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .search-box { background: #f8fafc; border-radius: 8px; padding: 6px 15px; display: flex; align-items: center; gap: 10px; width: 350px; border: 1px solid #e2e8f0; }
        .search-box input { border: none; background: transparent; outline: none; font-size: 14px; width: 100%; color: #334155; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 38px; height: 38px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; }
        
        /* CONTENT BOXES */
        .content-box { background: white; border-radius: 14px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        
        /* CUSTOM FORM INPUTS */
        .form-control, .form-select { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 15px; font-size: 14px; box-shadow: none; }
        .form-control:focus, .form-select:focus { border-color: #3b82f6; background-color: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        
        /* TABLE STYLING */
        .table-custom th { background: transparent; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; }
        .table-custom td { vertical-align: middle; font-size: 14px; padding: 14px 0; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .table-custom tr:last-child td { border-bottom: none; }
        
        .badge-exp { padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; background: rgba(59,130,246,0.1); color: #3b82f6; border: 1px solid rgba(59,130,246,0.2); }
        
        .btn-view { background: rgba(16,185,129,0.1); color: #059669; border: none; padding: 6px 12px; border-radius: 6px; transition: 0.2s; text-decoration: none; }
        .btn-view:hover { background: #059669; color: white; }
        
        .btn-delete { background: rgba(239,68,68,0.1); color: #dc2626; border: none; padding: 6px 12px; border-radius: 6px; transition: 0.2s; text-decoration: none; }
        .btn-delete:hover { background: #dc2626; color: white; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <div style="background: #3b82f6; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">DL</div>
            <div class="sidebar-title"><h2>DIGITAL LAB<br>MANUAL</h2></div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-chart-pie"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
            <li class="active" onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-alt"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-folder-open"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-circle"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li onclick="window.location.href='Expense Mgmt.php'"><i class="fas fa-wallet"></i> Expense Mgmt</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <!-- TOPBAR -->
        <div class="topbar">
            <div class="search-box">
                <i class="fas fa-search text-muted"></i>
                <input type="text" placeholder="Search lab manuals...">
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="user-profile">
                    <div class="user-avatar">AM</div>
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 13.5px; line-height: 1.2;">System Administrator</div>
                        <div class="text-muted" style="font-size: 11.5px;">University Tech</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- HEADER TITLE -->
        <div class="mb-2">
            <h4 class="fw-bold text-dark mb-1">📄 Lab Manuals (Practicals)</h4>
            <p class="text-muted small mb-0">Upload PDF practical templates and manage subject-wise manuals.</p>
        </div>

        <?php if($msg != "") echo $msg; ?>

        <div class="row g-4">
            <!-- UPLOAD FORM -->
            <div class="col-md-4">
                <div class="content-box">
                    <h6 class="fw-bold text-dark mb-4"><i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i>Upload Manual</h6>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">SELECT SUBJECT</label>
                            <select name="subject_name" class="form-select" required>
                                <option value="">-- Choose Subject --</option>
                                <?php foreach($subjects_list as $sub): ?>
                                    <option value="<?php echo htmlspecialchars($sub); ?>"><?php echo htmlspecialchars($sub); ?></option>
                                <?php endforeach; ?>
                                <!-- Fallback if no subjects exist -->
                                <?php if(empty($subjects_list)): ?>
                                    <option value="DBMS">Database Systems (DBMS)</option>
                                    <option value="DS">Data Structures (DS)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">PRACTICAL NO.</label>
                            <input type="text" name="practical_no" class="form-control" placeholder="e.g. Exp #01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">PRACTICAL TITLE</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. SQL Queries Implementation" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">UPLOAD PDF</label>
                            <input type="file" name="manual_file" class="form-control bg-white" accept="application/pdf" required>
                        </div>
                        <button type="submit" name="upload_manual" class="btn btn-primary w-100 fw-bold py-2" style="border-radius: 8px;">Upload Template</button>
                    </form>
                </div>
            </div>

            <!-- MANUALS LIST TABLE -->
            <div class="col-md-8">
                <div class="content-box h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="fw-bold text-dark mb-0">Available Templates</h6>
                        
                        <!-- FILTER FORM -->
                        <form method="GET" class="d-flex gap-2">
                            <select name="filter_subject" class="form-select form-select-sm shadow-none" style="width: auto; font-size: 12px; padding: 6px 30px 6px 12px;" onchange="this.form.submit()">
                                <option value="">All Subjects</option>
                                <?php foreach($subjects_list as $sub): ?>
                                    <option value="<?php echo htmlspecialchars($sub); ?>" <?php if($filter_sub == $sub) echo 'selected'; ?>><?php echo htmlspecialchars($sub); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Practical No.</th>
                                    <th>Title & Subject</th>
                                    <th>Upload Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($manuals)): ?>
                                    <tr><td colspan="4" class="text-center text-muted py-4">No lab manuals uploaded yet.</td></tr>
                                <?php else: foreach($manuals as $man): ?>
                                    <tr>
                                        <td><span class="badge-exp"><?php echo htmlspecialchars($man['practical_no']); ?></span></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($man['title']); ?></div>
                                            <small class="text-muted" style="font-size: 12px;"><?php echo htmlspecialchars($man['subject_name']); ?></small>
                                        </td>
                                        <td class="text-muted" style="font-size: 12px;">
                                            <?php echo date('d M Y', strtotime($man['uploaded_at'])); ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?php echo htmlspecialchars($man['file_path']); ?>" target="_blank" class="btn-view me-1" title="View PDF">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </a>
                                            <a href="?delete=<?php echo $man['id']; ?>&file=<?php echo urlencode($man['file_path']); ?>" class="btn-delete" onclick="return confirm('Kya aap yeh manual delete karna chahte hain?');" title="Delete">
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
    </div>

</body>
</html>