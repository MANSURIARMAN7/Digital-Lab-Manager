<?php
session_start();
include '../db.php';

// 1. Admin/Faculty Login Check
if (!isset($_SESSION['logged_in']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'faculty')) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$user_id'");
$user_data = $user_query ? $user_query->fetch_assoc() : null;
$user_name = $user_data['name'] ?? 'System Administrator';

// Ensure table exists
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

// Save Evaluation Logic
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['evaluate_submission'])) {
    $sub_id = (int)$_POST['submission_id'];
    $marks = (int)$_POST['marks'];
    $feedback = $conn->real_escape_string($_POST['feedback']);
    $action_btn = $_POST['action_btn'];
    
    $status = 'Pending';
    if ($action_btn == 'Approve') {
        $status = 'Approved';
    } elseif ($action_btn == 'Reject') {
        $status = 'Rejected';
    } elseif ($action_btn == 'Re-submit') {
        $status = 'Re-submit';
    }

    $sql_update = "UPDATE student_submissions SET marks = '$marks', feedback = '$feedback', status = '$status' WHERE submission_id = '$sub_id'";
    if ($conn->query($sql_update)) {
        $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Evaluation saved to database! Status updated to <b>$status</b>.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Error saving evaluation: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// Fetch real submissions only
$submissions_list = $conn->query("
    SELECT sub.*, 
           COALESCE(NULLIF(u.name, ''), 'Unknown Student') as student_name, 
           COALESCE(NULLIF(u.email, ''), 'N/A') as enrollment 
    FROM student_submissions sub 
    LEFT JOIN users u ON sub.student_id = u.user_id 
    ORDER BY sub.submitted_at DESC
");

$selected_sub_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_sub = null;

if ($selected_sub_id > 0) {
    $res = $conn->query("
        SELECT sub.*, 
               COALESCE(NULLIF(u.name, ''), 'Unknown Student') as student_name, 
               COALESCE(NULLIF(u.email, ''), 'N/A') as enrollment 
        FROM student_submissions sub 
        LEFT JOIN users u ON sub.student_id = u.user_id 
        WHERE sub.submission_id = $selected_sub_id
    ");
    if ($res && $res->num_rows > 0) {
        $current_sub = $res->fetch_assoc();
    }
} 

if (!$current_sub && $submissions_list && $submissions_list->num_rows > 0) {
    $submissions_list->data_seek(0);
    $current_sub = $submissions_list->fetch_assoc();
    $selected_sub_id = $current_sub['submission_id'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review & Marks - Admin Portal</title>
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

        .content-box { background: white; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Faculty / Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-alt"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-folder-open"></i> Submissions</li>
            <li class="active" onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-circle"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        
        <div class="topbar mb-3">
            <div class="search-box">
                <i class="fas fa-search text-muted"></i>
                <input type="text" placeholder="Search reviews...">
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
                                $name_parts = explode(' ', $user_name);
                                echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : 'Admin';
                            ?>
                        </h4>
                    </div>
                    <div class="profile-avatar">HOD</div>
                </a>
            </div>
        </div>

        <div class="mb-3">
            <h3 class="fw-bold text-dark mb-1" style="font-size: 22px;"><i class="fas fa-check-circle text-success me-2"></i> Faculty Review & Evaluation</h3>
            <p class="text-muted small mb-0">Assess student lab manuals, grade submissions, and provide remarks or feedback.</p>
        </div>

        <?php echo $message; ?>

        <?php if($submissions_list && $submissions_list->num_rows > 0): ?>
            <div class="content-box mb-3 py-2 px-3">
                <form method="GET" action="Review & Marks.php" class="row align-items-center g-2">
                    <div class="col-md-9">
                        <select name="id" class="form-select form-select-sm fw-bold text-primary" onchange="this.form.submit()">
                            <?php 
                            $submissions_list->data_seek(0);
                            while($item = $submissions_list->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $item['submission_id']; ?>" <?php echo ($selected_sub_id == $item['submission_id']) ? 'selected' : ''; ?>>
                                    Student: <?php echo htmlspecialchars($item['student_name']); ?> (<?php echo htmlspecialchars($item['enrollment']); ?>) | Subject: <?php echo htmlspecialchars($item['subject_name']); ?> [<?php echo htmlspecialchars($item['practical_no']); ?>] - Status: [<?php echo $item['status']; ?>]
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 text-end">
                        <?php if($current_sub): ?>
                            <span class="badge bg-secondary px-3 py-2">Status: <strong><?php echo htmlspecialchars($current_sub['status']); ?></strong></span>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if($current_sub): ?>
            <div class="row g-4">
                <div class="col-md-7">
                    <div class="content-box h-100 d-flex flex-column">
                        <h5 class="fw-bold text-dark mb-3" style="font-size: 16px;"><i class="fas fa-file-pdf text-danger me-2"></i> Student Submitted Manual (PDF View)</h5>
                        
                        <div class="bg-light border rounded p-5 text-center flex-grow-1 d-flex flex-column align-items-center justify-content-center mb-3">
                            <i class="fas fa-file-pdf text-danger fa-4x mb-3"></i>
                            <h6 class="fw-bold text-dark"><?php echo htmlspecialchars(basename($current_sub['file_path'])); ?></h6>
                            <p class="text-muted small mt-1">Student: <strong><?php echo htmlspecialchars($current_sub['student_name']); ?></strong> | Submitted: <?php echo date('d M Y, h:i A', strtotime($current_sub['submitted_at'])); ?></p>
                            
                            <a href="<?php echo htmlspecialchars($current_sub['file_path']); ?>" target="_blank" class="btn btn-outline-danger btn-sm fw-bold px-4 py-2 mt-3" style="border-radius: 8px; border-width: 2px;">
                                <i class="fas fa-external-link-alt me-1"></i> Open Full Screen PDF
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="content-box h-100">
                        <h5 class="fw-bold text-dark mb-3" style="font-size: 16px;"><i class="fas fa-edit text-primary me-2"></i> Faculty Evaluation Panel</h5>
                        
                        <form action="Review & Marks.php?id=<?php echo $selected_sub_id; ?>" method="POST">
                            <input type="hidden" name="submission_id" value="<?php echo $current_sub['submission_id']; ?>">
                            <input type="hidden" name="evaluate_submission" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Give Marks (out of 20)</label>
                                <input type="number" name="marks" class="form-control" min="0" max="20" required value="<?php echo (int)$current_sub['marks']; ?>" placeholder="e.g. 18">
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted">Remarks & Feedback</label>
                                <textarea name="feedback" class="form-control" rows="4" placeholder="Good work, neat diagrams..."><?php echo htmlspecialchars($current_sub['feedback'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="action_btn" value="Approve" class="btn btn-success fw-bold py-2" style="border-radius: 8px;">
                                    <i class="fas fa-check me-1"></i> Approve
                                </button>
                                <button type="submit" name="action_btn" value="Reject" class="btn btn-danger fw-bold py-2" style="border-radius: 8px;" onclick="return confirm('Reject this submission?');">
                                    <i class="fas fa-times me-1"></i> Reject
                                </button>
                                <button type="submit" name="action_pdf" value="Re-submit" name="action_btn" value="Re-submit" class="btn btn-warning fw-bold text-dark py-2" style="border-radius: 8px;">
                                    <i class="fas fa-redo me-1"></i> Re-submit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="content-box text-center py-5">
                <i class="fas fa-folder-open text-muted mb-3" style="font-size: 50px;"></i>
                <h5 class="fw-bold text-dark">No Student Submissions Found</h5>
                <p class="text-muted small">There are currently no real submissions in the database from students to review.</p>
            </div>
        <?php endif; ?>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>