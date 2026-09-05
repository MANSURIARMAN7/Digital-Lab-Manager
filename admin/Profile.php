<?php
session_start();
include '../db.php'; // Database connection

// Check agar admin logged in hai ya nahi
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$message = "";

// ==========================================
// 🛠️ SMART DB AUTO-FIX FOR PREFERENCES
// ==========================================
// Check if preference columns exist in 'users' table, if not, create them!
$check_cols = $conn->query("SHOW COLUMNS FROM users LIKE 'email_notifications'");
if ($check_cols->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN email_notifications TINYINT(1) DEFAULT 1");
    $conn->query("ALTER TABLE users ADD COLUMN two_factor_auth TINYINT(1) DEFAULT 0");
}

// ==========================================
// 🚀 PASSWORD UPDATE LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
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
                    $message = '<div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius:10px; font-weight:600;" role="alert"><i class="fas fa-check-circle me-2"></i> Password updated successfully! 🎉<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                } else {
                    $message = '<div class="alert alert-danger alert-dismissible fade show mb-4" style="border-radius:10px;" role="alert"><i class="fas fa-exclamation-triangle me-2"></i> Error updating password!</div>';
                }
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show mb-4" style="border-radius:10px;" role="alert"><i class="fas fa-times-circle me-2"></i> Incorrect Old Password! ❌<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        }
    } else {
        $message = '<div class="alert alert-warning alert-dismissible fade show mb-4" style="border-radius:10px; color:#92400e;" role="alert"><i class="fas fa-exclamation-circle me-2"></i> New Password and Confirm Password do not match! ⚠️<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// ==========================================
// ⚙️ PREFERENCES UPDATE LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_preferences'])) {
    // If checkbox is checked it sends value, otherwise it's not set in POST
    $email_notif = isset($_POST['email_notifications']) ? 1 : 0;
    $two_factor = isset($_POST['two_factor_auth']) ? 1 : 0;
    
    $pref_query = "UPDATE users SET email_notifications = '$email_notif', two_factor_auth = '$two_factor' WHERE user_id = '$admin_id'";
    if ($conn->query($pref_query)) {
        $message = '<div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius:10px; font-weight:600;" role="alert"><i class="fas fa-check-circle me-2"></i> System preferences saved to database! ⚙️<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show mb-4" style="border-radius:10px;" role="alert"><i class="fas fa-exclamation-triangle me-2"></i> Error updating preferences!</div>';
    }
}

// Fetch Admin Details (Refreshed after updates)
$admin_query = "SELECT * FROM users WHERE user_id = '$admin_id'";
$admin_data = $conn->query($admin_query)->fetch_assoc();
$admin_name = $admin_data['name'] ?? 'System Administrator';

// Default preference values if null
$email_notifications = $admin_data['email_notifications'] ?? 1;
$two_factor_auth = $admin_data['two_factor_auth'] ?? 0;

// Fetch Quick Stats for Profile Card
$q_students = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='student'");
$total_students = $q_students ? $q_students->fetch_assoc()['cnt'] : 0;

$q_faculty = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='faculty'");
$total_faculty = $q_faculty ? $q_faculty->fetch_assoc()['cnt'] : 0;
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
            --shadow-float: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            --radius-xl: 16px;
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        /* 🔥 PREMIUM BLUE SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; overflow-y: auto; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0; letter-spacing: 0.5px; color: #ffffff;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); border-left: 3px solid transparent; }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; border-left: 4px solid #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);}

        /* 📦 PROFILE CARDS */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 35px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); }
        .icon-box { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 26px; }
        .blue-box { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }

        /* PROFILE HEADER & AVATAR */
        .profile-header-card { overflow: hidden; padding: 0; }
        .bg-admin-header { background: linear-gradient(135deg, #1e3a8a 0%, #4338ca 100%); color: white; padding: 25px 20px 50px 20px; text-align: center; }
        .admin-avatar-large { 
            width: 110px; height: 110px; background: var(--surface); color: var(--primary); 
            display: flex; align-items: center; justify-content: center; font-size: 45px; 
            border-radius: 50%; margin: -55px auto 20px auto; box-shadow: 0 8px 20px rgba(0,0,0,0.1); border: 5px solid var(--surface);
            position: relative; z-index: 2;
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

        /* TOGGLE SWITCHES */
        .form-check-input:checked { background-color: var(--primary); border-color: var(--primary); }
        .form-check-input { width: 3em !important; height: 1.5em !important; cursor: pointer; }

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
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-pdf"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-inbox"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-double"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge">
                    <i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span>
                </div>
            </div>
            
            <div class="profile-pill" style="cursor: default;">
                <div class="profile-text">
                    <span class="profile-welcome">Welcome Back,</span>
                    <h4 class="profile-name">
                        <?php 
                            $name_parts = explode(' ', $admin_name);
                            echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : 'Admin';
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar">AD</div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 mt-2 page-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box blue-box"><i class="fas fa-cog"></i></div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);">Settings & Security</h3>
                    <p class="text-muted fw-semibold small mb-0">Manage your system credentials, view account details, and preferences.</p>
                </div>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary fw-bold" style="border-radius: 10px; padding: 10px 20px;">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>

        <?php echo $message; // Alerts ?>

        <div class="row g-4">
            
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
                        <p class="text-muted fw-semibold mb-3 small">Role: System Administrator</p>
                        
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

            <div class="col-md-8">
                <div class="content-box h-100">
                    
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

                    <div class="tab-content" id="pills-tabContent">
                        
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

                                <div class="d-grid mt-2">
                                    <button type="submit" name="update_password" class="btn-gradient py-3" style="font-size: 16px;">
                                        <i class="fas fa-save me-2"></i> Save New Password
                                    </button>
                                </div>
                            </form>
                        </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live Clock Script
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Keep correct tab active if preference was updated
        <?php if (isset($_POST['update_preferences'])): ?>
            var triggerEl = document.querySelector('#pills-preferences-tab');
            var tab = new bootstrap.Tab(triggerEl);
            tab.show();
        <?php endif; ?>
    </script>
</body>
</html>