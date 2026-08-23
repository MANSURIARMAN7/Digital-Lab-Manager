<?php
include '../db.php';
include 'header.php';

$msg = "";

// Handle Upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_manual'])) {
    $subject  = $conn->real_escape_string($_POST['subject_name']);
    $prac_no  = $conn->real_escape_string($_POST['practical_no']);
    $title    = $conn->real_escape_string($_POST['title']);

    $target_dir = "../uploads/manuals/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $file_name  = time() . "_" . basename($_FILES["manual_file"]["name"]);
    $target_file = $target_dir . $file_name;
    $file_type  = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if ($file_type != "pdf") {
        $msg = "<div class='alert alert-danger'><i class='fas fa-triangle-exclamation'></i> Only PDF files are allowed!</div>";
    } elseif (move_uploaded_file($_FILES["manual_file"]["tmp_name"], $target_file)) {
        if ($conn->query("INSERT INTO lab_manuals (subject_name, practical_no, title, file_path) VALUES ('$subject', '$prac_no', '$title', '$target_file')")) {
            $msg = "<div class='alert alert-success'><i class='fas fa-circle-check'></i> Lab Manual Uploaded Successfully!</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger'><i class='fas fa-triangle-exclamation'></i> File upload failed.</div>";
    }
}

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['file'])) {
    $del_id = $conn->real_escape_string($_GET['delete']);
    $file_path = $_GET['file'];
    if (file_exists($file_path)) unlink($file_path);
    $conn->query("DELETE FROM lab_manuals WHERE id = '$del_id'");
    $_SESSION['success_message'] = "Lab manual deleted.";
    echo "<script>window.location.href='Lab_Manuals.php';</script>";
    exit();
}

// Fetch Subjects
$subjects_list = [];
$sub_res = $conn->query("SELECT DISTINCT subject_name FROM subjects ORDER BY subject_name ASC");
if ($sub_res) { while ($r = $sub_res->fetch_assoc()) { $subjects_list[] = $r['subject_name']; } }

// Fetch Manuals
$manuals = [];
$filter_sub = isset($_GET['filter_subject']) ? $conn->real_escape_string($_GET['filter_subject']) : '';
$query = "SELECT * FROM lab_manuals";
if ($filter_sub != "") $query .= " WHERE subject_name = '$filter_sub'";
$query .= " ORDER BY uploaded_at DESC";
$res = $conn->query($query);
if ($res) { while ($row = $res->fetch_assoc()) { $manuals[] = $row; } }

// Count
$total_manuals_res = $conn->query("SELECT COUNT(*) as total FROM lab_manuals");
$total_manuals = $total_manuals_res ? $total_manuals_res->fetch_assoc()['total'] : 0;
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Lab Manuals</h1>
        <p class="page-subtitle">Upload PDF practical templates and manage subject-wise manuals.</p>
    </div>
    <span class="badge-status badge-info" style="padding: 8px 16px; font-size: 13px;">
        <i class="fas fa-file-pdf me-1"></i> <?php echo $total_manuals; ?> Manuals Uploaded
    </span>
</div>

<?php if($msg != "") echo $msg; ?>

