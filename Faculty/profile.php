<?php
session_start();
include '../db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

// Directory for profile pictures
$upload_dir = "../uploads/profiles/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Fetch Current User Data
$query = "SELECT * FROM users WHERE user_id='$user_id'";
$result = $conn->query($query);
$user = $result->fetch_assoc();

$faculty_name = $user['name'] ?? 'Faculty';

// Generate Initials for Profile Avatar (Topbar)
$name_parts = explode(' ', trim($faculty_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

// Handle Profile Update & File Upload
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string(trim($_POST['email']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $cabin = $conn->real_escape_string(trim($_POST['cabin_no']));
    $bio = $conn->real_escape_string(trim($_POST['bio']));
    
    $new_password = trim($_POST['password']);
    $old_password = trim($_POST['old_password']);
    $captcha_input = trim($_POST['captcha_input']);

    $update_sql = "UPDATE users SET email='$email', phone='$phone', cabin_no='$cabin', bio='$bio'";
    $can_update = true;

    // 🔥 PASSWORD SECURITY CHECK LOGIC
    if (!empty($new_password)) {
        if (empty($old_password) || empty($captcha_input)) {
            $error_msg = "Please enter Old Password and solve the CAPTCHA to change your password!";
            $can_update = false;
        } elseif ($captcha_input != $_SESSION['captcha_answer']) {
            $error_msg = "Incorrect CAPTCHA answer! Please try again.";
            $can_update = false;
        } elseif ($old_password !== $user['password']) {
            $error_msg = "Incorrect Old Password!";
            $can_update = false;
        } else {
            $update_sql .= ", password='" . $conn->real_escape_string($new_password) . "'";
        }
    }

    // Photo Upload Logic
    if ($can_update && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $ext = pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION);
        $new_filename = "fac_" . $user_id . "_" . time() . "." . $ext;
        $target_file = $upload_dir . $new_filename;

        $imageFileType = strtolower($ext);
        if(in_array($imageFileType, ['jpg', 'png', 'jpeg', 'webp'])) {
            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) {
                $update_sql .= ", profile_pic='$new_filename'";
            } else {
                $error_msg = "Failed to upload image.";
                $can_update = false;
            }
        } else {
            $error_msg = "Only JPG, JPEG, PNG & WEBP files are allowed.";
            $can_update = false;
        }
    }

    $update_sql .= " WHERE user_id='$user_id'";

    if ($can_update && empty($error_msg)) {
        if ($conn->query($update_sql)) {
            $success_msg = "Profile updated successfully! 🚀";
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['cabin_no'] = $cabin;
            $user['bio'] = $bio;
            if(!empty($new_password)) $user['password'] = $new_password;
            
            // If new pic uploaded, update the user array to show it immediately
            if(isset($new_filename)) $user['profile_pic'] = $new_filename;
        } else {
            $error_msg = "Database Error: " . $conn->error;
        }
    }
}

// 🔥 GENERATE NEW CAPTCHA
$num1 = rand(1, 9);
$num2 = rand(1, 9);
$_SESSION['captcha_answer'] = $num1 + $num2;
$captcha_question = "$num1 + $num2 = ?";

$default_avatar = "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=1e293b&color=fff&size=200&bold=true";
$profile_src = !empty($user['profile_pic']) ? $upload_dir . $user['profile_pic'] : $default_avatar;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - KDP Faculty</title>
    
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
            --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        /* 🔥 SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid white;}
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        /* MAIN CONTENT */
        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* 🌐 TOPBAR */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        .security-badge { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 10px; padding: 10px 18px; font-weight: 700; font-size: 13px; }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);}

        /* 📦 PROFILE CARDS */
        .card-custom { background: var(--surface); border-radius: var(--radius-xl); border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); overflow: hidden; margin-bottom: 25px;}
        .card-custom:hover { box-shadow: var(--shadow-float); }
        
        /* COVER & AVATAR */
        .profile-cover { height: 140px; background: linear-gradient(135deg, #1e3a8a 0%, #4338ca 100%); position: relative; }
        .profile-content { padding: 0 30px 30px 30px; text-align: center; }
        .profile-img-wrapper { position: relative; z-index: 10; margin-top: -65px; margin-bottom: 15px; display: inline-block; }
        .profile-img-wrapper img { width: 130px; height: 130px; border-radius: 50%; border: 5px solid #ffffff; box-shadow: 0 8px 20px rgba(67, 56, 202, 0.15); background: #fff; object-fit: cover; }
        .cam-btn { position: absolute; bottom: 5px; right: 5px; background: var(--primary); color: white; width: 38px; height: 38px; border-radius: 50%; display: flex; justify-content: center; align-items: center; cursor: pointer; border: 3px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.2); transition: 0.3s; }
        .cam-btn:hover { background: var(--primary-hover); transform: scale(1.1); }

        .badge-role { background-color: rgba(59, 130, 246, 0.1); color: #2563eb; padding: 6px 18px; border-radius: 30px; font-weight: 800; font-size: 12px; border: 1px solid rgba(59, 130, 246, 0.2); display: inline-block; text-transform: uppercase; letter-spacing: 0.5px;}

        /* FORMS & INPUTS */
        .info-title { font-weight: 800; color: var(--text-main); font-size: 18px; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px; }
        .input-group { border-radius: 10px; overflow: hidden; border: 1px solid #cbd5e1; transition: var(--transition-bounce); background: #f8fafc;}
        .input-group:focus-within { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(67, 56, 202, 0.1); background: #ffffff;}
        .input-group-text { background: transparent; border: none; color: var(--text-muted); padding-left: 18px; font-size: 15px;}
        .form-control { border: none; padding: 12px 15px; font-weight: 600; font-size: 14.5px; color: var(--text-main); background: transparent; box-shadow: none !important;}
        .form-label { font-size: 12px; font-weight: 800; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;}

        .btn-update { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 14px 35px; font-weight: 800; font-size: 15px; border-radius: 10px; border: none; transition: var(--transition-bounce); box-shadow: 0 6px 15px rgba(16, 185, 129, 0.3); text-transform: uppercase; letter-spacing: 1px;}
        .btn-update:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4); }

        /* TAGS & LISTS */
        .subject-tag { display: inline-flex; align-items: center; background: #ffffff; border: 1px solid #cbd5e1; color: var(--text-main); padding: 8px 16px; border-radius: 8px; font-size: 14px; margin-right: 10px; margin-bottom: 10px; font-weight: 700; box-shadow: 0 2px 4px rgba(0,0,0,0.01);}
        .sem-badge { background: rgba(67, 56, 202, 0.1); color: var(--primary); padding: 3px 10px; border-radius: 6px; font-size: 11px; margin-right: 10px; font-weight: 800; text-transform: uppercase;}
        
        .meta-list { list-style: none; padding: 0; margin: 0; text-align: left;}
        .meta-list li { display: flex; align-items: center; gap: 15px; padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-weight: 600; color: var(--text-main); font-size: 14.5px;}
        .meta-list li:last-child { border-bottom: none; }
        .meta-list li i { width: 35px; height: 35px; background: #f1f5f9; color: var(--primary); display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 15px;}

        /* SECURITY BOX */
        .security-box { background: rgba(239, 68, 68, 0.03); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; padding: 25px; margin-top: 15px; }
        .captcha-box { background: #ffffff; padding: 12px 25px; border-radius: 8px; font-weight: 900; letter-spacing: 2px; font-size: 18px; color: #dc2626; display: inline-block; border: 2px dashed #fca5a5; }

        .alert-custom { padding: 15px 20px; border-radius: 10px; margin-bottom: 25px; font-size: 14px; font-weight: 700; display: flex; align-items: center;}
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }

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
            <div class="sidebar-subtitle">Faculty Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='labmanual_list.php'"><i class="fas fa-check-double"></i> Review & Evaluate</li>
            <li onclick="window.location.href='reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li class="active" onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
                <div class="security-badge d-none d-md-flex align-items-center">
                    <i class="fas fa-shield-check me-2"></i> Profile Management
                </div>
            </div>
            
            <a href="profile.php" class="profile-pill" style="padding: 10px 22px 10px 28px;">
                <div class="profile-text">
                    <span class="profile-welcome" style="font-size: 11px;">Welcome Back,</span>
                    <h4 class="profile-name" style="font-size: 17px; font-weight: 900; letter-spacing: 0.3px;">
                        <?php 
                            $disp_name = htmlspecialchars($faculty_name);
                            if(stripos($disp_name, 'Prof') === false && stripos($disp_name, 'Dr') === false) { echo "Prof. " . $disp_name; } else { echo $disp_name; }
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar" style="width: 48px; height: 48px; font-size: 16px; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.4);"><?php echo $initials; ?></div>
            </a>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1" style="font-size: 26px;">Account Settings</h3>
                <p class="text-muted small fw-semibold mb-0">Update your personal details and security preferences.</p>
            </div>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert-custom alert-success"><i class="fas fa-check-circle fs-5 me-3"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert-custom alert-danger"><i class="fas fa-exclamation-triangle fs-5 me-3"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row g-4">
                
                <!-- LEFT COLUMN: AVATAR & INFO -->
                <div class="col-lg-4">
                    <div class="card-custom mb-4">
                        <div class="profile-cover"></div>
                        <div class="profile-content">
                            <!-- Image Upload -->
                            <div class="profile-img-wrapper">
                                <img id="previewImg" src="<?php echo $profile_src; ?>" alt="Profile">
                                <label for="profileUpload" class="cam-btn" title="Update Photo">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" id="profileUpload" name="profile_pic" accept="image/*" style="display: none;" onchange="previewFile()">
                            </div>
                            
                            <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
                            <p class="text-muted fw-bold small mb-3 letter-spacing-1"><?php echo htmlspecialchars($user['user_id']); ?></p>
                            
                            <?php if(!empty($user['bio'])): ?>
                                <p class="text-secondary small fst-italic px-3 mb-3 fw-medium">"<?php echo htmlspecialchars($user['bio']); ?>"</p>
                            <?php endif; ?>

                            <span class="badge-role"><i class="fas fa-award me-1"></i> <?php echo htmlspecialchars($user['designation'] ?? 'Faculty Member'); ?></span>
                        </div>
                        
                        <ul class="meta-list border-top border-light">
                            <li><i class="fas fa-building"></i> <span><?php echo htmlspecialchars($user['department']); ?></span></li>
                            <?php if(!empty($user['cabin_no'])): ?>
                                <li><i class="fas fa-door-open"></i> <span>Cabin: <?php echo htmlspecialchars($user['cabin_no']); ?></span></li>
                            <?php endif; ?>
                            <!-- 🔥 FIXED ICON HERE -->
                            <li><i class="fas fa-check-circle text-success" style="background: rgba(16,185,129,0.1);"></i> <span>Status: Active</span></li>
                        </ul>
                    </div>

                    <!-- ASSIGNED MODULES (CHIPS) -->
                    <div class="card-custom p-4">
                        <h5 class="info-title mb-3"><i class="fas fa-layer-group text-primary"></i> Assigned Modules</h5>
                        <div class="mt-2">
                            <?php 
                            // 🔥 FIXED QUERY: MATCHING WITH subjects TABLE via faculty_name
                            $fac_name_safe = $conn->real_escape_string($user['name']); 
                            $mod_query = "SELECT DISTINCT subject_name, semester FROM subjects WHERE faculty_name LIKE '%$fac_name_safe%' ORDER BY semester ASC";
                            $mod_res = $conn->query($mod_query);

                            if ($mod_res && $mod_res->num_rows > 0) {
                                while($row = $mod_res->fetch_assoc()) {
                                    $sem_text = str_ireplace('Semester', 'Sem', htmlspecialchars($row['semester']));
                                    echo '<div class="subject-tag"><span class="sem-badge">' . $sem_text . '</span> ' . htmlspecialchars($row['subject_name']) . '</div>';
                                }
                            } else {
                                echo '<div class="p-3 bg-light rounded text-center text-muted small fw-bold"><i class="fas fa-info-circle mb-2 fs-4 opacity-50"></i><br>No modules assigned by HOD yet.</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: FORMS -->
                <div class="col-lg-8">
                    
                    <!-- Basic Info -->
                    <div class="card-custom p-4 p-md-5 mb-4">
                        <h5 class="info-title"><i class="fas fa-user-edit text-primary"></i> Personal Details</h5>
                        <div class="row g-4 mt-1">
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Enter email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone-alt"></i></span>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Enter phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Office / Cabin No.</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-door-closed"></i></span>
                                    <input type="text" name="cabin_no" class="form-control" value="<?php echo htmlspecialchars($user['cabin_no'] ?? ''); ?>" placeholder="e.g. Block A, Room 204">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Short Bio / Expertise</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-quote-left"></i></span>
                                    <input type="text" name="bio" class="form-control" value="<?php echo htmlspecialchars($user['bio'] ?? ''); ?>" placeholder="e.g. AI Enthusiast">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Security Info -->
                    <div class="card-custom p-4 p-md-5 mb-4 border-top border-4 border-danger">
                        <h5 class="info-title text-danger" style="border-bottom: none;"><i class="fas fa-shield-alt"></i> Update Password</h5>
                        <p class="text-muted small fw-semibold mb-3">Leave fields blank if you do not want to change your password.</p>
                        
                        <div class="security-box">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Old Password</label>
                                    <div class="input-group bg-white">
                                        <span class="input-group-text"><i class="fas fa-unlock-alt"></i></span>
                                        <input type="password" name="old_password" class="form-control" placeholder="Current Password">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">New Password</label>
                                    <div class="input-group bg-white">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" name="password" class="form-control" placeholder="New Password">
                                    </div>
                                </div>
                                <div class="col-12 mt-4 pt-3 border-top border-danger border-opacity-10">
                                    <label class="form-label text-danger">Solve to Verify Action</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="captcha-box"><?php echo $captcha_question; ?></div>
                                        <input type="number" name="captcha_input" class="form-control bg-white" placeholder="Enter Answer" style="max-width: 150px; height: 50px; font-weight:bold; font-size:16px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end mb-4">
                        <button type="submit" class="btn-update shadow-sm"><i class="fas fa-save me-2"></i> Save All Changes</button>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        function previewFile() {
            const preview = document.getElementById('previewImg');
            const file = document.getElementById('profileUpload').files[0];
            const reader = new FileReader();

            reader.addEventListener("load", function () {
                preview.src = reader.result;
            }, false);

            if (file) {
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>