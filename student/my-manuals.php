<?php
session_start();
include '../db.php';

// 1. Check Login & Security
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$enrollment = $conn->real_escape_string((string)$_SESSION['user_id']);

// 2. Fetch Student Profile for Branch & Sem Matching
$user_query = $conn->query("SELECT name, email, department, designation FROM users WHERE user_id = '$enrollment'");
$student_data = $user_query->fetch_assoc();

$student_name = $student_data['name'] ?? 'Student';
$branch = trim($student_data['department'] ?? 'Computer Engineering');
$raw_semester = trim($student_data['designation'] ?? '1');

// 🔥 SMART MAPPING LOGIC 
preg_match('/\d+/', $raw_semester, $sem_matches);
$sem_num = $sem_matches[0] ?? '1';
$mapped_semester = "Semester " . $sem_num; 

// Generate Initials for Profile Avatar
$name_parts = explode(' ', trim($student_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

// ==========================================
// 📚 FETCH ALL LAB MANUALS FOR THIS STUDENT
// ==========================================
$query = "SELECT * FROM lab_manuals WHERE department = '$branch' AND (semester = '$sem_num' OR semester = '$mapped_semester') ORDER BY uploaded_at DESC";
$manuals = $conn->query($query);
$total_manuals = $manuals ? $manuals->num_rows : 0;

// ==========================================
// ⏳ FETCH UPCOMING DEADLINES (Active Only)
// ==========================================
$today_date = date('Y-m-d');
$deadlines_query = "SELECT title, subject_name, end_date FROM lab_manuals WHERE department = '$branch' AND (semester = '$sem_num' OR semester = '$mapped_semester') AND end_date >= '$today_date' AND end_date != '0000-00-00' ORDER BY end_date ASC LIMIT 4";
$upcoming_deadlines = $conn->query($deadlines_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Manuals - Student Portal</title>
    
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

        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        .security-badge { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 10px; padding: 10px 18px; font-weight: 700; font-size: 13px; }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3); letter-spacing: 1px;}

        /* 📦 CONTENT CARDS */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 30px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        .icon-box { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 26px; }
        .blue-box { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }

        /* SEARCH INPUT */
        .search-modern { border-radius: 10px 0 0 10px !important; border: 1px solid #cbd5e1; font-weight: 500; font-size: 14px; padding: 12px 18px; background: #f8fafc;}
        .search-modern:focus { background: #ffffff; box-shadow: none; border-color: var(--primary); }
        .btn-search { border-radius: 0 10px 10px 0 !important; background: var(--primary); color: white; padding: 0 20px; font-weight: 700; border: none; transition: var(--transition-bounce);}
        .btn-search:hover { background: var(--primary-hover); }

        /* TABLE */
        .table-custom th { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #ffffff; }
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; transition: background-color 0.2s; }
        .table-custom tbody tr:hover td { background-color: #f8fafc; }
        
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .bg-primary-soft { background: rgba(59, 130, 246, 0.1); color: #1d4ed8; }

        .btn-view { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: var(--transition-bounce); display: inline-flex; align-items: center; gap: 8px;}
        .btn-view:hover { background: #2563eb; color: white; transform: scale(1.05); box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2); }
        
        .btn-submit { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: var(--transition-bounce); display: inline-flex; align-items: center; gap: 8px;}
        .btn-submit:hover { transform: scale(1.05); box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); color: white; }

        /* WIDGET CARDS */
        .widget-item { background: #f8fafc; border-radius: 12px; padding: 15px; border: 1px solid #e2e8f0; margin-bottom: 12px; transition: var(--transition-bounce); }
        .widget-item:hover { background: #ffffff; border-color: #cbd5e1; transform: translateX(5px); box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
        
        .guideline-list { padding-left: 15px; margin: 0; font-size: 13.5px; color: var(--text-muted); font-weight: 500; line-height: 1.8;}
        .guideline-list li { margin-bottom: 8px; }
        .guideline-list li i { color: var(--primary); font-size: 12px; margin-right: 5px; }

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
            <li class="active" onclick="window.location.href='my-manuals.php'"><i class="fas fa-book-open"></i> Course Manuals</li>
            <li onclick="window.location.href='history.php'"><i class="fas fa-history"></i> My Submissions</li>
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
                <div class="icon-box blue-box"><i class="fas fa-book-open"></i></div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);">Course Lab Manuals</h3>
                    <p class="text-muted fw-semibold small mb-0">Download official lab manuals uploaded by your faculty for <?php echo htmlspecialchars($mapped_semester); ?>.</p>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-3 py-2 fs-6" style="border-radius: 10px; font-weight: 700;">
                    Total Manuals: <?php echo $total_manuals; ?>
                </span>
            </div>
        </div>

        <!-- TWO COLUMN LAYOUT -->
        <div class="row g-4">
            
            <!-- LEFT COLUMN: MANUALS TABLE -->
            <div class="col-lg-8">
                <div class="content-box h-100">
                    <!-- SEARCH BAR -->
                    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                        <h5 class="box-title mb-0 border-0 pb-0"><i class="fas fa-folder-open text-primary me-2"></i> Available Manuals</h5>
                        <div class="input-group shadow-sm" style="width: 280px;">
                            <input type="text" id="manualSearch" class="form-control search-modern" placeholder="Search subject or title..." onkeyup="filterManuals()">
                            <button class="btn-search"><i class="fas fa-search"></i></button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom mb-0" id="manualTable">
                            <thead>
                                <tr>
                                    <th>Manual Details</th>
                                    <th>Important Dates</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($manuals && $manuals->num_rows > 0): ?>
                                    <?php while($row = $manuals->fetch_assoc()): ?>
                                        <tr class="manual-row">
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-danger text-white rounded p-2 text-center" style="width:45px; height:45px; display:flex; align-items:center; justify-content:center;">
                                                        <i class="fas fa-file-pdf fs-5"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold subject-title" style="font-size: 15px; color: var(--text-main);"><?php echo htmlspecialchars($row['title']); ?></div>
                                                        <div class="text-primary fw-bold mt-1" style="font-size: 12.5px;"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="font-size: 12.5px; color: var(--text-main);" class="fw-semibold">
                                                    <i class="fas fa-upload text-muted me-1"></i> Added: <?php echo date('d M', strtotime($row['uploaded_at'])); ?><br>
                                                    
                                                    <?php 
                                                    // Handle Deadline
                                                    $deadline_color = 'text-muted';
                                                    $deadline_text = 'No Deadline';
                                                    if (!empty($row['end_date']) && $row['end_date'] != '0000-00-00') {
                                                        $deadline_date = strtotime($row['end_date']);
                                                        $today = time();
                                                        $diff_days = round(($deadline_date - $today) / (60 * 60 * 24));
                                                        
                                                        $deadline_text = date('d M Y', $deadline_date);
                                                        if ($diff_days < 0) { $deadline_color = 'text-danger'; $deadline_text .= ' (Overdue)'; }
                                                        elseif ($diff_days <= 2) { $deadline_color = 'text-danger'; $deadline_text .= ' (Ending Soon)'; }
                                                        else { $deadline_color = 'text-success'; }
                                                    }
                                                    ?>
                                                    <div class="mt-1">
                                                        <i class="fas fa-flag-checkered <?php echo $deadline_color; ?> me-1"></i> 
                                                        <span class="<?php echo $deadline_color; ?>">Due: <?php echo $deadline_text; ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <?php 
                                                    $raw_path = $row['file_path'];
                                                    $pos = strpos($raw_path, 'uploads/');
                                                    $safe_pdf_path = ($pos !== false) ? '../' . substr($raw_path, $pos) : '../' . $raw_path;
                                                ?>
                                                <!-- Download Button -->
                                                <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" target="_blank" class="btn-view" title="Download Manual">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <!-- Submit Button -->
                                                <a href="upload_manual.php?subject=<?php echo urlencode($row['subject_name']); ?>" class="btn-submit ms-2" title="Submit Practical">
                                                    <i class="fas fa-upload me-1"></i> Submit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            <i class="fas fa-box-open mb-3" style="font-size: 45px; opacity: 0.3; color: var(--primary);"></i><br>
                                            <h5 class="fw-bold text-dark mb-1">No Manuals Found</h5>
                                            <p class="small mb-0">Your department hasn't uploaded any lab manuals for your semester yet.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: WIDGETS & GUIDELINES -->
            <div class="col-lg-4">
                
                <!-- UPCOMING DEADLINES WIDGET -->
                <div class="content-box mb-4">
                    <h5 class="box-title"><i class="fas fa-clock text-warning me-2"></i> Upcoming Deadlines</h5>
                    
                    <?php if($upcoming_deadlines && $upcoming_deadlines->num_rows > 0): ?>
                        <?php while($dl = $upcoming_deadlines->fetch_assoc()): 
                            $dl_date = strtotime($dl['end_date']);
                            $days_left = round(($dl_date - time()) / (60 * 60 * 24));
                            $alert_class = ($days_left <= 2) ? 'text-danger' : 'text-success';
                        ?>
                        <div class="widget-item">
                            <h6 class="fw-bold text-dark mb-1" style="font-size: 14px;"><?php echo htmlspecialchars($dl['subject_name']); ?></h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted fw-semibold"><?php echo htmlspecialchars($dl['title']); ?></small>
                                <span class="badge bg-light border <?php echo $alert_class; ?> fw-bold shadow-sm">
                                    <?php echo date('d M', $dl_date); ?>
                                </span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-calendar-check text-success mb-2" style="font-size: 30px; opacity: 0.7;"></i>
                            <p class="text-muted small fw-semibold mb-0">No immediate deadlines approaching. Relax!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- GUIDELINES WIDGET -->
                <div class="content-box" style="background: linear-gradient(135deg, rgba(67, 56, 202, 0.03), rgba(59, 130, 246, 0.05)); border: 1px solid rgba(67, 56, 202, 0.1);">
                    <h5 class="box-title"><i class="fas fa-info-circle text-primary me-2"></i> Submission Guidelines</h5>
                    <ul class="list-unstyled guideline-list">
                        <li><i class="fas fa-check-circle"></i> Always download the official manual first before performing practicals.</li>
                        <li><i class="fas fa-check-circle"></i> Create a single PDF file for each practical submission.</li>
                        <li><i class="fas fa-check-circle"></i> File size should not exceed <strong>5MB</strong>.</li>
                        <li><i class="fas fa-check-circle"></i> Name your file properly (e.g., <em>EnrollmentNo_Subject_Prac1.pdf</em>).</li>
                        <li><i class="fas fa-check-circle"></i> Late submissions may be rejected by the faculty automatically.</li>
                    </ul>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live Clock Script
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Live Search Filter Logic
        function filterManuals() {
            let input = document.getElementById("manualSearch").value.toLowerCase();
            let rows = document.querySelectorAll(".manual-row");

            rows.forEach(row => {
                let text = row.querySelector(".subject-title").innerText.toLowerCase();
                let subject = row.querySelector(".text-primary").innerText.toLowerCase();
                
                if (text.includes(input) || subject.includes(input)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>