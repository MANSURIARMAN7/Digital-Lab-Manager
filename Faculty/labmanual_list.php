<?php
session_start();
include '../db.php';

// 1. Check Login
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}
$faculty_id = $_SESSION['user_id'];
$faculty_name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'M.C.THAKOR';
$msg = "";

// ==========================================
// FORM HANDLING (Grade, Reset, Bulk, Export)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action_type']) && $_POST['action_type'] == 'single_grade' && $_POST['action'] == 'Reset') {
        $sub_id = intval($_POST['submission_id']);
        $conn->query("UPDATE submissions SET status='Pending', marks=0, remark='', rubric_data=NULL WHERE id=$sub_id");
    }
    elseif (isset($_POST['action_type']) && $_POST['action_type'] == 'rubric_grade') {
        $sub_id = intval($_POST['submission_id']);
        $action = $_POST['action'];
        $remark = $conn->real_escape_string(trim($_POST['remark']));
        $reg = isset($_POST['mark_reg']) ? intval($_POST['mark_reg']) : 0;
        $und = isset($_POST['mark_und']) ? intval($_POST['mark_und']) : 0;
        $obs = isset($_POST['mark_obs']) ? intval($_POST['mark_obs']) : 0;
        $viva = isset($_POST['mark_viva']) ? intval($_POST['mark_viva']) : 0;

        $total = $reg + $und + $obs + $viva;
        $status = ($action == 'Approve') ? 'Approved' : 'Rejected';
        if ($status == 'Rejected') {
            $total = 0; $r_json = "NULL";
        } else {
            $r_json = "'" . json_encode(["reg"=>$reg, "und"=>$und, "obs"=>$obs, "viva"=>$viva]) . "'";
        }
        $conn->query("UPDATE submissions SET status='$status', marks=$total, remark='$remark', rubric_data=$r_json WHERE id=$sub_id");
    }
    elseif (isset($_POST['action_type']) && $_POST['action_type'] == 'bulk_approve') {
        $b_sub = $conn->real_escape_string($_POST['bulk_subject']);
        $b_sem = $conn->real_escape_string($_POST['bulk_sem']);
        $b_branch = $conn->real_escape_string($_POST['bulk_branch']);
        $d_rub = json_encode(["reg"=>5, "und"=>5, "obs"=>5, "viva"=>5]);
        
        $b_sql = "UPDATE submissions s JOIN users u ON s.student_id = u.user_id 
                  SET s.status='Approved', s.marks=20, s.remark='Auto Approved', s.rubric_data='$d_rub' 
                  WHERE s.subject_name='$b_sub' AND s.status='Pending' AND u.designation='$b_sem' AND u.department='$b_branch'";
        $conn->query($b_sql);
    }
    elseif (isset($_POST['export_csv'])) {
        header("Content-Disposition: attachment; filename=\"Marks_Report.csv\"");
        header("Content-Type: text/csv;");
        $out = fopen("php://output", "w");
        fputcsv($out, ['Enrollment No.', 'Student Name', 'Branch', 'Subject', 'Status', 'Marks', 'Remark']);
        
        $e_sub = $conn->real_escape_string($_POST['export_subject']);
        $e_sem = $conn->real_escape_string($_POST['export_sem']);
        $e_branch = $conn->real_escape_string($_POST['export_branch']);
        
        $e_q = "SELECT s.*, u.name as student_name, u.department as branch FROM submissions s JOIN users u ON s.student_id = u.user_id WHERE s.subject_name='$e_sub' AND u.designation='$e_sem' AND u.department='$e_branch' ORDER BY s.student_id ASC";
        $e_res = $conn->query($e_q);
        while ($row = $e_res->fetch_assoc()) fputcsv($out, [$row['student_id'], $row['student_name'], $row['branch'], $row['subject_name'], $row['status'], $row['marks'], $row['remark']]);
        fclose($out); exit();
    }
}

// ==========================================
// 🚀 TERA LOGIC: PURE DATABASE DRIVEN FILTERS
// ==========================================

// 1. Fetch Branches from Database
$available_branches = [];
$branch_res = $conn->query("SELECT DISTINCT department FROM subjects WHERE department IS NOT NULL AND department != ''");
if($branch_res) {
    while($r = $branch_res->fetch_assoc()) {
        $available_branches[] = $r['department'];
    }
}
if(empty($available_branches)) $available_branches = ['Computer Engineering']; // Fallback
$selected_branch = isset($_GET['branch']) ? $_GET['branch'] : $available_branches[0];

