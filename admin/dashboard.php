<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch Admin Details for Profile Pill
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query ? $admin_query->fetch_assoc() : null;
$admin_name = $admin_data['name'] ?? 'System Administrator';

// ==========================================
// 📊 METRICS & COUNTERS QUERY
// ==========================================
$students_count_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$total_students = ($students_count_res) ? $students_count_res->fetch_assoc()['total'] : 0;

$faculty_count_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'faculty'");
$total_faculty = ($faculty_count_res) ? $faculty_count_res->fetch_assoc()['total'] : 0;

$pending_count_res = $conn->query("SELECT COUNT(*) as total FROM student_submissions WHERE status = 'Pending'");
$pending_reviews = ($pending_count_res) ? $pending_count_res->fetch_assoc()['total'] : 0;

$rejected_count_res = $conn->query("SELECT COUNT(*) as total FROM student_submissions WHERE status = 'Rejected'");
$rejected_submissions = ($rejected_count_res) ? $rejected_count_res->fetch_assoc()['total'] : 0;

$approved_count_res = $conn->query("SELECT COUNT(*) as total FROM student_submissions WHERE status = 'Approved'");
$approved_submissions = ($approved_count_res) ? $approved_count_res->fetch_assoc()['total'] : 0;

$total_submissions = $pending_reviews + $rejected_submissions + $approved_submissions;

