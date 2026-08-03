<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | K.D. Polytechnic</title>
    <link rel="stylesheet" href="../assets/css/student.css?v=4">
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
            <a class="active" href="Stdashboard.php">🏠 <span>Dashboard</span></a>
            <a href="upload-manual.php">📤 <span>Upload Manual</span></a>
            <a href="my-manuals.php">📚 <span>My Manuals</span></a>
            <a href="submission-history.php">🕘 <span>History</span></a>
            <a href="profile.php">👤 <span>My Profile</span></a>
            <a href="login.php" class="logout">⇥ <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">

        <div class="topbar">
            <div>
                <p class="small-text">Welcome back,</p>
                <h1>Student Dashboard</h1>
            </div>
            <div class="date-box">
                <button onclick="toggleDarkMode()" class="theme-toggle">🌙 Dark Mode</button>
            </div>
        </div>

        <div class="welcome-box">
            <div>
                <h2>Hello, Student! 👋</h2>
                <p>Manage your lab manuals, track submissions, and check your academic history all in one place.</p>
            </div>
            <div class="welcome-icon">🎓</div>
        </div>

        <section class="stats">
            <div class="stat-card blue">
                <div class="stat-icon">📄</div>
                <div>
                    <p>Total Manuals</p>
                    <h2>0</h2>
                </div>
            </div>

            <div class="stat-card green">
                <div class="stat-icon">✓</div>
                <div>
                    <p>Submitted</p>
                    <h2>0</h2>
                </div>
            </div>

            <div class="stat-card orange">
                <div class="stat-icon">⏳</div>
                <div>
                    <p>Pending</p>
                    <h2>0</h2>
                </div>
            </div>
        </section>

        <section class="quick-section">
            <h2>Quick Access</h2>

            <div class="quick-grid">
                <a href="upload-manual.php" class="quick-card">
                    <span class="quick-icon">⬆</span>
                    <h3>Upload Manual</h3>
                    <p>Submit a new lab manual</p>
                </a>

                <a href="my-manuals.php" class="quick-card">
                    <span class="quick-icon">📚</span>
                    <h3>My Manuals</h3>
                    <p>View all submitted manuals</p>
                </a>

                <a href="submission-history.php" class="quick-card">
                    <span class="quick-icon">🕘</span>
                    <h3>History</h3>
                    <p>Check your activity history</p>
                </a>

                <a href="profile.php" class="quick-card">
                    <span class="quick-icon">👤</span>
                    <h3>Profile</h3>
                    <p>Update your personal details</p>
                </a>
            </div>
        </section>

    </main>
</div>

</body>
</html>