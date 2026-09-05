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
</html>