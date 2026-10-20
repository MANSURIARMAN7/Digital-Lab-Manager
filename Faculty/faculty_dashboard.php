<?php
session_start();
include '../db.php';

// 1. SECURE LOGIN CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_id = $_SESSION['user_id'];
$faculty_name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Faculty';

$name_parts = explode(' ', trim($faculty_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) { $initials .= strtoupper(substr(end($name_parts), 0, 1)); }

// 2. FETCH MAPPED SUBJECTS FOR THIS FACULTY
$fac_name_safe = $conn->real_escape_string($faculty_name);
$assigned_subjects = [];
$sub_query = $conn->query("SELECT DISTINCT subject_name FROM subjects WHERE faculty_name LIKE '%$fac_name_safe%'");
if ($sub_query && $sub_query->num_rows > 0) {
    while($r = $sub_query->fetch_assoc()) { $assigned_subjects[] = "'".$conn->real_escape_string($r['subject_name'])."'"; }
}
$in_clause = empty($assigned_subjects) ? "''" : implode(",", $assigned_subjects);

// 3. STATS CALCULATION
$manuals_q = $conn->query("SELECT COUNT(*) as cnt FROM lab_manuals WHERE subject_name IN ($in_clause)");
$tot_manuals = $manuals_q->fetch_assoc()['cnt'] ?? 0;

$subs_q = $conn->query("
    SELECT 
        COUNT(*) as total_subs,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_review,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as evaluated
    FROM student_submissions 
    WHERE subject_name IN ($in_clause)
");
$stats = $subs_q->fetch_assoc();

// 4. RECENT PENDING SUBMISSIONS
$recent_pending = $conn->query("
    SELECT s.subject_name, s.practical_no, s.submitted_at, u.name as student_name 
    FROM student_submissions s 
    JOIN users u ON s.student_id = u.user_id 
    WHERE s.subject_name IN ($in_clause) AND s.status = 'Pending' 
    ORDER BY s.submitted_at ASC LIMIT 4
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Dashboard - KDP Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { --sidebar-width: 270px; --primary: #4338ca; --bg-body: #f8fafc; --surface: #ffffff; --text-main: #0f172a; --text-muted: #64748b; --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 13px; color: #bfdbfe; margin-top: 5px; font-weight: 500; letter-spacing: 0.5px;} 
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}
        
        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 10px 22px 10px 28px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .profile-pill:hover { transform: translateY(-3px); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: var(--primary); font-weight: 800; text-transform: uppercase; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 17px; color: var(--text-main); font-weight: 900;}
        .profile-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.4);}

        .welcome-banner { background: linear-gradient(135deg, #0f766e, #14b8a6); border-radius: 16px; padding: 35px 40px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(20, 184, 166, 0.15); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;}
        .welcome-banner::before { content: '\f51c'; font-family: 'Font Awesome 5 Free'; font-weight: 900; position: absolute; right: 20px; top: -10px; font-size: 150px; color: rgba(255,255,255,0.05); }
        .welcome-text-area { position: relative; z-index: 2; color: white;}

        .stat-card-rad { background: var(--surface); border-radius: 16px; padding: 25px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.02); transition: var(--transition-bounce); cursor: pointer; position: relative; overflow: hidden;}
        .stat-card-rad:hover { transform: translateY(-5px); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.05); }
        .stat-card-rad::after { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; }
        .card-blue::after { background: #3b82f6; }
        .card-purple::after { background: #8b5cf6; }
        .card-yellow::after { background: #f59e0b; }
        .card-green::after { background: #10b981; }
        .stat-info h4 { font-size: 32px; font-weight: 900; color: var(--text-main); margin: 0; line-height: 1;}
        .stat-info span { font-size: 12px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; margin-bottom: 5px; display: block;}
        .stat-icon { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 26px; }

        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;}

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Faculty Portal</div>
        </div>
        <ul class="nav-links">
            <li class="active" onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='manage_manuals.php'"><i class="fas fa-file-pdf"></i> Upload Manuals</li>
            <li onclick="window.location.href='labmanual_list.php'"><i class="fas fa-check-double"></i> Review & Evaluate</li>
            <li onclick="window.location.href='reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Welcome Back,</span>
                    <h4 class="profile-name"><?php echo htmlspecialchars($name_parts[0]); ?></h4>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </a>
        </div>

        <div class="welcome-banner">
            <div class="welcome-text-area">
                <h2 class="fw-bold mb-2">Hello, <?php echo htmlspecialchars($faculty_name); ?>! 🎓</h2>
                <p class="mb-0 text-white-50 fw-medium">Manage your assigned subjects, upload manuals, and evaluate student progress.</p>
            </div>
            <button onclick="window.location.href='labmanual_list.php'" class="btn btn-light fw-bold px-4 py-2 rounded-3 text-success d-none d-md-block shadow-sm">
                <i class="fas fa-check-double me-2"></i> Evaluate Submissions
            </button>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card-rad card-blue" onclick="window.location.href='manage_manuals.php'">
                    <div class="stat-info"><span>Manuals Uploaded</span><h4><?php echo $tot_manuals; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-file-pdf"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-rad card-purple" onclick="window.location.href='labmanual_list.php'">
                    <div class="stat-info"><span>Total Submissions</span><h4><?php echo $stats['total_subs'] ?? 0; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;"><i class="fas fa-inbox"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-rad card-yellow" onclick="window.location.href='labmanual_list.php?status_filter=Pending'">
                    <div class="stat-info"><span>Pending Review</span><h4><?php echo $stats['pending_review'] ?? 0; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-rad card-green" onclick="window.location.href='labmanual_list.php?status_filter=Approved'">
                    <div class="stat-info"><span>Evaluated</span><h4><?php echo $stats['evaluated'] ?? 0; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>

        <div class="content-box p-0 overflow-hidden">
            <div class="p-4 border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-clipboard-list text-warning me-2"></i> Submissions Needing Review</h5>
                <a href="labmanual_list.php" class="btn btn-sm btn-outline-primary fw-bold px-3">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless mb-0">
                    <thead class="border-bottom text-muted small fw-bold text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Student Name</th>
                            <th class="py-3">Subject & Prac</th>
                            <th class="py-3">Submitted At</th>
                            <th class="pe-4 py-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_pending->num_rows > 0): ?>
                            <?php while($row = $recent_pending->fetch_assoc()): ?>
                                <tr class="border-bottom">
                                    <td class="ps-4 py-3 fw-bold text-dark"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                    <td class="py-3">
                                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                                        <div class="text-muted small fw-semibold">Prac <?php echo htmlspecialchars($row['practical_no']); ?></div>
                                    </td>
                                    <td class="py-3 fw-semibold text-muted"><i class="fas fa-clock text-warning me-1"></i> <?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?></td>
                                    <td class="pe-4 py-3 text-end">
                                        <a href="labmanual_list.php" class="btn btn-sm btn-success fw-bold px-3 rounded-pill shadow-sm">Grade Now</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted fw-bold"><i class="fas fa-coffee mb-2 fs-3 opacity-50"></i><br>All caught up! No pending reviews.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>