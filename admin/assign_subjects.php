<?php
session_start();
include '../db.php';

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$msg = "";
$selected_faculty = $_POST['faculty_id'] ?? '';
$selected_branch = $_POST['branch'] ?? '';
$selected_semester = $_POST['semester'] ?? '';
$subjects_input = $_POST['subjects'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_subject'])) {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm rounded-3' role='alert'>
                    <i class='fa-solid fa-shield-halved me-2'></i> Security error: Invalid CSRF token.
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    } else {
        $faculty_id = trim($_POST['faculty_id'] ?? '');
        $branch = trim($_POST['branch'] ?? '');
        $semester = trim($_POST['semester'] ?? '');
        
        $raw_subjects = $_POST['subjects'] ?? '';
        
        // Trim, format to Title Case, and remove duplicate entries
        $raw_array = array_filter(array_map('trim', explode(',', $raw_subjects)));
        $subject_array = array_values(array_unique(array_map(function($sub) {
            return ucwords(strtolower($sub));
        }, $raw_array)));

        if (!empty($faculty_id) && !empty($branch) && !empty($semester)) {
            // Fetch current subjects
            $fetch_stmt = $conn->prepare("SELECT subjects FROM users WHERE user_id = ?");
            $fetch_stmt->bind_param("s", $faculty_id);
            $fetch_stmt->execute();
            $res = $fetch_stmt->get_result();
            
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
            $fetch_stmt->close();

            if (!isset($existing_data[$branch])) {
                $existing_data[$branch] = [];
            }

            if (empty($subject_array)) {
                unset($existing_data[$branch][$semester]);
                if (empty($existing_data[$branch])) {
                    unset($existing_data[$branch]);
                }
            } else {
                $existing_data[$branch][$semester] = $subject_array;
            }

            $new_json = json_encode($existing_data);
            $update_stmt = $conn->prepare("UPDATE users SET subjects = ? WHERE user_id = ?");
            $update_stmt->bind_param("ss", $new_json, $faculty_id);
            
            if ($update_stmt->execute()) {
                $msg = "<div class='alert alert-success alert-dismissible fade show shadow-sm rounded-3' role='alert'>
                            <i class='fa-solid fa-check-circle me-2'></i> Subjects assigned to <strong>" . htmlspecialchars($branch) . " (" . htmlspecialchars($semester) . ")</strong> successfully!
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>";
            } else {
                $msg = "<div class='alert alert-danger alert-dismissible fade show shadow-sm rounded-3' role='alert'>
                            <i class='fa-solid fa-triangle-exclamation me-2'></i> Error: " . htmlspecialchars($conn->error) . "
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>";
            }
            $update_stmt->close();
        } else {
            $msg = "<div class='alert alert-warning alert-dismissible fade show shadow-sm rounded-3' role='alert'>
                        Please fill all required fields.
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>";
        }
    }
}

// Fetch all faculties
$faculties = [];
$fac_stmt = $conn->prepare("SELECT user_id, name, department FROM users WHERE role = 'faculty' ORDER BY name ASC");
if ($fac_stmt) {
    $fac_stmt->execute();
    $fac_result = $fac_stmt->get_result();
    while ($f = $fac_result->fetch_assoc()) {
        $faculties[] = $f;
    }
    $fac_stmt->close();
}

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
    </style>
</head>
<body>

    <div class="container">
        <div class="assign-card">
            <h3 class="mb-2 fw-bold" style="color: #0f172a;">Assign Faculty Subjects</h3>
            <p class="text-muted mb-4" style="font-size: 14px;">Map subjects by Branch and Semester.</p>

            <?php echo $msg; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="mb-3">
                    <label class="form-label">1. Select Faculty</label>
                    <select name="faculty_id" class="form-select" required>
                        <option value="">-- Choose Faculty --</option>
                        <?php foreach($faculties as $fac) { ?>
                            <option value="<?php echo htmlspecialchars($fac['user_id']); ?>" <?php echo ($selected_faculty === $fac['user_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($fac['name']); ?> <?php echo !empty($fac['department']) ? '(' . htmlspecialchars($fac['department']) . ')' : ''; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">2. Select Branch</label>
                    <select name="branch" class="form-select" required>
                        <option value="">-- Choose Branch --</option>
                        <?php foreach($branches as $b) { ?>
                            <option value="<?php echo htmlspecialchars($b); ?>" <?php echo ($selected_branch === $b) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b); ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">3. Select Semester</label>
                    <select name="semester" class="form-select" required>
                        <option value="">-- Choose Semester --</option>
                        <?php for($i = 1; $i <= 6; $i++) { 
                            $semVal = "Semester " . $i;
                        ?>
                            <option value="<?php echo $semVal; ?>" <?php echo ($selected_semester === $semVal) ? 'selected' : ''; ?>><?php echo $semVal; ?></option>
                        <?php } ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label">4. Subjects (Comma Separated)</label>
                    <input type="text" id="subjectsInput" name="subjects" class="form-control" value="<?php echo htmlspecialchars($subjects_input); ?>" placeholder="e.g. Java, Software Testing, DBMS" autocomplete="off">
                    <div id="subjectBadges" class="mt-2 d-flex flex-wrap gap-1"></div>
                    <small class="text-muted mt-1 d-block" style="font-size: 11px;">Separate multiple subjects with a comma (,). Leave empty to remove subjects.</small>
                </div>

                <button type="submit" name="assign_subject" class="btn btn-primary w-100">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Save Assignments
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('subjectsInput');
            const badgeContainer = document.getElementById('subjectBadges');

            function updateBadges() {
                badgeContainer.innerHTML = '';
                if (!input.value.trim()) return;
                
                const subjects = input.value.split(',').map(s => s.trim()).filter(s => s.length > 0);
                subjects.forEach(sub => {
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1';
                    badge.style.fontSize = '12px';
                    badge.textContent = sub;
                    badgeContainer.appendChild(badge);
                });
            }

            if (input) {
                input.addEventListener('input', updateBadges);
                updateBadges();
            }
        });
    </script>
</body>
</html>
