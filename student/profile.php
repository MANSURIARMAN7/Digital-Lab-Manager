<?php
// Error reporting ON taaki blank screen na aaye
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_name = $_SESSION['name'] ?? 'Student Name';
$student_id = $_SESSION['user_id'] ?? 'Enrollment No';
$student_email = $_SESSION['email'] ?? 'Not Provided';
$student_branch = "Computer Engineering";
$student_sem = "Semester 5";

// Load user details from users.json
$users_file = '../users.json';
if (file_exists($users_file)) {
    $users_data = json_decode(file_get_contents($users_file), true);
    if (is_array($users_data)) {
        foreach ($users_data as $u) {
            if (isset($u['user_id']) && $u['user_id'] === $student_id) {
                $student_sem = $u['sem'] ?? $student_sem;
                $student_branch = $u['branch'] ?? $student_branch;
                $student_email = $u['email'] ?? $student_email;
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | K.D. Polytechnic</title>
    <link rel="stylesheet" href="../assets/css/student.css?v=8">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 25px;
            margin-top: 20px;
        }
        .profile-card, .info-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .profile-card {
            text-align: center;
        }
        .avatar-box {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #2563eb;
            color: white;
            font-size: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
        }
        .info-group {
            margin-bottom: 18px;
        }
        .info-group label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            display: block;
            margin-bottom: 4px;
        }
        .info-group p {
            font-size: 16px;
            color: #1e293b;
            font-weight: 600;
            margin: 0;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
    </style>
</head>
<body>

<div class="app">
    <aside class="sidebar">
        <div class="college-name">
            <img src="../assets/images/KDP-Logo.png" alt="Logo" class="college-logo">
            <div>
                <h2>K.D. Polytechnic</h2>
                <p>Student Portal</p>
            </div>
        </div>
        <nav class="nav-links">
            <a href="stdashboard.php">🏠 <span>Dashboard</span></a>
            <a href="upload-manual.php">📤 <span>Upload Manual</span></a>
            <a href="my-manuals.php">📚 <span>My Manuals</span></a>
            <a href="submission-history.php">🕘 <span>History</span></a>
            <a class="active" href="profile.php">👤 <span>My Profile</span></a>
            <a href="../logout.php" class="logout">⇥ <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <p class="small-text">Academic Session 2026</p>
                <h1>Student Profile 👤</h1>
            </div>
        </header>

        <div class="profile-container">
            <!-- Left Side Card -->
            <div class="profile-card">
                <div class="avatar-box">👤</div>
                <h2 style="margin-bottom:5px;"><?php echo htmlspecialchars($student_name); ?></h2>
                <p style="color:#64748b; font-size:14px; margin-bottom:15px;"><?php echo htmlspecialchars($student_id); ?></p>
                <span style="background:#dcfce7; color:#166534; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:bold;">Active Student</span>
            </div>

            <!-- Right Side Details -->
            <div class="info-card">
                <h3 style="margin-bottom:20px; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">Personal Information</h3>
                
                <div class="info-grid">
                    <div class="info-group">
                        <label>Full Name</label>
                        <p><?php echo htmlspecialchars($student_name); ?></p>
                    </div>

                    <div class="info-group">
                        <label>Enrollment / User ID</label>
                        <p><?php echo htmlspecialchars($student_id); ?></p>
                    </div>

                    <div class="info-group">
                        <label>Branch</label>
                        <p><?php echo htmlspecialchars($student_branch); ?></p>
                    </div>

                    <div class="info-group">
                        <label>Current Semester</label>
                        <p><?php echo htmlspecialchars($student_sem); ?></p>
                    </div>

                    <div class="info-group">
                        <label>Email Address</label>
                        <p><?php echo htmlspecialchars($student_email); ?></p>
                    </div>

                    <div class="info-group">
                        <label>Role</label>
                        <p>Student</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>