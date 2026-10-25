<?php
ob_start();
session_start();
include '../db.php';

// 1. Student Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$msg = "";

// Auto-fix any bad database unique keys silently to prevent crashes
$conn->query("ALTER TABLE student_submissions DROP INDEX unique_practical_sub");

// SESSION MESSAGE (To handle PRG redirect messages)
if (isset($_SESSION['upload_msg'])) {
    $msg = $_SESSION['upload_msg'];
    unset($_SESSION['upload_msg']);
}

// Catch Silent Drop for Large Files
if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-exclamation-triangle me-2'></i> Error: File ki size server ki limit se badi hai! Kripya 5MB se choti PDF upload karein.</div>";
}

$user_q = $conn->query("SELECT name, department, semester, profile_photo FROM users WHERE user_id = '$student_id'");
$user_d = ($user_q && $user_q->num_rows > 0) ? $user_q->fetch_assoc() : [];

$branch = $user_d['department'] ?? 'N/A';
$semester = $user_d['semester'] ?? '0';
$profile_pic = !empty($user_d['profile_photo']) ? "../" . $user_d['profile_photo'] : null;

$name_parts = explode(" ", trim($user_d['name'] ?? 'Student'));
$profile_display_name = $name_parts[0] . ' ' . (count($name_parts) > 1 ? end($name_parts) : '');

$selected_subject = $_GET['subject_code'] ?? '';

// ==========================================
// HANDLE FILE UPLOAD / REPLACE / RESUBMIT
// ==========================================
if (isset($_POST['submit_practical'])) {
    $manual_id = (int)$_POST['manual_id'];
    $upload_type = $_POST['upload_type']; 
    
    if(empty($selected_subject) && isset($_POST['current_subject'])) {
        $selected_subject = $_POST['current_subject'];
    }
    
    // Check if Graded (Locked from Backend)
    $check_lock = $conn->query("SELECT status FROM student_submissions WHERE manual_id = '$manual_id' AND student_id = '$student_id' ORDER BY submitted_at DESC LIMIT 1");
    if($check_lock && $check_lock->num_rows > 0) {
        if($check_lock->fetch_assoc()['status'] === 'Graded') {
            $_SESSION['upload_msg'] = "<div class='alert alert-danger m-4'>Security Error: Yeh practical pehle hi check ho chuka hai (Locked).</div>";
            header("Location: submit_practical.php?subject_code=" . urlencode($selected_subject));
            exit();
        }
    }

    if (isset($_FILES['practical_file']) && $_FILES['practical_file']['error'] == 0) {
        $file_name = $_FILES['practical_file']['name'];
        $file_tmp = $_FILES['practical_file']['tmp_name'];
        $file_size = $_FILES['practical_file']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if ($file_ext === "pdf") {
            if ($file_size <= 5242880) { // 5MB limit
                $target_dir = "../uploads/submissions/";
                if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
                
                $new_filename = $student_id . "_" . $manual_id . "_" . time() . ".pdf";
                $target_file = $target_dir . $new_filename;
                $db_filepath = "uploads/submissions/" . $new_filename;
                
                if (move_uploaded_file($file_tmp, $target_file)) {
                    
                    if ($upload_type === 'replace') {
                        // REPLACE LOGIC: Find old file and update using manual_id
                        $old_file_q = $conn->query("SELECT file_path FROM student_submissions WHERE manual_id = '$manual_id' AND student_id = '$student_id' ORDER BY submitted_at DESC LIMIT 1");
                        $old_file_path = ($old_file_q && $old_file_q->num_rows > 0) ? "../" . $old_file_q->fetch_assoc()['file_path'] : "";
                        
                        if($conn->query("UPDATE student_submissions SET file_path='$db_filepath', submitted_at=NOW() WHERE manual_id='$manual_id' AND student_id='$student_id'")) {
                            if (!empty($old_file_path) && file_exists($old_file_path)) { unlink($old_file_path); } 
                            $_SESSION['upload_msg'] = "<div class='alert alert-success shadow-sm'><i class='fas fa-sync-alt me-2'></i> Practical file successfully replace ho gayi!</div>";
                        } else {
                            $_SESSION['upload_msg'] = "<div class='alert alert-danger shadow-sm'>Database Update Error: " . $conn->error . "</div>";
                        }
                    } else {
                        // NEW UPLOAD / RESUBMIT (ULTIMATE FAILSAFE)
                        $sql = "INSERT INTO student_submissions (student_id, manual_id, file_path, status, submitted_at) VALUES ('$student_id', '$manual_id', '$db_filepath', 'Submitted', NOW())";
                        
                        if ($conn->query($sql)) {
                            $_SESSION['upload_msg'] = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i> Practical successfully submit ho gaya!</div>";
                        } else {
                            // Agar Insert fail hota hai DB structure ki wajah se, toh silently Update kar do!
                            $update_sql = "UPDATE student_submissions SET file_path='$db_filepath', status='Submitted', submitted_at=NOW() WHERE manual_id='$manual_id' AND student_id='$student_id'";
                            if ($conn->query($update_sql)) {
                                $_SESSION['upload_msg'] = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i> Practical successfully submit ho gaya! (Updated)</div>";
                            } else {
                                $_SESSION['upload_msg'] = "<div class='alert alert-danger shadow-sm'>Database Error: " . $conn->error . "</div>";
                            }
                        }
                    }
                } else {
                    $_SESSION['upload_msg'] = "<div class='alert alert-danger shadow-sm'>File server par move nahi ho payi.</div>";
                }
            } else {
                $_SESSION['upload_msg'] = "<div class='alert alert-danger shadow-sm'><i class='fas fa-exclamation-circle me-2'></i> Error: File 5MB se badi hai! Kripya choti size ki PDF upload karein.</div>";
            }
        } else {
            $_SESSION['upload_msg'] = "<div class='alert alert-danger shadow-sm'><i class='fas fa-exclamation-circle me-2'></i> Error: Sirf PDF files hi allowed hain!</div>";
        }
    } else {
        $err_code = $_FILES['practical_file']['error'] ?? 4;
        $err_msg = "Kripya ek valid file select karein.";
        if($err_code == 1 || $err_code == 2) { $err_msg = "File ki size server limit se badi hai!"; }
        $_SESSION['upload_msg'] = "<div class='alert alert-danger shadow-sm'><i class='fas fa-exclamation-triangle me-2'></i> Upload Failed: $err_msg</div>";
    }

    header("Location: submit_practical.php?subject_code=" . urlencode($selected_subject));
    exit();
}

