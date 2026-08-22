<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_name = $_SESSION['name'] ?? 'Student';
$student_id = $_SESSION['user_id'] ?? ''; 

// Default details
$email = "student@kdpolytechnic.edu.in";
$branch = "Computer Engineering";
$semester = "Semester 5";
$contact = "+91 9876543210";
$academic_year = "2024 - 2027";
$success_msg = "";
$error_msg = "";
// Fetch User Data from users.json
$users_file = '../users.json';
if (file_exists($users_file)) {
    $users_data = json_decode(file_get_contents($users_file), true);
    if (is_array($users_data)) {
        foreach ($users_data as $u) {
            if (isset($u['user_id']) && $u['user_id'] === $student_id) {
                $email = $u['email'] ?? $email;
                $branch = $u['branch'] ?? $branch;
                $semester = $u['sem'] ?? $semester;
                $contact = $u['contact'] ?? $contact;
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
            grid-template-columns: 320px 1fr;
            gap: 25px;
            margin-top: 20px;
        }
        .profile-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
         .avatar-box {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #102a56, #2563eb);
            color: white;
            font-size: 38px;
            font-weight: bold;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
            box-shadow: 0 8px 16px rgba(16, 42, 86, 0.2);
        }
         .profile-card h2 {
            font-size: 20px;
            color: #0f172a;
            margin-bottom: 5px;
            }

        .profile-card p {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .badge-active {
            background: #dcfce7;
            color: #15803d;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
.info-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .info-card h3 {
            font-size: 16px;
            color: #102a56;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 10px;
        }
        .info-group {
            margin-bottom: 15px;
        }

        .info-group label {
            display: block;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-group p {
            font-size: 14px;
            color: #1e293b;
            font-weight: 500;
            margin: 0;
        }
         .info-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
            box-sizing: border-box;
        }

        .info-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-submit {
            background: #102a56;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-submit:hover {
            background: #1d4ed8;
        }
 @media (max-width: 900px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }

        /* Dark Mode Support */
        body.dark-mode .profile-card, 
        body.dark-mode .info-card {
            background: #1e293b;
            border-color: #334155;
        }

        body.dark-mode .profile-card h2,
        body.dark-mode .info-group p {
            color: #f8fafc;
        }

        body.dark-mode .info-card h3 {
            color: #38bdf8;
            border-bottom-color: #334155;
        }

        body.dark-mode .info-input {
            background: #0f172a;
            border-color: #334155;
            color: white;
        }
    </style>
</head>
<body>
    <script>
function toggleDarkMode() {
    document.body.classList.toggle("dark-mode");
}
</script>

<div class="app">
    <aside class="sidebar">
        <div class="college-name">
            <img src="../assets/images/KDP-Logo.png" alt="K.D. Polytechnic Logo" class="college-logo">
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
                <h1>My Profile</h1>
            </div>
            <div>
                <button onclick="toggleDarkMode()" class="theme-toggle">🌙 Dark Mode</button>
            </div>
        </header>

        <div class="profile-container">
            <!-- Left Side Avatar Card -->
            <div class="profile-card">
                <div class="avatar-box">
                    <?php echo strtoupper(substr($student_name, 0, 1)); ?>
                </div>
                <h2><?php echo htmlspecialchars($student_name); ?></h2>
                <p>Enrollment: <strong><?php echo htmlspecialchars($student_id); ?></strong></p>
                <span class="badge-active">Active Student</span>
            </div>
  <!-- Right Side Information Cards -->
            <div class="details-grid">
                <!-- Academic Details -->
                <div class="info-card">
                    <h3>🎓 Academic Information</h3>
                    <div class="info-group">
                        <label>Department / Branch</label>
                        <p><?php echo htmlspecialchars($branch); ?></p>
                    </div>
                    <div class="info-group">
                        <label>Current Semester</label>
                        <p><?php echo htmlspecialchars($semester); ?></p>
                    </div>
                    <div class="info-group">
                        <label>Enrollment Number</label>
                        <p><?php echo htmlspecialchars($student_id); ?></p>
                    </div>
                    <div class="info-group">
                        <label>Academic Duration</label>
                        <p><?php echo htmlspecialchars($academic_year); ?></p>
                    </div>
                </div>
