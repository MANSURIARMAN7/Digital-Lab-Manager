<?php
include '../db.php';
include 'header.php';

$sub_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$submission = null;
$student = null;

if ($sub_id) {
    $res = $conn->query("SELECT * FROM submissions WHERE id = $sub_id");
    if ($res && $res->num_rows > 0) {
        $submission = $res->fetch_assoc();
        $s_res = $conn->query("SELECT * FROM users WHERE user_id = '" . $conn->real_escape_string($submission['enrollment']) . "'");
        if ($s_res) $student = $s_res->fetch_assoc();
    }
}

// Handle evaluation POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['evaluate'])) {
    $eval_id  = (int)$_POST['submission_id'];
    $marks    = (int)$_POST['marks'];
    $remark   = $conn->real_escape_string($_POST['remark']);
    $status   = $conn->real_escape_string($_POST['action_status']);
    $conn->query("UPDATE submissions SET marks='$marks', remark='$remark', status='$status' WHERE id='$eval_id'");
    $_SESSION['success_message'] = "Submission evaluated successfully.";
    header("Location: Submissions.php");
    exit();
}
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Review &amp; Marks</h1>
        <p class="page-subtitle">Assess student lab manuals, grade submissions, and provide feedback.</p>
    </div>
    <a href="Submissions.php" class="btn btn-outline">
        <i class="fas fa-arrow-left"></i> Back to Submissions
    </a>
</div>

