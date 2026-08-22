<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_name = $_SESSION['name'] ?? 'Student';
$student_id = $_SESSION['user_id'] ?? ''; 

// Default details
$email = "student@kdpolytechnic.edu.in";
$branch = "Computer Engineering";
$semester = "Semester 5";
$contact = "+91 9876543210";
$academic_year = "2024 - 2027";
$success_msg = "";
$error_msg = "";
// Fetch User Data from users.json
$users_file = '../users.json';
if (file_exists($users_file)) {
    $users_data = json_decode(file_get_contents($users_file), true);
    if (is_array($users_data)) {
        foreach ($users_data as $u) {
            if (isset($u['user_id']) && $u['user_id'] === $student_id) {
                $email = $u['email'] ?? $email;
                $branch = $u['branch'] ?? $branch;
                $semester = $u['sem'] ?? $semester;
                $contact = $u['contact'] ?? $contact;
                break;
            }
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
    <link rel="stylesheet" href="../assets/css/student.css?v=8">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 25px;
            margin-top: 20px;
        }
        .profile-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }
         .avatar-box {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #102a56, #2563eb);
            color: white;
            font-size: 38px;
            font-weight: bold;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px auto;
            box-shadow: 0 8px 16px rgba(16, 42, 86, 0.2);
        }
         .profile-card h2 {
            font-size: 20px;
            color: #0f172a;
            margin-bottom: 5px;
            }

        .profile-card p {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .badge-active {
            background: #dcfce7;
            color: #15803d;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
.info-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
        }

        .info-card h3 {
            font-size: 16px;
            color: #102a56;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 10px;
        }
        .info-group {
            margin-bottom: 15px;
        }

        .info-group label {
            display: block;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-group p {
            font-size: 14px;
            color: #1e293b;
            font-weight: 500;
            margin: 0;
        }
         .info-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
            box-sizing: border-box;
        }

        .info-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn-submit {
            background: #102a56;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-submit:hover {
            background: #1d4ed8;
        }
 @media (max-width: 900px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }

        /* Dark Mode Support */
        body.dark-mode .profile-card, 
        body.dark-mode .info-card {
            background: #1e293b;
            border-color: #334155;
        }

        body.dark-mode .profile-card h2,
        body.dark-mode .info-group p {
            color: #f8fafc;
        }

        body.dark-mode .info-card h3 {
            color: #38bdf8;
            border-bottom-color: #334155;
        }

        body.dark-mode .info-input {
            background: #0f172a;
            border-color: #334155;
            color: white;
        }
    </style>
</head>
<body>