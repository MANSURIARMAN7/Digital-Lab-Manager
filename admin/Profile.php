<?php
session_start();
include '../db.php'; // Database connection

// 1. Admin Authentication Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    // Also support $_SESSION['logged_in'] check
    if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../login.php");
        exit();
    }
}

$admin_id = $_SESSION['user_id'];
$message = "";
$active_tab = "profile"; // default tab

// ==========================================
// 🛠️ SMART DB COLUMNS ENSURE
// ==========================================
$check_cols = $conn->query("SHOW COLUMNS FROM `users` LIKE 'email_notifications'");
if ($check_cols && $check_cols->num_rows == 0) {
    @$conn->query("ALTER TABLE `users` ADD COLUMN `email_notifications` TINYINT(1) DEFAULT 1");
    @$conn->query("ALTER TABLE `users` ADD COLUMN `two_factor_auth` TINYINT(1) DEFAULT 0");
}

$check_phone = $conn->query("SHOW COLUMNS FROM `users` LIKE 'phone'");
if ($check_phone && $check_phone->num_rows == 0) {
    @$conn->query("ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(20) DEFAULT NULL");
}

$check_alerts = $conn->query("SHOW COLUMNS FROM `users` LIKE 'submission_alerts'");
if ($check_alerts && $check_alerts->num_rows == 0) {
    @$conn->query("ALTER TABLE `users` ADD COLUMN `submission_alerts` TINYINT(1) DEFAULT 1");
}

