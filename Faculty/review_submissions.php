<?php
ob_start();
session_start();
include '../db.php';

// 1. Faculty Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$msg = "";

// Fetch Faculty Details
$fac_query = $conn->query("SELECT name, designation FROM users WHERE user_id = '$faculty_id'");
$fac_data = $fac_query->fetch_assoc();
$faculty_name = $fac_data['name'];
$designation = !empty($fac_data['designation']) ? $fac_data['designation'] : 'Faculty Member';

$safe_id = strtolower($conn->real_escape_string($faculty_id));
$safe_name = strtolower($conn->real_escape_string($faculty_name));

// ==========================================
// HANDLE RUBRICS EVALUATION & RESUBMISSION
// ==========================================
if (isset($_POST['save_evaluation'])) {
    $submission_id = (int)$_POST['submission_id'];
    $action_type = $_POST['action_type']; // 'grade' or 'resubmit'
    $feedback = $conn->real_escape_string(trim($_POST['feedback']));
    
    if ($action_type === 'resubmit') {
        $sql = "UPDATE student_submissions SET status='Resubmit', feedback='$feedback', total_marks=0 WHERE id=$submission_id";
        if ($conn->query($sql)) {
            $msg = "<div class='alert alert-warning shadow-sm'><i class='fas fa-undo me-2'></i> Student has been asked to resubmit the manual.</div>";
        }
    } else {
        $m1 = (int)$_POST['m_regularity'];
        $m2 = (int)$_POST['m_understanding'];
        $m3 = (int)$_POST['m_observation'];
        $m4 = (int)$_POST['m_viva'];
        $total = $m1 + $m2 + $m3 + $m4;
        
        $sql = "UPDATE student_submissions 
                SET regularity_marks=$m1, understanding_marks=$m2, observation_marks=$m3, viva_marks=$m4, total_marks=$total, status='Graded', feedback='$feedback' 
                WHERE id=$submission_id";
        if ($conn->query($sql)) {
            $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i> Evaluation saved successfully! Total marks: $total/20</div>";
        }
    }
}

// ==========================================
// 2-STEP FILTER LOGIC
// ==========================================
$selected_subject = $_GET['subject_code'] ?? '';
$selected_manual = $_GET['manual_id'] ?? '';

// Step 1: Fetch Assigned Subjects for Dropdown
$assigned_subjects = $conn->query("SELECT subject_code, subject_name FROM subjects WHERE (LOWER(faculty_name) LIKE '%$safe_id%' OR LOWER(faculty_name) LIKE '%$safe_name%') AND status='Active'");

// Step 2: Fetch Practicals ONLY IF a Subject is selected
$practicals = null;
if (!empty($selected_subject)) {
    $safe_sub = $conn->real_escape_string($selected_subject);
    $practicals = $conn->query("SELECT id, practical_no, title FROM lab_manuals WHERE faculty_id='$faculty_id' AND subject_code='$safe_sub' ORDER BY id ASC");
}

