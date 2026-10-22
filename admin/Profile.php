<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$msg = "";

// ==========================================
// 1. UPDATE PERSONAL INFO LOGIC
// ==========================================
if (isset($_POST['update_profile'])) {
    $new_name = $conn->real_escape_string(trim($_POST['name']));
    $new_email = trim($_POST['email']);
    
    // Smart fix for empty emails (Same as other pages)
    $email_val = empty($new_email) ? "NULL" : "'" . $conn->real_escape_string($new_email) . "'";
    
    if ($conn->query("UPDATE users SET name='$new_name', email=$email_val WHERE user_id='$admin_id'")) {
        $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-check-circle me-2'></i> Profile information updated successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-times-circle me-2'></i> Failed to update profile. " . $conn->error . "</div>";
    }
}

// ==========================================
// 2. CHANGE PASSWORD LOGIC
// ==========================================
if (isset($_POST['change_password'])) {
    // Fetch current password from DB for verification
    $check_query = $conn->query("SELECT password FROM users WHERE user_id='$admin_id'");
    $db_password = $check_query->fetch_assoc()['password'];
    
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    
    if ($current_pass !== $db_password) {
        $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-exclamation-triangle me-2'></i> <b>Error:</b> Current password is incorrect!</div>";
    } elseif ($new_pass !== $confirm_pass) {
        $msg = "<div class='alert alert-warning shadow-sm'><i class='fas fa-exclamation-circle me-2'></i> <b>Error:</b> New password and confirmation do not match!</div>";
    } elseif (strlen($new_pass) < 6) {
        $msg = "<div class='alert alert-warning shadow-sm'><i class='fas fa-shield-alt me-2'></i> <b>Weak Password:</b> New password must be at least 6 characters long.</div>";
    } else {
        $safe_new_pass = $conn->real_escape_string($new_pass);
        if ($conn->query("UPDATE users SET password='$safe_new_pass' WHERE user_id='$admin_id'")) {
            $msg = "<div class='alert alert-success shadow-sm'><i class='fas fa-lock me-2'></i> Password changed successfully! Please use your new password next time you log in.</div>";
        } else {
            $msg = "<div class='alert alert-danger shadow-sm'><i class='fas fa-times-circle me-2'></i> Error updating password.</div>";
        }
    }
}

// ==========================================
// 3. FETCH LATEST ADMIN DATA
// ==========================================
$admin_query = $conn->query("SELECT * FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query->fetch_assoc();

// Format dates
$created_date = isset($admin_data['created_at']) && !empty($admin_data['created_at']) 
                ? date('d M Y, h:i A', strtotime($admin_data['created_at'])) 
                : 'Not Available';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - K.D. Polytechnic</title>
    
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

        /* Uniform body styling */
        * { box-sizing: border-box; }
        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        /* UNIFORM SIDEBAR */
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

        /* TOPBAR */
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }

        /* PROFILE CONTENT BOXES */
        .content-box { background: var(--surface); border-radius: 16px; padding: 30px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); margin-bottom: 25px;}
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px;}

        /* Avatar Section */
        .profile-avatar-large { width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #1e3a8a, #4338ca); color: white; display: flex; align-items: center; justify-content: center; font-size: 40px; box-shadow: 0 10px 20px rgba(67, 56, 202, 0.2); margin-bottom: 15px; border: 4px solid #e0e7ff;}
        
        .form-label { font-size: 12px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;}
        .form-control { border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 600; font-size: 14.5px; padding: 10px 15px;}
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);}
        .form-control[readonly] { background-color: #f8fafc; color: #64748b; cursor: not-allowed; }

        .btn-toggle-pass { cursor: pointer; color: #64748b; }
        .btn-toggle-pass:hover { color: var(--primary); }

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
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li class="active" onclick="window.location.href='Profile.php'"><i class="fas fa-user-shield"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <div class="topbar">
            <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
        </div>
        
        <?php echo $msg; ?>

        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1"><i class="fas fa-user-shield text-primary me-2"></i> Admin Profile</h3>
            <p class="text-muted fw-semibold small mb-0">Manage your account details and security settings.</p>
        </div>

        <div class="row">
            <!-- LEFT COLUMN: Overview & Account Info -->
            <div class="col-lg-4">
                
                <!-- 1. Profile Overview -->
                <div class="content-box text-center">
                    <div class="d-flex justify-content-center">
                        <div class="profile-avatar-large"><i class="fas fa-user-tie"></i></div>
                    </div>
                    <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($admin_data['name']); ?></h4>
                    <span class="badge bg-primary px-3 py-2 rounded-pill mt-2 fw-bold text-uppercase" style="letter-spacing: 0.5px;">System Administrator</span>
                    
                    <div class="mt-4 pt-3 border-top text-start">
                        <div class="mb-2">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Admin ID</span>
                            <span class="fw-bold text-dark"><i class="fas fa-id-badge text-primary me-2"></i><?php echo htmlspecialchars($admin_data['user_id']); ?></span>
                        </div>
                    </div>
                </div>

                <!-- 4. Account Information -->
                <div class="content-box">
                    <div class="box-title"><i class="fas fa-info-circle text-info"></i> Account Information</div>
                    <div class="mb-3">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Account Created On</span>
                        <span class="fw-bold text-dark"><i class="far fa-calendar-alt text-secondary me-2"></i><?php echo $created_date; ?></span>
                    </div>
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Account Status</span>
                        <span class="badge bg-success px-2 py-1"><i class="fas fa-check-circle me-1"></i> Active</span>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: Edit Forms -->
            <div class="col-lg-8">
                
                <!-- 2. Personal & Contact Information -->
                <div class="content-box">
                    <div class="box-title"><i class="fas fa-user-edit text-primary"></i> Personal Information</div>
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($admin_data['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Official Email <span class="text-muted fw-normal">(Optional)</span></label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($admin_data['email']); ?>" placeholder="admin@example.com">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Admin ID <span class="text-muted fw-normal">(Locked)</span></label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin_data['user_id']); ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">System Role <span class="text-muted fw-normal">(Locked)</span></label>
                                <input type="text" class="form-control" value="System Administrator" readonly>
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary fw-bold px-4"><i class="fas fa-save me-2"></i> Save Changes</button>
                    </form>
                </div>

                <!-- 3. Security Settings -->
                <div class="content-box mb-0">
                    <div class="box-title"><i class="fas fa-lock text-warning"></i> Security Settings</div>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="curr_pass" class="form-control border-end-0" required placeholder="Enter current password">
                                <span class="input-group-text bg-white btn-toggle-pass border-start-0" onclick="togglePass('curr_pass', this)"><i class="fas fa-eye"></i></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="new_password" id="new_pass" class="form-control border-end-0" required minlength="6" placeholder="At least 6 characters">
                                    <span class="input-group-text bg-white btn-toggle-pass border-start-0" onclick="togglePass('new_pass', this)"><i class="fas fa-eye"></i></span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="conf_pass" class="form-control border-end-0" required minlength="6" placeholder="Repeat new password">
                                    <span class="input-group-text bg-white btn-toggle-pass border-start-0" onclick="togglePass('conf_pass', this)"><i class="fas fa-eye"></i></span>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-dark fw-bold px-4"><i class="fas fa-key me-2"></i> Update Password</button>
                    </form>
                </div>

            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password Toggle Logic
        function togglePass(inputId, iconElement) {
            var input = document.getElementById(inputId);
            var icon = iconElement.querySelector('i');
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Live Clock
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

sar gaama pa 