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
    } else {
        header("Location: admin/dashboard.php");
        exit();
    }
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_input = $conn->real_escape_string(trim($_POST['user_id']));
    $password = trim($_POST['password']);

    // 🚀 Flexible Query: Email, Short ID, ya Name teeno se check karega
    $query = "SELECT * FROM users WHERE email = '$user_input' OR user_id = '$user_input' OR name LIKE '%$user_input%'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $db_password = $user['password'];
        
        // 🔐 Smart Password Check (Supports Plain Text, MD5, & Hashed Passwords)
        if ($password === $db_password || md5($password) === $db_password || password_verify($password, $db_password)) {
            
            // Purana session saaf karo
            session_unset();
            
            // Session set kar rahe hain
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = strtolower(trim($user['role']));
            
            // Role ke hisaab se redirect
            if ($_SESSION['role'] == 'faculty') {
                header("Location: Faculty/faculty_dashboard.php");
            } elseif ($_SESSION['role'] == 'student') {
                header("Location: student/stdashboard.php");
            } else {
                header("Location: admin/dashboard.php"); // Redirect to admin dashboard
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --primary: #4338ca; 
            --primary-hover: #3730a3;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { 
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; color: var(--text-main); 
        }

        .login-wrapper {
            display: flex;
            background: var(--surface);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            width: 900px;
            max-width: 95%;
            animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeUp { 
            0% { opacity: 0; transform: translateY(40px); } 
            100% { opacity: 1; transform: translateY(0); } 
        }

        /* LEFT SIDE - BRANDING */
        .login-brand {
            flex: 1;
            background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%);
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            position: relative;
        }

        .login-brand::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: url('https://www.transparenttextures.com/patterns/cubes.png'); opacity: 0.1;
        }

        .brand-logo-container {
            width: 120px; height: 120px; background: rgba(255,255,255,0.1); 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            border: 3px solid rgba(255,255,255,0.2); margin-bottom: 25px; backdrop-filter: blur(5px);
            z-index: 2; padding: 10px;
        }
        
        .brand-logo-container img { width: 100%; height: 100%; object-fit: contain; border-radius: 50%; }

        .brand-title { font-size: 26px; font-weight: 800; margin-bottom: 10px; letter-spacing: 1px; z-index: 2; }
        .brand-subtitle { font-size: 14px; color: #bfdbfe; font-weight: 500; line-height: 1.6; z-index: 2; }

        /* RIGHT SIDE - FORM */
        .login-form-container {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header { margin-bottom: 35px; }
        .form-header h3 { font-size: 24px; font-weight: 800; color: var(--text-main); margin-bottom: 8px; }
        .form-header p { font-size: 14px; color: var(--text-muted); font-weight: 500; }

        .input-group-text { background: #f8fafc; border-radius: 12px 0 0 12px; border: 1px solid #cbd5e1; border-right: none; color: #94a3b8; padding: 0 18px; }
        input.form-control { border-radius: 0 12px 12px 0; padding: 14px 15px; border: 1px solid #cbd5e1; border-left: none; font-weight: 500; font-size: 14.5px; background: #f8fafc; transition: var(--transition-bounce); }
        input.form-control:focus { background: #ffffff; border-color: var(--primary); box-shadow: none; }
        
        .input-group:focus-within .input-group-text, .input-group:focus-within input.form-control { border-color: var(--primary); background: #ffffff; color: var(--primary); }
        .input-group:focus-within { box-shadow: 0 0 0 4px rgba(67, 56, 202, 0.1); border-radius: 12px; }

        /* PASSWORD EYE ICON FIX */
        .password-group input.form-control { border-radius: 0; }
        .password-toggle { background: #f8fafc; border-radius: 0 12px 12px 0; border: 1px solid #cbd5e1; border-left: none; cursor: pointer; color: #94a3b8; transition: var(--transition-bounce); }
        .input-group:focus-within .password-toggle { border-color: var(--primary); background: #ffffff; color: var(--primary); }

        .btn-login { 
            background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; border: none; font-weight: 700; padding: 14px; border-radius: 12px; 
            font-size: 16px; width: 100%; box-shadow: 0 4px 15px rgba(67, 56, 202, 0.3); transition: var(--transition-bounce); margin-top: 15px;
        }
        .btn-login:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(67, 56, 202, 0.4); color: white; }

        .alert-custom { background: #fef2f2; border-left: 4px solid #ef4444; color: #b91c1c; border-radius: 8px; padding: 12px 15px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; margin-bottom: 25px; }

        /* RESPONSIVE DESIGN */
        @media (max-width: 768px) {
            .login-wrapper { flex-direction: column; width: 100%; max-width: 450px; }
            .login-brand { padding: 40px 20px; }
            .brand-logo-container { width: 90px; height: 90px; }
            .login-form-container { padding: 40px 30px; }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        
        <div class="login-brand">
            <div class="brand-logo-container">
                <img src="assets/images/college-logo.png" alt="KDP Logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/8074/8074800.png'">
            </div>
            <h1 class="brand-title">K.D. Polytechnic</h1>
            <p class="brand-subtitle">Digital Lab Manual & Submission Management System. Secure portal for students and faculty.</p>
        </div>

        <div class="login-form-container">
            <div class="form-header">
                <h3>Welcome Back</h3>
                <p>Enter your credentials to access your account.</p>
            </div>

            <?php if (!empty($error)) { ?>
                <div class="alert-custom">
                    <i class="fas fa-exclamation-circle me-2 fs-5"></i> <?php echo $error; ?>
                </div>
            <?php } ?>

            <form action="" method="POST">
                
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1 mb-2">User ID / Enrollment</label>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="user_id" class="form-control" placeholder="e.g. 236170307001 or admin" required autocomplete="off">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted text-uppercase letter-spacing-1 mb-2">Password</label>
                    <div class="input-group password-group shadow-sm">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter your password" required>
                        <span class="input-group-text password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    Secure Login <i class="fas fa-arrow-right ms-2"></i>
                </button>
                
            </form>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password Visibility Toggle Logic
        function togglePassword() {
            var passInput = document.getElementById("passwordInput");
            var eyeIcon = document.getElementById("eyeIcon");
            
            if (passInput.type === "password") {
                passInput.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
            } else {
                passInput.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>