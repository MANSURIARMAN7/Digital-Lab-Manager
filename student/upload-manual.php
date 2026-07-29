<?php
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $successMessage = "Lab manual submitted successfully! (Demo)";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Manual | K.D. Polytechnic</title>
    <link rel="stylesheet" href="../assets/css/student.css?v=3">
</head>
<body>

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
            <a href="Stdashboard.php">🏠 <span>Dashboard</span></a>
            <a class="active" href="upload-manual.php">📤 <span>Upload Manual</span></a>
            <a href="my-manuals.php">📚 <span>My Manuals</span></a>
            <a href="submission-history.php">🕘 <span>History</span></a>
            <a href="profile.php">👤 <span>My Profile</span></a>
            <a href="../login.php" class="logout">⇥ <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">

        <header class="topbar">
            <div>
                <p class="small-text">Student Portal</p>
                <h1>Upload Lab Manual 📤</h1>
            </div>
            <div class="date-box">
                <span>📅</span> 2026
            </div>
        </header>

        <section class="upload-page">

            <div class="upload-info">
                <div class="info-icon">📄</div>
                <h2>Submit Your Lab Manual</h2>
                <p>Fill in the details carefully and upload your lab manual file.</p>

                <div class="guidelines">
                    <h3>Guidelines</h3>
                    <p>✓ Upload PDF, DOC, or DOCX file</p>
                    <p>✓ Maximum file size: 10 MB</p>
                    <p>✓ Check subject and experiment number before submitting</p>
                </div>
            </div>

            <div class="form-card">

                <?php if ($successMessage != "") { ?>
                    <div class="success-message">
                        ✅ <?php echo $successMessage; ?>
                    </div>
                <?php } ?>

                <h2>Manual Details</h2>
                <p class="form-note">Fields marked with * are required.</p>

                <form method="POST" enctype="multipart/form-data">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Subject Name *</label>
                            <select name="subject" required>
                                <option value="">Select Subject</option>
                                <option>Web Development</option>
                                <option>Database Management System</option>
                                <option>Computer Network</option>
                                <option>Java Programming</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Experiment Number *</label>
                            <input type="number" name="experiment_no" placeholder="Example: 1" min="1" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Manual Title *</label>
                        <input type="text" name="title" placeholder="Example: HTML Basic Tags Experiment" required>
                    </div>

                    <div class="form-group">
                        <label>Upload File *</label>
                        <input type="file" name="manual_file" accept=".pdf,.doc,.docx" required>
                        <small>Accepted: PDF, DOC, DOCX (Maximum 10 MB)</small>
                    </div>

                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" rows="4" placeholder="Write any note here (optional)"></textarea>
                    </div>

                    <button type="submit" class="submit-btn">📤 Submit Manual</button>
                </form>
            </div>

        </section>
    </main>
</div>

</body>
</html>