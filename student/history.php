<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security Check: Agar student logged in nahi hai toh login page par bhejo
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] != true || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'] ?? '';
$users_file = '../users.json';
$success_msg = '';
$error_msg = '';

// 1. Data Update Logic (Jab form submit hoga)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $new_phone = trim($_POST['phone']); // Agar phone ka column hai

    if (file_exists($users_file)) {
        $users_data = json_decode(file_get_contents($users_file), true);
        $updated = false;

        foreach ($users_data as &$user) {
            if ($user['user_id'] == $student_id) {
                $user['name'] = $new_name;
                $user['email'] = $new_email;
                if (!empty($new_phone)) {
                    $user['phone'] = $new_phone;
                }
                  // Profile Picture Upload Logic
                if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $pic_name = 'student_' . $student_id . '_' . time() . '.' . $ext;
                        $upload_dir = '../uploads/profile_pics/';
                        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $pic_name);
                        $user['profile_pic'] = $pic_name; // Save pic name in JSON
                    }
                }

                $updated = true;
                break;
            }
        }
        unset($user); // Break reference

        if ($updated) {
            file_put_contents($users_file, json_encode($users_data, JSON_PRETTY_PRINT));
            $success_msg = "Profile updated successfully!";
            // Session update karo taaki sidebar mein naya naam dikhe
            $_SESSION['name'] = $new_name;
            $_SESSION['email'] = $new_email;
        } else {
            $error_msg = "User data not found in database.";
        }
    }
}
// 2. Current Data Load Karne ka Logic (Pehle se saved data)
$student_name = "Student";
$student_email = "";
$student_branch = "Computer Engineering";
$student_sem = "Semester 5";
$student_phone = "";
$profile_pic = "";
if (file_exists($users_file)) {
    $users_data = json_decode(file_get_contents($users_file), true);
    foreach ($users_data as $user) {
        if ($user['user_id'] == $student_id) {
            $student_name = $user['name'] ?? $student_name;
            $student_email = $user['email'] ?? "";
            $student_branch = $user['branch'] ?? $student_branch;
            $student_sem = $user['sem'] ?? $student_sem;
            $student_phone = $user['phone'] ?? "";
            $profile_pic = $user['profile_pic'] ?? "";
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | K.D. Polytechnic</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .profile-card { background: #fff; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: none; }
        .profile-header { background: linear-gradient(135deg, #0d6efd, #0a58ca); border-radius: 15px 15px 0 0; color: #fff; padding: 30px; }
        .profile-img { width: 120px; height: 120px; border: 5px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .form-label { font-weight: 600; color: #333; }
        .btn-primary { background-color: #0d6efd; border-radius: 8px; padding: 10px 25px; }
        .text-kd { color: #0d6efd; font-weight: bold; }
    </style>
</head>
<body>

<!-- Ye Navbar tumhare 'my-manuals.php' ya 'dashboard.php' jaisa hi hai, bas link change kiya hai -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-university me-2"></i>K.D. Polytechnic</a>
    <div class="ms-auto">
        <span class="navbar-text text-white me-3">Welcome, <?php echo htmlspecialchars($student_name); ?></span>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <?php if($success_msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if($error_msg): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card profile-card">
                <div class="profile-header text-center">
                    <div class="d-flex justify-content-center mb-3">
                        <?php if($profile_pic): ?>
                            <img src="../uploads/profile_pics/<?php echo $profile_pic; ?>" class="rounded-circle profile-img" alt="Profile">
                        <?php else: ?>
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student_name); ?>&background=0D6EFD&color=fff&size=128" class="rounded-circle profile-img" alt="Profile">
                        <?php endif; ?>
                    </div>
                    <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($student_name); ?></h4>
                    <p class="mb-1"><?php echo htmlspecialchars($student_branch); ?> | <?php echo htmlspecialchars($student_sem); ?></p>
                    <small><i class="fas fa-id-card me-1"></i> ID: <?php echo htmlspecialchars($student_id); ?></small>
                </div>

                <div class="card-body p-4">
                    <h5 class="mb-4 text-kd">Update Personal Details</h5>
                    <form method="POST" action="" enctype="multipart/form-data">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($student_name); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($student_email); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($student_phone); ?>" placeholder="Enter phone number">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Branch / Semester (Read-Only)</label>
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($student_branch); ?> / <?php echo htmlspecialchars($student_sem); ?>" disabled>
                            </div>
                            
                            <div class="col-12 mt-3">
                                <label class="form-label">Change Profile Picture (Optional)</label>
                                <input type="file" class="form-control" name="profile_pic" accept="image/*">
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



