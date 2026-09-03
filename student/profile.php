<?php
session_start();
require_once '../db.php';

// ============================================================
// 1. Authentication Check
// ============================================================
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

$enrollment = $_SESSION['user_id']; // already validated

// ============================================================
// 2. Captcha Management (Simple Math)
// ============================================================
if (!isset($_SESSION['captcha_num1']) || !isset($_SESSION['captcha_num2'])) {
    $_SESSION['captcha_num1'] = rand(1, 9);
    $_SESSION['captcha_num2'] = rand(1, 9);
}
$c_num1 = $_SESSION['captcha_num1'];
$c_num2 = $_SESSION['captcha_num2'];
$c_ans = $c_num1 + $c_num2;

// ============================================================
// 3. Message Variable
// ============================================================
$msg = '';

// ============================================================
// 4. Handle Form Submissions
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---------- Profile Update ----------
    if (isset($_POST['update_profile'])) {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name) || empty($email)) {
            $msg = '<div class="alert alert-warning alert-dismissible fade show">Name and email are required.</div>';
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
            $stmt->bind_param('sss', $name, $email, $enrollment);
            if ($stmt->execute()) {
                $msg = '<div class="alert alert-success alert-dismissible fade show">Profile updated successfully!</div>';
            } else {
                $msg = '<div class="alert alert-danger alert-dismissible fade show">Error updating profile. Please try again.</div>';
            }
            $stmt->close();
        }
    }

    // ---------- Password Update ----------
    if (isset($_POST['update_password'])) {
        $old_pass      = $_POST['old_password'] ?? '';
        $new_pass      = $_POST['new_password'] ?? '';
        $user_captcha  = isset($_POST['captcha_answer']) ? (int)$_POST['captcha_answer'] : 0;

        // Verify Captcha
        if ($user_captcha !== $c_ans) {
            $msg = '<div class="alert alert-danger alert-dismissible fade show">Incorrect Captcha! Please try again.</div>';
            // Regenerate captcha for next attempt
            $_SESSION['captcha_num1'] = rand(1, 9);
            $_SESSION['captcha_num2'] = rand(1, 9);
        } else {
            // Fetch current password hash from DB
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->bind_param('s', $enrollment);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            $stored_pass = $row['password'] ?? '';

            // Verify old password (supports both plain and hashed)
            $valid_old = false;
            if (password_verify($old_pass, $stored_pass)) {
                $valid_old = true;
            } elseif ($old_pass === $stored_pass) {
                // Legacy plain-text password – upgrade to hash
                $valid_old = true;
            }

            if (!$valid_old) {
                $msg = '<div class="alert alert-danger alert-dismissible fade show">Incorrect old password.</div>';
            } elseif (empty($new_pass)) {
                $msg = '<div class="alert alert-warning alert-dismissible fade show">New password cannot be empty.</div>';
            } elseif (strlen($new_pass) < 6) {
                $msg = '<div class="alert alert-warning alert-dismissible fade show">New password must be at least 6 characters.</div>';
            } else {
                // Hash new password
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->bind_param('ss', $hashed, $enrollment);
                if ($stmt->execute()) {
                    $msg = '<div class="alert alert-success alert-dismissible fade show">Password updated successfully!</div>';
                    // Regenerate session ID to prevent fixation
                    session_regenerate_id(true);
                } else {
                    $msg = '<div class="alert alert-danger alert-dismissible fade show">Error updating password. Please try again.</div>';
                }
                $stmt->close();
            }

            // Always regenerate captcha after a password attempt (success or failure)
            $_SESSION['captcha_num1'] = rand(1, 9);
            $_SESSION['captcha_num2'] = rand(1, 9);
        }
    }
}

// ============================================================
// 5. Fetch Student Data (prepared statement)
// ============================================================
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param('s', $enrollment);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// Set defaults if missing
$student_name = $student['name'] ?? 'Student';
$department   = $student['department'] ?? 'Computer Engineering';
$semester     = $student['designation'] ?? 'Semester 1';
$email        = $student['email'] ?? ($enrollment . '@kdp.edu');

