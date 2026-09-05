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
        $message = "<div class='alert alert-success alert-dismissible fade show mb-4' style='border-radius:10px; font-weight:600;' role='alert'><i class='fas fa-check-circle me-2'></i>Evaluation saved to database! Status updated to <b>$status</b>.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $message = "<div class='alert alert-danger alert-dismissible fade show mb-4' style='border-radius:10px;' role='alert'><i class='fas fa-exclamation-triangle me-2'></i>Error saving evaluation: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// 🔍 SEARCH & FETCH SUBMISSIONS
// ==========================================
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql_search = "";
if (!empty($search_query)) {
    $safe_search = $conn->real_escape_string($search_query);
    $sql_search = " WHERE u.name LIKE '%$safe_search%' OR u.email LIKE '%$safe_search%' OR sub.subject_name LIKE '%$safe_search%' ";
}

$submissions_list = $conn->query("
    SELECT sub.*, 
           COALESCE(NULLIF(u.name, ''), 'Unknown Student') as student_name, 
           COALESCE(NULLIF(u.email, ''), 'N/A') as enrollment 
    FROM student_submissions sub 
    LEFT JOIN users u ON sub.student_id = u.user_id 
    $sql_search
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --sidebar-width: 270px; 
            --primary: #4338ca; 
            --primary-hover: #3730a3;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --shadow-float: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            --radius-xl: 16px;
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; overflow-y: auto; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0; letter-spacing: 0.5px; color: #ffffff;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); border-left: 3px solid transparent; }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; border-left: 4px solid #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);}

        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 30px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        .content-box:hover { box-shadow: var(--shadow-float); }
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 15px; }
        .icon-box { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 26px; }
        .green-box { background: rgba(16, 185, 129, 0.1); color: #10b981; }

        .search-modern { border-radius: 10px 0 0 10px !important; border: 1px solid #cbd5e1; font-weight: 500; font-size: 14px; padding: 10px 18px;}
        .search-modern:focus { box-shadow: none; border-color: var(--primary); }
        .btn-search { border-radius: 0 10px 10px 0 !important; background: var(--primary); color: white; padding: 0 20px; font-weight: 700; border: none; transition: var(--transition-bounce);}
        .btn-search:hover { background: var(--primary-hover); }

        .btn-outline-modern { background: white; color: var(--text-main); font-weight: 700; padding: 10px 18px; border-radius: 10px; border: 1px solid #cbd5e1; transition: var(--transition-bounce); }
        .btn-outline-modern:hover { background: #f8fafc; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }

        input.form-control, select.form-select, textarea.form-control { border-radius: 10px; padding: 12px; border: 1px solid #cbd5e1; font-weight: 500; font-size: 14px; transition: var(--transition-bounce); }
        input.form-control:focus, select.form-select:focus, textarea.form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); transform: scale(1.01); }

        .btn-action { font-weight: 700; padding: 10px 15px; border-radius: 10px; transition: var(--transition-bounce); border: none; }
        .btn-action:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.1); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        /* ========================================================
           🖨️ SMART PRINT VIEW - Hides UI, Shows Clean Report 
           ======================================================== */
        #printReportContainer { display: none; } /* Hidden normally */
        
        @media print {
            /* Hide the entire Dashboard UI */
            .sidebar, .normal-view-wrapper { display: none !important; }
            body { background: white !important; margin: 0; padding: 0; color: #000; }
            
            /* Show ONLY the Print Container */
            #printReportContainer { 
                display: block !important; 
                width: 100%; 
                font-family: Arial, sans-serif;
            }
            
            /* Print Styling */
            .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
            .print-header h2 { margin: 0 0 5px 0; font-size: 24px; font-weight: bold; text-transform: uppercase; }
            .print-header h4 { margin: 0 0 5px 0; font-size: 18px; color: #333; }
            .print-header p { margin: 0; font-size: 12px; color: #666; }
            
            .print-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            .print-table th, .print-table td { border: 1px solid #000; padding: 10px; text-align: left; font-size: 13px; }
            .print-table th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; font-weight: bold; text-transform: uppercase; }
            
            /* Add some spacing for readability */
            .print-table tr:nth-child(even) td { background-color: #fafafa !important; -webkit-print-color-adjust: exact; }
        }
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
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-pdf"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-inbox"></i> Submissions</li>
            <li class="active" onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-double"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- NORMAL MAIN CONTENT WRAPPER -->
    <div class="main normal-view-wrapper">
        
        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge">
                    <i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span>
                </div>
                
                <!-- 🛠️ Functional Search Form -->
                <form action="" method="GET" class="d-flex shadow-sm" style="border-radius: 10px;">
                    <input type="text" name="search" class="form-control search-modern px-4" placeholder="Search student or subject..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 250px;">
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i></button>
                </form>

                <!-- 🖨️ PRINT REPORT BUTTON (Prints the list of submitted manuals) -->
                <button class="btn-outline-modern ms-3" onclick="window.print()" title="Print Submissions Report">
                    <i class="fas fa-print text-primary me-2"></i> Print Report
                </button>
                
                <!-- 📊 Export to Excel Button -->
                <button class="btn-outline-modern ms-2" onclick="exportSubmissionsToCSV('Submissions_Report.csv')" title="Download All Reports">
                    <i class="fas fa-file-excel text-success me-2"></i> Export Data
                </button>
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
                <div class="profile-avatar">AD</div>
            </a>
        </div>

        <?php echo $message; // Alerts ?>

        <!-- PAGE HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-5 mt-2 page-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box green-box"><i class="fas fa-check-double"></i></div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);">Review & Evaluation</h3>
                    <p class="text-muted fw-semibold small mb-0">Assess student lab manuals, grade submissions, and provide feedback.</p>
                </div>
            </div>
            <?php if(!empty($search_query)): ?>
            <a href="Review & Marks.php" class="btn btn-outline-danger fw-bold" style="border-radius: 10px; padding: 10px 20px;">
                <i class="fas fa-times me-1"></i> Clear Search
            </a>
            <?php endif; ?>
        </div>

        <!-- SUBMISSION SELECTOR -->
        <?php if($submissions_list && $submissions_list->num_rows > 0): ?>
            <div class="content-box mb-4 py-3 px-4" style="background: linear-gradient(135deg, rgba(67, 56, 202, 0.03), rgba(59, 130, 246, 0.05)); border: 1px solid rgba(67, 56, 202, 0.1);">
                <form method="GET" action="Review & Marks.php" class="row align-items-center g-3">
                    <div class="col-md-9">
                        <label class="form-label fw-bold small text-primary text-uppercase letter-spacing-1 mb-2"><i class="fas fa-filter me-1"></i> Select Student Submission to Review</label>
                        <select name="id" class="form-select fw-bold text-dark shadow-sm" style="border-color: #cbd5e1; cursor: pointer;" onchange="this.form.submit()">
                            <?php 
                            $submissions_list->data_seek(0);
                            while($item = $submissions_list->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $item['submission_id']; ?>" <?php echo ($selected_sub_id == $item['submission_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($item['student_name']); ?> (<?php echo htmlspecialchars($item['enrollment']); ?>) | <?php echo htmlspecialchars($item['subject_name']); ?> [<?php echo htmlspecialchars($item['practical_no']); ?>] - [<?php echo $item['status']; ?>]
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 text-end mt-4">
                        <?php if($current_sub): ?>
                            <?php 
                                $status_color = 'bg-secondary';
                                if($current_sub['status'] == 'Approved') $status_color = 'bg-success';
                                elseif($current_sub['status'] == 'Rejected') $status_color = 'bg-danger';
                                elseif($current_sub['status'] == 'Pending') $status_color = 'bg-warning text-dark';
                            ?>
                            <div class="badge <?php echo $status_color; ?> px-4 py-3 shadow-sm fs-6 rounded-pill border">
                                Status: <strong><?php echo htmlspecialchars($current_sub['status']); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- TWO COLUMN LAYOUT: PDF VIEWER & EVALUATION PANEL -->
            <?php if($current_sub): ?>
            <div class="row g-4">
                
                <!-- LEFT COLUMN: PDF VIEWER -->
                <div class="col-md-7 pdf-viewer-col">
                    <div class="content-box h-100 d-flex flex-column">
                        <h5 class="box-title"><i class="fas fa-file-pdf text-danger me-2"></i> Student Submitted Manual</h5>
                        <hr class="mb-4" style="border-color: #e2e8f0;">
                        
                        <div class="bg-light border p-5 text-center flex-grow-1 d-flex flex-column align-items-center justify-content-center shadow-sm" style="border-radius: 14px; border-style: dashed !important; border-color: #cbd5e1 !important;">
                            <i class="fas fa-file-pdf text-danger mb-3" style="font-size: 60px;"></i>
                            <h5 class="fw-bold text-dark mt-2"><?php echo htmlspecialchars(basename($current_sub['file_path'])); ?></h5>
                            <p class="text-muted small mt-2 fw-semibold">
                                <i class="fas fa-user text-primary me-1"></i> <?php echo htmlspecialchars($current_sub['student_name']); ?> <br>
                                <i class="far fa-clock text-warning me-1 mt-2"></i> Submitted: <?php echo date('d M Y, h:i A', strtotime($current_sub['submitted_at'])); ?>
                            </p>
                            
                            <?php 
                                // 🛠️ SMART FIX FOR 404 PDF ERROR
                                $raw_path = $current_sub['file_path'];
                                // Check if path already contains "uploads/" to avoid double pathing
                                $pos = strpos($raw_path, 'uploads/');
                                if ($pos !== false) {
                                    $safe_pdf_path = '../' . substr($raw_path, $pos);
                                } else {
                                    $safe_pdf_path = '../' . $raw_path; // Fallback
                                }
                            ?>
                            
                            <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" target="_blank" class="btn btn-outline-danger fw-bold px-4 py-2 mt-4" style="border-radius: 10px; border-width: 2px; transition: var(--transition-bounce);">
                                <i class="fas fa-external-link-alt me-2"></i> Open Full Screen PDF
                            </a>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: EVALUATION PANEL -->
                <div class="col-md-5 eval-panel-col">
                    <div class="content-box h-100">
                        <h5 class="box-title"><i class="fas fa-edit text-primary me-2"></i> Faculty Evaluation Panel</h5>
                        <hr class="mb-4" style="border-color: #e2e8f0;">
                        
                        <form action="Review & Marks.php?id=<?php echo $selected_sub_id; ?>" method="POST">
                            <input type="hidden" name="submission_id" value="<?php echo $current_sub['submission_id']; ?>">
                            <input type="hidden" name="evaluate_submission" value="1">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Give Marks (out of 20)</label>
                                <div class="input-group shadow-sm" style="border-radius: 10px; overflow: hidden;">
                                    <span class="input-group-text bg-light border-end-0 text-primary fw-bold px-3"><i class="fas fa-star"></i></span>
                                    <input type="number" name="marks" class="form-control border-start-0 ps-0" min="0" max="20" required value="<?php echo (int)$current_sub['marks']; ?>" placeholder="e.g. 18" style="font-size: 18px; font-weight: 700;">
                                </div>
                            </div>
                            
                            <div class="mb-5">
                                <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Remarks & Feedback</label>
                                <textarea name="feedback" class="form-control shadow-sm" rows="5" placeholder="Great work, diagrams are neat and logic is clear..." style="resize: none;"><?php echo htmlspecialchars($current_sub['feedback'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="d-grid gap-3">
                                <button type="submit" name="action_btn" value="Approve" class="btn btn-success btn-action text-white">
                                    <i class="fas fa-check-circle me-2"></i> Approve Submission
                                </button>
                                
                                <div class="row g-2">
                                    <div class="col-6">
                                        <button type="submit" name="action_btn" value="Re-submit" class="btn btn-warning w-100 btn-action text-dark">
                                            <i class="fas fa-redo me-2"></i> Re-submit
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button type="submit" name="action_btn" value="Reject" class="btn btn-danger w-100 btn-action" onclick="return confirm('Are you sure you want to Reject this submission?');">
                                            <i class="fas fa-times-circle me-2"></i> Reject
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- NO SUBMISSIONS FOUND STATE -->
            <div class="content-box text-center py-5 shadow-sm">
                <div class="mb-4 mt-2">
                    <i class="fas fa-folder-open text-muted" style="font-size: 60px; opacity: 0.5;"></i>
                </div>
                <h4 class="fw-bold text-dark">No Student Submissions Found</h4>
                <p class="text-muted fw-semibold mb-4">There are currently no lab manual submissions matching your search.</p>
                <button class="btn btn-light border fw-bold px-4" onclick="window.location.href='Review & Marks.php';">
                    <i class="fas fa-sync-alt text-primary me-2"></i> Refresh List
                </button>
            </div>
        <?php endif; ?>

        <!-- 📊 HIDDEN TABLE FOR EXCEL EXPORT (Also used as data source for printing) -->
        <?php if($submissions_list && $submissions_list->num_rows > 0): ?>
        <table id="hiddenExportTable" style="display:none;">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Enrollment No</th>
                    <th>Subject Name</th>
                    <th>Practical No</th>
                    <th>Status</th>
                    <th>Marks</th>
                    <th>Feedback</th>
                    <th>Submitted At</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $submissions_list->data_seek(0);
                while($export_row = $submissions_list->fetch_assoc()): 
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($export_row['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($export_row['enrollment']); ?></td>
                    <td><?php echo htmlspecialchars($export_row['subject_name']); ?></td>
                    <td><?php echo htmlspecialchars($export_row['practical_no']); ?></td>
                    <td><?php echo htmlspecialchars($export_row['status']); ?></td>
                    <td><?php echo htmlspecialchars($export_row['marks']); ?></td>
                    <td><?php echo htmlspecialchars($export_row['feedback']); ?></td>
                    <td><?php echo date('d M Y, h:i A', strtotime($export_row['submitted_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php endif; ?>

    </div>

    <!-- 🖨️ PRINT ONLY CONTAINER (Visible ONLY when printing) -->
    <div id="printReportContainer">
        <div class="print-header">
            <h2>K.D. Polytechnic</h2>
            <h4>Lab Manual Submissions Report</h4>
            <p>Generated by: <?php echo htmlspecialchars($user_name); ?> | Date: <?php echo date('d M Y, h:i A'); ?></p>
        </div>
        <table class="print-table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Enrollment No</th>
                    <th>Subject</th>
                    <th>Practical No</th>
                    <th>Status</th>
                    <th>Marks</th>
                    <th>Date Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if($submissions_list && $submissions_list->num_rows > 0) {
                    $submissions_list->data_seek(0);
                    while($print_row = $submissions_list->fetch_assoc()): 
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($print_row['student_name']); ?></td>
                    <td><?php echo htmlspecialchars($print_row['enrollment']); ?></td>
                    <td><?php echo htmlspecialchars($print_row['subject_name']); ?></td>
                    <td><?php echo htmlspecialchars($print_row['practical_no']); ?></td>
                    <td><?php echo htmlspecialchars($print_row['status']); ?></td>
                    <td><?php echo htmlspecialchars($print_row['marks']); ?></td>
                    <td><?php echo date('d M Y', strtotime($print_row['submitted_at'])); ?></td>
                </tr>
                <?php 
                    endwhile; 
                } else {
                    echo "<tr><td colspan='7' style='text-align:center;'>No submissions found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 1. Live Clock Script
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // 2. EXPORT HIDDEN SUBMISSIONS TO EXCEL/CSV
        function exportSubmissionsToCSV(filename) {
            var table = document.getElementById("hiddenExportTable");
            if(!table) {
                alert("No data available to export.");
                return;
            }

            var csv = [];
            var rows = table.querySelectorAll("tr");
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                
                for (var j = 0; j < cols.length; j++) {
                    var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " "); // remove line breaks
                    row.push('"' + data + '"');
                }
                csv.push(row.join(","));
            }

            var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
            var downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>