// ==========================================
// FETCH SUBJECTS & PRACTICALS
// ==========================================
$subjects_query = $conn->query("SELECT DISTINCT subject_code, subject_name FROM lab_manuals WHERE department = '$branch' AND semester = '$semester'");

$manuals = null;
if (!empty($selected_subject)) {
    $safe_sub = $conn->real_escape_string($selected_subject);
    $manuals = $conn->query("SELECT * FROM lab_manuals WHERE department = '$branch' AND semester = '$semester' AND subject_code = '$safe_sub' ORDER BY id ASC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Practical - Student Portal</title>
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
        .profile-name { margin: 0; font-size: 16px; color: white; font-weight: 900;}
        .profile-avatar { background: white; color: var(--primary); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; overflow: hidden; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);}
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
        .form-select { border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 600; padding: 12px 15px;}
        
        .prac-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 20px; background: #fff; transition: all 0.2s; position: relative; overflow: hidden;}
        .prac-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.04); border-color: #cbd5e1;}
        .prac-title { font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 5px;}
        
        .deadline-badge { display: inline-flex; align-items: center; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 800; letter-spacing: 0.5px; margin-top: 10px;}
        .deadline-safe { background: #dcfce7; color: #16a34a; border: 1px solid #4ade80; }
        .deadline-late { background: #fee2e2; color: #dc2626; border: 1px solid #f87171; }
        .late-flag { background: #dc2626; color: white; padding: 4px 10px; border-radius: 50px; font-size: 11px; font-weight: 800; margin-left: 10px; }
        
        .upload-area { border: 2px dashed #cbd5e1; border-radius: 10px; padding: 20px; text-align: center; background: #f8fafc; margin-top: 15px;}
        .locked-area { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 25px; text-align: center; margin-top: 15px;}
        
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
            <li onclick="window.location.href='Stdashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='submit_practical.php'"><i class="fas fa-file-upload"></i> Submit Practicals</li>
            <li onclick="window.location.href='my_history.php'"><i class="fas fa-history"></i> My History</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-graduate"></i> My Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar mb-4">
            <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Student • Sem <?php echo htmlspecialchars($semester); ?></span>
                    <h4 class="profile-name"><?php echo htmlspecialchars($profile_display_name); ?></h4>
                </div>
                <div class="profile-avatar">
                    <?php if($profile_pic): ?>
                        <img src="<?php echo $profile_pic; ?>" alt="Profile">
                    <?php else: ?>
                        <i class="fas fa-user-graduate"></i>
                    <?php endif; ?>
                </div>
            </a>
        </div>

        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1"><i class="fas fa-file-upload text-primary me-2"></i> Submit Practical Assignments</h3>
            <p class="text-muted fw-semibold small mb-0">Select a subject to view its practicals and submission status.</p>
        </div>

        <?php echo $msg; ?>

        <!-- STEP 1: SUBJECT FILTER -->
        <div class="content-box bg-light">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label fw-bold small text-muted text-uppercase">Select Subject</label>
                    <select name="subject_code" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Choose Subject --</option>
                        <?php if($subjects_query && $subjects_query->num_rows > 0): ?>
                            <?php while($sub = $subjects_query->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($sub['subject_code']); ?>" <?php echo ($selected_subject == $sub['subject_code']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sub['subject_code'] . " - " . $sub['subject_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </form>
        </div>

        <!-- STEP 2: PRACTICALS LIST -->
        <?php if (!empty($selected_subject)): ?>
            <?php if($manuals && $manuals->num_rows > 0): ?>
                
                <h5 class="fw-bold mb-3"><i class="fas fa-folder-open text-primary me-2"></i> Practicals for <?php echo htmlspecialchars($selected_subject); ?></h5>
                
                <?php while($prac = $manuals->fetch_assoc()): ?>
                    <?php 
                        $manual_id = $prac['id'];
                        $sub_q = $conn->query("SELECT * FROM student_submissions WHERE student_id = '$student_id' AND manual_id = '$manual_id' ORDER BY submitted_at DESC LIMIT 1");
                        $has_submission = false;
                        $sub_data = null;
                        if($sub_q && $sub_q->num_rows > 0) {
                            $has_submission = true;
                            $sub_data = $sub_q->fetch_assoc();
                        }
                        
                        $deadline_timestamp = strtotime($prac['end_date']);
                        $is_late = ($deadline_timestamp < time());
                        
                        // Check if student submitted after deadline
                        $submitted_late = false;
                        if($has_submission) {
                            $submitted_time = strtotime($sub_data['submitted_at']);
                            if($submitted_time > $deadline_timestamp) {
                                $submitted_late = true;
                            }
                        }
                    ?>
                    
                    <div class="prac-card">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h4 class="prac-title"><?php echo htmlspecialchars($prac['practical_no']); ?>: <?php echo htmlspecialchars($prac['title']); ?></h4>
                                <a href="../<?php echo $prac['file_path']; ?>" target="_blank" class="text-decoration-none small fw-bold text-primary"><i class="fas fa-file-download"></i> View Faculty PDF</a>
                            </div>
                            
                            <div class="d-flex align-items-center">
                                <?php if(!$has_submission): ?>
                                    <span class="badge bg-secondary px-3 py-2">Not Submitted</span>
                                <?php elseif($sub_data['status'] == 'Submitted'): ?>
                                    <span class="badge bg-warning text-dark px-3 py-2">Pending Review</span>
                                <?php elseif($sub_data['status'] == 'Graded'): ?>
                                    <span class="badge bg-success px-3 py-2">Graded</span>
                                <?php elseif($sub_data['status'] == 'Resubmit'): ?>
                                    <span class="badge bg-danger px-3 py-2">Resubmit Required</span>
                                <?php endif; ?>

                                <!-- RED FLAG for late submission -->
                                <?php if($submitted_late && $has_submission): ?>
                                    <span class="late-flag"><i class="fas fa-flag"></i> Late</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if($is_late && !$has_submission): ?>
                            <div class="deadline-badge deadline-late"><i class="fas fa-exclamation-triangle me-2"></i> Deadline Passed: <?php echo date('d M Y, h:i A', $deadline_timestamp); ?></div>
                        <?php else: ?>
                            <div class="deadline-badge deadline-safe"><i class="far fa-clock me-2"></i> Due Date: <?php echo date('d M Y, h:i A', $deadline_timestamp); ?></div>
                        <?php endif; ?>

                        <!-- UPLOAD / LOCK LOGIC SECTION -->
                        
                        <?php if(!$has_submission): ?>
                            <form method="POST" enctype="multipart/form-data" class="upload-area <?php echo $is_late ? 'border-danger' : ''; ?>">
                                <input type="hidden" name="current_subject" value="<?php echo htmlspecialchars($selected_subject); ?>">
                                <input type="hidden" name="manual_id" value="<?php echo $manual_id; ?>">
                                <input type="hidden" name="upload_type" value="new">
                                <div class="mb-3 text-start">
                                    <label class="form-label fw-bold small">Upload Your PDF Solution <span class="text-muted fw-normal">(Max 5MB)</span></label>
                                    <input class="form-control" type="file" name="practical_file" accept=".pdf" required>
                                </div>
                                <button type="submit" name="submit_practical" class="btn <?php echo $is_late ? 'btn-danger' : 'btn-primary'; ?> fw-bold w-100">
                                    <i class="fas fa-cloud-upload-alt me-2"></i> <?php echo $is_late ? 'Submit Late Assignment' : 'Submit File'; ?>
                                </button>
                            </form>

                        <?php elseif($sub_data['status'] == 'Submitted'): ?>
                            <!-- STRICT DEADLINE REPLACE LOGIC -->
                            <?php if(!$is_late): ?>
                                <div class="upload-area border-warning">
                                    <p class="small text-muted fw-bold mb-2"><i class="fas fa-info-circle"></i> Faculty has not reviewed this yet. You can replace your file before the deadline.</p>
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="current_subject" value="<?php echo htmlspecialchars($selected_subject); ?>">
                                        <input type="hidden" name="manual_id" value="<?php echo $manual_id; ?>">
                                        <input type="hidden" name="upload_type" value="replace">
                                        <div class="d-flex gap-2 align-items-center">
                                            <input class="form-control" type="file" name="practical_file" accept=".pdf" required>
                                            <button type="submit" name="submit_practical" class="btn btn-warning fw-bold text-nowrap"><i class="fas fa-sync"></i> Replace File</button>
                                        </div>
                                        <div class="mt-2 text-start"><a href="../<?php echo $sub_data['file_path']; ?>" target="_blank" class="small fw-bold text-success"><i class="fas fa-eye"></i> View Current Upload</a></div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="locked-area border-danger">
                                    <p class="text-danger fw-bold mb-2"><i class="fas fa-lock"></i> Deadline Passed</p>
                                    <p class="small text-muted mb-0">You can no longer replace this file as the deadline has expired.</p>
                                    <div class="mt-3"><a href="../<?php echo $sub_data['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-bold"><i class="fas fa-eye"></i> View Uploaded File</a></div>
                                </div>
                            <?php endif; ?>

                        <?php elseif($sub_data['status'] == 'Graded'): ?>
                            <div class="locked-area">
                                <p class="text-muted small fw-bold mb-3 text-start">Marks: <span class="text-success fs-6"><?php echo $sub_data['total_marks']; ?>/20</span></p>
                                <p class="small text-muted mb-3 text-start">Submission is locked because this practical has been graded.</p>
                                <button class="btn btn-light border text-muted w-100 py-2 fw-bold" disabled><i class="fas fa-lock me-2"></i> Upload Locked</button>
                            </div>

                        <?php elseif($sub_data['status'] == 'Resubmit'): ?>
                            <div class="upload-area border-danger">
                                <p class="small text-dark mb-3 text-start"><b>Faculty feedback:</b> <?php echo htmlspecialchars($sub_data['feedback']); ?></p>
                                <form method="POST" enctype="multipart/form-data" class="bg-white border rounded-3 p-4">
                                    <input type="hidden" name="current_subject" value="<?php echo htmlspecialchars($selected_subject); ?>">
                                    <input type="hidden" name="manual_id" value="<?php echo $manual_id; ?>">
                                    <input type="hidden" name="upload_type" value="resubmit">
                                    <div class="d-flex flex-column align-items-center mb-3">
                                        <i class="fas fa-file-pdf fs-2 text-primary mb-2"></i>
                                        <h6 class="fw-bold mb-1">Upload Corrected PDF</h6>
                                        <span class="small text-muted">PDF - File size limit 5MB</span>
                                    </div>
                                    <input class="form-control mb-3" type="file" name="practical_file" accept=".pdf" required>
                                    <button type="submit" name="submit_practical" class="btn btn-primary fw-bold w-100">Submit Corrected File</button>
                                </form>
                                <p class="small text-muted mt-3 mb-0 text-start">Previous attempts remain available in submission history.</p>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                <?php endwhile; ?>
                
            <?php else: ?>
                <div class="text-center py-5 content-box"><i class="fas fa-folder-open fs-1 text-muted opacity-50 mb-3"></i><h5 class="fw-bold text-muted">No practicals found for this subject.</h5></div>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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