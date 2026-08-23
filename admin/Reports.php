<?php
include 'header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">📊 Reports & Analytics</h4>
        <p class="text-muted small mb-0">Generate, view, and export comprehensive system reports.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-danger shadow-sm border-danger border-opacity-50">
            <i class="fa-solid fa-file-pdf me-1"></i> Export Master PDF
        </button>
        <button class="btn btn-outline-success shadow-sm border-success border-opacity-50">
            <i class="fa-solid fa-file-excel me-1"></i> Export Master Excel
        </button>
    </div>
</div>

<!-- Smart Filters Section -->
<div class="content-card border-0 shadow-sm mb-4 p-3 bg-white rounded-3">
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label text-muted small fw-semibold mb-1">Department</label>
            <select class="form-select bg-light border-0 shadow-none text-muted font-sm py-2">
                <option value="">All Departments</option>
                <option value="CE">Computer Engineering (CE)</option>
                <option value="IT">Information Tech. (IT)</option>
                <option value="ME">Mechanical Eng. (ME)</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label text-muted small fw-semibold mb-1">Semester / Batch</label>
            <select class="form-select bg-light border-0 shadow-none text-muted font-sm py-2">
                <option value="">All Batches</option>
                <option value="sem4">Semester 4 (2024)</option>
                <option value="sem6">Semester 6 (2024)</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label text-muted small fw-semibold mb-1">Date Range</label>
            <div class="input-group">
                <input type="date" class="form-control bg-light border-0 shadow-none text-muted font-sm py-2">
                <span class="input-group-text bg-light border-0 text-muted">to</span>
                <input type="date" class="form-control bg-light border-0 shadow-none text-muted font-sm py-2">
            </div>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100 py-2 shadow-sm">
                <i class="fa-solid fa-filter me-1"></i> Apply Filter
            </button>
        </div>
    </div>
</div>

<!-- Report Modules / Cards -->
<div class="row g-4">

    <!-- Report Card 1 -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm rounded-4" style="transition: transform 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-user-graduate fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Student Academic Report</h6>
                        <span class="badge bg-light text-secondary border mt-1">Enrollment & Status</span>
                    </div>
                </div>
                <p class="text-muted small mb-4">Complete list of active, inactive, and dropped out students filtered by batch and department.</p>
                <div class="d-flex gap-2 mt-auto">
                    <button class="btn btn-sm btn-light text-primary flex-grow-1 fw-medium"><i class="fa-solid fa-eye me-1"></i> View</button>
                    <button class="btn btn-sm btn-light text-success flex-grow-1 fw-medium"><i class="fa-solid fa-download me-1"></i> CSV</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Card 2 -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm rounded-4" style="transition: transform 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-file-circle-check fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Submission Status</h6>
                        <span class="badge bg-light text-secondary border mt-1">Lab Manuals</span>
                    </div>
                </div>
                <p class="text-muted small mb-4">Detailed statistics on pending, approved, and rejected manual submissions across all subjects.</p>
                <div class="d-flex gap-2 mt-auto">
                    <button class="btn btn-sm btn-light text-primary flex-grow-1 fw-medium"><i class="fa-solid fa-eye me-1"></i> View</button>
                    <button class="btn btn-sm btn-light text-success flex-grow-1 fw-medium"><i class="fa-solid fa-download me-1"></i> CSV</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Card 3 -->
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm rounded-4" style="transition: transform 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-3 d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-chalkboard-user fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Faculty Workload</h6>
                        <span class="badge bg-light text-secondary border mt-1">Review Tracking</span>
                    </div>
                </div>
                <p class="text-muted small mb-4">Track how many manuals each faculty member has reviewed and how many are pending in their queue.</p>
                <div class="d-flex gap-2 mt-auto">
                    <button class="btn btn-sm btn-light text-primary flex-grow-1 fw-medium"><i class="fa-solid fa-eye me-1"></i> View</button>
                    <button class="btn btn-sm btn-light text-success flex-grow-1 fw-medium"><i class="fa-solid fa-download me-1"></i> CSV</button>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
include 'footer.php';
?>
