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

        <div class="logo-section">
            <img src="assets/images/kdp-logo.png" alt="KDP Logo">

            <h1>Welcome to KDP</h1>

            <p>
                Digital Lab Manual & Expense Tracker
            </p>
        </div>


        <div class="register-box">

            <h2>Create Account</h2>

            <form>

                <label>Full Name(As Per Id Card)</label>
                <input type="text" placeholder="Enter your full name">


                <label>Enrollment Number</label>
                <input type="text" placeholder="Enter enrollment number">


                <label>Email</label>
                <input type="email" placeholder="Enter email">


                <label>Mobile Number</label>
                <input type="tel" placeholder="Enter mobile number">


                <label>Semester</label>
                <select>
                    <option>Select Semester</option>
                    <option>Semester 1</option>
                    <option>Semester 2</option>
                    <option>Semester 3</option>
                    <option>Semester 4</option>
                    <option>Semester 5</option>
                    <option>Semester 6</option>
                </select>


                <label>Password</label>

                <div class="password-box">
                    <input type="password" id="password" placeholder="Enter password">

                    <img src="assets/icons/eye.png" id="eye">
                </div>


                <label>Confirm Password</label>

                <input type="password" placeholder="Confirm password">


                <button type="submit">
                    Create Account
                </button>


                <p class="login-link">
                    Already have an account?
                    <a href="#">Login</a>
                </p>


            </form>

        </div>


    </div>


<script src="assets/js/register.js"></script>

</body>

</html>