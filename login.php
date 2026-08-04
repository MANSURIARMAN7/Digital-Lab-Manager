<?php
session_start();
include 'db.php'; // 🔥 Live Database Connection

// Agar pehle se login hai, toh uske dashboard par bhej do
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['role'] === 'faculty') {
        header("Location: Faculty/faculty_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: student/stdashboard.php");
        exit();
    }
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $conn->real_escape_string($_POST['user_id']);
    $password = $_POST['password'];

    // 🚀 MySQL se seedha User check kar rahe hain
    $query = "SELECT * FROM users WHERE user_id = '$user_id' AND password = '$password'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Purana session saaf karo
        session_unset();
        
        // Session set kar rahe hain
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = strtolower($user['role']);
        
        // Subjects save karo
        if (isset($user['subjects']) && !empty($user['subjects'])) {
            $decoded = json_decode($user['subjects'], true);
            if (is_array($decoded)) {
                $_SESSION['subjects'] = $decoded;
            } else {
                $_SESSION['subjects'] = array_map('trim', explode(',', $user['subjects']));
            }
        }
        
        // Role ke hisaab se redirect
        if ($_SESSION['role'] == 'faculty') {
            header("Location: Faculty/faculty_dashboard.php");
        } elseif ($_SESSION['role'] == 'student') {
            header("Location: student/stdashboard.php");
        } else {
            header("Location: admin/dashboard.php"); // Redirect to modular admin dashboard
        }
        exit();
    } else {
        $error = "Invalid User ID or Password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - K.D. Polytechnic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; }
        
        .login-container { background: #ffffff; width: 400px; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-top: 5px solid #113460; text-align: center; }
        
        /* Circular Logo Design */
        .logo-wrapper { width: 90px; height: 90px; background: #ffffff; border-radius: 50%; margin: 0 auto 20px auto; display: flex; align-items: center; justify-content: center; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 2px solid #e2e8f0; }
        .logo-wrapper img { width: 105%; height: auto; }
        
        h2 { color: #0f172a; font-size: 24px; margin-bottom: 5px; }
        p { color: #64748b; font-size: 14px; margin-bottom: 25px; }
        
        .input-group { position: relative; margin-bottom: 20px; text-align: left; }
        .input-group label { display: block; font-size: 13px; color: #1e293b; font-weight: 600; margin-bottom: 5px; }
        .input-group i { position: absolute; left: 15px; top: 35px; color: #94a3b8; }
        .input-group input { width: 100%; padding: 12px 15px 12px 40px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; background: #f8fafc; transition: 0.3s; outline: none; }
        .input-group input:focus { border-color: #2563eb; background: #ffffff; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        
        .error-msg { background: #fee2e2; color: #dc2626; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; justify-content: center; }
        
        .btn-login { width: 100%; background: #113460; color: #ffffff; border: none; padding: 12px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-login:hover { background: #1e4b85; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(17, 52, 96, 0.2); }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="logo-wrapper">
            <!-- Ensure KDP Logo path is correct -->
            <img src="assets/images/KDP-Logo.png" alt="KDP Logo">
        </div>
        <h2>Welcome Back</h2>
        <p>Login to KDP Digital Lab Manager</p>

        <?php if (!empty($error)) { ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php } ?>

        <form action="" method="POST">
            <div class="input-group">
                <label for="user_id">Enrollment No. / Faculty ID</label>
                <i class="fas fa-user"></i>
                <input type="text" name="user_id" id="user_id" placeholder="Enter your ID" required>
            </div>
            
            <div class="input-group">
                <label for="password">Password</label>
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" class="btn-login">Secure Login</button>
        </form>
    </div>

</body>
</html>