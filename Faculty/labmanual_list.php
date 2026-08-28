<?php
session_start();
include '../db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}
$faculty_id = $_SESSION['user_id'];
$faculty_name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Faculty';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type']) && $_POST['action_type'] == 'single_grade' && $_POST['action'] == 'Reset') {
        $sub_id = intval($_POST['submission_id']);
        $conn->query("UPDATE student_submissions SET status='Pending', marks=0, feedback=NULL WHERE submission_id=$sub_id");
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
        
        $conn->query("UPDATE student_submissions SET status='$status', marks=$total, feedback='$remark' WHERE submission_id=$sub_id");
    }
    elseif (isset($_POST['action_type']) && $_POST['action_type'] == 'bulk_approve') {
        $b_sub = $conn->real_escape_string($_POST['bulk_subject']);
        $conn->query("UPDATE student_submissions SET status='Approved', marks=20, feedback='Auto Approved by Faculty' WHERE subject_name='$b_sub' AND status='Pending'");
    }
    elseif (isset($_POST['export_csv'])) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="Marks_Report.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Student Name', 'Enrollment No.', 'Subject', 'Practical No', 'Status', 'Marks', 'Feedback']);
        
        $e_sub = $conn->real_escape_string($_POST['export_subject']);
        $q = "SELECT s.*, u.name as student_name, u.email as student_enrollment FROM student_submissions s JOIN users u ON s.student_id = u.user_id WHERE s.subject_name='$e_sub' ORDER BY u.email ASC";
        $res = $conn->query($q);
        
        if($res) {
            while($row = $res->fetch_assoc()) {
                fputcsv($out, [$row['student_name'], $row['student_enrollment'], $row['subject_name'], $row['practical_no'], $row['status'], $row['marks'], $row['feedback']]);
            }
        }
        fclose($out);
        exit();
    }
}

$available_branches = [];
$branch_res = $conn->query("SELECT DISTINCT department FROM subjects WHERE department IS NOT NULL AND department != ''");
if($branch_res && $branch_res->num_rows > 0) {
    while($r = $branch_res->fetch_assoc()) { $available_branches[] = $r['department']; }
}
if(empty($available_branches)) $available_branches = ['Computer Engineering'];
$selected_branch = isset($_GET['branch']) ? $_GET['branch'] : $available_branches[0];

$available_semesters = ['Semester 1', 'Semester 2', 'Semester 3', 'Semester 4', 'Semester 5', 'Semester 6'];
$selected_sem = isset($_GET['sem']) ? $_GET['sem'] : 'Semester 1';
$sem_number = (int) str_replace('Semester ', '', $selected_sem);

$available_subjects = [];
$safe_br = $conn->real_escape_string($selected_branch);
$fac_name_safe = $conn->real_escape_string($faculty_name);

