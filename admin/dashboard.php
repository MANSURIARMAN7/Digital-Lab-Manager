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

// DYNAMIC METRICS FOR OVERVIEW CARDS
$st_count = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'] ?? 0;
$fac_count = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='faculty'")->fetch_assoc()['c'] ?? 0;
$sub_count = $conn->query("SELECT COUNT(*) as c FROM subjects")->fetch_assoc()['c'] ?? 0;
$man_count = $conn->query("SELECT COUNT(*) as c FROM lab_manuals")->fetch_assoc()['c'] ?? 0;

// FETCH RECENTLY ADDED USERS
$recent_users = $conn->query("SELECT name, user_id, email, department, role FROM users WHERE role != 'admin' ORDER BY user_id DESC LIMIT 5");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - K.D. Polytechnic</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 270px;
            --primary: #4338ca;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* FIX: Uniform body styling to prevent scrollbar shifting */
        * { box-sizing: border-box; }
        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        /* SIDEBAR (Perfectly Uniform) */
        .sidebar { width: var(--sidebar-width); min-width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}

        /* MAIN CONTENT */
        /* FIX: Ensure overflow-y is scroll so width remains constant */
        .main { flex: 1; padding: 30px 45px; overflow-y: scroll; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

        /* HOD PROFILE PILL */
        .profile-pill { display: flex; align-items: center; background: linear-gradient(135deg, #1e3a8a, #4338ca); padding: 12px 25px 12px 30px; border-radius: 50px; cursor: pointer; text-decoration: none; color: white; transition: var(--transition-bounce); box-shadow: 0 10px 20px rgba(67, 56, 202, 0.2); border: 2px solid rgba(255,255,255,0.1);}
        .profile-pill:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(67, 56, 202, 0.3); border-color: rgba(255,255,255,0.3);}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 11px; color: #bfdbfe; font-weight: 800; text-transform: uppercase; margin-bottom: 2px; letter-spacing: 0.5px;}
        .profile-name { margin: 0; font-size: 18px; color: white; font-weight: 900;}
        .profile-avatar { background: white; color: var(--primary); width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);}

        /* OVERVIEW CARDS */
        .stat-card { background: var(--surface); border-radius: 16px; padding: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: var(--transition-bounce); cursor: pointer; position: relative; overflow: hidden;}
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px -5px rgba(0,0,0,0.05); border-color: var(--primary); }
        .stat-card::after { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; }
        .card-blue::after { background: #3b82f6; }
        .card-purple::after { background: #8b5cf6; }
        .card-green::after { background: #10b981; }
        .card-yellow::after { background: #f59e0b; }
        
        .stat-info h4 { font-size: 28px; font-weight: 900; color: var(--text-main); margin: 0; line-height: 1;}
        .stat-info span { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; margin-bottom: 5px; display: block;}
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }

        /* SECTIONS */
        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); height: 100%;}
        .box-title { font-size: 16px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;}

        /* Table Styles */
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 12px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 13.5px; font-weight: 600; padding: 12px 10px; border-bottom: 1px solid #f1f5f9; color: var(--text-main);}
        .badge-role { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .role-student { background: #dbeafe; color: #1e40af; }
        .role-faculty { background: #fef3c7; color: #b45309; }

        /* Quick Action Buttons */
        .btn-quick { display: flex; align-items: center; gap: 15px; width: 100%; padding: 15px; background: var(--bg-body); border: 1px solid #e2e8f0; border-radius: 12px; color: var(--text-main); font-weight: 700; text-decoration: none; transition: 0.2s; margin-bottom: 15px;}
        .btn-quick:hover { background: white; border-color: var(--primary); color: var(--primary); transform: translateX(5px); box-shadow: 0 4px 10px rgba(0,0,0,0.03);}
        .btn-quick i { font-size: 20px; width: 30px; text-align: center; }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <!-- UNIFORM SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
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
            <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            
            <a href="Profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Welcome Back,</span>
                    <h4 class="profile-name">Head of Department</h4>
                </div>
                <div class="profile-avatar"><i class="fas fa-user-tie"></i></div>
            </a>
        </div>

        <div class="mb-4">
            <h3 class="fw-bold text-dark">Admin Overview</h3>
            <p class="text-muted fw-semibold">Manage system records and track user data.</p>
        </div>

        <!-- DYNAMIC OVERVIEW CARDS -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card card-blue" onclick="window.location.href='Student_Mgmt.php'">
                    <div class="stat-info"><span>Total Students</span><h4><?php echo $st_count; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-user-graduate"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-purple" onclick="window.location.href='faculty_mgmt.php'">
                    <div class="stat-info"><span>Total Faculty</span><h4><?php echo $fac_count; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;"><i class="fas fa-chalkboard-teacher"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-green" onclick="window.location.href='subject_mgmt.php'">
                    <div class="stat-info"><span>Total Subjects</span><h4><?php echo $sub_count; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-book-open"></i></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card-yellow">
                    <div class="stat-info"><span>Lab Manuals</span><h4><?php echo $man_count; ?></h4></div>
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-file-pdf"></i></div>
                </div>
            </div>
        </div>

        <!-- BOTTOM SECTIONS -->
        <div class="row g-4">
            <!-- RECENTLY ADDED USERS TABLE -->
            <div class="col-lg-8">
                <div class="content-box p-0">
                    <div class="box-title px-4 pt-4 pb-3 m-0">
                        <span><i class="fas fa-users text-primary me-2"></i> Recently Added Users</span>
                        <a href="Student_Mgmt.php" class="btn btn-sm btn-outline-primary fw-bold px-3">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">User Info</th>
                                    <th>Department</th>
                                    <th>System Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($recent_users && $recent_users->num_rows > 0): ?>
                                    <?php while($row = $recent_users->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold"><?php echo htmlspecialchars($row['name']); ?></div>
                                                <div class="text-muted small mt-1"><i class="fas fa-id-card me-1"></i> <?php echo htmlspecialchars($row['user_id']); ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($row['department']); ?></div>
                                            </td>
                                            <td>
                                                <span class="badge-role role-<?php echo htmlspecialchars($row['role']); ?>">
                                                    <?php echo htmlspecialchars($row['role']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted fw-bold">No recent users found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="col-lg-4">
                <div class="content-box">
                    <div class="box-title">
                        <span><i class="fas fa-bolt text-warning me-2"></i> Quick Actions</span>
                    </div>
                    <div class="d-flex flex-column">
                        <a href="Student_Mgmt.php" class="btn-quick">
                            <i class="fas fa-user-plus text-primary"></i> Add New Student
                        </a>
                        <a href="faculty_mgmt.php" class="btn-quick">
                            <i class="fas fa-chalkboard-teacher text-success"></i> Add New Faculty
                        </a>
                        <a href="subject_mgmt.php" class="btn-quick m-0">
                            <i class="fas fa-book text-danger"></i> Manage Subjects
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live Clock Script
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