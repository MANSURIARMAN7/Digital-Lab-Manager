<?php
session_start();
include '../db.php';

// 1. Secure Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$enrollment = $conn->real_escape_string((string)$_SESSION['user_id']);
$subject = isset($_GET['subject']) ? $conn->real_escape_string($_GET['subject']) : '';
$prac_no = isset($_GET['prac']) ? $conn->real_escape_string($_GET['prac']) : '';

// 2. Fetch Reference Manual
$manual_query = $conn->query("SELECT * FROM lab_manuals WHERE subject_name = '$subject' AND practical_no = '$prac_no' LIMIT 1");
$pdf_path = '';
$manual_id = 0;
if ($manual_query && $manual_query->num_rows > 0) {
    $manual_data = $manual_query->fetch_assoc();
    $manual_id = (int)($manual_data['id'] ?? 0);
    $pdf_path = $manual_data['file_path'] ?? ''; 
    $pdf_path = str_replace('../', '', $pdf_path); 
}

$msg = "";

// 3. Pre-check: Already Submitted?
$check_sub = $conn->query("SELECT * FROM student_submissions WHERE student_id = '$enrollment' AND subject_name = '$subject' AND practical_no = '$prac_no' LIMIT 1");
$is_submitted = ($check_sub && $check_sub->num_rows > 0);
$sub_data = $is_submitted ? $check_sub->fetch_assoc() : null;

