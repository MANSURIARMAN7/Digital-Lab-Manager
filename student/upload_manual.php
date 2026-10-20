<?php
session_start();
include '../db.php';

// 1. SECURE LOGIN CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$enrollment = $conn->real_escape_string((string)$_SESSION['user_id']);
$student_name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Student';

// Initials for Profile Avatar
$name_parts = explode(' ', trim($student_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) { $initials .= strtoupper(substr(end($name_parts), 0, 1)); }

$msg = "";
$manual_id = isset($_GET['manual_id']) ? (int)$_GET['manual_id'] : 0;
$subject_name = isset($_GET['subject']) ? $conn->real_escape_string($_GET['subject']) : '';
$prac_no = isset($_GET['prac']) ? $conn->real_escape_string($_GET['prac']) : '';

// Invalid access prevention
if ($manual_id == 0 || empty($subject_name)) {
    header("Location: my-manuals.php");
    exit();
}

// 2. CHECK EXISTING SUBMISSION & FEEDBACK
$check_sql = "SELECT * FROM student_submissions WHERE student_id='$enrollment' AND manual_id='$manual_id'";
$check_res = $conn->query($check_sql);
$existing_sub = $check_res->fetch_assoc();

// 3. SMART FILE UPLOAD & OVERWRITE LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_practical'])) {
    if (isset($_FILES['practical_file']) && $_FILES['practical_file']['error'] == 0) {
        
        $allowed_types = ['pdf'];
        $file_ext = strtolower(pathinfo($_FILES['practical_file']['name'], PATHINFO_EXTENSION));

        if(in_array($file_ext, $allowed_types)) {
            $upload_dir = "../uploads/submissions/";
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

            // Clean Filename: Time_Enrollment_PracX.pdf
            $safe_prac = preg_replace("/[^a-zA-Z0-9]/", "", $prac_no);
            $file_name = time() . "_" . $enrollment . "_Prac" . $safe_prac . ".pdf";
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['practical_file']['tmp_name'], $target_file)) {
                $db_path = "uploads/submissions/" . $file_name;
                
                if ($existing_sub) {
                    // Re-upload logic (Replace rejected or pending)
                    if ($existing_sub['status'] == 'Rejected' || $existing_sub['status'] == 'Pending') {
                        // 🧹 Delete old file to save server storage
                        $old_file = "../" . $existing_sub['file_path'];
                        if(file_exists($old_file)) { unlink($old_file); }
                        
                        $up_sql = "UPDATE student_submissions SET file_path='$db_path', status='Pending', submitted_at=CURRENT_TIMESTAMP WHERE submission_id='".$existing_sub['submission_id']."'";
                        if($conn->query($up_sql)) {
                            $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px;'><i class='fas fa-check-circle me-2'></i> Practical Re-submitted Successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                            $existing_sub['status'] = 'Pending'; // Update UI state
                            $existing_sub['file_path'] = $db_path;
                        }
                    } else {
                        $msg = "<div class='alert alert-warning alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px;'><i class='fas fa-shield-alt me-2'></i> Already Approved! Modification locked.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                    }
                } else {
                    // Fresh Upload
                    $in_sql = "INSERT INTO student_submissions (student_id, manual_id, subject_name, practical_no, file_path, status) 
                               VALUES ('$enrollment', '$manual_id', '$subject_name', '$prac_no', '$db_path', 'Pending')";
                    if($conn->query($in_sql)) {
                        $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px;'><i class='fas fa-check-circle me-2'></i> Practical Submitted Successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                        $existing_sub = $conn->query($check_sql)->fetch_assoc(); // Fetch for UI
                    }
                }
            } else {
                $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i> Server upload failed. Try again.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } else {
            $msg = "<div class='alert alert-warning alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px;'><i class='fas fa-file-pdf me-2'></i> Security Error: Only PDF files are allowed.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } else {
        $msg = "<div class='alert alert-warning alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px;'><i class='fas fa-exclamation-circle me-2'></i> Please select a valid PDF file.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Practical - KDP Student Portal</title>
    
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
            --radius-xl: 16px;
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
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

        /* 📦 CONTENT BOXES */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 30px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); }
        .box-title { font-size: 18px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }

        .info-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 25px; }
        .info-card h4 { font-size: 20px; font-weight: 800; color: var(--primary); margin-bottom: 5px;}
        .info-card p { font-size: 14px; font-weight: 600; color: var(--text-muted); margin: 0;}

        /* 🚀 PREMIUM INPUTS */
        .form-label { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;}
        .form-control { border-radius: 8px; border: 1px solid #cbd5e1; padding: 15px; font-size: 14.5px; font-weight: 600; background: #f8fafc; transition: 0.2s;}
        .form-control:focus { background: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(67, 56, 202, 0.1); }
        
        .btn-upload { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 15px 30px; border-radius: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; width: 100%; transition: var(--transition-bounce); font-size: 15px; display: flex; justify-content: center; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);}
        .btn-upload:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4); }

        .status-badge { padding: 8px 15px; border-radius: 8px; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; margin-top: 15px;}
        .bg-Pending { background: #fef3c7; color: #d97706; border: 1px solid #fde68a;}
        .bg-Approved { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0;}
        .bg-Rejected { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;}

        .btn-action { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 8px 15px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px;}
        .btn-action:hover { background: #2563eb; color: white; transform: translateY(-2px); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Student Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='Stdashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='my-manuals.php'"><i class="fas fa-book-open"></i> Course Manuals</li>
            <li onclick="window.location.href='history.php'"><i class="fas fa-history"></i> My Submissions</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            </div>
            
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Logged in as,</span>
                    <h4 class="profile-name">
                        <?php echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : $student_name; ?>
                    </h4>
                    <span style="font-size:11px; font-weight:700; color:var(--primary);"><?php echo htmlspecialchars($enrollment); ?></span>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </a>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
            <div>
                <h3 class="fw-bold text-dark mb-1" style="font-size: 26px;">Submit Practical</h3>
                <p class="text-muted fw-semibold small mb-0">Upload your completed lab manual in PDF format for faculty review.</p>
            </div>
            <a href="my-manuals.php" class="btn btn-outline-secondary fw-bold" style="border-radius: 10px; padding: 10px 20px;">
                <i class="fas fa-arrow-left me-2"></i> Back to Manuals
            </a>
        </div>

        <?php echo $msg; ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="content-box">
                    
                    <div class="info-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4><?php echo htmlspecialchars($subject_name); ?></h4>
                                <p><i class="fas fa-tag me-1"></i> Practical Reference: <?php echo htmlspecialchars($prac_no); ?></p>
                            </div>
                            <i class="fas fa-file-alt text-primary opacity-25" style="font-size: 40px;"></i>
                        </div>

                        <?php if ($existing_sub): ?>
                            <div class="mt-3 pt-3 border-top">
                                <div class="fw-bold text-dark mb-1" style="font-size: 13px;">Current Status:</div>
                                <span class="status-badge bg-<?php echo $existing_sub['status']; ?> mt-0">
                                    <?php echo $existing_sub['status']; ?>
                                </span>
                                
                                <?php if ($existing_sub['status'] == 'Rejected'): ?>
                                    <div class="alert alert-danger mt-3 mb-0" style="border-radius: 8px;">
                                        <strong><i class="fas fa-exclamation-circle me-1"></i> Faculty Remarks:</strong><br>
                                        <?php echo htmlspecialchars($existing_sub['feedback']); ?>
                                    </div>
                                <?php elseif ($existing_sub['status'] == 'Approved'): ?>
                                    <div class="alert alert-success mt-3 mb-0 d-flex justify-content-between align-items-center" style="border-radius: 8px;">
                                        <div>
                                            <strong><i class="fas fa-check-circle me-1"></i> Approved!</strong><br>
                                            Your marks: <?php echo htmlspecialchars($existing_sub['marks']); ?> / 20
                                        </div>
                                        <?php 
                                            $raw_path = $existing_sub['file_path'];
                                            $pos = strpos($raw_path, 'uploads/');
                                            $safe_pdf_path = ($pos !== false) ? '../' . substr($raw_path, $pos) : '../' . $raw_path;
                                        ?>
                                        <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" target="_blank" class="btn btn-success btn-sm fw-bold">View PDF</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$existing_sub || $existing_sub['status'] == 'Rejected' || $existing_sub['status'] == 'Pending'): ?>
                        <h5 class="box-title border-bottom-0 mb-3"><i class="fas fa-cloud-upload-alt text-primary me-2"></i> Upload Your File</h5>
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label">Select PDF File</label>
                                <input type="file" name="practical_file" class="form-control p-3" accept=".pdf" required>
                                <div class="text-muted fw-semibold mt-2" style="font-size:12px;">
                                    <i class="fas fa-info-circle text-primary me-1"></i> Ensure your file is clearly scanned and under 5MB.
                                </div>
                            </div>

                            <button type="submit" name="submit_practical" class="btn-upload">
                                <?php echo ($existing_sub) ? '<i class="fas fa-sync-alt"></i> Re-Submit File' : '<i class="fas fa-paper-plane"></i> Submit Practical'; ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-lock text-muted mb-3" style="font-size: 40px; opacity: 0.3;"></i>
                            <h5 class="fw-bold text-dark">Submission Locked</h5>
                            <p class="text-muted fw-semibold mb-0">Your practical has been approved by the faculty and cannot be modified.</p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
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
    </script>
</body>
</html>