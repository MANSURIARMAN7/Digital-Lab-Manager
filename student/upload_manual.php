<?php
/**
 * Practical Submission Page (Enhanced)
 * -------------------------------------
 * Allows students to upload practical work (PDF/zip/image) with optional
 * answer text. Displays the reference manual PDF on the left panel.
 *
 * Security: Prepared statements, file validation, XSS prevention.
 * UX: Shows submission status, feedback, and allows re-submission if rejected.
 */

session_start();
include '../db.php';

// -----------------------------------------------------------------------------
// 1. SECURE LOGIN CHECK
// -----------------------------------------------------------------------------
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}
$enrollment = (string) $_SESSION['user_id'];

// -----------------------------------------------------------------------------
// 2. GET SUBJECT & PRACTICAL NO FROM URL
// -----------------------------------------------------------------------------
$subject = isset($_GET['subject']) ? trim($_GET['subject']) : '';
$prac_no = isset($_GET['prac']) ? trim($_GET['prac']) : '';

// If subject or prac_no is missing, redirect to dashboard with error
if (empty($subject) || empty($prac_no)) {
    header("Location: Stdashboard.php?error=missing_params");
    exit();
}

// -----------------------------------------------------------------------------
// 3. FETCH REFERENCE MANUAL (using prepared statements)
// -----------------------------------------------------------------------------
$manual_sql = "SELECT * FROM lab_manuals WHERE subject_name = ? AND practical_no = ? LIMIT 1";
$manual_stmt = $conn->prepare($manual_sql);
$manual_stmt->bind_param("ss", $subject, $prac_no);
$manual_stmt->execute();
$manual_result = $manual_stmt->get_result();
$manual_data = $manual_result->fetch_assoc();
$manual_stmt->close();

$manual_id = $manual_data['id'] ?? 0;
$manual_title = $manual_data['title'] ?? 'Practical Submission';
$pdf_path = $manual_data['file_path'] ?? '';
// Normalize path: remove leading '../' if exists, because we prepend later
$pdf_path = ltrim($pdf_path, '../');

// -----------------------------------------------------------------------------
// 4. CHECK IF STUDENT HAS ALREADY SUBMITTED THIS PRACTICAL
// -----------------------------------------------------------------------------
$sub_sql = "SELECT * FROM student_submissions 
            WHERE student_id = ? AND subject_name = ? AND practical_no = ? 
            ORDER BY submitted_at DESC LIMIT 1";
$sub_stmt = $conn->prepare($sub_sql);
$sub_stmt->bind_param("sss", $enrollment, $subject, $prac_no);
$sub_stmt->execute();
$sub_result = $sub_stmt->get_result();
$sub_data = $sub_result->fetch_assoc();
$sub_stmt->close();

$is_submitted = ($sub_data && !empty($sub_data['id']));
$current_status = $sub_data['status'] ?? '';
$allow_resubmit = ($current_status === 'Rejected'); // Only if rejected

