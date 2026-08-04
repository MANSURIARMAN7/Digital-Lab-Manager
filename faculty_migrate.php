<?php
// Root folder ka db connection
include 'db.php';

// 1. Create Main Database for the Team
$sql_db = "CREATE DATABASE IF NOT EXISTS kdp_college";
if ($conn->query($sql_db) === TRUE) {
    echo "✅ Main Database 'kdp_college' created for the team.<br>";
} else {
    die("Error creating database: " . $conn->error);
}

$conn->select_db("kdp_college");

// 2. Core Users Table (Kyunki login aur faculty id isme hogi)
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'faculty', 'student') NOT NULL,
    name VARCHAR(100) NOT NULL
)";
if($conn->query($sql_users) === TRUE) echo "✅ Core 'users' table created.<br>";

// 3. Submissions Table (Kyunki Faculty ko manual check karke marks isme update karne hain)
$sql_submissions = "CREATE TABLE IF NOT EXISTS submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment VARCHAR(50) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    marks INT DEFAULT NULL,
    remark TEXT DEFAULT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if($conn->query($sql_submissions) === TRUE) echo "✅ Faculty action table 'submissions' created.<br>";

// Ek Demo Faculty daal dete hain testing ke liye
$conn->query("INSERT IGNORE INTO users (user_id, password, role, name) VALUES ('FAC-01', 'admin', 'faculty', 'M.C. Thakor')");

echo "<hr><h3 style='color: green;'>Leader's Core & Faculty Database Setup Complete! 🚀</h3>";
?>