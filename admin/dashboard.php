<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch Admin Details for Profile Pill
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query ? $admin_query->fetch_assoc() : null;
$admin_name = $admin_data['name'] ?? 'System Administrator';

// ==========================================
// 📊 METRICS & COUNTERS QUERY
// ==========================================
// Total Students Count
$students_count_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$total_students = ($students_count_res) ? $students_count_res->fetch_assoc()['total'] : 0;

// Active Faculty Count
$faculty_count_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'faculty'");
$total_faculty = ($faculty_count_res) ? $faculty_count_res->fetch_assoc()['total'] : 0;

// Pending Reviews Count
$pending_count_res = $conn->query("SELECT COUNT(*) as total FROM student_submissions WHERE status = 'Pending'");
$pending_reviews = ($pending_count_res) ? $pending_count_res->fetch_assoc()['total'] : 0;

// Rejected Submissions Count
$rejected_count_res = $conn->query("SELECT COUNT(*) as total FROM student_submissions WHERE status = 'Rejected'");
$rejected_submissions = ($rejected_count_res) ? $rejected_count_res->fetch_assoc()['total'] : 0;

// Approved Submissions Count (for chart/breakdown)
$approved_count_res = $conn->query("SELECT COUNT(*) as total FROM student_submissions WHERE status = 'Approved'");
$approved_submissions = ($approved_count_res) ? $approved_count_res->fetch_assoc()['total'] : 0;

$total_submissions = $pending_reviews + $rejected_submissions + $approved_submissions;