// -----------------------------------------------------------------------------
// 5. HANDLE FORM SUBMISSION (with file validation and prepared statements)
// -----------------------------------------------------------------------------
$msg = "";
$msg_type = "success";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_hidden'])) {
    // Check if resubmission is allowed (only if rejected or first time)
    if ($is_submitted && !$allow_resubmit) {
        $msg = "You have already submitted this practical. Re-submission is not allowed unless it was rejected.";
        $msg_type = "error";
    } else {
        // Get answer text
        $answer_text = isset($_POST['answer_text']) ? trim($_POST['answer_text']) : '';
        $answer_text = htmlspecialchars($answer_text, ENT_QUOTES, 'UTF-8'); // for safe display later

        // File upload handling
        $uploaded_file = "";
        $file_error = false;
        $max_file_size = 5 * 1024 * 1024; // 5MB
        $allowed_extensions = ['pdf', 'zip', 'png', 'jpg', 'jpeg', 'txt'];

        if (isset($_FILES["attachment"]) && $_FILES["attachment"]["error"] == 0) {
            $file = $_FILES["attachment"];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            // Validate file size
            if ($file_size > $max_file_size) {
                $msg = "File size exceeds 5MB limit. Please upload a smaller file.";
                $msg_type = "error";
                $file_error = true;
            }
            // Validate file extension
            if (!in_array($file_ext, $allowed_extensions)) {
                $msg = "Invalid file type. Allowed: " . implode(', ', $allowed_extensions);
                $msg_type = "error";
                $file_error = true;
            }

            if (!$file_error) {
                $target_dir = "../uploads/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                // Create a unique filename
                $new_filename = time() . "_" . preg_replace("/[^a-zA-Z0-9.-]/", "_", basename($file_name));
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($file_tmp, $target_file)) {
                    $uploaded_file = $target_file;
                } else {
                    $msg = "Error moving uploaded file. Please try again.";
                    $msg_type = "error";
                    $file_error = true;
                }
            }
        } else {
            // No file uploaded – if it's a resubmission, we might allow updating only text? But we require file.
            $msg = "Please select a file to upload.";
            $msg_type = "error";
            $file_error = true;
        }

        // If no errors, insert/update the submission
        if (!$file_error && !empty($uploaded_file)) {
            // If resubmission (rejected), update existing record; else insert new
            if ($allow_resubmit && $is_submitted) {
                // Update the existing record
                $update_sql = "UPDATE student_submissions 
                               SET file_path = ?, answer_text = ?, status = 'Pending', updated_at = NOW()
                               WHERE id = ? AND student_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssis", $uploaded_file, $answer_text, $sub_data['id'], $enrollment);
                if ($update_stmt->execute()) {
                    $msg = "Your submission has been updated successfully. It is now pending review again.";
                    $msg_type = "success";
                } else {
                    $msg = "Database update failed: " . $conn->error;
                    $msg_type = "error";
                }
                $update_stmt->close();
            } else {
                // Insert new submission
                $insert_sql = "INSERT INTO student_submissions 
                               (student_id, manual_id, subject_name, practical_no, file_path, answer_text, status) 
                               VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("sissss", $enrollment, $manual_id, $subject, $prac_no, $uploaded_file, $answer_text);
                if ($insert_stmt->execute()) {
                    $msg = "Success: Practical Uploaded Successfully!";
                    $msg_type = "success";
                    // Refresh submission data to show updated status
                    $is_submitted = true;
                    $current_status = 'Pending';
                    // Fetch new record to update $sub_data
                    $new_sub_sql = "SELECT * FROM student_submissions WHERE student_id = ? AND subject_name = ? AND practical_no = ? ORDER BY submitted_at DESC LIMIT 1";
                    $new_sub_stmt = $conn->prepare($new_sub_sql);
                    $new_sub_stmt->bind_param("sss", $enrollment, $subject, $prac_no);
                    $new_sub_stmt->execute();
                    $new_sub_result = $new_sub_stmt->get_result();
                    $sub_data = $new_sub_result->fetch_assoc();
                    $new_sub_stmt->close();
                } else {
                    $msg = "Database insert failed: " . $conn->error;
                    $msg_type = "error";
                }
                $insert_stmt->close();
            }
        }
    }
}

