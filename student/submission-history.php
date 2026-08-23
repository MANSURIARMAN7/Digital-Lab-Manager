<?php
// Error display ON taaki koi issue ho toh turant dikhe
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_name = $_SESSION['name'] ?? 'Student';
$student_id = $_SESSION['user_id'] ?? '';

// Load Submissions History
$submissions_file = '../Faculty/submissions.json';
$history_list = [];

if (file_exists($submissions_file)) {
    $all_subs = json_decode(file_get_contents($submissions_file), true);
    if (is_array($all_subs)) {
        foreach ($all_subs as $sub) {
            if (isset($sub['enrollment']) && $sub['enrollment'] == $student_id) {
                $history_list[] = $sub;
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
    <title>Submission History | K.D. Polytechnic</title>
    <link rel="stylesheet" href="../assets/css/student.css?v=8">
    <style>
        .history-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef9c3; color: #854d0e; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
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
            <a href="my-manuals.php">📚 <span>My Manuals</span></a>
            <a class="active" href="submission-history.php">🕘 <span>History</span></a>
            <a href="profile.php">👤 <span>My Profile</span></a>
            <a href="../logout.php" class="logout">⇥ <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <p class="small-text">Academic Session 2026</p>
                <h1>Submission History 🕘</h1>
            </div>
        </header>

        <div class="history-card">
            <h2>All Past Activity</h2>
            <p style="color:#64748b; font-size:14px;">A complete log of all lab manuals you have submitted so far.</p>

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subject</th>
                        <th>Practical Title</th>
                        <th>Submitted On</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($history_list) > 0): ?>
                        <?php foreach ($history_list as $index => $item): ?>
                            <?php 
                                $status = strtolower($item['status'] ?? 'pending');
                                $badgeClass = 'status-pending';
                                if ($status === 'approved') $badgeClass = 'status-approved';
                                if ($status === 'rejected') $badgeClass = 'status-rejected';
                            ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($item['subject'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['title'] ?? 'Practical File'); ?></td>
                                <td><?php echo htmlspecialchars($item['date'] ?? date('Y-m-d')); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">
                                No submission history found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

</body>
</html>