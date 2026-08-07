<?php
session_start();
include '../db.php';

// 1. Check Login
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$msg = "";

// 2. Handle Grading (Rubric Assessment, Reject, or Reset)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 🔥 RESET TO PENDING LOGIC
    if (isset($_POST['action_type']) && $_POST['action_type'] === 'single_grade' && $_POST['action'] === 'Reset') {
        $sub_id = (int)$_POST['submission_id'];
        $update_sql = "UPDATE submissions SET status='Pending', marks=0, remark='', rubric_data=NULL WHERE id=$sub_id";
        if ($conn->query($update_sql)) {
            $msg = "<div class='alert alert-warning alert-dismissible fade show shadow-sm'><i class='fa-solid fa-rotate-left me-2'></i> Submission reset to <strong>Pending</strong>! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } 
    // 🔥 NEW RUBRIC GRADING LOGIC
    elseif (isset($_POST['action_type']) && $_POST['action_type'] === 'rubric_grade') {
        $sub_id = (int)$_POST['submission_id'];
        $action = $_POST['action']; 
        $remark = $conn->real_escape_string(trim($_POST['remark']));
        
        // Rubric Marks (Out of 5 each)
        $reg = isset($_POST['mark_reg']) ? (int)$_POST['mark_reg'] : 0;
        $und = isset($_POST['mark_und']) ? (int)$_POST['mark_und'] : 0;
        $obs = isset($_POST['mark_obs']) ? (int)$_POST['mark_obs'] : 0;
        $viva = isset($_POST['mark_viva']) ? (int)$_POST['mark_viva'] : 0;
        
        $total_marks = $reg + $und + $obs + $viva; // Total out of 20
        $status = ($action === 'Approve') ? 'Approved' : 'Rejected';
        
        if ($status === 'Rejected') {
            $total_marks = 0;
            $rubric_json = NULL;
        } else {
            // Save rubric breakdown as JSON
            $rubric_json = json_encode(['reg' => $reg, 'und' => $und, 'obs' => $obs, 'viva' => $viva]);
        }

        $rubric_val = ($rubric_json === NULL) ? "NULL" : "'$rubric_json'";
        
        $update_sql = "UPDATE submissions SET status='$status', marks='$total_marks', remark='$remark', rubric_data=$rubric_val WHERE id=$sub_id";
        if ($conn->query($update_sql)) {
            $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm'><i class='fa-solid fa-circle-check me-2'></i> Status updated to <strong>$status</strong> with $total_marks/20 marks! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $msg = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    }
    // 3. Handle Bulk Approval (Sabhi Pending ko 20/20 dena)
    elseif (isset($_POST['action_type']) && $_POST['action_type'] === 'bulk_approve') {
        $bulk_sub_name = $conn->real_escape_string($_POST['bulk_subject']);
        if (!empty($bulk_sub_name)) {
            $default_rubric = json_encode(['reg' => 5, 'und' => 5, 'obs' => 5, 'viva' => 5]);
            $bulk_sql = "UPDATE submissions SET status='Approved', marks=20, remark='Auto Approved', rubric_data='$default_rubric' WHERE subject='$bulk_sub_name' AND status='Pending'";
            if ($conn->query($bulk_sql)) {
                $affected = $conn->affected_rows;
                $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm'><i class='fa-solid fa-wand-magic-sparkles me-2'></i> Successfully approved <strong>$affected</strong> pending submissions with 20/20 marks! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        }
    }
}

// 4. Fetch Subjects & Group by Semester
$raw_subjects = [];
$sub_query = "SELECT subjects FROM users WHERE user_id = '$faculty_id'";
$sub_result = $conn->query($sub_query);
if ($sub_result && $sub_result->num_rows > 0) {
    $row = $sub_result->fetch_assoc();
    if (!empty($row['subjects'])) {
        $decoded = json_decode($row['subjects'], true);
        $raw_subjects = is_array($decoded) ? $decoded : array_map('trim', explode(',', $row['subjects']));
    }
}

$faculty_semesters = ["Semester 1", "Semester 2", "Semester 3", "Semester 4", "Semester 5", "Semester 6"];
$grouped_subjects = array_fill_keys($faculty_semesters, []);
$grouped_subjects["Other Subjects"] = [];

foreach($raw_subjects as $sub) {
    $sub_text = is_array($sub) ? ($sub['name'] ?? '') : $sub;
    if(preg_match('/Sem\s*(\d+)\s*[-:]\s*(.*)/i', $sub_text, $matches)) {
        $sem = "Semester " . $matches[1];
        $subject_name = trim($matches[2]);
        if(!in_array($sem, $faculty_semesters)) $faculty_semesters[] = $sem;
    } else {
        $sem = "Other Subjects"; 
        $subject_name = trim($sub_text);
    }
    $grouped_subjects[$sem][] = $subject_name;
}

// 5. Handle Filters from URL & Search Query
$selected_sem = isset($_GET['sem']) && in_array($_GET['sem'], $faculty_semesters) ? $_GET['sem'] : 'Semester 1';
$available_subjects = $grouped_subjects[$selected_sem] ?? [];
$selected_subject = isset($_GET['subject']) && in_array($_GET['subject'], $available_subjects) ? $_GET['subject'] : ($available_subjects[0] ?? '');
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'All';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$safe_sub = $conn->real_escape_string($selected_subject);

// 6. Build SQL Query based on Filters
$submissions_data = [];
if (!empty($safe_sub)) {
    $sql_where = "WHERE s.subject = '$safe_sub'";
    if ($status_filter !== 'All') {
        $safe_status = $conn->real_escape_string($status_filter);
        $sql_where .= " AND s.status = '$safe_status'";
    }
    if (!empty($search_query)) {
        $safe_search = $conn->real_escape_string($search_query);
        $sql_where .= " AND (s.enrollment LIKE '%$safe_search%' OR u.name LIKE '%$safe_search%')";
    }
    $query = "SELECT s.*, u.name as student_name FROM submissions s LEFT JOIN users u ON s.enrollment = u.user_id $sql_where ORDER BY s.id DESC";
    $res = $conn->query($query);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $submissions_data[] = $r;
        }
    }
}

// 7. CSV/EXCEL EXPORT LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['export_csv'])) {
    $filename = "Marks_Report_" . str_replace(' ', '_', $safe_sub) . "_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Enrollment No.', 'Student Name', 'Subject', 'Status', 'Marks (Out of 20)', 'Feedback/Remark'));
    foreach ($submissions_data as $row) {
        $student_name = $row['student_name'] ?: 'Student';
        fputcsv($output, array($row['enrollment'], $student_name, $row['subject'], $row['status'], ($row['status'] == 'Pending') ? '-' : $row['marks'], $row['remark']));
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Lab Manuals - Faculty Portal</title>
    <!-- Bootstrap CSS for Modal support -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        
        .sidebar { width: 260px; background-color: #113460; color: #ffffff; display: flex; flex-direction: column; padding: 25px 0; box-shadow: 4px 0 10px rgba(0,0,0,0.1); z-index: 10; }
        .sidebar-logo-container { text-align: center; margin-bottom: 20px; }
        .logo-wrapper { width: 90px; height: 90px; background: #ffffff; border-radius: 50%; margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; overflow: hidden; box-shadow: 0 0 15px rgba(255,255,255,0.15); border: 3px solid rgba(255,255,255,0.2); }
        .sidebar-logo { width: 105%; height: auto; }
        .sidebar-title h2 { font-size: 19px; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 2px; }
        .sidebar-title p { font-size: 13px; color: #94a3b8; margin: 0;}
        .sidebar-divider { height: 1px; background: #1e4b85; margin: 15px 20px; }
        
        .nav-links { list-style: none; padding: 0; flex-grow: 1; margin-top: 10px; }
        .nav-links li { padding: 15px 25px; margin: 5px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 15px; transition: 0.3s; color: #cbd5e1; }
        .nav-links li:hover { background: #1e4b85; color: white; }
        .nav-links li.active { background: #2563eb; color: white; box-shadow: 0 4px 10px rgba(37,99,235,0.3); }
        
        .main { flex: 1; padding: 30px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-left h2 { color: #0f172a; font-size: 26px; font-weight: 700; margin: 0; }
        .header-left p { color: #64748b; font-size: 14px; margin-top: 5px; }
        .header-actions { display: flex; gap: 10px; align-items: center; }

        .filter-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .filter-row { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .filter-box { display: flex; flex-direction: column; flex: 1; min-width: 180px; }
        .filter-box label { color: #64748b; font-size: 11px; margin-bottom: 6px; text-transform: uppercase; font-weight: 700; }
        .filter-box select, .filter-box input { padding: 9px 12px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; font-size: 14px; }

        .table-card { background: white; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); overflow: hidden; }
        .table-custom { margin: 0; width: 100%; border-collapse: collapse; }
        .table-custom th { background: #f8fafc; color: #475569; padding: 14px 16px; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .table-custom td { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid #e2e8f0; color: #1e293b; font-size: 14px; }
        
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; text-transform: uppercase; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #d1fae5; color: #059669; }
        .status-rejected { background: #fee2e2; color: #dc2626; }

        .btn-pdf { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .btn-pdf:hover { background: #2563eb; color: white; }

        .btn-evaluate { background: #2563eb; color: white; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .btn-evaluate:hover { background: #1d4ed8; }
        .btn-reset { background: #f59e0b; color: white; border: none; padding: 6px 10px; border-radius: 6px; font-size: 12px; cursor: pointer; }
        
        .btn-bulk { background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .btn-export { background: #10b981; }

        /* 🔥 RUBRIC TABLE CSS */
        .rubric-table th { background: #f1f5f9; font-size: 13px; text-align: center; }
        .rubric-table td { font-size: 12px; vertical-align: middle; }
        .rubric-desc { color: #64748b; font-size: 11px; }
        .rubric-input { width: 70px; text-align: center; font-weight: bold; margin: 0 auto; }
        .total-box { font-size: 20px; font-weight: bold; color: #2563eb; text-align: right; padding: 15px; background: #eff6ff; border-radius: 8px; margin-top: 15px; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <!-- Sidebar content same as before -->
        <div class="sidebar-logo-container">
            <div class="logo-wrapper"><img src="../assets/images/KDP-Logo.png" alt="Logo" class="sidebar-logo"></div>
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2><p>Faculty Portal</p></div>
        </div>
        <div class="sidebar-divider"></div>
        <ul class="nav-links">
            <li onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='labmanual_list.php'"><i class="fas fa-book-open"></i> Lab Manuals</li>
            <li onclick="window.location.href='reports.php'"><i class="fas fa-file-alt"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
            <li class="logout-btn" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <div class="header">
            <div class="header-left">
                <h2>Check Lab Manuals</h2>
                <p>Use GTU Standard Assessment Rubrics for Grading.</p>
            </div>
            <div class="header-actions">
                <?php if (!empty($submissions_data)): ?>
                    <form method="POST" style="margin: 0;">
                        <button type="submit" name="export_csv" value="1" class="btn-bulk btn-export"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
                    </form>
                <?php endif; ?>
                <?php if (!empty($selected_subject)): ?>
                    <form method="POST" onsubmit="return confirm('Approve ALL pending submissions with 20/20 marks?');" style="margin: 0;">
                        <input type="hidden" name="action_type" value="bulk_approve">
                        <input type="hidden" name="bulk_subject" value="<?php echo htmlspecialchars($selected_subject); ?>">
                        <button type="submit" class="btn-bulk"><i class="fa-solid fa-wand-magic-sparkles"></i> Approve All</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php echo $msg; ?>

        <!-- FILTERS (Same as before) -->
        <div class="filter-card">
            <form method="GET" id="filterForm">
                <div class="filter-row">
                    <div class="filter-box">
                        <label>Semester</label>
                        <select name="sem" onchange="document.getElementById('filterForm').submit();">
                            <?php foreach($faculty_semesters as $sem) { ?>
                                <option value="<?php echo $sem; ?>" <?php if($selected_sem == $sem) echo 'selected'; ?>><?php echo $sem; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="filter-box">
                        <label>Subject</label>
                        <select name="subject" onchange="document.getElementById('filterForm').submit();">
                            <?php if(empty($available_subjects)) { ?>
                                <option value="">No Subjects</option>
                            <?php } else { foreach($available_subjects as $sub) { ?>
                                <option value="<?php echo htmlspecialchars($sub); ?>" <?php if($selected_subject == $sub) echo 'selected'; ?>><?php echo htmlspecialchars($sub); ?></option>
                            <?php } } ?>
                        </select>
                    </div>
                    <div class="filter-box">
                        <label>Filter Status</label>
                        <select name="status_filter" onchange="document.getElementById('filterForm').submit();">
                            <option value="All" <?php if($status_filter == 'All') echo 'selected'; ?>>All Submissions</option>
                            <option value="Pending" <?php if($status_filter == 'Pending') echo 'selected'; ?>>Pending</option>
                            <option value="Approved" <?php if($status_filter == 'Approved') echo 'selected'; ?>>Approved</option>
                            <option value="Rejected" <?php if($status_filter == 'Rejected') echo 'selected'; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="filter-box">
                        <label>Search Student</label>
                        <input type="text" name="search" placeholder="Name or Enrollment..." value="<?php echo htmlspecialchars($search_query); ?>" onchange="document.getElementById('filterForm').submit();">
                    </div>
                </div>
            </form>
        </div>

        <!-- TABLE DATA -->
        <div class="table-card">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Enrollment No.</th>
                        <th>Student Name</th>
                        <th>PDF File</th>
                        <th>Status</th>
                        <th>Marks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions_data)) { ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted"><i class="fa-solid fa-inbox fs-4 mb-2 d-block"></i> No submissions found.</td></tr>
                    <?php } else { 
                        foreach ($submissions_data as $row) { 
                            $status_class = "status-" . strtolower($row['status']);
                            $student_name = $row['student_name'] ?: "Student";
                            $rubric_json = htmlspecialchars(json_encode($row['rubric_data'] ?? ''));
                            $remark_text = htmlspecialchars($row['remark'] ?? '');
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['enrollment']); ?></strong></td>
                        <td><?php echo htmlspecialchars($student_name); ?></td>
                        <td>
                            <a href="../uploads/<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn-pdf">
                                <i class="fa-regular fa-file-pdf"></i> View Manual
                            </a>
                        </td>
                        <td><span class="badge-status <?php echo $status_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        <td>
                            <strong><?php echo ($row['status'] == 'Pending') ? '-' : $row['marks'] . ' / 20'; ?></strong>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <!-- Evaluate Button opens the Modal -->
                                <button type="button" class="btn-evaluate" 
                                    onclick="openGradingModal(<?php echo $row['id']; ?>, '<?php echo addslashes($student_name); ?>', '<?php echo $rubric_json; ?>', '<?php echo addslashes($remark_text); ?>')">
                                    <i class="fa-solid fa-clipboard-check"></i> Evaluate
                                </button>
                                
                                <!-- Undo/Reset Button -->
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action_type" value="single_grade">
                                    <input type="hidden" name="submission_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="action" value="Reset" class="btn-reset" title="Reset to Pending"><i class="fa-solid fa-rotate-left"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php } } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 🔥 ASSESSMENT RUBRIC MODAL -->
    <div class="modal fade" id="gradingModal" tabindex="-1" aria-labelledby="gradingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: #113460; color: white;">
                    <h5 class="modal-title" id="gradingModalLabel"><i class="fa-solid fa-file-signature me-2"></i> Assessment Rubrics</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form method="POST">
                    <div class="modal-body">
                        <p><strong>Evaluating: </strong> <span id="modal_student_name" class="text-primary fw-bold"></span></p>
                        <input type="hidden" name="action_type" value="rubric_grade">
                        <input type="hidden" name="submission_id" id="modal_sub_id">
                        
                        <table class="table table-bordered rubric-table">
                            <thead>
                                <tr>
                                    <th>Criteria</th>
                                    <th>Max (M)</th>
                                    <th>Rubrics Guidelines</th>
                                    <th>Marks Given</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Regularity -->
                                <tr>
                                    <td class="fw-bold">Regularity</td>
                                    <td class="text-center">5</td>
                                    <td class="rubric-desc">
                                        Low: (1-2) Poor (0-40%)<br>
                                        Medium: (3-4) Moderate (40-70%)<br>
                                        High: (5) High (>70%)
                                    </td>
                                    <td><input type="number" name="mark_reg" id="mark_reg" class="form-control rubric-input" min="0" max="5" value="0" oninput="calculateTotal()" required></td>
                                </tr>
                                <!-- Understanding -->
                                <tr>
                                    <td class="fw-bold">Understanding</td>
                                    <td class="text-center">5</td>
                                    <td class="rubric-desc">
                                        Low: (1-2) Poor Understanding<br>
                                        Medium: (3-4) Basic Understanding<br>
                                        High: (5) Excellent Understanding
                                    </td>
                                    <td><input type="number" name="mark_und" id="mark_und" class="form-control rubric-input" min="0" max="5" value="0" oninput="calculateTotal()" required></td>
                                </tr>
                                <!-- Observation / Analysis -->
                                <tr>
                                    <td class="fw-bold">Observation / Analysis</td>
                                    <td class="text-center">5</td>
                                    <td class="rubric-desc">
                                        Low: (1-2) Incomplete observation<br>
                                        Medium: (3-4) Basic analysis<br>
                                        High: (5) Analyzes systematically
                                    </td>
                                    <td><input type="number" name="mark_obs" id="mark_obs" class="form-control rubric-input" min="0" max="5" value="0" oninput="calculateTotal()" required></td>
                                </tr>
                                <!-- Mock Viva Test -->
                                <tr>
                                    <td class="fw-bold">Mock Viva Test</td>
                                    <td class="text-center">5</td>
                                    <td class="rubric-desc">
                                        Low: (1-2) Very few correct<br>
                                        Medium: (3-4) Partially correct<br>
                                        High: (5) All correct
                                    </td>
                                    <td><input type="number" name="mark_viva" id="mark_viva" class="form-control rubric-input" min="0" max="5" value="0" oninput="calculateTotal()" required></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="total-box">
                            Marks for Practical: <span id="totalMarksDisplay">0</span> / 20
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-bold">Feedback / Remarks</label>
                            <input type="text" name="remark" id="modal_remark" class="form-control" placeholder="Enter remarks (if any)...">
                        </div>
                    </div>
                    
                    <div class="modal-footer" style="background: #f8fafc;">
                        <button type="submit" name="action" value="Reject" class="btn btn-danger"><i class="fa-solid fa-xmark"></i> Reject Submission</button>
                        <button type="submit" name="action" value="Approve" class="btn btn-success px-4"><i class="fa-solid fa-check"></i> Approve & Save Marks</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle (Includes Popper for Modals) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Calculate Total Marks dynamically in the Modal
        function calculateTotal() {
            let reg = parseInt(document.getElementById('mark_reg').value) || 0;
            let und = parseInt(document.getElementById('mark_und').value) || 0;
            let obs = parseInt(document.getElementById('mark_obs').value) || 0;
            let viva = parseInt(document.getElementById('mark_viva').value) || 0;
            
            // Prevent going above 5
            if(reg > 5) { reg = 5; document.getElementById('mark_reg').value = 5; }
            if(und > 5) { und = 5; document.getElementById('mark_und').value = 5; }
            if(obs > 5) { obs = 5; document.getElementById('mark_obs').value = 5; }
            if(viva > 5) { viva = 5; document.getElementById('mark_viva').value = 5; }
            
            let total = reg + und + obs + viva;
            document.getElementById('totalMarksDisplay').innerText = total;
        }

        // Open Modal and Pre-fill Data
        function openGradingModal(id, name, rubricJson, remark) {
            document.getElementById('modal_sub_id').value = id;
            document.getElementById('modal_student_name').innerText = name;
            document.getElementById('modal_remark').value = remark;
            
            let rData = { reg: 0, und: 0, obs: 0, viva: 0 };
            
            // Format JSON string properly before parsing
            if(rubricJson && rubricJson !== 'null' && rubricJson !== '""') {
                try {
                    // Remove quotes added by HTML escaping if necessary
                    let cleanJson = rubricJson.replace(/^"|"$/g, '');
                    // Unescape HTML entities
                    let txt = document.createElement("textarea");
                    txt.innerHTML = cleanJson;
                    rData = JSON.parse(txt.value);
                } catch(e) { console.error("Error parsing Rubric JSON", e); }
            }
            
            document.getElementById('mark_reg').value = rData.reg || 0;
            document.getElementById('mark_und').value = rData.und || 0;
            document.getElementById('mark_obs').value = rData.obs || 0;
            document.getElementById('mark_viva').value = rData.viva || 0;
            
            calculateTotal();
            
            var modal = new bootstrap.Modal(document.getElementById('gradingModal'));
            modal.show();
        }
    </script>
</body>
</html>