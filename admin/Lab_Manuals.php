<?php
session_start();
include '../db.php';

// ========================================================================
// 0. AJAX API: DYNAMIC SUBJECT FETCHING (Runs in background without reload)
// ========================================================================
if (isset($_GET['ajax_subjects'])) {
    $branch = $conn->real_escape_string($_GET['branch']);
    $semester = $conn->real_escape_string($_GET['semester']);
    
    // NOTE: Yahan 'department' aur 'semester' teri subjects table ke columns hone chahiye. 
    // Agar columns ka naam alag hai (jaise 'branch_name'), toh yahan change kar lena.
    $query = "SELECT DISTINCT subject_name FROM subjects WHERE department = '$branch' AND semester = '$semester' ORDER BY subject_name ASC";
    
    $res = $conn->query($query);
    echo '<option value="">-- Choose Subject --</option>';
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            echo '<option value="' . htmlspecialchars($row['subject_name']) . '">' . htmlspecialchars($row['subject_name']) . '</option>';
        }
    } else {
        echo '<option value="">❌ No subjects found for this Sem/Branch</option>';
    }
    exit(); // AJAX call yahan se wapas chali jayegi, poora page load nahi hoga
}
// ====================================================================

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$msg = "";

// 2. Handle Upload Manual
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_manual'])) {
    $branch = $conn->real_escape_string($_POST['branch']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $subject = $conn->real_escape_string($_POST['subject_name']);
    $prac_no = $conn->real_escape_string($_POST['practical_no']);
    $title = $conn->real_escape_string($_POST['title']);
    $start_date = $conn->real_escape_string($_POST['start_date']);
    $end_date = $conn->real_escape_string($_POST['end_date']);

    // Date Validation
    if ($end_date < $start_date) {
        $msg = "<div class='alert alert-danger shadow-sm border-0' style='border-radius: 10px;'><i class='fas fa-exclamation-circle me-2'></i> End Date, Start Date se pehle ki nahi ho sakti!</div>";
    } else {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES["manual_file"]["name"]);
        $target_file = $target_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if ($file_type != "pdf") {
            $msg = "<div class='alert alert-danger shadow-sm border-0' style='border-radius: 10px;'><i class='fas fa-exclamation-circle me-2'></i> Sirf PDF files allow hain!</div>";
        } else {
            if (move_uploaded_file($_FILES["manual_file"]["tmp_name"], $target_file)) {
                $insert_query = "INSERT INTO lab_manuals (branch, semester, subject_name, practical_no, title, file_path, start_date, end_date) 
                                 VALUES ('$branch', '$semester', '$subject', '$prac_no', '$title', '$target_file', '$start_date', '$end_date')";
                if ($conn->query($insert_query)) {
                    $msg = "<div class='alert alert-success shadow-sm border-0' style='border-radius: 10px;'><i class='fas fa-check-circle me-2'></i> Lab Manual Uploaded Successfully!</div>";
                } else {
                    $msg = "<div class='alert alert-danger shadow-sm border-0' style='border-radius: 10px;'>Database Error: " . $conn->error . "</div>";
                }
            } else {
                $msg = "<div class='alert alert-danger shadow-sm border-0' style='border-radius: 10px;'>File upload mein error aayi!</div>";
            }
        }
    }
}

// 3. Handle Delete Manual
if (isset($_GET['delete']) && isset($_GET['file'])) {
    $del_id = $conn->real_escape_string($_GET['delete']);
    $file_path = $_GET['file'];

    if (file_exists($file_path)) {
        unlink($file_path);
    }
    $conn->query("DELETE FROM lab_manuals WHERE id = '$del_id'");
    header("Location: Lab_Manuals.php");
    exit();
}

// Fetch all subjects for the Filter dropdown on the right side
$all_subjects = [];
$sub_res = $conn->query("SELECT DISTINCT subject_name FROM subjects ORDER BY subject_name ASC");
if ($sub_res) {
    while ($r = $sub_res->fetch_assoc()) {
        $all_subjects[] = $r['subject_name'];
    }
}