<?php if($submission): ?>
<!-- SUBMISSION REVIEW PANEL -->
<div class="row g-4">
    <!-- LEFT: PDF VIEWER -->
    <div class="col-md-6">
        <div class="content-box h-100">
            <div class="section-title"><i class="fas fa-file-pdf" style="color: #ef4444;"></i> Submitted Manual</div>

            <!-- Student Info -->
            <div style="display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: var(--body-bg); border-radius: 10px; margin-bottom: 16px;">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['name'] ?? $submission['enrollment']); ?>&background=3b82f6&color=fff&bold=true&size=64"
                     style="width: 44px; height: 44px; border-radius: 11px;" alt="">
                <div>
                    <div style="font-weight: 700; font-size: 14.5px;"><?php echo htmlspecialchars($student['name'] ?? 'Unknown Student'); ?></div>
                    <div style="font-size: 12px; color: var(--text-muted);">
                        Enrollment: <?php echo htmlspecialchars($submission['enrollment']); ?> &nbsp;|&nbsp; Subject: <strong><?php echo htmlspecialchars($submission['subject']); ?></strong>
                    </div>
                </div>
                <div style="margin-left: auto;">
                    <?php
                        $badge = 'badge-pending';
                        if($submission['status'] == 'Approved') $badge = 'badge-approved';
                        if($submission['status'] == 'Rejected') $badge = 'badge-rejected';
                    ?>
                    <span class="badge-status <?php echo $badge; ?>"><?php echo $submission['status']; ?></span>
                </div>
            </div>

            <div style="background: #f8fafc; border: 2px dashed var(--card-border); border-radius: 12px; padding: 32px 20px; text-align: center;">
                <i class="fas fa-file-pdf" style="font-size: 44px; color: #ef4444; margin-bottom: 10px;"></i>
                <p style="font-weight: 700; font-size: 14px; color: var(--text-primary); margin: 0 0 4px;">
                    <?php echo basename($submission['file_path']); ?>
                </p>
                <p style="font-size: 12px; color: var(--text-muted); margin: 0 0 16px;">
                    Submitted: <?php echo date('d M Y, h:i A', strtotime($submission['upload_date'])); ?>
                </p>
                <a href="<?php echo htmlspecialchars($submission['file_path']); ?>" target="_blank"
                   class="btn btn-danger" style="background: #ef4444; box-shadow: 0 4px 12px rgba(239,68,68,0.3);">
                    <i class="fas fa-expand"></i> Open Full PDF
                </a>
            </div>

            <?php if($submission['remark']): ?>
            <div style="margin-top: 14px; padding: 12px 16px; background: var(--yellow-light); border-radius: 10px; border-left: 3px solid var(--yellow);">
                <div style="font-size: 11.5px; font-weight: 700; color: #92400e; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">Previous Remark</div>
                <div style="font-size: 13.5px; color: #78350f;"><?php echo htmlspecialchars($submission['remark']); ?></div>
                <?php if($submission['marks'] !== null): ?>
                    <div style="margin-top: 6px; font-size: 12px; color: #92400e;">Marks given: <strong><?php echo $submission['marks']; ?>/10</strong></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT: EVALUATION PANEL -->
    <div class="col-md-6">
        <div class="content-box h-100">
            <div class="section-title"><i class="fas fa-feather-pointed"></i> Faculty Evaluation Panel</div>

            <form method="POST" style="display: grid; gap: 16px;">
                <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                <input type="hidden" name="action_status" id="actionStatus" value="Approved">

                <div>
                    <label class="form-label">Marks (out of 10)</label>
                    <div class="input-with-icon">
                        <i class="fas fa-star"></i>
                        <input type="number" name="marks" class="form-control" placeholder="e.g. 8" min="0" max="10"
                               value="<?php echo htmlspecialchars($submission['marks'] ?? ''); ?>">
                    </div>
                </div>

                <div>
                    <label class="form-label">Remarks &amp; Feedback</label>
                    <textarea name="remark" class="form-control" rows="5"
                              placeholder="Good work, neat diagrams, improve..." style="resize: vertical;"><?php echo htmlspecialchars($submission['remark'] ?? ''); ?></textarea>
                </div>

                <!-- Grade Slider Visual -->
                <div style="padding: 14px 16px; background: var(--body-bg); border-radius: 10px;">
                    <div style="font-size: 11.5px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">Quick Grade</div>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                        <?php for($i=1; $i<=10; $i++): ?>
                            <button type="button" onclick="document.querySelector('[name=marks]').value='<?php echo $i; ?>'"
                                    style="width: 34px; height: 34px; border-radius: 8px; border: 1.5px solid var(--card-border); background: #fff; font-size: 13px; font-weight: 600; color: var(--text-secondary); cursor: pointer; transition: all 0.15s; <?php if(($submission['marks'] ?? '') == $i) echo 'background: var(--brand-accent); color: #fff; border-color: var(--brand-accent);'; ?>"
                                    onmouseover="this.style.background='var(--brand-accent)'; this.style.color='#fff'; this.style.borderColor='var(--brand-accent)';"
                                    onmouseout="this.style.background='#fff'; this.style.color='var(--text-secondary)'; this.style.borderColor='var(--card-border)';">
                                <?php echo $i; ?>
                            </button>
                        <?php endfor; ?>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-top: 4px;">
                    <button type="submit" name="evaluate" class="btn btn-success"
                            onclick="document.getElementById('actionStatus').value='Approved';">
                        <i class="fas fa-check"></i> Approve
                    </button>
                    <button type="submit" name="evaluate" class="btn btn-danger"
                            onclick="document.getElementById('actionStatus').value='Rejected';">
                        <i class="fas fa-xmark"></i> Reject
                    </button>
                    <button type="submit" name="evaluate" class="btn btn-warning"
                            onclick="document.getElementById('actionStatus').value='Pending';">
                        <i class="fas fa-rotate-right"></i> Re-submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php else: ?>

<!-- NO SUBMISSION SELECTED -->
<div class="content-box" style="text-align: center; padding: 60px 24px;">
    <i class="fas fa-file-signature" style="font-size: 52px; color: #cbd5e1; margin-bottom: 16px;"></i>
    <h4 style="font-size: 18px; font-weight: 700; color: var(--text-secondary); margin-bottom: 6px;">No Submission Selected</h4>
    <p style="color: var(--text-muted); font-size: 13.5px; margin-bottom: 20px;">Open a submission from the Submissions page to begin evaluation.</p>
    <a href="Submissions.php" class="btn btn-primary">
        <i class="fas fa-folder-open"></i> View Submissions
    </a>
</div>

<?php endif; ?>

<?php include 'footer.php'; ?>
