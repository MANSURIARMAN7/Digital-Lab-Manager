<?php
$host = "localhost";
$user = "root";       
$pass = "";           
$dbname = "kdp_collage";

// Create connection
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Database select or create karo
try {
    $conn->select_db($dbname);
} catch (mysqli_sql_exception $e) {
    // Agar database nahi mila, toh usey create karo
    if ($conn->query("CREATE DATABASE `$dbname`")) {
        $conn->select_db($dbname);
    } else {
        die("Database Creation Failed: " . $conn->error);
    }
}

// Ensure users table exists
$createUsersTable = "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'faculty', 'student') NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `subjects` TEXT DEFAULT NULL,
    `sem` VARCHAR(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($createUsersTable);

// Drop old submissions table if it uses the outdated schema
$checkSubmissionsTable = $conn->query("SHOW COLUMNS FROM `submissions` LIKE 'student_id'");
if ($checkSubmissionsTable && $checkSubmissionsTable->num_rows == 0) {
    $conn->query("DROP TABLE IF EXISTS `submissions`");
}

// Ensure submissions table exists with the correct schema
$createSubmissionsTable = "CREATE TABLE IF NOT EXISTS `submissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` VARCHAR(50) NOT NULL,
    `subject_name` VARCHAR(100) NOT NULL,
    `practical_no` INT DEFAULT NULL,
    `answer_text` TEXT DEFAULT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    `marks` INT DEFAULT NULL,
    `remark` TEXT DEFAULT NULL,
    `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($createSubmissionsTable);

// Add missing columns if table already existed without them
$checkSubjects = $conn->query("SHOW COLUMNS FROM `users` LIKE 'subjects'");
if ($checkSubjects && $checkSubjects->num_rows == 0) {
    $conn->query("ALTER TABLE `users` ADD COLUMN `subjects` TEXT DEFAULT NULL");
}

$checkSem = $conn->query("SHOW COLUMNS FROM `users` LIKE 'sem'");
if ($checkSem && $checkSem->num_rows == 0) {
    $conn->query("ALTER TABLE `users` ADD COLUMN `sem` VARCHAR(50) DEFAULT NULL");
}

$checkDept = $conn->query("SHOW COLUMNS FROM `users` LIKE 'department'");
if ($checkDept && $checkDept->num_rows == 0) {
    $conn->query("ALTER TABLE `users` ADD COLUMN `department` VARCHAR(100) DEFAULT NULL");
}

// Insert default Admin if it doesn't exist
$checkAdmin = $conn->query("SELECT * FROM `users` WHERE `user_id` = 'admin'");
if ($checkAdmin && $checkAdmin->num_rows == 0) {
    $conn->query("INSERT INTO `users` (`user_id`, `password`, `role`, `name`) VALUES ('admin', 'admin123', 'admin', 'System Administrator')");
}

// Ensure FAC-01 faculty has subjects populated for demo/default usage
$conn->query("UPDATE `users` SET `subjects` = '[\"Web Development\", \"Database Management\"]' WHERE `user_id` = 'FAC-01' AND (`subjects` IS NULL OR `subjects` = '')");
?>