// 4. Handle Upload Post (BUG FIXED HERE 🐛🔨)
// Ab hum hidden field check kar rahe hain, button nahi!
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_hidden']) && !$is_submitted) {
    $answer_text = $conn->real_escape_string($_POST['answer_text'] ?? '');
    
    // File upload logic
    $uploaded_file = "";
    if (isset($_FILES["attachment"]) && $_FILES["attachment"]["error"] == 0) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]+/", "_", basename($_FILES["attachment"]["name"]));
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
            $uploaded_file = $target_file;
        }
    }

    // Insert to DB
    $insert = "INSERT IGNORE INTO student_submissions (student_id, manual_id, subject_name, practical_no, file_path, status) 
               VALUES ('$enrollment', '$manual_id', '$subject', '$prac_no', '$uploaded_file', 'Pending')";
    
    if ($conn->query($insert)) {
        // Bulletproof Redirect to Dashboard
        echo "<script>
                alert('Success: Practical Uploaded! Returning to Dashboard.');
                window.location.replace('Stdashboard.php');
              </script>";
        exit();
    } else {
        $msg = "<div class='alert alert-danger'>Database Error: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Workspace - Submission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f1f5f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; height: 100vh; overflow: hidden; display: flex; flex-direction: column; margin: 0; }
        .workspace-nav { background: #1e293b; color: white; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 10;}
        .workspace-title { margin: 0; font-size: 16px; font-weight: 600; }
        .btn-back { background: rgba(255,255,255,0.1); color: white; border: none; padding: 6px 15px; border-radius: 6px; font-size: 13px; text-decoration: none; transition: 0.2s; }
        .btn-back:hover { background: rgba(255,255,255,0.2); color: white; }
        .workspace-container { display: flex; flex: 1; overflow: hidden; }
        
        .pdf-panel { flex: 1; border-right: 1px solid #cbd5e1; background: #e2e8f0; display: flex; flex-direction: column; }
        .panel-header { background: #f8fafc; padding: 12px 20px; font-weight: 700; font-size: 14px; border-bottom: 1px solid #e2e8f0; color: #334155; }
        
        .editor-panel { flex: 1; background: white; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 30px; text-align: center; overflow-y: auto; }
        .editor-form { display: flex; flex-direction: column; flex: 1; padding: 10px 25px; text-align: left; width: 100%; }
        .step-label { font-size: 13px; font-weight: 700; color: #3b82f6; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        .custom-textarea { flex: 1; min-height: 150px; width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 15px; font-family: monospace; font-size: 14px; resize: none; background: #f8fafc; margin-bottom: 25px; }
        .file-upload-box { border: 2px dashed #cbd5e1; padding: 20px; border-radius: 8px; text-align: center; background: #f8fafc; margin-bottom: 25px; transition: 0.2s; }
        .confirm-box { background: #fffbeb; border: 1px solid #fde68a; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 12px; align-items: flex-start; text-align: left; }
        .confirm-box input[type="checkbox"] { margin-top: 3px; transform: scale(1.2); cursor: pointer; }
        .confirm-box label { font-size: 13px; color: #92400e; font-weight: 600; cursor: pointer; margin: 0; }
        .btn-submit { background: #10b981; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 700; font-size: 15px; text-transform: uppercase; letter-spacing: 1px; transition: 0.2s; width: 100%; }
        .btn-submit:hover:not(:disabled) { background: #059669; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(16,185,129,0.3); }
        .btn-submit:disabled { background: #cbd5e1; cursor: not-allowed; color: #64748b; }
        
        .submitted-card { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 30px; max-width: 450px; width: 100%; box-shadow: 0 4px 12px rgba(16,185,129,0.1); margin:auto; }
        .submitted-card i { font-size: 48px; color: #10b981; margin-bottom: 15px; }
        .submitted-card h3 { font-size: 20px; font-weight: 700; color: #065f46; margin-bottom: 10px; }
        .submitted-card p { font-size: 14px; color: #047857; margin-bottom: 20px; }
        .badge-status { padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; margin-bottom: 20px; }
        .status-Pending { background: #fef3c7; color: #d97706; }
    </style>
</head>
<body>

    <div class="workspace-nav">
        <div class="d-flex align-items-center gap-3">
            <a href="Stdashboard.php" class="btn-back"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
            <h4 class="workspace-title">Digital Workspace - <?php echo htmlspecialchars($prac_no ?: 'Practical'); ?></h4>
        </div>
        <div style="font-size: 13px; opacity: 0.8;">
            <i class="fas fa-book me-1"></i> <?php echo htmlspecialchars($subject ?: 'Submission Area'); ?>
        </div>
    </div>

    <div class="workspace-container">
        <!-- LEFT: MANUAL PDF -->
        <div class="pdf-panel">
            <div class="panel-header"><i class="fas fa-file-pdf text-danger me-2"></i> Reference Manual</div>
            <?php if($pdf_path != ''): ?>
                <iframe src="../<?php echo $pdf_path; ?>" width="100%" height="100%" style="border:none;"></iframe>
            <?php else: ?>
                <div class="d-flex justify-content-center align-items-center h-100 text-muted">
                    <p>No Reference PDF provided for this practical.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: EDITOR OR SUCCESS CARD -->
        <div class="editor-panel">
            <?php if ($is_submitted): ?>
                <div class="submitted-card">
                    <i class="fas fa-check-circle"></i>
                    <h3>Already Submitted!</h3>
                    <p>You have successfully submitted this practical. It is currently under review by your faculty.</p>
                    
                    <div>
                        <span class="badge-status status-<?php echo htmlspecialchars($sub_data['status'] ?? 'Pending'); ?>">
                            Status: <?php echo htmlspecialchars($sub_data['status'] ?? 'Pending'); ?>
                        </span>
                    </div>

                    <div class="d-flex gap-2 justify-content-center mt-3">
                        <?php if(!empty($sub_data['file_path'])): ?>
                            <a href="<?php echo htmlspecialchars($sub_data['file_path']); ?>" target="_blank" class="btn btn-outline-success btn-sm px-3 fw-bold">
                                <i class="fas fa-eye me-1"></i> View My Upload
                            </a>
                        <?php endif; ?>
                        <a href="Stdashboard.php" class="btn btn-primary btn-sm px-3 fw-bold">
                            <i class="fas fa-home me-1"></i> Dashboard
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="panel-header w-100 text-start" style="border-radius: 0; border-bottom: 1px solid #e2e8f0; margin-bottom: 15px;">
                    <i class="fas fa-pen-nib text-primary me-2"></i> Complete & Submit Practical
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="editor-form" onsubmit="document.getElementById('submitBtn').disabled = true; document.getElementById('submitBtn').innerHTML = '<i class=\'fas fa-spinner fa-spin me-2\'></i> Uploading...';">
                    <?php if($msg != "") echo $msg; ?>
                    
                    <!-- 🔥 THE MAGIC FIX: HIDDEN INPUT -->
                    <input type="hidden" name="submit_hidden" value="1">
                    
                    <div class="step-label">1. Write Observation / Code</div>
                    <textarea name="answer_text" class="custom-textarea" placeholder="// Type your practical output, procedure, or code here..." required></textarea>
                    
                    <div class="step-label">2. Upload File (PDF / Images / Zip)</div>
                    <div class="file-upload-box">
                        <i class="fas fa-cloud-upload-alt text-muted mb-2" style="font-size: 24px;"></i>
                        <label class="fw-bold d-block mb-2" style="font-size: 13px; color:#475569;">Attach Screenshots or Program Files (.pdf, .zip, .jpg)</label>
                        <input type="file" name="attachment" class="form-control form-control-sm" accept=".pdf,.png,.jpg,.zip,.txt" required>
                    </div>

                    <div class="step-label">3. Confirm Submission</div>
                    <div class="confirm-box">
                        <input type="checkbox" id="confirmCheck" onchange="toggleSubmit()">
                        <label for="confirmCheck">I confirm that I have completed the practical and attached the required files.</label>
                    </div>

                    <!-- Button 'name' hata diya hai taaki conflict na ho -->
                    <button type="submit" id="submitBtn" class="btn-submit" disabled>
                        <i class="fas fa-check-circle me-2"></i> Upload & Confirm Submit
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSubmit() {
            var checkBox = document.getElementById("confirmCheck");
            var btn = document.getElementById("submitBtn");
            if(checkBox && btn) {
                btn.disabled = !checkBox.checked;
            }
        }
    </script>
</body>
</html>