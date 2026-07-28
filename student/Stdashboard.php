<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | K.D. Polytechnic</title>
    <link rel="stylesheet" href="../assets/css/student.css?v=2">
</head>
<body>

<div class="app">

    <aside class="sidebar">
        <div class="college-name">
            <div class="logo-circle">KD</div>
            <div>
                <h2>K.D. Polytechnic</h2>
                <p>Student Portal</p>
            </div>
        </div>

        <nav class="nav-links">
            <a class="active" href="dashboard.php">⌂ <span>Dashboard</span></a>
            <a href="my-manuals.php">📚 <span>My Manuals</span></a>
            <a href="history.php">🕘 <span>History</span></a>
            <a href="profile.php">👤 <span>My Profile</span></a>
            <a href="login.php" class="logout">⇥ <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div>
                <p class="small-text">Student Dashboard</p>
                <h1>Welcome back, Student! 👋</h1>
            </div>
            <div class="date-box">
                <span>📅</span> 2026
            </div>
        </header>

        <section class="welcome-box">
            <div>
                <h2>Digital Lab Manual & Expense Tracker</h2>
                <p>Upload your lab manual, check submitted work and manage your academic records easily.</p>
            </div>
            <div class="welcome-icon">🎓</div>
        </section>

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

                <a href="history.php" class="quick-card">
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