<?php
session_start();
include '../db.php';

// 1. SECURE LOGIN CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$faculty_name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Faculty';

// Initials for Profile Avatar
$name_parts = explode(' ', trim($faculty_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) { $initials .= strtoupper(substr(end($name_parts), 0, 1)); }

$msg = "";

// ==========================================
// 🎯 EVALUATION SUBMIT LOGIC (20 MARKS RUBRIC)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['evaluate'])) {
    $sub_id = (int)$_POST['submission_id'];
    $status = $conn->real_escape_string($_POST['status']);
    $feedback = $conn->real_escape_string($_POST['feedback']);
    
    // Rubric Marks (Max 5 each)
    $mark_reg = isset($_POST['mark_reg']) ? (int)$_POST['mark_reg'] : 0;
    $mark_und = isset($_POST['mark_und']) ? (int)$_POST['mark_und'] : 0;
    $mark_obs = isset($_POST['mark_obs']) ? (int)$_POST['mark_obs'] : 0;
    $mark_viva = isset($_POST['mark_viva']) ? (int)$_POST['mark_viva'] : 0;
    
    $total_marks = $mark_reg + $mark_und + $mark_obs + $mark_viva;

    $update_sql = "UPDATE student_submissions 
                   SET status='$status', feedback='$feedback', 
                       mark_reg='$mark_reg', mark_und='$mark_und', mark_obs='$mark_obs', mark_viva='$mark_viva', marks='$total_marks' 
                   WHERE submission_id='$sub_id'";
                   
    if ($conn->query($update_sql)) {
        $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px;'><i class='fas fa-check-circle me-2'></i> Submission Evaluated Successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i> Error saving evaluation.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// 🧠 FETCH FACULTY SPECIFIC SUBJECTS (MAPPING)
// ==========================================
$fac_name_safe = $conn->real_escape_string($faculty_name);
$assigned_subjects = [];
$sub_query = "SELECT DISTINCT department, semester, subject_name FROM subjects WHERE faculty_name LIKE '%$fac_name_safe%' ORDER BY semester ASC, subject_name ASC";
$sub_res = $conn->query($sub_query);

if ($sub_res && $sub_res->num_rows > 0) {
    while($r = $sub_res->fetch_assoc()) { 
        $key = $r['department'] . "|" . $r['semester'];
        $assigned_subjects[$key][] = $r['subject_name']; 
    }
}

// ==========================================
// 🔍 FILTERS & FETCH SUBMISSIONS
// ==========================================
$status_filter = isset($_GET['status_filter']) ? $conn->real_escape_string($_GET['status_filter']) : 'All';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$safe_search = $conn->real_escape_string($search_query);

$allowed_subs = [];
foreach($assigned_subjects as $subs) { foreach($subs as $s) { $allowed_subs[] = "'".$conn->real_escape_string($s)."'"; } }
$in_clause = empty($allowed_subs) ? "''" : implode(",", $allowed_subs);

$list_sql = "SELECT s.*, u.name as student_name, u.email as student_enrollment, lm.end_date 
             FROM student_submissions s 
             JOIN users u ON s.student_id = u.user_id 
             LEFT JOIN lab_manuals lm ON s.manual_id = lm.id
             WHERE s.subject_name IN ($in_clause)";

if ($status_filter !== 'All') { $list_sql .= " AND s.status = '$status_filter'"; }
if (!empty($search_query)) { $list_sql .= " AND (u.name LIKE '%$safe_search%' OR u.email LIKE '%$safe_search%' OR s.subject_name LIKE '%$safe_search%')"; }

$list_sql .= " ORDER BY s.submitted_at DESC";
$submissions = $conn->query($list_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review & Evaluate - KDP Faculty Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --sidebar-width: 270px; 
            --primary: #4338ca; 
            --primary-hover: #3730a3;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --shadow-float: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        /* 🔥 PREMIUM MICROSOFT UNIFIED SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 13px; color: #bfdbfe; margin-top: 5px; font-weight: 500; letter-spacing: 0.5px;} 
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        /* 🔥 MAIN CONTENT ANIMATION */
        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* 🌐 TOPBAR & PROFILE PILL */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 10px 22px 10px 28px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: var(--primary); font-weight: 800; text-transform: uppercase; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 17px; color: var(--text-main); font-weight: 900;}
        .profile-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.4);}

        /* 📦 CONTENT BOXES & FILTERS */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 25px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        
        .filter-panel { display: flex; gap: 15px; align-items: center; background: #ffffff; padding: 20px; border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 25px;}
        .filter-panel select, .filter-panel input.form-control { padding: 0 15px; height: 42px; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 600; font-size: 13.5px; transition: 0.2s;}
        .filter-panel select:focus, .filter-panel input.form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); background: #ffffff;}
        
        /* 📋 TABLE */
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); padding: 15px 10px; background: #f8fafc; border-bottom: 2px solid #e2e8f0;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; }
        
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;}
        .status-Pending { background: #fef3c7; color: #d97706; border: 1px solid #fde68a;}
        .status-Approved { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0;}
        .status-Rejected { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;}

        .btn-action { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 8px 15px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px;}
        .btn-action:hover { background: #2563eb; color: white; transform: translateY(-2px); }
        .btn-evaluate { background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; }
        .btn-evaluate:hover { box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); color: white;}

        /* MODALS STYLING */
        .rubric-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); z-index: 9999; align-items: center; justify-content: center; }
        .rubric-modal-overlay.active { display: flex; }
        .rubric-modal { background: white; border-radius: 16px; width: 600px; max-width: 95%; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: fadeUp 0.3s ease-out; max-height: 90vh; display: flex; flex-direction: column;}
        .modal-header-custom { padding: 18px 25px; background: linear-gradient(135deg, #1e3a8a, #4338ca); color: white; display: flex; justify-content: space-between; align-items: center;}
        .btn-close-custom { background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; transition: 0.2s;}
        .btn-close-custom:hover { background: rgba(255,255,255,0.4); }
        
        .rubric-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .rubric-box { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 15px; }
        .rubric-box label { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--primary); display: block; margin-bottom: 8px;}

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Faculty Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='manage_manuals.php'"><i class="fas fa-file-pdf"></i> Upload Manuals</li>
            <li class="active" onclick="window.location.href='labmanual_list.php'"><i class="fas fa-check-double"></i> Review & Evaluate</li>
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
                    <span class="profile-welcome">Welcome Back,</span>
                    <h4 class="profile-name">
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
            <h3 class="fw-bold text-dark mb-1" style="font-size: 26px;">Evaluate Submissions</h3>
            <p class="text-muted small fw-semibold mb-0">Review student work, assign marks based on the 20-point rubric, and provide feedback.</p>
        </div>

        <?php echo $msg; ?>

        <!-- FILTERS -->
        <div class="filter-panel shadow-sm">
            <form method="GET" class="d-flex w-100 gap-3 align-items-center">
                <div class="flex-grow-1">
                    <div class="d-flex border rounded-3 overflow-hidden" style="border-color: #cbd5e1 !important;">
                        <span class="bg-light px-3 d-flex align-items-center text-muted border-end"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control border-0" placeholder="Search by Student Name, Enrollment, or Subject..." value="<?php echo htmlspecialchars($search_query); ?>" style="border-radius: 0;">
                    </div>
                </div>
                <div style="min-width: 200px;">
                    <select name="status_filter" class="form-select" onchange="this.form.submit()">
                        <option value="All" <?php if($status_filter == 'All') echo 'selected'; ?>>All Submissions</option>
                        <option value="Pending" <?php if($status_filter == 'Pending') echo 'selected'; ?>>Pending Review</option>
                        <option value="Approved" <?php if($status_filter == 'Approved') echo 'selected'; ?>>Approved (Graded)</option>
                        <option value="Rejected" <?php if($status_filter == 'Rejected') echo 'selected'; ?>>Rejected (Needs Revision)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary fw-bold px-4" style="height: 42px; border-radius: 8px;">Filter</button>
            </form>
        </div>

        <!-- SUBMISSIONS TABLE -->
        <div class="content-box p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Student Info</th>
                            <th>Subject & Practical</th>
                            <th>Submission Timeline</th>
                            <th>Status & Score</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($submissions && $submissions->num_rows > 0): ?>
                            <?php while($row = $submissions->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark" style="font-size: 14.5px;"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                        <div class="text-muted small fw-bold mt-1"><i class="fas fa-id-card me-1"></i> <?php echo htmlspecialchars($row['student_enrollment']); ?></div>
                                    </td>
                                    <td>
                                        <div class="text-primary fw-bold" style="font-size: 13.5px;"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                                        <div class="text-muted small fw-bold mt-1">Prac: <?php echo htmlspecialchars($row['practical_no']); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size: 12px; color: var(--text-main);" class="fw-semibold">
                                            <i class="fas fa-calendar-check text-success me-1"></i> <?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?>
                                            
                                            <?php 
                                            // Late Submission Logic
                                            if(!empty($row['end_date']) && $row['end_date'] != '0000-00-00') {
                                                $deadline = strtotime($row['end_date'] . ' 23:59:59');
                                                $submitted = strtotime($row['submitted_at']);
                                                if($submitted > $deadline) {
                                                    $days_late = ceil(($submitted - $deadline) / 86400);
                                                    echo "<div class='mt-1 text-danger'><i class='fas fa-exclamation-triangle me-1'></i> Late by $days_late Days</div>";
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-modern status-<?php echo htmlspecialchars($row['status']); ?> mb-1 d-inline-block">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                        <?php if($row['status'] !== 'Pending'): ?>
                                            <div class="fw-bold text-dark mt-1" style="font-size: 13px;">Marks: <?php echo $row['marks']; ?> <span class="text-muted fw-normal">/ 20</span></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php 
                                            $raw_path = $row['file_path'];
                                            $pos = strpos($raw_path, 'uploads/');
                                            $safe_pdf_path = ($pos !== false) ? '../' . substr($raw_path, $pos) : '../' . $raw_path;
                                        ?>
                                        <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" target="_blank" class="btn-action me-1" title="View Document"><i class="fas fa-file-pdf"></i></a>
                                        
                                        <?php 
                                            // Pass all data to JS for evaluation modal
                                            $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); 
                                        ?>
                                        <button type="button" class="btn-action btn-evaluate" onclick='openEvaluateModal(<?php echo $js_data; ?>)'>
                                            <i class="fas fa-pen-nib"></i> Grade
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-check-circle mb-3" style="font-size: 40px; opacity: 0.3;"></i><br>
                                    <span class="fw-bold fs-6">No submissions found.</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- 🎯 MODAL: EVALUATION RUBRIC -->
    <div id="evalModal" class="rubric-modal-overlay">
        <div class="rubric-modal">
            <div class="modal-header-custom">
                <div>
                    <h5 class="mb-0 fw-bold"><i class="fas fa-clipboard-check me-2"></i> Evaluate Submission</h5>
                    <div class="small mt-1 opacity-75" id="eval_subtitle">Student Name - Subject</div>
                </div>
                <button type="button" class="btn-close-custom" onclick="closeEvalModal()"><i class="fas fa-times"></i></button>
            </div>
            
            <form action="" method="POST" style="overflow-y: auto; padding: 25px;">
                <input type="hidden" name="evaluate" value="1">
                <input type="hidden" name="submission_id" id="eval_sub_id">
                
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-star text-warning me-2"></i> 20-Marks Grading Rubric</h6>
                
                <div class="rubric-grid">
                    <div class="rubric-box">
                        <label>Regularity (Max 5)</label>
                        <input type="number" name="mark_reg" id="mark_reg" class="form-control" min="0" max="5" value="0" required onchange="calculateTotal()">
                    </div>
                    <div class="rubric-box">
                        <label>Understanding (Max 5)</label>
                        <input type="number" name="mark_und" id="mark_und" class="form-control" min="0" max="5" value="0" required onchange="calculateTotal()">
                    </div>
                    <div class="rubric-box">
                        <label>Observation & Logic (Max 5)</label>
                        <input type="number" name="mark_obs" id="mark_obs" class="form-control" min="0" max="5" value="0" required onchange="calculateTotal()">
                    </div>
                    <div class="rubric-box">
                        <label>Viva / Quiz (Max 5)</label>
                        <input type="number" name="mark_viva" id="mark_viva" class="form-control" min="0" max="5" value="0" required onchange="calculateTotal()">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded-3 border mb-4">
                    <span class="fw-bold text-uppercase" style="font-size:12px; color:var(--text-muted); letter-spacing:1px;">Total Score Calculated</span>
                    <h4 class="mb-0 fw-bold text-primary"><span id="total_display">0</span> <small class="text-muted" style="font-size:16px;">/ 20</small></h4>
                </div>

                <div class="mb-3">
                    <label class="form-label">Final Status</label>
                    <select name="status" id="eval_status" class="form-select fw-bold" required>
                        <option value="Approved" class="text-success">Approved (Pass)</option>
                        <option value="Rejected" class="text-danger">Rejected (Needs Revision)</option>
                        <option value="Pending" class="text-warning">Pending</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label">Faculty Feedback / Remarks</label>
                    <textarea name="feedback" id="eval_feedback" class="form-control" rows="3" placeholder="Provide specific feedback or reason for rejection..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 border-top pt-3">
                    <button type="button" class="btn btn-light fw-bold" onclick="closeEvalModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm"><i class="fas fa-save me-2"></i> Save Evaluation</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        function calculateTotal() {
            let r = parseInt(document.getElementById('mark_reg').value) || 0;
            let u = parseInt(document.getElementById('mark_und').value) || 0;
            let o = parseInt(document.getElementById('mark_obs').value) || 0;
            let v = parseInt(document.getElementById('mark_viva').value) || 0;
            document.getElementById('total_display').innerText = (r + u + o + v);
        }

        function openEvaluateModal(data) {
            document.getElementById('eval_sub_id').value = data.submission_id;
            document.getElementById('eval_subtitle').innerText = data.student_name + " | Prac: " + data.practical_no;
            
            document.getElementById('mark_reg').value = data.mark_reg || 0;
            document.getElementById('mark_und').value = data.mark_und || 0;
            document.getElementById('mark_obs').value = data.mark_obs || 0;
            document.getElementById('mark_viva').value = data.mark_viva || 0;
            
            document.getElementById('eval_status').value = data.status;
            document.getElementById('eval_feedback').value = data.feedback || '';
            
            calculateTotal();
            document.getElementById('evalModal').classList.add('active');
        }

        function closeEvalModal() {
            document.getElementById('evalModal').classList.remove('active');
        }
    </script>
</body>
</html>





