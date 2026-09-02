<?php
session_start();
include '../db.php';

$msg = "";

// Variables to keep form data sticky
$sel_faculty = $_POST['faculty_id'] ?? '';
$sel_branch = $_POST['branch'] ?? '';
$sel_semester = $_POST['semester'] ?? '';
$sel_subjects = $_POST['subjects'] ?? '';

// Agar form submit hua hai
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_subject'])) {
    $faculty_id = $conn->real_escape_string($_POST['faculty_id']);
    $branch = $conn->real_escape_string($_POST['branch']);
    $semester = $conn->real_escape_string($_POST['semester']);

    // Subjects ko array me convert karna
    $raw_subjects = $_POST['subjects'];
    $subject_array = array_filter(array_map('trim', explode(',', $raw_subjects)));

    // 1. Current subjects fetch karo
    $fetch_sql = "SELECT subjects FROM users WHERE user_id = '$faculty_id'";
    $res = $conn->query($fetch_sql);

    $existing_data = [];
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (!empty($row['subjects'])) {
            $decoded = json_decode($row['subjects'], true);
            if (is_array($decoded)) {
                $existing_data = $decoded;
            }
        }
    }

    // 2. Branch exist nahi karti toh banao
    if (!isset($existing_data[$branch])) {
        $existing_data[$branch] = [];
    }

    // 3. Sirf us Branch ke us Semester ke subjects update karo
    if (empty($subject_array)) {
        unset($existing_data[$branch][$semester]); // Khali choda toh remove ho jayega
        // Agar branch khali ho gayi, toh branch ko hi hata do
        if (empty($existing_data[$branch])) {
            unset($existing_data[$branch]);
        }
    } else {
        $existing_data[$branch][$semester] = $subject_array;
    }

    // 4. Wapas JSON banake Database me save kar do
    $new_json = $conn->real_escape_string(json_encode($existing_data));

    $update_sql = "UPDATE users SET subjects = '$new_json' WHERE user_id = '$faculty_id'";
    if ($conn->query($update_sql)) {
        // Change: Highlight the exact subjects assigned in the success message
        if (empty($subject_array)) {
            $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm rounded-3'>
                        <i class='fa-solid fa-eraser me-2'></i> Successfully <strong>removed</strong> all subjects from <strong>$branch ($semester)</strong>!
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
        } else {
            $assigned_str = htmlspecialchars(implode(', ', $subject_array));
            $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm rounded-3'>
                        <i class='fa-solid fa-check-circle me-2'></i> Successfully assigned to <strong>$branch ($semester)</strong>: 
                        <span class='badge bg-success ms-1 px-2 py-1'>$assigned_str</span>
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
        }
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm rounded-3'>
                    <i class='fa-solid fa-triangle-exclamation me-2'></i> Error: " . $conn->error . "
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
}

// Fetch all faculties
$faculties = [];
$fac_query = "SELECT user_id, name, department FROM users WHERE role = 'faculty' ORDER BY name ASC";
$fac_result = $conn->query($fac_query);
if ($fac_result) {
    while ($f = $fac_result->fetch_assoc()) {
        $faculties[] = $f;
    }
}

// Branches List (You can fetch this from DB if you have a branches table)
$branches = [
    'Computer Engineering',
    'Civil Engineering',
    'Mechanical Engineering',
    'Electrical Engineering',
    'Information Technology'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Subjects - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .assign-card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); max-width: 600px; margin: 40px auto; border: 1px solid #e2e8f0; }
        .form-label { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control, .form-select { border-radius: 8px; padding: 10px 15px; background: #f8fafc; border: 1px solid #cbd5e1; font-weight: 500; }
        .form-control:focus, .form-select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .btn-primary { background: #2563eb; border: none; padding: 10px; font-weight: 600; border-radius: 8px; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-light { padding: 10px; font-weight: 600; border-radius: 8px; background: #f8fafc; color: #475569; }
        .btn-light:hover { background: #e2e8f0; }
    </style>
</head>
<body>

    <div class="container">
        <div class="assign-card">
            <h3 class="mb-2 fw-bold" style="color: #0f172a;">Assign Faculty Subjects</h3>
            <p class="text-muted mb-4" style="font-size: 14px;">Map subjects by Branch and Semester.</p>

            <?php echo $msg; ?>

            <form method="POST">

                <!-- 1. Select Faculty -->
                <div class="mb-3">
                    <label class="form-label">1. Select Faculty</label>
                    <select name="faculty_id" class="form-select" required>
                        <option value="">-- Choose Faculty --</option>
                        <?php foreach($faculties as $fac) { ?>
                            <option value="<?php echo htmlspecialchars($fac['user_id']); ?>" <?php if($sel_faculty == $fac['user_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($fac['name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <!-- 2. Select Branch -->
                <div class="mb-3">
                    <label class="form-label">2. Select Branch</label>
                    <select name="branch" class="form-select" required>
                        <option value="">-- Choose Branch --</option>
                        <?php foreach($branches as $b) { ?>
                            <option value="<?php echo $b; ?>" <?php if($sel_branch == $b) echo 'selected'; ?>><?php echo $b; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <!-- 3. Select Semester -->
                <div class="mb-3">
                    <label class="form-label">3. Select Semester</label>
                    <select name="semester" class="form-select" required>
                        <option value="">-- Choose Semester --</option>
                        <option value="Semester 1" <?php if($sel_semester == 'Semester 1') echo 'selected'; ?>>Semester 1</option>
                        <option value="Semester 2" <?php if($sel_semester == 'Semester 2') echo 'selected'; ?>>Semester 2</option>
                        <option value="Semester 3" <?php if($sel_semester == 'Semester 3') echo 'selected'; ?>>Semester 3</option>
                        <option value="Semester 4" <?php if($sel_semester == 'Semester 4') echo 'selected'; ?>>Semester 4</option>
                        <option value="Semester 5" <?php if($sel_semester == 'Semester 5') echo 'selected'; ?>>Semester 5</option>
                        <option value="Semester 6" <?php if($sel_semester == 'Semester 6') echo 'selected'; ?>>Semester 6</option>
                    </select>
                </div>

                <!-- 4. Type Subjects -->
                <div class="mb-4">
                    <label class="form-label">4. Subjects (Comma Separated)</label>
                    <input type="text" name="subjects" class="form-control" placeholder="e.g. Java, DBMS (Leave blank to remove)" value="<?php echo htmlspecialchars($sel_subjects); ?>">
                    <small class="text-muted mt-1 d-block" style="font-size: 11px;">Separate multiple subjects with a comma (,). Leave empty to remove current subjects.</small>
                </div>

                <!-- Save and Clear buttons -->
                <div class="d-flex gap-3">
                    <button type="submit" name="assign_subject" class="btn btn-primary w-100">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Save
                    </button>
                    <a href="?" class="btn btn-light border w-100 text-center text-decoration-none">
                        <i class="fa-solid fa-eraser me-2"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
