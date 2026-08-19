<?php
session_start();
include '../db.php'; // Apne database connection ka sahi path daalna

// Check agar admin logged in hai ya nahi
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$admin_id = $_SESSION['user_id'];
$message = "";

// 🚀 PASSWORD UPDATE LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $old_password = $conn->real_escape_string($_POST['old_password']);
    $new_password = $conn->real_escape_string($_POST['new_password']);
    $confirm_password = $conn->real_escape_string($_POST['confirm_password']);

    if ($new_password === $confirm_password) {
        // Purana password check karo
        $check_query = "SELECT password FROM users WHERE user_id = '$admin_id'";
        $res = $conn->query($check_query);
        
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if ($row['password'] === $old_password) {
                // Password match ho gaya, ab naya set karo
                $update_query = "UPDATE users SET password = '$new_password' WHERE user_id = '$admin_id'";
                if ($conn->query($update_query)) {
                    $message = '<div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle-fill me-2"></i> Password updated successfully! 🎉<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                } else {
                    $message = '<div class="alert alert-danger">Error updating password!</div>';
                }
            } else {
                $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bi bi-x-circle-fill me-2"></i> Incorrect Old Password! ❌<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
            }
        }
    } else {
        $message = '<div class="alert alert-warning alert-dismissible fade show" role="alert">New Password and Confirm Password do not match! ⚠️<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
}

// Fetch Admin Details
$admin_query = "SELECT * FROM users WHERE user_id = '$admin_id'";
$admin_data = $conn->query($admin_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - KDP</title>
    <!-- Bootstrap CSS (Tere project me jo ho wo use kar lena) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .admin-avatar { width: 120px; height: 120px; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 3rem; border-radius: 50%; margin: 0 auto; box-shadow: 0 5px 15px rgba(30, 60, 114, 0.3); }
        .bg-admin-header { background-color: #1e3c72; color: white; padding: 20px; border-radius: 15px 15px 0 0; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12 text-center">
            <h2 class="fw-bold text-dark"><i class="bi bi-person-badge-fill text-primary"></i> Administrator Profile</h2>
            <p class="text-muted">Manage your admin credentials</p>
        </div>
    </div>

    <!-- Message Alert Box -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?= $message; ?>
        </div>
    </div>

    <div class="row justify-content-center">
        <!-- Left Side: Profile Card -->
        <div class="col-md-4 mb-4">
            <div class="card card-custom h-100 text-center overflow-hidden">
                <div class="bg-admin-header mb-4">
                    <h5 class="mb-0">System Admin</h5>
                </div>
                <div class="card-body">
                    <div class="admin-avatar mb-3">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($admin_data['name'] ?? 'Admin'); ?></h4>
                    <p class="text-muted mb-3">ID: <?= htmlspecialchars($admin_data['user_id']); ?></p>
                    <span class="badge bg-success px-3 py-2 rounded-pill"><i class="bi bi-check-circle me-1"></i> Full Access Active</span>
                </div>
            </div>
        </div>

        <!-- Right Side: Password Update Card -->
        <div class="col-md-8 mb-4">
            <div class="card card-custom h-100">
                <div class="card-body p-4 p-md-5">
                    <h4 class="fw-bold mb-4 text-danger"><i class="bi bi-key-fill me-2"></i> Update Security Credentials</h4>
                    <p class="text-muted mb-4">Please ensure your new password is secure. We recommend using a mix of letters and numbers.</p>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-unlock-fill text-muted"></i></span>
                                <input type="password" name="old_password" class="form-control border-start-0 bg-light" placeholder="Enter current password" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-muted"></i></span>
                                    <input type="password" name="new_password" class="form-control border-start-0 bg-light" placeholder="New password" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label fw-bold">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-muted"></i></span>
                                    <input type="password" name="confirm_password" class="form-control border-start-0 bg-light" placeholder="Confirm password" required>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="update_password" class="btn btn-primary btn-lg rounded-3 fw-bold">
                                <i class="bi bi-save me-2"></i> Save New Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>