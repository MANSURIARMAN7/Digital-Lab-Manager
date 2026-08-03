<?php
$host = "localhost";
$user = "root";       
$pass = "";           
$dbname = "kdp_college";

// Create connection
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Database select karo (Agar bani hui hai toh)
$conn->select_db($dbname);
?>