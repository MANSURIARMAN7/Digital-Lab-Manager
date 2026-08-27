<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security Check: Agar student logged in nahi hai toh login page par bhejo
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] != true || $_SESSION['role'] != 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'] ?? '';
$users_file = '../users.json';
$success_msg = '';
$error_msg = '';

// 1. Data Update Logic (Jab form submit hoga)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $new_phone = trim($_POST['phone']); // Agar phone ka column hai

    if (file_exists($users_file)) {
        $users_data = json_decode(file_get_contents($users_file), true);
        $updated = false;

        foreach ($users_data as &$user) {
            if ($user['user_id'] == $student_id) {
                $user['name'] = $new_name;
                $user['email'] = $new_email;
                if (!empty($new_phone)) {
                    $user['phone'] = $new_phone;
                }
                  // Profile Picture Upload Logic
                if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $pic_name = 'student_' . $student_id . '_' . time() . '.' . $ext;
                        $upload_dir = '../uploads/profile_pics/';
                        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $pic_name);
                        $user['profile_pic'] = $pic_name; // Save pic name in JSON
                    }
                }

                $updated = true;
                break;
            }
        }
        unset($user); // Break reference

        if ($updated) {
            file_put_contents($users_file, json_encode($users_data, JSON_PRETTY_PRINT));
            $success_msg = "Profile updated successfully!";
            // Session update karo taaki sidebar mein naya naam dikhe
            $_SESSION['name'] = $new_name;
            $_SESSION['email'] = $new_email;
        } else {
            $error_msg = "User data not found in database.";
        }
    }
}
// 2. Current Data Load Karne ka Logic (Pehle se saved data)
$student_name = "Student";
$student_email = "";
$student_branch = "Computer Engineering";
$student_sem = "Semester 5";
$student_phone = "";
$profile_pic = "";