// 4. Build Dynamic Filter Query for displaying manuals
$filter_branch = isset($_GET['filter_branch']) ? $conn->real_escape_string($_GET['filter_branch']) : '';
$filter_sem = isset($_GET['filter_sem']) ? $conn->real_escape_string($_GET['filter_sem']) : '';
$filter_sub = isset($_GET['filter_subject']) ? $conn->real_escape_string($_GET['filter_subject']) : '';

$query = "SELECT * FROM lab_manuals WHERE 1=1";
if ($filter_branch != "") { $query .= " AND branch = '$filter_branch'"; }
if ($filter_sem != "") { $query .= " AND semester = '$filter_sem'"; }
if ($filter_sub != "") { $query .= " AND subject_name = '$filter_sub'"; }
$query .= " ORDER BY uploaded_at DESC";

$manuals = [];
$res = $conn->query($query);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $manuals[] = $row;
    }
}

$current_date = date("Y-m-d"); 
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
        .user-avatar { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px solid #3b82f6; background: #fff; }

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
        
        .badge-active { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: rgba(16,185,129,0.1); color: #059669; }
        .badge-invalid { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: rgba(239,68,68,0.1); color: #dc2626; }

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
        <div class="topbar">
            <div class="search-box">
                <i class="fas fa-search text-muted"></i>
                <input type="text" placeholder="Search lab manuals...">
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="user-profile">
                    <!-- KDP College Logo -->
                    <div class="user-avatar">
                        <img src="../logo.png" alt="KDP" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 13.5px; line-height: 1.2;">K.D. Polytechnic</div>
                        <div class="text-muted" style="font-size: 11.5px;">System Administrator</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-2">
            <h4 class="fw-bold text-dark mb-1">📄 Lab Manuals (Practicals)</h4>
            <p class="text-muted small mb-0">Upload PDF practical templates, set deadlines, and map to branches.</p>
        </div>

        <?php if($msg != "") echo $msg; ?>

        <div class="row g-4">
            <!-- UPLOAD FORM -->
            <div class="col-md-4">
                <div class="content-box">
                    <h6 class="fw-bold text-dark mb-4"><i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i>Upload Manual</h6>
                    <form method="POST" enctype="multipart/form-data">
                        
                        <div class="row">
                            <!-- IDs added here to connect with JavaScript -->
                            <div class="col-6 mb-3">
                                <label class="form-label text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">BRANCH</label>
                                <select name="branch" id="upload_branch" class="form-select" style="font-size: 13px;" onchange="fetchSubjects()" required>
                                    <option value="">-- Branch --</option>
                                    <option value="Computer Engineering">Computer Engg.</option>
                                    <option value="Mechanical Engineering">Mechanical Engg.</option>
                                    <option value="Civil Engineering">Civil Engg.</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">SEMESTER</label>
                                <select name="semester" id="upload_semester" class="form-select" style="font-size: 13px;" onchange="fetchSubjects()" required>
                                    <option value="">-- Sem --</option>
                                    <option value="Semester 1">Semester 1</option>
                                    <option value="Semester 2">Semester 2</option>
                                    <option value="Semester 3">Semester 3</option>
                                    <option value="Semester 4">Semester 4</option>
                                    <option value="Semester 5">Semester 5</option>
                                    <option value="Semester 6">Semester 6</option>
                                </select>
                            </div>
                        </div>

                        <!-- DYNAMIC SUBJECT DROPDOWN -->
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">SELECT SUBJECT</label>
                            <select name="subject_name" id="upload_subject" class="form-select" required>
                                <option value="">-- Pehle Branch & Sem select kare --</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-5 mb-3">
                                <label class="form-label text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">PRACTICAL NO.</label>
                                <input type="text" name="practical_no" class="form-control" placeholder="e.g. Exp 1" style="font-size: 13px;" required>
                            </div>
                            <div class="col-7 mb-3">
                                <label class="form-label text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">TITLE</label>
                                <input type="text" name="title" class="form-control" placeholder="SQL Queries..." style="font-size: 13px;" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">START DATE</label>
                                <input type="date" name="start_date" class="form-control" style="font-size: 13px;" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">DEADLINE (END)</label>
                                <input type="date" name="end_date" class="form-control" style="font-size: 13px;" required>
                            </div>
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
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <h6 class="fw-bold text-dark mb-0">Available Templates</h6>

                        <!-- FILTER FORM -->
                        <form method="GET" class="d-flex gap-2 flex-wrap">
                            <select name="filter_branch" class="form-select form-select-sm shadow-none" style="width: auto; font-size: 12px;" onchange="this.form.submit()">
                                <option value="">All Branches</option>
                                <option value="Computer Engineering" <?php if($filter_branch == "Computer Engineering") echo 'selected'; ?>>Computer Engg.</option>
                                <option value="Mechanical Engineering" <?php if($filter_branch == "Mechanical Engineering") echo 'selected'; ?>>Mechanical Engg.</option>
                                <option value="Civil Engineering" <?php if($filter_branch == "Civil Engineering") echo 'selected'; ?>>Civil Engg.</option>
                            </select>

                            <select name="filter_sem" class="form-select form-select-sm shadow-none" style="width: auto; font-size: 12px;" onchange="this.form.submit()">
                                <option value="">All Sems</option>
                                <?php for($i=1; $i<=6; $i++): $s = "Semester $i"; ?>
                                    <option value="<?php echo $s; ?>" <?php if($filter_sem == $s) echo 'selected'; ?>><?php echo $s; ?></option>
                                <?php endfor; ?>
                            </select>

                            <select name="filter_subject" class="form-select form-select-sm shadow-none" style="width: auto; font-size: 12px;" onchange="this.form.submit()">
                                <option value="">All Subjects</option>
                                <?php foreach($all_subjects as $sub): ?>
                                    <option value="<?php echo htmlspecialchars($sub); ?>" <?php if($filter_sub == $sub) echo 'selected'; ?>><?php echo htmlspecialchars($sub); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Practical</th>
                                    <th>Details</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($manuals)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No lab manuals uploaded matching filters.</td></tr>
                                <?php else: foreach($manuals as $man): 
                                    $is_active = ($current_date <= $man['end_date']);
                                ?>
                                    <tr>
                                        <td><span class="badge-exp"><?php echo htmlspecialchars($man['practical_no']); ?></span></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($man['title']); ?></div>
                                            <small class="text-muted" style="font-size: 11px;">
                                                <?php echo htmlspecialchars($man['branch']); ?> | <?php echo htmlspecialchars($man['semester']); ?> | <?php echo htmlspecialchars($man['subject_name']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div style="font-size: 13px; color: #475569;">
                                                <i class="fas fa-calendar-alt me-1 text-muted"></i> 
                                                <?php echo date('d M Y', strtotime($man['end_date'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($is_active): ?>
                                                <span class="badge-active">Active</span>
                                            <?php else: ?>
                                                <span class="badge-invalid">Invalid/Expired</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?php echo htmlspecialchars($man['file_path']); ?>" target="_blank" class="btn-view me-1" title="View PDF">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </a>
                                            <a href="?delete=<?php echo $man['id']; ?>&file=<?php echo urlencode($man['file_path']); ?>" class="btn-delete" onclick="return confirm('Kya aap yeh manual sach mein delete karna chahte hain?');" title="Delete">
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

    <!-- JAVASCRIPT FOR DYNAMIC AJAX CALLS -->
    <script>
        function fetchSubjects() {
            var branch = document.getElementById("upload_branch").value;
            var sem = document.getElementById("upload_semester").value;
            var subjectDropdown = document.getElementById("upload_subject");

            if (branch !== "" && sem !== "") {
                subjectDropdown.innerHTML = '<option value="">⏳ Fetching subjects...</option>';
                
                // Fetching database data in background
                fetch(`Lab_Manuals.php?ajax_subjects=1&branch=${encodeURIComponent(branch)}&semester=${encodeURIComponent(sem)}`)
                    .then(response => response.text())
                    .then(data => {
                        subjectDropdown.innerHTML = data;
                    })
                    .catch(error => {
                        subjectDropdown.innerHTML = '<option value="">❌ Error loading subjects</option>';
                    });
            } else {
                subjectDropdown.innerHTML = '<option value="">-- Pehle Branch & Sem select kare --</option>';
            }
        }
    </script>
</body>
</html>