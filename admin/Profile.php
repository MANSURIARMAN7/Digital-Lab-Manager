<?php
session_start();
include '../db.php'; // Database connection

// Check agar admin logged in hai ya nahi
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$message = "";

// 🤖 CAPTCHA INITIALIZATION
if (!isset($_SESSION['captcha_num1']) || !isset($_SESSION['captcha_num2'])) {
    $_SESSION['captcha_num1'] = rand(1, 9);
    $_SESSION['captcha_num2'] = rand(1, 9);
}

// 🛠️ SMART DB AUTO-FIX FOR PREFERENCES
$check_cols = $conn->query("SHOW COLUMNS FROM users LIKE 'email_notifications'");
if ($check_cols->num_rows == 0) {
    @$conn->query("ALTER TABLE users ADD COLUMN email_notifications TINYINT(1) DEFAULT 1");
    @$conn->query("ALTER TABLE users ADD COLUMN two_factor_auth TINYINT(1) DEFAULT 0");
}

// 🚀 PASSWORD UPDATE LOGIC WITH CAPTCHA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    
    $user_captcha = isset($_POST['captcha_input']) ? (int)$_POST['captcha_input'] : 0;
    $real_captcha = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];

    // CHECK CAPTCHA FIRST
    if ($user_captcha !== $real_captcha) {
        $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px;' role='alert'><i class='fas fa-robot me-2'></i> Incorrect CAPTCHA! Please try again. ❌<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        // PROCEED WITH PASSWORD UPDATE
        $old_password = $conn->real_escape_string($_POST['old_password']);
        $new_password = $conn->real_escape_string($_POST['new_password']);
        $confirm_password = $conn->real_escape_string($_POST['confirm_password']);

        if ($new_password === $confirm_password) {
            $check_query = "SELECT password FROM users WHERE user_id = '$admin_id'";
            $res = $conn->query($check_query);
            
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                
                if ($row['password'] === $old_password) {
                    $update_query = "UPDATE users SET password = '$new_password' WHERE user_id = '$admin_id'";
                    if ($conn->query($update_query)) {
                        $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px; font-weight:600;' role='alert'><i class='fas fa-check-circle me-2'></i> Password updated successfully! 🎉<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                    } else {
                        $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px;' role='alert'><i class='fas fa-exclamation-triangle me-2'></i> Error updating password!</div>";
                    }
                } else {
                    $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px;' role='alert'><i class='fas fa-times-circle me-2'></i> Incorrect Old Password! ❌<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
                }
            }
        } else {
            $message = "<div class='alert alert-warning alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px; color:#92400e;' role='alert'><i class='fas fa-exclamation-circle me-2'></i> New Password and Confirm Password do not match! ⚠️<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    }
    
    // Regenerate CAPTCHA after every submit (Security Best Practice)
    $_SESSION['captcha_num1'] = rand(1, 9);
    $_SESSION['captcha_num2'] = rand(1, 9);
}

// ⚙️ PREFERENCES UPDATE LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_preferences'])) {
    $email_notif = isset($_POST['email_notifications']) ? 1 : 0;
    $two_factor = isset($_POST['two_factor_auth']) ? 1 : 0;
    
    $pref_query = "UPDATE users SET email_notifications = '$email_notif', two_factor_auth = '$two_factor' WHERE user_id = '$admin_id'";
    if ($conn->query($pref_query)) {
        $message = "<div class='alert alert-success alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px; font-weight:600;' role='alert'><i class='fas fa-check-circle me-2'></i> System preferences saved to database! ⚙️<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $message = "<div class='alert alert-danger alert-dismissible fade show shadow-sm mb-4' style='border-radius:10px;' role='alert'><i class='fas fa-exclamation-triangle me-2'></i> Error updating preferences!</div>";
    }
}

