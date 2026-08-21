<?php
include '../db.php';
include 'header.php';

$msg = "";

// 2. Handle Upload Manual
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_manual'])) {
    $subject = $conn->real_escape_string($_POST['subject_name']);
    $prac_no = $conn->real_escape_string($_POST['practical_no']);
    $title = $conn->real_escape_string($_POST['title']);
    
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_name = time() . "_" . basename($_FILES["manual_file"]["name"]);
    $target_file = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if ($file_type != "pdf") {
        $msg = "<div class='alert alert-danger shadow-sm border-0'><i class='fas fa-exclamation-circle me-2'></i> Only PDF files are allowed!</div>";
    } else {
        if (move_uploaded_file($_FILES["manual_file"]["tmp_name"], $target_file)) {
            $insert_query = "INSERT INTO lab_manuals (subject_name, practical_no, title, file_path) VALUES ('$subject', '$prac_no', '$title', '$target_file')";
            if ($conn->query($insert_query)) {
                $msg = "<div class='alert alert-success shadow-sm border-0'><i class='fas fa-check-circle me-2'></i> Lab Manual Uploaded Successfully!</div>";
            }
        } else {
            $msg = "<div class='alert alert-danger shadow-sm border-0'>Error during file upload!</div>";
        }
    }
}

// 3. Handle Delete Manual
if (isset($_GET['delete']) && isset($_GET['file'])) {
    $del_id = $conn->real_escape_string($_GET['delete']);
    $file_path = $_GET['file'];
    
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    $conn->query("DELETE FROM lab_manuals WHERE id = '$del_id'");
    $_SESSION['success_message'] = "Lab manual deleted successfully.";
    echo "<script>window.location.href='Lab_Manuals.php';</script>";
    exit();
}

// 4. Fetch Active Subjects for Dropdown
$subjects_list = [];
$sub_res = $conn->query("SELECT DISTINCT subject_name FROM subjects ORDER BY subject_name ASC");
if ($sub_res) {
    while ($r = $sub_res->fetch_assoc()) {
        $subjects_list[] = $r['subject_name'];
    }
}

// 5. Fetch Uploaded Manuals
$manuals = [];
$filter_sub = isset($_GET['filter_subject']) ? $conn->real_escape_string($_GET['filter_subject']) : '';
$query = "SELECT * FROM lab_manuals";
if ($filter_sub != "") {
    $query .= " WHERE subject_name = '$filter_sub'";
}
$query .= " ORDER BY uploaded_at DESC";

$res = $conn->query($query);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $manuals[] = $row;
    }
}
?>

<!-- HEADER TITLE -->
<div class="page-header mt-2 mb-4">
    <div>
        <h4 class="page-title">📄 Lab Manuals (Practicals)</h4>
        <p class="page-subtitle">Upload PDF practical templates and manage subject-wise manuals.</p>
    </div>
</div>

<?php if($msg != "") echo $msg; ?>

<div class="row g-4">
    <!-- UPLOAD FORM -->
    <div class="col-md-4">
        <div class="content-box">
            <h6 class="fw-bold text-dark mb-4"><i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i>Upload Manual</h6>
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">SELECT SUBJECT</label>
                    <select name="subject_name" class="form-select" required>
                        <option value="">-- Choose Subject --</option>
                        <?php foreach($subjects_list as $sub): ?>
                            <option value="<?php echo htmlspecialchars($sub); ?>"><?php echo htmlspecialchars($sub); ?></option>
                        <?php endforeach; ?>
                        <?php if(empty($subjects_list)): ?>
                            <option value="DBMS">Database Systems (DBMS)</option>
                            <option value="DS">Data Structures (DS)</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">PRACTICAL NO.</label>
                    <input type="text" name="practical_no" class="form-control" placeholder="e.g. Exp #01" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">PRACTICAL TITLE</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. SQL Queries Implementation" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">UPLOAD PDF</label>
                    <input type="file" name="manual_file" class="form-control bg-white" accept="application/pdf" required>
                </div>
                <button type="submit" name="upload_manual" class="btn btn-primary w-100 py-2 fs-6">Upload Template</button>
            </form>
        </div>
    </div>

    <!-- MANUALS LIST TABLE -->
    <div class="col-md-8">
        <div class="content-box h-100">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold text-dark mb-0">Available Templates</h6>
                
                <!-- FILTER FORM -->
                <form method="GET" class="d-flex gap-2">
                    <select name="filter_subject" class="form-select form-select-sm shadow-none" style="width: auto; padding: 6px 30px 6px 12px;" onchange="this.form.submit()">
                        <option value="">All Subjects</option>
                        <?php foreach($subjects_list as $sub): ?>
                            <option value="<?php echo htmlspecialchars($sub); ?>" <?php if($filter_sub == $sub) echo 'selected'; ?>><?php echo htmlspecialchars($sub); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Practical No.</th>
                            <th>Title & Subject</th>
                            <th>Upload Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($manuals)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No lab manuals uploaded yet.</td></tr>
                        <?php else: foreach($manuals as $man): ?>
                            <tr>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1"><?php echo htmlspecialchars($man['practical_no']); ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($man['title']); ?></div>
                                    <small class="text-muted" style="font-size: 12px;"><?php echo htmlspecialchars($man['subject_name']); ?></small>
                                </td>
                                <td class="text-muted" style="font-size: 13px;">
                                    <?php echo date('d M Y', strtotime($man['uploaded_at'])); ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?php echo htmlspecialchars($man['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-success border-0 me-1" title="View PDF">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>
                                    <a href="?delete=<?php echo $man['id']; ?>&file=<?php echo urlencode($man['file_path']); ?>" class="btn btn-sm btn-outline-danger border-0" onclick="return confirm('Are you sure you want to delete this manual?');" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>