$sub_query = "SELECT subject_name FROM subjects WHERE faculty_name LIKE '%$fac_name_safe%' AND department = '$safe_br' AND semester = '$sem_number'";
$sub_res = $conn->query($sub_query);
if ($sub_res && $sub_res->num_rows > 0) {
    while($r = $sub_res->fetch_assoc()) { $available_subjects[] = $r['subject_name']; }
}
$selected_subject = isset($_GET['subject']) ? $_GET['subject'] : (!empty($available_subjects) ? $available_subjects[0] : '');
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'All';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$submissions_data = [];
if (!empty($selected_subject)) {
    $safe_sub = $conn->real_escape_string($selected_subject);
    $q = "SELECT s.*, u.name as student_name, u.email as student_enrollment FROM student_submissions s JOIN users u ON s.student_id = u.user_id WHERE s.subject_name = '$safe_sub'";
    
    if ($status_filter !== 'All') { $q .= " AND s.status = '" . $conn->real_escape_string($status_filter) . "'"; }
    if (!empty($search_query)) {
        $safe_search = $conn->real_escape_string($search_query);
        $q .= " AND (u.name LIKE '%$safe_search%' OR u.email LIKE '%$safe_search%')";
    }
    $q .= " ORDER BY s.submitted_at DESC";
    
    $res = $conn->query($q);
    if ($res) { while($row = $res->fetch_assoc()) { $submissions_data[] = $row; } }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Evaluate Manuals - Faculty Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ================= UNIFIED SIDEBAR & BODY CSS ================= */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f1f5f9; display: flex; height: 100vh; overflow: hidden; }
        
        .sidebar { width: 260px; min-width: 260px; background-color: #113460; color: #ffffff; display: flex; flex-direction: column; padding: 25px 0; box-shadow: 4px 0 10px rgba(0,0,0,0.1); z-index: 10; flex-shrink: 0;}
        .sidebar-logo-container { text-align: center; margin-bottom: 20px; }
        .logo-wrapper { width: 90px; height: 90px; background: #ffffff; border-radius: 50%; margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; overflow: hidden; box-shadow: 0 0 15px rgba(255,255,255,0.15); border: 3px solid rgba(255,255,255,0.2); }
        .sidebar-logo { width: 105%; height: auto; }
        .sidebar-title h2 { font-size: 19px; font-weight: 600; letter-spacing: 0.5px; margin: 0; }
        .sidebar-title p { font-size: 13px; color: #94a3b8; margin: 2px 0 0 0;}
        .sidebar-divider { height: 1px; background: #1e4b85; margin: 15px 20px; }
        
        .nav-links { list-style: none; padding: 0; flex-grow: 1; margin: 10px 0 0 0; }
        .nav-links li { padding: 15px 25px; margin: 5px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 15px; transition: 0.3s; color: #cbd5e1; }
        .nav-links li:hover { background: #1e4b85; color: white; }
        .nav-links li.active { background: #2563eb; color: white; box-shadow: 0 4px 10px rgba(37,99,235,0.3); }
        .nav-links li i { font-size: 18px; width: 20px; text-align: center; }
        .logout-btn { color: #fca5a5 !important; margin-top: auto; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.1) !important; color: #ef4444 !important; }
        
        .main { flex: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column; }
        /* =============================================================== */
        
        .content-card { background: white; padding: 20px 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid #e2e8f0;}
        .table-custom th { background: #f8fafc; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; padding: 14px; border-bottom: 2px solid #e2e8f0;}
        .table-custom td { padding: 14px; vertical-align: middle; font-size: 14px; border-bottom: 1px solid #f1f5f9; }
        
        .badge-pending { background: #fef3c7; color: #d97706; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 11px;}
        .badge-approved { background: #d1fae5; color: #059669; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 11px;}
        .badge-rejected { background: #fee2e2; color: #dc2626; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 11px;}
        .btn-eval { background: rgba(37,99,235,0.1); color: #2563eb; border: 1px solid #2563eb; border-radius: 6px; font-weight: 600; padding: 4px 12px; transition: 0.2s;}
        .btn-eval:hover { background: #2563eb; color: white; }
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
            <li class="active" onclick="window.location.href='labmanual_list.php'"><i class="fas fa-check-circle"></i> Review & Evaluate</li>
            <li onclick="window.location.href='reports.php'"><i class="fas fa-file-alt"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
            <li class="logout-btn" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>    
        </ul>
    </div>

    <div class="main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Evaluate Practical Submissions</h3>
                <p class="text-muted small mb-0">Grade student lab manuals and generate rubrics.</p>
            </div>
            
            <div class="d-flex gap-2">
                <form method="POST">
                    <input type="hidden" name="action_type" value="bulk_approve">
                    <input type="hidden" name="bulk_subject" value="<?php echo htmlspecialchars($selected_subject); ?>">
                    <button type="submit" class="btn btn-outline-success fw-bold" onclick="return confirm('Approve ALL pending submissions for this subject?');"><i class="fas fa-check-double me-1"></i> Bulk Approve</button>
                </form>
                <form method="POST">
                    <input type="hidden" name="export_csv" value="1">
                    <input type="hidden" name="export_subject" value="<?php echo htmlspecialchars($selected_subject); ?>">
                    <button type="submit" class="btn btn-outline-secondary fw-bold"><i class="fas fa-file-csv me-1"></i> Export Report</button>
                </form>
            </div>
        </div>

        <div class="content-card">
            <form method="GET" id="filterForm" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label text-muted small fw-bold mb-1">Branch</label>
                    <select name="branch" class="form-select" onchange="this.form.submit()">
                        <?php foreach($available_branches as $b) { echo "<option value='$b' ".($selected_branch==$b?'selected':'').">$b</option>"; } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted small fw-bold mb-1">Semester</label>
                    <select name="sem" class="form-select" onchange="this.form.submit()">
                        <?php foreach($available_semesters as $s) { echo "<option value='$s' ".($selected_sem==$s?'selected':'').">$s</option>"; } ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-bold mb-1">Subject</label>
                    <select name="subject" class="form-select" onchange="this.form.submit()">
                        <?php foreach($available_subjects as $sub) { echo "<option value='".htmlspecialchars($sub)."' ".($selected_subject==$sub?'selected':'').">".htmlspecialchars($sub)."</option>"; } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-muted small fw-bold mb-1">Status</label>
                    <select name="status_filter" class="form-select" onchange="this.form.submit()">
                        <option value="All" <?php if($status_filter=='All') echo 'selected';?>>All</option>
                        <option value="Pending" <?php if($status_filter=='Pending') echo 'selected';?>>Pending</option>
                        <option value="Approved" <?php if($status_filter=='Approved') echo 'selected';?>>Approved</option>
                        <option value="Rejected" <?php if($status_filter=='Rejected') echo 'selected';?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small fw-bold mb-1">Search Student</label>
                    <input type="text" name="search" class="form-control" placeholder="Name or Enrollment" value="<?php echo htmlspecialchars($search_query); ?>" onchange="this.form.submit()">
                </div>
            </form>
        </div>

        <div class="content-card p-0">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Student Details</th>
                            <th>Practical</th>
                            <th>Status</th>
                            <th>Marks</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($submissions_data)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i><br>
                                    No submissions found for the selected criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($submissions_data as $row): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['student_enrollment']); ?></small>
                                </td>
                                <td><span class="text-primary fw-medium"><?php echo htmlspecialchars($row['practical_no']); ?></span></td>
                                <td>
                                    <?php if($row['status'] == 'Approved'): ?> <span class="badge-approved">Approved</span>
                                    <?php elseif($row['status'] == 'Rejected'): ?> <span class="badge-rejected">Rejected</span>
                                    <?php else: ?> <span class="badge-pending">Pending Review</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo ($row['status'] == 'Pending') ? '--' : $row['marks'] . ' / 20'; ?></strong></td>
                                <td class="text-end">
                                    <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-dark me-1" title="View PDF"><i class="fas fa-file-pdf"></i> View</a>
                                    
                                    <?php if($row['status'] == 'Pending'): ?>
                                        <button type="button" class="btn-eval" onclick="openModal(<?php echo $row['submission_id']; ?>, '<?php echo addslashes($row['student_name']); ?>', '<?php echo addslashes($row['practical_no']); ?>')">
                                            <i class="fas fa-edit me-1"></i> Evaluate
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action_type" value="single_grade">
                                            <input type="hidden" name="action" value="Reset">
                                            <input type="hidden" name="submission_id" value="<?php echo $row['submission_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to reset this evaluation?');" title="Reset Marks"><i class="fas fa-undo"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="gradeModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; border-radius:12px; padding:25px; width:100%; max-width:450px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                <h5 class="fw-bold m-0"><i class="fas fa-clipboard-check text-primary me-2"></i> Grade Submission</h5>
                <button type="button" class="btn-close" onclick="closeModal()"></button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action_type" value="rubric_grade">
                <input type="hidden" name="submission_id" id="modal_sub_id">
                
                <div class="mb-3 bg-light p-2 rounded">
                    <small class="d-block text-muted">Student Name</small>
                    <strong id="modal_student_name" class="text-dark"></strong><br>
                    <small class="d-block text-muted mt-2">Practical Details</small>
                    <strong id="modal_practical_no" class="text-primary"></strong>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold text-muted mb-1">Regularity (0-5)</label>
                        <input type="number" name="mark_reg" class="form-control" min="0" max="5" value="5" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold text-muted mb-1">Understanding (0-5)</label>
                        <input type="number" name="mark_und" class="form-control" min="0" max="5" value="5" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold text-muted mb-1">Observation (0-5)</label>
                        <input type="number" name="mark_obs" class="form-control" min="0" max="5" value="5" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold text-muted mb-1">Viva (0-5)</label>
                        <input type="number" name="mark_viva" class="form-control" min="0" max="5" value="5" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted mb-1">Faculty Feedback (Optional)</label>
                    <input type="text" name="remark" class="form-control" placeholder="Good work / Needs improvement...">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light border fw-bold" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="action" value="Reject" class="btn btn-outline-danger fw-bold">Reject</button>
                    <button type="submit" name="action" value="Approve" class="btn btn-success fw-bold px-4">Approve File</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(subId, studentName, pracNo) {
            document.getElementById('modal_sub_id').value = subId;
            document.getElementById('modal_student_name').innerText = studentName;
            document.getElementById('modal_practical_no').innerText = pracNo;
            document.getElementById('gradeModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('gradeModal').style.display = 'none';
        }
    </script>
</body>
</html>