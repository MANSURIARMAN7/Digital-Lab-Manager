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

// 2. Handle Grading (Approve, Reject, or Reset with Remark)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type']) && $_POST['action_type'] === 'single_grade') {
    $sub_id = (int)$_POST['submission_id'];
    $action = $_POST['action']; 
    
    if ($action === 'Reset') {
        // Undo karke wapas pending karna hai
        $update_sql = "UPDATE submissions SET status='Pending', marks=0, remark='' WHERE id=$sub_id";
        if ($conn->query($update_sql)) {
            $msg = "<div class='alert alert-warning alert-dismissible fade show shadow-sm'><i class='fa-solid fa-rotate-left me-2'></i> Submission reset to <strong>Pending</strong>! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } else {
        // Approve ya Reject karna hai
        $marks = isset($_POST['marks']) ? (int)$_POST['marks'] : 0;
        $remark = $conn->real_escape_string(trim($_POST['remark']));
        
        $status = ($action === 'Approve') ? 'Approved' : 'Rejected';
        if ($status === 'Rejected') $marks = 0;

        $update_sql = "UPDATE submissions SET status='$status', marks='$marks', remark='$remark' WHERE id=$sub_id";
        if ($conn->query($update_sql)) {
            $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm'><i class='fa-solid fa-circle-check me-2'></i> Status updated to <strong>$status</strong>! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        } else {
            $msg = "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    }
}

// 3. Handle Bulk Approval (Sabhi Pending bacchon ko 10/10 de kar approve karna)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_type']) && $_POST['action_type'] === 'bulk_approve') {
    $bulk_sub_name = $conn->real_escape_string($_POST['bulk_subject']);
    if (!empty($bulk_sub_name)) {
        $bulk_sql = "UPDATE submissions SET status='Approved', marks=10, remark='Auto Approved' WHERE subject='$bulk_sub_name' AND status='Pending'";
        if ($conn->query($bulk_sql)) {
            $affected = $conn->affected_rows;
            $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm'><i class='fa-solid fa-wand-magic-sparkles me-2'></i> Successfully approved <strong>$affected</strong> pending submissions with 10/10 marks! <button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
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

    $query = "SELECT s.*, u.name as student_name 
              FROM submissions s 
              LEFT JOIN users u ON s.enrollment = u.user_id 
              $sql_where 
              ORDER BY s.id DESC";
              
    $res = $conn->query($query);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $submissions_data[] = $r;
        }
    }
}

// 7. 🔥 CSV/EXCEL EXPORT LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['export_csv'])) {
    $filename = "Marks_Report_" . str_replace(' ', '_', $safe_sub) . "_" . date('Y-m-d') . ".csv";
    
    // Set headers to force download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write Column Headers
    fputcsv($output, array('Enrollment No.', 'Student Name', 'Subject', 'Status', 'Marks', 'Feedback/Remark'));
    
    // Write Data Rows
    foreach ($submissions_data as $row) {
        $student_name = $row['student_name'] ?: 'Student';
        fputcsv($output, array(
            $row['enrollment'],
            $student_name,
            $row['subject'],
            $row['status'],
            ($row['status'] == 'Pending') ? '-' : $row['marks'],
            $row['remark']
        ));
    }
    
    fclose($output);
    exit(); // Stop HTML rendering since we downloaded a file
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Lab Manuals - Faculty Portal</title>
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
        .nav-links li i { font-size: 18px; width: 20px; text-align: center; }
        .logout-btn { color: #fca5a5 !important; margin-top: auto; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.1) !important; color: #ef4444 !important; }

        .main { flex: 1; padding: 30px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header-left h2 { color: #0f172a; font-size: 26px; font-weight: 700; margin: 0; }
        .header-left p { color: #64748b; font-size: 14px; margin-top: 5px; }
        
        .header-actions { display: flex; gap: 10px; align-items: center; }

        .filter-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .filter-row { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .filter-box { display: flex; flex-direction: column; flex: 1; min-width: 180px; }
        .filter-box label { color: #64748b; font-size: 11px; margin-bottom: 6px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
        .filter-box select, .filter-box input { padding: 9px 12px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; color: #113460; font-weight: 600; font-size: 14px; }
        .filter-box select:focus, .filter-box input:focus { border-color: #2563eb; background: #fff; }

        .table-card { background: white; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); overflow: hidden; }
        .table-custom { margin: 0; width: 100%; border-collapse: collapse; }
        .table-custom th { background: #f8fafc; color: #475569; padding: 14px 16px; font-size: 12px; text-transform: uppercase; font-weight: 700; border-bottom: 2px solid #e2e8f0; }
        .table-custom td { padding: 14px 16px; vertical-align: middle; border-bottom: 1px solid #e2e8f0; color: #1e293b; font-size: 14px; }
        
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; text-transform: uppercase; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #d1fae5; color: #059669; }
        .status-rejected { background: #fee2e2; color: #dc2626; }

        .btn-pdf { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; }
        .btn-pdf:hover { background: #2563eb; color: white; }

        .marks-input { width: 65px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; font-weight: bold; color: #0f172a; outline: none; font-size: 13px; }
        .remark-input { width: 140px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; color: #475569; font-size: 12px; outline: none; }
        .remark-input:focus, .marks-input:focus { border-color: #2563eb; }

        .action-form { display: flex; align-items: center; gap: 6px; }
        .btn-action { padding: 6px 10px; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-approve { background: #10b981; color: white; }
        .btn-approve:hover { background: #059669; }
        .btn-reject { background: #ef4444; color: white; }
        .btn-reject:hover { background: #dc2626; }
        .btn-reset { background: #f59e0b; color: white; }
        .btn-reset:hover { background: #d97706; }

        .btn-bulk { background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-bulk:hover { background: #1d4ed8; }
        .btn-export { background: #10b981; }
        .btn-export:hover { background: #059669; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
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
                <p>Review student submissions, give feedback & assign marks.</p>
            </div>
            
            <div class="header-actions">
                <!-- 🔥 EXPORT TO EXCEL BUTTON -->
                <?php if (!empty($submissions_data)): ?>
                    <form method="POST" style="margin: 0;">
                        <button type="submit" name="export_csv" value="1" class="btn-bulk btn-export"><i class="fa-solid fa-file-csv"></i> Download CSV</button>
                    </form>
                <?php endif; ?>

                <!-- BULK APPROVE BUTTON -->
                <?php if (!empty($selected_subject)): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to approve ALL pending submissions for this subject with 10/10 marks?');" style="margin: 0;">
                        <input type="hidden" name="action_type" value="bulk_approve">
                        <input type="hidden" name="bulk_subject" value="<?php echo htmlspecialchars($selected_subject); ?>">
                        <button type="submit" class="btn-bulk"><i class="fa-solid fa-wand-magic-sparkles"></i> Approve All Pending</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <?php echo $msg; ?>

        <!-- ADVANCED FILTERS -->
        <div class="filter-card">
            <form method="GET" id="filterForm">
                <div class="filter-row">
                    <div class="filter-box">
                        <label><i class="fa-solid fa-layer-group me-1"></i> Semester</label>
                        <select name="sem" onchange="document.getElementById('filterForm').submit();">
                            <?php foreach($faculty_semesters as $sem) { ?>
                                <option value="<?php echo $sem; ?>" <?php if($selected_sem == $sem) echo 'selected'; ?>><?php echo $sem; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    
                    <div class="filter-box">
                        <label><i class="fa-solid fa-book me-1"></i> Subject</label>
                        <select name="subject" onchange="document.getElementById('filterForm').submit();">
                            <?php if(empty($available_subjects)) { ?>
                                <option value="">No Subjects</option>
                            <?php } else { 
                                foreach($available_subjects as $sub) { ?>
                                <option value="<?php echo htmlspecialchars($sub); ?>" <?php if($selected_subject == $sub) echo 'selected'; ?>><?php echo htmlspecialchars($sub); ?></option>
                            <?php } } ?>
                        </select>
                    </div>

                    <div class="filter-box">
                        <label><i class="fa-solid fa-filter me-1"></i> Filter Status</label>
                        <select name="status_filter" onchange="document.getElementById('filterForm').submit();">
                            <option value="All" <?php if($status_filter == 'All') echo 'selected'; ?>>All Submissions</option>
                            <option value="Pending" <?php if($status_filter == 'Pending') echo 'selected'; ?>>Pending</option>
                            <option value="Approved" <?php if($status_filter == 'Approved') echo 'selected'; ?>>Approved</option>
                            <option value="Rejected" <?php if($status_filter == 'Rejected') echo 'selected'; ?>>Rejected</option>
                        </select>
                    </div>

                    <div class="filter-box">
                        <label><i class="fa-solid fa-magnifying-glass me-1"></i> Search Student</label>
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
                        <th>Remark / Feedback</th>
                        <th style="min-width: 250px;">Action / Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($submissions_data)) { ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted"><i class="fa-solid fa-inbox fs-4 mb-2 d-block"></i> No submissions found matching your filters.</td></tr>
                    <?php } else { 
                        foreach ($submissions_data as $row) { 
                            $status_class = "status-" . strtolower($row['status']);
                            $student_name = $row['student_name'] ?: "Student";
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['enrollment']); ?></strong></td>
                        <td><?php echo htmlspecialchars($student_name); ?></td>
                        <td>
                            <a href="../uploads/<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn-pdf">
                                <i class="fa-regular fa-file-pdf"></i> View File
                            </a>
                        </td>
                        <td><span class="badge-status <?php echo $status_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        
                        <!-- REMARK DISPLAY OR INPUT -->
                        <td>
                            <small class="text-muted d-block"><?php echo !empty($row['remark']) ? htmlspecialchars($row['remark']) : '-'; ?></small>
                        </td>

                        <!-- INLINE GRADING FORM -->
                        <td>
                            <form method="POST" class="action-form">
                                <input type="hidden" name="action_type" value="single_grade">
                                <input type="hidden" name="submission_id" value="<?php echo $row['id']; ?>">
                                
                                <input type="text" name="remark" class="remark-input" placeholder="Remark..." value="<?php echo htmlspecialchars($row['remark'] ?? ''); ?>">
                                <input type="number" name="marks" class="marks-input" min="0" max="10" placeholder="/10" value="<?php echo ($row['marks'] > 0) ? $row['marks'] : ''; ?>" required title="Marks out of 10">
                                
                                <button type="submit" name="action" value="Approve" class="btn-action btn-approve" title="Approve"><i class="fa-solid fa-check"></i></button>
                                <button type="submit" name="action" value="Reject" class="btn-action btn-reject" formnovalidate title="Reject"><i class="fa-solid fa-xmark"></i></button>
                                
                                <!-- 🔥 RESET BUTTON ADDED -->
                                <button type="submit" name="action" value="Reset" class="btn-action btn-reset" formnovalidate title="Reset to Pending"><i class="fa-solid fa-rotate-left"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php } } ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>