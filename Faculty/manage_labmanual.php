<?php
session_start();
include '../db.php';

// 1. Faculty Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$faculty_name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Faculty';

// Generate Initials
$name_parts = explode(' ', trim($faculty_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

$msg = "";

// ==========================================
// 🚀 UPLOAD NEW LAB MANUAL LOGIC
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_manual'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $department = $conn->real_escape_string($_POST['department']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $subject_name = $conn->real_escape_string($_POST['subject_name']);
    $end_date = empty($_POST['end_date']) ? '0000-00-00' : $conn->real_escape_string($_POST['end_date']);
    $prac_no = $conn->real_escape_string($_POST['practical_no'] ?? '1'); // Fixed missing practical_no handling

    if (isset($_FILES['manual_file']) && $_FILES['manual_file']['error'] == 0) {
        $upload_dir = "../uploads/lab_manuals/";
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

        $file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]+/", "_", basename($_FILES['manual_file']['name']));
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['manual_file']['tmp_name'], $target_file)) {
            $db_path = "uploads/lab_manuals/" . $file_name;
            
            // Faculty details implicitly tied to the upload
            $sql = "INSERT INTO lab_manuals (title, department, semester, subject_name, practical_no, file_path, end_date) 
                    VALUES ('$title', '$department', '$semester', '$subject_name', '$prac_no', '$db_path', '$end_date')";
            
            if ($conn->query($sql)) {
                $msg = "<div class='alert alert-success alert-dismissible fade show mb-4 shadow-sm' style='border-radius:10px;'><i class='fas fa-check-circle me-2'></i> Manual Uploaded Successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $msg = "<div class='alert alert-danger alert-dismissible fade show mb-4 shadow-sm' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i> DB Error: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } else {
            $msg = "<div class='alert alert-danger alert-dismissible fade show mb-4 shadow-sm' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i> Failed to move file to uploads folder.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } else {
        $msg = "<div class='alert alert-warning alert-dismissible fade show mb-4 shadow-sm' style='border-radius:10px;'><i class='fas fa-exclamation-circle me-2'></i> Please select a valid PDF/DOC file.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// 🗑️ DELETE LAB MANUAL LOGIC
// ==========================================
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    
    // Safety check to only delete their own subjects' manuals
    $get_file = $conn->query("SELECT file_path FROM lab_manuals WHERE id='$del_id'");
    if ($get_file && $get_file->num_rows > 0) {
        $file_data = $get_file->fetch_assoc();
        $full_path = "../" . $file_data['file_path'];
        if (file_exists($full_path)) { unlink($full_path); }
        
        $conn->query("DELETE FROM lab_manuals WHERE id='$del_id'");
        header("Location: manage_manuals.php");
        exit();
    }
}

// ==========================================
// 🧠 SMART FACULTY SUBJECT MAPPING (Restricts choices)
// ==========================================
$fac_name_safe = $conn->real_escape_string($faculty_name);
$assigned_subjects = [];
$sub_query = "SELECT DISTINCT department, semester, subject_name FROM subjects WHERE faculty_name LIKE '%$fac_name_safe%' ORDER BY semester ASC, subject_name ASC";
$sub_res = $conn->query($sub_query);
if ($sub_res && $sub_res->num_rows > 0) {
    while($r = $sub_res->fetch_assoc()) { 
        // Group by branch and semester for clean dropdown
        $key = $r['department'] . "|" . $r['semester'];
        $assigned_subjects[$key][] = $r['subject_name']; 
    }
}

// ==========================================
// 📋 FETCH MANUALS LIST (Only for assigned subjects)
// ==========================================
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$safe_search = $conn->real_escape_string($search_query);

// Build IN clause for security
$allowed_subs = [];
foreach($assigned_subjects as $subs) { foreach($subs as $s) { $allowed_subs[] = "'".$conn->real_escape_string($s)."'"; } }
$in_clause = empty($allowed_subs) ? "''" : implode(",", $allowed_subs);

$list_sql = "SELECT * FROM lab_manuals WHERE subject_name IN ($in_clause)";
if (!empty($search_query)) {
    $list_sql .= " AND (title LIKE '%$safe_search%' OR subject_name LIKE '%$safe_search%')";
}
$list_sql .= " ORDER BY uploaded_at DESC";
$manuals_list = $conn->query($list_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Manuals - KDP Faculty</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --sidebar-width: 270px; 
            --primary: #4338ca; 
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --shadow-float: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0; }
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover, .nav-links li.active { color: #ffffff; background: rgba(255,255,255,0.2); }
        .nav-links li.active { font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid white;}
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; }
        
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 10px 22px 10px 28px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); text-decoration: none; color: inherit; box-shadow: var(--shadow-float); }
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800;}

        .content-box { background: var(--surface); border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); padding: 30px;}
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }
        
        .form-label { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;}
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 12px 15px; font-size: 14.5px; font-weight: 600; background: #f8fafc;}
        .form-control:focus, .form-select:focus { background: white; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); }
        
        .btn-upload { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; width: 100%; transition: 0.2s;}
        .btn-upload:hover { transform: translateY(-2px); box-shadow: 0 8px 15px rgba(16, 185, 129, 0.3); }

        .search-group { display: flex; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; background: #f8fafc; }
        .search-group input { border: none; background: transparent; padding: 10px 15px; font-weight: 600; font-size: 14px; flex: 1; outline: none;}
        .search-group button { border: none; background: var(--primary); color: white; padding: 0 20px; cursor: pointer; transition: 0.2s;}

        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); padding: 15px 10px; background: #f8fafc; border-bottom: 2px solid #e2e8f0;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; }
        
        .btn-action { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center;}
        .btn-action:hover { background: #2563eb; color: white; transform: translateY(-2px); }
        .btn-delete { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        .btn-delete:hover { background: #dc2626; color: white;}
    </style>
</head>
<body>
    
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='manage_manuals.php'"><i class="fas fa-file-pdf"></i> Upload Manuals</li>
            <li onclick="window.location.href='labmanual_list.php'"><i class="fas fa-check-double"></i> Review & Evaluate</li>
            <li onclick="window.location.href='reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            </div>
            
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome" style="font-size: 11px;">Welcome Back,</span>
                    <h4 class="profile-name" style="font-size: 17px; font-weight: 900;">
                        <?php 
                            $disp_name = htmlspecialchars($faculty_name);
                            if(stripos($disp_name, 'Prof') === false && stripos($disp_name, 'Dr') === false) { echo "Prof. " . $disp_name; } else { echo $disp_name; }
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </a>
        </div>

        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1" style="font-size: 26px;">Manuals Repository</h3>
            <p class="text-muted small fw-semibold mb-0">Upload official lab manuals, set deadlines, and manage tasks for your students.</p>
        </div>

        <?php echo $msg; ?>

        <div class="row g-4">
            <!-- LEFT COLUMN: UPLOAD FORM -->
            <div class="col-lg-4">
                <div class="content-box h-100">
                    <h5 class="box-title"><i class="fas fa-cloud-upload-alt text-primary me-2"></i> Upload New Manual</h5>
                    
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="upload_manual" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label">Manual Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. DAA Practical 1-10" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Practical No.</label>
                            <input type="text" name="practical_no" class="form-control" placeholder="e.g. Prac 1, Ex 5" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Target Subject</label>
                            <select id="subjectSelect" class="form-select" onchange="autoFillDeptSem()" required>
                                <option value="" disabled selected>-- Select your assigned subject --</option>
                                <?php 
                                    if(empty($assigned_subjects)) {
                                        echo '<option value="" disabled>No subjects assigned to you</option>';
                                    } else {
                                        foreach($assigned_subjects as $key => $subs) {
                                            list($dept, $sem) = explode("|", $key);
                                            echo "<optgroup label='".htmlspecialchars($dept)." - Sem ".htmlspecialchars($sem)."'>";
                                            foreach($subs as $sub) {
                                                echo "<option value='".htmlspecialchars($sub)."' data-dept='".htmlspecialchars($dept)."' data-sem='".htmlspecialchars($sem)."'>".htmlspecialchars($sub)."</option>";
                                            }
                                            echo "</optgroup>";
                                        }
                                    }
                                ?>
                            </select>
                            <!-- Hidden inputs to submit dept and sem automatically -->
                            <input type="hidden" name="subject_name" id="hiddenSubject">
                            <input type="hidden" name="department" id="hiddenDept">
                            <input type="hidden" name="semester" id="hiddenSem">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Submission Deadline</label>
                            <input type="date" name="end_date" class="form-control">
                            <small class="text-muted fw-semibold mt-1 d-block" style="font-size:11px;">Optional. Leave blank if no strict deadline.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Upload File (PDF/DOC)</label>
                            <input type="file" name="manual_file" class="form-control" accept=".pdf,.doc,.docx" required>
                        </div>

                        <button type="submit" class="btn-upload"><i class="fas fa-upload me-2"></i> Upload Securely</button>
                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN: MANUALS TABLE -->
            <div class="col-lg-8">
                <div class="content-box h-100 p-0 overflow-hidden">
                    <div class="d-flex justify-content-between align-items-center p-4 border-bottom">
                        <h5 class="box-title mb-0 border-0 pb-0"><i class="fas fa-folder-open text-success me-2"></i> Uploaded Manuals</h5>
                        
                        <div class="d-flex gap-2">
                            <form method="GET" class="search-group" style="width: 250px;">
                                <input type="text" name="search" placeholder="Search manuals..." value="<?php echo htmlspecialchars($search_query); ?>">
                                <button type="submit"><i class="fas fa-search"></i></button>
                            </form>
                            <button class="btn btn-outline-dark fw-bold border-1 border-secondary" onclick="exportTableToCSV('Lab_Manuals_List.csv')" title="Export to Excel">
                                <i class="fas fa-file-csv"></i>
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom mb-0" id="manualTable">
                            <thead>
                                <tr>
                                    <th class="ps-4">Manual Details</th>
                                    <th>Subject & Prac</th>
                                    <th>Important Dates</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($manuals_list && $manuals_list->num_rows > 0): ?>
                                    <?php while($row = $manuals_list->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-danger text-white rounded p-2 text-center" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                                                        <i class="fas fa-file-pdf fs-5"></i>
                                                    </div>
                                                    <div class="fw-bold text-dark" style="font-size: 14.5px;"><?php echo htmlspecialchars($row['title']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-primary fw-bold" style="font-size: 13.5px;"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                                                <div class="text-muted fw-bold small mt-1"><i class="fas fa-tag me-1"></i> Prac: <?php echo htmlspecialchars($row['practical_no'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td>
                                                <div style="font-size: 12px; color: var(--text-main);" class="fw-semibold">
                                                    <i class="fas fa-upload text-muted me-1"></i> <?php echo date('d M Y', strtotime($row['uploaded_at'])); ?><br>
                                                    <?php 
                                                        $deadline = (!empty($row['end_date']) && $row['end_date'] != '0000-00-00') ? date('d M Y', strtotime($row['end_date'])) : 'No Deadline';
                                                        $color = ($deadline == 'No Deadline') ? 'text-muted' : 'text-danger';
                                                    ?>
                                                    <div class="mt-1"><i class="fas fa-flag-checkered <?php echo $color; ?> me-1"></i> <span class="<?php echo $color; ?>"><?php echo $deadline; ?></span></div>
                                                </div>
                                            </td>
                                            <td class="text-end pe-4">
                                                <?php 
                                                    $raw_path = $row['file_path'];
                                                    $pos = strpos($raw_path, 'uploads/');
                                                    $safe_pdf_path = ($pos !== false) ? '../' . substr($raw_path, $pos) : '../' . $raw_path;
                                                ?>
                                                <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" target="_blank" class="btn-action me-1" title="View PDF"><i class="fas fa-eye"></i></a>
                                                <a href="manage_manuals.php?delete_id=<?php echo $row['id']; ?>" class="btn-action btn-delete" title="Delete Manual" onclick="return confirm('Are you sure you want to permanently delete this manual?');"><i class="fas fa-trash-alt"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="fas fa-box-open mb-3" style="font-size: 40px; opacity: 0.3;"></i><br>
                                            <span class="fw-bold fs-6">No manuals uploaded yet.</span>
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
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Auto-fill hidden inputs based on selected subject
        function autoFillDeptSem() {
            var select = document.getElementById("subjectSelect");
            var selectedOption = select.options[select.selectedIndex];
            
            if(selectedOption.value !== "") {
                document.getElementById("hiddenSubject").value = selectedOption.value;
                document.getElementById("hiddenDept").value = selectedOption.getAttribute("data-dept");
                document.getElementById("hiddenSem").value = selectedOption.getAttribute("data-sem");
            }
        }

        // Export Table to CSV
        function exportTableToCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("#manualTable tr");
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                for (var j = 0; j < cols.length - 1; j++) { // Skip action column
                    var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " - ");
                    row.push('"' + data + '"');
                }
                csv.push(row.join(","));
            }

            var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
            var downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }
    </script>
</body>
</html>