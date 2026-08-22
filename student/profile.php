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