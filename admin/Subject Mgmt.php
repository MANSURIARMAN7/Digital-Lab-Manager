<?php
include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">📚 Subject Management</h4>
        <p class="text-muted small mb-0">Create, edit, and assign academic course subjects to registered faculty members.</p>
    </div>
    <button class="btn btn-primary shadow-sm">
        <i class="fa-solid fa-plus me-1"></i> Add New Subject
    </button>
</div>

<div class="content-card border-0 shadow-sm">
    <!-- Advanced Search & Filters -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-0 py-2">
                    <i class="fa-solid fa-magnifying-glass text-muted"></i>
                </span>
                <input type="text" class="form-control bg-light border-0 shadow-none py-2" placeholder="Search by subject name or code...">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Subject Code</th>
                    <th>Subject Name</th>
                    <th>Semester</th>
                    <th>Assigned Faculty</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">CS401</span></td>
                    <td class="fw-semibold text-dark">Database Management Systems</td>
                    <td>Semester 4</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="https://ui-avatars.com/api/?name=Dr.+Amit+Kapoor&background=2563eb&color=fff&bold=true" class="rounded-circle shadow-sm" width="28" alt="Faculty">
                            <span>Dr. Amit Kapoor</span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
include 'footer.php';
?>
