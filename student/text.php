<?php echo "Server Working Perfectly!"; ?>

<?php
// Error display ON taaki koi issue ho toh turant dikhe
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_name = $_SESSION['name'] ?? 'Student';
$student_id = $_SESSION['user_id'] ?? '';

// Load Submissions History
$submissions_file = '../Faculty/submissions.json';
$history_list = [];

if (file_exists($submissions_file)) {
    $all_subs = json_decode(file_get_contents($submissions_file), true);
    if (is_array($all_subs)) {
        foreach ($all_subs as $sub) {
            if (isset($sub['enrollment']) && $sub['enrollment'] == $student_id) {
                $history_list[] = $sub;
            }
        }
    }
}