<div class="row g-4">
    <!-- UPLOAD FORM -->
    <div class="col-md-4">
        <div class="content-box">
            <div class="section-title"><i class="fas fa-cloud-arrow-up"></i> Upload New Manual</div>
            <form method="POST" enctype="multipart/form-data" style="display: grid; gap: 14px;">
                <div>
                    <label class="form-label">Select Subject</label>
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
                <div>
                    <label class="form-label">Practical No.</label>
                    <div class="input-with-icon">
                        <i class="fas fa-hashtag"></i>
                        <input type="text" name="practical_no" class="form-control" placeholder="e.g. Exp #01" required>
                    </div>
                </div>
                <div>
                    <label class="form-label">Practical Title</label>
                    <div class="input-with-icon">
                        <i class="fas fa-heading"></i>
                        <input type="text" name="title" class="form-control" placeholder="e.g. SQL Queries Implementation" required>
                    </div>
                </div>
                <div>
                    <label class="form-label">Upload PDF</label>
                    <div style="border: 2px dashed var(--card-border); border-radius: 10px; padding: 20px; text-align: center; background: #f8fafc; transition: var(--transition); cursor: pointer;"
                         onclick="document.getElementById('pdfInput').click();"
                         id="dropZone">
                        <i class="fas fa-file-pdf" style="font-size: 28px; color: #ef4444; margin-bottom: 8px;"></i>
                        <p style="font-size: 13px; color: var(--text-secondary); font-weight: 600; margin: 0 0 4px;">Click to browse PDF</p>
                        <p style="font-size: 11.5px; color: var(--text-muted); margin: 0;" id="fileNameLabel">Only .pdf files accepted</p>
                        <input type="file" id="pdfInput" name="manual_file" accept="application/pdf" required style="display: none;" onchange="document.getElementById('fileNameLabel').textContent = this.files[0]?.name || 'No file chosen';">
                    </div>
                </div>
                <button type="submit" name="upload_manual" class="btn btn-primary btn-w-full" style="margin-top: 4px;">
                    <i class="fas fa-upload"></i> Upload Manual
                </button>
            </form>
        </div>
    </div>

    <!-- MANUALS LIST -->
    <div class="col-md-8">
        <div class="content-box" style="padding: 0; overflow: hidden;">
            <div style="padding: 16px 22px; border-bottom: 1px solid var(--card-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div class="section-title mb-0"><i class="fas fa-folder-open"></i> Uploaded Templates</div>
                <form method="GET" style="display: flex; gap: 8px;">
                    <select name="filter_subject" class="form-select" style="width: auto; font-size: 12.5px; padding: 7px 32px 7px 12px;" onchange="this.form.submit()">
                        <option value="">All Subjects</option>
                        <?php foreach($subjects_list as $sub): ?>
                            <option value="<?php echo htmlspecialchars($sub); ?>" <?php if($filter_sub == $sub) echo 'selected'; ?>><?php echo htmlspecialchars($sub); ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if(empty($manuals)): ?>
                <div class="empty-state" style="border: none;">
                    <div class="empty-state-icon"><i class="fas fa-file-pdf"></i></div>
                    <h5>No Manuals Uploaded Yet</h5>
                    <p>Upload your first PDF lab manual using the form.</p>
                </div>
            <?php else: ?>
                <table class="table-custom" style="margin: 0;">
                    <thead>
                        <tr>
                            <th style="padding-left: 22px;">Practical</th>
                            <th>Title & Subject</th>
                            <th>Uploaded</th>
                            <th class="text-end" style="padding-right: 22px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($manuals as $man): ?>
                        <tr>
                            <td style="padding-left: 22px;">
                                <span style="background: var(--blue-light); color: var(--brand-accent); font-size: 12px; font-weight: 700; padding: 5px 11px; border-radius: 7px; white-space: nowrap;">
                                    <?php echo htmlspecialchars($man['practical_no']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 34px; height: 34px; background: #fef2f2; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <i class="fas fa-file-pdf" style="color: #ef4444; font-size: 15px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; font-size: 13.5px;"><?php echo htmlspecialchars($man['title']); ?></div>
                                        <div style="font-size: 11.5px; color: var(--text-muted);"><?php echo htmlspecialchars($man['subject_name']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size: 12.5px; color: var(--text-muted);">
                                <i class="far fa-calendar" style="margin-right: 5px;"></i>
                                <?php echo date('d M Y', strtotime($man['uploaded_at'])); ?>
                            </td>
                            <td class="text-end" style="padding-right: 22px;">
                                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                    <a href="<?php echo htmlspecialchars($man['file_path']); ?>" target="_blank"
                                       class="btn btn-sm" style="background: var(--green-light); color: var(--green-dark); border-radius: 8px; padding: 6px 12px;"
                                       title="View PDF">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?delete=<?php echo $man['id']; ?>&file=<?php echo urlencode($man['file_path']); ?>"
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Delete this manual?');" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>