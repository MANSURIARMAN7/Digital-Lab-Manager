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
$fac_query = $conn->query("SELECT name, designation FROM users WHERE user_id = '$faculty_id'");
$fac_data = $fac_query->fetch_assoc();
$faculty_name = $fac_data['name'];
$designation = !empty($fac_data['designation']) ? $fac_data['designation'] : 'Faculty Member';

$safe_id = strtolower($conn->real_escape_string($faculty_id));
$safe_name = strtolower($conn->real_escape_string($faculty_name));

// ==========================================
// FILTERS LOGIC (Date filters removed for clean UI)
// ==========================================
$f_subject = $_GET['subject'] ?? '';
$f_semester = $_GET['semester'] ?? '';

// Build Filter Queries
$filter_manuals = " WHERE faculty_id = '$faculty_id'";
$filter_subs = " WHERE lm.faculty_id = '$faculty_id'";

if (!empty($f_subject)) {
    $safe_sub = $conn->real_escape_string($f_subject);
    $filter_manuals .= " AND subject_code = '$safe_sub'";
    $filter_subs .= " AND lm.subject_code = '$safe_sub'";
}
if (!empty($f_semester)) {
    $safe_sem = $conn->real_escape_string($f_semester);
    $filter_manuals .= " AND semester = '$safe_sem'";
    $filter_subs .= " AND lm.semester = '$safe_sem'";
}

// ==========================================
// EXPORT TO CSV LOGIC
// ==========================================
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Faculty_Report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, array('Student Name', 'Enrollment No', 'Subject', 'Practical', 'Submission Date', 'Status', 'Total Marks (/20)'));
    
    // Fetch Data
    $export_query = $conn->query("
        SELECT u.name as student_name, u.user_id as enrollment_no, lm.subject_name, lm.practical_no, ss.submitted_at, ss.status, ss.total_marks 
        FROM student_submissions ss 
        JOIN lab_manuals lm ON ss.manual_id = lm.id 
        JOIN users u ON ss.student_id = u.user_id 
        $filter_subs 
        ORDER BY ss.submitted_at DESC
    ");
    
    while ($row = $export_query->fetch_assoc()) {
        fputcsv($output, array(
            $row['student_name'], 
            $row['enrollment_no'], 
            $row['subject_name'], 
            $row['practical_no'], 
            date('d M Y, h:i A', strtotime($row['submitted_at'])), 
            $row['status'], 
            ($row['status'] == 'Graded' ? $row['total_marks'] : '-')
        ));
    }
    fclose($output);
    exit();
}

// ==========================================
// FETCH SUMMARY STATISTICS (SAFE METHOD)
// ==========================================
$uploaded_manuals = 0;
$q_up = $conn->query("SELECT COUNT(*) as c FROM lab_manuals $filter_manuals");
if ($q_up) { $uploaded_manuals = $q_up->fetch_assoc()['c']; }

$total_submissions = 0;
$q_tot = $conn->query("SELECT COUNT(*) as c FROM student_submissions ss JOIN lab_manuals lm ON ss.manual_id = lm.id $filter_subs");
if ($q_tot) { $total_submissions = $q_tot->fetch_assoc()['c']; }

$pending_reviews = 0;
$q_pen = $conn->query("SELECT COUNT(*) as c FROM student_submissions ss JOIN lab_manuals lm ON ss.manual_id = lm.id $filter_subs AND ss.status = 'Submitted'");
if ($q_pen) { $pending_reviews = $q_pen->fetch_assoc()['c']; }

$reviewed_submissions = 0;
$q_rev = $conn->query("SELECT COUNT(*) as c FROM student_submissions ss JOIN lab_manuals lm ON ss.manual_id = lm.id $filter_subs AND ss.status IN ('Graded', 'Resubmit')");
if ($q_rev) { $reviewed_submissions = $q_rev->fetch_assoc()['c']; }

// ==========================================
// FETCH ASSIGNED SUBJECTS FOR DROPDOWN (Added 'semester' column here)
// ==========================================
$assigned_subjects = $conn->query("SELECT subject_code, subject_name, semester FROM subjects WHERE (LOWER(faculty_name) LIKE '%$safe_id%' OR LOWER(faculty_name) LIKE '%$safe_name%') AND status='Active'");

