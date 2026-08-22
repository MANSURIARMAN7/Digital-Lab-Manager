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