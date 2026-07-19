<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account - KDP</title>

    <link rel="stylesheet" href="assets/css/register.css">

</head>

<body>

    <div class="register-container">

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

        <div class="register-box">

            <h2>Create Account</h2>

            <form action="" method="POST">

                <label>Full Name (As Per ID Card)</label>

                <input
                    type="text"
                    id="fullname"
                    name="fullname"
                    placeholder="Enter Full Name"
                    required>

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

                <label>Email Address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter Email Address"
                    required>

                <label>Mobile Number</label>

                <input
                    type="tel"
                    id="mobile"
                    name="mobile"
                    placeholder="Enter 10 Digit Mobile Number"
                    maxlength="10"
                    pattern="[0-9]{10}"
                    required>

                <label>Semester</label>

                <select
                    id="semester"
                    name="semester"
                    required>

                    <option value="">Select Semester</option>

                    <option>Semester 1</option>
                    <option>Semester 2</option>
                    <option>Semester 3</option>
                    <option>Semester 4</option>
                    <option>Semester 5</option>
                    <option>Semester 6</option>

                </select>
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


                <label>Confirm Password</label>

                <div class="password-box">

                    <input
                        type="password"
                        id="confirmPassword"
                        name="confirmPassword"
                        placeholder="Confirm Password"
                        required>

                    <img
                        src="assets/icons/eye.png"
                        id="toggleConfirmPassword"
                        alt="Show Password">

                </div>


                <button type="submit">
                    Create Account
                </button>


                <p class="login-link">
                    Already have an account?
                    <a href="login.php">Login</a>
                </p>

            </form>

        </div>

    </div>

    <script src="assets/js/register.js"></script>

</body>

</html>