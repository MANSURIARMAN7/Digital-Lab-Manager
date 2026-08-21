<?php
include '../db.php';
include 'header.php';
?>

<div class="page-header mt-2 mb-4">
    <div>
        <h4 class="page-title">✅ Faculty Review & Evaluation</h4>
        <p class="page-subtitle">Assess student lab manuals, grade submissions, and provide remarks or feedback.</p>
    </div>
</div>

<div class="content-box">
    <div class="row g-4">

        <div class="col-md-6 border-end pe-md-4">
            <h6 class="fw-bold text-dark mb-3"><i class="fa-regular fa-file-pdf text-danger me-2"></i>Student Submitted Manual (PDF View)</h6>
            <div class="p-5 text-center position-relative" style="background: var(--body-bg); border: 2px dashed var(--card-border); border-radius: 12px;">
                <i class="fa-solid fa-file-pdf text-danger display-2 mb-3"></i>
                <p class="fw-semibold text-dark mb-1">Student_Manual_Rehan_Exp1.pdf</p>
                <p class="text-muted small mb-3">Size: 2.4 MB | Uploaded: Today, 11:00 AM</p>
                <button class="btn btn-outline-danger btn-sm px-4 fw-bold">
                    <i class="fa-solid fa-expand me-1"></i> Open Full Screen PDF
                </button>
            </div>
        </div>

        <div class="col-md-6 ps-md-4">
            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-feather-pointed text-primary me-2"></i>Faculty Evaluation Panel</h6>
            
            <div class="mb-3">
                <label class="form-label">Give Marks (out of 10)</label>
                <input type="number" class="form-control" placeholder="e.g. 9" min="0" max="10">
            </div>

            <div class="mb-3">
                <label class="form-label">Remarks & Feedback</label>
                <textarea class="form-control" rows="4" placeholder="Good work, neat diagrams..."></textarea>
            </div>

            <div class="d-flex gap-2 pt-2">
                <button class="btn btn-success flex-fill shadow-sm py-2 fw-bold border-0">
                    <i class="fa-solid fa-check me-1"></i> Approve
                </button>
                <button class="btn btn-danger flex-fill shadow-sm py-2 fw-bold border-0">
                    <i class="fa-solid fa-xmark me-1"></i> Reject
                </button>
                <button class="btn btn-warning text-white flex-fill shadow-sm py-2 fw-bold border-0">
                    <i class="fa-solid fa-rotate-right me-1"></i> Re-submit
                </button>
            </div>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>
