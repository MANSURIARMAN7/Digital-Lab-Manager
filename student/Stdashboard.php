<?php
ob_start();
session_start();
include '../db.php';

// 1. Student Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// 2. Fetch Logged-in Student Details
$user_query = $conn->query("SELECT name, department, semester, profile_photo FROM users WHERE user_id = '$student_id'");
$user_data = $user_query->fetch_assoc();

$full_name = $user_data['name'];
$branch = !empty($user_data['department']) ? $user_data['department'] : 'N/A';
$semester = !empty($user_data['semester']) ? $user_data['semester'] : '0';
$profile_pic = !empty($user_data['profile_photo']) ? "../" . $user_data['profile_photo'] : null;

// Calculate First Name & Surname for Profile Pill
$name_parts = explode(" ", trim($full_name));
$first_name = $name_parts[0];
$last_name = (count($name_parts) > 1) ? end($name_parts) : '';
$profile_display_name = $first_name . ' ' . $last_name;

// ==========================================
// FETCH DYNAMIC STATISTICS
// ==========================================

// 1. Total Manuals (Assigned to this Branch & Sem)
$manuals_query = $conn->query("SELECT COUNT(*) as c FROM lab_manuals WHERE department = '$branch' AND semester = '$semester'");
$total_manuals = $manuals_query ? $manuals_query->fetch_assoc()['c'] : 0;

// 2. Total Submitted by this Student
$subs_query = $conn->query("SELECT COUNT(DISTINCT manual_id) as c FROM student_submissions WHERE student_id = '$student_id'");
$submitted_manuals = $subs_query ? $subs_query->fetch_assoc()['c'] : 0;

// 3. Pending Submissions
$pending_manuals = $total_manuals - $submitted_manuals;
if($pending_manuals < 0) $pending_manuals = 0;

// 4. Reviewed / Graded
$graded_query = $conn->query("SELECT COUNT(*) as c FROM student_submissions WHERE student_id = '$student_id' AND status = 'Graded'");
$graded_manuals = $graded_query ? $graded_query->fetch_assoc()['c'] : 0;

// Progress
$progress_percentage = ($total_manuals > 0) ? round(($submitted_manuals / $total_manuals) * 100) : 0;

