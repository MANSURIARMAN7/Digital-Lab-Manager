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
$user_data = $user_query->fetch_assoc();
$user_name = $user_data['name'] ?? 'System Administrator';

// Ensure table exists (With answer_text column)
$conn->query("CREATE TABLE IF NOT EXISTS student_submissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    manual_id INT NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    practical_no VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    answer_text TEXT DEFAULT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'Pending',
    marks INT DEFAULT 0,
    feedback TEXT DEFAULT NULL
)");

// Ensure answer_text column is there for old databases
$check_col = $conn->query("SHOW COLUMNS FROM student_submissions LIKE 'answer_text'");
if ($check_col && $check_col->num_rows == 0) {
    $conn->query("ALTER TABLE student_submissions ADD COLUMN answer_text TEXT NULL AFTER file_path");
}

// 🎯 Save Evaluation Logic
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
        $message = "<div class='alert alert-success alert-dismissible fade show mb-4 shadow-sm' style='border-radius:10px; font-weight:600;'><i class='fas fa-check-circle me-2'></i>Evaluation saved to database! Status updated to <b>$status</b>.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    } else {
        $message = "<div class='alert alert-danger alert-dismissible fade show mb-4 shadow-sm' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i>Error saving evaluation: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// 🔍 SEARCH & FETCH SUBMISSIONS
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
            --shadow-float: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 13px; color: #bfdbfe; margin-top: 5px; font-weight: 500;} 
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 10px 22px 10px 28px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: var(--primary); font-weight: 800; text-transform: uppercase; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 17px; color: var(--text-main); font-weight: 900;}
        
        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .icon-box-green { width: 50px; height: 50px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 600; font-size: 14px;}
        .form-control:focus, .form-select:focus { border-color: var(--primary); outline: none; background: white; box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);}
        
        .pdf-viewer-col { height: 100%; border: 1px solid #cbd5e1; border-radius: 12px; overflow: hidden; background: #e2e8f0; }
        .eval-panel-col { padding-left: 25px; }
        
        .btn-action-view { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 8px 15px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
        .btn-action-view:hover { background: #2563eb; color: white; transform: translateY(-2px); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        /* Print Styles */
        @media print {
            .sidebar, .topbar, .page-header, .filter-box, .eval-panel-col, .btn, form { display: none !important; }
            .main { padding: 0 !important; width: 100% !important; height: auto !important; overflow: visible !important;}
            .content-box { border: none !important; box-shadow: none !important; padding: 0 !important;}
            #printReportContainer { display: block !important; }
        }
        #printReportContainer { display: none; }
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
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-inbox"></i> Submissions</li>
            <li class="active" onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-double"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
                
                <form method="GET" class="d-flex shadow-sm" style="border-radius: 8px;">
                    <input type="text" name="search" class="form-control border-end-0 px-4" placeholder="Search student or subject..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 250px;">
                    <button type="submit" class="btn btn-primary px-3"><i class="fas fa-search"></i></button>
                </form>
            </div>
            
            <a href="Profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Welcome Back,</span>
                    <h4 class="profile-name">
                        <?php echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : 'Admin'; ?>
                    </h4>
                </div>
                <div class="profile-avatar"><i class="fas fa-user-shield"></i></div>
            </a>
        </div>

        <?php echo $message; ?>

        <!-- PAGE HEADER -->
        <div class="page-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box-green"><i class="fas fa-check-double"></i></div>
                <div>
                    <h3 class="fw-bold mb-1">Review & Evaluation</h3>
                    <p class="text-muted fw-semibold small mb-0">Assess student lab manuals, grade submissions, and provide feedback.</p>
                </div>
            </div>
            <?php if (!empty($search_query)): ?>
                <a href="Review & Marks.php" class="btn btn-outline-danger fw-bold" style="border-radius: 10px; padding: 10px 20px;">
                    <i class="fas fa-times me-1"></i> Clear Search
                </a>
            <?php endif; ?>
        </div>

        <!-- SUBMISSION SELECTOR -->
        <?php if($submissions_list && $submissions_list->num_rows > 0): ?>
            <div class="content-box mb-4 py-3 px-4" style="background: linear-gradient(135deg, rgba(67, 56, 202, 0.03), rgba(59, 130, 246, 0.05));">
                <form method="GET" action="Review & Marks.php" class="row align-items-center g-3">
                    <div class="col-md-9">
                        <label class="form-label fw-bold small text-primary text-uppercase letter-spacing-1 mb-2"><i class="fas fa-filter me-1"></i> Select Student Submission to Review</label>
                        <select name="id" class="form-select shadow-sm py-2" style="cursor: pointer;" onchange="this.form.submit()">
                            <?php 
                            $submissions_list->data_seek(0);
                            while($item = $submissions_list->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $item['submission_id']; ?>" <?php echo ($selected_sub_id == $item['submission_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($item['student_name']); ?> (<?php echo htmlspecialchars($item['enrollment']); ?>) | <?php echo htmlspecialchars($item['subject_name']); ?> | Prac: <?php echo htmlspecialchars($item['practical_no']); ?> - [<?php echo $item['status']; ?>]
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3 text-end mt-4">
                        <?php 
                            $status_color = 'bg-secondary';
                            if($current_sub['status'] == 'Approved') $status_color = 'bg-success';
                            elseif($current_sub['status'] == 'Rejected') $status_color = 'bg-danger';
                            elseif($current_sub['status'] == 'Pending') $status_color = 'bg-warning text-dark';
                        ?>
                        <div class="badge <?php echo $status_color; ?> px-4 py-3 shadow-sm fs-6 rounded-pill border">
                            Status: <strong><?php echo htmlspecialchars($current_sub['status']); ?></strong>
                        </div>
                    </div>
                </form>
            </div>

            <!-- TWO COLUMN LAYOUT: FILE VIEWER & EVALUATION PANEL -->
            <?php if($current_sub): ?>
                <div class="row g-4">
                    
                    <!-- LEFT COLUMN: SMART FILE VIEWER -->
                    <div class="col-md-7">
                        <div class="content-box h-100 d-flex flex-column p-0">
                            <?php 
                                $raw_path = $current_sub['file_path'];
                                $pos = strpos($raw_path, 'uploads/');
                                $safe_pdf_path = ($pos !== false) ? '../' . substr($raw_path, $pos) : '../' . $raw_path;
                                $file_ext = strtolower(pathinfo($safe_pdf_path, PATHINFO_EXTENSION));
                            ?>
                            
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light">
                                <h5 class="mb-0 fw-bold"><i class="fas fa-paperclip text-danger me-2"></i> Attached File</h5>
                                <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-bold px-3">
                                    <i class="fas fa-external-link-alt me-1"></i> Open File
                                </a>
                            </div>
                            
                            <!-- Viewer Body -->
                            <div class="flex-grow-1 position-relative" style="min-height: 600px; display: flex; align-items: center; justify-content: center; background: #e2e8f0;">
                                <?php if($file_ext == 'pdf'): ?>
                                    <iframe src="<?php echo htmlspecialchars($safe_pdf_path); ?>" width="100%" height="100%" style="border:none; position: absolute; top:0; left:0; right:0; bottom:0;"></iframe>
                                <?php else: ?>
                                    <div class="text-center p-5">
                                        <i class="fas fa-file-archive text-secondary mb-3" style="font-size: 60px;"></i>
                                        <h5 class="fw-bold text-dark">File Preview Not Available</h5>
                                        <p class="text-muted small">This is a .<?php echo htmlspecialchars($file_ext); ?> file. Please download it to view the contents.</p>
                                        <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" download class="btn btn-primary mt-2 px-4 py-2" style="border-radius: 8px; font-weight: bold;">
                                            <i class="fas fa-download me-2"></i> Download File
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: EVALUATION PANEL -->
                    <div class="col-md-5 eval-panel-col">
                        <div class="content-box h-100 border-start-0">
                            <h5 class="fw-bold mb-4 border-bottom pb-3"><i class="fas fa-pen-nib text-primary me-2"></i> Faculty Evaluation Panel</h5>
                            
                            <!-- DISPLAY STUDENT'S CODE / OBSERVATION HERE -->
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1"><i class="fas fa-terminal text-dark me-1"></i> Student's Observation / Code</label>
                                <div class="p-3 bg-light border rounded-3" style="min-height: 100px; max-height: 250px; overflow-y: auto; font-family: monospace; font-size: 13px; color: var(--text-main);">
                                    <?php 
                                        $student_text = trim($current_sub['answer_text'] ?? '');
                                        echo empty($student_text) ? '<span class="text-muted fst-italic">No observation or code provided by the student.</span>' : nl2br(htmlspecialchars($student_text));
                                    ?>
                                </div>
                            </div>
                            
                            <hr class="mb-4">

                            <form action="Review & Marks.php?id=<?php echo $selected_sub_id; ?>" method="POST">
                                <input type="hidden" name="submission_id" value="<?php echo $current_sub['submission_id']; ?>">
                                <input type="hidden" name="evaluate_submission" value="1">
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Give Marks (out of 20)</label>
                                    <div class="input-group shadow-sm border rounded-3 overflow-hidden">
                                        <span class="input-group-text bg-light border-0 text-primary fw-bold px-3"><i class="fas fa-star"></i></span>
                                        <input type="number" name="marks" class="form-control border-0" min="0" max="20" required value="<?php echo (int)$current_sub['marks']; ?>" placeholder="e.g. 18" style="font-size: 18px; font-weight: 700;">
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1">Remarks & Feedback</label>
                                    <textarea name="feedback" class="form-control shadow-sm" rows="3" placeholder="Great work, diagrams are neat..." style="resize: none;"><?php echo htmlspecialchars($current_sub['feedback'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="d-grid gap-3 mt-5">
                                    <button type="submit" name="action_btn" value="Approve" class="btn btn-success fw-bold py-3 text-white" style="border-radius: 12px; font-size: 15px;">
                                        <i class="fas fa-check-circle me-2"></i> Approve Submission
                                    </button>
                                    
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <button type="submit" name="action_btn" value="Re-submit" class="btn btn-warning fw-bold w-100 py-3 text-dark" style="border-radius: 12px;">
                                                <i class="fas fa-redo me-2"></i> Re-submit
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button type="submit" name="action_btn" value="Reject" class="btn btn-danger fw-bold w-100 py-3" style="border-radius: 12px;" onclick="return confirm('Are you sure you want to Reject this submission?');">
                                                <i class="fas fa-times-circle me-2"></i> Reject
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        
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

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live Clock Script
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const clockEl = document.getElementById('liveClock');
            if(clockEl) clockEl.innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>