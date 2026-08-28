<?php
session_start();
include '../db.php';

// Check Login
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$enrollment = $conn->real_escape_string((string)$_SESSION['user_id']);

// Fetch All Submissions
$query = "SELECT * FROM student_submissions WHERE student_id = '$enrollment' ORDER BY submitted_at DESC";
$submissions = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - K.D. Polytechnic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-width: 260px; --bg-color: #f4f7f6; --sidebar-bg: #1b365d; --primary-blue: #3b82f6; }
        body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        
        /* SIDEBAR */
        .sidebar { width: var(--sidebar-width); background-color: var(--sidebar-bg); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar-header { padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 15px; }
        .sidebar-header img { width: 80px; height: 80px; object-fit: contain; margin-bottom: 15px; background: white; border-radius: 50%; padding: 5px; }
        .sidebar-title { font-size: 18px; font-weight: 700; margin: 0 0 5px 0; letter-spacing: 0.5px; }
        
        .nav-links { list-style: none; padding: 0 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 14px 20px; margin: 5px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 500; color: #cbd5e1; transition: 0.2s; }
        .nav-links li:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-links li.active { background-color: var(--primary-blue); color: white; box-shadow: 0 4px 10px rgba(59,130,246,0.3); }

        /* MAIN CONTENT */
        .main { flex: 1; overflow-y: auto; padding: 30px 40px; }
        .header-title h2 { font-size: 26px; font-weight: 700; color: #0f172a; margin: 0 0 5px 0; }
        .header-title p { font-size: 14px; color: #64748b; margin: 0 0 25px 0; }

        /* FILTERS & TABLE */
        .content-box { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; }
        
        .filter-btn { background: white; border: 1px solid #cbd5e1; color: #64748b; padding: 6px 16px; border-radius: 30px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; margin-right: 8px; }
        .filter-btn.active, .filter-btn:hover { background: var(--primary-blue); color: white; border-color: var(--primary-blue); }
        
        .table-custom th { font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; border-bottom: 2px solid #f1f5f9; padding: 15px 10px; background: #f8fafc; }
        .table-custom td { font-size: 14px; color: #334155; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        .badge-status { padding: 5px 14px; border-radius: 20px; font-size: 11.5px; font-weight: 700; display: inline-block; }
        .status-Pending { background: rgba(245,158,11,0.1); color: #d97706; }
        .status-Approved { background: rgba(16,185,129,0.1); color: #059669; }
        .status-Rejected { background: rgba(239,68,68,0.1); color: #dc2626; }
        
        .btn-view { background: white; color: #3b82f6; border: 1px solid #3b82f6; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none; transition: 0.2s;}
        .btn-view:hover { background: #3b82f6; color: white; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/college-logo.png" alt="Logo"> 
            <h2 class="sidebar-title">K.D. Polytechnic</h2>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='Stdashboard.php'"><i class="fas fa-home" style="width: 20px;"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='my-manuals.php'"><i class="fas fa-book" style="width: 20px;"></i> My Submissions</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle" style="width: 20px;"></i> Profile</li>
            <li onclick="window.location.href='history.php'"><i class="fas fa-history" style="width: 20px;"></i> History</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #fca5a5;"><i class="fas fa-sign-out-alt" style="width: 20px;"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="header-title">
            <h2>My Submissions</h2>
            <p>Track all your uploaded practicals and their review status.</p>
        </div>

        <div class="content-box">
            <!-- Filter Buttons -->
            <div class="mb-4">
                <button class="filter-btn active" onclick="filterData('All', this)">All Submissions</button>
                <button class="filter-btn" onclick="filterData('Pending', this)">Pending Review</button>
                <button class="filter-btn" onclick="filterData('Approved', this)">Approved</button>
                <button class="filter-btn" onclick="filterData('Rejected', this)">Rejected</button>
            </div>

            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Practical Details</th>
                            <th>Subject</th>
                            <th>Submission Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($submissions && $submissions->num_rows > 0): ?>
                            <?php while($row = $submissions->fetch_assoc()): ?>
                                <tr class="sub-row" data-status="<?php echo htmlspecialchars($row['status']); ?>">
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['practical_no'] ?? 'Manual Submission'); ?></div>
                                    </td>
                                    <td><span class="text-muted"><?php echo htmlspecialchars($row['subject_name'] ?? '-'); ?></span></td>
                                    <td><?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?></td>
                                    <td>
                                        <span class="badge-status status-<?php echo htmlspecialchars($row['status']); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn-view" title="View PDF">
                                            <i class="fas fa-eye me-1"></i> View File
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open mb-3" style="font-size: 40px; opacity: 0.3;"></i><br>
                                    You haven't submitted any practicals yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // JS Filter Logic
        function filterData(status, btn) {
            // Update active button styling
            let buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Filter rows
            let rows = document.querySelectorAll('.sub-row');
            rows.forEach(row => {
                if (status === 'All' || row.getAttribute('data-status') === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>