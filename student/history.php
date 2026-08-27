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