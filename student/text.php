<?php echo "Server Working Perfectly!"; ?>

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
iv class="college-name">
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
                                if ($status === 'appro       if ($status === 'rejected') $badgeClass = 'status-rejected';
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