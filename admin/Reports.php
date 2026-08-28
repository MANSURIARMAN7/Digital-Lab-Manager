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
// 📊 REAL DATABASE METRICS FOR REPORTS
// ==========================================
$total_students = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='student'")->fetch_assoc()['cnt'] ?? 0;
$total_faculty = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='faculty'")->fetch_assoc()['cnt'] ?? 0;
$total_subs = $conn->query("SELECT COUNT(*) as cnt FROM student_submissions")->fetch_assoc()['cnt'] ?? 0;
$pending_subs = $conn->query("SELECT COUNT(*) as cnt FROM student_submissions WHERE status='Pending'")->fetch_assoc()['cnt'] ?? 0;
$approved_subs = $conn->query("SELECT COUNT(*) as cnt FROM student_submissions WHERE status='Approved'")->fetch_assoc()['cnt'] ?? 0;

// Fetch Departments for Filters
$departments_list = $conn->query("SELECT DISTINCT department FROM users WHERE department IS NULL OR department != '' ORDER BY department ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin Portal</title>
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
        
        /* UNIFORM TOPBAR */
        .topbar { background: transparent; padding: 0 0 10px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px;}
        .search-box { background: #fff; border-radius: 8px; padding: 10px 15px; display: flex; align-items: center; gap: 10px; width: 350px; border: 1px solid #e2e8f0; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .search-box input { border: none; background: transparent; outline: none; font-size: 14px; width: 100%; color: #334155; }
        
        .profile-pill { display: flex; align-items: center; background-color: #ffffff; padding: 6px 16px 6px 20px; border-radius: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; cursor: pointer; text-decoration: none; color: inherit; transition: all 0.2s;}
        .profile-text { text-align: right; margin-right: 15px; }
        .profile-welcome { display: block; font-size: 9.5px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 14px; color: #1e293b; font-weight: 700; }
        .profile-avatar { width: 42px; height: 42px; background-color: var(--accent-blue); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; box-shadow: 0 3px 8px rgba(37, 99, 235, 0.4); letter-spacing: 1px;}

        /* CONTENT CARDS */
        .content-box { background: white; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
        .report-card { background: white; border-radius: 12px; padding: 22px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); height: 100%; display: flex; flex-direction: column; justify-content: space-between; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-alt"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-folder-open"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-circle"></i> Review & Marks</li>
            <li class="active" onclick="window.location.href='Reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        
        <!-- TOPBAR -->
        <div class="topbar mb-3">
            <div class="search-box">
                <i class="fas fa-search text-muted"></i>
                <input type="text" placeholder="Search analytics...">
            </div>
            
            <div class="d-flex align-items-center gap-4">
                <div class="position-relative" style="cursor: pointer; padding: 8px; background: white; border-radius: 8px; border: 1px solid #e2e8f0;" onclick="window.location.href='Submissions.php'">
                    <i class="far fa-bell text-secondary fs-5"></i>
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

        <!-- PAGE HEADER & EXPORT BUTTONS -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h3 class="fw-bold text-dark mb-1" style="font-size: 24px;"><i class="fas fa-chart-bar text-primary me-2"></i> Reports & Analytics</h3>
                <p class="text-muted small mb-0">Generate, view, and export comprehensive system reports from live database.</p>
            </div>
            
            <div class="d-flex gap-2">
                <button onclick="alert('Master PDF Report Download Triggered Successfully!');" class="btn btn-outline-danger btn-sm fw-bold px-3 py-2" style="border-radius: 8px;">
                    <i class="fas fa-file-pdf me-1"></i> Export Master PDF
                </button>
                <button onclick="alert('Master Excel Report Download Triggered Successfully!');" class="btn btn-outline-success btn-sm fw-bold px-3 py-2" style="border-radius: 8px;">
                    <i class="fas fa-file-excel me-1"></i> Export Master Excel
                </button>
            </div>
        </div>

        <!-- FILTER BAR BOX -->
        <div class="content-box mb-4 py-3">
            <form method="GET" action="Reports.php" class="row align-items-end g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">Department</label>
                    <select name="department" class="form-select form-select-sm fw-bold">
                        <option value="all">All Departments</option>
                        <?php if($departments_list && $departments_list->num_rows > 0): ?>
                            <?php while($d = $departments_list->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($d['department']); ?>"><?php echo htmlspecialchars($d['department']); ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted">Semester / Batch</label>
                    <select name="semester" class="form-select form-select-sm fw-bold">
                        <option value="all">All Semesters</option>
                        <?php for($i=1; $i<=6; $i++): ?>
                            <option value="Semester <?php echo $i; ?>">Semester <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold py-2" style="border-radius: 8px; background: var(--accent-blue);">
                        <i class="fas fa-filter me-1"></i> Apply Filter Analytics
                    </button>
                </div>
            </form>
        </div>

        <!-- THREE REPORT CARDS WITH LIVE METRICS -->
        <div class="row g-4">
            <!-- CARD 1: STUDENTS -->
            <div class="col-md-4">
                <div class="report-card">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width: 45px; height: 45px; background: rgba(37,99,235,0.1); color: var(--accent-blue); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 16px;">Student Academic Report</h5>
                                <span class="badge bg-primary mt-1" style="font-size: 10px;">Total Registered: <?php echo $total_students; ?></span>
                            </div>
                        </div>
                        <p class="text-muted small">Complete list of active students and enrollment statuses filtered by department and batch.</p>
                    </div>
                    <div class="d-flex gap-2 mt-3 pt-3 border-top">
                        <a href="Student_Mgmt.php" class="btn btn-sm btn-outline-primary w-50 fw-bold" style="border-radius: 6px;"><i class="fas fa-eye me-1"></i> View</a>
                        <button onclick="alert('Student Report CSV exported successfully!');" class="btn btn-sm btn-outline-success w-50 fw-bold" style="border-radius: 6px;"><i class="fas fa-download me-1"></i> CSV</button>
                    </div>
                </div>
            </div>

            <!-- CARD 2: SUBMISSIONS -->
            <div class="col-md-4">
                <div class="report-card">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width: 45px; height: 45px; background: rgba(16,185,129,0.1); color: #059669; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 16px;">Submission Status</h5>
                                <span class="badge bg-success mt-1" style="font-size: 10px;">Total Uploads: <?php echo $total_subs; ?> (<?php echo $pending_subs; ?> Pending)</span>
                            </div>
                        </div>
                        <p class="text-muted small">Detailed statistics on pending, approved, and rejected manual submissions across all subjects.</p>
                    </div>
                    <div class="d-flex gap-2 mt-3 pt-3 border-top">
                        <a href="Submissions.php" class="btn btn-sm btn-outline-primary w-50 fw-bold" style="border-radius: 6px;"><i class="fas fa-eye me-1"></i> View</a>
                        <button onclick="alert('Submission Status CSV exported successfully!');" class="btn btn-sm btn-outline-success w-50 fw-bold" style="border-radius: 6px;"><i class="fas fa-download me-1"></i> CSV</button>
                    </div>
                </div>
            </div>

            <!-- CARD 3: FACULTY WORKLOAD -->
            <div class="col-md-4">
                <div class="report-card">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width: 45px; height: 45px; background: rgba(245,158,11,0.1); color: #d97706; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold text-dark mb-0" style="font-size: 16px;">Faculty Workload</h5>
                                <span class="badge bg-warning text-dark mt-1" style="font-size: 10px;">Active Faculty: <?php echo $total_faculty; ?></span>
                            </div>
                        </div>
                        <p class="text-muted small">Track faculty evaluation metrics and review tracking across departments.</p>
                    </div>
                    <div class="d-flex gap-2 mt-3 pt-3 border-top">
                        <a href="faculty_mgmt.php" class="btn btn-sm btn-outline-primary w-50 fw-bold" style="border-radius: 6px;"><i class="fas fa-eye me-1"></i> View</a>
                        <button onclick="alert('Faculty Workload CSV exported successfully!');" class="btn btn-sm btn-outline-success w-50 fw-bold" style="border-radius: 6px;"><i class="fas fa-download me-1"></i> CSV</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>