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
$pdf_path = "";
$manual_id = 0;
$manual_title = "Practical Submission";

if ($manual_query && $manual_query->num_rows > 0) {
    $manual_data = $manual_query->fetch_assoc();
    $manual_id = (int)$manual_data['id'];
    $manual_title = $manual_data['title'] ?? 'Practical Submission';
    $pdf_path = $manual_data['file_path'] ?? '';
    $pdf_path = str_replace('../', '', $pdf_path);
}

$msg = "";

// 🔥 SMART DB FIX: Add old_file_path column if not exists
$check_col = $conn->query("SHOW COLUMNS FROM student_submissions LIKE 'old_file_path'");
if ($check_col && $check_col->num_rows == 0) {
    @$conn->query("ALTER TABLE student_submissions ADD COLUMN old_file_path TEXT NULL AFTER file_path");
}

// 3. Pre-check: Already submitted?
$check_sub = $conn->query("SELECT * FROM student_submissions WHERE student_id = '$enrollment' AND subject_name = '$subject' AND practical_no = '$prac_no' LIMIT 1");
$is_submitted = ($check_sub && $check_sub->num_rows > 0);
$sub_data = $is_submitted ? $check_sub->fetch_assoc() : null;
$current_status = $sub_data['status'] ?? '';
$old_file_path = $sub_data['file_path'] ?? '';
$existing_old_history = $sub_data['old_file_path'] ?? null;

// Check if user clicked "Edit" button while in Pending status
$is_editing = (isset($_GET['edit']) && $_GET['edit'] == 'true' && $current_status === 'Pending');

