<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_name = $_SESSION['name'] ?? 'Student';
$student_id = $_SESSION['user_id'] ?? '';

// 1. DELETE LOGIC (Jab student Delete par click kare)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['file_id'])) {
    $delete_id = $_GET['file_id'];
    $submissions_file = '../Faculty/submissions.json';

    if (file_exists($submissions_file)) {
        $all_subs = json_decode(file_get_contents($submissions_file), true);
        if (is_array($all_subs)) {
            $updated_subs = [];
            foreach ($all_subs as $sub) {
                // Match Enrollment & File Unique Identifier
                if (isset($sub['enrollment']) && $sub['enrollment'] == $student_id && isset($sub['filename']) && $sub['filename'] === $delete_id) {
                    // Physical file delete from uploads directory
                    $file_path = "../uploads/" . $sub['filename'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    continue; // Is entry ko array me include nahi karenge (Delete ho gayi)
                }
                $updated_subs[] = $sub;
            }
            // JSON file update karo
            file_put_contents($submissions_file, json_encode($updated_subs, JSON_PRETTY_PRINT));
            header("Location: my-manuals.php?status=deleted");
            exit();
        }
    }
}

// 2. Get Student's Current Semester from users.json
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

// 3. Load all submissions from submissions.json
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .manuals-table-container {
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            margin-top: 20px;
        }
        .filter-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .semester-select {
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            outline: none;
            font-size: 14px;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .status-approved { background: #dcfce7; color: #15803d; }
        .status-pending { background: #fef3c7; color: #b45309; }
        .status-rejected { background: #fee2e2; color: #b91c1c; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 14px;
        }
        th { background: #f8fafc; color: #475569; font-weight: 600; }
        
        .action-cell {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .btn-action {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            background: #eff6ff;
            border-radius: 6px;
            transition: 0.2s;
            font-size: 13px;
        }
        .btn-action:hover { background: #dbeafe; }

        .btn-delete {
            color: #dc2626;
            background: #fef2f2;
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 500;
            transition: 0.2s;
        }
        .btn-delete:hover { background: #fee2e2; }

        .empty-container {
            text-align: center;
            padding: 40px 20px;
        }
        .empty-container i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 12px;
        }
        .empty-container p {
            color: #64748b;
            margin-bottom: 16px;
            font-size: 15px;
        }
        .btn-upload-now {
            background: #1e3a8a;
            color: #fff;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-upload-now:hover { background: #1e40af; }
        
        .alert-success {
            background: #dcfce7;
            color: #15803d;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
        }
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
            <?php if(isset($_GET['status']) && $_GET['status'] === 'deleted'): ?>
                <div class="alert-success">
                    <i class="fa-solid fa-check-circle"></i> Manual successfully deleted!
                </div>
            <?php endif; ?>

            <div class="filter-box">
                <h2 style="font-size: 18px; margin: 0;">Select Semester:</h2>
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

    function confirmDelete(filename) {
        return confirm("Kya aap sure hain ki is manual ko delete karna chahte hain?");
    }

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
                    let statusText = m.status || 'Pending';
                    if (statusText.toLowerCase() === 'approved') statusClass = 'status-approved';
                    if (statusText.toLowerCase() === 'rejected') statusClass = 'status-rejected';

                    tbody.innerHTML += `
                        <tr>
                            <td><strong>${m.subject || 'N/A'}</strong></td>
                            <td>${m.title || 'Practical File'}</td>
                            <td>${m.date || 'N/A'}</td>
                            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                            <td>${m.remarks || 'No remarks'}</td>
                            <td>
                                <div class="action-cell">
                                    <a href="../uploads/${m.filename || '#'}" target="_blank" class="btn-action">
                                        <i class="fa-regular fa-file-pdf"></i> View
                                    </a>
                                    <a href="my-manuals.php?action=delete&file_id=${encodeURIComponent(m.filename)}" 
                                       onclick="return confirmDelete('${m.filename}')" 
                                       class="btn-delete" title="Delete Manual">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                </div>
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
                    <td colspan="6">
                        <div class="empty-container">
                            <i class="fa-solid fa-folder-open"></i>
                            <p>No manuals uploaded for <strong>${selectedSem}</strong> yet.</p>
                            <a href="upload-manual.php" class="btn-upload-now">
                                <i class="fa-solid fa-cloud-arrow-up"></i> Upload Manual
                            </a>
                        </div>
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