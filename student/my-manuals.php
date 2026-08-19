<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_name = $_SESSION['name'] ?? 'Student';
$student_id = $_SESSION['user_id'] ?? '';

// 1. Get Student's Current Semester from users.json
$student_sem = "Semester 5"; // Default fallback
$users_file = '../users.json';
if (file_exists($users_file)) {
    $users_data = json_decode(file_get_contents($users_file), true);
    if (is_array($users_data)) {
        foreach ($users_data as $u) {
            if (isset($u['user_id']) && $u['user_id'] === $student_id) {
                if (isset($u['sem'])) {
                    $student_sem = $u['sem'];
                }
                break;
            }
        }
    }
}

// 2. Load all submissions from submissions.json
$submissions_file = '../Faculty/submissions.json';
$my_submissions = [];

if (file_exists($submissions_file)) {
    $all_subs = json_decode(file_get_contents($submissions_file), true);
    if (is_array($all_subs)) {
        foreach ($all_subs as $sub) {
            if (isset($sub['enrollment']) && $sub['enrollment'] == $student_id) {
                $my_submissions[] = $sub;
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
    <title>My Manuals | K.D. Polytechnic</title>
    <link rel="stylesheet" href="../assets/css/student.css?v=8">
    <style>
        .manuals-table-container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .filter-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        th { background: #f8fafc; color: #475569; }
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
            <a class="active" href="my-manuals.php">📚 <span>My Manuals</span></a>
            <a href="submission-history.php">🕘 <span>History</span></a>
            <a href="profile.php">👤 <span>My Profile</span></a>
            <a href="../logout.php" class="logout">⇥ <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <p class="small-text">Academic Session 2026</p>
                <h1>My Submitted Manuals 📚</h1>
            </div>
        </header>

        <div class="manuals-table-container">
            <div class="filter-box">
                <h2>Select Semester:</h2>
                <select class="semester-select" id="semSelect" onchange="filterManuals()">
                    <option <?php echo ($student_sem == 'Semester 1') ? 'selected' : ''; ?>>Semester 1</option>
                    <option <?php echo ($student_sem == 'Semester 2') ? 'selected' : ''; ?>>Semester 2</option>
                    <option <?php echo ($student_sem == 'Semester 3') ? 'selected' : ''; ?>>Semester 3</option>
                    <option <?php echo ($student_sem == 'Semester 4') ? 'selected' : ''; ?>>Semester 4</option>
                    <option <?php echo ($student_sem == 'Semester 5') ? 'selected' : ''; ?>>Semester 5</option>
                    <option <?php echo ($student_sem == 'Semester 6') ? 'selected' : ''; ?>>Semester 6</option>
                </select>
            </div>

            <table id="manualsTable">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Practical Title</th>
                        <th>Date Uploaded</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    </tbody>
            </table>
        </div>
    </main>
</div>

<script>
    const allManuals = <?php echo json_encode($my_submissions); ?>;

    function filterManuals() {
        const selectedSem = document.getElementById('semSelect').value;
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = '';

        let count = 0;

        if (allManuals && allManuals.length > 0) {
            allManuals.forEach((m) => {
                let mSem = m.sem ? m.sem : 'Semester 5';

                if (mSem === selectedSem) {
                    let statusClass = 'status-pending';
                    if (m.status.toLowerCase() === 'approved') statusClass = 'status-approved';
                    if (m.status.toLowerCase() === 'rejected') statusClass = 'status-rejected';

                    tbody.innerHTML += `
                        <tr>
                            <td><strong>${m.subject || 'N/A'}</strong></td>
                            <td>${m.title || 'Practical File'}</td>
                            <td>${m.date || 'N/A'}</td>
                            <td><span class="status-badge ${statusClass}">${m.status || 'Pending'}</span></td>
                            <td>${m.remarks || 'No remarks'}</td>
                            <td>
                                <a href="../uploads/${m.filename || '#'}" target="_blank" style="color:#2563eb; text-decoration:none;">📄 View PDF</a>
                            </td>
                        </tr>
                    `;
                    count++;
                }
            });
        }

        if (count === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align:center; color:#64748b; padding:20px;">
                        No manuals uploaded for ${selectedSem} yet.
                    </td>
                </tr>
            `;
        }
    }

    window.onload = function() {
        filterManuals();
    };
</script>
</body>
</html>