<?php
session_start();
include '../db.php';

// 1. SECURE LOGIN CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$enrollment = $conn->real_escape_string((string)$_SESSION['user_id']);

// 2. FETCH STUDENT DATA FOR STRICT MAPPING
$user_query = $conn->query("SELECT name, department, designation FROM users WHERE user_id = '$enrollment'");
$student_data = $user_query->fetch_assoc();

$student_name = $student_data['name'] ?? 'Student';
$branch = trim($student_data['department']) ?? 'Computer Engineering';
$raw_semester = trim($student_data['designation']) ?? '1';

// Extract exact semester number
preg_match('/\d+/', $raw_semester, $sem_matches);
$sem_num = $sem_matches[0] ?? '1';
$mapped_semester = "Semester " . $sem_num;

// Initials for Profile Avatar
$name_parts = explode(' ', trim($student_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) { $initials .= strtoupper(substr(end($name_parts), 0, 1)); }

// 3. FETCH STRICTLY MAPPED MANUALS
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$safe_search = $conn->real_escape_string($search_query);

// Query: Fetch manuals ONLY for this branch and sem, and check if student already submitted
$manuals_sql = "
    SELECT lm.*, 
           ss.submission_id, ss.status as sub_status, ss.submitted_at
    FROM lab_manuals lm
    LEFT JOIN student_submissions ss 
        ON lm.id = ss.manual_id AND ss.student_id = '$enrollment'
    WHERE lm.department = '$branch' AND (lm.semester = '$sem_num' OR lm.semester = '$mapped_semester')
";

if (!empty($search_query)) {
    $manuals_sql .= " AND (lm.title LIKE '%$safe_search%' OR lm.subject_name LIKE '%$safe_search%')";
}
$manuals_sql .= " ORDER BY lm.uploaded_at DESC";

$manuals_res = $conn->query($manuals_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Manuals - KDP Student Portal</title>
    
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

        /* 📦 CONTENT BOX & SEARCH */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 25px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); display: flex; align-items: center;}
        
        .search-group { display: flex; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; background: #f8fafc; width: 300px;}
        .search-group input { border: none; background: transparent; padding: 10px 15px; font-weight: 600; font-size: 14px; flex: 1; outline: none;}
        .search-group button { border: none; background: var(--primary); color: white; padding: 0 20px; cursor: pointer; transition: 0.2s;}
        .search-group button:hover { background: var(--primary-hover); }

        /* 📋 TABLE */
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; }
        
        .btn-action { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 8px 15px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: var(--transition-bounce); display: inline-flex; align-items: center; gap: 6px;}
        .btn-action:hover { background: #2563eb; color: white; transform: translateY(-2px); }
        
        .btn-submit { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .btn-submit:hover { box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); color: white;}

        .btn-done { background: #f1f5f9; color: #64748b; pointer-events: none; }

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
                <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            </div>
            
            <a href="profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Logged in as,</span>
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

        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1" style="font-size: 26px;">My Course Manuals</h3>
            <p class="text-muted small fw-semibold mb-0">Download official manuals provided by your faculty and submit your practicals before the deadline.</p>
        </div>

        <div class="content-box p-0 overflow-hidden">
            <div class="d-flex justify-content-between align-items-center p-4 border-bottom">
                <h5 class="box-title mb-0 border-0 pb-0"><i class="fas fa-file-alt text-primary me-2"></i> Assigned Manuals for Sem <?php echo $sem_num; ?></h5>
                
                <form method="GET" class="search-group">
                    <input type="text" name="search" placeholder="Search by Title or Subject..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Manual Info</th>
                            <th>Subject Details</th>
                            <th>Timeline & Deadline</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($manuals_res && $manuals_res->num_rows > 0): ?>
                            <?php while($row = $manuals_res->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-danger text-white rounded p-2 text-center" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                                                <i class="fas fa-file-pdf fs-5"></i>
                                            </div>
                                            <div class="fw-bold text-dark" style="font-size: 14.5px;"><?php echo htmlspecialchars($row['title']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-primary fw-bold" style="font-size: 13.5px;"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                                        <div class="text-muted fw-bold small mt-1"><i class="fas fa-tag me-1"></i> Prac: <?php echo htmlspecialchars($row['practical_no'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size: 12px; color: var(--text-main);" class="fw-semibold">
                                            <i class="fas fa-calendar-plus text-muted me-1"></i> Uploaded: <?php echo date('d M Y', strtotime($row['uploaded_at'])); ?><br>
                                            <?php 
                                                $deadline = (!empty($row['end_date']) && $row['end_date'] != '0000-00-00') ? date('d M Y', strtotime($row['end_date'])) : 'No Deadline';
                                                
                                                $deadline_color = 'text-muted';
                                                if($deadline !== 'No Deadline' && empty($row['sub_status'])) {
                                                    $today = time();
                                                    $dl_time = strtotime($row['end_date'] . " 23:59:59");
                                                    if($today > $dl_time) {
                                                        $deadline_color = 'text-danger';
                                                        $deadline .= ' (Overdue)';
                                                    } elseif (($dl_time - $today) < (3 * 86400)) {
                                                        $deadline_color = 'text-warning';
                                                        $deadline .= ' (Ending Soon)';
                                                    }
                                                }
                                            ?>
                                            <div class="mt-1"><i class="fas fa-flag-checkered <?php echo $deadline_color; ?> me-1"></i> <span class="<?php echo $deadline_color; ?>"><?php echo $deadline; ?></span></div>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php 
                                            $raw_path = $row['file_path'];
                                            $pos = strpos($raw_path, 'uploads/');
                                            $safe_pdf_path = ($pos !== false) ? '../' . substr($raw_path, $pos) : '../' . $raw_path;
                                        ?>
                                        <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" target="_blank" class="btn-action me-2" title="Download Official Manual"><i class="fas fa-download"></i> View</a>
                                        
                                        <?php if(empty($row['sub_status']) || $row['sub_status'] == 'Rejected'): ?>
                                            <a href="upload_manual.php?manual_id=<?php echo $row['id']; ?>&subject=<?php echo urlencode($row['subject_name']); ?>&prac=<?php echo urlencode($row['practical_no']); ?>" class="btn-action btn-submit">
                                                <i class="fas fa-cloud-upload-alt"></i> Submit
                                            </a>
                                        <?php else: ?>
                                            <span class="btn-action btn-done"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($row['sub_status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open mb-3" style="font-size: 40px; opacity: 0.3;"></i><br>
                                    <span class="fw-bold fs-6">No manuals assigned for your semester yet.</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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
    </script>
</body>
</html>





