// If success, redirect after 3 seconds (auto redirect with JavaScript)
$auto_redirect = ($msg_type === 'success' && !empty($msg));
if ($auto_redirect) {
    $redirect_url = "Stdashboard.php";
    // we'll add a meta refresh or JS later
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical Submission - KDP</title>

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

        body {
            background-color: var(--bg-body);
            font-family: 'Plus Jakarta Sans', sans-serif;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            margin: 0;
            color: var(--text-main);
        }

        /* 🌐 TOP NAVIGATION BAR */
        .workspace-nav {
            background: linear-gradient(135deg, #1e3a8a 0%, #4338ca 100%);
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            z-index: 10;
            flex-shrink: 0;
        }
        .workspace-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        .btn-back {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition-bounce);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-back:hover {
            background: rgba(255,255,255,0.25);
            color: white;
            transform: translateY(-2px);
        }
        .workspace-meta {
            font-size: 13px;
            font-weight: 600;
            color: #bfdbfe;
            background: rgba(255,255,255,0.1);
            padding: 6px 15px;
            border-radius: 20px;
        }

        /* 💻 SPLIT WORKSPACE */
        .workspace-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        /* LEFT: PDF VIEWER */
        .pdf-panel {
            flex: 1;
            border-right: 1px solid #cbd5e1;
            background: #e2e8f0;
            display: flex;
            flex-direction: column;
        }
        .panel-header {
            background: #f8fafc;
            padding: 12px 25px;
            font-weight: 800;
            font-size: 15px;
            border-bottom: 1px solid #cbd5e1;
            color: var(--text-main);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-action {
            font-size: 12.5px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition-bounce);
            text-decoration: none;
            border: 1px solid;
        }
        .btn-download {
            background: white;
            color: var(--primary);
            border-color: #cbd5e1;
        }
        .btn-download:hover {
            background: #f1f5f9;
            color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .btn-print {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            cursor: pointer;
        }
        .btn-print:hover {
            background: var(--primary-hover);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(67,56,202,0.2);
        }

        .pdf-placeholder {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            color: var(--text-muted);
            text-align: center;
            padding: 20px;
        }
        .pdf-placeholder i {
            font-size: 50px;
            opacity: 0.3;
            margin-bottom: 15px;
        }

        /* RIGHT: EDITOR FORM */
        .editor-panel {
            flex: 1;
            background: var(--surface);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0;
            overflow-y: auto;
        }
        .editor-form-wrapper {
            width: 100%;
            max-width: 650px;
            margin: auto;
            padding: 40px;
        }

        .step-label {
            font-size: 13px;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .step-number {
            background: var(--primary);
            color: white;
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 11px;
        }

        .custom-textarea {
            width: 100%;
            min-height: 180px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            padding: 20px;
            font-family: 'Consolas', monospace;
            font-size: 14.5px;
            resize: vertical;
            background: #f1f5f9;
            color: #334155;
            margin-bottom: 20px;
            transition: var(--transition-bounce);
        }
        .custom-textarea:focus {
            border-color: var(--primary);
            background: #ffffff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(67,56,202,0.1);
        }
        .custom-textarea:disabled {
            background: #e2e8f0;
            color: #64748b;
            cursor: not-allowed;
        }

        .file-upload-box {
            border: 2px dashed #cbd5e1;
            padding: 25px 20px;
            border-radius: 12px;
            text-align: center;
            background: #f8fafc;
            margin-bottom: 20px;
            transition: var(--transition-bounce);
            cursor: pointer;
        }
        .file-upload-box:hover {
            border-color: var(--primary);
            background: #eff6ff;
        }
        input[type="file"] {
            padding: 8px;
            background: white;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            width: 80%;
            margin: 0 auto;
            display: block;
            font-size: 13px;
        }
        input[type="file"]:disabled {
            background: #e2e8f0;
            cursor: not-allowed;
        }

        .hard-copy-note {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .hard-copy-note i {
            font-size: 20px;
            color: var(--primary);
            margin-top: 2px;
        }

        .confirm-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: flex-start;
            text-align: left;
        }
        .confirm-box input[type="checkbox"] {
            margin-top: 4px;
            transform: scale(1.3);
            cursor: pointer;
            accent-color: #d97706;
        }
        .confirm-box label {
            font-size: 14px;
            color: #92400e;
            font-weight: 700;
            cursor: pointer;
            margin: 0;
            line-height: 1.5;
        }

        .btn-submit {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: var(--transition-bounce);
            width: 100%;
            box-shadow: 0 4px 15px rgba(16,185,129,0.3);
        }
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16,185,129,0.4);
        }
        .btn-submit:disabled {
            background: #cbd5e1;
            box-shadow: none;
            cursor: not-allowed;
            color: #64748b;
            transform: none;
        }

        /* SUCCESS / STATUS CARD */
        .submitted-card-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            width: 100%;
            background: #f8fafc;
            padding: 20px;
        }
        .submitted-card {
            background: white;
            border: 1px solid #cbd5e1;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            text-align: center;
        }
        .success-icon-wrapper {
            width: 80px;
            height: 80px;
            background: rgba(16,185,129,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px auto;
        }
        .success-icon-wrapper i {
            font-size: 40px;
            color: #10b981;
        }
        .submitted-card h3 {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 10px;
        }
        .submitted-card p {
            font-size: 14.5px;
            color: var(--text-muted);
            font-weight: 500;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        .badge-status {
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 800;
            display: inline-block;
            margin-bottom: 30px;
            letter-spacing: 0.5px;
        }
        .status-Pending {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        .status-Approved {
            background: #d1fae5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        .status-Rejected {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-custom {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }

        .feedback-box {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            text-align: left;
            border-left: 4px solid var(--primary);
        }
        .feedback-box strong {
            color: var(--text-main);
        }

        /* Responsive tweaks */
        @media (max-width: 992px) {
            .pdf-panel {
                display: none !important;
            }
            .editor-panel {
                flex: 1;
            }
        }
    </style>
</head>
<body>

    <!-- ============================================================
    TOP NAVIGATION
    ============================================================ -->
    <div class="workspace-nav">
        <div class="d-flex align-items-center gap-3">
            <a href="Stdashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <h4 class="workspace-title d-none d-md-block">Digital Workspace</h4>
        </div>
        <div class="workspace-meta">
            <i class="fas fa-book me-2"></i> <?php echo htmlspecialchars($subject ?: 'Submission Area'); ?>
            <span class="mx-2">|</span>
            <i class="fas fa-file-code me-1"></i> <?php echo htmlspecialchars($prac_no ?: 'Practical'); ?>
        </div>
    </div>

    <!-- ============================================================
    WORKSPACE CONTAINER
    ============================================================ -->
    <div class="workspace-container">

        <!-- LEFT PANEL: PDF VIEWER -->
        <div class="pdf-panel d-none d-lg-flex">
            <div class="panel-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-file-pdf text-danger fs-5"></i>
                    <span class="d-none d-xl-inline">Reference Manual:</span>
                    <span class="badge bg-light text-dark border fw-bold"><?php echo htmlspecialchars($manual_title); ?></span>
                </div>
                <div class="action-buttons">
                    <?php if (!empty($pdf_path)): ?>
                        <a href="../<?php echo htmlspecialchars($pdf_path); ?>" download="<?php echo htmlspecialchars($subject.'_'.$prac_no); ?>_Manual.pdf" class="btn-action btn-download" title="Download for Print Shop">
                            <i class="fas fa-download"></i> Save
                        </a>
                        <button onclick="printPDF()" class="btn-action btn-print" title="Print Directly">
                            <i class="fas fa-print"></i> Print
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($pdf_path)): ?>
                <iframe src="../<?php echo htmlspecialchars($pdf_path); ?>" id="pdfViewer" width="100%" height="100%" style="border:none;"></iframe>
            <?php else: ?>
                <div class="pdf-placeholder">
                    <i class="fas fa-file-excel"></i>
                    <h5 class="fw-bold text-dark">No Reference Manual</h5>
                    <p class="small">The faculty has not uploaded a PDF for this practical yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT PANEL: EDITOR / STATUS -->
        <div class="editor-panel">

            <?php if ($is_submitted && !$allow_resubmit): ?>
                <!-- ============================================================
                ALREADY SUBMITTED (with status and feedback)
                ============================================================ -->
                <div class="submitted-card-wrapper">
                    <div class="submitted-card">
                        <div class="success-icon-wrapper">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3>Practical Submitted</h3>
                        <p>Your work for <strong><?php echo htmlspecialchars($prac_no); ?></strong> has been uploaded on <strong><?php echo date('d M Y, h:i A', strtotime($sub_data['submitted_at'])); ?></strong>.</p>

                        <div class="badge-status status-<?php echo htmlspecialchars($sub_data['status'] ?? 'Pending'); ?>">
                            Current Status: <?php echo htmlspecialchars($sub_data['status'] ?? 'Pending'); ?>
                        </div>

                        <?php if (!empty($sub_data['remarks'])): ?>
                            <div class="feedback-box">
                                <strong><i class="fas fa-comment-dots me-2"></i> Faculty Feedback:</strong>
                                <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($sub_data['remarks'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($sub_data['marks']) && $sub_data['status'] == 'Approved'): ?>
                            <div class="alert alert-success fw-bold" style="border-radius:10px;">
                                <i class="fas fa-star text-warning me-2"></i> Marks: <?php echo $sub_data['marks']; ?>/20
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-3 mt-3">
                            <?php
                            $student_path = $sub_data['file_path'];
                            if (strpos($student_path, '../') === false && !empty($student_path)) {
                                $student_path = '../' . $student_path;
                            }
                            ?>
                            <?php if (!empty($sub_data['file_path'])): ?>
                                <a href="<?php echo htmlspecialchars($student_path); ?>" target="_blank" class="btn btn-outline-secondary fw-bold py-2" style="border-radius: 10px;">
                                    <i class="fas fa-file-invoice me-2"></i> View My Uploaded File
                                </a>
                            <?php endif; ?>
                            <a href="Stdashboard.php" class="btn btn-primary fw-bold py-2" style="border-radius: 10px; background: var(--primary);">
                                <i class="fas fa-home me-2"></i> Return to Dashboard
                            </a>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- ============================================================
                SUBMISSION FORM (first time OR rejected → allow resubmit)
                ============================================================ -->
                <div class="editor-form-wrapper">

                    <div class="text-center mb-4 pb-3 border-bottom">
                        <h4 class="fw-bold text-dark mb-2">
                            <?php echo ($allow_resubmit) ? 'Re-submit Practical' : 'Complete & Submit Practical'; ?>
                        </h4>
                        <p class="text-muted small fw-semibold">
                            <?php if ($allow_resubmit): ?>
                                Your previous submission was rejected. Please review the feedback and upload a revised version.
                            <?php else: ?>
                                Ensure all steps are completed before final submission.
                            <?php endif; ?>
                        </p>
                    </div>

                    <!-- Display messages -->
                    <?php if (!empty($msg)): ?>
                        <div class="alert-custom alert-<?php echo $msg_type; ?>">
                            <i class="fas fa-<?php echo ($msg_type == 'success') ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                            <?php echo htmlspecialchars($msg); ?>
                        </div>
                        <?php if ($msg_type == 'success'): ?>
                            <meta http-equiv="refresh" content="3;url=Stdashboard.php">
                            <div class="text-center text-muted small mt-2">Redirecting to dashboard in 3 seconds...</div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- If rejected, show feedback -->
                    <?php if ($allow_resubmit && !empty($sub_data['remarks'])): ?>
                        <div class="feedback-box">
                            <strong><i class="fas fa-comment-dots me-2"></i> Faculty Feedback:</strong>
                            <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($sub_data['remarks'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Submission Form -->
                    <form method="POST" enctype="multipart/form-data" id="submissionForm" onsubmit="return handleFormSubmit();">
                        <input type="hidden" name="submit_hidden" value="1">

                        <!-- Step 1: Answer Text -->
                        <div class="step-label"><span class="step-number">1</span> Observation / Code Output</div>
                        <textarea name="answer_text" class="custom-textarea" placeholder="// Type your practical logic, output, procedure, or rough notes here...&#10;// This helps faculties review code snippets quickly." <?php echo ($allow_resubmit && isset($sub_data['answer_text'])) ? '' : 'required'; ?>><?php echo htmlspecialchars($sub_data['answer_text'] ?? ''); ?></textarea>

                        <!-- Step 2: File Upload -->
                        <div class="step-label"><span class="step-number">2</span> Upload Final File</div>
                        <div class="file-upload-box">
                            <i class="fas fa-cloud-upload-alt text-primary mb-3" style="font-size: 32px;"></i>
                            <label class="fw-bold d-block mb-1 text-dark" style="font-size: 15px;">Upload Document or Photos</label>
                            <p class="text-muted small mb-3">Accepted formats: .pdf, .zip, .png, .jpg (Max 5MB)</p>
                            <input type="file" name="attachment" accept=".pdf,.png,.jpg,.zip,.txt" required>
                            <?php if ($allow_resubmit && !empty($sub_data['file_path'])): ?>
                                <small class="d-block text-muted mt-2">Previous file: <?php echo basename($sub_data['file_path']); ?></small>
                            <?php endif; ?>
                        </div>

                        <!-- Step 3: Confirmation -->
                        <div class="step-label"><span class="step-number">3</span> Final Confirmation</div>
                        <div class="confirm-box">
                            <input type="checkbox" id="confirmCheck" onchange="toggleSubmit()">
                            <label for="confirmCheck">I declare that this submission is my original work and I have attached the correct files.</label>
                        </div>

                        <button type="submit" id="submitBtn" class="btn-submit mt-2" disabled>
                            <i class="fas fa-paper-plane me-2"></i>
                            <?php echo ($allow_resubmit) ? 'Re-submit Practical' : 'Submit Practical Securely'; ?>
                        </button>
                    </form>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================
    SCRIPTS
    ============================================================ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSubmit() {
            var checkBox = document.getElementById("confirmCheck");
            var btn = document.getElementById("submitBtn");
            if (checkBox && btn) {
                btn.disabled = !checkBox.checked;
            }
        }

        function handleFormSubmit() {
            var btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> Uploading Securely...';
            return true;
        }

        function printPDF() {
            var iframe = document.getElementById('pdfViewer');
            if (iframe) {
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) {
                    alert("Printing directly from the viewer is blocked by your browser's security. Please use the 'Save' button to download and print it.");
                }
            }
        }
    </script>
</body>
</html>