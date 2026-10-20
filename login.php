<?php
session_start();
include 'db.php'; // Database connection

// Agar pehle se login hai, toh seedha dashboard par bhej do
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['role'] === 'faculty') {
        header("Location: faculty/faculty_dashboard.php");
        exit();
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: student/Stdashboard.php");
        exit();
    } else {
        header("Location: admin/dashboard.php");
        exit();
    }
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_input = $conn->real_escape_string(trim($_POST['user_id']));
    $password = trim($_POST['password']);

    // Flexible Query: Email, short ID, ya Name teeno se check karega
    $query = "SELECT * FROM users WHERE email = '$user_input' OR user_id = '$user_input' OR name LIKE '%$user_input%'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $db_password = $user['password'];

        // Smart Password Check (Supports Plain Text, MD5, & Hashed Password)
        if ($password === $db_password || md5($password) === $db_password || password_verify($password, $db_password)) {
            
            // Purane session saaf karo
            session_unset();

            // Naye session set karo
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = strtolower(trim($user['role']));

            // Role ke hisaab se redirect
            if ($_SESSION['role'] === 'faculty') {
                header("Location: faculty/faculty_dashboard.php");
            } elseif ($_SESSION['role'] === 'student') {
                header("Location: student/Stdashboard.php");
            } else {
                header("Location: admin/dashboard.php"); 
            }
            exit();
        } else {
            $error = "Invalid Password! Please try again.";
        }
    } else {
        $error = "User ID / Enrollment not found in database!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login - K.D. Polytechnic</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4338ca;
            --primary-dark: #312e81;
            --surface: #ffffff;
            --bg-body: #f1f5f9;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); color: var(--text-main); height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }

        .login-wrapper { background: var(--surface); border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15); overflow: hidden; width: 1000px; max-width: 95%; display: flex; min-height: 600px; animation: scaleUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes scaleUp { 0% { opacity: 0; transform: scale(0.95) translateY(20px); } 100% { opacity: 1; transform: scale(1) translateY(0); } }

        .login-graphic { width: 45%; background: linear-gradient(135deg, #1e3a8a 0%, #4338ca 100%); color: white; padding: 50px 40px; display: flex; flex-direction: column; justify-content: space-between; position: relative; overflow: hidden;}
        .login-graphic::before { content: ''; position: absolute; top: -50px; left: -50px; width: 250px; height: 250px; background: rgba(255,255,255,0.05); border-radius: 50%; }
        .login-graphic::after { content: ''; position: absolute; bottom: -100px; right: -50px; width: 350px; height: 350px; background: rgba(255,255,255,0.05); border-radius: 50%; }
        
        .brand-info { position: relative; z-index: 2; }
        .brand-info img { width: 70px; height: 70px; border-radius: 50%; padding: 3px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.5); margin-bottom: 20px;}
        .brand-info h2 { font-weight: 900; font-size: 28px; line-height: 1.2; margin-bottom: 15px;}
        .brand-info p { font-size: 15px; font-weight: 500; opacity: 0.8; line-height: 1.6;}
        
        .system-badge { background: rgba(255,255,255,0.1); backdrop-filter: blur(5px); padding: 15px 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.2); position: relative; z-index: 2; }
        .system-badge i { font-size: 24px; color: #a5b4fc; margin-bottom: 10px;}
        .system-badge h6 { font-weight: 800; margin: 0; font-size: 14px; letter-spacing: 0.5px;}

        .login-form-area { width: 55%; padding: 60px 70px; display: flex; flex-direction: column; justify-content: center; }
        
        .form-header { margin-bottom: 35px; }
        .form-header h3 { font-size: 32px; font-weight: 900; color: var(--text-main); margin-bottom: 8px;}
        .form-header p { font-size: 15px; color: var(--text-muted); font-weight: 600;}

        .form-label { font-size: 12px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; margin-bottom: 8px;}
        
        .input-group-custom { position: relative; margin-bottom: 25px; }
        .input-group-custom i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; transition: 0.3s; z-index: 2;}
        .form-control { width: 100%; border-radius: 12px; border: 2px solid #e2e8f0; padding: 15px 15px 15px 45px; font-size: 15px; font-weight: 600; color: var(--text-main); background: #f8fafc; transition: var(--transition-bounce);}
        .form-control:focus { border-color: var(--primary); background: white; outline: none; box-shadow: 0 0 0 4px rgba(67, 56, 202, 0.1);}
        .form-control:focus + i { color: var(--primary); }

        .btn-toggle-pass { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0; font-size: 15px; z-index: 2; transition: 0.2s;}
        .btn-toggle-pass:hover { color: var(--primary); }

        .btn-submit { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; border: none; padding: 16px; width: 100%; border-radius: 12px; font-size: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: var(--transition-bounce); box-shadow: 0 10px 20px rgba(67, 56, 202, 0.2); margin-top: 10px;}
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 30px rgba(67, 56, 202, 0.3); }

        .alert-custom { border-radius: 10px; font-weight: 700; font-size: 14px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .back-home { display: inline-flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 14px; font-weight: 700; text-decoration: none; margin-top: 25px; justify-content: center; width: 100%; transition: 0.2s;}
        .back-home:hover { color: var(--primary); }

        @media (max-width: 991px) {
            .login-wrapper { flex-direction: column; width: 500px; }
            .login-graphic { width: 100%; padding: 40px 30px; text-align: center; }
            .login-graphic::before, .login-graphic::after { display: none; }
            .brand-info img { margin: 0 auto 15px; }
            .system-badge { display: none; }
            .login-form-area { width: 100%; padding: 40px 30px; }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-graphic">
            <div class="brand-info">
                <img src="assets/images/college-logo.png" alt="KDP Logo">
                <h2>Digital Lab<br>Manager</h2>
                <p>Secure login portal for Students, Faculties, and Administrators of K.D. Polytechnic.</p>
            </div>
            <div class="system-badge">
                <i class="fas fa-shield-check"></i>
                <h6>End-to-End Secure Platform</h6>
                <div class="small mt-1 opacity-75 fw-medium">All data mapped & encrypted securely.</div>
            </div>
        </div>

        <div class="login-form-area">
            <div class="form-header">
                <h3>Welcome Back</h3>
                <p>Enter your credentials to access your account.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-custom mb-4 p-3 d-flex align-items-center gap-2">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="mb-4">
                    <label class="form-label">User ID / Enrollment / Email</label>
                    <div class="input-group-custom">
                        <input type="text" name="user_id" class="form-control" placeholder="e.g. 236170307001 or admin" required autocomplete="off">
                        <i class="fas fa-user"></i>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group-custom">
                        <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter your password" required>
                        <i class="fas fa-lock"></i>
                        <button type="button" class="btn-toggle-pass" onclick="togglePassword()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    Sign In <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </form>

            <a href="index.php" class="back-home"><i class="fas fa-arrow-left"></i> Back to Homepage</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            var passInput = document.getElementById("passwordInput");
            var eyeIcon = document.getElementById("eyeIcon");
            
            if (passInput.type === "password") {
                passInput.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
                eyeIcon.style.color = "var(--primary)";
            } else {
                passInput.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
                eyeIcon.style.color = "#94a3b8";
            }
        }
    </script>
</body>
</html>