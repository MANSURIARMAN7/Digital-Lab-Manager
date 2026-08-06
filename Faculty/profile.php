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

// Fetch Current User Data (Database se data laana zaroori hai password check karne ke liye)
$query = "SELECT * FROM users WHERE user_id='$user_id'";
$result = $conn->query($query);
$user = $result->fetch_assoc();

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
            // Agar sab sahi hai toh query mein password update jodo
            $update_sql .= ", password='" . $conn->real_escape_string($new_password) . "'";
        }
    }

    // Photo Upload Logic (Sirf tabhi jab error na ho)
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
            // Update user array to reflect changes immediately
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['cabin_no'] = $cabin;
            $user['bio'] = $bio;
            if(!empty($new_password)) $user['password'] = $new_password;
        } else {
            $error_msg = "Database Error: " . $conn->error;
        }
    }
}

// 🔥 GENERATE NEW CAPTCHA FOR EVERY PAGE LOAD
$num1 = rand(1, 9);
$num2 = rand(1, 9);
$_SESSION['captcha_answer'] = $num1 + $num2;
$captcha_question = "$num1 + $num2 = ?";

$raw_subjects = [];
if (!empty($user['subjects'])) {
    $decoded = json_decode($user['subjects'], true);
    $raw_subjects = is_array($decoded) ? $decoded : array_map('trim', explode(',', $user['subjects']));
}

