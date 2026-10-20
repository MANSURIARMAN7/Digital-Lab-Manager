<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$msg = "";

// Generate Math CAPTCHA
if (!isset($_SESSION['captcha_ans'])) {
    $_SESSION['num1'] = rand(1, 9);
    $_SESSION['num2'] = rand(1, 9);
    $_SESSION['captcha_ans'] = $_SESSION['num1'] + $_SESSION['num2'];
}

// Fetch Admin Data
$query = $conn->query("SELECT * FROM users WHERE user_id='$admin_id'");
$admin = $query->fetch_assoc();

// Change Password Logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $user_ans = (int)$_POST['captcha_ans'];

    if ($user_ans === $_SESSION['captcha_ans']) {
        if ($old_pass === $admin['password']) { // NOTE: Update to password_verify if using hashed passwords
            $update = $conn->query("UPDATE users SET password='$new_pass' WHERE user_id='$admin_id'");
            if ($update) {
                $msg = "<div class='alert alert-success shadow-sm' style='border-radius:10px;'><i class='fas fa-check-circle me-2'></i> Password updated successfully!</div>";
                // Reset Captcha
                $_SESSION['num1'] = rand(1, 9);
                $_SESSION['num2'] = rand(1, 9);
                $_SESSION['captcha_ans'] = $_SESSION['num1'] + $_SESSION['num2'];
            }
        } else {
            $msg = "<div class='alert alert-danger shadow-sm' style='border-radius:10px;'><i class='fas fa-times-circle me-2'></i> Incorrect current password.</div>";
        }
    } else {
        $msg = "<div class='alert alert-warning shadow-sm' style='border-radius:10px;'><i class='fas fa-robot me-2'></i> CAPTCHA failed! Please try again.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Profile - KDP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-width: 270px; --primary: #4338ca; --bg-body: #f8fafc; --surface: #ffffff; --text-main: #0f172a; --text-muted: #64748b; --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 13px; color: #bfdbfe; margin-top: 5px; font-weight: 500; letter-spacing: 0.5px;} 
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}
        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }
        .content-box { background: var(--surface); border-radius: 16px; padding: 30px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 25px;}
        .form-control { border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 600; padding: 12px 15px;}
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1); background: white;}
        .profile-avatar-large { width: 100px; height: 100px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 35px; font-weight: 800; box-shadow: 0 10px 20px rgba(67, 56, 202, 0.3); margin-bottom: 20px;}
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
            <li class="active" onclick="window.location.href='Profile.php'"><i class="fas fa-user-shield"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <h3 class="fw-bold m-0 mb-4">Security & Profile Settings</h3>
        <?php echo $msg; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="content-box text-center d-flex flex-column align-items-center justify-content-center h-100">
                    <div class="profile-avatar-large"><i class="fas fa-user-shield"></i></div>
                    <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($admin['name']); ?></h4>
                    <span class="badge bg-primary px-3 py-2 mt-2 rounded-pill"><i class="fas fa-crown me-1"></i> Super Admin</span>
                    <hr class="w-100 mt-4 mb-3">
                    <div class="w-100 text-start text-muted fw-bold small">
                        <p class="mb-2"><i class="fas fa-id-badge me-2"></i> ID: <?php echo htmlspecialchars($admin['user_id']); ?></p>
                        <p class="mb-0"><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($admin['email']); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="content-box">
                    <h5 class="fw-bold mb-4 border-bottom pb-3"><i class="fas fa-lock text-primary me-2"></i> Change Password</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Current Password</label>
                            <input type="password" name="old_password" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        
                        <!-- 🚀 ALIGNED CAPTCHA FIX -->
                        <div class="mb-4 bg-light p-3 rounded-3 border">
                            <label class="form-label small fw-bold text-primary text-uppercase mb-2"><i class="fas fa-shield-alt me-1"></i> Security Verification</label>
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-white border rounded px-3 py-2 fw-bold text-dark fs-5 shadow-sm">
                                    <?php echo $_SESSION['num1'] . " + " . $_SESSION['num2']; ?> = 
                                </div>
                                <input type="number" name="captcha_ans" class="form-control m-0" placeholder="?" required style="width: 100px; text-align:center; font-size:18px;">
                            </div>
                        </div>

                        <button type="submit" name="update_password" class="btn btn-primary fw-bold px-4 py-2 w-100" style="border-radius: 10px;">
                            <i class="fas fa-save me-2"></i> Update Credentials
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>