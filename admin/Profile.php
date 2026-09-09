<?php
session_start();
include '../db.php'; // Database connection

// 1. Admin Authentication Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../login.php");
        exit();
    }
}

$admin_id = $_SESSION['user_id'];
$message = "";
$active_tab = "profile"; // default tab

// Directory for profile pictures
$upload_dir = "../uploads/profiles/";
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

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

$check_pic = $conn->query("SHOW COLUMNS FROM `users` LIKE 'profile_pic'");
if ($check_pic && $check_pic->num_rows == 0) {
    @$conn->query("ALTER TABLE `users` ADD COLUMN `profile_pic` VARCHAR(255) DEFAULT NULL");
}

// ==========================================
// 🗑️ REMOVE PROFILE PHOTO LOGIC
// ==========================================
if (($_SERVER['REQUEST_METHOD'] ?? '') == 'POST' && isset($_POST['remove_photo'])) {
    $active_tab = "profile";
    $old_pic_q = $conn->query("SELECT `profile_pic` FROM `users` WHERE `user_id` = '$admin_id'");
    if ($old_pic_q && $old_pic_q->num_rows > 0) {
        $old_pic = $old_pic_q->fetch_assoc()['profile_pic'] ?? '';
        if (!empty($old_pic) && file_exists($upload_dir . $old_pic)) {
            @unlink($upload_dir . $old_pic);
        }
    }
    $conn->query("UPDATE `users` SET `profile_pic` = NULL WHERE `user_id` = '$admin_id'");
    $message = '<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:12px; background: rgba(16, 185, 129, 0.12); color: #065f46; font-weight:700;" role="alert">
        <i class="fas fa-trash-alt me-2 fs-5"></i> Profile picture removed successfully! Reverted to default avatar.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}

// ==========================================
// 👤 1. UPDATE PROFILE & PHOTO LOGIC
// ==========================================
if (($_SERVER['REQUEST_METHOD'] ?? '') == 'POST' && isset($_POST['update_profile'])) {
    $active_tab = "profile";
    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $department = $conn->real_escape_string(trim($_POST['department'] ?? ''));

    $new_pic_name = null;
    $upload_ok = true;

    // Handle Profile Photo Upload if provided
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        $file_tmp = $_FILES['profile_pic']['tmp_name'];
        $file_name = $_FILES['profile_pic']['name'];
        $file_size = $_FILES['profile_pic']['size'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_exts)) {
            $message = '<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:12px; background: rgba(239, 68, 68, 0.12); color: #991b1b; font-weight:700;" role="alert">
                <i class="fas fa-times-circle me-2 fs-5"></i> Only JPG, JPEG, PNG, and WEBP image formats are supported!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            $upload_ok = false;
        } elseif ($file_size > 5 * 1024 * 1024) {
            $message = '<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:12px; background: rgba(239, 68, 68, 0.12); color: #991b1b; font-weight:700;" role="alert">
                <i class="fas fa-times-circle me-2 fs-5"></i> Profile picture size must be under 5MB!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            $upload_ok = false;
        } else {
            $new_pic_name = "admin_" . preg_replace('/[^a-zA-Z0-9_-]/', '', $admin_id) . "_" . time() . "." . $ext;
            $target_path = $upload_dir . $new_pic_name;

            if (move_uploaded_file($file_tmp, $target_path)) {
                // Delete existing old photo
                $old_pic_q = $conn->query("SELECT `profile_pic` FROM `users` WHERE `user_id` = '$admin_id'");
                if ($old_pic_q && $old_pic_q->num_rows > 0) {
                    $old_pic = $old_pic_q->fetch_assoc()['profile_pic'] ?? '';
                    if (!empty($old_pic) && file_exists($upload_dir . $old_pic)) {
                        @unlink($upload_dir . $old_pic);
                    }
                }
            } else {
                $upload_ok = false;
                $message = '<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:12px; background: rgba(239, 68, 68, 0.12); color: #991b1b; font-weight:700;" role="alert">
                    <i class="fas fa-times-circle me-2 fs-5"></i> Failed to save uploaded image. Check folder permissions!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }
        }
    }

    if ($upload_ok) {
        if (!empty($name) && !empty($email)) {
            $pic_clause = ($new_pic_name !== null) ? ", `profile_pic` = '$new_pic_name'" : "";
            $update_profile_query = "UPDATE `users` SET `name` = '$name', `email` = '$email', `phone` = '$phone', `department` = '$department' $pic_clause WHERE `user_id` = '$admin_id'";
            if ($conn->query($update_profile_query)) {
                $_SESSION['name'] = $name;
                $message = '<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:12px; background: rgba(16, 185, 129, 0.12); color: #065f46; font-weight:700;" role="alert">
                    <i class="fas fa-check-circle me-2 fs-5"></i> Profile details & photo updated successfully! 🎉
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:12px; background: rgba(239, 68, 68, 0.12); color: #991b1b; font-weight:700;" role="alert">
                    <i class="fas fa-times-circle me-2 fs-5"></i> Database Error: ' . htmlspecialchars($conn->error) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>';
            }
        } else {
            $message = '<div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:12px; background: rgba(245, 158, 11, 0.12); color: #92400e; font-weight:700;" role="alert">
                <i class="fas fa-exclamation-triangle me-2 fs-5"></i> Name and Email cannot be empty!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }
    }
}

