<?php
ob_start();
session_start();
include '../db.php';

// 1. Faculty Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];

// 2. Fetch Logged-in Faculty Details
$faculty_query = $conn->query("SELECT name, designation, department FROM users WHERE user_id = '$faculty_id'");
$faculty_data = $faculty_query->fetch_assoc();
$faculty_name = $faculty_data['name'];
$designation = !empty($faculty_data['designation']) ? $faculty_data['designation'] : 'Faculty Member';

// ==========================================
// FETCH DYNAMIC STATISTICS (Robust Mapping)
// ==========================================

$safe_id = strtolower($conn->real_escape_string($faculty_id));
$safe_name = strtolower($conn->real_escape_string($faculty_name));

// 1. Assigned Subjects Count (Case-insensitive check in main subjects table)
$sub_query = $conn->query("SELECT COUNT(*) as c FROM subjects WHERE (LOWER(faculty_name) LIKE '%$safe_id%' OR LOWER(faculty_name) LIKE '%$safe_name%') AND status='Active'");
$assigned_subjects = $sub_query ? $sub_query->fetch_assoc()['c'] : 0;

// 2. Uploaded Manuals Count 
$manuals_query = $conn->query("SELECT COUNT(*) as c FROM lab_manuals WHERE faculty_id = '$faculty_id'");
$uploaded_manuals = $manuals_query ? $manuals_query->fetch_assoc()['c'] : 0;

// 3. Pending & Reviewed Submissions
$pending_query = $conn->query("SELECT COUNT(*) as c FROM student_submissions ss JOIN lab_manuals lm ON ss.manual_id = lm.id WHERE lm.faculty_id = '$faculty_id' AND ss.status = 'Submitted'");
$pending_reviews = $pending_query ? $pending_query->fetch_assoc()['c'] : 0;

$reviewed_query = $conn->query("SELECT COUNT(*) as c FROM student_submissions ss JOIN lab_manuals lm ON ss.manual_id = lm.id WHERE lm.faculty_id = '$faculty_id' AND ss.status IN ('Graded', 'Resubmit')");
$reviewed_submissions = $reviewed_query ? $reviewed_query->fetch_assoc()['c'] : 0;

