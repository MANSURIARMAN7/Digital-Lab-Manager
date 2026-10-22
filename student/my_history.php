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

// 2. Fetch Logged-in Student Details (SAFE METHOD)
$user_query = $conn->query("SELECT name, department, semester, profile_photo FROM users WHERE user_id = '$student_id'");
$user_data = ($user_query && $user_query->num_rows > 0) ? $user_query->fetch_assoc() : [];

$branch = !empty($user_data['department']) ? $user_data['department'] : 'N/A';
$semester = !empty($user_data['semester']) ? $user_data['semester'] : '0';
$profile_pic = !empty($user_data['profile_photo']) ? "../" . $user_data['profile_photo'] : null;

$name_parts = explode(" ", trim($user_data['name'] ?? 'Student'));
$profile_display_name = $name_parts[0] . ' ' . (count($name_parts) > 1 ? end($name_parts) : '');

// ==========================================
// CALCULATE PERFORMANCE SUMMARY (Only Graded)
// ==========================================
$graded_count = 0;
$total_earned_marks = 0;
$pending_count = 0;
$resubmit_count = 0;

$stats_q = $conn->query("SELECT status, total_marks FROM student_submissions WHERE student_id = '$student_id'");
if ($stats_q && $stats_q->num_rows > 0) {
    while ($row = $stats_q->fetch_assoc()) {
        if ($row['status'] == 'Graded') {
            $graded_count++;
            $total_earned_marks += (int)$row['total_marks'];
        } elseif ($row['status'] == 'Submitted') {
            $pending_count++;
        } elseif ($row['status'] == 'Resubmit') {
            $resubmit_count++;
        }
    }
}
$total_possible_marks = $graded_count * 20;

// ==========================================
// SEARCH, FILTER & SORTING LOGIC
// ==========================================
$f_subject = $_GET['subject_code'] ?? '';
$f_status = $_GET['status'] ?? '';
$f_search = $_GET['search'] ?? '';

$filter_sql = "";
if (!empty($f_subject)) {
    $safe_sub = $conn->real_escape_string($f_subject);
    $filter_sql .= " AND lm.subject_code = '$safe_sub' ";
}
if (!empty($f_status)) {
    $safe_status = $conn->real_escape_string($f_status);
    $filter_sql .= " AND ss.status = '$safe_status' ";
}
if (!empty($f_search)) {
    $safe_search = $conn->real_escape_string($f_search);
    $filter_sql .= " AND (lm.title LIKE '%$safe_search%' OR lm.practical_no LIKE '%$safe_search%') ";
}

// Fetch Assigned Subjects for Filter Dropdown
$subjects_query = $conn->query("SELECT DISTINCT subject_code, subject_name FROM lab_manuals WHERE department = '$branch' AND semester = '$semester'");

// ==========================================
// FETCH ATTEMPT HISTORY (MASTER LOGIC)
// ==========================================
// First, order by manual_id and submitted_at ASC to assign correct attempt numbers
$history_query = "
    SELECT ss.*, lm.subject_code, lm.practical_no, lm.title 
    FROM student_submissions ss 
    JOIN lab_manuals lm ON ss.manual_id = lm.id 
    WHERE ss.student_id = '$student_id' $filter_sql
    ORDER BY ss.manual_id ASC, ss.submitted_at ASC
";
$h_res = $conn->query($history_query);

$grouped_history = [];
$attempt_tracker = [];

