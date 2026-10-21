<?php
session_start();
include '../db.php';

// 1. SECURE LOGIN CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$enrollment = $conn->real_escape_string((string)$_SESSION['user_id']);

// 2. FETCH STUDENT PROFILE
$user_query = $conn->query("SELECT name FROM users WHERE user_id = '$enrollment'");
$student_data = $user_query->fetch_assoc();
$student_name = $student_data['name'] ?? 'Student';

// Initials for Profile Avatar
$name_parts = explode(' ', trim($student_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) { $initials .= strtoupper(substr(end($name_parts), 0, 1)); }

// 3. FETCH STATS
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM student_submissions 
    WHERE student_id = '$enrollment'
");
$stats = $stats_query->fetch_assoc();

// 4. FETCH SUBMISSION HISTORY
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$safe_search = $conn->real_escape_string($search_query);

$hist_sql = "
    SELECT s.*, lm.title, lm.end_date 
    FROM student_submissions s 
    LEFT JOIN lab_manuals lm ON s.manual_id = lm.id 
    WHERE s.student_id = '$enrollment'
";

if (!empty($search_query)) {
    $hist_sql .= " AND (s.subject_name LIKE '%$safe_search%' OR lm.title LIKE '%$safe_search%')";
}
$hist_sql .= " ORDER BY s.submitted_at DESC";

$history_res = $conn->query($hist_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions History - KDP Student Portal</title>
    
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
            --radius-xl: 16px;
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        /* 🔥 PREMIUM MICROSOFT UNIFIED SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 13px; color: #bfdbfe; margin-top: 5px; font-weight: 500; letter-spacing: 0.5px;} 
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        /* 🔥 MAIN CONTENT ANIMATION */
        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* 🌐 TOPBAR & PROFILE PILL */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 10px 22px 10px 28px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: var(--primary); font-weight: 800; text-transform: uppercase; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 17px; color: var(--text-main); font-weight: 900;}
        .profile-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.4);}

        /* 📦 CONTENT BOXES & STATS */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 25px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); display: flex; align-items: center;}
        
        .stat-card-rad { background: var(--surface); border-radius: 12px; padding: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; position: relative; overflow: hidden;}
        .stat-card-rad::after { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; }
        .card-blue::after { background: #3b82f6; }
        .card-yellow::after { background: #f59e0b; }
        .card-green::after { background: #10b981; }
        .card-red::after { background: #ef4444; }
        .stat-info h4 { font-size: 26px; font-weight: 900; color: var(--text-main); margin: 0; line-height: 1;}
        .stat-info span { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; margin-bottom: 5px; display: block;}
        .stat-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }

        .search-group { display: flex; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; background: #f8fafc; width: 300px;}
        .search-group input { border: none; background: transparent; padding: 10px 15px; font-weight: 600; font-size: 14px; flex: 1; outline: none;}
        .search-group button { border: none; background: var(--primary); color: white; padding: 0 20px; cursor: pointer; transition: 0.2s;}
        
        /* 📋 TABLE */
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; }
        
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;}
        .status-Pending { background: #fef3c7; color: #d97706; border: 1px solid #fde68a;}
        .status-Approved { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0;}
        .status-Rejected { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;}

        .btn-action { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 8px 15px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: var(--transition-bounce); display: inline-flex; align-items: center; gap: 6px; cursor: pointer;}
        .btn-action:hover { background: #2563eb; color: white; transform: translateY(-2px); }
        .btn-feedback { background: rgba(16, 185, 129, 0.1); color: #059669; }
        .btn-feedback:hover { background: #059669; color: white; }

        /* MODALS STYLING */
        .rubric-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); z-index: 9999; align-items: center; justify-content: center; }
        .rubric-modal-overlay.active { display: flex; }
        .rubric-modal { background: white; border-radius: 16px; width: 450px; max-width: 95%; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: fadeUp 0.3s ease-out;}
        .modal-header-custom { padding: 18px 25px; background: linear-gradient(135deg, #1e3a8a, #4338ca); color: white; display: flex; justify-content: space-between; align-items: center;}
        .btn-close-custom { background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center;}
        .btn-close-custom:hover { background: rgba(255,255,255,0.4); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Student Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='Stdashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='my-manuals.php'"><i class="fas fa-book-open"></i> Course Manuals</li>
            <li class="active" onclick="window.location.href='history.php'"><i class="fas fa-history"></i> My Submissions</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            </div>
            
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Logged in as,</span>
                    <h4 class="profile-name">
                        <?php echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : $student_name; ?>
                    </h4>
                    <span style="font-size:11px; font-weight:700; color:var(--primary);"><?php echo htmlspecialchars($enrollment); ?></span>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </a>
        </div>

        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1" style="font-size: 26px;">My Submissions History</h3>
            <p class="text-muted fw-semibold small mb-0">Track all your uploaded lab manuals, check their evaluation status, and view faculty remarks.</p>
        </div>

        <!-- 🗂️ STATS GRID -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card-rad card-blue">
                    <div class="stat-info"><span>Total Uploads</span><h4><?php echo $stats['total'] ?? 0; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-cloud-upload-alt"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-rad card-green">
                    <div class="stat-info"><span>Approved</span><h4><?php echo $stats['approved'] ?? 0; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-rad card-yellow">
                    <div class="stat-info"><span>Pending Review</span><h4><?php echo $stats['pending'] ?? 0; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-rad card-red">
                    <div class="stat-info"><span>Rejected</span><h4><?php echo $stats['rejected'] ?? 0; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
        </div>

        <!-- 📋 SUBMISSIONS TABLE -->
        <div class="content-box p-0 overflow-hidden">
            <div class="d-flex justify-content-between align-items-center p-4 border-bottom">
                <h5 class="box-title mb-0 border-0 pb-0"><i class="fas fa-history text-primary me-2"></i> Evaluation Records</h5>
                
                <form method="GET" class="search-group">
                    <input type="text" name="search" placeholder="Search Subject or Title..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Subject & Practical</th>
                            <th>Submission Timeline</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($history_res && $history_res->num_rows > 0): ?>
                            <?php while($row = $history_res->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark" style="font-size: 14.5px;"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                                        <div class="text-muted fw-bold small mt-1">Prac: <?php echo htmlspecialchars($row['practical_no']); ?> | <?php echo htmlspecialchars($row['title'] ?? 'Manual'); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size: 12px; color: var(--text-main);" class="fw-semibold">
                                            <i class="fas fa-calendar-check text-success me-1"></i> Submitted: <?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?>
                                            
                                            <?php 
                                            // Late Tag Logic
                                            if(!empty($row['end_date']) && $row['end_date'] != '0000-00-00') {
                                                $deadline = strtotime($row['end_date'] . ' 23:59:59');
                                                $submitted = strtotime($row['submitted_at']);
                                                if($submitted > $deadline) {
                                                    echo "<div class='mt-1 text-danger'><i class='fas fa-exclamation-triangle me-1'></i> Submitted Late</div>";
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-modern status-<?php echo htmlspecialchars($row['status']); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($row['status'] !== 'Pending'): ?>
                                            <strong class="<?php echo ($row['status'] == 'Approved') ? 'text-success' : 'text-danger'; ?>" style="font-size:14px;">
                                                <?php echo $row['marks']; ?> <small class="text-muted fw-normal" style="font-size: 12px;">/ 20</small>
                                            </strong>
                                        <?php else: ?>
                                            <span class="text-muted small fw-bold">--</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php 
                                            $raw_path = $row['file_path'];
                                            $pos = strpos($raw_path, 'uploads/');
                                            $safe_pdf_path = ($pos !== false) ? '../' . substr($raw_path, $pos) : '../' . $raw_path;
                                        ?>
                                        <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" target="_blank" class="btn-action me-1" title="View Submitted File"><i class="fas fa-file-pdf"></i> View File</a>
                                        
                                        <?php if($row['status'] !== 'Pending'): 
                                            $js_data = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); 
                                        ?>
                                            <button type="button" class="btn-action btn-feedback" onclick='openFeedbackModal(<?php echo $js_data; ?>)' title="View Faculty Remarks">
                                                <i class="fas fa-comment-dots"></i> Remarks
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-history mb-3" style="font-size: 40px; opacity: 0.3;"></i><br>
                                    <span class="fw-bold fs-6">No submission history found.</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- 🎯 MODAL: SCORECARD & FEEDBACK PREVIEW -->
    <div id="feedbackModal" class="rubric-modal-overlay">
        <div class="rubric-modal">
            <div class="modal-header-custom" id="fb_header_bg">
                <h5 class="mb-0 fw-bold"><i class="fas fa-award me-2"></i> Evaluation Scorecard</h5>
                <button type="button" class="btn-close-custom" onclick="closeFeedbackModal()"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="p-4 bg-white">
                <div class="text-center mb-4">
                    <h5 id="fb_subject_name" class="fw-bold text-dark mb-1">Subject Name</h5>
                    <p id="fb_prac_no" class="text-muted small fw-bold mb-0">Practical Details</p>
                </div>
                
                <div class="bg-light border rounded-3 p-3 mb-4">
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><span class="text-muted fw-bold">Regularity</span><span id="fb_reg" class="text-primary fw-bold"></span></div>
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><span class="text-muted fw-bold">Understanding</span><span id="fb_und" class="text-primary fw-bold"></span></div>
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><span class="text-muted fw-bold">Observation & Logic</span><span id="fb_obs" class="text-primary fw-bold"></span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted fw-bold">Viva / Quiz</span><span id="fb_viva" class="text-primary fw-bold"></span></div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center bg-dark text-white p-3 rounded-3 mb-3 shadow-sm">
                    <span class="text-uppercase fw-bold" style="letter-spacing: 1px;">Total Score Assigned</span>
                    <h4 id="fb_total" class="mb-0 fw-bold"></h4>
                </div>
                
                <div class="p-3 bg-light border-start border-4 rounded" id="fb_remark_box">
                    <span class="text-muted small fw-bold d-block mb-1">Faculty Remarks:</span>
                    <div id="fb_remark" class="fst-italic text-dark"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        function openFeedbackModal(data) {
            document.getElementById('fb_subject_name').innerText = data.subject_name;
            document.getElementById('fb_prac_no').innerText = "Practical: " + data.practical_no + " | " + (data.title || 'Manual');
            
            document.getElementById('fb_reg').innerText = data.mark_reg + " / 5";
            document.getElementById('fb_und').innerText = data.mark_und + " / 5";
            document.getElementById('fb_obs').innerText = data.mark_obs + " / 5";
            document.getElementById('fb_viva').innerText = data.mark_viva + " / 5";
            
            let totalBox = document.getElementById('fb_total');
            let headerBg = document.getElementById('fb_header_bg');
            let remarkBox = document.getElementById('fb_remark_box');
            
            totalBox.innerText = data.marks + " / 20";
            
            if (data.status === 'Rejected') {
                headerBg.style.background = 'linear-gradient(135deg, #dc2626, #ef4444)';
                totalBox.classList.remove('text-warning');
                totalBox.classList.add('text-danger');
                remarkBox.classList.remove('border-info');
                remarkBox.classList.add('border-danger');
            } else {
                headerBg.style.background = 'linear-gradient(135deg, #059669, #10b981)';
                totalBox.classList.remove('text-danger');
                totalBox.classList.add('text-warning');
                remarkBox.classList.remove('border-danger');
                remarkBox.classList.add('border-info');
            }

            let remarkText = document.getElementById('fb_remark');
            if(data.feedback && data.feedback.trim() !== '') {
                remarkText.innerText = '"' + data.feedback + '"';
            } else {
                remarkText.innerHTML = '<span class="text-muted">No specific remarks provided by faculty.</span>';
            }

            document.getElementById('feedbackModal').classList.add('active');
        }

        function closeFeedbackModal() {
            document.getElementById('feedbackModal').classList.remove('active');
        }
    </script>
</body>
</html>