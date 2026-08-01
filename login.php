<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login - KDP</title>
    <!-- FontAwesome Link Add Kiya -->
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
            <form action="" method="POST">
                
                <label>Enrollment No. / User ID</label>
                <input type="text" id="user_id" name="user_id" placeholder="Enter Enrollment or User ID" required>

                <label>Password</label>
                <div class="password-box">
                    <input type="password" id="password" name="password" placeholder="Enter Password" required>
                    <!-- Yahan IMG hata kar FontAwesome Icon laga diya -->
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