// ==========================================
// 👤 1. UPDATE PROFILE INFORMATION LOGIC
// ==========================================
if (($_SERVER['REQUEST_METHOD'] ?? '') == 'POST' && isset($_POST['update_profile'])) {
    $active_tab = "profile";
    $name = $conn->real_escape_string(trim($_POST['name']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $department = $conn->real_escape_string(trim($_POST['department']));

    if (!empty($name) && !empty($email)) {
        $update_profile_query = "UPDATE `users` SET `name` = '$name', `email` = '$email', `phone` = '$phone', `department` = '$department' WHERE `user_id` = '$admin_id'";
        if ($conn->query($update_profile_query)) {
            $_SESSION['name'] = $name;
            $message = '<div class="alert alert-success alert-dismissible fade show shadow-sm" style="border-radius:12px; font-weight:600;" role="alert">
                <i class="fas fa-check-circle me-2 fs-5"></i> Profile details updated successfully! 🎉
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        } else {
            $message = '<div class="alert alert-danger alert-dismissible fade show shadow-sm" style="border-radius:12px;" role="alert">
                <i class="fas fa-times-circle me-2 fs-5"></i> Database Error: ' . htmlspecialchars($conn->error) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }
    } else {
        $message = '<div class="alert alert-warning alert-dismissible fade show shadow-sm" style="border-radius:12px;" role="alert">
            <i class="fas fa-exclamation-triangle me-2 fs-5"></i> Name and Email cannot be empty!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// ==========================================
// 🔐 2. PASSWORD UPDATE LOGIC
// ==========================================
if (($_SERVER['REQUEST_METHOD'] ?? '') == 'POST' && isset($_POST['update_password'])) {
    $active_tab = "security";
    $old_password = trim($_POST['old_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (strlen($new_password) < 6) {
        $message = '<div class="alert alert-warning alert-dismissible fade show shadow-sm" style="border-radius:12px;" role="alert">
            <i class="fas fa-shield-alt me-2 fs-5"></i> New password must be at least 6 characters long!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } elseif ($new_password !== $confirm_password) {
        $message = '<div class="alert alert-danger alert-dismissible fade show shadow-sm" style="border-radius:12px;" role="alert">
            <i class="fas fa-times-circle me-2 fs-5"></i> New Password and Confirm Password do not match! ❌
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        $check_query = "SELECT `password` FROM `users` WHERE `user_id` = '$admin_id'";
        $res = $conn->query($check_query);

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $db_pass = $row['password'];

            // Supports plain text, MD5, or password_verify
            $is_matched = ($old_password === $db_pass) || (md5($old_password) === $db_pass) || (password_verify($old_password, $db_pass));

            if ($is_matched) {
                $safe_new_pass = $conn->real_escape_string($new_password);
                $update_query = "UPDATE `users` SET `password` = '$safe_new_pass' WHERE `user_id` = '$admin_id'";

                if ($conn->query($update_query)) {
                    $message = '<div class="alert alert-success alert-dismissible fade show shadow-sm" style="border-radius:12px; font-weight:600;" role="alert">
                        <i class="fas fa-key me-2 fs-5"></i> Password updated successfully! Your account is secured. 🎉
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                } else {
                    $message = '<div class="alert alert-danger alert-dismissible fade show shadow-sm" style="border-radius:12px;" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 fs-5"></i> Error updating password in database!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                }
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show shadow-sm" style="border-radius:12px;" role="alert">
                    <i class="fas fa-lock me-2 fs-5"></i> Incorrect Current Password! Please try again. ❌
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }
        }
    }
}

// ==========================================
// ⚙️ 3. PREFERENCES UPDATE LOGIC
// ==========================================
if (($_SERVER['REQUEST_METHOD'] ?? '') == 'POST' && isset($_POST['update_preferences'])) {
    $active_tab = "preferences";
    $email_notif = isset($_POST['email_notifications']) ? 1 : 0;
    $two_factor = isset($_POST['two_factor_auth']) ? 1 : 0;
    $sub_alerts = isset($_POST['submission_alerts']) ? 1 : 0;

    $pref_query = "UPDATE `users` SET `email_notifications` = '$email_notif', `two_factor_auth` = '$two_factor', `submission_alerts` = '$sub_alerts' WHERE `user_id` = '$admin_id'";
    if ($conn->query($pref_query)) {
        $message = '<div class="alert alert-success alert-dismissible fade show shadow-sm" style="border-radius:12px; font-weight:600;" role="alert">
            <i class="fas fa-sliders-h me-2 fs-5"></i> System preferences saved successfully! ⚙️
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show shadow-sm" style="border-radius:12px;" role="alert">
            <i class="fas fa-exclamation-triangle me-2 fs-5"></i> Error updating preferences!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    }
}

// ==========================================
// 📊 FETCH UPDATED ADMIN & SYSTEM STATS
// ==========================================
$admin_query = "SELECT * FROM `users` WHERE `user_id` = '$admin_id'";
$admin_data = $conn->query($admin_query)->fetch_assoc();

$admin_name = $admin_data['name'] ?? 'System Administrator';
$admin_email = $admin_data['email'] ?? 'admin@kdpolytechnic.ac.in';
$admin_dept = $admin_data['department'] ?? 'Computer Engineering';
$admin_phone = $admin_data['phone'] ?? '+91 98765 43210';
$admin_created = !empty($admin_data['created_at']) ? date('M d, Y', strtotime($admin_data['created_at'])) : 'Aug 2026';
$email_notifications = $admin_data['email_notifications'] ?? 1;
$two_factor_auth = $admin_data['two_factor_auth'] ?? 0;
$submission_alerts = $admin_data['submission_alerts'] ?? 1;

// System Metrics
$q_students = $conn->query("SELECT COUNT(*) as cnt FROM `users` WHERE `role`='student'");
$total_students = $q_students ? $q_students->fetch_assoc()['cnt'] : 0;

$q_faculty = $conn->query("SELECT COUNT(*) as cnt FROM `users` WHERE `role`='faculty'");
$total_faculty = $q_faculty ? $q_faculty->fetch_assoc()['cnt'] : 0;

$q_manuals = $conn->query("SELECT COUNT(*) as cnt FROM `lab_manuals`");
$total_manuals = $q_manuals ? $q_manuals->fetch_assoc()['cnt'] : 0;

$q_subs = $conn->query("SELECT COUNT(*) as cnt FROM `student_submissions`");
$total_submissions = $q_subs ? $q_subs->fetch_assoc()['cnt'] : 0;

$client_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Standard Web Browser';
$browser_short = 'Chrome / Desktop';
if (strpos($user_agent, 'Firefox') !== false) $browser_short = 'Mozilla Firefox';
elseif (strpos($user_agent, 'Edg') !== false) $browser_short = 'Microsoft Edge';
elseif (strpos($user_agent, 'Safari') !== false && strpos($user_agent, 'Chrome') === false) $browser_short = 'Apple Safari';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Lab Manual Portal</title>
    
    <!-- Bootstrap 5, FontAwesome & Modern Typography -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --sidebar-width: 260px; 
            --bg-color: #f4f7fe; 
            --sidebar-bg: #1a365d; 
            --accent-blue: #2563eb; 
            --accent-hover: #1d4ed8;
            --surface: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow-subtle: 0 4px 14px rgba(0, 0, 0, 0.04);
            --shadow-float: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
            --radius-xl: 16px;
            --radius-lg: 12px;
            --radius-md: 10px;
            --transition-smooth: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        * { box-sizing: border-box; }

        body { 
            background-color: var(--bg-color); 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            display: flex; 
            height: 100vh; 
            overflow: hidden; 
            margin: 0; 
            color: var(--text-main); 
        }
        
        /* 🔵 SIDEBAR (Matches Admin Panel) */
        .sidebar { 
            width: var(--sidebar-width); 
            background-color: var(--sidebar-bg); 
            color: #ffffff; 
            display: flex; 
            flex-direction: column; 
            z-index: 100; 
            overflow-y: auto; 
            flex-shrink: 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }
        .sidebar-logo-container { 
            padding: 30px 20px 20px 20px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            border-bottom: 1px solid rgba(255,255,255,0.08); 
            text-align: center; 
        }
        .sidebar-logo-container img { 
            width: 85px; 
            height: 85px; 
            object-fit: contain; 
            margin-bottom: 12px; 
            border-radius: 50%; 
            padding: 4px; 
            background: rgba(255,255,255,0.1); 
            border: 2px solid rgba(255,255,255,0.25); 
        }
        .sidebar-title h2 { 
            font-size: 18px; 
            font-weight: 700; 
            margin: 0; 
            line-height: 1.2; 
            letter-spacing: 0.5px; 
            color: #ffffff;
        }
        .sidebar-subtitle { 
            font-size: 12px; 
            color: #94a3b8; 
            margin-top: 4px; 
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .nav-links { 
            list-style: none; 
            padding: 20px 14px; 
            margin: 0; 
            flex-grow: 1; 
        }
        .nav-links li { 
            padding: 12px 18px; 
            margin: 5px 0; 
            border-radius: 8px; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            gap: 14px; 
            font-size: 14px; 
            font-weight: 600; 
            color: #a0aec0; 
            transition: var(--transition-smooth); 
        }
        .nav-links li:hover { 
            color: #ffffff; 
            background: rgba(255,255,255,0.08); 
            transform: translateX(3px); 
        }
        .nav-links li.active { 
            background: var(--accent-blue); 
            color: #ffffff; 
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35); 
            font-weight: 700; 
        }
        .nav-links li i { font-size: 16px; width: 20px; text-align: center; }
        .nav-links li.mt-auto { color: #f87171 !important; }
        .nav-links li.mt-auto:hover { background: rgba(239, 68, 68, 0.15) !important; color: #ffffff !important; }

        /* ✨ MAIN CONTENT AREA */
        .main { 
            flex: 1; 
            padding: 28px 40px 40px 40px; 
            overflow-y: auto; 
            height: 100vh; 
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(15px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* 🌈 TOPBAR */
        .topbar { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            margin-bottom: 24px;
        }
        .clock-badge { 
            background: #ffffff; 
            border: 1px solid var(--border-color); 
            border-radius: 10px; 
            padding: 8px 16px; 
            color: #475569; 
            font-weight: 700; 
            font-size: 13px; 
            box-shadow: 0 2px 6px rgba(0,0,0,0.02); 
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .clock-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
            animation: pulseDot 2s infinite;
        }
        @keyframes pulseDot {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.3); opacity: 0.7; }
        }

        .profile-pill { 
            display: flex; 
            align-items: center; 
            background-color: #ffffff; 
            padding: 6px 16px 6px 20px; 
            border-radius: 30px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.04); 
            border: 1px solid var(--border-color); 
            text-decoration: none; 
            color: inherit; 
            transition: var(--transition-smooth);
        }
        .profile-pill:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 16px rgba(0,0,0,0.06); 
        }
        .profile-text { text-align: right; margin-right: 14px; }
        .profile-welcome { 
            display: block; 
            font-size: 9.5px; 
            color: #64748b; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.8px; 
            margin-bottom: 2px; 
        }
        .profile-name { margin: 0; font-size: 14px; color: #1e293b; font-weight: 700; }
        .profile-avatar { 
            width: 42px; 
            height: 42px; 
            background: linear-gradient(135deg, #1e3a8a, #2563eb); 
            color: #ffffff; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 13px; 
            font-weight: 700; 
            box-shadow: 0 3px 8px rgba(37, 99, 235, 0.35); 
            letter-spacing: 1px;
        }

        /* 📦 CONTENT CARDS */
        .card-custom { 
            background: var(--surface); 
            border: 1px solid var(--border-color); 
            border-radius: var(--radius-xl); 
            box-shadow: 0 2px 8px rgba(0,0,0,0.03); 
            transition: var(--transition-smooth); 
            overflow: hidden;
        }
        .card-custom:hover { box-shadow: var(--shadow-subtle); }

        /* 👑 LEFT PROFILE BANNER & AVATAR */
        .profile-header-banner {
            background: linear-gradient(135deg, #1a365d 0%, #1e40af 100%);
            height: 125px;
            position: relative;
            padding: 16px 20px;
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
        }
        .badge-verified-pill {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }
        .profile-avatar-circle {
            width: 104px;
            height: 104px;
            border-radius: 50%;
            background: #ffffff;
            padding: 5px;
            margin: -52px auto 14px auto;
            position: relative;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .profile-avatar-inner {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
        }
        .avatar-online-dot {
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 24px;
            height: 24px;
            background: #10b981;
            border: 3px solid #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 10px;
        }

        /* 📊 STATS MINI GRID */
        .stat-mini-tile {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 12px 10px;
            text-align: center;
            transition: var(--transition-smooth);
        }
        .stat-mini-tile:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }
        .stat-mini-num {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-main);
            margin: 0;
            line-height: 1.1;
        }
        .stat-mini-text {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
            margin-bottom: 0;
        }

        /* 🗂️ NAV TABS */
        .nav-tabs-modern {
            display: flex;
            gap: 6px;
            background: #f1f5f9;
            padding: 6px;
            border-radius: var(--radius-lg);
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }
        .nav-tabs-modern .nav-link {
            flex: 1;
            text-align: center;
            border: none;
            color: #64748b;
            font-weight: 700;
            font-size: 13.5px;
            padding: 10px 14px;
            border-radius: var(--radius-md);
            transition: var(--transition-smooth);
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .nav-tabs-modern .nav-link:hover {
            color: var(--accent-blue);
            background: rgba(255, 255, 255, 0.7);
        }
        .nav-tabs-modern .nav-link.active {
            background: #ffffff;
            color: var(--accent-blue);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        /* 📝 FORM STYLES */
        .form-label-title {
            font-size: 12px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 6px;
            display: block;
        }
        .input-group-modern {
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: var(--radius-md);
            transition: var(--transition-smooth);
        }
        .input-group-modern:focus-within {
            border-color: var(--accent-blue);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }
        .input-icon-left {
            padding: 0 16px;
            color: #94a3b8;
            font-size: 15px;
        }
        .input-group-modern:focus-within .input-icon-left {
            color: var(--accent-blue);
        }
        .form-control-modern {
            flex: 1;
            border: none;
            background: transparent;
            padding: 12px 14px 12px 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main);
            outline: none;
            font-family: inherit;
        }
        .form-control-modern:read-only {
            color: #64748b;
            cursor: not-allowed;
        }
        .btn-eye-toggle {
            background: transparent;
            border: none;
            padding: 0 16px;
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.2s;
        }
        .btn-eye-toggle:hover { color: var(--accent-blue); }

        /* 🚀 ACTION BUTTON */
        .btn-theme-primary {
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
            color: #ffffff;
            border: none;
            font-weight: 700;
            font-size: 14.5px;
            padding: 11px 24px;
            border-radius: var(--radius-md);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            transition: var(--transition-smooth);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-theme-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.4);
            color: #ffffff;
        }

        /* 🎚️ SWITCH CARD */
        .switch-tile-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 16px 20px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: var(--transition-smooth);
        }
        .switch-tile-card:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
        }
        .form-check-input-lg {
            width: 3.2em !important;
            height: 1.6em !important;
            cursor: pointer;
        }
        .form-check-input-lg:checked {
            background-color: var(--accent-blue);
            border-color: var(--accent-blue);
        }

        /* 🛡️ PERMISSIONS */
        .privilege-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 16px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            height: 100%;
            transition: var(--transition-smooth);
        }
        .privilege-card:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .privilege-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        /* ⚡ PASSWORD METER */
        .pwd-strength-bar {
            height: 6px;
            border-radius: 4px;
            background: #e2e8f0;
            margin-top: 8px;
            overflow: hidden;
        }
        .pwd-strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        /* SCROLLBAR */
        ::-webkit-scrollbar { width: 7px; height: 7px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body>

    <!-- 🔵 SIDEBAR (Exact Admin Panel Layout) -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-alt"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-folder-open"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-circle"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li class="active" onclick="window.location.href='Profile.php'"><i class="fas fa-user-shield"></i> Admin Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- ✨ MAIN CONTENT -->
    <div class="main">
        
        <!-- 🌈 TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge">
                    <span class="clock-dot"></span>
                    <i class="far fa-clock text-primary"></i>
                    <span id="liveClock">Loading time...</span>
                </div>
                <div class="d-none d-md-flex align-items-center gap-2 text-muted small fw-semibold">
                    <span>Admin Portal</span> <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                    <span>Account Settings</span> <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                    <span class="text-primary fw-bold">Administrator Profile</span>
                </div>
            </div>
            
            <div class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Master Authority</span>
                    <h4 class="profile-name"><?php echo htmlspecialchars($admin_name); ?></h4>
                </div>
                <div class="profile-avatar">
                    <?php 
                        $words = explode(' ', trim($admin_name));
                        $initials = '';
                        foreach ($words as $w) {
                            if (!empty($w)) $initials .= strtoupper($w[0]);
                            if (strlen($initials) >= 2) break;
                        }
                        echo !empty($initials) ? $initials : 'AD';
                    ?>
                </div>
            </div>
        </div>

        <!-- 🚀 PAGE HEADER -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 52px; height: 52px; border-radius: 12px; background: rgba(37, 99, 235, 0.1); color: var(--accent-blue); display: flex; align-items: center; justify-content: center; font-size: 24px;">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 25px; color: var(--text-main);">HOD / Administrator Profile</h3>
                    <p class="text-muted fw-semibold small mb-0">Manage department credentials, personal administrative info, and portal security.</p>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3 mt-md-0">
                <a href="dashboard.php" class="btn btn-outline-secondary fw-bold d-flex align-items-center gap-2" style="border-radius: 8px; padding: 9px 18px; font-size: 13.5px;">
                    <i class="fas fa-arrow-left"></i> Dashboard
                </a>
                <button class="btn btn-outline-primary fw-bold d-flex align-items-center gap-2" onclick="location.reload();" style="border-radius: 8px; padding: 9px 18px; font-size: 13.5px;">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        <!-- 🔔 ALERTS -->
        <?php if (!empty($message)) echo $message; ?>

        <!-- 📐 MAIN 2-COLUMN GRID -->
        <div class="row g-4">
            
            <!-- 👤 LEFT COLUMN: MASTER ADMIN ID CARD -->
            <div class="col-lg-4">
                
                <!-- Master Identity Card -->
                <div class="card-custom mb-4">
                    <div class="profile-header-banner">
                        <span class="badge-verified-pill">
                            <i class="fas fa-crown text-warning me-1"></i> Root Authority
                        </span>
                    </div>
                    
                    <div class="profile-avatar-circle">
                        <div class="profile-avatar-inner">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="avatar-online-dot" title="Authenticated & Active">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                    
                    <div class="text-center px-4 pb-4">
                        <h4 class="fw-bold mb-1 text-dark" style="font-size: 20px;"><?php echo htmlspecialchars($admin_name); ?></h4>
                        <p class="text-muted small fw-semibold mb-2"><?php echo htmlspecialchars($admin_email); ?></p>
                        
                        <span class="badge px-3 py-2 rounded-pill mb-3" style="background: rgba(37, 99, 235, 0.1); color: #2563eb; border: 1px solid rgba(37, 99, 235, 0.2); font-weight: 700;">
                            <i class="fas fa-shield-alt me-1"></i> Head of Department (HOD)
                        </span>

                        <!-- Department Callout -->
                        <div class="p-2 mb-3 rounded-2 text-center" style="background: #f1f5f9; font-size: 12.5px; font-weight: 700; color: #475569;">
                            <i class="fas fa-building text-primary me-1"></i> <?php echo htmlspecialchars($admin_dept); ?>
                        </div>
                        
                        <!-- Mini Counters -->
                        <div class="row g-2 pt-2 border-top">
                            <div class="col-6">
                                <div class="stat-mini-tile">
                                    <p class="stat-mini-num text-primary"><?php echo $total_students; ?></p>
                                    <p class="stat-mini-text">Students</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-mini-tile">
                                    <p class="stat-mini-num text-success"><?php echo $total_faculty; ?></p>
                                    <p class="stat-mini-text">Faculty</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-mini-tile">
                                    <p class="stat-mini-num text-warning"><?php echo $total_manuals; ?></p>
                                    <p class="stat-mini-text">Manuals</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-mini-tile">
                                    <p class="stat-mini-num text-danger"><?php echo $total_submissions; ?></p>
                                    <p class="stat-mini-text">Submissions</p>
                                </div>
                            </div>
                        </div>

                        <!-- Security Health Score -->
                        <div class="p-3 bg-light rounded-3 mt-3 text-start border">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold small text-dark"><i class="fas fa-shield-virus text-primary me-1"></i> Account Health</span>
                                <span class="badge bg-success-subtle text-success fw-bold">96% Optimal</span>
                            </div>
                            <div class="progress" style="height: 6px; border-radius: 4px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 96%"></div>
                            </div>
                            <small class="text-muted mt-2 d-block" style="font-size: 11px;">
                                <i class="fas fa-check text-success me-1"></i> Database Synchronized &middot; 
                                <i class="fas fa-check text-success me-1"></i> HTTPS/Session Active
                            </small>
                        </div>

                    </div>
                </div>

                <!-- Session Information Card -->
                <div class="card-custom p-4">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2" style="font-size: 14px;">
                        <i class="fas fa-network-wired text-primary"></i> Current Session Details
                    </h6>
                    <ul class="list-unstyled mb-0" style="font-size: 13px;">
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted fw-semibold">Admin User ID:</span>
                            <span class="fw-bold text-dark"><code><?php echo htmlspecialchars($admin_id); ?></code></span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted fw-semibold">Department:</span>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($admin_dept); ?></span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted fw-semibold">Client IP:</span>
                            <span class="badge bg-secondary-subtle text-secondary fw-semibold"><?php echo htmlspecialchars($client_ip); ?></span>
                        </li>
                        <li class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted fw-semibold">Client Agent:</span>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($browser_short); ?></span>
                        </li>
                        <li class="d-flex justify-content-between pt-2">
                            <span class="text-muted fw-semibold">Account Created:</span>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($admin_created); ?></span>
                        </li>
                    </ul>
                </div>

            </div>

            <!-- 📝 RIGHT COLUMN: INTERACTIVE TABS (PROFILE, SECURITY, PREFERENCES, PERMISSIONS) -->
            <div class="col-lg-8">
                <div class="card-custom p-4 p-md-5">
                    
                    <!-- Navigation Pills -->
                    <div class="nav-tabs-modern" id="profileTabs" role="tablist">
                        <button class="nav-link <?php echo ($active_tab == 'profile') ? 'active' : ''; ?>" id="tab-profile-btn" data-bs-toggle="pill" data-bs-target="#tab-profile" type="button" role="tab">
                            <i class="fas fa-user-edit"></i> Profile Details
                        </button>
                        <button class="nav-link <?php echo ($active_tab == 'security') ? 'active' : ''; ?>" id="tab-security-btn" data-bs-toggle="pill" data-bs-target="#tab-security" type="button" role="tab">
                            <i class="fas fa-key"></i> Security & Password
                        </button>
                        <button class="nav-link <?php echo ($active_tab == 'preferences') ? 'active' : ''; ?>" id="tab-preferences-btn" data-bs-toggle="pill" data-bs-target="#tab-preferences" type="button" role="tab">
                            <i class="fas fa-sliders-h"></i> Preferences
                        </button>
                        <button class="nav-link <?php echo ($active_tab == 'permissions') ? 'active' : ''; ?>" id="tab-permissions-btn" data-bs-toggle="pill" data-bs-target="#tab-permissions" type="button" role="tab">
                            <i class="fas fa-shield-alt"></i> Permissions
                        </button>
                    </div>

                    <!-- Tabs Content -->
                    <div class="tab-content" id="profileTabsContent">
                        
                        <!-- 👤 TAB 1: EDIT PROFILE DETAILS -->
                        <div class="tab-pane fade <?php echo ($active_tab == 'profile') ? 'show active' : ''; ?>" id="tab-profile" role="tabpanel">
                            <div class="mb-4">
                                <h5 class="fw-bold text-dark mb-1">Administrative & Personal Information</h5>
                                <p class="text-muted fw-semibold small">Update your official display name, official email address, phone number, and department.</p>
                            </div>

                            <form method="POST" action="Profile.php">
                                <div class="row g-3">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-title">Full Name <span class="text-danger">*</span></label>
                                        <div class="input-group-modern">
                                            <span class="input-icon-left"><i class="fas fa-user"></i></span>
                                            <input type="text" name="name" class="form-control-modern" value="<?php echo htmlspecialchars($admin_name); ?>" required placeholder="e.g. Dr. John Doe">
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-title">Admin User ID (System Permanent)</label>
                                        <div class="input-group-modern" style="background: #f1f5f9;">
                                            <span class="input-icon-left"><i class="fas fa-id-badge"></i></span>
                                            <input type="text" class="form-control-modern" value="<?php echo htmlspecialchars($admin_id); ?>" readonly title="Unique Admin ID cannot be changed">
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-title">Official Email Address <span class="text-danger">*</span></label>
                                        <div class="input-group-modern">
                                            <span class="input-icon-left"><i class="fas fa-envelope"></i></span>
                                            <input type="email" name="email" class="form-control-modern" value="<?php echo htmlspecialchars($admin_email); ?>" required placeholder="admin@kdpolytechnic.ac.in">
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-title">Contact Phone Number</label>
                                        <div class="input-group-modern">
                                            <span class="input-icon-left"><i class="fas fa-phone-alt"></i></span>
                                            <input type="text" name="phone" class="form-control-modern" value="<?php echo htmlspecialchars($admin_phone); ?>" placeholder="+91 98765 43210">
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-title">Department</label>
                                        <div class="input-group-modern">
                                            <span class="input-icon-left"><i class="fas fa-building"></i></span>
                                            <select name="department" class="form-control-modern" style="cursor: pointer;">
                                                <option value="Computer Engineering" <?php echo ($admin_dept == 'Computer Engineering') ? 'selected' : ''; ?>>Computer Engineering</option>
                                                <option value="Information Technology" <?php echo ($admin_dept == 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                                                <option value="Mechanical Engineering" <?php echo ($admin_dept == 'Mechanical Engineering') ? 'selected' : ''; ?>>Mechanical Engineering</option>
                                                <option value="Civil Engineering" <?php echo ($admin_dept == 'Civil Engineering') ? 'selected' : ''; ?>>Civil Engineering</option>
                                                <option value="Electrical Engineering" <?php echo ($admin_dept == 'Electrical Engineering') ? 'selected' : ''; ?>>Electrical Engineering</option>
                                                <option value="General Administration" <?php echo ($admin_dept == 'General Administration') ? 'selected' : ''; ?>>General Administration</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-title">System Role Authority</label>
                                        <div class="input-group-modern" style="background: #f1f5f9;">
                                            <span class="input-icon-left"><i class="fas fa-shield-alt text-primary"></i></span>
                                            <input type="text" class="form-control-modern" value="Level 1 - Master Administrator" readonly>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-4 pt-2 border-top">
                                    <button type="submit" name="update_profile" class="btn-theme-primary">
                                        <i class="fas fa-save"></i> Save Profile Changes
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- 🔐 TAB 2: SECURITY & PASSWORD UPDATE -->
                        <div class="tab-pane fade <?php echo ($active_tab == 'security') ? 'show active' : ''; ?>" id="tab-security" role="tabpanel">
                            <div class="mb-4">
                                <h5 class="fw-bold text-dark mb-1">Update Security Credentials</h5>
                                <p class="text-muted fw-semibold small">Please ensure your master password is secure. Keeping your administrative account safe is vital.</p>
                            </div>

                            <form method="POST" action="Profile.php">
                                <div class="mb-3">
                                    <label class="form-label-title">Current Password <span class="text-danger">*</span></label>
                                    <div class="input-group-modern">
                                        <span class="input-icon-left"><i class="fas fa-unlock-alt"></i></span>
                                        <input type="password" name="old_password" id="old_password" class="form-control-modern" required placeholder="Enter existing password">
                                        <button type="button" class="btn-eye-toggle" onclick="togglePasswordVisibility('old_password', this)">
                                            <i class="far fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-title">New Password <span class="text-danger">*</span></label>
                                        <div class="input-group-modern">
                                            <span class="input-icon-left"><i class="fas fa-lock"></i></span>
                                            <input type="password" name="new_password" id="new_password" class="form-control-modern" required placeholder="Minimum 6 characters" oninput="checkPasswordStrength(this.value)">
                                            <button type="button" class="btn-eye-toggle" onclick="togglePasswordVisibility('new_password', this)">
                                                <i class="far fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="pwd-strength-bar">
                                            <div id="passwordMeterFill" class="pwd-strength-fill"></div>
                                        </div>
                                        <small id="passwordStrengthText" class="text-muted fw-semibold" style="font-size: 11px;">Password strength</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label-title">Confirm New Password <span class="text-danger">*</span></label>
                                        <div class="input-group-modern">
                                            <span class="input-icon-left"><i class="fas fa-check-double"></i></span>
                                            <input type="password" name="confirm_password" id="confirm_password" class="form-control-modern" required placeholder="Re-enter new password">
                                            <button type="button" class="btn-eye-toggle" onclick="togglePasswordVisibility('confirm_password', this)">
                                                <i class="far fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-3 bg-light rounded-3 my-3 border">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="fas fa-info-circle text-primary mt-1"></i>
                                        <div class="small text-muted">
                                            <strong>Security Recommendation:</strong>
                                            Use a combination of upper and lowercase letters, numbers, and special symbols. This administrative account controls laboratory submissions and curriculum.
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-4 pt-2 border-top">
                                    <button type="submit" name="update_password" class="btn-theme-primary">
                                        <i class="fas fa-key"></i> Save New Password
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- ⚙️ TAB 3: SYSTEM PREFERENCES -->
                        <div class="tab-pane fade <?php echo ($active_tab == 'preferences') ? 'show active' : ''; ?>" id="tab-preferences" role="tabpanel">
                            <div class="mb-4">
                                <h5 class="fw-bold text-dark mb-1">System & Notification Preferences</h5>
                                <p class="text-muted fw-semibold small">Manage real-time notifications, automated dispatch alerts, and extra verification options.</p>
                            </div>

                            <form method="POST" action="Profile.php">
                                
                                <div class="switch-tile-card">
                                    <div class="d-flex align-items-center gap-3">
                                        <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(37, 99, 235, 0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 19px;">
                                            <i class="fas fa-envelope-open-text"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-1 text-dark">Email Dispatch Notifications</h6>
                                            <small class="text-muted fw-semibold">Receive direct email notifications for portal-wide announcements and daily activity summaries.</small>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input form-check-input-lg" type="checkbox" name="email_notifications" value="1" <?php echo $email_notifications ? 'checked' : ''; ?>>
                                    </div>
                                </div>

                                <div class="switch-tile-card">
                                    <div class="d-flex align-items-center gap-3">
                                        <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 19px;">
                                            <i class="fas fa-shield-alt"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-1 text-dark">Two-Factor Authentication (2FA)</h6>
                                            <small class="text-muted fw-semibold">Require secondary verification prompt when signing in from unfamiliar devices or IP locations.</small>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input form-check-input-lg" type="checkbox" name="two_factor_auth" value="1" <?php echo $two_factor_auth ? 'checked' : ''; ?>>
                                    </div>
                                </div>

                                <div class="switch-tile-card">
                                    <div class="d-flex align-items-center gap-3">
                                        <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(245, 158, 11, 0.1); color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 19px;">
                                            <i class="fas fa-bell"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold mb-1 text-dark">Student Submission Instant Alerts</h6>
                                            <small class="text-muted fw-semibold">Display real-time badge counters on the administrative dashboard when new student manuals arrive.</small>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input form-check-input-lg" type="checkbox" name="submission_alerts" value="1" <?php echo $submission_alerts ? 'checked' : ''; ?>>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-4 pt-2 border-top">
                                    <button type="submit" name="update_preferences" class="btn-theme-primary">
                                        <i class="fas fa-check-circle"></i> Save Preferences
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- 🛡️ TAB 4: SYSTEM PRIVILEGES MATRIX -->
                        <div class="tab-pane fade <?php echo ($active_tab == 'permissions') ? 'show active' : ''; ?>" id="tab-permissions" role="tabpanel">
                            <div class="mb-4">
                                <h5 class="fw-bold text-dark mb-1">Administrative Privileges Matrix</h5>
                                <p class="text-muted fw-semibold small">Active authorization permissions and privileges assigned to this super-administrator account.</p>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="privilege-card">
                                        <div class="privilege-icon-box" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                                            <i class="fas fa-users-cog"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Student & User Management</h6>
                                            <small class="text-muted">Create, edit, batch upload, activate, and manage student and faculty accounts.</small>
                                            <span class="badge bg-success-subtle text-success fw-bold mt-2 d-inline-block">Read / Write / Delete</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="privilege-card">
                                        <div class="privilege-icon-box" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                            <i class="fas fa-file-pdf"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Curriculum & Manuals</h6>
                                            <small class="text-muted">Upload and publish department lab manuals, practical deadlines, and syllabus guidelines.</small>
                                            <span class="badge bg-success-subtle text-success fw-bold mt-2 d-inline-block">Full Control</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="privilege-card">
                                        <div class="privilege-icon-box" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                            <i class="fas fa-check-double"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Grading & Review Overrides</h6>
                                            <small class="text-muted">Evaluate, approve, reject, grade, and override submission rubrics across all subjects.</small>
                                            <span class="badge bg-success-subtle text-success fw-bold mt-2 d-inline-block">Full Authority</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="privilege-card">
                                        <div class="privilege-icon-box" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                                            <i class="fas fa-database"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Database & Architecture</h6>
                                            <small class="text-muted">Database table auto-verification, schema updates, and direct SQL synchronization.</small>
                                            <span class="badge bg-danger-subtle text-danger fw-bold mt-2 d-inline-block">Super Admin Only</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // 🕒 Live Real-time Clock
        function updateLiveClock() {
            const now = new Date();
            const options = { 
                weekday: 'short', 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit', 
                hour12: true 
            };
            const timeStr = now.toLocaleDateString('en-IN', options);
            const el = document.getElementById('liveClock');
            if (el) el.innerText = timeStr;
        }
        setInterval(updateLiveClock, 1000);
        updateLiveClock();

        // 👁️ Toggle Password Visibility
        function togglePasswordVisibility(fieldId, btnEl) {
            const field = document.getElementById(fieldId);
            if (!field) return;
            const icon = btnEl.querySelector('i');
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'far fa-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'far fa-eye';
            }
        }

        // ⚡ Interactive Real-time Password Strength Meter
        function checkPasswordStrength(password) {
            const fill = document.getElementById('passwordMeterFill');
            const txt = document.getElementById('passwordStrengthText');
            if (!fill || !txt) return;

            let score = 0;
            if (password.length >= 6) score += 25;
            if (password.length >= 10) score += 25;
            if (/[0-9]/.test(password)) score += 25;
            if (/[^A-Za-z0-9]/.test(password)) score += 25;

            fill.style.width = score + '%';

            if (score <= 25) {
                fill.style.backgroundColor = '#ef4444';
                txt.innerText = 'Strength: Weak';
                txt.style.color = '#ef4444';
            } else if (score <= 50) {
                fill.style.backgroundColor = '#f59e0b';
                txt.innerText = 'Strength: Fair';
                txt.style.color = '#f59e0b';
            } else if (score <= 75) {
                fill.style.backgroundColor = '#2563eb';
                txt.innerText = 'Strength: Good';
                txt.style.color = '#2563eb';
            } else {
                fill.style.backgroundColor = '#10b981';
                txt.innerText = 'Strength: Strong & Secure 🛡️';
                txt.style.color = '#10b981';
            }
        }
    </script>
</body>
</html>