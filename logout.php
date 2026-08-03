<?php
session_start(); // Memory start karo
session_unset(); // Saara data (naam, id) hata do
session_destroy(); // Memory ko hamesha ke liye khatam kar do

// Wapas login page par bhej do
header("Location: login.php");
exit();
?>