// ==========================================
// SUBJECT-WISE REPORT DATA
// ==========================================
$subject_wise_data = $conn->query("
    SELECT lm.subject_code, lm.subject_name, 
           COUNT(DISTINCT lm.id) as total_manuals,
           COUNT(ss.id) as total_subs,
           SUM(CASE WHEN ss.status = 'Submitted' THEN 1 ELSE 0 END) as pending,
           SUM(CASE WHEN ss.status IN ('Graded', 'Resubmit') THEN 1 ELSE 0 END) as reviewed
    FROM lab_manuals lm
    LEFT JOIN student_submissions ss ON lm.id = ss.manual_id
    WHERE lm.faculty_id = '$faculty_id'
    GROUP BY lm.subject_code
");

// ==========================================
// DETAILED SUBMISSIONS TABLE
// ==========================================
$detailed_subs = $conn->query("
    SELECT ss.*, u.name as student_name, u.user_id as enrollment_no, lm.subject_name, lm.subject_code, lm.practical_no 
    FROM student_submissions ss 
    JOIN lab_manuals lm ON ss.manual_id = lm.id 
    JOIN users u ON ss.student_id = u.user_id 
    $filter_subs 
    ORDER BY ss.submitted_at DESC LIMIT 50
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Faculty Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .profile-pill { display: flex; align-items: center; background: linear-gradient(135deg, #1e3a8a, #4338ca); padding: 12px 25px 12px 30px; border-radius: 50px; cursor: pointer; text-decoration: none; color: white; transition: var(--transition-bounce); box-shadow: 0 10px 20px rgba(67, 56, 202, 0.2); border: 2px solid rgba(255,255,255,0.1);}
        
        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
        
        .stat-card { background: var(--bg-body); border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px;}
        .stat-icon { width: 45px; height: 45px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-info h4 { font-size: 24px; font-weight: 900; margin: 0; color: var(--text-main);}
        .stat-info span { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); }

        .table-custom th { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 12px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 13.5px; font-weight: 600; padding: 12px 10px; border-bottom: 1px solid #f1f5f9; }

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
            <li onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='upload_manual.php'"><i class="fas fa-file-upload"></i> Upload Manuals</li>
            <li onclick="window.location.href='review_submissions.php'"><i class="fas fa-check-double"></i> Review Submissions</li>
            <li class="active" onclick="window.location.href='reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> My Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="topbar">
            <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome" style="color: #bfdbfe; font-size: 11px; font-weight: 800; display: block; margin-bottom: 2px;"><?php echo htmlspecialchars($designation); ?></span>
                    <h4 class="profile-name" style="color: white; margin: 0; font-size: 17px; font-weight: 900;"><?php echo htmlspecialchars($faculty_name); ?></h4>
                </div>
                <div class="profile-avatar" style="background: white; color: #4338ca; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;"><i class="fas fa-user-tie"></i></div>
            </a>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1"><i class="fas fa-chart-line text-primary me-2"></i> Reports & Analytics</h3>
                <p class="text-muted fw-semibold small mb-0">Track lab manual activity and student practical submissions.</p>
            </div>
            <button onclick="exportToCSV()" class="btn btn-dark fw-bold px-4 py-2 shadow-sm"><i class="fas fa-file-excel text-success me-2"></i> Export Report</button>
        </div>

        <!-- FILTERS -->
        <div class="content-box">
            <form method="GET" class="row g-3 align-items-end" id="filterForm">
                <div class="col-md-5">
                    <label class="form-label fw-bold small text-muted text-uppercase">Subject</label>
                    <select name="subject" id="subjectFilter" class="form-select border-secondary-subtle" onchange="autoFillSemester()">
                        <option value="" data-sem="">All Subjects</option>
                        <?php if($assigned_subjects && $assigned_subjects->num_rows > 0): ?>
                            <?php while($sub = $assigned_subjects->fetch_assoc()): ?>
                                <!-- Added data-sem attribute for JavaScript -->
                                <option value="<?php echo htmlspecialchars($sub['subject_code']); ?>" data-sem="<?php echo $sub['semester']; ?>" <?php echo ($f_subject == $sub['subject_code']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sub['subject_code'] . " - " . $sub['subject_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Semester</label>
                    <select name="semester" id="semesterFilter" class="form-select border-secondary-subtle">
                        <option value="">All</option>
                        <option value="1" <?php echo ($f_semester == '1') ? 'selected' : ''; ?>>Semester 1</option>
                        <option value="2" <?php echo ($f_semester == '2') ? 'selected' : ''; ?>>Semester 2</option>
                        <option value="3" <?php echo ($f_semester == '3') ? 'selected' : ''; ?>>Semester 3</option>
                        <option value="4" <?php echo ($f_semester == '4') ? 'selected' : ''; ?>>Semester 4</option>
                        <option value="5" <?php echo ($f_semester == '5') ? 'selected' : ''; ?>>Semester 5</option>
                        <option value="6" <?php echo ($f_semester == '6') ? 'selected' : ''; ?>>Semester 6</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100"><i class="fas fa-filter"></i> Apply Filter</button>
                    <a href="reports.php" class="btn btn-light border fw-bold w-100"><i class="fas fa-sync-alt"></i> Reset</a>
                </div>
            </form>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card border-primary border-start border-4">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fas fa-inbox"></i></div>
                    <div class="stat-info"><span>Total Submissions</span><h4><?php echo $total_submissions; ?></h4></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-warning border-start border-4">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fas fa-clock"></i></div>
                    <div class="stat-info"><span>Pending Reviews</span><h4><?php echo $pending_reviews; ?></h4></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-success border-start border-4">
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fas fa-check-double"></i></div>
                    <div class="stat-info"><span>Reviewed</span><h4><?php echo $reviewed_submissions; ?></h4></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-info border-start border-4">
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fas fa-file-pdf"></i></div>
                    <div class="stat-info"><span>Uploaded Manuals</span><h4><?php echo $uploaded_manuals; ?></h4></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- OVERVIEW CHART -->
            <div class="col-lg-4">
                <div class="content-box h-100 d-flex flex-column">
                    <h6 class="fw-bold mb-4 text-dark"><i class="fas fa-chart-pie text-primary me-2"></i> Submission Overview</h6>
                    <div class="flex-grow-1 position-relative" style="min-height: 250px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- SUBJECT-WISE REPORT -->
            <div class="col-lg-8">
                <div class="content-box h-100">
                    <h6 class="fw-bold mb-4 text-dark"><i class="fas fa-book-open text-primary me-2"></i> Subject-wise Report</h6>
                    <div class="table-responsive">
                        <table class="table table-custom text-center">
                            <thead class="text-start">
                                <tr>
                                    <th>Subject</th>
                                    <th class="text-center">Manuals Uploaded</th>
                                    <th class="text-center">Submissions</th>
                                    <th class="text-center text-success">Reviewed</th>
                                    <th class="text-center text-warning">Pending</th>
                                </tr>
                            </thead>
                            <tbody class="text-start">
                                <?php if($subject_wise_data && $subject_wise_data->num_rows > 0): ?>
                                    <?php while($s_row = $subject_wise_data->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-primary"><?php echo htmlspecialchars($s_row['subject_code']); ?></div>
                                                <div class="small text-muted text-truncate" style="max-width:200px;"><?php echo htmlspecialchars($s_row['subject_name']); ?></div>
                                            </td>
                                            <td class="text-center"><span class="badge bg-light text-dark border"><?php echo $s_row['total_manuals']; ?></span></td>
                                            <td class="text-center fw-bold"><?php echo $s_row['total_subs']; ?></td>
                                            <td class="text-center text-success fw-bold"><?php echo $s_row['reviewed']; ?></td>
                                            <td class="text-center text-warning fw-bold"><?php echo $s_row['pending']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No data available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- DETAILED REPORT TABLE -->
        <div class="content-box">
            <h6 class="fw-bold mb-4 text-dark"><i class="fas fa-list-alt text-primary me-2"></i> Detailed Report (Recent 50 Submissions)</h6>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Student & ID</th>
                            <th>Subject & Practical</th>
                            <th>Submission Date</th>
                            <th>Review Status</th>
                            <th>Total Marks</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($detailed_subs && $detailed_subs->num_rows > 0): ?>
                            <?php while($d_row = $detailed_subs->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($d_row['student_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($d_row['enrollment_no']); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($d_row['subject_code']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($d_row['practical_no']); ?></div>
                                    </td>
                                    <td><?php echo date('d M Y, h:i A', strtotime($d_row['submitted_at'])); ?></td>
                                    <td>
                                        <?php 
                                            if($d_row['status'] == 'Submitted') echo '<span class="badge bg-warning text-dark">Pending</span>';
                                            elseif($d_row['status'] == 'Graded') echo '<span class="badge bg-success">Reviewed</span>';
                                            else echo '<span class="badge bg-danger">Resubmit</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if($d_row['status'] == 'Graded'): ?>
                                            <span class="fw-bold text-success"><?php echo $d_row['total_marks']; ?>/20</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="review_submissions.php?subject_code=<?php echo urlencode($d_row['subject_code']); ?>&manual_id=<?php echo $d_row['manual_id']; ?>" class="btn btn-sm btn-outline-primary fw-bold">Review Page <i class="fas fa-arrow-right ms-1"></i></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted fw-bold">No records found matching filters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Automatic Semester Selection Logic
        function autoFillSemester() {
            var subjSelect = document.getElementById('subjectFilter');
            var semSelect = document.getElementById('semesterFilter');
            var selectedOption = subjSelect.options[subjSelect.selectedIndex];
            var semValue = selectedOption.getAttribute('data-sem');

            if(semValue) {
                semSelect.value = semValue;
            } else {
                semSelect.value = ""; // Reset if "All Subjects" is selected
            }
        }

        // Clock
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const clockEl = document.getElementById('liveClock');
            if(clockEl) clockEl.innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Chart.js Setup
        const ctx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Reviewed', 'Pending'],
                datasets: [{
                    data: [<?php echo $reviewed_submissions; ?>, <?php echo $pending_reviews; ?>],
                    backgroundColor: ['#10b981', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { font: { family: "'Plus Jakarta Sans', sans-serif", weight: 'bold' } } }
                },
                cutout: '75%'
            }
        });

        // Export Function
        function exportToCSV() {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('export', 'csv');
            window.location.href = 'reports.php?' + urlParams.toString();
        }
    </script>
</body>
</html>