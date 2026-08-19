<?php

session_start();
include '../db.php';

// Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 1. Total Students
$student_count_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student'");
$total_students = ($student_count_res) ? $student_count_res->fetch_assoc()['total'] : 0;

// 2. Active Faculty
$faculty_count_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='faculty'");
$active_faculty = ($faculty_count_res) ? $faculty_count_res->fetch_assoc()['total'] : 0;

// 3. Pending Reviews
$pending_res = $conn->query("SELECT COUNT(*) as total FROM submissions WHERE status='Pending'");
$pending_reviews = ($pending_res) ? $pending_res->fetch_assoc()['total'] : 0;

// 4. Rejected Submissions
$rejected_res = $conn->query("SELECT COUNT(*) as total FROM submissions WHERE status='Rejected'");
$rejected_submissions = ($rejected_res) ? $rejected_res->fetch_assoc()['total'] : 0;

// 5. Total Submissions for Chart
$total_sub_res = $conn->query("SELECT COUNT(*) as total FROM submissions");
$total_submissions = ($total_sub_res) ? $total_sub_res->fetch_assoc()['total'] : 0;

// 6. Recent Submissions for Table (Joining users table to get name & department)
$recent_submissions = $conn->query("
    SELECT u.name, u.department, s.subject_name, s.status, s.submitted_at 
    FROM submissions s 
    JOIN users u ON s.student_id = u.user_id 
    ORDER BY s.submitted_at DESC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Lab Manager Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-width: 260px; --bg-color: #f8fafc; }
        body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        
        /* SIDEBAR (Tere Naye Design Ke Hisaab Se) */
        .sidebar { width: var(--sidebar-width); background-color: #0f172a; color: #ffffff; display: flex; flex-direction: column; padding: 20px 0; z-index: 10; overflow-y: auto; }
        .sidebar-logo-container { padding: 0 20px 20px 20px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar-title h2 { font-size: 15px; font-weight: 700; margin: 0; line-height: 1.2; letter-spacing: 0.5px; }
        .nav-links { list-style: none; padding: 15px 15px 0 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 11px 16px; margin: 4px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 14px; font-size: 14px; font-weight: 500; color: #94a3b8; transition: 0.2s ease-in-out; }
        .nav-links li:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-links li.active { background: #3b82f6; color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }

        /* MAIN CONTENT AREA */
        .main { flex: 1; padding: 25px 35px; overflow-y: auto; display: flex; flex-direction: column; gap: 25px; }
        
        /* TOP BAR */
        .topbar { background: white; padding: 12px 25px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .search-box { background: #f8fafc; border-radius: 8px; padding: 6px 15px; display: flex; align-items: center; gap: 10px; width: 350px; border: 1px solid #e2e8f0; }
        .search-box input { border: none; background: transparent; outline: none; font-size: 14px; width: 100%; color: #334155; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 38px; height: 38px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; }
        .notif-badge { width: 36px; height: 36px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; cursor: pointer; }

        /* DASHBOARD GRID CARDS */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .stat-card { background: white; border-radius: 14px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.01); position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; }
        .stat-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; }
        .stat-card:nth-child(1)::before { background: #3b82f6; }
        .stat-card:nth-child(2)::before { background: #10b981; }
        .stat-card:nth-child(3)::before { background: #f59e0b; }
        .stat-card:nth-child(4)::before { background: #ef4444; }

        .stat-title { font-size: 13px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 28px; font-weight: 700; color: #0f172a; margin-top: 8px; }
        .stat-icon { position: absolute; right: 20px; top: 20px; width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .stat-card:nth-child(1) .stat-icon { background: rgba(59,130,246,0.1); color: #3b82f6; }
        .stat-card:nth-child(2) .stat-icon { background: rgba(16,185,129,0.1); color: #10b981; }
        .stat-card:nth-child(3) .stat-icon { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .stat-card:nth-child(4) .stat-icon { background: rgba(239,68,68,0.1); color: #ef4444; }

        /* LOWER SECTION */
        .lower-grid { display: grid; grid-template-columns: 4fr 6fr; gap: 20px; }
        .content-box { background: white; border-radius: 14px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        
        /* TABLE STYLING */
        .table-custom th { background: transparent; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; }
        .table-custom td { vertical-align: middle; font-size: 14px; padding: 14px 0; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .table-custom tr:last-child td { border-bottom: none; }
        
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-pending { background: rgba(245,158,11,0.1); color: #d97706; }
        .badge-approved { background: rgba(16,185,129,0.1); color: #059669; }
        .badge-rejected { background: rgba(239,68,68,0.1); color: #dc2626; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <div style="background: #3b82f6; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">DL</div>
            <div class="sidebar-title"><h2>DIGITAL LAB<br>MANUAL</h2></div>
        </div>
        <ul class="nav-links">
            <li class="active" onclick="window.location.href='dashboard.php'"><i class="fas fa-chart-pie"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-alt"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-folder-open"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-circle"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li onclick="window.location.href='Expense Mgmt.php'"><i class="fas fa-wallet"></i> Expense Mgmt</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <!-- TOPBAR -->
        <div class="topbar">
            <div class="search-box">
                <i class="fas fa-search text-muted"></i>
                <input type="text" placeholder="Search globally...">
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="notif-badge"><i class="far fa-bell"></i></div>
<!-- Clicakble Profile Icon -->
                <div class="user-profile">
                    <a href="Profile.php" class="d-flex align-items-center gap-2" style="text-decoration: none; color: inherit;">
                        <div class="user-avatar">AM</div>
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 13.5px; line-height: 1.2;">Prof. M. C. Thakor</div>
                            <div class="text-muted" style="font-size: 11.5px;">Computer Engineering Dept.</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- HEADER TITLE -->
        <div>
            <h4 class="fw-bold text-dark mb-1">Digital Lab Manager Dashboard</h4>
            <p class="text-muted small mb-0">Overview of student admissions, lab manuals progress, and review analytics.</p>
        </div>

        <!-- STATS GRID (DYNAMIC VALUES) -->
        <div class="stats-grid">
            <div class="stat-card">
                <div>
                    <div class="stat-title">Total Students</div>
                    <div class="stat-value"><?php echo number_format($total_students); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-title">Active Faculty</div>
                    <div class="stat-value"><?php echo number_format($active_faculty); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-title">Pending Reviews</div>
                    <div class="stat-value"><?php echo number_format($pending_reviews); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-card">
                <div>
                    <div class="stat-title">Rejected Submissions</div>
                    <div class="stat-value"><?php echo number_format($rejected_submissions); ?></div>
                </div>
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            </div>
        </div>

        <!-- LOWER SECTION -->
        <div class="lower-grid">
            <!-- BREAKDOWN GRAPH CARD (DYNAMIC VALUE) -->
            <div class="content-box">
                <h6 class="fw-bold text-dark mb-4">Submission Breakdown</h6>
                <div class="d-flex flex-column align-items-center justify-content-center py-3">
                    <div style="width: 170px; height: 170px; border-radius: 50%; background: conic-gradient(#10b981 0% 65%, #f59e0b 65% 85%, #ef4444 85% 100%); display: flex; align-items: center; justify-content: center; position: relative;">
                        <div style="width: 125px; height: 125px; background: white; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                            <span class="fw-bold text-dark" style="font-size: 18px;"><?php echo number_format($total_submissions); ?></span>
                            <span class="text-muted" style="font-size: 11px;">Submissions</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RECENT SUBMISSIONS TABLE (DYNAMIC DATA) -->
            <div class="content-box">
                <h6 class="fw-bold text-dark mb-3">Recent Student Manual Submissions</h6>
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_submissions && $recent_submissions->num_rows > 0): ?>
                            <?php while($row = $recent_submissions->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw-semibold">
                                        <?php echo htmlspecialchars($row['name']); ?> 
                                        <!-- Shows abbreviation like (Computer) -->
                                        <span class="text-muted" style="font-size: 12px;">(<?php echo htmlspecialchars(explode(' ', $row['department'])[0]); ?>)</span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                    <td class="text-muted"><?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?></td>
                                    <td>
                                        <?php 
                                            // Dynamic Badge CSS Class based on Status
                                            $badge_class = 'badge-pending';
                                            if($row['status'] == 'Approved') $badge_class = 'badge-approved';
                                            if($row['status'] == 'Rejected') $badge_class = 'badge-rejected';
                                        ?>
                                        <span class="badge-status <?php echo $badge_class; ?>"><?php echo $row['status']; ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No recent submissions found in database.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>
