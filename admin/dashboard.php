<?php
session_start();
include '../db.php';

// 1. SECURE LOGIN CHECK
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$admin_id = $_SESSION['user_id'];

// 2. FETCH ADMIN DETAILS
$admin_query = $conn->query("SELECT name FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data['name'] ?? 'System Administrator';

// Initials for Profile Avatar
$name_parts = explode(' ', trim($admin_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) { $initials .= strtoupper(substr(end($name_parts), 0, 1)); }

// 3. FETCH SYSTEM STATS
$q_stu = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='student'");
$tot_stu = $q_stu->fetch_assoc()['cnt'] ?? 0;

$q_fac = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='faculty'");
$tot_fac = $q_fac->fetch_assoc()['cnt'] ?? 0;

$q_sub = $conn->query("SELECT COUNT(DISTINCT subject_code) as cnt FROM subjects");
$tot_sub = $q_sub->fetch_assoc()['cnt'] ?? 0;

$q_man = $conn->query("SELECT COUNT(*) as cnt FROM lab_manuals");
$tot_man = $q_man->fetch_assoc()['cnt'] ?? 0;

// 4. RECENTLY ADDED USERS
$recent_users = $conn->query("SELECT name, email, role, department, created_at FROM users WHERE role IN ('student', 'faculty') ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KDP Portal</title>
    
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

        /* 🚀 WELCOME BANNER */
        .welcome-banner { background: linear-gradient(135deg, #1e3a8a, #4338ca); border-radius: var(--radius-xl); padding: 35px 40px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(67, 56, 202, 0.15); margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;}
        .welcome-banner::before { content: '\f51c'; font-family: 'Font Awesome 5 Free'; font-weight: 900; position: absolute; right: 20px; top: -20px; font-size: 180px; color: rgba(255,255,255,0.05); }
        .welcome-text-area { position: relative; z-index: 2; color: white;}
        .welcome-banner h2 { font-size: 28px; font-weight: 800; margin-bottom: 5px; }

        /* 🗂️ STAT CARDS */
        .stat-card-rad { background: var(--surface); border-radius: 16px; padding: 25px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.02); transition: var(--transition-bounce); cursor: pointer; position: relative; overflow: hidden;}
        .stat-card-rad:hover { transform: translateY(-5px); box-shadow: var(--shadow-float); }
        .stat-card-rad::after { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; }
        .card-blue::after { background: #3b82f6; }
        .card-green::after { background: #10b981; }
        .card-yellow::after { background: #f59e0b; }
        .card-purple::after { background: #8b5cf6; }
        .stat-info h4 { font-size: 32px; font-weight: 900; color: var(--text-main); margin: 0; line-height: 1;}
        .stat-info span { font-size: 12px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; margin-bottom: 5px; display: block;}
        .stat-icon { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 26px; }

        /* 📦 CONTENT BOXES & TABLE */
        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 25px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;}

        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; }
        
        .badge-role { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;}
        .role-student { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe;}
        .role-faculty { background: #fce7f3; color: #9d174d; border: 1px solid #fbcfe8;}

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
            <li onclick="window.location.href='Profile.php'"><i class="fas fa-user-shield"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
                <div class="d-none d-md-flex align-items-center" style="background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 10px; padding: 10px 18px; font-weight: 700; font-size: 13px;">
                    <i class="fas fa-shield-check me-2"></i> Master Control Active
                </div>
            </div>
            
            <a href="Profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">System Admin</span>
                    <h4 class="profile-name">
                        <?php echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : htmlspecialchars($admin_name); ?>
                    </h4>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </a>
        </div>

        <!-- 🚀 WELCOME BANNER -->
        <div class="welcome-banner">
            <div class="welcome-text-area">
                <h2>Welcome, <?php echo htmlspecialchars($name_parts[0]); ?>! 👋</h2>
                <p class="mb-0 fw-medium text-white-50" style="font-size: 15px;">You have full control over the Digital Lab Management System. Monitor activity below.</p>
            </div>
            <div class="d-none d-md-block">
                <button onclick="window.location.href='Student_Mgmt.php'" class="btn btn-light fw-bold px-4 py-2" style="border-radius: 10px; color: var(--primary);">
                    <i class="fas fa-user-plus me-2"></i> Manage Users
                </button>
            </div>
        </div>

        <!-- 🗂️ STATS GRID -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card-rad card-blue" onclick="window.location.href='Student_Mgmt.php'">
                    <div class="stat-info"><span>Total Students</span><h4><?php echo $tot_stu; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-user-graduate"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-rad card-green" onclick="window.location.href='faculty_mgmt.php'">
                    <div class="stat-info"><span>Total Faculty</span><h4><?php echo $tot_fac; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-chalkboard-teacher"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-rad card-yellow" onclick="window.location.href='subject_mgmt.php'">
                    <div class="stat-info"><span>Active Subjects</span><h4><?php echo $tot_sub; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-book-open"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-rad card-purple">
                    <div class="stat-info"><span>Manuals Uploaded</span><h4><?php echo $tot_man; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;"><i class="fas fa-file-pdf"></i></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- 📜 LEFT: RECENT USERS -->
            <div class="col-lg-8">
                <div class="content-box p-0 overflow-hidden h-100">
                    <div class="d-flex justify-content-between align-items-center p-4 border-bottom">
                        <h5 class="box-title mb-0 border-0 pb-0"><i class="fas fa-users text-primary me-2"></i> Recently Added Users</h5>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">User Name</th>
                                    <th>Email / ID</th>
                                    <th>Department</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($recent_users && $recent_users->num_rows > 0): ?>
                                    <?php while($user = $recent_users->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark" style="font-size: 14.5px;"><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td>
                                                <div style="font-size: 13.5px;" class="fw-semibold text-muted">
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark" style="font-size: 13.5px;">
                                                    <?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge-role role-<?php echo htmlspecialchars($user['role']); ?>">
                                                    <i class="fas <?php echo ($user['role'] == 'student') ? 'fa-user-graduate' : 'fa-chalkboard-teacher'; ?> me-1"></i> <?php echo htmlspecialchars($user['role']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <span class="fw-bold fs-6">No recent users found.</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ⚡ RIGHT: QUICK ACTIONS -->
            <div class="col-lg-4">
                <div class="content-box h-100" style="background: linear-gradient(135deg, rgba(67, 56, 202, 0.03), rgba(59, 130, 246, 0.05)); border: 1px solid rgba(67, 56, 202, 0.1);">
                    <h5 class="box-title"><i class="fas fa-bolt text-warning me-2"></i> Quick Actions</h5>
                    
                    <div class="d-grid gap-3">
                        <a href="Student_Mgmt.php" class="btn btn-light fw-bold py-3 text-start d-flex justify-content-between align-items-center" style="border-radius: 12px; border: 1px solid #cbd5e1; color: var(--text-main); transition: 0.2s;">
                            <span><i class="fas fa-user-plus text-primary me-2"></i> Add New Student</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                        
                        <a href="faculty_mgmt.php" class="btn btn-light fw-bold py-3 text-start d-flex justify-content-between align-items-center" style="border-radius: 12px; border: 1px solid #cbd5e1; color: var(--text-main); transition: 0.2s;">
                            <span><i class="fas fa-chalkboard-teacher text-success me-2"></i> Add New Faculty</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                        
                        <a href="subject_mgmt.php" class="btn btn-light fw-bold py-3 text-start d-flex justify-content-between align-items-center" style="border-radius: 12px; border: 1px solid #cbd5e1; color: var(--text-main); transition: 0.2s;">
                            <span><i class="fas fa-book-open text-warning me-2"></i> Manage Subjects</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>

                        <a href="Profile.php" class="btn btn-light fw-bold py-3 text-start d-flex justify-content-between align-items-center" style="border-radius: 12px; border: 1px solid #cbd5e1; color: var(--text-main); transition: 0.2s;">
                            <span><i class="fas fa-shield-alt text-danger me-2"></i> Security Settings</span>
                            <i class="fas fa-chevron-right text-muted small"></i>
                        </a>
                    </div>
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