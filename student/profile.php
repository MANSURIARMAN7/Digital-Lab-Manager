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

// ==========================================
// HANDLE FORM SUBMISSIONS
// ==========================================

// A. Update Personal Info (ONLY Mobile Number)
if (isset($_POST['update_profile'])) {
    $mobile = $conn->real_escape_string(trim($_POST['mobile']));
    
    // PHP Fallback Validation for numbers only
    if(preg_match('/^[0-9]{0,15}$/', $mobile)) {
        $sql = "UPDATE users SET mobile='$mobile' WHERE user_id='$student_id'";
        if ($conn->query($sql)) {
            $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i> Mobile number updated successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-exclamation-triangle me-2'></i> Error updating profile.</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger shadow-sm'>Please enter a valid mobile number.</div>";
    }
}

// B. Upload Profile Photo
if (isset($_POST['upload_photo']) && isset($_FILES['profile_photo'])) {
    $target_dir = "../uploads/profiles/";
    if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $file_name = $_FILES["profile_photo"]["name"];
    $file_tmp = $_FILES["profile_photo"]["tmp_name"];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    $allowed = array("jpg", "jpeg", "png");
    if (in_array($file_ext, $allowed)) {
        $new_filename = "stu_" . $student_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_filename;
        $db_filepath = "uploads/profiles/" . $new_filename;
        
        if (move_uploaded_file($file_tmp, $target_file)) {
            $conn->query("UPDATE users SET profile_photo='$db_filepath' WHERE user_id='$student_id'");
            $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-image me-2'></i> Profile photo updated!</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-times-circle me-2'></i> Only JPG, JPEG, & PNG files are allowed.</div>";
    }
}

// C. Change Password
if (isset($_POST['change_password'])) {
    $current_pwd = $_POST['current_password'];
    $new_pwd = $_POST['new_password'];
    $confirm_pwd = $_POST['confirm_password'];
    
    if ($new_pwd !== $confirm_pwd) {
        $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-exclamation-circle me-2'></i> New passwords do not match!</div>";
    } else {
        $pwd_check = $conn->query("SELECT password FROM users WHERE user_id='$student_id'")->fetch_assoc();
        
        if ($pwd_check['password'] === $current_pwd) {
            $conn->query("UPDATE users SET password='$new_pwd' WHERE user_id='$student_id'");
            $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-lock me-2'></i> Password changed successfully!</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-times-circle me-2'></i> Current password is incorrect!</div>";
        }
    }
}

// ==========================================
// FETCH USER DATA FOR DISPLAY (SAFE METHOD)
// ==========================================
$user_query = $conn->query("SELECT * FROM users WHERE user_id = '$student_id'");
$user_data = ($user_query && $user_query->num_rows > 0) ? $user_query->fetch_assoc() : [];

$full_name = $user_data['name'] ?? 'Student';
$branch = !empty($user_data['department']) ? $user_data['department'] : 'N/A';
$semester = !empty($user_data['semester']) ? $user_data['semester'] : '0';
$profile_pic = !empty($user_data['profile_photo']) ? "../" . $user_data['profile_photo'] : null;

$name_parts = explode(" ", trim($full_name));
$profile_display_name = $name_parts[0] . ' ' . (count($name_parts) > 1 ? end($name_parts) : '');

// ==========================================
// ACADEMIC SUMMARY STATS
// ==========================================
$total_submissions = 0;
$graded_count = 0;
$pending_count = 0;

$stats_q = $conn->query("SELECT status FROM student_submissions WHERE student_id = '$student_id'");
if ($stats_q && $stats_q->num_rows > 0) {
    while ($row = $stats_q->fetch_assoc()) {
        $total_submissions++;
        if ($row['status'] == 'Graded') { $graded_count++; }
        if ($row['status'] == 'Submitted') { $pending_count++; }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student Portal</title>
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

        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .profile-pill { display: flex; align-items: center; background: linear-gradient(135deg, #1e3a8a, #4338ca); padding: 12px 25px 12px 30px; border-radius: 50px; cursor: pointer; text-decoration: none; color: white; transition: var(--transition-bounce); box-shadow: 0 10px 20px rgba(67, 56, 202, 0.2); border: 2px solid rgba(255,255,255,0.1);}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: #bfdbfe; font-weight: 800; text-transform: uppercase; margin-bottom: 2px; letter-spacing: 0.5px;}
        .profile-name { margin: 0; font-size: 16px; color: white; font-weight: 900;}
        .profile-avatar { background: white; color: var(--primary); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; overflow: hidden; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);}
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }

        .content-box { background: var(--surface); border-radius: 16px; padding: 30px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
        
        .header-card { position: relative; overflow: hidden; }
        .header-bg { position: absolute; top: 0; left: 0; right: 0; height: 120px; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border-radius: 16px 16px 0 0; }
        .header-content { position: relative; z-index: 1; padding-top: 60px; display: flex; align-items: flex-end; gap: 25px; }
        
        /* AVATAR HOVER EFFECTS FOR VIEW/CHANGE */
        .big-avatar { width: 130px; height: 130px; border-radius: 50%; border: 5px solid #ffffff; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 50px; color: #94a3b8; box-shadow: 0 10px 20px rgba(0,0,0,0.1); overflow: hidden; position: relative;}
        .big-avatar img { width: 100%; height: 100%; object-fit: cover; }
        
        .avatar-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.6); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; opacity: 0; transition: 0.3s; }
        .big-avatar:hover .avatar-overlay { opacity: 1; }
        .avatar-btn { color: white; font-size: 12px; font-weight: 700; cursor: pointer; padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.5); transition: 0.2s; background: rgba(255,255,255,0.1);}
        .avatar-btn:hover { background: rgba(255,255,255,0.3); }

        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px; padding-top: 25px; border-top: 1px solid #f1f5f9;}
        .info-item label { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 5px; display: block; letter-spacing: 0.5px;}
        .info-item div { font-size: 15px; font-weight: 700; color: var(--text-main); }

        .form-label { font-size: 12.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;}
        .form-control { border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 14.5px; padding: 12px 15px;}
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);}
        .form-control[readonly] { background-color: #f8fafc; cursor: not-allowed; color: var(--text-muted); }
        
        .badge-active { background: #dcfce7; color: #16a34a; border: 1px solid #4ade80; padding: 5px 12px; border-radius: 50px; font-size: 11px; font-weight: 800; letter-spacing: 0.5px;}
        
        /* SUMMARY CARDS */
        .summary-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 15px;}
        .summary-card h4 { margin: 0; font-size: 20px; font-weight: 900;}
        .summary-card span { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted);}
        
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
            <li onclick="window.location.href='submit_practical.php'"><i class="fas fa-file-upload"></i> Submit Practicals</li>
            <li onclick="window.location.href='my_history.php'"><i class="fas fa-history"></i> My History</li>
            <li class="active" onclick="window.location.href='profile.php'"><i class="fas fa-user-graduate"></i> My Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
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

        <?php echo $msg; ?>

        <!-- PROFILE HEADER CARD -->
        <div class="content-box header-card p-0">
            <div class="header-bg"></div>
            <div class="px-4 pb-4">
                <div class="header-content">
                    <div class="big-avatar">
                        <?php if($profile_pic): ?>
                            <img src="<?php echo $profile_pic; ?>" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user-graduate text-secondary"></i>
                        <?php endif; ?>
                        
                        <!-- NEW HOVER OVERLAY: VIEW & CHANGE -->
                        <div class="avatar-overlay">
                            <?php if($profile_pic): ?>
                            <div class="avatar-btn" data-bs-toggle="modal" data-bs-target="#viewPhotoModal"><i class="fas fa-eye me-1"></i> View</div>
                            <?php endif; ?>
                            <div class="avatar-btn" onclick="document.getElementById('photoUpload').click()"><i class="fas fa-camera me-1"></i> Change</div>
                        </div>
                    </div>
                    
                    <!-- Hidden Form for Photo Upload -->
                    <form method="POST" enctype="multipart/form-data" id="photoForm" class="d-none">
                        <input type="file" name="profile_photo" id="photoUpload" accept="image/*" onchange="document.getElementById('uploadBtn').click()">
                        <button type="submit" name="upload_photo" id="uploadBtn"></button>
                    </form>

                    <div class="flex-grow-1 pb-2">
                        <h2 class="fw-black text-dark mb-1"><?php echo htmlspecialchars($full_name); ?> <span class="badge-active ms-2"><i class="fas fa-shield-check"></i> Active</span></h2>
                        <h6 class="text-muted fw-bold mb-0">Sem <?php echo htmlspecialchars($semester); ?> • <?php echo htmlspecialchars($branch); ?></h6>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <label>Enrollment No. (User ID)</label>
                        <div><i class="fas fa-id-card text-primary me-2"></i> <?php echo htmlspecialchars($user_data['user_id']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Email Address</label>
                        <div><i class="fas fa-envelope text-primary me-2"></i> <?php echo htmlspecialchars($user_data['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Department</label>
                        <div><i class="fas fa-building text-primary me-2"></i> <?php echo htmlspecialchars($branch); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACADEMIC SUMMARY -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="summary-card border-primary border-start border-4 cursor-pointer" onclick="window.location.href='my_history.php'">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3"><i class="fas fa-upload fs-4"></i></div>
                    <div><span>Total Uploads</span><h4 class="text-primary"><?php echo $total_submissions; ?></h4></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card border-success border-start border-4 cursor-pointer" onclick="window.location.href='my_history.php?status=Graded'">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-3"><i class="fas fa-check-double fs-4"></i></div>
                    <div><span>Graded Practicals</span><h4 class="text-success"><?php echo $graded_count; ?></h4></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card border-warning border-start border-4 cursor-pointer" onclick="window.location.href='my_history.php?status=Submitted'">
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3"><i class="fas fa-clock fs-4"></i></div>
                    <div><span>Pending Reviews</span><h4 class="text-warning"><?php echo $pending_count; ?></h4></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- LEFT COLUMN: EDIT INFO -->
            <div class="col-lg-7">
                <div class="content-box">
                    <h6 class="fw-bold mb-4 text-dark"><i class="fas fa-user-edit text-primary me-2"></i> Personal Information</h6>
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Full Name <span class="text-danger small text-lowercase fst-italic">(Cannot be changed)</span></label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['name']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address <span class="text-danger small text-lowercase fst-italic">(Read Only)</span></label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile Number</label>
                                <!-- Validation: Only numbers using oninput -->
                                <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($user_data['mobile'] ?? ''); ?>" placeholder="Enter 10 digit number" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" name="update_profile" class="btn btn-primary fw-bold px-4 py-2"><i class="fas fa-save me-2"></i> Update Mobile Number</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN: PASSWORD -->
            <div class="col-lg-5">
                <div class="content-box">
                    <h6 class="fw-bold mb-4 text-dark"><i class="fas fa-shield-alt text-danger me-2"></i> Account Security</h6>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-dark fw-bold px-4 py-2 w-100"><i class="fas fa-key me-2"></i> Update Password</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- View Profile Photo Modal -->
    <?php if($profile_pic): ?>
    <div class="modal fade" id="viewPhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 bg-transparent">
                <div class="modal-body text-center p-0 position-relative">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    <img src="<?php echo $profile_pic; ?>" class="img-fluid rounded-4 shadow-lg" style="max-height: 80vh;" alt="Profile Picture">
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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