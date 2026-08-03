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

 <!-- Top Bar -->
        <header class="topbar">

            <div>
                <p class="small-text">Student Dashboard</p>
                <h1>Welcome Back, Student 👋</h1>
            </div>

            <div class="date-box">
                <button class="theme-toggle" onclick="toggleDarkMode()">
                    🌙 Dark Mode
                </button>
            </div>

        </header>


        <!-- Welcome Section -->
        <section class="welcome-box">

            <div>

                <h2>Digital Lab Manual & Expense Tracker</h2>

                <p>
                    Manage your practical manuals, submissions,
                    expenses and academic progress from one dashboard.
                </p>

            </div>

            <div class="welcome-icon">
                🎓
            </div>

        </section>


        <!-- Student Profile -->

        <section class="profile-card">

            <div class="profile-left">

                <div class="profile-image">
                    👨‍🎓
                </div>

                <div>

                    <h2>Hamza Belim</h2>

                    <p>Enrollment No : 230250107001</p>

                    <p>Branch : Computer Engineering</p>

                    <p>Semester : 5</p>

                </div>

            </div>

            <a href="profile.php" class="edit-profile">
                Edit Profile
            </a>

        </section>


        <!-- Statistics -->

        <section class="stats">

            <div class="stat-card blue">
                <h3>Total Experiments</h3>
                <h1>0</h1>
            </div>

            <div class="stat-card green">
                <h3>Completed</h3>
                <h1>0</h1>
            </div>

            <div class="stat-card orange">
                <h3>Pending</h3>
                <h1>0</h1>
            </div>

            <div class="stat-card red">
                <h3>Total Expenses</h3>
                <h1>₹0</h1>
            </div>

        </section>


        <!-- Semester -->

        <section class="semester-section">

            <h2>Select Semester</h2>

            <select class="semester-select">

                <option>Semester 1</option>
                <option>Semester 2</option>
                <option>Semester 3</option>
                <option>Semester 4</option>
                <option selected>Semester 5</option>
                <option>Semester 6</option>

            </select>

        </section>
 <!-- Subjects -->

        <section class="subjects-section">

            <h2>Lab Subjects</h2>

            <div class="subject-grid">

                <a href="web-development.php" class="subject-card">
                    <span>🌐</span>
                    <h3>Web Development</h3>
                    <p>View Experiments</p>
                </a>

                <a href="dbms.php" class="subject-card">
                    <span>🗄️</span>
                    <h3>Database Management System</h3>
                    <p>View Experiments</p>
                </a>

                <a href="python.php" class="subject-card">
                    <span>🐍</span>
                    <h3>Python Programming</h3>
                    <p>View Experiments</p>
                </a>

                <a href="java.php" class="subject-card">
                    <span>☕</span>
                    <h3>Java Programming</h3>
                    <p>View Experiments</p>
                </a>

                <a href="iot.php" class="subject-card">
                    <span>📡</span>
                    <h3>Internet of Things</h3>
                    <p>View Experiments</p>
                </a>

            </div>

        </section>


        <!-- Recent Activity -->

        <section class="activity-section">

            <h2>Recent Activity</h2>

            <div class="activity-box">

                <p>📄 No practical uploaded yet.</p>
                <p>⏳ No pending submissions.</p>
                <p>✅ Faculty approval will appear here.</p>

            </div>

        </section>


        <!-- Quick Access -->

        <section class="quick-section">

            <h2>Quick Access</h2>

            <div class="quick-grid">

                <a href="upload-manual.php" class="quick-card">
                    📤
                    <h3>Upload Manual</h3>
                    <p>Upload practical file</p>
                </a>

                <a href="my-manuals.php" class="quick-card">
                    📚
                    <h3>My Manuals</h3>
                    <p>View submitted manuals</p>
                </a>

                <a href="submission-history.php" class="quick-card">
                    🕘
                    <h3>Submission History</h3>
                    <p>Check all submissions</p>
                </a>

                <a href="profile.php" class="quick-card">
                    👤
                    <h3>My Profile</h3>
                    <p>Manage profile</p>
                </a>

            </div>

        </section>
</main>

</div>

<script>
function toggleDarkMode() {
    document.body.classList.toggle("dark-mode");
}

// Current Year
document.getElementById("year").innerHTML = new Date().getFullYear();
</script>

<footer class="footer">
    <p>
        © <span id="year"></span> K.D. Polytechnic |
        Digital Lab Manual & Expense Tracker
    </p>
</footer>

</body>
</html>