// ============================================================
// 6. Generate Initials for Avatar
// ============================================================
$name_parts = explode(' ', trim($student_name));
$initials = strtoupper($name_parts[0][0] ?? 'S');
if (count($name_parts) > 1) {
    $initials .= strtoupper(end($name_parts)[0] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile & Settings - K.D. Polytechnic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-width: 260px; --bg-color: #f4f7f6; --sidebar-bg: #1b365d; --primary-blue: #3b82f6; }
        body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        .sidebar { width: var(--sidebar-width); background-color: var(--sidebar-bg); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar-header { padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 15px; }
        .sidebar-header img { width: 80px; height: 80px; object-fit: contain; margin-bottom: 15px; background: white; border-radius: 50%; padding: 5px; }
        .sidebar-title { font-size: 18px; font-weight: 700; margin: 0 0 5px 0; }
        .nav-links { list-style: none; padding: 0 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 14px 20px; margin: 5px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 500; color: #cbd5e1; transition: 0.2s; }
        .nav-links li:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-links li.active { background-color: var(--primary-blue); color: white; box-shadow: 0 4px 10px rgba(59,130,246,0.3); }
        .main { flex: 1; overflow-y: auto; padding: 30px 40px; }
        .card-custom { background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 25px; }
        .profile-banner { height: 140px; background: linear-gradient(135deg, #1b365d, #3b82f6); position: relative; }
        .avatar-container {
            width: 90px; height: 90px;
            background: #1b365d; color: #ffffff;
            font-size: 30px; font-weight: 800;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: -45px auto 12px auto;
            border: 4px solid #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: relative;
            z-index: 2;
        }
        .section-title { font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .form-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 14px; font-size: 14px; border-radius: 8px; }
        .form-control:focus { background-color: white; border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .btn-custom { background: var(--primary-blue); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .btn-custom:hover { background: #2563eb; color: white; box-shadow: 0 4px 10px rgba(59,130,246,0.3); }
        .captcha-box { background: #e0f2fe; border: 1px solid #bae6fd; color: #0369a1; padding: 10px 15px; border-radius: 8px; font-weight: 700; font-size: 16px; text-align: center; letter-spacing: 1px; }
        @media (max-width: 768px) {
            .main { padding: 15px; }
            .row.g-4 > .col-md-4 { order: 2; }
            .row.g-4 > .col-md-8 { order: 1; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/college-logo.png" alt="Logo">
        <h2 class="sidebar-title">K.D. Polytechnic</h2>
    </div>
    <ul class="nav-links">
        <li onclick="window.location.href='Stdashboard.php'"><i class="fas fa-home" style="width:20px;"></i> Dashboard</li>
        <li onclick="window.location.href='my-manuals.php'"><i class="fas fa-book" style="width:20px;"></i> My Submissions</li>
        <li class="active" onclick="window.location.href='profile.php'"><i class="fas fa-user-circle" style="width:20px;"></i> Profile</li>
        <li onclick="window.location.href='history.php'"><i class="fas fa-history" style="width:20px;"></i> History</li>
        <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color:#fca5a5;"><i class="fas fa-sign-out-alt" style="width:20px;"></i> Logout</li>
    </ul>
</div>

<!-- MAIN CONTENT -->
<div class="main">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1" style="font-size:24px;">Student Settings</h2>
            <p class="text-muted small mb-0">Manage your profile details and security credentials.</p>
        </div>
    </div>

    <!-- Display Messages -->
    <?php echo $msg; ?>

    <!-- Profile & Forms Row -->
    <div class="row g-4">

        <!-- LEFT: Profile Card -->
        <div class="col-md-4">
            <div class="card-custom text-center pb-4">
                <div class="profile-banner"></div>
                <div class="avatar-container"><?php echo htmlspecialchars($initials); ?></div>
                <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($student_name); ?></h5>
                <p class="text-muted small mb-3"><?php echo htmlspecialchars($enrollment); ?></p>

                <div class="px-4 text-start border-top pt-3 mt-3">
                    <p class="small mb-2 text-secondary"><i class="fas fa-code-branch me-2 text-primary"></i> Dept: <strong><?php echo htmlspecialchars($department); ?></strong></p>
                    <p class="small mb-2 text-secondary"><i class="fas fa-layer-group me-2 text-primary"></i> Sem: <strong><?php echo htmlspecialchars($semester); ?></strong></p>
                    <p class="small mb-0 text-secondary"><i class="fas fa-shield-alt me-2 text-success"></i> Status: <strong class="text-success">Active</strong></p>
                </div>
            </div>
        </div>

        <!-- RIGHT: Edit Forms -->
        <div class="col-md-8">

            <!-- Personal Details Form -->
            <div class="card-custom p-4">
                <div class="section-title"><i class="fas fa-user-edit text-primary"></i> Personal Details</div>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($student_name); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Enrollment / User ID (Locked)</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($enrollment); ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch (Locked)</label>
                            <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($department); ?>" disabled>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" name="update_profile" class="btn-custom">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Update Password Form -->
            <div class="card-custom p-4">
                <div class="section-title text-danger"><i class="fas fa-lock"></i> Update Password & Security</div>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Old Password</label>
                            <input type="password" name="old_password" class="form-control" placeholder="Enter current password" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password (min 6 chars)</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
                        </div>

                        <div class="col-md-6 mt-3">
                            <label class="form-label">Solve to Verify (Captcha)</label>
                            <div class="captcha-box">
                                <?php echo $c_num1 . ' + ' . $c_num2 . ' = ?'; ?>
                            </div>
                        </div>
                        <div class="col-md-6 mt-3 d-flex align-items-end">
                            <div class="w-100">
                                <label class="form-label">Enter Answer</label>
                                <input type="number" name="captcha_answer" class="form-control" placeholder="Answer" required>
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" name="update_password" class="btn btn-danger px-4 py-2 fw-semibold" style="border-radius:8px; font-size:14px;">Update Password</button>
                        </div>
                    </div>
                </form>
            </div>

        </div><!-- /col-md-8 -->
    </div><!-- /row -->

</div><!-- /main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>