// ==========================================
// 🔐 2. PASSWORD UPDATE LOGIC
// ==========================================
if (($_SERVER['REQUEST_METHOD'] ?? '') == 'POST' && isset($_POST['update_password'])) {
    $active_tab = "security";
    $old_password = trim($_POST['old_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (strlen($new_password) < 6) {
        $message = '<div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:12px; background: rgba(245, 158, 11, 0.12); color: #92400e; font-weight:700;" role="alert">
            <i class="fas fa-shield-alt me-2 fs-5"></i> New password must be at least 6 characters long!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } elseif ($new_password !== $confirm_password) {
        $message = '<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:12px; background: rgba(239, 68, 68, 0.12); color: #991b1b; font-weight:700;" role="alert">
            <i class="fas fa-times-circle me-2 fs-5"></i> New Password and Confirm Password do not match! ❌
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        $check_query = "SELECT `password` FROM `users` WHERE `user_id` = '$admin_id'";
        $res = $conn->query($check_query);

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $db_pass = $row['password'];

            $is_matched = ($old_password === $db_pass) || (md5($old_password) === $db_pass) || (password_verify($old_password, $db_pass));

            if ($is_matched) {
                $safe_new_pass = $conn->real_escape_string($new_password);
                $update_query = "UPDATE `users` SET `password` = '$safe_new_pass' WHERE `user_id` = '$admin_id'";

                if ($conn->query($update_query)) {
                    $message = '<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:12px; background: rgba(16, 185, 129, 0.12); color: #065f46; font-weight:700;" role="alert">
                        <i class="fas fa-key me-2 fs-5"></i> Password updated successfully! Your account is secured. 🎉
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                } else {
                    $message = '<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:12px; background: rgba(239, 68, 68, 0.12); color: #991b1b; font-weight:700;" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 fs-5"></i> Error updating password in database!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
                }
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:12px; background: rgba(239, 68, 68, 0.12); color: #991b1b; font-weight:700;" role="alert">
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
        $message = '<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:12px; background: rgba(16, 185, 129, 0.12); color: #065f46; font-weight:700;" role="alert">
            <i class="fas fa-sliders-h me-2 fs-5"></i> System preferences saved successfully! ⚙️
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" style="border-radius:12px; background: rgba(239, 68, 68, 0.12); color: #991b1b; font-weight:700;" role="alert">
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

// Profile Photo Resolution
$profile_pic = $admin_data['profile_pic'] ?? null;
$has_photo = (!empty($profile_pic) && file_exists($upload_dir . $profile_pic));
$photo_url = $has_photo ? $upload_dir . htmlspecialchars($profile_pic) : '';

// System statistics matching Dashboard
$st_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$total_students = $st_res ? $st_res->fetch_assoc()['total'] : 0;

$fac_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'faculty'");
$total_faculty = $fac_res ? $fac_res->fetch_assoc()['total'] : 0;

$sub_res = $conn->query("SELECT COUNT(*) as total FROM student_submissions");
$total_submissions = $sub_res ? $sub_res->fetch_assoc()['total'] : 0;

$pen_res = $conn->query("SELECT COUNT(*) as total FROM student_submissions WHERE status = 'Pending'");
$pending_submissions = $pen_res ? $pen_res->fetch_assoc()['total'] : 0;

// Admin initials for avatar
$name_parts = explode(' ', trim($admin_name));
$initials = 'AD';
if (count($name_parts) >= 2) {
    $initials = strtoupper(mb_substr($name_parts[0], 0, 1) . mb_substr($name_parts[count($name_parts)-1], 0, 1));
} elseif (!empty($name_parts[0])) {
    $initials = strtoupper(mb_substr($name_parts[0], 0, 2));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Lab Manual Portal</title>
    
    <!-- Bootstrap, FontAwesome & PREMIUM GOOGLE FONT (Exact Match to Dashboard) -->
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

        body { 
            background-color: var(--bg-body); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main);
        }
        
        /* 🔥 EXACT PREMIUM ROYAL BLUE SIDEBAR AS DASHBOARD */
        .sidebar { 
            width: var(--sidebar-width); 
            background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); /* Royal Blue to Indigo Gradient */
            color: #ffffff; display: flex; flex-direction: column; z-index: 10; overflow-y: auto; 
            box-shadow: 4px 0 24px rgba(0,0,0,0.08);
        }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0; letter-spacing: 0.5px; color: #ffffff;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { 
            padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; 
            font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); border-left: 3px solid transparent;
        }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .nav-links li.active { 
            background: rgba(255, 255, 255, 0.2); /* Glass white overlay */
            color: #ffffff; border-left: 4px solid #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        /* ✨ MAIN CONTENT ANIMATION */
        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* 🌈 TOPBAR & PROFILE PILL */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { 
            display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; 
            border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; 
            transition: var(--transition-bounce); box-shadow: var(--shadow-float);
        }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);}
        .profile-avatar-pill { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #ffffff; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3); }

        /* 🚀 PREMIUM BUTTONS */
        .btn-gradient { 
            background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; border: none; font-weight: 700; padding: 10px 20px; border-radius: 10px; 
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); transition: var(--transition-bounce);
        }
        .btn-gradient:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4); color: white; }

        /* 📦 CONTENT BOXES */
        .content-box { 
            background: var(--surface); border-radius: var(--radius-xl); padding: 30px; 
            border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: var(--shadow-float); transition: var(--transition-bounce); 
        }
        .box-title { font-size: 18px; font-weight: 800; color: var(--text-main); margin-bottom: 4px; }

        /* 🏆 MASTER IDENTITY PROFILE CARD */
        .identity-card {
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: var(--shadow-float);
            overflow: hidden;
            transition: var(--transition-bounce);
        }
        .identity-card:hover { box-shadow: 0 16px 32px -4px rgba(0,0,0,0.08); }
        .identity-header-cover {
            height: 90px;
            background: linear-gradient(135deg, #1e3a8a 0%, #4338ca 60%, #3b82f6 100%);
            position: relative;
        }
        .identity-avatar-wrap {
            position: relative;
            width: 95px;
            height: 95px;
            margin: -48px auto 14px;
        }
        .identity-avatar {
            width: 95px;
            height: 95px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #3730a3);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: 800;
            border: 4px solid #ffffff;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35);
        }
        .identity-avatar-img {
            width: 95px;
            height: 95px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ffffff;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35);
            background: #ffffff;
        }
        .avatar-camera-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 32px;
            height: 32px;
            background: var(--primary);
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            cursor: pointer;
            border: 2.5px solid #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.18);
            transition: var(--transition-bounce);
        }
        .avatar-camera-btn:hover {
            transform: scale(1.15);
            background: #312e81;
            color: #ffffff;
        }
        .status-dot-active {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 16px;
            height: 16px;
            background: #10b981;
            border: 3px solid #ffffff;
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.4);
        }

        /* 📸 PHOTO UPLOAD BOX INSIDE TAB */
        .photo-upload-box {
            background: #f8fafc;
            border: 1.5px dashed #cbd5e1;
            border-radius: 14px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 22px;
            transition: var(--transition-bounce);
        }
        .photo-upload-box:hover {
            border-color: var(--primary);
            background: #f1f5f9;
        }
        .thumb-preview {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .thumb-initials {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5, #3730a3);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
            border: 3px solid #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        /* 📊 MINI STATS INSIDE PROFILE */
        .mini-stat-card {
            background: #f8fafc;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 12px;
            padding: 14px 12px;
            text-align: center;
            transition: var(--transition-bounce);
        }
        .mini-stat-card:hover { transform: translateY(-3px); background: #ffffff; box-shadow: 0 6px 15px rgba(0,0,0,0.05); }
        .mini-stat-num { font-size: 20px; font-weight: 800; color: var(--text-main); margin-bottom: 2px; }
        .mini-stat-label { font-size: 10.5px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

        .icon-box-sm { 
            width: 38px; height: 38px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; margin-bottom: 6px; 
        }
        .blue-box { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .green-box { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .yellow-box { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .purple-box { background: rgba(99, 102, 241, 0.1); color: #6366f1; }

        /* 🗂️ MODERN NAV PILL TABS */
        .nav-modern-pills {
            background: #f1f5f9;
            padding: 6px;
            border-radius: 12px;
            display: flex;
            gap: 6px;
            border: none;
            margin-bottom: 25px;
        }
        .nav-modern-pills .nav-link {
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            padding: 10px 18px;
            background: transparent;
            transition: var(--transition-bounce);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .nav-modern-pills .nav-link:hover {
            color: var(--primary);
            background: rgba(255,255,255,0.6);
        }
        .nav-modern-pills .nav-link.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            font-weight: 800;
        }

        /* 📝 MODERN FORMS */
        .form-label-modern {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
            margin-bottom: 6px;
            display: block;
        }
        .form-control-modern {
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            padding: 12px 16px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-main);
            background-color: #f8fafc;
            transition: all 0.25s ease;
        }
        .form-control-modern:focus {
            background-color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(67, 56, 202, 0.1);
            outline: none;
        }
        .form-control-modern:disabled, .form-control-modern[readonly] {
            background-color: #f1f5f9;
            color: #64748b;
            cursor: not-allowed;
        }

        .input-group-modern {
            position: relative;
        }
        .input-group-modern .btn-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            z-index: 5;
            transition: color 0.2s;
        }
        .input-group-modern .btn-eye:hover { color: var(--primary); }

        /* ⚡ PASSWORD METER */
        .password-meter-bg {
            height: 6px;
            background: #e2e8f0;
            border-radius: 50px;
            overflow: hidden;
            margin-top: 8px;
        }
        .password-meter-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 50px;
        }

        /* ⚙️ PREFERENCE TOGGLE CARDS */
        .toggle-card {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            transition: var(--transition-bounce);
        }
        .toggle-card:hover {
            border-color: #cbd5e1;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .form-check-input-modern {
            width: 48px;
            height: 26px;
            cursor: pointer;
        }
        .form-check-input-modern:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        /* 🛡️ PRIVILEGE CARD */
        .privilege-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            transition: var(--transition-bounce);
            height: 100%;
        }
        .privilege-card:hover {
            transform: translateY(-4px);
            background: #ffffff;
            box-shadow: var(--shadow-float);
            border-color: #cbd5e1;
        }

        /* SCROLLBAR */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <!-- 🔥 EXACT ROYAL BLUE SIDEBAR AS DASHBOARD -->
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
            <li class="active" onclick="window.location.href='Profile.php'"><i class="fas fa-user-shield"></i> Admin Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="main">
        
        <!-- 🌈 EXACT TOPBAR AS DASHBOARD -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge">
                    <i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span>
                </div>
                
                <div class="dropdown">
                    <button class="btn-gradient dropdown-toggle" type="button" id="quickActions" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bolt me-1"></i> Quick Action
                    </button>
                    <ul class="dropdown-menu border-0 mt-2 p-2" aria-labelledby="quickActions" style="border-radius: 12px; box-shadow: var(--shadow-float);">
                        <li><a class="dropdown-item py-2 fw-bold text-secondary" style="border-radius: 8px;" href="Student_Mgmt.php"><i class="fas fa-user-plus text-primary me-2"></i> Add Student</a></li>
                        <li><a class="dropdown-item py-2 fw-bold text-secondary" style="border-radius: 8px;" href="faculty_mgmt.php"><i class="fas fa-chalkboard-teacher text-success me-2"></i> Manage Faculty</a></li>
                        <li><hr class="dropdown-divider my-2"></li>
                        <li><a class="dropdown-item py-2 fw-bold text-secondary" style="border-radius: 8px;" href="Reports.php"><i class="fas fa-file-pdf text-danger me-2"></i> Generate Report</a></li>
                    </ul>
                </div>
            </div>
            
            <a href="Profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">K.D. Polytechnic</span>
                    <h4 class="profile-name">
                        <?php 
                            echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : 'Admin';
                        ?>
                    </h4>
                </div>
                <?php if ($has_photo): ?>
                    <img src="<?= $photo_url ?>" alt="Avatar" class="profile-avatar-pill">
                <?php else: ?>
                    <div class="profile-avatar"><?= $initials ?></div>
                <?php endif; ?>
            </a>
        </div>

        <!-- PAGE HEADER WITH BACK BUTTON -->
        <div class="mb-4 mt-2 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);">Admin Profile</h3>
                <p class="text-muted fw-semibold small mb-0">Manage account credentials, personal details, and portal security.</p>
            </div>
            <a href="dashboard.php" class="btn-gradient text-decoration-none d-inline-flex align-items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- SERVER STATUS NOTIFICATIONS -->
        <?php if (!empty($message)) echo $message; ?>

        <!-- PROFILE LAYOUT: 2-COLUMN MODERN DESIGN -->
        <div class="row g-4">
            
            <!-- 👤 LEFT COLUMN: MASTER PROFILE IDENTITY CARD -->
            <div class="col-lg-4 col-xl-4">
                <div class="identity-card mb-4">
                    <div class="identity-header-cover"></div>
                    
                    <div class="px-4 pb-4 text-center">
                        <div class="identity-avatar-wrap">
                            <?php if ($has_photo): ?>
                                <img src="<?= $photo_url ?>" alt="Admin Avatar" class="identity-avatar-img" id="masterAvatarImg">
                                <div class="identity-avatar d-none" id="masterAvatarInitials"><?= $initials ?></div>
                            <?php else: ?>
                                <div class="identity-avatar" id="masterAvatarInitials"><?= $initials ?></div>
                                <img src="" alt="Admin Avatar" class="identity-avatar-img d-none" id="masterAvatarImg">
                            <?php endif; ?>
                            
                            <!-- 📷 Camera Trigger Button -->
                            <label for="profilePicInput" class="avatar-camera-btn" title="Upload New Photo">
                                <i class="fas fa-camera"></i>
                            </label>
                            <span class="status-dot-active" title="Account Active & Verified"></span>
                        </div>

                        <h4 class="fw-bold text-dark mb-1" style="font-size: 20px;"><?= htmlspecialchars($admin_name) ?></h4>
                        <p class="text-muted small fw-semibold mb-3"><?= htmlspecialchars($admin_email) ?></p>
                        
                        <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                            <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-2 rounded-pill" style="font-size: 11.5px;">
                                <i class="fas fa-shield-alt me-1"></i> Head of Department (HOD)
                            </span>
                        </div>

                        <div class="p-2 px-3 rounded-3 bg-light border text-secondary small fw-bold mb-4 d-inline-flex align-items-center gap-2">
                            <i class="fas fa-laptop-code text-primary"></i> <?= htmlspecialchars($admin_dept) ?>
                        </div>

                        <!-- 📊 LIVE MINI STATS GRID (Matching Dashboard Stats) -->
                        <div class="row g-2 pt-2 border-top">
                            <div class="col-6">
                                <div class="mini-stat-card">
                                    <div class="icon-box-sm blue-box"><i class="fas fa-user-graduate"></i></div>
                                    <div class="mini-stat-num"><?= $total_students ?></div>
                                    <div class="mini-stat-label">Students</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mini-stat-card">
                                    <div class="icon-box-sm green-box"><i class="fas fa-chalkboard-teacher"></i></div>
                                    <div class="mini-stat-num"><?= $total_faculty ?></div>
                                    <div class="mini-stat-label">Faculty</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mini-stat-card">
                                    <div class="icon-box-sm yellow-box"><i class="fas fa-inbox"></i></div>
                                    <div class="mini-stat-num"><?= $total_submissions ?></div>
                                    <div class="mini-stat-label">Submissions</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mini-stat-card">
                                    <div class="icon-box-sm purple-box"><i class="fas fa-user-shield"></i></div>
                                    <div class="mini-stat-num" style="font-size: 16px; margin-top: 4px;">Active</div>
                                    <div class="mini-stat-label">Portal Status</div>
                                </div>
                            </div>
                        </div>

                        <!-- ACCOUNT FOOTNOTE -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top text-muted small fw-semibold">
                            <span><i class="far fa-calendar-check me-1 text-primary"></i> Joined: <?= $admin_created ?></span>
                            <span class="badge bg-success-subtle text-success fw-bold">Verified</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ⚙️ RIGHT COLUMN: TABBED SETTINGS & EDIT SUITE -->
            <div class="col-lg-8 col-xl-8">
                <div class="content-box">
                    
                    <!-- 🗂️ PILL TAB CONTROLS -->
                    <ul class="nav nav-modern-pills" id="profileTab" role="tablist">
                        <li class="nav-item flex-fill text-center" role="presentation">
                            <button class="nav-link w-100 justify-content-center <?= ($active_tab == 'profile') ? 'active' : ''; ?>" id="tab-profile-btn" data-bs-toggle="pill" data-bs-target="#tab-profile" type="button" role="tab">
                                <i class="fas fa-id-card"></i> Profile Details
                            </button>
                        </li>
                        <li class="nav-item flex-fill text-center" role="presentation">
                            <button class="nav-link w-100 justify-content-center <?= ($active_tab == 'security') ? 'active' : ''; ?>" id="tab-security-btn" data-bs-toggle="pill" data-bs-target="#tab-security" type="button" role="tab">
                                <i class="fas fa-lock"></i> Security & Password
                            </button>
                        </li>
                        <li class="nav-item flex-fill text-center" role="presentation">
                            <button class="nav-link w-100 justify-content-center <?= ($active_tab == 'preferences') ? 'active' : ''; ?>" id="tab-preferences-btn" data-bs-toggle="pill" data-bs-target="#tab-preferences" type="button" role="tab">
                                <i class="fas fa-sliders-h"></i> Preferences
                            </button>
                        </li>
                        <li class="nav-item flex-fill text-center" role="presentation">
                            <button class="nav-link w-100 justify-content-center <?= ($active_tab == 'privileges') ? 'active' : ''; ?>" id="tab-privileges-btn" data-bs-toggle="pill" data-bs-target="#tab-privileges" type="button" role="tab">
                                <i class="fas fa-shield-halved"></i> Privileges
                            </button>
                        </li>
                    </ul>

                    <!-- TAB CONTENT BODIES -->
                    <div class="tab-content" id="profileTabContent">
                        
                        <!-- 👤 TAB 1: PROFILE DETAILS -->
                        <div class="tab-pane fade <?= ($active_tab == 'profile') ? 'show active' : ''; ?>" id="tab-profile" role="tabpanel">
                            <div class="mb-4">
                                <h5 class="box-title">Administrative & Personal Information</h5>
                                <p class="text-muted fw-semibold small mb-0">Update your official display name, contact phone number, profile photo, and departmental records.</p>
                            </div>

                            <form method="POST" action="Profile.php" enctype="multipart/form-data" id="profileForm">
                                
                                <!-- 📸 MODERN PROFILE PHOTO MANAGEMENT CARD -->
                                <div class="photo-upload-box">
                                    <div class="d-flex align-items-center gap-3">
                                        <div id="tabThumbContainer">
                                            <?php if ($has_photo): ?>
                                                <img src="<?= $photo_url ?>" alt="Current Avatar" class="thumb-preview" id="tabAvatarThumb">
                                                <div class="thumb-initials d-none" id="tabAvatarThumbInitials"><?= $initials ?></div>
                                            <?php else: ?>
                                                <div class="thumb-initials" id="tabAvatarThumbInitials"><?= $initials ?></div>
                                                <img src="" alt="Preview" class="thumb-preview d-none" id="tabAvatarThumb">
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Profile Photo</h6>
                                            <p class="text-muted small mb-0 fw-semibold">Upload an official avatar or photo (JPG, PNG, WEBP, Max 5MB).</p>
                                            <div id="photoStatusText" class="small text-primary fw-bold mt-1 d-none">New photo selected! Click 'Save Profile Details' to apply.</div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                                        <label for="profilePicInput" class="btn btn-sm btn-outline-primary fw-bold px-3 py-2 rounded-3" style="cursor: pointer;">
                                            <i class="fas fa-upload me-1"></i> Choose Photo
                                        </label>
                                        <input type="file" name="profile_pic" id="profilePicInput" accept="image/png, image/jpeg, image/jpg, image/webp" class="d-none" onchange="previewSelectedPhoto(this)">
                                        
                                        <?php if ($has_photo): ?>
                                            <button type="submit" name="remove_photo" class="btn btn-sm btn-outline-danger fw-bold px-3 py-2 rounded-3" onclick="return confirm('Are you sure you want to remove your profile photo?');">
                                                <i class="fas fa-trash-alt me-1"></i> Remove
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label-modern"><i class="fas fa-user text-primary me-1"></i> Full Name *</label>
                                        <input type="text" name="name" class="form-control form-control-modern" value="<?= htmlspecialchars($admin_name) ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-modern"><i class="fas fa-id-badge text-secondary me-1"></i> Admin User ID (Permanent)</label>
                                        <input type="text" class="form-control form-control-modern" value="<?= htmlspecialchars($admin_id) ?>" readonly>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-modern"><i class="fas fa-envelope text-primary me-1"></i> Official Email Address *</label>
                                        <input type="email" name="email" class="form-control form-control-modern" value="<?= htmlspecialchars($admin_email) ?>" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-modern"><i class="fas fa-phone text-primary me-1"></i> Contact Phone Number</label>
                                        <input type="text" name="phone" class="form-control form-control-modern" value="<?= htmlspecialchars($admin_phone) ?>" placeholder="+91 98765 43210">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-modern"><i class="fas fa-building text-primary me-1"></i> Department</label>
                                        <input type="text" name="department" class="form-control form-control-modern" value="<?= htmlspecialchars($admin_dept) ?>" placeholder="e.g. Computer Engineering">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-modern"><i class="fas fa-user-shield text-secondary me-1"></i> System Role</label>
                                        <input type="text" class="form-control form-control-modern" value="Super Administrator (HOD)" readonly>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                                    <button type="submit" name="update_profile" class="btn-gradient d-inline-flex align-items-center gap-2">
                                        <i class="fas fa-save"></i> Save Profile Details
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- 🔐 TAB 2: SECURITY & PASSWORD -->
                        <div class="tab-pane fade <?= ($active_tab == 'security') ? 'show active' : ''; ?>" id="tab-security" role="tabpanel">
                            <div class="mb-4">
                                <h5 class="box-title">Password & Security Management</h5>
                                <p class="text-muted fw-semibold small mb-0">Ensure your administrative account is protected with a strong, complex password.</p>
                            </div>

                            <form method="POST" action="Profile.php">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label-modern"><i class="fas fa-lock text-primary me-1"></i> Current Password *</label>
                                        <div class="input-group-modern">
                                            <input type="password" name="old_password" id="old_password" class="form-control form-control-modern" placeholder="Enter current password" required>
                                            <button type="button" class="btn-eye" onclick="togglePasswordVisibility('old_password', this)"><i class="far fa-eye"></i></button>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-modern"><i class="fas fa-key text-primary me-1"></i> New Password *</label>
                                        <div class="input-group-modern">
                                            <input type="password" name="new_password" id="new_password" class="form-control form-control-modern" placeholder="Enter new password (min 6 chars)" oninput="checkPasswordStrength(this.value)" required>
                                            <button type="button" class="btn-eye" onclick="togglePasswordVisibility('new_password', this)"><i class="far fa-eye"></i></button>
                                        </div>
                                        <div class="password-meter-bg">
                                            <div class="password-meter-bar" id="passwordMeterBar"></div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-1">
                                            <small class="fw-bold text-muted" id="passwordStrengthText" style="font-size: 11px;">Strength: None</small>
                                            <small class="text-muted" style="font-size: 11px;">Min 6 chars</small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-modern"><i class="fas fa-check-double text-primary me-1"></i> Confirm New Password *</label>
                                        <div class="input-group-modern">
                                            <input type="password" name="confirm_password" id="confirm_password" class="form-control form-control-modern" placeholder="Repeat new password" required>
                                            <button type="button" class="btn-eye" onclick="togglePasswordVisibility('confirm_password', this)"><i class="far fa-eye"></i></button>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-3 mt-4 rounded-3 bg-light border">
                                    <div class="d-flex align-items-center gap-2 text-dark fw-bold small mb-1">
                                        <i class="fas fa-info-circle text-primary"></i> Password Security Recommendations
                                    </div>
                                    <ul class="text-muted small mb-0 ps-3 fw-semibold">
                                        <li>Use at least 8 characters with a mix of numbers, letters, and symbols.</li>
                                        <li>Never reuse passwords across different academic or personal accounts.</li>
                                    </ul>
                                </div>

                                <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                                    <button type="submit" name="update_password" class="btn-gradient d-inline-flex align-items-center gap-2">
                                        <i class="fas fa-key"></i> Update Password
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- ⚙️ TAB 3: SYSTEM PREFERENCES -->
                        <div class="tab-pane fade <?= ($active_tab == 'preferences') ? 'show active' : ''; ?>" id="tab-preferences" role="tabpanel">
                            <div class="mb-4">
                                <h5 class="box-title">Notification & Security Preferences</h5>
                                <p class="text-muted fw-semibold small mb-0">Control administrative alerts, practical submission notifications, and multi-factor authentication.</p>
                            </div>

                            <form method="POST" action="Profile.php">
                                <div class="toggle-card">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="icon-box-sm blue-box"><i class="fas fa-envelope-open-text"></i></div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">Email Notifications</h6>
                                            <small class="text-muted fw-semibold">Receive daily summary reports and department circular alerts via official email.</small>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input form-check-input-modern" type="checkbox" name="email_notifications" id="email_notifications" <?= ($email_notifications == 1) ? 'checked' : ''; ?>>
                                    </div>
                                </div>

                                <div class="toggle-card">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="icon-box-sm yellow-box"><i class="fas fa-bell"></i></div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">Practical Submission Alerts</h6>
                                            <small class="text-muted fw-semibold">Trigger real-time alert badges when students submit practical assignments.</small>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input form-check-input-modern" type="checkbox" name="submission_alerts" id="submission_alerts" <?= ($submission_alerts == 1) ? 'checked' : ''; ?>>
                                    </div>
                                </div>

                                <div class="toggle-card">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="icon-box-sm green-box"><i class="fas fa-shield-virus"></i></div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">Two-Factor Authentication (2FA)</h6>
                                            <small class="text-muted fw-semibold">Require one-time verification challenge on logins from unrecognized devices.</small>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch m-0">
                                        <input class="form-check-input form-check-input-modern" type="checkbox" name="two_factor_auth" id="two_factor_auth" <?= ($two_factor_auth == 1) ? 'checked' : ''; ?>>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                                    <button type="submit" name="update_preferences" class="btn-gradient d-inline-flex align-items-center gap-2">
                                        <i class="fas fa-sliders-h"></i> Save Preferences
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- 🛡️ TAB 4: SYSTEM PRIVILEGES MATRIX -->
                        <div class="tab-pane fade <?= ($active_tab == 'privileges') ? 'show active' : ''; ?>" id="tab-privileges" role="tabpanel">
                            <div class="mb-4">
                                <h5 class="box-title">Administrative Clearance & Privileges</h5>
                                <p class="text-muted fw-semibold small mb-0">Active authorization permissions and clearance assigned to this super-administrator account.</p>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="privilege-card">
                                        <div class="icon-box-sm blue-box"><i class="fas fa-users-cog"></i></div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">User & Student Management</h6>
                                            <p class="text-muted small mb-2 fw-semibold">Full authority to enroll, batch import, edit, and manage students and faculty.</p>
                                            <span class="badge bg-success-subtle text-success fw-bold">Full CRUD Granted</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="privilege-card">
                                        <div class="icon-box-sm green-box"><i class="fas fa-file-pdf"></i></div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Curriculum & Manuals</h6>
                                            <p class="text-muted small mb-2 fw-semibold">Upload, assign, and publish official practical lab manuals and deadlines.</p>
                                            <span class="badge bg-success-subtle text-success fw-bold">Full Authority</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="privilege-card">
                                        <div class="icon-box-sm yellow-box"><i class="fas fa-check-double"></i></div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Review & Marks Evaluation</h6>
                                            <p class="text-muted small mb-2 fw-semibold">Inspect submissions, assign grades, approval overrides, and remarks.</p>
                                            <span class="badge bg-success-subtle text-success fw-bold">Unrestricted Access</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="privilege-card">
                                        <div class="icon-box-sm purple-box"><i class="fas fa-database"></i></div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Database & Core Architecture</h6>
                                            <p class="text-muted small mb-2 fw-semibold">Table self-healing verification, schema updates, and direct SQL queries.</p>
                                            <span class="badge bg-danger-subtle text-danger fw-bold">Super Admin Level</span>
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

    <!-- Bootstrap Bundle JS (with Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // 🕒 Real-time Live Clock (Exact match to Dashboard)
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
            const clockEl = document.getElementById('liveClock');
            if (clockEl) {
                clockEl.innerText = now.toLocaleDateString('en-IN', options);
            }
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
            const bar = document.getElementById('passwordMeterBar');
            const txt = document.getElementById('passwordStrengthText');
            if (!bar || !txt) return;

            let score = 0;
            if (password.length >= 6) score += 25;
            if (password.length >= 10) score += 25;
            if (/[0-9]/.test(password)) score += 25;
            if (/[^A-Za-z0-9]/.test(password)) score += 25;

            bar.style.width = score + '%';

            if (score === 0) {
                bar.style.backgroundColor = '#e2e8f0';
                txt.innerText = 'Strength: None';
                txt.style.color = '#64748b';
            } else if (score <= 25) {
                bar.style.backgroundColor = '#ef4444';
                txt.innerText = 'Strength: Weak';
                txt.style.color = '#ef4444';
            } else if (score <= 50) {
                bar.style.backgroundColor = '#f59e0b';
                txt.innerText = 'Strength: Fair';
                txt.style.color = '#f59e0b';
            } else if (score <= 75) {
                bar.style.backgroundColor = '#3b82f6';
                txt.innerText = 'Strength: Good';
                txt.style.color = '#3b82f6';
            } else {
                bar.style.backgroundColor = '#10b981';
                txt.innerText = 'Strength: Strong & Secure 🛡️';
                txt.style.color = '#10b981';
            }
        }

        // 📸 Instant Photo Preview
        function previewSelectedPhoto(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Validate size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert("Selected file is too large! Please choose an image smaller than 5MB.");
                    input.value = "";
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const dataUrl = e.target.result;

                    // 1. Update Left Column Master Card Avatar
                    const masterImg = document.getElementById('masterAvatarImg');
                    const masterInitials = document.getElementById('masterAvatarInitials');
                    if (masterImg) {
                        masterImg.src = dataUrl;
                        masterImg.classList.remove('d-none');
                    }
                    if (masterInitials) {
                        masterInitials.classList.add('d-none');
                    }

                    // 2. Update Tab 1 Thumbnail Preview
                    const tabThumb = document.getElementById('tabAvatarThumb');
                    const tabInitials = document.getElementById('tabAvatarThumbInitials');
                    if (tabThumb) {
                        tabThumb.src = dataUrl;
                        tabThumb.classList.remove('d-none');
                    }
                    if (tabInitials) {
                        tabInitials.classList.add('d-none');
                    }

                    // 3. Show notification prompt
                    const statusTxt = document.getElementById('photoStatusText');
                    if (statusTxt) {
                        statusTxt.classList.remove('d-none');
                    }
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>