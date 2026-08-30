<?php
session_start();
include '../db.php';

// 1. Admin Login Check
//  ==========
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// 2. Fetch Submissions Data (Joining with users table to get student details)
$filter_status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

$query = "SELECT s.*, s.student_id as enrollment, s.subject_name as subject, u.name, u.department 
          FROM submissions s 
          JOIN users u ON s.student_id = u.user_id";

if ($filter_status != '') {
    $query .= " WHERE s.status = '$filter_status'";
}
$query .= " ORDER BY s.submitted_at DESC";

$submissions = [];
$res = $conn->query($query);
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $submissions[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submissions - Digital Lab Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-width: 260px; --bg-color: #f8fafc; }
        body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        
        /* SIDEBAR (New Premium Design) */
        .sidebar { width: var(--sidebar-width); background-color: #0f172a; color: #ffffff; display: flex; flex-direction: column; padding: 20px 0; z-index: 10; overflow-y: auto; }
        .sidebar-logo-container { padding: 0 20px 20px 20px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid rgba(255,255,255,0.08); }
        .sidebar-title h2 { font-size: 15px; font-weight: 700; margin: 0; line-height: 1.2; letter-spacing: 0.5px; }
        .nav-links { list-style: none; padding: 15px 15px 0 15px; margin: 0; flex-grow: 1; display: flex; flex-direction: column; }
        .nav-links li { padding: 11px 16px; margin: 4px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 14px; font-size: 14px; font-weight: 500; color: #94a3b8; transition: 0.2s ease-in-out; }
        .nav-links li:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-links li.active { background: #3b82f6; color: white; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }

        /* MAIN CONTENT AREA */
        .main { flex: 1; padding: 25px 35px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; }
        
        /* TOP BAR */
        .topbar { background: white; padding: 12px 25px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .search-box { background: #f8fafc; border-radius: 8px; padding: 6px 15px; display: flex; align-items: center; gap: 10px; width: 350px; border: 1px solid #e2e8f0; }
        .search-box input { border: none; background: transparent; outline: none; font-size: 14px; width: 100%; color: #334155; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 38px; height: 38px; background: #3b82f6; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; }
        
        /* CONTENT BOXES */
        .content-box { background: white; border-radius: 14px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.01); }
        
        /* TABLE STYLING */
        .table-custom th { background: transparent; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; }
        .table-custom td { vertical-align: middle; font-size: 14px; padding: 14px 0; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .table-custom tr:last-child td { border-bottom: none; }
        
        /* BADGES */
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-pending { background: rgba(245,158,11,0.1); color: #d97706; }
        .badge-approved { background: rgba(16,185,129,0.1); color: #059669; }
        .badge-rejected { background: rgba(239,68,68,0.1); color: #dc2626; }
        
        .btn-view { background: rgba(59,130,246,0.1); color: #3b82f6; border: none; padding: 6px 12px; border-radius: 6px; transition: 0.2s; text-decoration: none; font-size: 13px; font-weight: 600; }
        .btn-view:hover { background: #3b82f6; color: white; }
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
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-chart-pie"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-alt"></i> Lab Manuals</li>
            
            <!-- Yahan Submissions par active class laga di -->
            <li class="active" onclick="window.location.href='Submissions.php'"><i class="fas fa-folder-open"></i> Submissions</li>
            
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
                <input type="text" placeholder="Search submissions by student or subject...">
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="user-profile">
                    <div class="user-avatar">AM</div>
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 13.5px; line-height: 1.2;">System Administrator</div>
                        <div class="text-muted" style="font-size: 11.5px;">University Tech</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- HEADER TITLE -->
        <div class="mb-2 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold text-dark mb-1">📂 Student Submissions</h4>
                <p class="text-muted small mb-0">Track all lab manual submissions by students across all branches.</p>
            </div>
        </div>

        <!-- SUBMISSIONS LIST TABLE -->
        <div class="content-box">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold text-dark mb-0">Recent Submissions</h6>
                
                <!-- FILTER FORM -->
                <form method="GET" class="d-flex gap-2">
                    <select name="status" class="form-select form-select-sm shadow-none" style="width: auto; font-size: 12px; padding: 6px 30px 6px 12px;" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php if($filter_status == 'Pending') echo 'selected'; ?>>Pending</option>
                        <option value="Approved" <?php if($filter_status == 'Approved') echo 'selected'; ?>>Approved</option>
                        <option value="Rejected" <?php if($filter_status == 'Rejected') echo 'selected'; ?>>Rejected</option>
                    </select>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Student & Enrollment</th>
                            <th>Subject & Branch</th>
                            <th>Submitted Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($submissions)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No submissions found.</td></tr>
                        <?php else: foreach($submissions as $sub): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($sub['name']); ?></div>
                                    <small class="text-muted" style="font-size: 12px;"><?php echo htmlspecialchars($sub['enrollment']); ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($sub['subject']); ?></div>
                                    <small class="text-muted" style="font-size: 11px;"><?php echo htmlspecialchars($sub['department']); ?></small>
                                </td>
                                <td class="text-muted" style="font-size: 13px;">
                                    <i class="far fa-calendar-alt me-1"></i>
                                    <?php echo date('d M Y, h:i A', strtotime($sub['submitted_at'])); ?>
                                </td>
                                <td>
                                    <?php 
                                        $badge_class = 'badge-pending';
                                        if($sub['status'] == 'Approved') $badge_class = 'badge-approved';
                                        if($sub['status'] == 'Rejected') $badge_class = 'badge-rejected';
                                    ?>
                                    <span class="badge-status <?php echo $badge_class; ?>"><?php echo $sub['status']; ?></span>
                                </td>
                                <td class="text-end">
                                    <a href="<?php echo htmlspecialchars($sub['file_path']); ?>" target="_blank" class="btn-view">
                                        <i class="fa-solid fa-eye me-1"></i> View PDF
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>