// Progress Calculation
$total_submissions = $pending_reviews + $reviewed_submissions;
$progress_percentage = ($total_submissions > 0) ? round(($reviewed_submissions / $total_submissions) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - K.D. Polytechnic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-width: 270px; --primary: #4338ca; --bg-body: #f8fafc; --surface: #ffffff; --text-main: #0f172a; --text-muted: #64748b; --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        * { box-sizing: border-box; }
        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        .sidebar { width: var(--sidebar-width); min-width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}

        .main { flex: 1; padding: 30px 45px; overflow-y: scroll; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

        .profile-pill { display: flex; align-items: center; background: linear-gradient(135deg, #1e3a8a, #4338ca); padding: 12px 25px 12px 30px; border-radius: 50px; cursor: pointer; text-decoration: none; color: white; transition: var(--transition-bounce); box-shadow: 0 10px 20px rgba(67, 56, 202, 0.2); border: 2px solid rgba(255,255,255,0.1);}
        .profile-pill:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(67, 56, 202, 0.3); border-color: rgba(255,255,255,0.3);}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: #bfdbfe; font-weight: 800; text-transform: uppercase; margin-bottom: 2px; letter-spacing: 0.5px;}
        .profile-name { margin: 0; font-size: 17px; color: white; font-weight: 900;}
        .profile-avatar { background: white; color: var(--primary); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);}

        .welcome-banner { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border-radius: 16px; padding: 35px 40px; color: white; margin-bottom: 25px; box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15); position: relative; overflow: hidden;}
        .welcome-banner::after { content: '\f51c'; font-family: 'Font Awesome 5 Free'; font-weight: 900; position: absolute; right: 40px; top: -20px; font-size: 150px; opacity: 0.05; transform: rotate(-15deg); pointer-events: none;}
        .welcome-banner h2 { font-weight: 800; font-size: 28px; margin-bottom: 8px;}
        .welcome-banner p { font-size: 15px; font-weight: 500; margin: 0; opacity: 0.9;}

        .stat-card { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; transition: var(--transition-bounce);}
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.05); }
        .stat-info h4 { font-size: 32px; font-weight: 900; color: var(--text-main); margin: 0;}
        .stat-info span { font-size: 12px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 5px; letter-spacing: 0.5px;}
        .stat-icon { width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; }

        .progress-circle { position: relative; width: 60px; height: 60px; border-radius: 50%; background: conic-gradient(#10b981 <?php echo $progress_percentage; ?>%, #e2e8f0 0); display: flex; align-items: center; justify-content: center;}
        .progress-circle::after { content: ''; position: absolute; width: 46px; height: 46px; background: white; border-radius: 50%; }
        .progress-value { position: relative; z-index: 1; font-size: 12px; font-weight: 800; color: #10b981; }

        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
        .box-title { font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px;}
        
        .action-list a { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; background: var(--bg-body); border-radius: 12px; color: var(--text-main); text-decoration: none; font-weight: 700; margin-bottom: 12px; transition: var(--transition-bounce); border: 1px solid transparent;}
        .action-list a:hover { background: white; border-color: var(--primary); color: var(--primary); transform: translateX(5px); box-shadow: 0 4px 15px rgba(0,0,0,0.03);}
        .action-icon { width: 35px; height: 35px; background: rgba(67, 56, 202, 0.1); color: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 15px;}

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
        </div>
        <ul class="nav-links">
            <li class="active" onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='upload_manual.php'"><i class="fas fa-file-upload"></i> Upload Manuals</li>
            <li onclick="window.location.href='review_submissions.php'"><i class="fas fa-check-double"></i> Review Submissions</li>
            <li onclick="window.location.href='reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> My Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome"><?php echo htmlspecialchars($designation); ?></span>
                    <h4 class="profile-name"><?php echo htmlspecialchars($faculty_name); ?></h4>
                </div>
                <div class="profile-avatar"><i class="fas fa-user-tie"></i></div>
            </a>
        </div>

        <div class="welcome-banner">
            <h2>Welcome back, <?php echo explode(' ', trim($faculty_name))[0]; ?>!</h2>
            <p>Manage your assigned lab manuals and evaluate student practical submissions seamlessly.</p>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card border-start border-primary border-4">
                    <div class="stat-info"><span>Assigned Subjects</span><h4><?php echo $assigned_subjects; ?></h4></div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-book"></i></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card border-start border-info border-4">
                    <div class="stat-info"><span>Uploaded Manuals</span><h4><?php echo $uploaded_manuals; ?></h4></div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-file-pdf"></i></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card border-start border-warning border-4">
                    <div class="stat-info"><span>Pending Reviews</span><h4><?php echo $pending_reviews; ?></h4></div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card border-start border-success border-4">
                    <div class="stat-info"><span>Reviewed / Graded</span><h4><?php echo $reviewed_submissions; ?></h4></div>
                    <div class="progress-circle">
                        <span class="progress-value"><?php echo $progress_percentage; ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="content-box h-100">
                    <div class="box-title"><i class="fas fa-bolt text-warning"></i> Quick Actions</div>
                    <div class="action-list">
                        <a href="upload_manual.php">
                            <div class="d-flex align-items-center">
                                <div class="action-icon"><i class="fas fa-upload"></i></div>
                                <div>
                                    <div class="mb-0">Upload Lab Manual</div>
                                    <small class="text-muted fw-normal">Add new practicals for your subjects</small>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                        <a href="review_submissions.php">
                            <div class="d-flex align-items-center">
                                <div class="action-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-double"></i></div>
                                <div>
                                    <div class="mb-0">Review Submissions</div>
                                    <small class="text-muted fw-normal">Evaluate files and assign marks</small>
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="content-box h-100">
                    <div class="box-title d-flex justify-content-between">
                        <span><i class="fas fa-inbox text-primary"></i> Recent Student Submissions</span>
                        <a href="review_submissions.php" class="btn btn-sm btn-outline-primary fw-bold px-3">View All</a>
                    </div>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-folder-open fs-1 opacity-25 mb-3 d-block"></i>
                        <h6 class="fw-bold">No Recent Submissions</h6>
                        <small>When students submit practicals for your assigned subjects, they will appear here.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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