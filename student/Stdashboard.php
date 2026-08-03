<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | K.D. Polytechnic</title>
    <link rel="stylesheet" href="../assets/css/student.css?v=5">
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
                <p class="small-text">Academic Session 2026</p>
                <h1>Student Dashboard</h1>
            </div>
            <div class="date-box">
                <button onclick="toggleDarkMode()" class="theme-toggle">🌙 Dark Mode</button>
            </div>
        </div>

        <div class="welcome-box">
            <div>
                <h2>Welcome back, Hamza! 👋</h2>
                <p style="margin-bottom: 8px;"><strong>Enrollment No:</strong> 216120307001 | <strong>Branch:</strong> Computer Engineering</p>
                <p><strong>Current Semester:</strong> Semester 5 | <strong>Academic Status:</strong> Active</p>
            </div>
            <div class="welcome-icon">🎓</div>
        </div>

        <section class="stats">
            <div class="stat-card blue">
                <div class="stat-icon">📄</div>
                <div>
                    <p>Total Practicals</p>
                    <h2>12</h2>
                </div>
            </div>

            <div class="stat-card green">
                <div class="stat-icon">✓</div>
                <div>
                    <p>Approved Manuals</p>
                    <h2>8</h2>
                </div>
            </div>

            <div class="stat-card orange">
                <div class="stat-icon">⏳</div>
                <div>
                    <p>Pending Approval</p>
                    <h2>4</h2>
                </div>
            </div>
        </section>

        <section class="semester-section" style="margin-bottom: 28px;">
            <h2>⚡ Current Active Practical (Week 3)</h2>
            <div style="background: #f8fafc; padding: 18px; border-radius: 12px; border-left: 5px solid #2563eb; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="color: #102a56; margin-bottom: 5px;">Practical #3: Implementation of Relational Database Queries</h3>
                    <p style="color: #64748b; font-size: 14px;">Subject: Database Management System (DBMS) | Deadline: End of Week</p>
                </div>
                <a href="upload-manual.php" class="submit-btn" style="text-decoration: none; font-size: 14px;">Upload Work 📤</a>
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

</body>
</html>