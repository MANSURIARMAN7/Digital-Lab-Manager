<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_name = $_SESSION['name'] ?? 'Student';
$student_id = $_SESSION['user_id'] ?? ''; 

// 1. STATS LOGIC
$json_file = '../Faculty/submissions.json'; 
$total_manuals = 0; $approved_manuals = 0; $pending_manuals = 0;

if (file_exists($json_file)) {
    $all_submissions = json_decode(file_get_contents($json_file), true);
    if (is_array($all_submissions)) {
        foreach ($all_submissions as $sub) {
            if (isset($sub['enrollment']) && $sub['enrollment'] == $student_id) {
                $total_manuals++;
                $status = strtolower($sub['status']);
                if ($status == 'approved') $approved_manuals++;
                else if ($status == 'pending') $pending_manuals++;
            }
        }
    }
}

// 2. SUBJECT LOGIC
$student_subjects = [];
$student_sem = "Semester 5"; 

$users_file = '../users.json';
if (file_exists($users_file)) {
    $users_data = json_decode(file_get_contents($users_file), true);
    if (is_array($users_data)) {
        foreach ($users_data as $u) {
            if (isset($u['user_id']) && $u['user_id'] === $student_id) {
                if (isset($u['subjects'])) {
                    $student_subjects = $u['subjects'];
                }
                if (isset($u['sem'])) {
                    $student_sem = $u['sem'];
                }
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
    <title>Student Dashboard | K.D. Polytechnic</title>
    <link rel="stylesheet" href="../assets/css/student.css?v=8">
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
            <a class="active" href="stdashboard.php">🏠 <span>Dashboard</span></a>
            <a href="upload-manual.php">📤 <span>Upload Manual</span></a>
            <a href="my-manuals.php">📚 <span>My Manuals</span></a>
            <a href="submission-history.php">🕘 <span>History</span></a>
            <a href="profile.php">👤 <span>My Profile</span></a>
            <a href="../logout.php" class="logout">⇥ <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <p class="small-text">Academic Session 2026</p>
                <h1>Student Dashboard</h1>
            </div>
            <div class="date-box" style="display: flex; align-items: center; gap: 10px; background: transparent; padding: 0; border: none; box-shadow: none;">
                <div class="notification-box" style="position: relative; cursor: pointer; background: #102a56; color: white; padding: 10px 14px; border-radius: 9px; display: flex; align-items: center; justify-content: center; height: 42px; box-sizing: border-box;">
                    🔔<span style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: bold; line-height: 1;">2</span>
                </div>
                <button onclick="toggleDarkMode()" class="theme-toggle">🌙 Dark Mode</button>
            </div>
        </header>

        <div class="welcome-box">
            <div>
                <h2>Welcome back, <?php echo htmlspecialchars($student_name); ?>! 👋</h2>
                <p style="margin-bottom: 8px;"><strong>Enrollment No:</strong> <?php echo htmlspecialchars($student_id); ?> | <strong>Branch:</strong> Computer Engineering</p>
                <p><strong>Current Semester:</strong> <?php echo htmlspecialchars($student_sem); ?> | <strong>Academic Status:</strong> Active</p>
            </div>
            <div class="welcome-icon">🎓</div>
        </div>

        <section class="stats">
            <div class="stat-card blue"><div class="stat-icon">📄</div><div><p>Total Manuals</p><h2><?php echo $total_manuals; ?></h2></div></div>
            <div class="stat-card green"><div class="stat-icon">✓</div><div><p>Approved</p><h2><?php echo $approved_manuals; ?></h2></div></div>
            <div class="stat-card orange"><div class="stat-icon">⏳</div><div><p>Pending</p><h2><?php echo $pending_manuals; ?></h2></div></div>
        </section>

        <section class="semester-section">
            <h2>Select Semester</h2>
            <select class="semester-select" id="semSelect" onchange="filterSubjects()">
                <option <?php echo ($student_sem == 'Semester 1') ? 'selected' : ''; ?>>Semester 1</option>
                <option <?php echo ($student_sem == 'Semester 2') ? 'selected' : ''; ?>>Semester 2</option>
                <option <?php echo ($student_sem == 'Semester 3') ? 'selected' : ''; ?>>Semester 3</option>
                <option <?php echo ($student_sem == 'Semester 4') ? 'selected' : ''; ?>>Semester 4</option>
                <option <?php echo ($student_sem == 'Semester 5') ? 'selected' : ''; ?>>Semester 5</option>
                <option <?php echo ($student_sem == 'Semester 6') ? 'selected' : ''; ?>>Semester 6</option>
            </select>

            <h2 style="margin-top:25px;">Subjects</h2>
            <div class="subject-grid" id="subjectContainer">
                <!-- Data will be loaded here dynamically -->
            </div>
        </section>

        <section class="quick-section">
            <h2>Quick Access</h2>
            <div class="quick-grid">
                <a href="upload-manual.php" class="quick-card">
                    <span class="quick-icon">⬆</span>
                    <h3>Upload Manual</h3>
                    <p>Submit your current practical file</p>
                </a>
                <a href="my-manuals.php" class="quick-card">
                    <span class="quick-icon">📚</span>
                    <h3>My Manuals</h3>
                    <p>View & track submitted manuals</p>
                </a>
                <a href="submission-history.php" class="quick-card">
                    <span class="quick-icon">🕘</span>
                    <h3>History</h3>
                    <p>Check past practical status</p>
                </a>
                <a href="profile.php" class="quick-card">
                    <span class="quick-icon">👤</span>
                    <h3>Profile</h3>
                    <p>Update photo & personal details</p>
                </a>
            </div>
        </section>
    </main>
</div>

<script>
    const allSubjects = <?php echo json_encode($student_subjects); ?>;
    const icons = ['🌐', '🗄', '📡', '☕', '🐍', '💻', '📱', '⚙️'];

    function filterSubjects() {
        const selectedSem = document.getElementById('semSelect').value;
        const container = document.getElementById('subjectContainer');
        container.innerHTML = ''; 

        let count = 0;

        if (allSubjects && allSubjects.length > 0) {
            allSubjects.forEach((sub) => {
                let subName = sub.name ? sub.name : sub;
                let subSem = sub.sem ? sub.sem : 'Semester 5'; 

                if (subSem === selectedSem) {
                    let icon = icons[count % icons.length];
                    let encodedName = encodeURIComponent(subName);
                    
                    container.innerHTML += `
                        <a href="upload-manual.php?subject=${encodedName}" class="subject-card">
                            ${icon} <h3>${subName}</h3>
                        </a>
                    `;
                    count++;
                }
            });
        }

        if (count === 0) {
            container.innerHTML = `
                <div style='grid-column: 1 / -1; background: #fff; padding: 20px; text-align: center; border-radius: 12px; color: #64748b;'>
                    <h3 style='margin-bottom:10px;'>No Subjects Found</h3>
                    <p>No subjects assigned to you for ${selectedSem}.</p>
                </div>
            `;
        }
    }

    window.onload = function() {
        filterSubjects();
    };
</script>
</body>
</html>