<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_name = $_SESSION['name'];
$student_id = $_SESSION['user_id']; 

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
<<<<<<< Updated upstream

<script>
function toggleDarkMode() {
    document.body.classList.toggle("dark-mode");
}
</script>
=======
    <script>
    function toggleDarkMode() { document.body.classList.toggle("dark-mode"); }
    </script>
>>>>>>> Stashed changes

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
            <a href="login.php" class="logout">⇥ <span>Logout</span></a>
            <a href="../logout.php" class="logout">⇥ <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
<<<<<<< Updated upstream

        <div class="topbar">
=======
        <header class="topbar">
>>>>>>> Stashed changes
            <div>
                <p class="small-text">Welcome back,</p>
                <h1>Student Dashboard</h1>
                <p class="small-text">Student Dashboard</p>
                <h1>Welcome back, <?php echo htmlspecialchars($student_name); ?>! 👋</h1>
            </div>
            <div class="date-box">
<<<<<<< Updated upstream
                <button onclick="toggleDarkMode()" class="theme-toggle">🌙 Dark Mode</button>
=======
                <span>📅</span> <?php echo date("Y"); ?>
                <button class="theme-toggle" onclick="toggleDarkMode()">🌙 Dark Mode</button>
>>>>>>> Stashed changes
            </div>
        </div>

        <div class="welcome-box">
            <div>
                <h2>Hello, Student! 👋</h2>
                <p>Manage your lab manuals, track submissions, and check your academic history all in one place.</p>
            </div>
            <div class="welcome-icon">🎓</div>
<<<<<<< Updated upstream
        </div>
=======
        </section>
>>>>>>> Stashed changes

        <section class="stats">
            <div class="stat-card blue"><div class="stat-icon">📄</div><div><p>Total Manuals</p><h2><?php echo $total_manuals; ?></h2></div></div>
            <div class="stat-card green"><div class="stat-icon">✓</div><div><p>Approved</p><h2><?php echo $approved_manuals; ?></h2></div></div>
            <div class="stat-card orange"><div class="stat-icon">⏳</div><div><p>Pending</p><h2><?php echo $pending_manuals; ?></h2></div></div>
        </section>

        <section class="semester-section">
            <h2>Select Semester</h2>
            <!-- YAHAN DROPDOWN MEIN onchange EVENT LAGAYA HAI -->
            <select class="semester-select" id="semSelect" onchange="filterSubjects()">
                <option <?php echo ($student_sem == 'Semester 1') ? 'selected' : ''; ?>>Semester 1</option>
                <option <?php echo ($student_sem == 'Semester 2') ? 'selected' : ''; ?>>Semester 2</option>
                <option <?php echo ($student_sem == 'Semester 3') ? 'selected' : ''; ?>>Semester 3</option>
                <option <?php echo ($student_sem == 'Semester 4') ? 'selected' : ''; ?>>Semester 4</option>
                <option <?php echo ($student_sem == 'Semester 5') ? 'selected' : ''; ?>>Semester 5</option>
                <option <?php echo ($student_sem == 'Semester 6') ? 'selected' : ''; ?>>Semester 6</option>
            </select>

            <h2 style="margin-top:25px;">Subjects</h2>
            
            <!-- JS Is container ke andar subjects bharegi -->
            <div class="subject-grid" id="subjectContainer">
                <!-- Data will be loaded here dynamically -->
            </div>
        </section>

        <section class="quick-section">
            <h2>Quick Access</h2>
            <div class="quick-grid">
                <a href="upload-manual.php" class="quick-card"><span class="quick-icon">⬆</span><h3>Upload Manual</h3><p>Submit a new lab manual</p></a>
                <a href="my-manuals.php" class="quick-card"><span class="quick-icon">📚</span><h3>My Manuals</h3><p>View all submitted manuals</p></a>
                <a href="submission-history.php" class="quick-card"><span class="quick-icon">🕘</span><h3>History</h3><p>Check your activity history</p></a>
                <a href="profile.php" class="quick-card"><span class="quick-icon">👤</span><h3>Profile</h3><p>Update your personal details</p></a>
            </div>
        </section>
    </main>
</div>

<!-- 🔥 YE SCRIPT JSON DATA SE MENU FILTER KAREGI -->
<script>
    // PHP se saare subjects JSON format me nikal liye
    const allSubjects = <?php echo json_encode($student_subjects); ?>;
    const icons = ['🌐', '🗄', '📡', '☕', '🐍', '💻', '📱', '⚙️'];

    function filterSubjects() {
        const selectedSem = document.getElementById('semSelect').value;
        const container = document.getElementById('subjectContainer');
        container.innerHTML = ''; // Pehle purana data saaf karo

        let count = 0;

        if (allSubjects && allSubjects.length > 0) {
            allSubjects.forEach((sub) => {
                // Agar array/object format me hai toh use karo, warna directly string
                let subName = sub.name ? sub.name : sub;
                let subSem = sub.sem ? sub.sem : 'Semester 5'; 

                // Agar JSON ka semester aur Menu ka semester match ho raha hai
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

        // Agar koi subject nahi mila toh Error message dikhao
        if (count === 0) {
            container.innerHTML = `
                <div style='grid-column: 1 / -1; background: #fff; padding: 20px; text-align: center; border-radius: 12px; color: #64748b;'>
                    <h3 style='margin-bottom:10px;'>No Subjects Found</h3>
                    <p>No subjects assigned to you for ${selectedSem}.</p>
                </div>
            `;
        }
    }

    // Page load hote hi ek baar function run kar do taaki default subject aa jaye
    window.onload = function() {
        filterSubjects();
    };
</script>
</body>
</html>