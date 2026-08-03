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
                    <p>✓ Check subject and practical details before submitting</p>
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
                            <select name="subject" id="subjectSelect" onchange="autoFillPracticalDetails()" required>
                                <option value="">Select Subject</option>
                                <option value="Web Development">Web Development</option>
                                <option value="Database Management System">Database Management System</option>
                                <option value="Computer Network">Computer Network</option>
                                <option value="Java Programming">Java Programming</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Practical Number (Current Week) *</label>
                            <input type="number" name="experiment_no" id="practicalNoInput" placeholder="Auto-filled on subject select" min="1" required readonly style="background-color: #f1f5f9; cursor: not-allowed;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Practical Title *</label>
                        <input type="text" name="title" id="practicalTitleInput" placeholder="Select a subject to auto-fill practical name" required readonly style="background-color: #f1f5f9; cursor: not-allowed;">
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

<script>
// Subject-wise current week practical logic
const currentWeekPracticals = {
    "Web Development": {
        practical_no: 3,
        title: "Practical #3: Building Responsive Layouts using CSS Flexbox & Grid"
    },
    "Database Management System": {
        practical_no: 3,
        title: "Practical #3: Implementation of Relational DDL and DML Queries"
    },
    "Computer Network": {
        practical_no: 3,
        title: "Practical #3: IP Addressing, Subnetting, and Ping Commands"
    },
    "Java Programming": {
        practical_no: 3,
        title: "Practical #3: Class Inheritance and Method Overriding Concepts"
    }
};

function autoFillPracticalDetails() {
    const subjectSelect = document.getElementById("subjectSelect").value;
    const practicalNoInput = document.getElementById("practicalNoInput");
    const practicalTitleInput = document.getElementById("practicalTitleInput");

    if (subjectSelect && currentWeekPracticals[subjectSelect]) {
        // Auto fill current week data
        practicalNoInput.value = currentWeekPracticals[subjectSelect].practical_no;
        practicalTitleInput.value = currentWeekPracticals[subjectSelect].title;
    } else {
        // Clear if no subject chosen
        practicalNoInput.value = "";
        practicalTitleInput.value = "";
    }
}
</script>

</body>
</html>