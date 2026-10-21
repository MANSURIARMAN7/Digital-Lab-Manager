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

$fac_query = $conn->query("SELECT name, designation FROM users WHERE user_id = '$faculty_id'");
$fac_data = $fac_query->fetch_assoc();
$faculty_name = $fac_data['name'];
$designation = !empty($fac_data['designation']) ? $fac_data['designation'] : 'Faculty Member';

// Robust Mapping Query from the main 'subjects' table where Admin actually assigns the faculty
$assigned_subjects = $conn->query("SELECT subject_code, subject_name, department, semester FROM subjects WHERE faculty_name LIKE '%$faculty_id%' AND status='Active'");$safe_id = strtolower($conn->real_escape_string($faculty_id));
$safe_name = strtolower($conn->real_escape_string($faculty_name));

$assigned_subjects = $conn->query("SELECT subject_code, subject_name, department, semester FROM subjects WHERE (LOWER(faculty_name) LIKE '%$safe_id%' OR LOWER(faculty_name) LIKE '%$safe_name%') AND status='Active'");

// ==========================================
// UPLOAD LAB MANUAL LOGIC
// ==========================================
if (isset($_POST['upload_manual'])) {
    $subject_data = explode('|', $_POST['subject_data']); 
    $sub_code = $conn->real_escape_string($subject_data[0]);
    $sub_name = $conn->real_escape_string($subject_data[1]);
    $dept = $conn->real_escape_string($subject_data[2]);
    $sem = $conn->real_escape_string($subject_data[3]);
    
    $prac_no = $conn->real_escape_string(trim($_POST['practical_no']));
    $title = $conn->real_escape_string(trim($_POST['title']));
    $start_date = date('Y-m-d H:i:s'); 
    $deadline = $conn->real_escape_string($_POST['deadline']); 
    
    $target_dir = "../uploads/lab_manuals/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = $_FILES["manual_file"]["name"];
    $file_tmp = $_FILES["manual_file"]["tmp_name"];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    if ($file_ext !== "pdf") {
        $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-exclamation-triangle me-2'></i> <b>Error:</b> Only PDF files are allowed.</div>";
    } else {
        $new_filename = time() . "_" . $faculty_id . "_" . preg_replace('/[^A-Za-z0-9\-]/', '', $prac_no) . ".pdf";
        $target_file = $target_dir . $new_filename;
        $db_filepath = "uploads/lab_manuals/" . $new_filename;
        
        if (move_uploaded_file($file_tmp, $target_file)) {
            $sql = "INSERT INTO lab_manuals (faculty_id, subject_code, subject_name, department, branch, semester, practical_no, title, file_path, start_date, end_date) 
                    VALUES ('$faculty_id', '$sub_code', '$sub_name', '$dept', '$dept', '$sem', '$prac_no', '$title', '$db_filepath', '$start_date', '$deadline')";
            
            if ($conn->query($sql)) {
                $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i> Manual <b>$prac_no</b> uploaded successfully with deadline!</div>";
            } else {
                $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-times-circle me-2'></i> Database Error: " . $conn->error . "</div>";
            }
        } else {
            $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-exclamation-circle me-2'></i> Failed to move uploaded file to server folder.</div>";
        }
    }
}

