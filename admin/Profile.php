<?php
session_start();
include '../db.php'; // Database connection

// Security Check: Ensure only logged-in admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$message = "";

// ==========================================
// 🚀 PROFILE UPDATE LOGIC (Name & Email)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $admin_name = trim($_POST['admin_name']);
    $admin_email = trim($_POST['admin_email']);

    // Using Prepared Statements for security
    $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
    $update_stmt->bind_param("sss", $admin_name, $admin_email, $admin_id);
    
    if ($update_stmt->execute()) {
        $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-person-check-fill me-2"></i> Profile details updated successfully! 🎉
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    } else {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Error updating profile!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    }
    $update_stmt->close();
}

// ==========================================
// 🚀 SECURE PASSWORD UPDATE LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        // Fetch current password hash securely
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Validate against hashed password (or plain text fallback if not hashed yet)
            // Note: password_verify requires passwords in DB to be hashed using password_hash()
            if (password_verify($old_password, $row['password']) || $row['password'] === $old_password) {
                
                // Hash the new password before saving
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $update_pass_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $update_pass_stmt->bind_param("ss", $hashed_new_password, $admin_id);
                
                if ($update_pass_stmt->execute()) {
                    $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="bi bi-shield-check me-2"></i> Password secured and updated! 🔒
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>';
                }
                $update_pass_stmt->close();
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-x-circle-fill me-2"></i> Incorrect Old Password! ❌
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>';
            }
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i> New Password and Confirm Password do not match! ⚠️
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>';
    }
}

// ==========================================
// Fetch Admin Details for Display
// ==========================================
$fetch_stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$fetch_stmt->bind_param("s", $admin_id);
$fetch_stmt->execute();
$admin_data = $fetch_stmt->get_result()->fetch_assoc();
$fetch_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOD Profile - Digital Lab Manager</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card-custom:hover { transform: translateY(-2px); }
        .admin-avatar { width: 110px; height: 110px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 3rem; border-radius: 50%; margin: 0 auto; box-shadow: 0 5px 15px rgba(30, 60, 114, 0.3); border: 4px solid #fff; }
        .bg-admin-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 20px; border-radius: 15px 15px 0 0; }
        .toggle-password { cursor: pointer; }
    </style>
</head>
<body>

    <div class="container py-5">
        <div class="row mb-4 align-items-center">
            <div class="col-md-6">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-2 rounded-3 fw-bold">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Dashboard
                </a>
                <h2 class="fw-bold text-dark mb-0"><i class="fa-solid fa-user-shield text-primary me-2"></i> HOD Profile</h2>
                <p class="text-muted mt-1">Manage your department credentials and personal information</p>
            </div>
        </div>

        <!-- Message Alert Box -->
        <div class="row">
            <div class="col-12">
                <?= $message; ?>
            </div>
        </div>

        <div class="row">
            <!-- Left Side: Profile Card -->
            <div class="col-lg-4 mb-4">
                <div class="card card-custom h-100 text-center overflow-hidden">
                    <div class="bg-admin-header mb-4">
                        <h5 class="mb-0 fw-bold">Computer Engineering</h5>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="admin-avatar mb-3 position-relative" style="top: -30px;">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <h4 class="fw-bold mb-1 mt-n3"><?= htmlspecialchars($admin_data['name'] ?? 'Head of Department'); ?></h4>
                        <p class="text-muted mb-3">ID: <span class="fw-semibold text-dark"><?= htmlspecialchars($admin_data['user_id'] ?? 'Admin'); ?></span></p>
                        <span class="badge bg-success px-3 py-2 rounded-pill mb-3 shadow-sm"><i class="bi bi-check-circle me-1"></i> HOD Access Active</span>
                        
                        <div class="text-start mt-4 bg-light p-3 rounded-3">
                            <p class="small text-muted mb-2"><i class="fa-solid fa-envelope me-2 text-primary"></i> <strong>Email:</strong> <?= htmlspecialchars($admin_data['email'] ?? 'Not Provided'); ?></p>
                            <p class="small text-muted mb-2"><i class="fa-solid fa-building me-2 text-primary"></i> <strong>Department:</strong> Computer Engineering</p>
                            <p class="small text-muted mb-0"><i class="fa-solid fa-graduation-cap me-2 text-primary"></i> <strong>Institution:</strong> K. D. Polytechnic</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Forms -->
            <div class="col-lg-8 mb-4">
                
                <!-- Profile Details Update Card -->
                <div class="card card-custom mb-4">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-person-lines-fill me-2 text-primary"></i> Update Profile Details</h5>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-muted">Full Name</label>
                                    <input type="text" name="admin_name" class="form-control bg-light" value="<?= htmlspecialchars($admin_data['name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-muted">Email Address</label>
                                    <input type="email" name="admin_email" class="form-control bg-light" value="<?= htmlspecialchars($admin_data['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="update_profile" class="btn btn-primary px-4 rounded-3 fw-bold">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Password Update Card -->
                <div class="card card-custom h-100">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3 text-danger"><i class="bi bi-shield-lock-fill me-2"></i> Security Credentials</h5>
                        <p class="text-muted small mb-4">Please ensure your new password is at least 8 characters long and contains a mix of letters and numbers.</p>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-muted">Current Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-unlock-fill text-muted"></i></span>
                                    <input type="password" name="old_password" id="old_password" class="form-control border-start-0 border-end-0 bg-light" placeholder="Enter current password" required>
                                    <span class="input-group-text bg-light border-start-0 toggle-password" onclick="togglePassword('old_password', this)"><i class="bi bi-eye-slash text-muted"></i></span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold small text-muted">New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-muted"></i></span>
                                        <input type="password" name="new_password" id="new_password" class="form-control border-start-0 border-end-0 bg-light" placeholder="New password" required>
                                        <span class="input-group-text bg-light border-start-0 toggle-password" onclick="togglePassword('new_password', this)"><i class="bi bi-eye-slash text-muted"></i></span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold small text-muted">Confirm New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-muted"></i></span>
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control border-start-0 border-end-0 bg-light" placeholder="Confirm password" required>
                                        <span class="input-group-text bg-light border-start-0 toggle-password" onclick="togglePassword('confirm_password', this)"><i class="bi bi-eye-slash text-muted"></i></span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="update_password" class="btn btn-danger btn-lg rounded-3 fw-bold shadow-sm">
                                    <i class="bi bi-key-fill me-2"></i> Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // JavaScript to toggle password visibility
    function togglePassword(inputId, iconElement) {
        var input = document.getElementById(inputId);
        var icon = iconElement.querySelector('i');
        
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("bi-eye-slash");
            icon.classList.add("bi-eye");
        } else {
            input.type = "password";
            icon.classList.remove("bi-eye");
            icon.classList.add("bi-eye-slash");
        }
    }
</script>
</body>
</html>
