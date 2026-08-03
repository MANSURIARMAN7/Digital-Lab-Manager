<?php
include 'header.php';
?>

<h4 class="fw-bold text-dark mb-4">
    ✅ Faculty Review & Evaluation
</h4>

<div class="content-card">
    <div class="row">

        <div class="col-md-6 border-end">
            <h6 class="fw-bold mb-3">Student Submitted Manual (PDF View)</h6>
            <div class="p-4 bg-light text-center border rounded">
                <i class="fa-solid fa-file-pdf text-danger display-3"></i>
                <p class="mt-2 text-muted mb-0">Student_Manual_Rehan_Exp1.pdf</p>
                <button class="btn btn-sm btn-primary mt-2">Open Full Screen PDF</button>
            </div>
        </div>

        <div class="col-md-6 ps-4">
            <h6 class="fw-bold mb-3">Faculty Action Box</h6>
            <div class="mb-3">
                <label class="form-label">Give Marks (out of 10)</label>
                <input type="number" class="form-control" placeholder="e.g. 9">
            </div>

            <div class="mb-3">
                <label class="form-label">Remarks</label>
                <textarea class="form-control" rows="3" placeholder="Good work, neat diagrams..."></textarea>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-success flex-fill">
                    <i class="fa-solid fa-check"></i> Approve
                </button>
                <button class="btn btn-danger flex-fill">
                    <i class="fa-solid fa-xmark"></i> Reject
                </button>
                <button class="btn btn-warning flex-fill text-white">
                    <i class="fa-solid fa-rotate-right"></i> Re-submit
                </button>
            </div>
        </div>

    </div>
</div>

<?php
include 'footer.php';
?>
