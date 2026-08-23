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

// Ensure student_submissions table exists
$conn->query("CREATE TABLE IF NOT EXISTS student_submissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    manual_id INT NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    practical_no VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'Pending',
    marks INT DEFAULT 0,
    feedback TEXT DEFAULT NULL
)");

// ==========================================
// 🔍 REAL SEARCH & STATUS FILTER LOGIC
// ==========================================
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$where_clauses = [];

// Status filter condition
if ($filter_status !== 'all' && $filter_status !== '') {
    $safe_status = $conn->real_escape_string($filter_status);
    $where_clauses[] = "sub.status = '$safe_status'";
}

// Search filter condition (searches student name, enrollment/email, or subject name)
if (!empty($search_query)) {
    $safe_search = $conn->real_escape_string($search_query);
    $where_clauses[] = "(u.name LIKE '%$safe_search%' OR u.email LIKE '%$safe_search%' OR sub.subject_name LIKE '%$safe_search%')";
}

// Build final SQL WHERE clause
$sql_where = "";
if (count($where_clauses) > 0) {
    $sql_where = "WHERE " . implode(" AND ", $where_clauses);
}

// Fetch Real Submissions with Filter applied
$submissions_query = "
    SELECT sub.*, 
           COALESCE(NULLIF(u.name, ''), 'Unknown Student') as student_name, 
           COALESCE(NULLIF(u.email, ''), 'N/A') as student_enrollment, 
           COALESCE(NULLIF(u.department, ''), 'Computer Engineering') as department 
    FROM student_submissions sub 
    LEFT JOIN users u ON sub.student_id = u.user_id 
    $sql_where 
    ORDER BY sub.submitted_at DESC
";
$submissions_result = $conn->query($submissions_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Submissions - Admin Portal</title>
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
        .profile-pill { display: flex; align-items: center; background-color: #ffffff; padding: 6px 16px 6px 20px; border-radius: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; cursor: pointer; text-decoration: none; color: inherit; transition: all 0.2s;}
        .profile-text { text-align: right; margin-right: 15px; }
        .profile-welcome { display: block; font-size: 9.5px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 14px; color: #1e293b; font-weight: 700; }
        .profile-avatar { width: 42px; height: 42px; background-color: var(--accent-blue); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; box-shadow: 0 3px 8px rgba(37, 99, 235, 0.4); letter-spacing: 1px;}

        .content-box { background: white; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
        .table-custom th { background: #f8fafc; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; padding: 14px; }
        .table-custom td { vertical-align: middle; font-size: 14px; padding: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        
        .badge-pending { background: rgba(245,158,11,0.1); color: #d97706; border: 1px solid rgba(245,158,11,0.2); padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .badge-approved { background: rgba(16,185,129,0.1); color: #059669; border: 1px solid rgba(16,185,129,0.2); padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .badge-rejected { background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.2); padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
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
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-alt"></i> Lab Manuals</li>
            <li class="active" onclick="window.location.href='Submissions.php'"><i class="fas fa-folder-open"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-circle"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- TOPBAR -->
        <div class="topbar mb-3">
            <div>
                <h3 class="fw-bold text-dark mb-1" style="font-size: 24px;">Student Submissions</h3>
                <p class="text-muted small mb-0">Track and view all practical files uploaded by students.</p>
            </div>
            
            <div class="d-flex align-items-center gap-4">
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

        <!-- FILTER & SEARCH BAR SECTION -->
        <div class="content-box mb-4 py-3">
            <form method="GET" action="Submissions.php" class="row g-3 align-items-center justify-content-between">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0" style="border-radius: 8px 0 0 8px;"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by student name, enrollment, or subject..." value="<?php echo htmlspecialchars($search_query); ?>" style="border-radius: 0 8px 8px 0;">
                    </div>
                </div>
                
                <div class="col-md-4 d-flex gap-2">
                    <select name="status" class="form-select fw-bold" onchange="this.form.submit()" style="border-radius: 8px;">
                        <option value="all" <?php echo ($filter_status == 'all') ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="Pending" <?php echo ($filter_status == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo ($filter_status == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo ($filter_status == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                    <button type="submit" class="btn btn-primary px-4 fw-bold" style="border-radius: 8px;"><i class="fas fa-filter me-1"></i> Filter</button>
                </div>
            </form>
        </div>

        <!-- SUBMISSIONS TABLE -->
        <div class="content-box">
            <h5 class="fw-bold text-dark mb-3" style="font-size: 16px;"><i class="fas fa-history text-success me-2"></i> Submissions List</h5>
            
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Student Details</th>
                            <th>Subject & Practical</th>
                            <th>Submitted Date</th>
                            <th>Marks</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($submissions_result && $submissions_result->num_rows > 0): ?>
                            <?php while($row = $submissions_result->fetch_assoc()): 
                                $sub_id = $row['submission_id'] ?? 0;
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['student_enrollment']); ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                                        <small class="text-secondary"><?php echo htmlspecialchars($row['practical_no']); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-dark"><i class="far fa-clock text-muted me-1"></i> <?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark"><?php echo $row['marks']; ?> / 20</span>
                                    </td>
                                    <td>
                                        <?php if($row['status'] == 'Approved'): ?>
                                            <span class="badge-approved">Approved</span>
                                        <?php elseif($row['status'] == 'Rejected'): ?>
                                            <span class="badge-rejected">Rejected</span>
                                        <?php else: ?>
                                            <span class="badge-pending">Pending Review</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius: 6px; padding: 4px 10px;" title="View PDF">
                                            <i class="fas fa-file-pdf me-1"></i> View PDF
                                        </a>
                                        <a href="Review & Marks.php" class="btn btn-sm btn-outline-success" style="border-radius: 6px; padding: 4px 10px;" title="Evaluate">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-folder-open mb-2" style="font-size: 32px; color: #cbd5e1;"></i><br>
                                    <span>No submissions found matching your filter criteria.</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>