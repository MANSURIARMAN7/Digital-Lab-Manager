<?php
include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">📄 Lab Manuals (Practicals)</h4>
        <p class="text-muted small mb-0">Publish and manage practical templates, deadlines, and subject manuals for student download.</p>
    </div>
    <button class="btn btn-primary shadow-sm">
        <i class="fa-solid fa-upload me-1"></i> Upload Practical Template
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
                <input type="text" class="form-control bg-light border-0 shadow-none py-2" placeholder="Search by title or subject...">
            </div>
        </div>
        <div class="col-md-7 d-flex justify-content-md-end gap-2">
            <select class="form-select bg-light border-0 shadow-none w-auto text-muted font-sm py-2">
                <option value="">All Subjects</option>
                <option value="DBMS">Database Systems (DBMS)</option>
                <option value="DS">Data Structures (DS)</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Practical No.</th>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Deadline</th>
                    <th class="text-end">PDF Template</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">Exp #01</span></td>
                    <td class="fw-semibold text-dark">SQL Queries Implementation</td>
                    <td>DBMS Lab</td>
                    <td><i class="fa-regular fa-calendar text-muted me-2"></i>15 Aug 2026</td>
                    <td class="text-end">
                        <a href="#" class="btn btn-sm btn-outline-danger shadow-none py-1 px-3">
                            <i class="fa-solid fa-file-pdf me-1"></i> View PDF
                        </a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<?php
include 'footer.php';
?>
