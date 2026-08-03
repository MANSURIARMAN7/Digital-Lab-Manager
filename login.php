<?php
session_start();

$error = "";

// Agar user ne form submit kiya hai
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];

    // JSON file ka sahi rasta
    $json_file = 'users.json'; 
    
    if (file_exists($json_file)) {
        $users = json_decode(file_get_contents($json_file), true);
        $login_success = false;

        // Har user ko check karo
        foreach ($users as $user) {
            if ($user['user_id'] == $user_id && $user['password'] == $password) {
                
                // 🔥 YAHI WO JADUI LINE HAI JO PURANA KACHRA SAAF KAREGI!
                session_unset(); 
                
                // Login Pass! Session mein details save karo
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                // Subjects save karo (Ab ye naye format mein aayega)
                if (isset($user['subjects'])) {
                    $_SESSION['subjects'] = $user['subjects'];
                }

                $login_success = true;

                // Role ke hisaab se redirect karo
                if ($user['role'] == 'faculty') {
                    header("Location: Faculty/faculty_dashboard.php");
                    exit();
                } else if ($user['role'] == 'student') {
                    header("Location: student/stdashboard.php"); 
                    exit();
                } else if ($user['role'] == 'admin') {
                    header("Location: admin/adminpanel.php"); 
                    exit();
                }
            }
        }

        // Agar ID/Password match nahi hua
        if (!$login_success) {
            $error = "Invalid User ID or Password!";
        }
    } else {
        $error = "Database file not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login - KDP</title>
    <!-- FontAwesome Link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Version update taaki nayi CSS load ho -->
    <link rel="stylesheet" href="assets/css/login.css?v=9999">
</head>

<body>

    <div class="login-container">
        <!-- Left Side -->
        <div class="logo-section">
            <img src="assets/images/KDP-Logo.png" alt="KDP Logo">
            <h1>Welcome to KDP</h1>
            <p>
                Digital Lab Manual <br>
                & Expense Tracker
            </p>
        </div>

        <!-- Right Side -->
        <div class="login-box">
            <h2>Portal Login</h2>

            <!-- Yahan Error Message Dikhayega agar login fail hua -->
            <?php if($error != "") { ?>
                <div style="background: #fef2f2; color: #dc2626; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-weight: 600; border: 1px solid #fecaca;">
                    <?php echo $error; ?>
                </div>
            <?php } ?>

            <form action="" method="POST">
                
                <label>Enrollment No. / User ID</label>
                <input type="text" id="user_id" name="user_id" placeholder="Enter Enrollment or User ID" required>

                <label>Password</label>
                <div class="password-box">
                    <input type="password" id="password" name="password" placeholder="Enter Password" required>
                    <i class="fas fa-eye" id="togglePassword"></i>
                </div>

                <div class="login-options">
                    <label class="remember">
                        <input type="checkbox" name="remember">
                        Remember Me
                    </label>
                    <a href="#">Forgot Password?</a>
                </div>

                <button type="submit">Login</button>
            </form>
        </div>
    </div>

    <script src="assets/js/login.js?v=2"></script>
</body>

</html>