if ($h_res && $h_res->num_rows > 0) {
    while ($row = $h_res->fetch_assoc()) {
        $mid = $row['manual_id'];
        
        if (!isset($attempt_tracker[$mid])) {
            $attempt_tracker[$mid] = 0;
        }
        $attempt_tracker[$mid]++;
        
        $row['attempt_no'] = $attempt_tracker[$mid];
        
        // Group by manual ID so we can sort them nicely (latest attempt at the top of the practical)
        if (!isset($grouped_history[$mid])) {
            $grouped_history[$mid] = [];
        }
        // Prepend so latest attempt is first in the array
        array_unshift($grouped_history[$mid], $row);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My History - Student Portal</title>
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

        /* STATS CARDS */
        .stat-card { background: var(--surface); border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center;}
        .stat-info h4 { font-size: 26px; font-weight: 900; color: var(--text-main); margin: 0;}
        .stat-info span { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 5px; letter-spacing: 0.5px;}
        .stat-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }

        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
        .form-select, .form-control { border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 600; padding: 10px 15px;}
        
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; }
        
        .row-latest { background-color: #ffffff; border-left: 3px solid var(--primary); }
        .row-old { background-color: #f8fafc; opacity: 0.85; border-left: 3px solid #cbd5e1; }
        .row-old td { font-weight: 500; }

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
            <li onclick="window.location.href='Stdashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='submit_practical.php'"><i class="fas fa-file-upload"></i> Submit Practicals</li>
            <li class="active" onclick="window.location.href='my_history.php'"><i class="fas fa-history"></i> My History</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-graduate"></i> My Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <!-- TOPBAR -->
        <div class="topbar mb-4">
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

        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1"><i class="fas fa-clipboard-list text-primary me-2"></i> Digital Performance Report</h3>
            <p class="text-muted fw-semibold small mb-0">Track your submission history, attempt versions, and faculty feedback.</p>
        </div>

        <!-- PERFORMANCE SUMMARY CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card border-start border-success border-4">
                    <div class="stat-info"><span>Graded Practicals</span><h4><?php echo $graded_count; ?></h4></div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-double"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-start border-primary border-4">
                    <div class="stat-info"><span>Marks Earned</span><h4><span class="text-primary"><?php echo $total_earned_marks; ?></span> <span class="fs-6 text-muted">/ <?php echo $total_possible_marks; ?></span></h4></div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-award"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-start border-warning border-4">
                    <div class="stat-info"><span>Pending Reviews</span><h4><?php echo $pending_count; ?></h4></div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-start border-danger border-4">
                    <div class="stat-info"><span>Resubmit Required</span><h4><?php echo $resubmit_count; ?></h4></div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="fas fa-undo"></i></div>
                </div>
            </div>
        </div>

        <!-- SEARCH, FILTER & SORTING -->
        <div class="content-box bg-light">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted text-uppercase">Subject</label>
                    <select name="subject_code" class="form-select">
                        <option value="">All Subjects</option>
                        <?php if($subjects_query && $subjects_query->num_rows > 0): ?>
                            <?php while($sub = $subjects_query->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($sub['subject_code']); ?>" <?php echo ($f_subject == $sub['subject_code']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sub['subject_code'] . " - " . $sub['subject_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Submitted" <?php echo ($f_status == 'Submitted') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Graded" <?php echo ($f_status == 'Graded') ? 'selected' : ''; ?>>Graded</option>
                        <option value="Resubmit" <?php echo ($f_status == 'Resubmit') ? 'selected' : ''; ?>>Resubmit</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Search Title</label>
                    <input type="text" name="search" class="form-control" placeholder="Search practical..." value="<?php echo htmlspecialchars($f_search); ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100"><i class="fas fa-filter"></i> Apply</button>
                    <a href="my_history.php" class="btn btn-secondary fw-bold"><i class="fas fa-sync-alt"></i></a>
                </div>
            </form>
        </div>

        <!-- ATTEMPT HISTORY TABLE -->
        <div class="content-box">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Practical / Date</th>
                            <th>Attempt</th>
                            <th>Status</th>
                            <th>Marks</th>
                            <th>Faculty Feedback</th>
                            <th class="text-end">File</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($grouped_history)): ?>
                            <?php foreach ($grouped_history as $manual_id => $attempts): ?>
                                <?php 
                                    // $attempts array is already sorted so index 0 is the LATEST attempt
                                    foreach ($attempts as $index => $row): 
                                        $is_latest = ($index === 0);
                                        $row_class = $is_latest ? 'row-latest' : 'row-old';
                                ?>
                                    <tr class="<?php echo $row_class; ?>">
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['subject_code']); ?> - <?php echo htmlspecialchars($row['practical_no']); ?></div>
                                            <div class="small text-muted mb-1"><?php echo htmlspecialchars($row['title']); ?></div>
                                            <div class="small fw-semibold" style="color: #0284c7;"><i class="far fa-calendar-alt"></i> <?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $is_latest ? 'bg-primary' : 'bg-secondary'; ?>">Attempt <?php echo $row['attempt_no']; ?></span>
                                            <?php if($is_latest && count($attempts) > 1): ?>
                                                <div class="small text-muted mt-1 fw-bold">(Current)</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                                if($row['status'] == 'Submitted') echo '<span class="badge bg-warning text-dark">Pending</span>';
                                                elseif($row['status'] == 'Graded') echo '<span class="badge bg-success">Graded</span>';
                                                else echo '<span class="badge bg-danger">Resubmit</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if($row['status'] == 'Graded'): ?>
                                                <span class="fw-bold text-success"><?php echo $row['total_marks']; ?>/20</span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(!empty($row['feedback'])): ?>
                                                <span class="text-dark small fst-italic">"<?php echo htmlspecialchars($row['feedback']); ?>"</span>
                                            <?php else: ?>
                                                <!-- Addressed: Clear display when no feedback is available -->
                                                <span class="text-muted small">No feedback available yet.</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="../<?php echo $row['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-bold"><i class="fas fa-file-pdf me-1"></i> View PDF</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted fw-bold"><i class="fas fa-inbox fs-1 opacity-25 mb-3 d-block"></i> No submission history found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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