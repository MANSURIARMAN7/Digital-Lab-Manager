<?php
include '../db.php';
include 'header.php';
?>

<div class="page-header mt-2 mb-4">
    <div>
        <h4 class="page-title">📤 Student Submissions</h4>
        <p class="page-subtitle">Monitor, review, and evaluate practical manual submissions uploaded by students.</p>
    </div>
</div>

<div class="content-box">
    <!-- Advanced Search & Filters -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col-md-5">
            <div class="search-box w-100">
                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                <input type="text" placeholder="Search by student name or enrollment...">
            </div>
        </div>
        <div class="col-md-7 d-flex justify-content-md-end gap-2">
            <select class="form-select w-auto">
                <option value="">All Subjects</option>
                <option value="DBMS">Database Systems (DBMS)</option>
                <option value="DS">Data Structures (DS)</option>
            </select>
            <select class="form-select w-auto">
                <option value="">Status: All</option>
                <option value="pending">⏳ Under Review</option>
                <option value="approved">✅ Approved</option>
                <option value="rejected">❌ Rejected</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Practical No</th>
                    <th>Upload Date</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name=Belim+Hamza&background=10b981&color=fff&bold=true" class="rounded-circle shadow-sm" width="36" alt="Student">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.88rem;">Belim Hamza</h6>
                                <small class="text-muted">Enrollment: 7003</small>
                            </div>
                        </div>
                    </td>
                    <td class="fw-semibold text-dark">Exp #01 - SQL Queries</td>
                    <td>Today, 11:00 AM</td>
                    <td>
                        <span class="badge-status badge-pending">Under Review ⏳</span>
                    </td>
                    <td class="text-end">
                        <a href="Review & Marks.php" class="btn btn-sm btn-primary py-1 px-3 shadow-none">
                            <i class="fa-solid fa-file-signature me-1"></i> Evaluate
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