// ==========================================
// 🚀 UPLOAD & EDIT LOGIC WITH SMART HISTORY
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_hidden'])) {
    
    // Agar form already Approved hai, toh ignore karo
    if ($is_submitted && $current_status === 'Approved') {
        // Do nothing
    } else {
        $answer_text = $conn->real_escape_string($_POST['answer_text'] ?? '');
        $uploaded_file = $old_file_path; // Keep current file path as fallback
        
        $new_history_path = $existing_old_history;
        
        // Handle New File Upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $target_dir = "../uploads/";
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            
            $file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.-]/", "_", basename($_FILES['attachment']['name']));
            $target_file = $target_dir . $file_name;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
                $uploaded_file = $target_file;
                
                // 🔥 SMART HISTORY & CLEANUP
                if ($current_status === 'Rejected') {
                    // Agar file reject hui thi, toh purani file delete mat karo, history me shift kar do
                    $new_history_path = $old_file_path;
                } else if ($is_editing && $current_status === 'Pending') {
                    // Agar pending state me update kar raha hai (check hi nahi hui), toh purani uda do (Cleanup)
                    if (!empty($old_file_path)) {
                        $old_path_absolute = "../" . str_replace("../", "", $old_file_path);
                        if (file_exists($old_path_absolute)) {
                            unlink($old_path_absolute);
                        }
                    }
                }
            }
        }
        
        // Update or Insert
        if ($is_submitted && ($current_status === 'Rejected' || $is_editing)) {
            $history_sql = $new_history_path ? "'$new_history_path'" : "NULL";
            $update = "UPDATE student_submissions SET file_path = '$uploaded_file', old_file_path = $history_sql, answer_text = '$answer_text', status = 'Pending', marks = 0, mark_reg=0, mark_und=0, mark_obs=0, mark_viva=0, feedback=NULL WHERE student_id = '$enrollment' AND subject_name = '$subject' AND practical_no = '$prac_no'";
            $conn->query($update);
        } else {
            $insert = "INSERT IGNORE INTO student_submissions (student_id, manual_id, subject_name, practical_no, file_path, answer_text, status) 
                       VALUES ('$enrollment', '$manual_id', '$subject', '$prac_no', '$uploaded_file', '$answer_text', 'Pending')";
            $conn->query($insert);
        }
        
        echo "<script>
            alert('Success: Practical Uploaded Successfully! Returning to Dashboard.');
            window.location.replace('Stdashboard.php');
        </script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Workspace - KDP</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --primary: #4338ca; 
            --primary-hover: #3730a3;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; height: 100vh; overflow: hidden; display: flex; flex-direction: column; margin: 0; color: var(--text-main); }
        
        .workspace-nav { background: linear-gradient(135deg, #1e3a8a 0%, #4338ca 100%); color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); z-index: 10; }
        .workspace-title { margin: 0; font-size: 18px; font-weight: 800; letter-spacing: 0.5px; }
        
        .btn-back { background: rgba(255,255,255,0.15); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 8px 18px; border-radius: 12.5px; font-weight: 600; text-decoration: none; transition: var(--transition-bounce); display: flex; align-items: center; gap: 8px;}
        .btn-back:hover { background: rgba(255,255,255,0.25); color: white; transform: translateX(-3px); }
        .workspace-meta { font-size: 14px; font-weight: 600; color: #bfdbfe; background: rgba(255,255,255,0.1); padding: 6px 15px; border-radius: 20px;}

        .workspace-container { display: flex; flex: 1; overflow: hidden; }
        
        .pdf-panel { flex: 1; border-right: 1px solid #cbd5e1; background: #e2e8f0; display: flex; flex-direction: column; }
        .panel-header { background: #f8fafc; padding: 12px 25px; font-weight: 800; font-size: 15px; border-bottom: 1px solid #cbd5e1; color: var(--text-main); display: flex; justify-content: space-between; align-items: center; }
        
        .action-buttons { display: flex; gap: 10px; }
        .btn-action { font-size: 12px; font-weight: 700; padding: 6px 15px; border-radius: 6px; display: flex; align-items: center; gap: 5px; transition: var(--transition-bounce); text-decoration: none; border: 1px solid;}
        .btn-download { background: white; color: var(--primary); border-color: #cbd5e1; }
        .btn-download:hover { background: #f1f5f9; color: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.05);}
        .btn-print { background: var(--primary); color: white; border-color: var(--primary); cursor: pointer; }
        .btn-print:hover { background: var(--primary-hover); color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(67, 56, 202, 0.2);}

        .editor-panel { width: 450px; background: var(--surface); display: flex; flex-direction: column; overflow-y: auto; }
        .editor-form-wrapper { padding: 30px; }
        
        .step-label { font-size: 12px; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;}
        .step-number { background: rgba(67, 56, 202, 0.1); color: var(--primary); width: 24px; height: 24px; border-radius: 50%; display: inline-flex; justify-content: center; align-items: center; font-size: 12px;}
        
        .custom-textarea { width: 100%; border: 2px solid #e2e8f0; border-radius: 12px; padding: 15px; font-size: 14px; color: var(--text-main); font-family: monospace; resize: vertical; min-height: 180px; transition: 0.3s; background: #f8fafc;}
        .custom-textarea:focus { outline: none; border-color: var(--primary); background: #ffffff; box-shadow: 0 0 0 4px rgba(67, 56, 202, 0.1); }
        
        .file-upload-box { border: 2px dashed #cbd5e1; border-radius: 12px; padding: 25px 20px; text-align: center; background: #f8fafc; transition: 0.3s; cursor: pointer; margin-bottom: 25px;}
        .file-upload-box:hover { border-color: var(--primary); background: rgba(67, 56, 202, 0.02); }
        .file-upload-box i { font-size: 35px; color: #94a3b8; margin-bottom: 10px; transition: 0.3s;}
        .file-upload-box:hover i { color: var(--primary); transform: translateY(-5px); }
        
        .btn-submit { background: linear-gradient(135deg, #10b981, #059669); color: white; border: none; font-weight: 800; padding: 15px; border-radius: 12px; width: 100%; font-size: 15px; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3); transition: var(--transition-bounce); letter-spacing: 0.5px; text-transform: uppercase;}
        .btn-submit:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4); }
        .btn-submit:disabled { background: #cbd5e1; box-shadow: none; cursor: not-allowed; color: #f8fafc;}

        .submitted-card-wrapper { display: flex; align-items: center; justify-content: center; height: 100%; width: 100%; background: #f8fafc; }
        .submitted-card { background: white; border: 1px solid #e2e8f0; border-radius: 20px; padding: 35px; max-width: 380px; width: 90%; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; }
        .success-icon-wrapper { width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px auto; }
        
        .badge-status { padding: 8px 20px; border-radius: 30px; font-size: 13px; font-weight: 800; display: inline-block; margin-bottom: 25px; letter-spacing: 0.5px;}
        .status-Pending { background: #fef3c7; color: #d97706; border: 1px solid #fde68a;}
        .status-Approved { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0;}
        .status-Rejected { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;}
    </style>
</head>
<body>

    <div class="workspace-nav">
        <div class="d-flex align-items-center gap-3">
            <a href="Stdashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <h4 class="workspace-title d-none d-md-block">Digital Workspace</h4>
        </div>
        <div class="workspace-meta">
            <i class="fas fa-book me-2"></i><?php echo htmlspecialchars($subject ?? 'Submission Area'); ?>
            <span class="mx-2">|</span>
            <i class="fas fa-file-code me-1"></i> <?php echo htmlspecialchars($prac_no ?? 'Practical'); ?>
        </div>
    </div>

    <div class="workspace-container">
        <div class="pdf-panel d-none d-lg-flex">
            <div class="panel-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-file-pdf text-danger fs-5"></i>
                    <span class="d-none d-xl-inline">Reference Manual:</span>
                    <span class="badge bg-light text-dark border fw-bold"><?php echo htmlspecialchars($manual_title); ?></span>
                </div>
                <div class="action-buttons">
                    <?php if($pdf_path != ""): ?>
                        <a href="../<?php echo htmlspecialchars($pdf_path); ?>" download="<?php echo htmlspecialchars($subject . '_' . $prac_no); ?>_Manual.pdf" class="btn-action btn-download" title="Download for Print shop"><i class="fas fa-download"></i> Save</a>
                        <button onclick="printPDF()" class="btn-action btn-print" title="Print Directly"><i class="fas fa-print"></i> Print</button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if($pdf_path != ""): ?>
                <iframe src="../<?php echo htmlspecialchars($pdf_path); ?>" id="pdfViewer" width="100%" height="100%" style="border:none;"></iframe>
            <?php else: ?>
                <div class="pdf-placeholder text-center mt-5">
                    <i class="fas fa-file-excel text-muted fs-1 mb-3"></i>
                    <h5 class="fw-bold text-dark">No Reference Manual</h5>
                    <small class="text-muted">The faculty has not uploaded a PDF for this practical yet.</small>
                </div>
            <?php endif; ?>
        </div>

        <div class="editor-panel">
            <?php if ($is_submitted && ($current_status === 'Pending' || $current_status === 'Approved') && !$is_editing): ?>
                <div class="submitted-card-wrapper">
                    <div class="submitted-card">
                        <div class="success-icon-wrapper" style="background: <?php echo ($current_status === 'Approved') ? 'rgba(16,185,129,0.1)' : 'rgba(245,158,11,0.1)'; ?>;">
                            <i class="fas <?php echo ($current_status === 'Approved') ? 'fa-award text-success' : 'fa-clock text-warning'; ?>" style="font-size: 35px;"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1"><?php echo ($current_status === 'Approved') ? 'Evaluation Complete' : 'Submission Received'; ?></h4>
                        <p class="text-muted small fw-semibold mb-3">Your work for <strong><?php echo htmlspecialchars($prac_no); ?></strong> is safely stored.</p>
                        <div class="badge-status status-<?php echo htmlspecialchars($current_status); ?>">Current Status: <?php echo htmlspecialchars($current_status); ?></div>

                        <?php if($current_status === 'Approved'): ?>
                            <div class="bg-light border rounded-3 p-3 mb-4 text-start shadow-sm">
                                <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fas fa-chart-bar text-primary me-2"></i> Your Scorecard</h6>
                                <div class="d-flex justify-content-between mb-1"><span class="text-muted fw-bold small">Regularity</span><span class="fw-bold text-dark"><?php echo $sub_data['mark_reg'] ?? 0; ?>/5</span></div>
                                <div class="d-flex justify-content-between mb-1"><span class="text-muted fw-bold small">Understanding</span><span class="fw-bold text-dark"><?php echo $sub_data['mark_und'] ?? 0; ?>/5</span></div>
                                <div class="d-flex justify-content-between mb-1"><span class="text-muted fw-bold small">Observation</span><span class="fw-bold text-dark"><?php echo $sub_data['mark_obs'] ?? 0; ?>/5</span></div>
                                <div class="d-flex justify-content-between mb-2 border-bottom pb-2"><span class="text-muted fw-bold small">Viva/Quiz</span><span class="fw-bold text-dark"><?php echo $sub_data['mark_viva'] ?? 0; ?>/5</span></div>
                                <div class="d-flex justify-content-between align-items-center bg-dark text-white p-2 rounded-2 mt-1">
                                    <span class="text-uppercase fw-bold small" style="letter-spacing: 1px;">Total Marks</span>
                                    <h5 class="mb-0 fw-bold text-warning"><?php echo $sub_data['marks'] ?? 0; ?> <small style="font-size: 12px; color: #cbd5e1;">/ 20</small></h5>
                                </div>
                            </div>
                            <?php if(!empty($sub_data['feedback'])): ?>
                                <div class="p-3 bg-white border-start border-4 border-info rounded text-start shadow-sm">
                                    <span class="text-muted small fw-bold d-block mb-1">Faculty Remark:</span>
                                    <span class="fst-italic text-dark fw-semibold">"<?php echo htmlspecialchars($sub_data['feedback']); ?>"</span>
                                </div>
                            <?php endif; ?>
                        <?php elseif($current_status === 'Pending'): ?>
                            <div class="mt-2 text-center border-top pt-3">
                                <p class="small text-muted fw-bold mb-2">Realized you uploaded the wrong file?</p>
                                <a href="?subject=<?php echo urlencode($subject); ?>&prac=<?php echo urlencode($prac_no); ?>&edit=true" class="btn btn-outline-warning fw-bold px-4" style="border-radius:8px;">
                                    <i class="fas fa-edit me-1"></i> Edit / Replace File
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2 mt-3">
                            <?php 
                                $student_path = $sub_data['file_path'];
                                if(!empty($student_path)) {
                                    if(strpos($student_path, '../') === false) { $student_path = '../' . $student_path; }
                                    echo "<a href='".htmlspecialchars($student_path)."' target='_blank' class='btn btn-outline-dark fw-bold py-2' style='border-radius: 10px;'><i class='fas fa-file-invoice me-2'></i> View Uploaded File</a>";
                                }
                            ?>
                            <a href="Stdashboard.php" class="btn btn-primary fw-bold py-2" style="border-radius: 10px; background: var(--primary);"><i class="fas fa-home me-2"></i> Return to Dashboard</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="editor-form-wrapper">
                    <div class="text-center mb-4 pb-3 border-bottom">
                        <h4 class="fw-bold text-dark mb-2">Complete & Submit Practical</h4>
                        <p class="text-muted small fw-semibold">Ensure all steps are completed before final submission.</p>
                    </div>

                    <?php if ($msg !== "") echo $msg; ?>

                    <?php if ($is_submitted && $current_status === 'Rejected'): ?>
                        <div class="reject-alert-box mb-4 p-3 bg-white border border-danger border-2 rounded-3 shadow-sm">
                            <h6 class="text-danger fw-bold mb-2"><i class="fas fa-exclamation-circle me-1"></i> Submission Rejected (Needs Revision)</h6>
                            <p class="small mb-2 text-dark">Your previous upload was rejected. Correct mistakes and upload a new file.</p>
                            <?php if(!empty($sub_data['feedback'])): ?>
                                <div class="bg-light p-2 rounded border border-danger">
                                    <span class="fw-bold text-danger small">Faculty Reason:</span>
                                    <span class="fst-italic text-dark">"<?php echo htmlspecialchars($sub_data['feedback']); ?>"</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($is_editing): ?>
                        <div class="alert alert-warning border-warning border-2 p-3 mb-4 rounded-3 shadow-sm">
                            <h6 class="text-dark fw-bold mb-1"><i class="fas fa-pen me-2 text-warning"></i> Editing Submission</h6>
                            <p class="small text-muted mb-0">You are replacing your pending submission. The old file will be deleted.</p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" id="submissionForm" onsubmit="return handleFormSubmit();">
                        <input type="hidden" name="submit_hidden" value="1">
                        
                        <div class="step-label"><span class="step-number">1</span> Observation / Code Output</div>
                        <textarea name="answer_text" class="custom-textarea mb-4" placeholder="Type your logic here..." required><?php echo ($is_submitted && ($current_status === 'Rejected' || $is_editing)) ? htmlspecialchars($sub_data['answer_text']) : ''; ?></textarea>
                        
                        <div class="step-label"><span class="step-number">2</span> Upload Final File</div>
                        <label class="file-upload-box d-block w-100 mb-4">
                            <i class="fas fa-cloud-upload-alt text-primary mb-2" style="font-size: 32px;"></i>
                            <div class="fw-bold text-dark" style="font-size: 15px;"><?php echo ($is_submitted && ($current_status === 'Rejected' || $is_editing)) ? 'Upload Corrected Document' : 'Upload Document'; ?></div>
                            <small class="text-muted small mb-3 d-block">Accepted formats: .pdf, .zip, .png, .jpg (Max 5MB)</small>
                            <input type="file" name="attachment" accept=".pdf,.png,.jpg,.zip,.txt" <?php echo ($is_submitted && ($current_status === 'Rejected' || $is_editing)) ? 'required' : 'required'; ?>>
                        </label>

                        <div class="step-label"><span class="step-number">3</span> Final Confirmation</div>
                        <div class="form-check mb-4">
                            <input type="checkbox" id="confirmCheck" onchange="toggleSubmit()" class="form-check-input">
                            <label for="confirmCheck" class="form-check-label small fw-semibold text-muted">I declare this submission is my original work.</label>
                        </div>
                        
                        <button type="submit" id="submitBtn" class="btn-submit mt-2" disabled>
                            <?php echo ($is_submitted && ($current_status === 'Rejected' || $is_editing)) ? '<i class="fas fa-sync-alt me-2"></i> Update Securely' : '<i class="fas fa-paper-plane me-2"></i> Submit Securely'; ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSubmit() {
            var btn = document.getElementById("submitBtn");
            var checkbox = document.getElementById("confirmCheck");
            if(checkbox && btn) { btn.disabled = !checkbox.checked; }
        }
        function handleFormSubmit() {
            var btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> Uploading...';
            return true;
        }
        function printPDF() {
            var iframe = document.getElementById('pdfViewer');
            if (iframe) {
                try { iframe.contentWindow.focus(); iframe.contentWindow.print(); } 
                catch (e) { alert("Printing blocked by browser security. Use 'Save' instead."); }
            }
        }
    </script>
</body>
</html>