$recent_manuals = $conn->query("SELECT * FROM lab_manuals WHERE faculty_id = '$faculty_id' ORDER BY id DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Lab Manual - Faculty Portal</title>
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
        .profile-pill:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(67, 56, 202, 0.3); border-color: rgba(255,255,255,0.3);}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: #bfdbfe; font-weight: 800; text-transform: uppercase; margin-bottom: 2px; letter-spacing: 0.5px;}
        .profile-name { margin: 0; font-size: 17px; color: white; font-weight: 900;}
        .profile-avatar { background: white; color: var(--primary); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);}

        .content-box { background: var(--surface); border-radius: 16px; padding: 30px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 25px;}
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px;}

        .form-label { font-size: 12.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;}
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 14.5px; padding: 12px 15px;}
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);}
        
        .upload-area { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 20px; text-align: center; background: #f8fafc; transition: 0.3s;}
        .upload-area:hover { border-color: var(--primary); background: #f1f5f9;}

        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; }
        .sub-code { color: var(--primary); font-weight: 800; }
        .deadline-badge { background: rgba(239, 68, 68, 0.1); color: #dc2626; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 800; }

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
            <li class="active" onclick="window.location.href='upload_manual.php'"><i class="fas fa-file-upload"></i> Upload Manuals</li>
            <li onclick="window.location.href='review_submissions.php'"><i class="fas fa-check-double"></i> Review Submissions</li>
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
            <h3 class="fw-bold text-dark mb-1"><i class="fas fa-cloud-upload-alt text-primary me-2"></i> Upload Lab Manual</h3>
            <p class="text-muted fw-semibold small mb-0">Upload practical PDFs and set strict submission deadlines for students.</p>
        </div>

        <?php echo $msg; ?>

        <div class="row">
            <div class="col-lg-5">
                <div class="content-box">
                    <div class="box-title"><i class="fas fa-file-pdf text-danger"></i> New Practical Details</div>
                    <form method="POST" enctype="multipart/form-data">
                        
                        <div class="mb-3">
                            <label class="form-label">Select Assigned Subject <span class="text-danger">*</span></label>
                            <select name="subject_data" class="form-select" required>
                                <option value="" disabled selected>-- Choose from your mapped subjects --</option>
                                <?php if($assigned_subjects && $assigned_subjects->num_rows > 0): ?>
                                    <?php while($sub = $assigned_subjects->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($sub['subject_code'].'|'.$sub['subject_name'].'|'.$sub['department'].'|'.$sub['semester']); ?>">
                                            <?php echo htmlspecialchars($sub['subject_code'] . " - " . $sub['subject_name']); ?> (Sem <?php echo $sub['semester']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="" disabled>No active subjects assigned to you.</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-5 mb-3">
                                <label class="form-label">Practical No. <span class="text-danger">*</span></label>
                                <input type="text" name="practical_no" class="form-control" required placeholder="e.g. PR 1">
                            </div>
                            <div class="col-md-7 mb-3">
                                <label class="form-label">Title / Topic <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g. Logic Gates">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Submission Deadline <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="deadline" class="form-control text-danger fw-bold" required>
                            <small class="text-muted fst-italic">Students uploading after this time will be marked as LATE.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label d-block">Upload PDF File <span class="text-danger">*</span></label>
                            <div class="upload-area">
                                <input type="file" name="manual_file" accept=".pdf" class="form-control" required>
                                <small class="text-muted mt-2 d-block">Only .pdf format allowed (Max size 10MB)</small>
                            </div>
                        </div>

                        <button type="submit" name="upload_manual" class="btn btn-primary fw-bold px-4 w-100 py-3"><i class="fas fa-upload me-2"></i> Upload Manual & Notify</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="content-box h-100">
                    <div class="box-title"><i class="fas fa-history text-info"></i> Your Recently Uploaded Manuals</div>
                    
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Subject & PR No.</th>
                                    <th>Topic Name</th>
                                    <th>Deadline</th>
                                    <th class="text-end">View</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($recent_manuals && $recent_manuals->num_rows > 0): ?>
                                    <?php while($row = $recent_manuals->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="sub-code"><?php echo htmlspecialchars($row['subject_code']); ?></div>
                                                <span class="badge bg-dark mt-1"><?php echo htmlspecialchars($row['practical_no']); ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['title']); ?></div>
                                                <div class="text-muted small">Sem <?php echo htmlspecialchars($row['semester']); ?> • <?php echo date('d M', strtotime($row['uploaded_at'])); ?></div>
                                            </td>
                                            <td>
                                                <?php 
                                                    $deadline_date = date('d M Y, h:i A', strtotime($row['end_date']));
                                                    $is_past = (strtotime($row['end_date']) < time()) ? "opacity-50" : "";
                                                ?>
                                                <span class="deadline-badge <?php echo $is_past; ?>">
                                                    <i class="far fa-clock me-1"></i> <?php echo $deadline_date; ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="../<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-bold">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted fw-bold"><i class="fas fa-folder-open fs-1 opacity-25 mb-3 d-block"></i> No manuals uploaded yet.</td></tr>
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
            const clockEl = document.getElementById('liveClock');
            if(clockEl) clockEl.innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>