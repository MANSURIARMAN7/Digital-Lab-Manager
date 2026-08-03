<?php
// FILE: Faculty/update_status.php

// Root folder se database connection include kar rahe hain
include '../db.php'; 

if (isset($_POST['id']) && isset($_POST['status'])) {
    $ids_string = $_POST['id']; 
    $new_status = $_POST['status'];
    $marks = isset($_POST['marks']) && $_POST['marks'] !== '' ? intval($_POST['marks']) : NULL;
    $remark = isset($_POST['remark']) ? trim($_POST['remark']) : NULL;
    
    // Multiple IDs agar comma se separated aati hain (jaise: '246310307055,246310307056')
    $ids = explode(',', $ids_string);
    
    if (empty($ids)) {
        echo "Failed";
        exit();
    }

    $updated = false;

    // Prepared Statement for MySQL Update
    if ($new_status === 'Approved') {
        // Approval me Marks save honge aur Remark NULL/Empty ho jayega
        $stmt = $conn->prepare("UPDATE submissions SET status = ?, marks = ?, remark = NULL WHERE enrollment = ?");
        
        foreach ($ids as $enrollment) {
            $enrollment = trim($enrollment);
            $stmt->bind_param("sis", $new_status, $marks, $enrollment);
            if ($stmt->execute()) {
                $updated = true;
            }
        }
        $stmt->close();

    } else if ($new_status === 'Rejected') {
        // Rejection me Remark save hoga aur Marks NULL/Empty ho jayenge
        $stmt = $conn->prepare("UPDATE submissions SET status = ?, remark = ?, marks = NULL WHERE enrollment = ?");
        
        foreach ($ids as $enrollment) {
            $enrollment = trim($enrollment);
            $stmt->bind_param("sss", $new_status, $remark, $enrollment);
            if ($stmt->execute()) {
                $updated = true;
            }
        }
        $stmt->close();
    }

    if ($updated) {
        echo "Success";
    } else {
        echo "Failed";
    }

} else {
    echo "Invalid Request";
}
?>