// 2. Semesters (Fixed 1 to 6)
$available_semesters = ['Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Semester 5', 'Semester 6'];
$selected_sem = isset($_GET['sem']) ? $_GET['sem'] : 'Semester 1';

// 3. Fetch Subjects ONLY for Selected Branch & Semester from `subjects` table
$available_subjects = [];
$safe_br = $conn->real_escape_string($selected_branch);
$safe_sm = $conn->real_escape_string($selected_sem);

// Note: Ensure your columns in subjects table match (department, semester, subject_name)
// STRICT ACCESS: Sirf wahi subjects aayenge jo is faculty ko assign hue hain
    $sub_query = "SELECT subject_name FROM faculty_subjects WHERE faculty_id = '$faculty_id' AND branch = '$safe_br' AND semester = '$safe_sm'";
    $sub_res = $conn->query($sub_query);

    if ($sub_res && $sub_res->num_rows > 0) {
        while($r = $sub_res->fetch_assoc()) {
            $available_subjects[] = $r['subject_name'];
        }
    }

$selected_subject = isset($_GET['subject']) ? $_GET['subject'] : (!empty($available_subjects) ? $available_subjects[0] : '');
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'All';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// ==========================================
// FETCH ONLY THOSE STUDENTS WHO ARE IN THIS BRANCH & SEMESTER
// ==========================================
$submissions_data = [];
if (!empty($selected_subject)) {
    $s_sub = $conn->real_escape_string($selected_subject);
    
    // TERA LOGIC YAHAN HAI: Sirf wahi student aayenge jo us branch aur sem ke hain!
    $q = "SELECT s.*, u.name as student_name 
          FROM submissions s 
          JOIN users u ON s.student_id = u.user_id 
          WHERE s.subject_name = '$s_sub' 
          AND u.department = '$safe_br' 
          AND u.designation = '$safe_sm'";
          
    if ($status_filter !== 'All') $q .= " AND s.status = '".$conn->real_escape_string($status_filter)."'";
    if (!empty($search_query)) $q .= " AND (u.name LIKE '%".$conn->real_escape_string($search_query)."%' OR s.student_id LIKE '%".$conn->real_escape_string($search_query)."%')";
    $q .= " ORDER BY s.submitted_at DESC";
    
    $r = $conn->query($q);
    if ($r) while ($row = $r->fetch_assoc()) $submissions_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Check Lab Manuals - Faculty</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f1f5f9; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background-color: #113460; color: #ffffff; display: flex; flex-direction: column; padding: 25px 0; box-shadow: 4px 0 10px rgba(0,0,0,0.1); z-index: 10; }
        .sidebar-logo-container { text-align: center; margin-bottom: 20px; }
        .logo-wrapper { width: 90px; height: 90px; background: #ffffff; border-radius: 50%; margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 3px solid rgba(255,255,255,0.2); }
        .sidebar-logo { width: 105%; height: auto; }
        .sidebar-title h2 { font-size: 19px; font-weight: 600; letter-spacing: 0.5px; margin:0;}
        .sidebar-title p { font-size: 13px; color: #94a3b8; margin:2px 0 0 0;}
        .sidebar-divider { height: 1px; background: #1e4b85; margin: 15px 20px; }
        .nav-links { list-style: none; padding: 0; flex-grow: 1; margin-top: 10px; }
        .nav-links li { padding: 15px 25px; margin: 5px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 15px; transition: 0.3s; color: #cbd5e1; }
        .nav-links li:hover { background: #1e4b85; color: white; }
        .nav-links li.active { background: #2563eb; color: white; box-shadow: 0 4px 10px rgba(37,99,235,0.3); }
        .nav-links li i { font-size: 18px; width:20px; text-align:center;}
        .logout-btn { color: #fca5a5 !important; margin-top: auto; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.1) !important; color: #ef4444 !important; }
        
        .main { flex: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column;}
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header-left h2 { color: #0f172a; font-size: 26px; font-weight: 700; margin:0;}
        .header-left p { color: #64748b; font-size: 14px; margin: 5px 0 0 0; }
        .header-profile { display: flex; align-items: center; gap: 15px; background: #ffffff; padding: 8px 10px 8px 20px; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; text-decoration:none; cursor:pointer;}
        .profile-text { display: flex; flex-direction: column; text-align: right; }
        .welcome-text { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; margin:0;}
        .faculty-name { font-size: 15px; color: #0f172a; font-weight: 700; margin:0;}
        .profile-avatar { width: 42px; height: 42px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; border: 2px solid #2563eb; }

        .content-card { background: white; padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); }
        .filter-label { color: #64748b; font-size: 11px; margin-bottom: 6px; text-transform: uppercase; font-weight: 700; }
        .form-select, .form-control { padding: 9px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13.5px; font-weight:500; background:#f8fafc;}
        .btn-green { background: #059669; color: white; border: none; font-weight: 600; font-size: 14px; padding: 9px 18px; border-radius: 8px; box-shadow: 0 4px 10px rgba(5,150,105,0.2); }
        .btn-eval { border: 1px solid #2563eb; color: #2563eb; font-size: 12.5px; font-weight: 600; padding: 6px 14px; border-radius: 6px; background: transparent; text-decoration:none;}
        .btn-eval:hover { background: #2563eb; color: white; }

        .table-custom th { border-top: none; background: #f8fafc; color: #64748b; padding: 14px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .table-custom td { vertical-align: middle; padding: 16px 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; font-weight: 500;}
        .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #d1fae5; color: #059669; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <div class="logo-wrapper">
                <img src="../assets/images/KDP-Logo.png" class="sidebar-logo" onerror="this.src='../assets/images/college-logo.png'">
            </div>
            <div class="sidebar-title">
                <h2>K.D. Polytechnic</h2>
                <p>Faculty Portal</p>
            </div>
        </div>
        <div class="sidebar-divider"></div>
        <ul class="nav-links">
            <li onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='labmanual_list.php'"><i class="fas fa-book"></i> Lab Manuals</li>
            <li onclick="window.location.href='reports.php'"><i class="fas fa-file-alt"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
            <li class="logout-btn" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>    
        </ul>
    </div>

    <div class="main">
        <div class="header">
            <div class="header-left">
                <h2>Check Lab Manuals</h2>
                <p>Use GTU Standard Assessment Rubrics for Grading.</p>
            </div>
            <div class="d-flex gap-3 align-items-center">
                <div class="d-flex gap-2">
                    <?php if (!empty($submissions_data)): ?>
                        <form method="POST" style="margin: 0;">
                            <input type="hidden" name="export_branch" value="<?php echo htmlspecialchars($selected_branch); ?>">
                            <input type="hidden" name="export_sem" value="<?php echo htmlspecialchars($selected_sem); ?>">
                            <input type="hidden" name="export_subject" value="<?php echo htmlspecialchars($selected_subject); ?>">
                            <button type="submit" name="export_csv" class="btn btn-light fw-bold text-dark border shadow-sm" style="font-size: 14px; padding: 9px 18px; border-radius: 8px;"><i class="fa-solid fa-file-csv"></i> Export</button>
                        </form>
                    <?php endif; ?>
                    <?php if (!empty($selected_subject)): ?>
                        <form method="POST" style="margin: 0;" onsubmit="return confirm('Approve all pending for this Subject in this Branch/Sem?');">
                            <input type="hidden" name="action_type" value="bulk_approve">
                            <input type="hidden" name="bulk_branch" value="<?php echo htmlspecialchars($selected_branch); ?>">
                            <input type="hidden" name="bulk_sem" value="<?php echo htmlspecialchars($selected_sem); ?>">
                            <input type="hidden" name="bulk_subject" value="<?php echo htmlspecialchars($selected_subject); ?>">
                            <button type="submit" class="btn-green"><i class="fa-solid fa-wand-magic-sparkles"></i> Bulk Approve</button>
                        </form>
                    <?php endif; ?>
                </div>
                <a href="profile.php" class="header-profile">
                    <div class="profile-text">
                        <span class="welcome-text">WELCOME BACK,</span>
                        <span class="faculty-name"><?php echo $faculty_name; ?></span>
                    </div>
                    <div class="profile-avatar"><?php echo strtoupper(substr($faculty_name, 0, 1)); ?>.</div>
                </a>
            </div>
        </div>

        <div class="content-card">
            <form method="GET" id="filterForm" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="filter-label">BRANCH</label>
                    <select name="branch" class="form-select w-100" onchange="document.getElementById('filterForm').submit();">
                        <?php foreach($available_branches as $b) { ?>
                            <option value="<?php echo $b; ?>" <?php if($selected_branch == $b) echo 'selected'; ?>><?php echo $b; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="filter-label">SEMESTER</label>
                    <select name="sem" class="form-select w-100" onchange="document.getElementById('filterForm').submit();">
                        <?php foreach($available_semesters as $s) { ?>
                            <option value="<?php echo $s; ?>" <?php if($selected_sem == $s) echo 'selected'; ?>><?php echo $s; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="filter-label">SUBJECT</label>
                    <select name="subject" class="form-select w-100" onchange="document.getElementById('filterForm').submit();">
                        <?php if(empty($available_subjects)) echo "<option value=''>No Subjects Found</option>"; ?>
                        <?php foreach($available_subjects as $sub) { ?>
                            <option value="<?php echo htmlspecialchars($sub); ?>" <?php if($selected_subject == $sub) echo 'selected'; ?>><?php echo htmlspecialchars($sub); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="filter-label">STATUS</label>
                    <select name="status_filter" class="form-select w-100" onchange="document.getElementById('filterForm').submit();">
                        <option value="All" <?php if($status_filter == 'All') echo 'selected'; ?>>All</option>
                        <option value="Pending" <?php if($status_filter == 'Pending') echo 'selected'; ?>>Pending</option>
                        <option value="Approved" <?php if($status_filter == 'Approved') echo 'selected'; ?>>Approved</option>
                        <option value="Rejected" <?php if($status_filter == 'Rejected') echo 'selected'; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="filter-label">SEARCH</label>
                    <input type="text" name="search" class="form-control w-100" placeholder="Name or Enrollment..." value="<?php echo htmlspecialchars($search_query); ?>" onchange="document.getElementById('filterForm').submit();">
                </div>
            </form>
        </div>

        <div class="content-card flex-grow-1 pt-2">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th style="width:15%">ENROLLMENT</th>
                        <th style="width:25%">STUDENT NAME</th>
                        <th style="width:15%; text-align:center;">FILE</th>
                        <th style="width:15%; text-align:center;">STATUS</th>
                        <th style="width:10%; text-align:center;">MARKS</th>
                        <th style="width:20%; text-align:right;">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions_data)) { ?>
                        <tr><td colspan="6" style="text-align:center; padding:50px; color:#94a3b8;"><i class="fa-solid fa-folder-open" style="font-size:30px; display:block; margin-bottom:10px;"></i> No submissions found for this Branch/Sem.</td></tr>
                    <?php } else { foreach ($submissions_data as $row) { 
                        $st = strtolower($row['status']);
                        $r_json = htmlspecialchars(json_encode($row['rubric_data'] ?? ''));
                        $rmk = htmlspecialchars($row['remark'] ?? '');
                    ?>
                        <tr>
                            <td style="color:#64748b;"><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['student_name']); ?></td>
                            <td class="text-center">
                                <?php if(!empty($row['file_path'])) { ?>
                                    <a href="../uploads/<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" style="color:#2563eb; font-weight:600; text-decoration:none;"><i class="fa-solid fa-file-pdf"></i> View File</a>
                                <?php } else { echo "<span class='text-muted'>No File</span>"; } ?>
                            </td>
                            <td class="text-center"><span class="badge badge-status status-<?php echo $st; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td class="text-center"><strong><?php echo ($row['status'] == 'Pending') ? '--' : ($row['marks'] ?? '--'); ?></strong></td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn-eval" onclick="openGradingModal('<?php echo $row['id']; ?>', '<?php echo addslashes($row['student_name']); ?>', '<?php echo $r_json; ?>', '<?php echo addslashes($rmk); ?>')">Evaluate</button>
                                    <form method="POST" style="margin:0;"><input type="hidden" name="action_type" value="single_grade"><input type="hidden" name="submission_id" value="<?php echo $row['id']; ?>"><button type="submit" name="action" value="Reset" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-rotate-left"></i></button></form>
                                </div>
                            </td>
                        </tr>
                    <?php } } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- GRADING MODAL SAME AS BEFORE -->
    <div class="modal fade" id="gradingModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST">
                    <input type="hidden" name="action_type" value="rubric_grade">
                    <input type="hidden" name="submission_id" id="modal_sub_id">
                    <div class="modal-header bg-light border-0 pb-3">
                        <h5 class="modal-title fw-bold text-dark">Grade Submission</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3"><span class="text-muted fw-bold" style="font-size:12px;">STUDENT:</span> <span id="modal_student_name" class="fw-bold text-dark ms-2" style="font-size:16px;"></span></div>
                        <table class="table table-bordered align-middle">
                            <thead class="table-light text-center"><tr><th style="font-size:12px;">CRITERIA</th><th style="font-size:12px;">RUBRICS SCALE</th><th style="font-size:12px;">MARKS OBTAINED</th></tr></thead>
                            <tbody>
                                <tr><td class="fw-bold text-center">Regularity</td><td style="font-size:11.5px; color:#64748b;">Low: (1-2) | Medium: (3-4) | High: (5)</td><td class="text-center"><input type="number" name="mark_reg" id="mark_reg" class="form-control d-inline-block text-center fw-bold" style="width:70px;" min="0" max="5" value="0" oninput="calTotal()" required></td></tr>
                                <tr><td class="fw-bold text-center">Understanding</td><td style="font-size:11.5px; color:#64748b;">Low: (1-3) | Medium: (3-4) | High: (5)</td><td class="text-center"><input type="number" name="mark_und" id="mark_und" class="form-control d-inline-block text-center fw-bold" style="width:70px;" min="0" max="5" value="0" oninput="calTotal()" required></td></tr>
                                <tr><td class="fw-bold text-center">Observation</td><td style="font-size:11.5px; color:#64748b;">Low: (1-2) | Medium: (3-4) | High: (5)</td><td class="text-center"><input type="number" name="mark_obs" id="mark_obs" class="form-control d-inline-block text-center fw-bold" style="width:70px;" min="0" max="5" value="0" oninput="calTotal()" required></td></tr>
                                <tr><td class="fw-bold text-center">Viva Test</td><td style="font-size:11.5px; color:#64748b;">Low: (1-2) | Medium: (3-4) | High: (5)</td><td class="text-center"><input type="number" name="mark_viva" id="mark_viva" class="form-control d-inline-block text-center fw-bold" style="width:70px;" min="0" max="5" value="0" oninput="calTotal()" required></td></tr>
                            </tbody>
                        </table>
                        <div class="row align-items-center">
                            <div class="col-md-4"><div class="bg-light p-3 rounded text-center fs-5 fw-bold border">Total: <span id="tot" class="text-primary fs-3">0</span> / 20</div></div>
                            <div class="col-md-8"><label class="form-label fw-bold text-muted" style="font-size:12px;">FEEDBACK</label><input type="text" name="remark" id="modal_remark" class="form-control" placeholder="Enter remarks..."></div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0"><button type="submit" name="action" value="Reject" class="btn btn-outline-danger fw-bold">Reject</button><button type="submit" name="action" value="Approve" class="btn btn-success fw-bold">Approve & Save</button></div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function calTotal(){
            let r=parseInt(document.getElementById('mark_reg').value)||0, u=parseInt(document.getElementById('mark_und').value)||0, o=parseInt(document.getElementById('mark_obs').value)||0, v=parseInt(document.getElementById('mark_viva').value)||0;
            if(r>5) r=5; if(u>5) u=5; if(o>5) o=5; if(v>5) v=5;
            document.getElementById('mark_reg').value=r; document.getElementById('mark_und').value=u; document.getElementById('mark_obs').value=o; document.getElementById('mark_viva').value=v;
            document.getElementById('tot').innerText = r+u+o+v;
        }
        function openGradingModal(id, nm, rub, rmk) {
            document.getElementById('modal_sub_id').value=id; document.getElementById('modal_student_name').innerText=nm; document.getElementById('modal_remark').value=rmk;
            let rd={reg:0,und:0,obs:0,viva:0};
            if(rub && rub!=='null' && rub!=='""'){ try{let clean=rub.replace(/^"|"$/g,''); let t=document.createElement("textarea"); t.innerHTML=clean; rd=JSON.parse(t.value);}catch(e){} }
            document.getElementById('mark_reg').value=rd.reg; document.getElementById('mark_und').value=rd.und; document.getElementById('mark_obs').value=rd.obs; document.getElementById('mark_viva').value=rd.viva;
            calTotal(); new bootstrap.Modal(document.getElementById('gradingModal')).show();
        }
    </script>
</body>
</html>