// Fetch Admin Details
$admin_query = $conn->query("SELECT * FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data['name'] ?? 'System Administrator';

$email_notifications = $admin_data['email_notifications'] ?? 1;
$two_factor_auth = $admin_data['two_factor_auth'] ?? 0;

$name_parts = explode(' ', trim($admin_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

$q_students = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='student'");
$total_students = $q_students->fetch_assoc()['cnt'] ?? 0;

$q_faculty = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='faculty'");
$total_faculty = $q_faculty->fetch_assoc()['cnt'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Settings - Admin Portal</title>
    
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
        
        /* 🔥 PREMIUM BLUE SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover, .nav-links li.active { color: #ffffff; background: rgba(255,255,255,0.2); }
        .nav-links li.active { font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid white;}
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* 🌐 TOPBAR */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 10px 22px 10px 28px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: var(--primary); font-weight: 800; text-transform: uppercase; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 17px; color: var(--text-main); font-weight: 900;}
        .profile-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.4);}

        /* 📦 PROFILE CARDS */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 35px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); }
        .content-box:hover { box-shadow: var(--shadow-float); }
        
        .profile-header-card { overflow: hidden; padding: 0; }
        .bg-admin-header { background: linear-gradient(135deg, #1e3a8a, #4338ca); color: white; padding: 35px 20px 60px 20px; text-align: center; }
        .admin-avatar-large { 
            width: 110px; height: 110px; background: var(--surface); color: var(--primary); 
            display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: 800;
            border-radius: 50%; margin: -55px auto 20px auto; box-shadow: 0 8px 20px rgba(0,0,0,0.1); border: 5px solid var(--surface);
            position: relative; z-index: 2; letter-spacing: 2px;
        }

        /* 🚀 PREMIUM BUTTONS & INPUTS */
        .btn-gradient { background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; border: none; font-weight: 700; padding: 12px 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); transition: var(--transition-bounce); }
        .btn-gradient:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4); color: white; }
        
        .input-group-text { background: #f8fafc; border-radius: 10px 0 0 10px; border: 1px solid #cbd5e1; border-right: none; color: #94a3b8; }
        input.form-control { border-radius: 0 10px 10px 0; padding: 12px; border: 1px solid #cbd5e1; border-left: none; font-weight: 500; font-size: 14px; transition: var(--transition-bounce); background: #f8fafc; }
        input.form-control:focus { background: #ffffff; border-color: var(--primary); box-shadow: none; }
        .input-group:focus-within .input-group-text, .input-group:focus-within input.form-control { border-color: var(--primary); background: #ffffff; }
        .input-group:focus-within { box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); border-radius: 10px; }
        input.form-control:read-only { background-color: #f1f5f9; color: #64748b; cursor: not-allowed; }

        /* 🗂️ CUSTOM TABS */
        .nav-pills .nav-link { color: var(--text-muted); font-weight: 700; padding: 12px 20px; border-radius: 10px; margin-right: 10px; transition: var(--transition-bounce); }
        .nav-pills .nav-link:hover { background: #f1f5f9; color: var(--primary); }
        .nav-pills .nav-link.active { background: rgba(67, 56, 202, 0.1); color: var(--primary); border: 1px solid rgba(67, 56, 202, 0.2); }
        
        .tab-content { padding-top: 25px; }
        .form-check-input:checked { background-color: var(--primary); border-color: var(--primary); }
        .form-check-input { width: 3em !important; height: 1.5em !important; cursor: pointer; }

        /* 🤖 CAPTCHA DESIGN */
        .captcha-box { background: #f1f5f9; color: #0f172a; font-weight: 900; font-size: 16px; letter-spacing: 2px; padding: 0 20px; user-select: none; border-radius: 10px 0 0 10px; border: 1px solid #cbd5e1; border-right: none; display: flex; align-items: center;}

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
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li class="active" onclick="window.location.href='Profile.php'"><i class="fas fa-user-shield"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            </div>
            
            <div class="profile-pill" style="cursor: default;">
                <div class="profile-text">
                    <span class="profile-welcome">System Admin</span>
                    <h4 class="profile-name">
                        <?php 
                            $disp_name = htmlspecialchars($admin_name);
                            echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : $disp_name;
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 mt-2 page-header">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 55px; height: 55px; border-radius: 14px; background: rgba(67, 56, 202, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 24px;">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);">Settings & Security</h3>
                    <p class="text-muted fw-semibold small mb-0">Manage your system credentials, view account details, and set preferences.</p>
                </div>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary fw-bold" style="border-radius: 10px; padding: 10px 20px;">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>

        <?php echo $message; // Alerts ?>

        <div class="row g-4">
            
            <!-- LEFT COLUMN: DIGITAL ID CARD -->
            <div class="col-md-4">
                <div class="content-box profile-header-card h-100">
                    <div class="bg-admin-header">
                        <h4 class="fw-bold mb-0 text-white" style="letter-spacing: 1px;">K.D. Polytechnic</h4>
                        <p class="mb-0 mt-1" style="color: #bfdbfe; font-size: 13.5px;"><?php echo htmlspecialchars($admin_data['department'] ?? 'Computer Engineering'); ?></p>
                    </div>
                    
                    <div class="text-center px-4 pb-4">
                        <div class="admin-avatar-large">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        
                        <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($admin_name); ?></h4>
                        <p class="text-muted fw-semibold small mb-3">System Administrator</p>
                        
                        <span class="badge bg-success px-3 py-2 rounded-pill mb-4" style="font-size: 11px; letter-spacing: 0.5px; border: 1px solid rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.1) !important; color: #059669 !important;">
                            <i class="fas fa-check-circle me-1"></i> Account Active & Verified
                        </span>
                        
                        <hr style="border-color: #e2e8f0;">
                        
                        <div class="row text-center mt-4">
                            <div class="col-6 border-end">
                                <h3 class="fw-bold text-dark mb-0"><?php echo $total_students; ?></h3>
                                <p class="text-muted small fw-semibold mb-0">Students</p>
                            </div>
                            <div class="col-6">
                                <h3 class="fw-bold text-dark mb-0"><?php echo $total_faculty; ?></h3>
                                <p class="text-muted small fw-semibold mb-0">Faculty</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: FUNCTIONAL SETTINGS TABS -->
            <div class="col-md-8">
                <div class="content-box h-100">
                    
                    <!-- TAB NAVIGATION -->
                    <ul class="nav nav-pills mb-4 border-bottom pb-3" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="pills-security-tab" data-bs-toggle="pill" data-bs-target="#pills-security" type="button" role="tab"><i class="fas fa-key me-2"></i> Security</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-preferences-tab" data-bs-toggle="pill" data-bs-target="#pills-preferences" type="button" role="tab"><i class="fas fa-sliders-h me-2"></i> Preferences</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="pills-account-tab" data-bs-toggle="pill" data-bs-target="#pills-account" type="button" role="tab"><i class="fas fa-user-circle me-2"></i> Account Details</button>
                        </li>
                    </ul>

                    <!-- TAB CONTENT -->
                    <div class="tab-content" id="pills-tabContent">

                        <!-- 1. SECURITY TAB (PASSWORD FORM) -->
                        <div class="tab-pane fade show active" id="pills-security" role="tabpanel">
                            <h5 class="fw-bold text-dark mb-2" style="font-size: 18px;">Update Password</h5>
                            <p class="text-muted fw-semibold small mb-4">Ensure your new password is secure. Protecting master access is critical.</p>
                            
                            <form method="POST" action="">
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Current Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-unlock-alt"></i></span>
                                        <input type="password" name="old_password" class="form-control" placeholder="Enter current password" required>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6 mb-4 mb-md-0">
                                        <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" name="new_password" class="form-control" placeholder="Create new password" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Confirm New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-check-circle"></i></span>
                                            <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- 🤖 NEW SECURITY CAPTCHA -->
                                <div class="mb-4 p-3 bg-light border rounded-3 border-secondary" style="border-color: #cbd5e1 !important;">
                                    <label class="form-label fw-bold small text-dark text-uppercase letter-spacing-1 mb-2"><i class="fas fa-robot text-primary me-2"></i>Human Verification</label>
                                    <div class="d-flex align-items-center">
                                        <div class="captcha-box">
                                            <?php echo $_SESSION['captcha_num1'] . " + " . $_SESSION['captcha_num2'] . " = ?"; ?>
                                        </div>
                                        <input type="number" name="captcha_input" class="form-control" placeholder="Enter sum" required style="border-left: none; border-radius: 0 10px 10px 0;">
                                    </div>
                                </div>

                                <div class="d-grid mt-2">
                                    <button type="submit" name="update_password" class="btn-gradient py-3" style="font-size: 16px;">
                                        <i class="fas fa-save me-2"></i> Save New Password
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- 2. PREFERENCES TAB (WORKING TOGGLES) -->
                        <div class="tab-pane fade" id="pills-preferences" role="tabpanel">
                            <h5 class="fw-bold text-dark mb-2" style="font-size: 18px;">System Preferences</h5>
                            <p class="text-muted fw-semibold small mb-4">Manage database-connected notifications and security options.</p>
                            
                            <form method="POST" action="">
                                <div class="p-3 border rounded-3 mb-3 bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1"><i class="fas fa-envelope text-primary me-2"></i> Email Notifications</h6>
                                        <small class="text-muted fw-semibold">Receive system emails for new student submissions.</small>
                                    </div>
                                    <div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" name="email_notifications" value="1" <?php echo $email_notifications ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                                
                                <div class="p-3 border rounded-3 mb-4 bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1"><i class="fas fa-shield-alt text-success me-2"></i> Two-Factor Authentication (2FA)</h6>
                                        <small class="text-muted fw-semibold">Require an OTP when logging in from new devices.</small>
                                    </div>
                                    <div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" name="two_factor_auth" value="1" <?php echo $two_factor_auth ? 'checked' : ''; ?>>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" name="update_preferences" class="btn-gradient px-4 py-2">
                                        <i class="fas fa-sync-alt me-2"></i> Save Preferences
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- 3. ACCOUNT DETAILS TAB (READ ONLY) -->
                        <div class="tab-pane fade" id="pills-account" role="tabpanel">
                            <h5 class="fw-bold text-dark mb-2" style="font-size: 18px;">Personal Information</h5>
                            <p class="text-muted fw-semibold small mb-4">Your core account details. Contact super-admin to change these fields.</p>
                            
                            <div class="row mb-4 g-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Full Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_name); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Admin ID / Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-id-badge"></i></span>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_data['email'] ?? ''); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Department</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-building"></i></span>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_data['department'] ?? 'Computer Engineering'); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">System Role</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user-cog"></i></span>
                                        <input type="text" class="form-control" value="Master Admin" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Script that keeps the correct tab active after form submission -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        <?php if (isset($_POST['update_preferences'])): ?>
            var triggerEl = document.querySelector('#pills-preferences-tab');
            var tab = new bootstrap.Tab(triggerEl);
            tab.show();
        <?php elseif (isset($_POST['update_password'])): ?>
            var triggerEl = document.querySelector('#pills-security-tab');
            var tab = new bootstrap.Tab(triggerEl);
            tab.show();
        <?php endif; ?>
    </script>
</body>
</html>