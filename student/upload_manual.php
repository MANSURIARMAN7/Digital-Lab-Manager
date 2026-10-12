<?php
session_start();
include '../db.php'; // Ensure DB connection path is correct

// 1. Student Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$enrollment = $_SESSION['user_id'];
$subject = isset($_GET['subject']) ? $conn->real_escape_string($_GET['subject']) : '';
$prac_no = isset($_GET['prac']) ? $conn->real_escape_string($_GET['prac']) : '';

// 2. Fetch Manual PDF Path
$manual_query = $conn->query("SELECT * FROM lab_manuals WHERE subject_name = '$subject' AND practical_no = '$prac_no'");
$manual_data = $manual_query->fetch_assoc();
$pdf_path = $manual_data['file_path'] ?? ''; 
$pdf_path = str_replace('../', '', $pdf_path); // Adjust path for student folder relative to main dir

$msg = "";

// 3. Handle Final Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_practical'])) {
    $answer_text = $conn->real_escape_string($_POST['answer_text']);
    
    // File upload logic
    $uploaded_file = "";
    if (isset($_FILES["attachment"]) && $_FILES["attachment"]["error"] == 0) {
        $target_dir = "../uploads/submissions/";
        // Create folder if it doesn't exist
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true); 
        
        $file_name = time() . "_" . basename($_FILES["attachment"]["name"]);
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $target_file)) {
            $uploaded_file = $target_file;
        }
    }

    // Insert into Submissions table
    // Insert into Submissions table
    $insert = "INSERT INTO submissions (student_id, subject_name, practical_no, answer_text, file_path, status) 
               VALUES ('$enrollment', '$subject', '$prac_no', '$answer_text', '$uploaded_file', 'Pending')";
    
    if ($conn->query($insert)) {
        // YAHAN MENE Stdashboard.php LAGA DIYA HAI
        echo "<script>alert('Practical Uploaded & Submitted Successfully!'); window.location.href='Stdashboard.php';</script>";
        exit();
    }
    else {
        $msg = "<div class='alert alert-danger'>Error submitting practical!</div>";
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
        body { background-color: #f1f5f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; height: 100vh; overflow: hidden; display: flex; flex-direction: column; }
        
        /* Top Navigation */
        .workspace-nav { background: #1e293b; color: white; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .workspace-title { margin: 0; font-size: 16px; font-weight: 600; }
        .btn-back { background: rgba(255,255,255,0.1); color: white; border: none; padding: 6px 15px; border-radius: 6px; font-size: 13px; text-decoration: none; transition: 0.2s; }
        .btn-back:hover { background: rgba(255,255,255,0.2); color: white; }

        /* Split Screen Area */
        .workspace-container { display: flex; flex: 1; overflow: hidden; }
        
        /* Left: PDF Viewer */
        .pdf-panel { flex: 1; border-right: 1px solid #cbd5e1; background: #e2e8f0; display: flex; flex-direction: column; }
        .panel-header { background: #f8fafc; padding: 12px 20px; font-weight: 700; font-size: 14px; border-bottom: 1px solid #e2e8f0; color: #334155; }
        
        /* Right: Code/Writing Editor */
        .editor-panel { flex: 1; background: white; display: flex; flex-direction: column; }
        .editor-form { display: flex; flex-direction: column; flex: 1; padding: 25px; overflow-y: auto; }
        
        /* Step Styling */
        .step-label { font-size: 13px; font-weight: 700; color: #3b82f6; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
        
        .custom-textarea { flex: 1; min-height: 150px; width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 15px; font-family: monospace; font-size: 14px; resize: none; background: #f8fafc; margin-bottom: 25px; }
        .custom-textarea:focus { outline: none; border-color: #3b82f6; background: white; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        
        .file-upload-box { border: 2px dashed #cbd5e1; padding: 20px; border-radius: 8px; text-align: center; background: #f8fafc; margin-bottom: 25px; transition: 0.2s; }
        .file-upload-box:hover { border-color: #3b82f6; background: #eff6ff; }
        
        /* Confirmation Box */
        .confirm-box { background: #fffbeb; border: 1px solid #fde68a; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 12px; align-items: flex-start; }
        .confirm-box input[type="checkbox"] { margin-top: 3px; transform: scale(1.2); cursor: pointer; }
        .confirm-box label { font-size: 13px; color: #92400e; font-weight: 600; cursor: pointer; margin: 0; }

        .btn-submit { background: #10b981; color: white; border: none; padding: 14px; border-radius: 8px; font-weight: 700; font-size: 15px; text-transform: uppercase; letter-spacing: 1px; transition: 0.2s; }
        .btn-submit:hover:not(:disabled) { background: #059669; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(16,185,129,0.3); }
        .btn-submit:disabled { background: #cbd5e1; cursor: not-allowed; color: #64748b; }
    </style>
</head>
<body>

    <!-- Top Nav -->
    <div class="workspace-nav">
        <div class="d-flex align-items-center gap-3">
            <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left me-1"></i> Back</a>
            <h4 class="workspace-title">Digital Workspace - <?php echo htmlspecialchars($prac_no); ?></h4>
        </div>
        <div style="font-size: 13px; opacity: 0.8;">
            <i class="fas fa-book me-1"></i> <?php echo htmlspecialchars($subject); ?>
        </div>
    </div>

    <!-- Split Screen Workspace -->
    <div class="workspace-container">
        
        <!-- LEFT: MANUAL PDF -->
        <div class="pdf-panel">
            <div class="panel-header"><i class="fas fa-file-pdf text-danger me-2"></i> Step 1: Read Reference Manual</div>
            <?php if($pdf_path != ''): ?>
                <iframe src="../<?php echo $pdf_path; ?>" width="100%" height="100%" style="border:none;"></iframe>
            <?php else: ?>
                <div class="d-flex justify-content-center align-items-center h-100 text-muted">
                    <p>No PDF manual assigned for this practical.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: STUDENT EDITOR -->
        <div class="editor-panel">
            <div class="panel-header"><i class="fas fa-pen-nib text-primary me-2"></i> Step 2: Complete & Submit</div>
            
            <form method="POST" enctype="multipart/form-data" class="editor-form">
                <?php if($msg != "") echo $msg; ?>
                
                <div class="step-label">1. Write Observation / Code</div>
                <textarea name="answer_text" class="custom-textarea" placeholder="// Type your practical output, procedure, or code here...&#10;// Faculty will check this directly." required></textarea>
                
                <div class="step-label">2. Upload File (If Any)</div>
                <div class="file-upload-box">
                    <i class="fas fa-cloud-upload-alt text-muted mb-2" style="font-size: 24px;"></i>
                    <label class="fw-bold d-block mb-2" style="font-size: 13px; color:#475569;">Attach Screenshots or Program Files (.pdf, .zip, .jpg)</label>
                    <input type="file" name="attachment" class="form-control form-control-sm" accept=".pdf,.png,.jpg,.zip,.txt">
                </div>

                <div class="step-label">3. Confirm Submission</div>
                <div class="confirm-box">
                    <!-- Checkbox triggers the JS function -->
                    <input type="checkbox" id="confirmCheck" onchange="toggleSubmit()">
                    <label for="confirmCheck">I confirm that I have completed the practical and attached the required files. I am ready to submit this for faculty review.</label>
                </div>

                <!-- Submit button is disabled by default -->
                <button type="submit" name="submit_practical" id="submitBtn" class="btn-submit" disabled>
                    <i class="fas fa-check-circle me-2"></i> Upload & Confirm Submit
                </button>
            </form>
        </div>

    </div>

    <!-- JavaScript to control the Confirm Button -->
    <script>
        function toggleSubmit() {
            var checkBox = document.getElementById("confirmCheck");
            var btn = document.getElementById("submitBtn");
            
            if (checkBox.checked == true){
                btn.disabled = false; // Unlock button
            } else {
                btn.disabled = true;  // Lock button
            }
        }
    </script>

</body>
</html>