// ==========================================
// RECENT SUBMISSIONS & FEEDBACK
// ==========================================
$recent_subs = $conn->query("
    SELECT ss.*, lm.subject_code, lm.subject_name, lm.practical_no 
    FROM student_submissions ss 
    JOIN lab_manuals lm ON ss.manual_id = lm.id 
    WHERE ss.student_id = '$student_id' 
    ORDER BY ss.submitted_at DESC LIMIT 5
");

// ==========================================
// UPCOMING DEADLINES (Not submitted yet)
// ==========================================
$upcoming_deadlines = $conn->query("
    SELECT * FROM lab_manuals 
    WHERE department = '$branch' AND semester = '$semester' 
    AND id NOT IN (SELECT manual_id FROM student_submissions WHERE student_id = '$student_id')
    ORDER BY end_date ASC LIMIT 5
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - K.D. Polytechnic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-width: 270px; --primary: #4338ca; --bg-body: #f8fafc; --surface: #ffffff; --text-main: #0f172a; --text-muted: #64748b; --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        * { box-sizing: border-box; }
        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        /* UNIFORM KDP SIDEBAR */
        .sidebar { width: var(--sidebar-width); min-width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}

        /* MAIN CONTENT */
        .main { flex: 1; padding: 30px 45px; overflow-y: scroll; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* TOPBAR & PROFILE PILL */
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

        .profile-pill { display: flex; align-items: center; background: linear-gradient(135deg, #1e3a8a, #4338ca); padding: 12px 25px 12px 30px; border-radius: 50px; cursor: pointer; text-decoration: none; color: white; transition: var(--transition-bounce); box-shadow: 0 10px 20px rgba(67, 56, 202, 0.2); border: 2px solid rgba(255,255,255,0.1);}
        .profile-pill:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(67, 56, 202, 0.3); border-color: rgba(255,255,255,0.3);}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: #bfdbfe; font-weight: 800; text-transform: uppercase; margin-bottom: 2px; letter-spacing: 0.5px;}
        .profile-name { margin: 0; font-size: 16px; color: white; font-weight: 900;}
        .profile-avatar { background: white; color: var(--primary); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; overflow: hidden; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);}
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }

        /* WELCOME BANNER */
        .welcome-banner { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border-radius: 16px; padding: 35px 40px; color: white; margin-bottom: 25px; box-shadow: 0 10px 25px rgba(59, 130, 246, 0.15); position: relative; overflow: hidden;}
        .welcome-banner::after { content: '\f501'; font-family: 'Font Awesome 5 Free'; font-weight: 900; position: absolute; right: 40px; top: -20px; font-size: 150px; opacity: 0.05; transform: rotate(-15deg); pointer-events: none;}
        .welcome-banner h2 { font-weight: 800; font-size: 28px; margin-bottom: 8px;}
        .welcome-banner p { font-size: 15px; font-weight: 500; margin: 0; opacity: 0.9;}

        /* STATS CARDS */
        .stat-card { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; transition: var(--transition-bounce);}
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.05); }
        .stat-info h4 { font-size: 32px; font-weight: 900; color: var(--text-main); margin: 0;}
        .stat-info span { font-size: 12px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 5px; letter-spacing: 0.5px;}
        .stat-icon { width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; }

        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
        .box-title { font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px;}
        
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; }

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
            <li class="active" onclick="window.location.href='dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='submit_practical.php'"><i class="fas fa-file-upload"></i> Submit Practicals</li>
            <li onclick="window.location.href='my_history.php'"><i class="fas fa-history"></i> My History</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-graduate"></i> My Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Student • Sem <?php echo htmlspecialchars($semester); ?></span>
                    <h4 class="profile-name"><?php echo htmlspecialchars($profile_display_name); ?></h4>
                </div>
                <div class="profile-avatar">
                    <?php if($profile_pic): ?>
                        <img src="<?php echo $profile_pic; ?>" alt="Profile">
                    <?php else: ?>
                        <i class="fas fa-user-graduate"></i>
                    <?php endif; ?>
                </div>
            </a>
        </div>

        <div class="welcome-banner">
            <h2>Welcome back, <?php echo htmlspecialchars($full_name); ?>!</h2>
            <p>Enrollment: <?php echo htmlspecialchars($student_id); ?> | Branch: <?php echo htmlspecialchars($branch); ?></p>
        </div>

        <!-- OVERVIEW CARDS -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card border-start border-primary border-4">
                    <div class="stat-info"><span>Total Practicals</span><h4><?php echo $total_manuals; ?></h4></div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-book"></i></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card border-start border-info border-4">
                    <div class="stat-info"><span>Submitted</span><h4><?php echo $submitted_manuals; ?></h4></div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-upload"></i></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card border-start border-warning border-4">
                    <div class="stat-info"><span>Pending Submissions</span><h4><?php echo $pending_manuals; ?></h4></div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card border-start border-success border-4">
                    <div class="stat-info"><span>Graded By Faculty</span><h4><?php echo $graded_manuals; ?></h4></div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-double"></i></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- RECENT SUBMISSIONS & FEEDBACK -->
            <div class="col-lg-7">
                <div class="content-box h-100">
                    <div class="box-title d-flex justify-content-between">
                        <span><i class="fas fa-history text-primary"></i> Recent Submissions & Feedback</span>
                        <a href="my_history.php" class="btn btn-sm btn-outline-primary fw-bold px-3">View All</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Subject & Practical</th>
                                    <th>Status</th>
                                    <th>Marks</th>
                                    <th>Faculty Feedback</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($recent_subs && $recent_subs->num_rows > 0): ?>
                                    <?php while($sub = $recent_subs->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($sub['subject_code']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($sub['practical_no']); ?></div>
                                            </td>
                                            <td>
                                                <?php 
                                                    if($sub['status'] == 'Submitted') echo '<span class="badge bg-warning text-dark">Pending Review</span>';
                                                    elseif($sub['status'] == 'Graded') echo '<span class="badge bg-success">Graded</span>';
                                                    else echo '<span class="badge bg-danger">Resubmit</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <?php if($sub['status'] == 'Graded'): ?>
                                                    <span class="fw-bold text-success"><?php echo $sub['total_marks']; ?>/20</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if(!empty($sub['feedback'])): ?>
                                                    <span class="text-muted small fst-italic">"<?php echo htmlspecialchars($sub['feedback']); ?>"</span>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">You haven't submitted any practicals yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- UPCOMING DEADLINES -->
            <div class="col-lg-5">
                <div class="content-box h-100">
                    <div class="box-title"><i class="fas fa-exclamation-circle text-danger"></i> Pending & Upcoming Deadlines</div>
                    
                    <?php if($upcoming_deadlines && $upcoming_deadlines->num_rows > 0): ?>
                        <ul class="list-group list-group-flush">
                            <?php while($task = $upcoming_deadlines->fetch_assoc()): ?>
                                <?php 
                                    $deadline = strtotime($task['end_date']);
                                    $is_late = ($deadline < time());
                                ?>
                                <li class="list-group-item px-0 py-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($task['subject_code']); ?> - <?php echo htmlspecialchars($task['practical_no']); ?></h6>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($task['title']); ?></small>
                                            
                                            <?php if($is_late): ?>
                                                <small class="text-danger fw-bold"><i class="fas fa-times-circle"></i> Deadline Passed: <?php echo date('d M, h:i A', $deadline); ?></small>
                                            <?php else: ?>
                                                <small class="text-warning fw-bold text-dark"><i class="far fa-clock"></i> Due: <?php echo date('d M Y, h:i A', $deadline); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <a href="submit_practical.php" class="btn btn-sm <?php echo $is_late ? 'btn-danger' : 'btn-primary'; ?> fw-bold">Submit</a>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-glass-cheers fs-1 opacity-25 mb-3 d-block"></i>
                            <h6 class="fw-bold">All Caught Up!</h6>
                            <small>You have no pending assignments right now.</small>
                        </div>
                    <?php endif; ?>
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

