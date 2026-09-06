<?php
session_start();
include '../db.php';

// 1. Secure Student Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$enrollment = $conn->real_escape_string((string)$_SESSION['user_id']);

// 2. Fetch Profile Info for Topbar
$user_query = $conn->query("SELECT name, department, designation FROM users WHERE user_id = '$enrollment'");
$student_data = $user_query->fetch_assoc();
$student_name = $student_data['name'] ?? 'Student';

// Generate Initials
$name_parts = explode(' ', trim($student_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

// 3. Fetch Full Submission History
$history_query = $conn->query("SELECT * FROM student_submissions WHERE student_id = '$enrollment' ORDER BY submitted_at DESC");

// 4. Fetch Stats for Right Widget
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
$total_sub = $stats['total'] ?? 0;
$approved = $stats['approved'] ?? 0;
$pending = $stats['pending'] ?? 0;
$rejected = $stats['rejected'] ?? 0;

// 5. Fetch Recent Graded Submissions (For Trophy Box)
$recent_grades = $conn->query("SELECT subject_name, practical_no, marks, status FROM student_submissions WHERE student_id = '$enrollment' AND status != 'Pending' ORDER BY submitted_at DESC LIMIT 4");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - KDP</title>
    
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
        
        /* 🔥 PREMIUM BLUE SIDEBAR */
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

        /* 🌐 TOPBAR */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        .security-badge { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 10px; padding: 10px 18px; font-weight: 700; font-size: 13px; }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3); letter-spacing: 1px;}

        /* HEADER */
        .icon-box { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 26px; }
        .blue-box { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 25px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 20px;}
        .box-title { font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }

        /* ✨ TIMELINE STYLES (PREMIUM LOOK) */
        .timeline { position: relative; width: 100%; padding-left: 35px; margin-top: 10px; }
        .timeline::before { content: ''; position: absolute; left: 8px; top: 0; bottom: 0; width: 3px; background: #e2e8f0; border-radius: 4px; }
        
        .timeline-item { position: relative; margin-bottom: 25px; }
        .timeline-icon { position: absolute; left: -36.5px; top: 0; width: 22px; height: 22px; background: white; border: 5px solid var(--primary); border-radius: 50%; box-shadow: 0 0 0 4px rgba(67, 56, 202, 0.1); z-index: 1; transition: var(--transition-bounce);}
        .timeline-item:hover .timeline-icon { transform: scale(1.2); box-shadow: 0 0 0 5px rgba(67, 56, 202, 0.2); }
        
        .timeline-content { background: var(--surface); border: 1px solid rgba(226, 232, 240, 0.8); border-radius: 12px; padding: 20px 25px; box-shadow: 0 2px 5px rgba(0,0,0,0.01); transition: var(--transition-bounce); }
        .timeline-content:hover { transform: translateY(-3px); box-shadow: var(--shadow-float); border-color: #cbd5e1; }
        
        .timeline-date { font-size: 11.5px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; display: block; letter-spacing: 0.5px;}
        .timeline-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin: 0 0 6px 0; }
        .timeline-desc { font-size: 13.5px; color: #475569; margin: 0 0 15px 0; line-height: 1.5; font-weight: 500;}
        
        /* STATUS BADGES & FILTERS */
        .filter-btn { background: white; border: 1px solid #cbd5e1; color: #64748b; padding: 6px 16px; border-radius: 30px; font-size: 13px; font-weight: 700; cursor: pointer; transition: 0.2s; margin-right: 8px; margin-bottom: 10px;}
        .filter-btn.active, .filter-btn:hover { background: var(--primary); color: white; border-color: var(--primary); box-shadow: 0 4px 10px rgba(67, 56, 202, 0.2);}

        .badge-status { padding: 6px 14px; border-radius: 30px; font-size: 11px; font-weight: 800; display: inline-flex; align-items: center; gap: 5px; letter-spacing: 0.5px;}
        .status-Pending { background: #fef3c7; color: #d97706; border: 1px solid #fde68a;}
        .status-Approved { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0;}
        .status-Rejected { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;}
        
        .btn-view { background: rgba(59, 130, 246, 0.1); color: #2563eb; padding: 6px 16px; border-radius: 8px; font-size: 12px; font-weight: 700; text-decoration: none; transition: var(--transition-bounce); border: none; display: inline-flex; align-items: center; gap: 6px;}
        .btn-view:hover { background: #2563eb; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2); }

        /* WIDGETS */
        .stat-mini-box { display: flex; align-items: center; gap: 15px; padding: 15px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; margin-bottom: 12px; transition: 0.2s;}
        .stat-mini-box:hover { background: white; border-color: #cbd5e1; transform: translateX(5px); }
        .stat-icon-mini { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }

        .grade-item { padding: 12px 15px; border-left: 3px solid var(--primary); background: #f8fafc; border-radius: 0 8px 8px 0; margin-bottom: 10px; transition: 0.2s; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;}
        .grade-item:hover { background: white; border-left-width: 5px; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="Logo"> 
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
        
        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge">
                    <i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span>
                </div>
                <div class="security-badge d-none d-md-flex align-items-center">
                    <i class="fas fa-shield-check me-2"></i> Secure Session Verified
                </div>
            </div>
            
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Logged in as</span>
                    <h4 class="profile-name">
                        <?php 
                            echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : $student_name;
                        ?>
                    </h4>
                    <span style="font-size:11px; font-weight:700; color:var(--primary);"><?php echo htmlspecialchars($enrollment); ?></span>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </a>
        </div>

        <!-- PAGE HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4 mt-2 page-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box blue-box"><i class="fas fa-history"></i></div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);">Activity & History</h3>
                    <p class="text-muted fw-semibold small mb-0">Track your submission timeline and check recent faculty feedback.</p>
                </div>
            </div>
        </div>

        <!-- 2-COLUMN LAYOUT TO FILL EMPTY SPACE -->
        <div class="row g-4">
            
            <!-- LEFT: TIMELINE (Takes 65% width) -->
            <div class="col-lg-8">
                
                <!-- TIMELINE FILTERS -->
                <div class="mb-3 d-flex flex-wrap">
                    <button class="filter-btn active" onclick="filterData('All', this)"><i class="fas fa-list me-1"></i> All Submissions</button>
                    <button class="filter-btn text-warning border-warning" onclick="filterData('Pending', this)"><i class="fas fa-clock me-1"></i> Pending</button>
                    <button class="filter-btn text-success border-success" onclick="filterData('Approved', this)"><i class="fas fa-check-circle me-1"></i> Approved</button>
                    <button class="filter-btn text-danger border-danger" onclick="filterData('Rejected', this)"><i class="fas fa-times-circle me-1"></i> Rejected</button>
                </div>

                <!-- TIMELINE SECTION -->
                <div class="timeline">
                    <?php if($history_query && $history_query->num_rows > 0): ?>
                        <?php while($row = $history_query->fetch_assoc()): ?>
                            <div class="timeline-item sub-row" data-status="<?php echo htmlspecialchars($row['status']); ?>">
                                
                                <!-- Timeline Dot -->
                                <div class="timeline-icon 
                                    <?php 
                                        if($row['status'] == 'Approved') echo 'border-success'; 
                                        elseif($row['status'] == 'Rejected') echo 'border-danger'; 
                                        else echo 'border-warning'; 
                                    ?>">
                                </div>
                                
                                <!-- Content Card -->
                                <div class="timeline-content">
                                    <span class="timeline-date"><i class="far fa-clock me-1"></i> <?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?></span>
                                    
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                        <div class="flex-grow-1">
                                            <h3 class="timeline-title">Submitted <?php echo htmlspecialchars($row['practical_no']); ?></h3>
                                            <p class="timeline-desc">
                                                Practical document uploaded for <strong><?php echo htmlspecialchars($row['subject_name']); ?></strong>.
                                            </p>
                                            
                                            <span class="badge-status status-<?php echo htmlspecialchars($row['status']); ?>">
                                                <?php 
                                                    if($row['status'] == 'Pending') echo '<i class="fas fa-spinner fa-spin"></i> Under Review';
                                                    elseif($row['status'] == 'Approved') echo '<i class="fas fa-check-circle"></i> Approved';
                                                    else echo '<i class="fas fa-times-circle"></i> Needs Revision';
                                                ?>
                                            </span>
                                            
                                            <?php if($row['status'] == 'Approved' && !empty($row['marks'])): ?>
                                                <span class="ms-2 badge bg-dark" style="font-size: 11px; padding: 7px 14px; border-radius: 20px;">
                                                    <i class="fas fa-star text-warning me-1"></i> Graded: <?php echo htmlspecialchars($row['marks']); ?> / 20
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php 
                                            // SMART FIX FOR 404 PDF ERROR
                                            $raw_path = $row['file_path'];
                                            $pos = strpos($raw_path, 'uploads/');
                                            $safe_pdf_path = ($pos !== false) ? '../' . substr($raw_path, $pos) : '../' . $raw_path;
                                        ?>
                                        <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" target="_blank" class="btn-view shadow-sm">
                                            <i class="fas fa-file-pdf text-danger"></i> View File
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="text-center text-muted py-5" style="background: white; border: 1px dashed #cbd5e1; border-radius: var(--radius-xl); margin-left: 20px;">
                            <i class="fas fa-history mb-3" style="font-size: 50px; opacity: 0.3; color: var(--primary);"></i>
                            <h5 class="fw-bold text-dark">No Timeline Activity</h5>
                            <p class="mb-0 fw-semibold small">You haven't submitted any practicals yet. Head over to Course Manuals to start uploading.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT: WIDGETS & STATS (Takes 35% width) -->
            <div class="col-lg-4">
                
                <!-- WIDGET 1: SUBMISSION ANALYTICS -->
                <div class="content-box">
                    <h5 class="box-title"><i class="fas fa-chart-pie text-primary me-2"></i> Submission Analytics</h5>
                    
                    <div class="stat-mini-box">
                        <div class="stat-icon-mini" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-cloud-upload-alt"></i></div>
                        <div>
                            <h3 class="fw-bold text-dark mb-0" style="font-size: 20px; line-height: 1;"><?php echo $total_sub; ?></h3>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">Total Uploads</small>
                        </div>
                    </div>
                    
                    <div class="stat-mini-box">
                        <div class="stat-icon-mini" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <h3 class="fw-bold text-dark mb-0" style="font-size: 20px; line-height: 1;"><?php echo $approved; ?></h3>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">Approved</small>
                        </div>
                    </div>
                    
                    <div class="stat-mini-box">
                        <div class="stat-icon-mini" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-clock"></i></div>
                        <div>
                            <h3 class="fw-bold text-dark mb-0" style="font-size: 20px; line-height: 1;"><?php echo $pending; ?></h3>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">Pending Review</small>
                        </div>
                    </div>

                    <?php if($rejected > 0): ?>
                    <div class="stat-mini-box">
                        <div class="stat-icon-mini" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-times-circle"></i></div>
                        <div>
                            <h3 class="fw-bold text-danger mb-0" style="font-size: 20px; line-height: 1;"><?php echo $rejected; ?></h3>
                            <small class="text-danger fw-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">Needs Revision</small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- WIDGET 2: RECENT FEEDBACK/GRADES -->
                <div class="content-box">
                    <h5 class="box-title"><i class="fas fa-award text-warning me-2"></i> Recent Evaluations</h5>
                    
                    <?php if($recent_grades && $recent_grades->num_rows > 0): ?>
                        <?php while($grade = $recent_grades->fetch_assoc()): ?>
                            <div class="grade-item <?php echo $grade['status'] == 'Approved' ? 'border-success' : 'border-danger'; ?>">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold text-dark mb-0" style="font-size: 13.5px;"><?php echo htmlspecialchars($grade['subject_name']); ?></h6>
                                    
                                    <?php if($grade['status'] == 'Approved' && !empty($grade['marks'])): ?>
                                        <span class="badge bg-success fw-bold"><i class="fas fa-check me-1"></i> <?php echo $grade['marks']; ?>/20</span>
                                    <?php elseif($grade['status'] == 'Rejected'): ?>
                                        <span class="badge bg-danger fw-bold"><i class="fas fa-redo me-1"></i> Redo</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted fw-semibold" style="font-size: 11px;"><?php echo htmlspecialchars($grade['practical_no']); ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-clipboard-check text-muted mb-2" style="font-size: 28px; opacity: 0.5;"></i>
                            <p class="text-muted small fw-semibold mb-0">No evaluated practicals to show yet.</p>
                        </div>
                    <?php endif; ?>
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

        // 🌟 JS Filter Logic for Timeline
        function filterData(status, btn) {
            // Update active button styling
            let buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(b => b.classList.remove('active', 'text-white'));
            
            btn.classList.add('active', 'text-white');
            
            // Adjust styles based on which button is clicked
            buttons.forEach(b => {
                if(!b.classList.contains('active')){
                    if(b.innerText.includes('Pending')) b.className = "filter-btn text-warning border-warning";
                    if(b.innerText.includes('Approved')) b.className = "filter-btn text-success border-success";
                    if(b.innerText.includes('Rejected')) b.className = "filter-btn text-danger border-danger";
                    if(b.innerText.includes('All')) b.className = "filter-btn text-muted border-secondary";
                } else {
                    if(status === 'Pending') b.className = "filter-btn active bg-warning border-warning text-white";
                    if(status === 'Approved') b.className = "filter-btn active bg-success border-success text-white";
                    if(status === 'Rejected') b.className = "filter-btn active bg-danger border-danger text-white";
                    if(status === 'All') b.className = "filter-btn active bg-primary border-primary text-white";
                }
            });

            // Filter rows
            let rows = document.querySelectorAll('.sub-row');
            rows.forEach(row => {
                if (status === 'All' || row.getAttribute('data-status') === status) {
                    row.style.display = '';
                    setTimeout(() => { row.style.opacity = '1'; row.style.transform = 'translateY(0)'; }, 50);
                } else {
                    row.style.opacity = '0';
                    row.style.transform = 'translateY(10px)';
                    setTimeout(() => { row.style.display = 'none'; }, 200);
                }
            });
        }
    </script>
</body>
</html><?php
/**
 * Student Submission History (Enhanced)
 * -------------------------------------
 * Displays a complete timeline of all practical submissions by the logged-in student,
 * with pagination, filtering, search, detailed stats, and faculty feedback.
 *
 * Security: Uses prepared statements to prevent SQL injection.
 * UX: Real-time filter buttons, search box, subject dropdown, and modal popup for details.
 */

// Start session and include DB connection
session_start();
include '../db.php';

// -----------------------------------------------------------------------------
// 1. SECURE STUDENT LOGIN CHECK
// -----------------------------------------------------------------------------
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
$enrollment = (string) $_SESSION['user_id'];   // Student ID (enrollment number)

// -----------------------------------------------------------------------------
// 2. FETCH PROFILE DATA FOR TOPBAR
// -----------------------------------------------------------------------------
$user_stmt = $conn->prepare("SELECT name, department, designation FROM users WHERE user_id = ?");
$user_stmt->bind_param("s", $enrollment);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$student_data = $user_result->fetch_assoc();
$student_name = $student_data['name'] ?? 'Student';
$user_stmt->close();

// Generate initials for avatar
$name_parts = explode(' ', trim($student_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

// -----------------------------------------------------------------------------
// 3. HANDLE SEARCH, FILTER & PAGINATION PARAMETERS
// -----------------------------------------------------------------------------
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;                                          // Items per page
$offset = ($page - 1) * $limit;

// Filter by status (All, Pending, Approved, Rejected)
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'All';
$allowed_statuses = ['All', 'Pending', 'Approved', 'Rejected'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = 'All';
}

// Search term (practical number or subject name)
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Subject filter (optional, builds dynamic list later)
$subject_filter = isset($_GET['subject']) ? trim($_GET['subject']) : '';

// -----------------------------------------------------------------------------
// 4. BUILD THE QUERY WITH PREPARED STATEMENTS
// -----------------------------------------------------------------------------
// We also join with the users table to get the evaluator's name (if any)
$sql = "SELECT s.*, 
               u.name AS evaluator_name
        FROM student_submissions s
        LEFT JOIN users u ON s.evaluator_id = u.user_id
        WHERE s.student_id = ?";

$params = [$enrollment];
$types = "s";

// Apply status filter
if ($status_filter !== 'All') {
    $sql .= " AND s.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Apply search filter (practical_no or subject_name)
if (!empty($search_term)) {
    $sql .= " AND (s.practical_no LIKE ? OR s.subject_name LIKE ?)";
    $like_term = "%" . $search_term . "%";
    $params[] = $like_term;
    $params[] = $like_term;
    $types .= "ss";
}

// Apply subject filter (if a subject is selected)
if (!empty($subject_filter)) {
    $sql .= " AND s.subject_name = ?";
    $params[] = $subject_filter;
    $types .= "s";
}

// Order by most recent first
$sql .= " ORDER BY s.submitted_at DESC";

// -----------------------------------------------------------------------------
// 5. COUNT TOTAL MATCHING RECORDS FOR PAGINATION
// -----------------------------------------------------------------------------
$count_sql = str_replace(
    "SELECT s.*, u.name AS evaluator_name",
    "SELECT COUNT(*) AS total",
    $sql
);
$count_stmt = $conn->prepare($count_sql);
if ($count_stmt) {
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_rows = $count_result->fetch_assoc()['total'] ?? 0;
    $count_stmt->close();
} else {
    $total_rows = 0;
}
$total_pages = ceil($total_rows / $limit);

// -----------------------------------------------------------------------------
// 6. FETCH PAGINATED SUBMISSION DATA
// -----------------------------------------------------------------------------
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$history_stmt = $conn->prepare($sql);
if (!$history_stmt) {
    die("Query preparation failed: " . $conn->error);
}
$history_stmt->bind_param($types, ...$params);
$history_stmt->execute();
$history_result = $history_stmt->get_result();
$history_stmt->close();

// -----------------------------------------------------------------------------
// 7. FETCH STATISTICS FOR THE RIGHT WIDGET
// -----------------------------------------------------------------------------
$stats_sql = "SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) AS rejected,
                AVG(CASE WHEN status = 'Approved' AND marks IS NOT NULL THEN marks ELSE NULL END) AS avg_marks
              FROM student_submissions
              WHERE student_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("s", $enrollment);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

$total_sub   = $stats['total'] ?? 0;
$approved    = $stats['approved'] ?? 0;
$pending     = $stats['pending'] ?? 0;
$rejected    = $stats['rejected'] ?? 0;
$avg_marks   = $stats['avg_marks'] ? number_format($stats['avg_marks'], 1) : 'N/A';

// -----------------------------------------------------------------------------
// 8. FETCH RECENT GRADED SUBMISSIONS (FOR TROPHY BOX)
// -----------------------------------------------------------------------------
$recent_sql = "SELECT subject_name, practical_no, marks, status, submitted_at 
               FROM student_submissions 
               WHERE student_id = ? AND status != 'Pending' 
               ORDER BY submitted_at DESC LIMIT 4";
$recent_stmt = $conn->prepare($recent_sql);
$recent_stmt->bind_param("s", $enrollment);
$recent_stmt->execute();
$recent_grades = $recent_stmt->get_result();
$recent_stmt->close();

// -----------------------------------------------------------------------------
// 9. FETCH DISTINCT SUBJECTS FOR THE SUBJECT FILTER DROPDOWN
// -----------------------------------------------------------------------------
$subject_list_stmt = $conn->prepare("SELECT DISTINCT subject_name FROM student_submissions WHERE student_id = ? ORDER BY subject_name");
$subject_list_stmt->bind_param("s", $enrollment);
$subject_list_stmt->execute();
$subject_list_result = $subject_list_stmt->get_result();
$subject_list = [];
while ($row = $subject_list_result->fetch_assoc()) {
    $subject_list[] = $row['subject_name'];
}
$subject_list_stmt->close();

// -----------------------------------------------------------------------------
// 10. DETERMINE THE FILTER BUTTON ACTIVE STATE (for the UI)
// -----------------------------------------------------------------------------
function isFilterActive($status, $current) {
    return ($status === $current) ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions – Detailed View | KDP</title>

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ============================================================
           PREMIUM STYLING (same as original but extended)
           ============================================================ */
        :root {
            --sidebar-width: 270px;
            --primary: #4338ca;
            --primary-hover: #3730a3;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --shadow-float: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.01);
            --radius-xl: 16px;
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            height: 100vh;
            overflow: hidden;
            margin: 0;
            color: var(--text-main);
        }

        /* SIDEBAR (same) */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            z-index: 10;
            overflow-y: auto;
            box-shadow: 4px 0 24px rgba(0,0,0,0.08);
        }
        .sidebar-logo-container {
            padding: 35px 20px 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }
        .sidebar-logo-container img {
            width: 85px;
            height: 85px;
            margin-bottom: 15px;
            border-radius: 50%;
            padding: 4px;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.4);
        }
        .sidebar-title h2 {
            font-size: 19px;
            font-weight: 800;
            margin: 0;
            letter-spacing: 0.5px;
            color: #ffffff;
        }
        .sidebar-subtitle {
            font-size: 12px;
            color: #bfdbfe;
            margin-top: 5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .nav-links {
            list-style: none;
            padding: 25px 15px;
            margin: 0;
            flex-grow: 1;
        }
        .nav-links li {
            padding: 13px 20px;
            margin: 8px 0;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 14.5px;
            font-weight: 600;
            color: #dbeafe;
            transition: var(--transition-bounce);
            border-left: 3px solid transparent;
        }
        .nav-links li:hover {
            color: #ffffff;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .nav-links li.active {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border-left: 4px solid #ffffff;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .nav-links li i {
            font-size: 18px;
        }
        .nav-links li.mt-auto {
            color: #fca5a5 !important;
        }

        /* MAIN CONTENT */
        .main {
            flex: 1;
            padding: 30px 45px;
            overflow-y: auto;
            height: 100vh;
            animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes fadeUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* TOPBAR */
        .topbar {
            padding: 0 0 15px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .clock-badge {
            background: var(--surface);
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 18px;
            color: #475569;
            font-weight: 700;
            font-size: 13px;
            box-shadow: var(--shadow-float);
        }
        .security-badge {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 10px;
            padding: 10px 18px;
            font-weight: 700;
            font-size: 13px;
        }
        .profile-pill {
            display: flex;
            align-items: center;
            background-color: var(--surface);
            padding: 8px 18px 8px 24px;
            border-radius: 50px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            transition: var(--transition-bounce);
            box-shadow: var(--shadow-float);
        }
        .profile-pill:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1);
            border-color: #cbd5e1;
        }
        .profile-text {
            text-align: right;
            margin-right: 18px;
        }
        .profile-welcome {
            display: block;
            font-size: 10px;
            color: var(--primary);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }
        .profile-name {
            margin: 0;
            font-size: 15px;
            color: var(--text-main);
            font-weight: 800;
        }
        .profile-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #4f46e5, #3730a3);
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
            letter-spacing: 1px;
        }

        /* COMMON BOXES */
        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }
        .blue-box {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }
        .content-box {
            background: var(--surface);
            border-radius: var(--radius-xl);
            padding: 25px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            transition: var(--transition-bounce);
            margin-bottom: 20px;
        }
        .box-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        /* TIMELINE */
        .timeline {
            position: relative;
            width: 100%;
            padding-left: 35px;
            margin-top: 10px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #e2e8f0;
            border-radius: 4px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }
        .timeline-icon {
            position: absolute;
            left: -36.5px;
            top: 0;
            width: 22px;
            height: 22px;
            background: white;
            border: 5px solid var(--primary);
            border-radius: 50%;
            box-shadow: 0 0 0 4px rgba(67, 56, 202, 0.1);
            z-index: 1;
            transition: var(--transition-bounce);
        }
        .timeline-item:hover .timeline-icon {
            transform: scale(1.2);
            box-shadow: 0 0 0 5px rgba(67, 56, 202, 0.2);
        }
        .timeline-content {
            background: var(--surface);
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 12px;
            padding: 20px 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.01);
            transition: var(--transition-bounce);
        }
        .timeline-content:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-float);
            border-color: #cbd5e1;
        }
        .timeline-date {
            font-size: 11.5px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            margin-bottom: 8px;
            display: block;
            letter-spacing: 0.5px;
        }
        .timeline-title {
            font-size: 17px;
            font-weight: 800;
            color: var(--text-main);
            margin: 0 0 6px 0;
        }
        .timeline-desc {
            font-size: 13.5px;
            color: #475569;
            margin: 0 0 15px 0;
            line-height: 1.5;
            font-weight: 500;
        }

        /* STATUS BADGES & FILTER BUTTONS */
        .filter-btn {
            background: white;
            border: 1px solid #cbd5e1;
            color: #64748b;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
            margin-right: 8px;
            margin-bottom: 10px;
        }
        .filter-btn.active,
        .filter-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 10px rgba(67, 56, 202, 0.2);
        }
        .badge-status {
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            letter-spacing: 0.5px;
        }
        .status-Pending {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        .status-Approved {
            background: #d1fae5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .status-Rejected {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-view {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: var(--transition-bounce);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-view:hover {
            background: #2563eb;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
        }

        /* STAT MINI BOXES */
        .stat-mini-box {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            margin-bottom: 12px;
            transition: 0.2s;
        }
        .stat-mini-box:hover {
            background: white;
            border-color: #cbd5e1;
            transform: translateX(5px);
        }
        .stat-icon-mini {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* GRADE ITEM (recent evaluations) */
        .grade-item {
            padding: 12px 15px;
            border-left: 3px solid var(--primary);
            background: #f8fafc;
            border-radius: 0 8px 8px 0;
            margin-bottom: 10px;
            transition: 0.2s;
            border-top: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }
        .grade-item:hover {
            background: white;
            border-left-width: 5px;
        }

        /* PAGINATION */
        .pagination .page-link {
            color: var(--primary);
            font-weight: 600;
        }
        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        /* MODAL CUSTOM */
        .modal-content {
            border-radius: var(--radius-xl);
            border: none;
        }
        .modal-header {
            border-bottom: 1px solid #e2e8f0;
        }
        .modal-footer {
            border-top: 1px solid #e2e8f0;
        }

        /* SEARCH & FILTER BAR */
        .search-filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }
        .search-filter-bar .form-control,
        .search-filter-bar .form-select {
            border-radius: 30px;
            border: 1px solid #cbd5e1;
            font-weight: 500;
            padding: 8px 18px;
        }
        .search-filter-bar .btn-outline-primary {
            border-radius: 30px;
            font-weight: 700;
        }
        .search-filter-bar .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
        }

        /* SCROLLBAR */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>
</head>
<body>

    <!-- ============================================================
    SIDEBAR (unchanged)
    ============================================================ -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="Logo">
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

    <!-- ============================================================
    MAIN CONTENT AREA
    ============================================================ -->
    <div class="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge">
                    <i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span>
                </div>
                <div class="security-badge d-none d-md-flex align-items-center">
                    <i class="fas fa-shield-check me-2"></i> Secure Session Verified
                </div>
            </div>
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Logged in as</span>
                    <h4 class="profile-name">
                        <?php
                        echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : $student_name;
                        ?>
                    </h4>
                    <span style="font-size:11px; font-weight:700; color:var(--primary);"><?php echo htmlspecialchars($enrollment); ?></span>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </a>
        </div>

        <!-- PAGE HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4 mt-2 page-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box blue-box"><i class="fas fa-history"></i></div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);">Activity & History</h3>
                    <p class="text-muted fw-semibold small mb-0">
                        Track your submission timeline, view faculty feedback, and monitor your progress.
                    </p>
                </div>
            </div>
            <!-- Quick stats summary -->
            <div class="text-end d-none d-md-block">
                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold p-2 px-3">
                    <i class="fas fa-file-alt me-1"></i> Total: <?php echo $total_sub; ?>
                </span>
            </div>
        </div>

        <!-- ============================================================
        SEARCH & FILTER BAR
        ============================================================ -->
        <div class="search-filter-bar">
            <form method="GET" action="" class="d-flex flex-wrap gap-2 w-100">
                <!-- Hidden fields to preserve pagination -->
                <input type="hidden" name="page" value="1">

                <!-- Search Input -->
                <div class="flex-grow-1" style="min-width: 180px;">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="🔍 Search practical / subject..."
                           value="<?php echo htmlspecialchars($search_term); ?>">
                </div>

                <!-- Status Filter Dropdown (optional, but we keep buttons as well) -->
                <div style="min-width: 140px;">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="All" <?php echo $status_filter == 'All' ? 'selected' : ''; ?>>All Status</option>
                        <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>

                <!-- Subject Filter Dropdown -->
                <div style="min-width: 160px;">
                    <select name="subject" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Subjects</option>
                        <?php foreach ($subject_list as $sub): ?>
                            <option value="<?php echo htmlspecialchars($sub); ?>" <?php echo ($subject_filter == $sub) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sub); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-outline-primary btn-sm" style="border-radius:30px; font-weight:700;">
                    <i class="fas fa-filter"></i> Apply
                </button>
                <a href="history.php" class="btn btn-outline-secondary btn-sm" style="border-radius:30px; font-weight:700;">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </form>
        </div>

        <!-- ============================================================
        TWO-COLUMN LAYOUT
        ============================================================ -->
        <div class="row g-4">

            <!-- LEFT COLUMN: TIMELINE (with pagination) -->
            <div class="col-lg-8">

                <!-- Status Filter Buttons (visual quick filter) -->
                <div class="mb-3 d-flex flex-wrap">
                    <a href="?status=All&search=<?php echo urlencode($search_term); ?>&subject=<?php echo urlencode($subject_filter); ?>" class="filter-btn <?php echo isFilterActive('All', $status_filter); ?>">
                        <i class="fas fa-list me-1"></i> All
                    </a>
                    <a href="?status=Pending&search=<?php echo urlencode($search_term); ?>&subject=<?php echo urlencode($subject_filter); ?>" class="filter-btn text-warning border-warning <?php echo isFilterActive('Pending', $status_filter); ?>">
                        <i class="fas fa-clock me-1"></i> Pending
                    </a>
                    <a href="?status=Approved&search=<?php echo urlencode($search_term); ?>&subject=<?php echo urlencode($subject_filter); ?>" class="filter-btn text-success border-success <?php echo isFilterActive('Approved', $status_filter); ?>">
                        <i class="fas fa-check-circle me-1"></i> Approved
                    </a>
                    <a href="?status=Rejected&search=<?php echo urlencode($search_term); ?>&subject=<?php echo urlencode($subject_filter); ?>" class="filter-btn text-danger border-danger <?php echo isFilterActive('Rejected', $status_filter); ?>">
                        <i class="fas fa-times-circle me-1"></i> Rejected
                    </a>
                </div>

                <!-- TIMELINE SECTION -->
                <div class="timeline">
                    <?php if ($history_result && $history_result->num_rows > 0): ?>
                        <?php while ($row = $history_result->fetch_assoc()): ?>
                            <div class="timeline-item" data-status="<?php echo htmlspecialchars($row['status']); ?>">
                                <!-- Timeline Dot -->
                                <div class="timeline-icon
                                    <?php
                                    if ($row['status'] == 'Approved') echo 'border-success';
                                    elseif ($row['status'] == 'Rejected') echo 'border-danger';
                                    else echo 'border-warning';
                                    ?>">
                                </div>

                                <!-- Content Card -->
                                <div class="timeline-content">
                                    <span class="timeline-date">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?>
                                        <?php if (!empty($row['updated_at']) && $row['updated_at'] != $row['submitted_at']): ?>
                                            <span class="text-muted ms-2" style="font-weight:400; text-transform:none;">
                                                (updated <?php echo date('d M Y, h:i A', strtotime($row['updated_at'])); ?>)
                                            </span>
                                        <?php endif; ?>
                                    </span>

                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                        <div class="flex-grow-1">
                                            <h3 class="timeline-title">
                                                Submitted <?php echo htmlspecialchars($row['practical_no']); ?>
                                                <span class="badge bg-light text-dark ms-2 fw-bold" style="font-size:11px;">
                                                    <i class="far fa-file-pdf"></i> <?php echo htmlspecialchars($row['subject_name']); ?>
                                                </span>
                                            </h3>
                                            <p class="timeline-desc">
                                                <strong>File:</strong> <?php echo basename($row['file_path']); ?>
                                                <?php if (!empty($row['file_size'])): ?>
                                                    <span class="text-muted">(<?php echo number_format($row['file_size'] / 1024, 1); ?> KB)</span>
                                                <?php endif; ?>
                                            </p>

                                            <!-- Status Badge -->
                                            <span class="badge-status status-<?php echo htmlspecialchars($row['status']); ?>">
                                                <?php
                                                if ($row['status'] == 'Pending') echo '<i class="fas fa-spinner fa-spin"></i> Under Review';
                                                elseif ($row['status'] == 'Approved') echo '<i class="fas fa-check-circle"></i> Approved';
                                                else echo '<i class="fas fa-times-circle"></i> Needs Revision';
                                                ?>
                                            </span>

                                            <!-- Marks if Approved -->
                                            <?php if ($row['status'] == 'Approved' && !empty($row['marks'])): ?>
                                                <span class="ms-2 badge bg-dark" style="font-size: 11px; padding: 7px 14px; border-radius: 20px;">
                                                    <i class="fas fa-star text-warning me-1"></i> Marks: <?php echo htmlspecialchars($row['marks']); ?> / 20
                                                </span>
                                            <?php endif; ?>

                                            <!-- Evaluator name (if any) -->
                                            <?php if (!empty($row['evaluator_name'])): ?>
                                                <span class="ms-2 text-muted small fw-semibold">
                                                    <i class="fas fa-user-check me-1"></i> Evaluated by: <?php echo htmlspecialchars($row['evaluator_name']); ?>
                                                </span>
                                            <?php endif; ?>

                                            <!-- Show feedback/remarks if available -->
                                            <?php if (!empty($row['remarks'])): ?>
                                                <div class="mt-2 p-2 bg-light rounded-3 border" style="font-size:13px; font-weight:500; color:#334155;">
                                                    <i class="fas fa-comment-dots text-primary me-1"></i>
                                                    <strong>Feedback:</strong> <?php echo nl2br(htmlspecialchars($row['remarks'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="d-flex flex-column align-items-end gap-2">
                                            <?php
                                            // Fix file path
                                            $raw_path = $row['file_path'];
                                            $pos = strpos($raw_path, 'uploads/');
                                            $safe_pdf_path = ($pos !== false) ? '../' . substr($raw_path, $pos) : '../' . $raw_path;
                                            ?>
                                            <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" target="_blank" class="btn-view shadow-sm">
                                                <i class="fas fa-file-pdf text-danger"></i> View PDF
                                            </a>
                                            <!-- View Details Button (triggers modal) -->
                                            <button class="btn-view" style="background:rgba(139,92,246,0.1); color:#7c3aed;"
                                                    data-bs-toggle="modal" data-bs-target="#detailModal"
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-practical="<?php echo htmlspecialchars($row['practical_no']); ?>"
                                                    data-subject="<?php echo htmlspecialchars($row['subject_name']); ?>"
                                                    data-status="<?php echo htmlspecialchars($row['status']); ?>"
                                                    data-marks="<?php echo htmlspecialchars($row['marks']); ?>"
                                                    data-submitted="<?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?>"
                                                    data-updated="<?php echo !empty($row['updated_at']) ? date('d M Y, h:i A', strtotime($row['updated_at'])) : ''; ?>"
                                                    data-evaluator="<?php echo htmlspecialchars($row['evaluator_name']); ?>"
                                                    data-remarks="<?php echo htmlspecialchars($row['remarks']); ?>"
                                                    data-file="<?php echo htmlspecialchars($safe_pdf_path); ?>">
                                                <i class="fas fa-info-circle"></i> Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="text-center text-muted py-5" style="background: white; border: 1px dashed #cbd5e1; border-radius: var(--radius-xl); margin-left: 20px;">
                            <i class="fas fa-history mb-3" style="font-size: 50px; opacity: 0.3; color: var(--primary);"></i>
                            <h5 class="fw-bold text-dark">No Timeline Activity</h5>
                            <p class="mb-0 fw-semibold small">
                                <?php if (!empty($search_term) || $status_filter !== 'All' || !empty($subject_filter)): ?>
                                    No submissions match your current filters. Try resetting them.
                                <?php else: ?>
                                    You haven't submitted any practicals yet. Head over to Course Manuals to start uploading.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_term); ?>&subject=<?php echo urlencode($subject_filter); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_term); ?>&subject=<?php echo urlencode($subject_filter); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_term); ?>&subject=<?php echo urlencode($subject_filter); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>

            </div> <!-- end col-lg-8 -->

            <!-- RIGHT COLUMN: WIDGETS (Stats, Recent Evaluations) -->
            <div class="col-lg-4">

                <!-- WIDGET 1: SUBMISSION ANALYTICS (Enhanced) -->
                <div class="content-box">
                    <h5 class="box-title"><i class="fas fa-chart-pie text-primary me-2"></i> Submission Analytics</h5>

                    <div class="stat-mini-box">
                        <div class="stat-icon-mini" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-cloud-upload-alt"></i></div>
                        <div>
                            <h3 class="fw-bold text-dark mb-0" style="font-size: 20px; line-height: 1;"><?php echo $total_sub; ?></h3>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">Total Uploads</small>
                        </div>
                    </div>

                    <div class="stat-mini-box">
                        <div class="stat-icon-mini" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <h3 class="fw-bold text-dark mb-0" style="font-size: 20px; line-height: 1;"><?php echo $approved; ?></h3>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">Approved</small>
                        </div>
                    </div>

                    <div class="stat-mini-box">
                        <div class="stat-icon-mini" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-clock"></i></div>
                        <div>
                            <h3 class="fw-bold text-dark mb-0" style="font-size: 20px; line-height: 1;"><?php echo $pending; ?></h3>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">Pending Review</small>
                        </div>
                    </div>

                    <?php if ($rejected > 0): ?>
                        <div class="stat-mini-box">
                            <div class="stat-icon-mini" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-times-circle"></i></div>
                            <div>
                                <h3 class="fw-bold text-danger mb-0" style="font-size: 20px; line-height: 1;"><?php echo $rejected; ?></h3>
                                <small class="text-danger fw-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">Needs Revision</small>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Average Marks -->
                    <div class="stat-mini-box" style="background: #f1f5f9; border-color: #cbd5e1;">
                        <div class="stat-icon-mini" style="background: rgba(139,92,246,0.1); color: #7c3aed;"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <h3 class="fw-bold text-dark mb-0" style="font-size: 20px; line-height: 1;"><?php echo $avg_marks; ?></h3>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 10.5px; letter-spacing: 0.5px;">Average Marks (Approved)</small>
                        </div>
                    </div>
                </div>

                <!-- WIDGET 2: RECENT FEEDBACK/GRADES (Enhanced) -->
                <div class="content-box">
                    <h5 class="box-title"><i class="fas fa-award text-warning me-2"></i> Recent Evaluations</h5>

                    <?php if ($recent_grades && $recent_grades->num_rows > 0): ?>
                        <?php while ($grade = $recent_grades->fetch_assoc()): ?>
                            <div class="grade-item <?php echo $grade['status'] == 'Approved' ? 'border-success' : 'border-danger'; ?>">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold text-dark mb-0" style="font-size: 13.5px;"><?php echo htmlspecialchars($grade['subject_name']); ?></h6>
                                    <?php if ($grade['status'] == 'Approved' && !empty($grade['marks'])): ?>
                                        <span class="badge bg-success fw-bold"><i class="fas fa-check me-1"></i> <?php echo $grade['marks']; ?>/20</span>
                                    <?php elseif ($grade['status'] == 'Rejected'): ?>
                                        <span class="badge bg-danger fw-bold"><i class="fas fa-redo me-1"></i> Redo</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted fw-semibold" style="font-size: 11px;">
                                    <?php echo htmlspecialchars($grade['practical_no']); ?>
                                    <span class="ms-2">• <?php echo date('d M Y', strtotime($grade['submitted_at'])); ?></span>
                                </small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-clipboard-check text-muted mb-2" style="font-size: 28px; opacity: 0.5;"></i>
                            <p class="text-muted small fw-semibold mb-0">No evaluated practicals to show yet.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div> <!-- end col-lg-4 -->

        </div> <!-- end row -->

    </div> <!-- end .main -->

    <!-- ============================================================
    DETAIL MODAL (popup with full submission info)
    ============================================================ -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="detailModalLabel">
                        <i class="fas fa-file-alt text-primary me-2"></i> Submission Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailModalBody">
                    <!-- Dynamic content will be injected via JavaScript -->
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Practical:</strong> <span id="modalPractical"></span></p>
                            <p><strong>Subject:</strong> <span id="modalSubject"></span></p>
                            <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                            <p><strong>Marks:</strong> <span id="modalMarks"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Submitted:</strong> <span id="modalSubmitted"></span></p>
                            <p><strong>Last Updated:</strong> <span id="modalUpdated"></span></p>
                            <p><strong>Evaluator:</strong> <span id="modalEvaluator"></span></p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p><strong>Feedback / Remarks:</strong></p>
                        <div id="modalRemarks" class="p-3 bg-light rounded-3" style="font-weight:500; min-height:50px;"></div>
                    </div>
                    <div class="mt-3">
                        <a id="modalFileLink" href="#" target="_blank" class="btn btn-primary">
                            <i class="fas fa-file-pdf"></i> Open PDF
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
    SCRIPTS
    ============================================================ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ------------------------------------------------------------
        // 1. LIVE CLOCK
        // ------------------------------------------------------------
        function updateClock() {
            const now = new Date();
            const options = {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // ------------------------------------------------------------
        // 2. MODAL DATA POPULATION
        // ------------------------------------------------------------
        const detailModal = document.getElementById('detailModal');
        detailModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // Button that triggered the modal
            // Extract data from data-* attributes
            const id = button.getAttribute('data-id');
            const practical = button.getAttribute('data-practical');
            const subject = button.getAttribute('data-subject');
            const status = button.getAttribute('data-status');
            const marks = button.getAttribute('data-marks') || 'N/A';
            const submitted = button.getAttribute('data-submitted');
            const updated = button.getAttribute('data-updated') || 'Not updated';
            const evaluator = button.getAttribute('data-evaluator') || 'Not assigned';
            const remarks = button.getAttribute('data-remarks') || 'No feedback provided.';
            const file = button.getAttribute('data-file');

            // Update modal fields
            document.getElementById('modalPractical').textContent = practical;
            document.getElementById('modalSubject').textContent = subject;
            document.getElementById('modalStatus').innerHTML = `<span class="badge-status status-${status}">${status}</span>`;
            document.getElementById('modalMarks').textContent = marks;
            document.getElementById('modalSubmitted').textContent = submitted;
            document.getElementById('modalUpdated').textContent = updated;
            document.getElementById('modalEvaluator').textContent = evaluator;
            document.getElementById('modalRemarks').innerHTML = remarks.replace(/\n/g, '<br>');
            document.getElementById('modalFileLink').href = file;
        });
    </script>
</body>
</html>