// ==========================================
// 🕒 RECENT SUBMISSIONS QUERY
// ==========================================
$recent_query = $conn->query("
    SELECT sub.*, 
           COALESCE(NULLIF(u.name, ''), 'Unknown Student') as student_name, 
           COALESCE(NULLIF(u.email, ''), 'N/A') as student_enrollment 
    FROM student_submissions sub 
    LEFT JOIN users u ON sub.student_id = u.user_id 
    ORDER BY sub.submitted_at DESC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lab Manual Portal</title>
    
    <!-- Bootstrap, FontAwesome & PREMIUM GOOGLE FONT -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- 🚀 PREMIUM MODERN UI CSS -->
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

        body { 
            background-color: var(--bg-body); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main);
        }
        
        /* 🔥 NEW PREMIUM BLUE SIDEBAR (No Black) */
        .sidebar { 
            width: var(--sidebar-width); 
            background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); /* Royal Blue to Indigo Gradient */
            color: #ffffff; display: flex; flex-direction: column; z-index: 10; overflow-y: auto; 
            box-shadow: 4px 0 24px rgba(0,0,0,0.08);
        }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0; letter-spacing: 0.5px; color: #ffffff;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { 
            padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; 
            font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); border-left: 3px solid transparent;
        }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .nav-links li.active { 
            background: rgba(255, 255, 255, 0.2); /* Glass white overlay */
            color: #ffffff; border-left: 4px solid #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; } /* Soft red for logout */

        /* ✨ MAIN CONTENT ANIMATION */
        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* 🌈 TOPBAR & PROFILE PILL */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { 
            display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; 
            border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; 
            transition: var(--transition-bounce); box-shadow: var(--shadow-float);
        }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);}

        /* 🏆 FLOATING STAT CARDS */
        .stat-card { 
            background: var(--surface); border-radius: var(--radius-xl); padding: 26px; border: 1px solid rgba(226, 232, 240, 0.8); 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); position: relative; overflow: hidden; transition: var(--transition-bounce); cursor: pointer; 
        }
        .stat-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-float); border-color: #cbd5e1; }
        .stat-card-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; color: var(--text-muted); }
        .stat-card-value { font-size: 32px; font-weight: 800; color: var(--text-main); margin-top: 8px; margin-bottom: 0; line-height: 1; }
        
        .icon-box { width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: var(--transition-bounce); }
        .stat-card:hover .icon-box { transform: scale(1.1) rotate(5deg); }
        
        .blue-box { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .green-box { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .yellow-box { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .red-box { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        /* 📦 CONTENT BOXES */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 30px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); }
        .content-box:hover { box-shadow: var(--shadow-float); }
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 5px; }

        /* 🎯 GRADIENT TEXT & PIE CHART AREA */
        .gradient-text { background: linear-gradient(135deg, #4f46e5, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .breakdown-circle { 
            width: 140px; height: 140px; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; 
            margin: 0 auto; background: var(--surface); border: 8px solid #f1f5f9; box-shadow: inset 0 4px 10px rgba(0,0,0,0.05), 0 10px 20px rgba(0,0,0,0.05); 
        }
        .breakdown-circle h1 { font-size: 42px; font-weight: 800; margin: 0; line-height: 1; }

        /* 🚀 PREMIUM BUTTONS */
        .btn-gradient { 
            background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; border: none; font-weight: 700; padding: 10px 20px; border-radius: 10px; 
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); transition: var(--transition-bounce);
        }
        .btn-gradient:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4); color: white; }

        /* 📋 SLEEK TABLES */
        .table-custom th { background: transparent; font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; }
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; transition: background-color 0.2s; }
        .table-custom tbody tr:hover td { background-color: #f8fafc; }
        
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .bg-success-soft { background: rgba(16, 185, 129, 0.1); color: #059669; }
        .bg-danger-soft { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        .bg-warning-soft { background: rgba(245, 158, 11, 0.15); color: #d97706; }

        /* SCROLLBAR */
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
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li class="active" onclick="window.location.href='dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-pdf"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-inbox"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-double"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
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
                
                <div class="dropdown">
                    <button class="btn-gradient dropdown-toggle" type="button" id="quickActions" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bolt me-1"></i> Quick Action
                    </button>
                    <ul class="dropdown-menu border-0 mt-2 p-2" aria-labelledby="quickActions" style="border-radius: 12px; box-shadow: var(--shadow-float);">
                        <li><a class="dropdown-item py-2 fw-bold text-secondary" style="border-radius: 8px;" href="Student_Mgmt.php"><i class="fas fa-user-plus text-primary me-2"></i> Add Student</a></li>
                        <li><a class="dropdown-item py-2 fw-bold text-secondary" style="border-radius: 8px;" href="faculty_mgmt.php"><i class="fas fa-chalkboard-teacher text-success me-2"></i> Manage Faculty</a></li>
                        <li><hr class="dropdown-divider my-2"></li>
                        <li><a class="dropdown-item py-2 fw-bold text-secondary" style="border-radius: 8px;" href="Reports.php"><i class="fas fa-file-pdf text-danger me-2"></i> Generate Report</a></li>
                    </ul>
                </div>
            </div>
            
            <a href="Profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">K.D. Polytechnic</span>
                    <h4 class="profile-name">
                        <?php 
                            $name_parts = explode(' ', $admin_name);
                            echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : 'Admin';
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar">AD</div>
            </a>
        </div>

        <!-- PAGE HEADER -->
        <div class="mb-5 mt-2">
            <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);">Admin Overview</h3>
            <p class="text-muted fw-semibold small mb-0">Real-time system management for Computer Engineering Department.</p>
        </div>

        <!-- FLOATING STATS CARDS -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <a href="Student_Mgmt.php" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-card-title">Total Students</div>
                                <div class="stat-card-value"><?php echo $total_students; ?></div>
                            </div>
                            <div class="icon-box blue-box"><i class="fas fa-user-graduate"></i></div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="faculty_mgmt.php" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-card-title">Active Faculty</div>
                                <div class="stat-card-value"><?php echo $total_faculty; ?></div>
                            </div>
                            <div class="icon-box green-box"><i class="fas fa-chalkboard-teacher"></i></div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="Submissions.php?status=Pending" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-card-title">Pending Reviews</div>
                                <div class="stat-card-value"><?php echo $pending_reviews; ?></div>
                            </div>
                            <div class="icon-box yellow-box"><i class="fas fa-hourglass-half"></i></div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="Submissions.php?status=Rejected" class="text-decoration-none">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-card-title">Rejected Files</div>
                                <div class="stat-card-value"><?php echo $rejected_submissions; ?></div>
                            </div>
                            <div class="icon-box red-box"><i class="fas fa-times-circle"></i></div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- BREAKDOWN & TABLE -->
        <div class="row g-4">
            <div class="col-md-4">
                <div class="content-box h-100 d-flex flex-column justify-content-between">
                    <div class="text-center mb-4">
                        <h5 class="box-title">Submission Flow</h5>
                        <p class="text-muted small fw-semibold">Live status distribution</p>
                    </div>
                    
                    <div class="text-center mb-4">
                        <div class="breakdown-circle mb-4">
                            <h1 class="gradient-text"><?php echo $total_submissions; ?></h1>
                            <span class="text-muted fw-bold mt-1" style="font-size: 10px; letter-spacing: 1px;">TOTAL</span>
                        </div>
                        
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <span class="badge-modern bg-success-soft">App: <?php echo $approved_submissions; ?></span>
                            <span class="badge-modern bg-warning-soft">Pen: <?php echo $pending_reviews; ?></span>
                            <span class="badge-modern bg-danger-soft">Rej: <?php echo $rejected_submissions; ?></span>
                        </div>
                    </div>

                    <a href="Reports.php" class="btn btn-gradient w-100 text-center">View Full Analytics <i class="fas fa-arrow-right ms-2"></i></a>
                </div>
            </div>

            <div class="col-md-8">
                <div class="content-box h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="box-title">Recent Submissions</h5>
                        <a href="Submissions.php" class="btn btn-sm btn-light fw-bold" style="border-radius: 8px; box-shadow: var(--shadow-sm); color: var(--primary);">View All</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Student Details</th>
                                    <th>Subject & Practical</th>
                                    <th>Submission Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($recent_query && $recent_query->num_rows > 0): ?>
                                    <?php while($row = $recent_query->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div style="font-size: 13.5px;"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                                <div class="text-muted mt-1" style="font-size: 11px; font-weight: 700;"><?php echo htmlspecialchars($row['student_enrollment']); ?></div>
                                            </td>
                                            <td>
                                                <div style="font-size: 13.5px; color: var(--primary);"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                                                <div class="text-muted mt-1" style="font-size: 11px; font-weight: 700;"><?php echo htmlspecialchars($row['practical_no']); ?></div>
                                            </td>
                                            <td>
                                                <div style="font-size: 12.5px; color: var(--text-muted);"><?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?></div>
                                            </td>
                                            <td>
                                                <?php if($row['status'] == 'Approved'): ?>
                                                    <span class="badge-modern bg-success-soft">Approved</span>
                                                <?php elseif($row['status'] == 'Rejected'): ?>
                                                    <span class="badge-modern bg-danger-soft">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge-modern bg-warning-soft">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <i class="fas fa-inbox mb-3" style="font-size: 32px; color: #cbd5e1;"></i><br>
                                            <span class="fw-bold">No recent submissions found.</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Live Clock Script -->
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