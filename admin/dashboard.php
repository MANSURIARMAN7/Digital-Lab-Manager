<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch Admin Details
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data['name'] ?? 'System Administrator';

// Generate Initials
$name_parts = explode(' ', trim($admin_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

// 📊 CORE MANAGEMENT METRICS ONLY
$students_count_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
$total_students = ($students_count_res) ? $students_count_res->fetch_assoc()['total'] : 0;

$faculty_count_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'faculty'");
$total_faculty = ($faculty_count_res) ? $faculty_count_res->fetch_assoc()['total'] : 0;

$subject_count_res = $conn->query("SELECT COUNT(*) as total FROM subjects");
$total_subjects = ($subject_count_res) ? $subject_count_res->fetch_assoc()['total'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KDP System</title>
    
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
        
        /* 🔥 SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover, .nav-links li.active { color: #ffffff; background: rgba(255,255,255,0.2); }
        .nav-links li.active { font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid white;}
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* 🌐 TOPBAR */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 10px 22px 10px 28px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 17px; color: var(--text-main); font-weight: 900; letter-spacing: 0.3px;}
        .profile-avatar { width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.4);}

        /* 📊 STATS RIBBON */
        .stats-ribbon { display: flex; gap: 20px; margin-bottom: 35px; flex-wrap: wrap;}
        .stat-badge { flex: 1; min-width: 200px; background: var(--surface); padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 20px; transition: var(--transition-bounce); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
        .stat-badge:hover { transform: translateY(-5px); box-shadow: var(--shadow-float); border-color: #cbd5e1;}
        .stat-badge .icon-box { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 26px; }
        .stat-badge .info h4 { margin: 0; font-size: 32px; font-weight: 900; line-height: 1; color: var(--text-main);}
        .stat-badge .info span { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 5px; display: block;}

        /* 📦 QUICK ACTIONS BOX */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 35px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
        .box-title { font-size: 18px; font-weight: 800; color: var(--text-main); margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px;}
        
        .action-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 25px; text-align: center; text-decoration: none; color: inherit; transition: var(--transition-bounce); display: block;}
        .action-card:hover { background: #ffffff; border-color: var(--primary); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(67,56,202,0.1); }
        .action-card i { font-size: 40px; color: var(--primary); margin-bottom: 15px; }
        .action-card h5 { font-size: 16px; font-weight: 800; margin-bottom: 8px; color: var(--text-main);}
        .action-card p { font-size: 13px; font-weight: 500; color: var(--text-muted); margin: 0;}

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>
    
    <!-- SYSTEM ADMIN SIDEBAR (Cleaned up!) -->
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
            <li onclick="window.location.href='Profile.php'"><i class="fas fa-user-shield"></i> Profile</li>
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
            
            <a href="Profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">System Admin</span>
                    <h4 class="profile-name">
                        <?php 
                            $disp_name = htmlspecialchars($admin_name);
                            echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : $disp_name;
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </a>
        </div>

        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1" style="font-size: 26px;">System Overview</h3>
            <p class="text-muted small fw-semibold mb-0">Manage core database entries for K.D. Polytechnic.</p>
        </div>

        <!-- 📊 MANAGEMENT STATS RIBBON -->
        <div class="stats-ribbon">
            <div class="stat-badge">
                <div class="icon-box" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-user-graduate"></i></div>
                <div class="info"><span>Registered Students</span><h4><?php echo $total_students; ?></h4></div>
            </div>
            <div class="stat-badge">
                <div class="icon-box" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="info"><span>Active Faculties</span><h4><?php echo $total_faculty; ?></h4></div>
            </div>
            <div class="stat-badge">
                <div class="icon-box" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-book"></i></div>
                <div class="info"><span>Total Subjects</span><h4><?php echo $total_subjects; ?></h4></div>
            </div>
        </div>

        <!-- 🚀 QUICK ACTIONS (Replaced Recent Submissions Table) -->
        <div class="content-box">
            <h5 class="box-title"><i class="fas fa-bolt text-primary"></i> Quick Management Actions</h5>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <a href="Student_Mgmt.php" class="action-card">
                        <i class="fas fa-user-plus"></i>
                        <h5>Manage Students</h5>
                        <p>Add, edit, or remove student accounts and enrollment details.</p>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="faculty_mgmt.php" class="action-card">
                        <i class="fas fa-chalkboard-teacher text-success"></i>
                        <h5>Manage Faculties</h5>
                        <p>Assign departments, cabins, and update faculty credentials.</p>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="subject_mgmt.php" class="action-card">
                        <i class="fas fa-layer-group text-warning"></i>
                        <h5>Manage Subjects</h5>
                        <p>Link subjects to specific faculties and academic semesters.</p>
                    </a>
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
    </script>
</body>
</html>