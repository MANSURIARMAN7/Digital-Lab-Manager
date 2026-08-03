<?php
// FILE: update_status.php
if (isset($_POST['id']) && isset($_POST['status'])) {
    $ids_string = $_POST['id']; 
    $new_status = $_POST['status'];
    $marks = isset($_POST['marks']) ? $_POST['marks'] : '';
    $remark = isset($_POST['remark']) ? $_POST['remark'] : '';
    
    $ids = explode(',', $ids_string);
    $json_file = 'submissions.json';
    
    $data = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
    if (!is_array($data)) $data = [];
    $updated = false;
    
    foreach ($data as $key => $row) {
        if (in_array($row['enrollment'], $ids)) {
            $data[$key]['status'] = $new_status;
            if ($new_status === 'Approved') {
                $data[$key]['marks'] = $marks;
                $data[$key]['remark'] = ''; // Clear remark if approved
            } else if ($new_status === 'Rejected') {
                $data[$key]['remark'] = $remark;
                $data[$key]['marks'] = ''; // Clear marks if rejected
            }
            $updated = true;
        }
    }
    
    if ($updated) {
        file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT));
        echo "Success";
    } else {
        echo "Failed";
    }
} else {
    echo "Invalid Request";
}
?>