// Step 3: Fetch Student Submissions ONLY IF a Practical is selected
$submissions = null;
$manual_details = null;
if (!empty($selected_manual)) {
    $safe_manual = (int)$selected_manual;
    
    // Get deadline to check for LATE submissions
    $man_q = $conn->query("SELECT end_date FROM lab_manuals WHERE id='$safe_manual'");
    if($man_q && $man_q->num_rows > 0) {
        $manual_details = $man_q->fetch_assoc();
    }
    
    // Fetch submissions
    $submissions = $conn->query("
        SELECT ss.*, u.name as student_name, u.user_id as enrollment_no 
        FROM student_submissions ss 
        JOIN users u ON ss.student_id = u.user_id 
        WHERE ss.manual_id='$safe_manual' 
        ORDER BY ss.submitted_at DESC
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Submissions - Faculty Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-width: 270px; --primary: #4338ca; --bg-body: #f8fafc; --surface: #ffffff; --text-main: #0f172a; --text-muted: #64748b; --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        * { box-sizing: border-box; }
        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        .sidebar { width: var(--sidebar-width); min-width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}

        .main { flex: 1; padding: 30px 45px; overflow-y: scroll; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

        .profile-pill { display: flex; align-items: center; background: linear-gradient(135deg, #1e3a8a, #4338ca); padding: 12px 25px 12px 30px; border-radius: 50px; cursor: pointer; text-decoration: none; color: white; transition: var(--transition-bounce); box-shadow: 0 10px 20px rgba(67, 56, 202, 0.2); border: 2px solid rgba(255,255,255,0.1);}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: #bfdbfe; font-weight: 800; text-transform: uppercase; margin-bottom: 2px; letter-spacing: 0.5px;}
        .profile-name { margin: 0; font-size: 17px; color: white; font-weight: 900;}
        .profile-avatar { background: white; color: var(--primary); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);}

        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
        
        .form-select { border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 600; padding: 12px 15px;}
        .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);}
        
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; }
        
        .badge-late { background: #fee2e2; color: #dc2626; border: 1px solid #f87171; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; letter-spacing: 0.5px;}
        .badge-ontime { background: #dcfce7; color: #16a34a; border: 1px solid #4ade80; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; letter-spacing: 0.5px;}
        
        .rubrics-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; margin-bottom: 15px; }
        .rubrics-label { font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 5px; }
        .rubrics-input { border-radius: 6px; border: 1px solid #cbd5e1; text-align: center; font-weight: bold; width: 100%; padding: 8px;}

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='upload_manual.php'"><i class="fas fa-file-upload"></i> Upload Manuals</li>
            <li class="active" onclick="window.location.href='review_submissions.php'"><i class="fas fa-check-double"></i> Review Submissions</li>
            <li onclick="window.location.href='reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> My Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome"><?php echo htmlspecialchars($designation); ?></span>
                    <h4 class="profile-name"><?php echo htmlspecialchars($faculty_name); ?></h4>
                </div>
                <div class="profile-avatar"><i class="fas fa-user-tie"></i></div>
            </a>
        </div>

        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1"><i class="fas fa-clipboard-check text-primary me-2"></i> Review & Evaluate</h3>
            <p class="text-muted fw-semibold small mb-0">Select a subject and practical to evaluate student submissions using the 20-marks rubrics.</p>
        </div>

        <?php echo $msg; ?>

        <!-- 2-STEP FILTER SECTION -->
        <div class="content-box bg-white">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label fw-bold small text-muted text-uppercase">1. Select Subject</label>
                    <select name="subject_code" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Choose Subject --</option>
                        <?php if($assigned_subjects && $assigned_subjects->num_rows > 0): ?>
                            <?php while($sub = $assigned_subjects->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($sub['subject_code']); ?>" <?php echo ($selected_subject == $sub['subject_code']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sub['subject_code'] . " - " . $sub['subject_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="col-md-5">
                    <label class="form-label fw-bold small text-muted text-uppercase">2. Select Practical Folder</label>
                    <select name="manual_id" class="form-select" <?php echo empty($selected_subject) ? 'disabled' : ''; ?>>
                        <option value="">-- Choose Practical --</option>
                        <?php if($practicals && $practicals->num_rows > 0): ?>
                            <?php while($prac = $practicals->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($prac['id']); ?>" <?php echo ($selected_manual == $prac['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prac['practical_no'] . " - " . $prac['title']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary fw-bold px-4 w-100 py-2" style="height: 48px;" <?php echo empty($selected_subject) ? 'disabled' : ''; ?>>Load Files</button>
                </div>
            </form>
        </div>

        <!-- SUBMISSIONS TABLE -->
        <?php if (!empty($selected_manual)): ?>
        <div class="content-box">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Student Name & ID</th>
                            <th>Submission Date</th>
                            <th>Status & Late Alert</th>
                            <th>Marks</th>
                            <th class="text-end">Evaluate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($submissions && $submissions->num_rows > 0): ?>
                            <?php while($subm = $submissions->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($subm['student_name']); ?></div>
                                        <div class="text-muted small fw-semibold"><?php echo htmlspecialchars($subm['enrollment_no']); ?></div>
                                    </td>
                                    <td>
                                        <?php echo date('d M Y, h:i A', strtotime($subm['submitted_at'])); ?>
                                    </td>
                                    <td>
                                        <?php 
                                            // LATE DETECTION LOGIC
                                            $sub_time = strtotime($subm['submitted_at']);
                                            $deadline_time = strtotime($manual_details['end_date']);
                                            $is_late = ($sub_time > $deadline_time);
                                            
                                            if($subm['status'] == 'Submitted') echo '<span class="badge bg-warning text-dark px-2 py-1">Pending Review</span><br>';
                                            elseif($subm['status'] == 'Graded') echo '<span class="badge bg-success px-2 py-1">Graded</span><br>';
                                            elseif($subm['status'] == 'Resubmit') echo '<span class="badge bg-danger px-2 py-1">Resubmit Requested</span><br>';
                                            
                                            if($is_late) echo '<div class="badge-late mt-1 d-inline-block"><i class="fas fa-exclamation-circle"></i> LATE SUBMISSION</div>';
                                            else echo '<div class="badge-ontime mt-1 d-inline-block"><i class="fas fa-check"></i> ON TIME</div>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if($subm['status'] == 'Graded'): ?>
                                            <span class="fs-5 fw-black text-primary"><?php echo $subm['total_marks']; ?> <span class="fs-6 text-muted">/20</span></span>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="../<?php echo htmlspecialchars($subm['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-info fw-bold me-1" title="View PDF">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="openEvaluateModal(<?php echo $subm['id']; ?>, '<?php echo addslashes($subm['student_name']); ?>', <?php echo (int)$subm['regularity_marks']; ?>, <?php echo (int)$subm['understanding_marks']; ?>, <?php echo (int)$subm['observation_marks']; ?>, <?php echo (int)$subm['viva_marks']; ?>, '<?php echo addslashes($subm['feedback'] ?? ''); ?>')">
                                            <i class="fas fa-edit"></i> Grade
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted fw-bold"><i class="fas fa-inbox fs-1 opacity-25 mb-3 d-block"></i> No submissions found for this practical yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- EVALUATION MODAL (RUBRICS) -->
    <div class="modal fade" id="evaluateModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fas fa-check-double me-2"></i> Evaluate Submissions</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="submission_id" id="eval_sub_id">
                        
                        <div class="alert alert-light border text-center mb-4">
                            <span class="d-block small text-muted text-uppercase fw-bold">Student Name</span>
                            <h5 class="fw-bold text-dark mb-0" id="eval_student_name">Loading...</h5>
                        </div>

                        <!-- 20-MARKS RUBRICS -->
                        <h6 class="fw-bold text-primary mb-3">20-Marks Evaluation Rubrics</h6>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="rubrics-box">
                                    <div class="rubrics-label">Regularity (Out of 5)</div>
                                    <input type="number" name="m_regularity" id="m_reg" class="rubrics-input" min="0" max="5" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="rubrics-box">
                                    <div class="rubrics-label">Understanding (Out of 5)</div>
                                    <input type="number" name="m_understanding" id="m_und" class="rubrics-input" min="0" max="5" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="rubrics-box">
                                    <div class="rubrics-label">Observation (Out of 5)</div>
                                    <input type="number" name="m_observation" id="m_obs" class="rubrics-input" min="0" max="5" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="rubrics-box">
                                    <div class="rubrics-label">Mock Viva Test (Out of 5)</div>
                                    <input type="number" name="m_viva" id="m_viv" class="rubrics-input" min="0" max="5" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-bold small text-muted text-uppercase">Feedback / Comments</label>
                            <textarea name="feedback" id="eval_feedback" class="form-control" rows="2" placeholder="Write specific feedback here..."></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light border-0 rounded-bottom-4 d-flex justify-content-between">
                        <button type="submit" name="save_evaluation" value="resubmit" onclick="document.getElementById('action_type').value='resubmit'" class="btn btn-outline-danger fw-bold"><i class="fas fa-undo me-2"></i> Ask to Resubmit</button>
                        
                        <button type="submit" name="save_evaluation" value="grade" onclick="document.getElementById('action_type').value='grade'" class="btn btn-success fw-bold px-4"><i class="fas fa-save me-2"></i> Save Marks</button>
                        
                        <input type="hidden" name="action_type" id="action_type" value="grade">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openEvaluateModal(id, name, reg, und, obs, viv, feedback) {
            document.getElementById('eval_sub_id').value = id;
            document.getElementById('eval_student_name').innerText = name;
            
            document.getElementById('m_reg').value = reg;
            document.getElementById('m_und').value = und;
            document.getElementById('m_obs').value = obs;
            document.getElementById('m_viv').value = viv;
            document.getElementById('eval_feedback').value = feedback;
            
            new bootstrap.Modal(document.getElementById('evaluateModal')).show();
        }

        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const clockEl = document.getElementById('liveClock');
            if(clockEl) clockEl.innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>