$default_avatar = "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=1e293b&color=fff&size=200&bold=true";
$profile_src = !empty($user['profile_pic']) ? $upload_dir . $user['profile_pic'] : $default_avatar;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Premium Faculty Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; color: #334155; }
        .top-navbar { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; padding: 16px 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; }
        .top-navbar h4 { margin: 0; font-weight: 600; font-size: 1.1rem; }
        .back-btn { color: #cbd5e1; text-decoration: none; display: flex; align-items: center; gap: 8px; font-weight: 500; transition: 0.2s; font-size: 0.95rem; }
        .back-btn:hover { color: #ffffff; transform: translateX(-3px); }

        .card-custom { background: #ffffff; border: none; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 25px; }
        
        .profile-cover { height: 120px; background: linear-gradient(135deg, #2563eb, #1d4ed8); position: relative; }
        .profile-content { padding: 0 30px 30px 30px; text-align: center; }
        
        .profile-img-wrapper { position: relative; z-index: 10; margin-top: -60px; margin-bottom: 15px; display: inline-block; }
        .profile-img-wrapper img { width: 120px; height: 120px; border-radius: 50%; border: 5px solid #ffffff; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.15); background: #fff; object-fit: cover; }
        
        .cam-btn { position: absolute; bottom: 5px; right: 5px; background: #2563eb; color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; justify-content: center; align-items: center; cursor: pointer; border: 2px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.2); transition: 0.3s; }
        .cam-btn:hover { background: #1e40af; transform: scale(1.1); }

        .badge-role { background-color: #eff6ff; color: #2563eb; padding: 6px 16px; border-radius: 30px; font-weight: 600; font-size: 0.8rem; border: 1px solid #bfdbfe; display: inline-block; }

        .info-title { font-weight: 700; color: #0f172a; font-size: 1.1rem; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
        .input-group { border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; transition: all 0.3s ease; }
        .input-group:focus-within { border-color: #2563eb; box-shadow: 0 0 0 4px rgba(37,99,235,0.1); }
        .input-group-text { background: #f8fafc; border: none; color: #64748b; padding-left: 18px; }
        .form-control { border: none; padding: 12px 15px; font-weight: 500; color: #1e293b; background: #ffffff; }
        .form-control:focus { box-shadow: none; }
        .form-label { font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px; text-transform: uppercase; }

        .btn-update { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 12px 28px; font-weight: 600; border-radius: 10px; border: none; transition: all 0.3s ease; box-shadow: 0 6px 15px rgba(37, 99, 235, 0.2); }
        .btn-update:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3); }

        .subject-tag { display: inline-flex; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; color: #334155; padding: 8px 16px; border-radius: 8px; font-size: 0.9rem; margin-right: 10px; margin-bottom: 10px; font-weight: 600; }
        .sem-badge { background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; margin-right: 8px; }
        .meta-info { display: flex; align-items: center; padding: 12px 20px; border-bottom: 1px solid #f1f5f9; color: #475569; font-size: 0.95rem; font-weight: 500; }
        .meta-info i { width: 25px; color: #94a3b8; }
        .meta-info:last-child { border-bottom: none; }
        
        .security-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-top: 15px; }
        .captcha-box { background: #e2e8f0; padding: 10px 20px; border-radius: 8px; font-weight: 700; letter-spacing: 2px; font-size: 1.1rem; color: #0f172a; display: inline-block; }
    </style>
</head>
<body>

    <div class="top-navbar">
        <a href="faculty_dashboard.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
        <h4><i class="fa-solid fa-shield-halved me-2"></i> Faculty Settings</h4>
        <div style="width: 90px;"></div>
    </div>

    <div class="container py-5">
        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4"><i class="fa-solid fa-circle-check me-2"></i> <strong>Success!</strong> <?php echo $success_msg; ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4"><i class="fa-solid fa-triangle-exclamation me-2"></i> <strong>Error!</strong> <?php echo $error_msg; ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row gx-xl-5">
                
                <!-- Left Column -->
                <div class="col-lg-4">
                    <div class="card-custom">
                        <div class="profile-cover"></div>
                        <div class="profile-content">
                            <!-- Clickable Image Wrapper -->
                            <div class="profile-img-wrapper">
                                <img id="previewImg" src="<?php echo $profile_src; ?>" alt="Profile">
                                <label for="profileUpload" class="cam-btn" title="Upload Photo">
                                    <i class="fa-solid fa-camera"></i>
                                </label>
                                <input type="file" id="profileUpload" name="profile_pic" accept="image/*" style="display: none;" onchange="previewFile()">
                            </div>
                            <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
                            <p class="text-muted mb-3" style="font-size: 0.9rem;"><?php echo htmlspecialchars($user['user_id']); ?></p>
                            
                            <?php if(!empty($user['bio'])): ?>
                                <p class="text-muted small fst-italic px-3 mb-3">"<?php echo htmlspecialchars($user['bio']); ?>"</p>
                            <?php endif; ?>

                            <span class="badge-role"><i class="fa-solid fa-award me-1"></i> <?php echo htmlspecialchars($user['designation']); ?></span>
                        </div>
                        
                        <div class="pb-3">
                            <div class="meta-info"><i class="fa-solid fa-building"></i> <span><strong>Dept:</strong> <?php echo htmlspecialchars($user['department']); ?></span></div>
                            <?php if(!empty($user['cabin_no'])): ?>
                                <div class="meta-info"><i class="fa-solid fa-door-open"></i> <span><strong>Cabin:</strong> <?php echo htmlspecialchars($user['cabin_no']); ?></span></div>
                            <?php endif; ?>
                            <div class="meta-info"><i class="fa-solid fa-circle-dot text-success"></i> <span><strong>Status:</strong> Active</span></div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-8">
                    <!-- Basic Info -->
                    <div class="card-custom p-4 p-md-5 mb-4">
                        <h5 class="info-title"><i class="fa-solid fa-user-pen text-primary"></i> Personal Details</h5>
                        <div class="row mb-4 mt-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="Enter email">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Enter phone">
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label">Office / Cabin No.</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-door-closed"></i></span>
                                    <input type="text" name="cabin_no" class="form-control" value="<?php echo htmlspecialchars($user['cabin_no'] ?? ''); ?>" placeholder="e.g. Block A, Room 204">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Short Bio / Expertise</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fa-solid fa-quote-left"></i></span>
                                    <input type="text" name="bio" class="form-control" value="<?php echo htmlspecialchars($user['bio'] ?? ''); ?>" placeholder="e.g. AI Enthusiast">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 🔥 NEW SECURE PASSWORD SECTION -->
                    <div class="card-custom p-4 p-md-5 mb-4">
                        <h5 class="info-title text-danger"><i class="fa-solid fa-shield-halved"></i> Update Password</h5>
                        <p class="text-muted small mb-4">Leave fields blank if you do not want to change your password.</p>
                        
                        <div class="security-box">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Old Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa-solid fa-lock-open"></i></span>
                                        <input type="password" name="old_password" class="form-control" placeholder="Current Password">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                                        <input type="password" name="password" class="form-control" placeholder="New Password">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Solve to Verify</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="captcha-box"><?php echo $captcha_question; ?></div>
                                        <input type="number" name="captcha_input" class="form-control w-25" placeholder="Answer">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-end mt-2 mb-4">
                        <button type="submit" class="btn-update"><i class="fa-solid fa-floppy-disk me-2"></i> Save All Changes</button>
                    </div>

                    <!-- Assigned Modules -->
                    <div class="card-custom p-4 p-md-5">
                        <h5 class="info-title"><i class="fa-solid fa-layer-group text-primary"></i> Assigned Modules</h5>
                        <div class="mt-3">
                            <?php 
                            if (!empty($raw_subjects)) {
                                foreach ($raw_subjects as $sub) {
                                    $sub_text = is_array($sub) ? ($sub['name'] ?? '') : $sub;
                                    if(preg_match('/(Sem\s*\d+)\s*[-:]\s*(.*)/i', $sub_text, $matches)) {
                                        echo '<div class="subject-tag"><span class="sem-badge">' . htmlspecialchars(trim($matches[1])) . '</span> ' . htmlspecialchars(trim($matches[2])) . '</div>';
                                    } else {
                                        echo '<div class="subject-tag"><i class="fa-solid fa-hashtag text-muted me-2" style="font-size: 0.8rem;"></i> ' . htmlspecialchars($sub_text) . '</div>';
                                    }
                                }
                            } else {
                                echo '<p class="text-muted mb-0"><i class="fa-solid fa-circle-info me-2"></i> No modules assigned by the admin yet.</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Live Image Preview Script -->
    <script>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>