// ==========================================
// 🕒 RECENT SUBMISSIONS QUERY (FIXED LEFT JOIN)
// ==========================================
$recent_query = $conn->query("
    SELECT sub.*, 
           COALESCE(NULLIF(u.name, ''), 'Unknown Student') as student_name, 
           COALESCE(NULLIF(u.email, ''), 'N/A') as student_enrollment 
    FROM student_submissions sub 
    LEFT JOIN users u ON sub.student_id = u.user_id 
    ORDER BY sub.submitted_at DESC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lab Manual Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --sidebar-width: 260px; --bg-color: #f4f7fe; --sidebar-bg: #1a365d; --accent-blue: #2563eb; }
        body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        .sidebar { width: var(--sidebar-width); background-color: var(--sidebar-bg); color: #ffffff; display: flex; flex-direction: column; z-index: 10; overflow-y: auto; }
        .sidebar-logo-container { padding: 30px 20px 20px 20px; display: flex; flex-direction: column; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; }
        .sidebar-logo-container img { width: 90px; height: 90px; object-fit: contain; margin-bottom: 15px; border-radius: 50%; padding: 5px; background: rgba(255,255,255,0.1); }
        .sidebar-title h2 { font-size: 18px; font-weight: 700; margin: 0; line-height: 1.2; letter-spacing: 0.5px; color: #fff;}
        .sidebar-subtitle { font-size: 13px; color: #94a3b8; margin-top: 5px; font-weight: 500;}
        .nav-links { list-style: none; padding: 20px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 12px 20px; margin: 5px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 500; color: #a0aec0; transition: all 0.3s ease; }
        .nav-links li:hover { color: white; background: rgba(255,255,255,0.08); }
        .nav-links li.active { background: var(--accent-blue); color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4); font-weight: 600; }
        .main { flex: 1; padding: 30px 40px; overflow-y: auto; }
        
        .topbar { background: transparent; padding: 0 0 10px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px;}
        .search-box { background: #fff; border-radius: 8px; padding: 10px 15px; display: flex; align-items: center; gap: 10px; width: 350px; border: 1px solid #e2e8f0; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .search-box input { border: none; background: transparent; outline: none; font-size: 14px; width: 100%; color: #334155; }
        
        .profile-pill { display: flex; align-items: center; background-color: #ffffff; padding: 6px 16px 6px 20px; border-radius: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; cursor: pointer; text-decoration: none; color: inherit; transition: all 0.2s;}
        .profile-text { text-align: right; margin-right: 15px; }
        .profile-welcome { display: block; font-size: 9.5px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 14px; color: #1e293b; font-weight: 700; }
        .profile-avatar { width: 42px; height: 42px; background-color: var(--accent-blue); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; box-shadow: 0 3px 8px rgba(37, 99, 235, 0.4); letter-spacing: 1px;}

        .stat-card { background: white; border-radius: 12px; padding: 22px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); position: relative; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s;}
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.05); }
        .stat-card::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; }
        .stat-card.blue::before { background-color: #2563eb; }
        .stat-card.green::before { background-color: #10b981; }
        .stat-card.yellow::before { background-color: #f59e0b; }
        .stat-card.red::before { background-color: #ef4444; }

        .content-box { background: white; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
        .table-custom th { background: #f8fafc; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; padding: 14px; }
        .table-custom td { vertical-align: middle; font-size: 14px; padding: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li class="active" onclick="window.location.href='dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-alt"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-folder-open"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-circle"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- TOPBAR -->
        <div class="topbar mb-4">
            <div class="search-box">
                <i class="fas fa-search text-muted"></i>
                <input type="text" placeholder="Search dashboard...">
            </div>
            
            <div class="d-flex align-items-center gap-4">
                <div class="position-relative" style="cursor: pointer; padding: 8px; background: white; border-radius: 8px; border: 1px solid #e2e8f0;" onclick="window.location.href='Submissions.php'">
                    <i class="far fa-bell text-secondary fs-5"></i>
                    <?php if($pending_reviews > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 9px;"><?php echo $pending_reviews; ?></span>
                    <?php endif; ?>
                </div>

                <a href="Profile.php" class="profile-pill">
                    <div class="profile-text">
                        <span class="profile-welcome">Welcome Back,</span>
                        <h4 class="profile-name">
                            <?php 
                                $name_parts = explode(' ', $admin_name);
                                echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : 'Admin';
                            ?>
                        </h4>
                    </div>
                    <div class="profile-avatar">HOD</div>
                </a>
            </div>
        </div>

        <!-- PAGE HEADER -->
        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1" style="font-size: 24px;">Admin Dashboard</h3>
            <p class="text-muted small mb-0">Overview and real-time system management for Computer Engineering Department.</p>
        </div>

        <!-- STATS CARDS ROW (CLICKABLE BUTTONS) -->
        <div class="row g-4 mb-4">
            <!-- Total Students Card -> Redirects to Student Management -->
            <div class="col-md-3">
                <a href="Student_Mgmt.php" class="text-decoration-none">
                    <div class="stat-card blue h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted fw-bold" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Total Students</span>
                                <h2 class="fw-bold text-dark mt-1 mb-0" style="font-size: 28px;"><?php echo $total_students; ?></h2>
                            </div>
                            <div class="p-3 rounded-circle bg-light text-primary fs-4"><i class="fas fa-user-graduate"></i></div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Active Faculty Card -> Redirects to Faculty Management -->
            <div class="col-md-3">
                <a href="faculty_mgmt.php" class="text-decoration-none">
                    <div class="stat-card green h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted fw-bold" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Active Faculty</span>
                                <h2 class="fw-bold text-dark mt-1 mb-0" style="font-size: 28px;"><?php echo $total_faculty; ?></h2>
                            </div>
                            <div class="p-3 rounded-circle bg-light text-success fs-4"><i class="fas fa-chalkboard-teacher"></i></div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Pending Reviews Card -> Redirects to Submissions filtered by Pending -->
            <div class="col-md-3">
                <a href="Submissions.php?status=Pending" class="text-decoration-none">
                    <div class="stat-card yellow h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted fw-bold" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Pending Reviews</span>
                                <h2 class="fw-bold text-dark mt-1 mb-0" style="font-size: 28px;"><?php echo $pending_reviews; ?></h2>
                            </div>
                            <div class="p-3 rounded-circle bg-light text-warning fs-4"><i class="fas fa-clock"></i></div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Rejected Submissions Card -> Redirects to Submissions filtered by Rejected -->
            <div class="col-md-3">
                <a href="Submissions.php?status=Rejected" class="text-decoration-none">
                    <div class="stat-card red h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted fw-bold" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Rejected Submissions</span>
                                <h2 class="fw-bold text-dark mt-1 mb-0" style="font-size: 28px;"><?php echo $rejected_submissions; ?></h2>
                            </div>
                            <div class="p-3 rounded-circle bg-light text-danger fs-4"><i class="fas fa-times-circle"></i></div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- TWO COLUMN LAYOUT: BREAKDOWN & RECENT SUBMISSIONS -->
        <div class="row g-4">
            <!-- SUBMISSION BREAKDOWN -->
            <div class="col-md-5">
                <div class="content-box h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h5 class="fw-bold text-dark mb-1" style="font-size: 16px;"><i class="fas fa-chart-pie text-primary me-2"></i> Submission Breakdown</h5>
                        <p class="text-muted small">Live status distribution of student lab files.</p>
                    </div>
                    
                    <div class="text-center py-4">
                        <div class="p-4 rounded-circle d-inline-block bg-light border mb-3">
                            <h1 class="fw-bold text-primary mb-0" style="font-size: 36px;"><?php echo $total_submissions; ?></h1>
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;">Total Submissions</small>
                        </div>
                        <div class="d-flex justify-content-around mt-3">
                            <a href="Submissions.php?status=Approved" class="text-decoration-none"><span class="badge bg-success px-2 py-1" style="cursor: pointer;">Approved: <?php echo $approved_submissions; ?></span></a>
                            <a href="Submissions.php?status=Pending" class="text-decoration-none"><span class="badge bg-warning text-dark px-2 py-1" style="cursor: pointer;">Pending: <?php echo $pending_reviews; ?></span></a>
                            <a href="Submissions.php?status=Rejected" class="text-decoration-none"><span class="badge bg-danger px-2 py-1" style="cursor: pointer;">Rejected: <?php echo $rejected_submissions; ?></span></a>
                        </div>
                    </div>

                    <div class="text-end">
                        <a href="Reports.php" class="btn btn-sm btn-outline-primary fw-bold" style="border-radius: 6px;">View Full Reports <i class="fas fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>

            <!-- RECENT STUDENT SUBMISSIONS TABLE -->
            <div class="col-md-7">
                <div class="content-box h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-dark mb-0" style="font-size: 16px;"><i class="fas fa-history text-success me-2"></i> Recent Student Submissions</h5>
                        <a href="Submissions.php" class="btn btn-sm btn-light border fw-bold" style="border-radius: 6px;">View All</a>
                    </div>
                    
                    <div class="table-responsive">
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
                                <?php if($recent_query && $recent_query->num_rows > 0): ?>
                                    <?php while($row = $recent_query->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark" style="font-size: 13.5px;"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                                <small class="text-muted" style="font-size: 11px;"><?php echo htmlspecialchars($row['student_enrollment']); ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-primary" style="font-size: 13.5px;"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                                                <small class="text-secondary" style="font-size: 11px;"><?php echo htmlspecialchars($row['practical_no']); ?></small>
                                            </td>
                                            <td>
                                                <small class="text-dark" style="font-size: 12px;"><?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if($row['status'] == 'Approved'): ?>
                                                    <span class="badge bg-success text-white px-2 py-1" style="font-size: 10.5px; border-radius: 20px;">Approved</span>
                                                <?php elseif($row['status'] == 'Rejected'): ?>
                                                    <span class="badge bg-danger text-white px-2 py-1" style="font-size: 10.5px; border-radius: 20px;">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark px-2 py-1" style="font-size: 10.5px; border-radius: 20px;">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <i class="fas fa-folder-open mb-2" style="font-size: 32px; color: #cbd5e1;"></i><br>
                                            <span>No recent submissions found.</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>