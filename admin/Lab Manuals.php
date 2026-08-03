<?php
include 'header.php';
?>

<h4 class="fw-bold text-dark mb-4">
    📄 Lab Manuals (Practicals)
</h4>

<div class="content-card">

    <button class="btn btn-primary mb-3">
        <i class="fa-solid fa-upload me-1"></i>
        Upload Practical Template
    </button>

    <div class="table-responsive">
        <table class="table">
            <thead class="table-light">
                <tr>
                    <th>Practical No.</th>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Deadline</th>
                    <th>PDF Template</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Exp #01</td>
                    <td>SQL Queries Implementation</td>
                    <td>DBMS Lab</td>
                    <td>15 Aug 2026</td>
                    <td>
                        <a href="#" class="btn btn-sm btn-outline-danger">
                            <i class="fa-solid fa-file-pdf"></i> View PDF
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
