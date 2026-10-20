<?php
session_start();
include '../db.php';

// 1. SECURE LOGIN CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$enrollment = $conn->real_escape_string((string)$_SESSION['user_id']);

// 2. FETCH STUDENT DATA
$user_query = $conn->query("SELECT name, department, designation FROM users WHERE user_id = '$enrollment'");
$student_data = $user_query->fetch_assoc();
$student_name = $student_data['name'] ?? 'Student';
$branch = trim($student_data['department']) ?? 'Computer Engineering';
$raw_semester = trim($student_data['designation']) ?? '1';

preg_match('/\d+/', $raw_semester, $sem_matches);
$sem_num = $sem_matches[0] ?? '1';

$name_parts = explode(' ', trim($student_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) { $initials .= strtoupper(substr(end($name_parts), 0, 1)); }

// 3. STATS CALCULATION
$mapped_semester = "Semester " . $sem_num;
$total_manuals_query = $conn->query("SELECT COUNT(*) as cnt FROM lab_manuals WHERE department = '$branch' AND (semester = '$sem_num' OR semester = '$mapped_semester')");
$total_manuals = $total_manuals_query->fetch_assoc()['cnt'] ?? 0;

$stats_query = $conn->query("
    SELECT 
        COUNT(*) as submitted,
        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM student_submissions 
    WHERE student_id = '$enrollment'
");
$stats = $stats_query->fetch_assoc();

$pending_tasks = max(0, $total_manuals - ($stats['submitted'] ?? 0) + ($stats['rejected'] ?? 0));

// 4. RECENT ACTIVITY
$recent_subs = $conn->query("SELECT subject_name, practical_no, status, submitted_at FROM student_submissions WHERE student_id = '$enrollment' ORDER BY submitted_at DESC LIMIT 4");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - KDP Portal</title>
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

        .welcome-banner { background: linear-gradient(135deg, #2563eb, #4f46e5); border-radius: 16px; padding: 35px 40px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(67, 56, 202, 0.15); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;}
        .welcome-banner::before { content: '\f19d'; font-family: 'Font Awesome 5 Free'; font-weight: 900; position: absolute; right: 20px; top: -10px; font-size: 150px; color: rgba(255,255,255,0.05); }
        .welcome-text-area { position: relative; z-index: 2; color: white;}

        .stat-card-rad { background: var(--surface); border-radius: 16px; padding: 25px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.02); transition: var(--transition-bounce); cursor: pointer; position: relative; overflow: hidden;}
        .stat-card-rad:hover { transform: translateY(-5px); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.05); }
        .stat-card-rad::after { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; }
        .card-blue::after { background: #3b82f6; }
        .card-red::after { background: #ef4444; }
        .card-green::after { background: #10b981; }
        .stat-info h4 { font-size: 32px; font-weight: 900; color: var(--text-main); margin: 0; line-height: 1;}
        .stat-info span { font-size: 12px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; margin-bottom: 5px; display: block;}
        .stat-icon { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 26px; }

        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;}
        .status-Pending { background: #fef3c7; color: #d97706;}
        .status-Approved { background: #d1fae5; color: #059669;}
        .status-Rejected { background: #fee2e2; color: #dc2626;}

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Student Portal</div>
        </div>
        <ul class="nav-links">
            <li class="active" onclick="window.location.href='Stdashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='my-manuals.php'"><i class="fas fa-book-open"></i> Course Manuals</li>
            <li onclick="window.location.href='history.php'"><i class="fas fa-history"></i> My Submissions</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Logged in as,</span>
                    <h4 class="profile-name"><?php echo htmlspecialchars($name_parts[0]); ?></h4>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </a>
        </div>

        <div class="welcome-banner">
            <div class="welcome-text-area">
                <h2 class="fw-bold mb-2">Welcome, <?php echo htmlspecialchars($name_parts[0]); ?>! 👋</h2>
                <p class="mb-0 text-white-50 fw-medium">Branch: <?php echo htmlspecialchars($branch); ?> | Semester: <?php echo htmlspecialchars($sem_num); ?></p>
            </div>
            <button onclick="window.location.href='my-manuals.php'" class="btn btn-light fw-bold px-4 py-2 rounded-3 text-primary d-none d-md-block shadow-sm">
                <i class="fas fa-upload me-2"></i> Submit Practical
            </button>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card-rad card-blue" onclick="window.location.href='my-manuals.php'">
                    <div class="stat-info"><span>Total Assigned Manuals</span><h4><?php echo $total_manuals; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-book"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card-rad card-red" onclick="window.location.href='my-manuals.php'">
                    <div class="stat-info"><span>Pending Tasks</span><h4><?php echo $pending_tasks; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-exclamation-circle"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card-rad card-green" onclick="window.location.href='history.php'">
                    <div class="stat-info"><span>Approved Submissions</span><h4><?php echo $stats['approved'] ?? 0; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>

        <div class="content-box p-0 overflow-hidden">
            <div class="p-4 border-bottom bg-light">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-history text-primary me-2"></i> Recent Submissions</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless mb-0">
                    <thead class="border-bottom text-muted small fw-bold text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Subject</th>
                            <th class="py-3">Practical No</th>
                            <th class="py-3">Submitted At</th>
                            <th class="pe-4 py-3 text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_subs->num_rows > 0): ?>
                            <?php while($row = $recent_subs->fetch_assoc()): ?>
                                <tr class="border-bottom">
                                    <td class="ps-4 py-3 fw-bold text-dark"><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                    <td class="py-3 fw-semibold text-muted">Prac <?php echo htmlspecialchars($row['practical_no']); ?></td>
                                    <td class="py-3 fw-semibold text-muted"><?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?></td>
                                    <td class="pe-4 py-3 text-end">
                                        <span class="badge-modern status-<?php echo htmlspecialchars($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted fw-bold">No recent submissions found.</td></tr>
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