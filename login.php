<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - KDP</title>

    <link rel="stylesheet" href="assets/css/login.css">
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

            <h2>Student Login</h2>

            <form action="" method="POST">

                <label>Enrollment Number</label>

                <input
                    type="text"
                    id="enrollment"
                    name="enrollment"
                    placeholder="Enter 12 Digit Enrollment Number"
                    maxlength="12"
                    minlength="12"
                    pattern="[0-9]{12}"
                    required>

                <label>Password</label>

                <div class="password-box">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter Password"
                        required>

                    <img
                        src="assets/icons/eye.png"
                        id="togglePassword"
                        alt="Show Password">

                </div>

                <div class="login-options">

                    <label class="remember">

                        <input type="checkbox" name="remember">

                        Remember Me

                    </label>

                    <a href="#">Forgot Password?</a>

                </div>

                <button type="submit">
                    Login
                </button>

                <p class="register-link">

                    Don't have an account?

                    <a href="register.php">Create Account</a>

                </p>

            </form>

        </div>

    </div>

    <script src="assets/js/login.js"></script>

</body>

</html>