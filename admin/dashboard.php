<?php
include 'header.php'; // This includes the session_start(), DB connection (if in header, wait no it's not)
include '../db.php';

// Data Fetching
$student_count_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student'");
$total_students = ($student_count_res) ? $student_count_res->fetch_assoc()['total'] : 0;

$faculty_count_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='faculty'");
$active_faculty = ($faculty_count_res) ? $faculty_count_res->fetch_assoc()['total'] : 0;

$pending_res = $conn->query("SELECT COUNT(*) as total FROM submissions WHERE status='Pending'");
$pending_reviews = ($pending_res) ? $pending_res->fetch_assoc()['total'] : 0;

$rejected_res = $conn->query("SELECT COUNT(*) as total FROM submissions WHERE status='Rejected'");
$rejected_submissions = ($rejected_res) ? $rejected_res->fetch_assoc()['total'] : 0;

$total_sub_res = $conn->query("SELECT COUNT(*) as total FROM submissions");
$total_submissions = ($total_sub_res) ? $total_sub_res->fetch_assoc()['total'] : 0;

$recent_submissions = $conn->query("
    SELECT u.name, u.department, s.subject as subject_name, s.status, s.upload_date as submitted_at 
    FROM submissions s 
    JOIN users u ON s.enrollment = u.user_id 
    ORDER BY s.upload_date DESC 
    LIMIT 5
");
?>

<!-- HEADER TITLE -->
<div class="page-header mt-2 mb-4">
    <div>
        <h4 class="page-title">Dashboard Overview</h4>
        <p class="page-subtitle">Track student admissions, lab manuals progress, and reviews.</p>
    </div>
</div>

<!-- STATS GRID -->
<div class="stats-grid mb-4">
    <div class="stat-card primary">
        <div>
            <div class="stat-title">Total Students</div>
            <div class="stat-value"><?php echo number_format($total_students); ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
    </div>
    <div class="stat-card success">
        <div>
            <div class="stat-title">Active Faculty</div>
            <div class="stat-value"><?php echo number_format($active_faculty); ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
    </div>
    <div class="stat-card warning">
        <div>
            <div class="stat-title">Pending Reviews</div>
            <div class="stat-value"><?php echo number_format($pending_reviews); ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
    </div>
    <div class="stat-card danger">
        <div>
            <div class="stat-title">Rejected Submissions</div>
            <div class="stat-value"><?php echo number_format($rejected_submissions); ?></div>
        </div>
        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
    </div>
</div>

<!-- LOWER SECTION -->
<div class="row">
    <!-- BREAKDOWN GRAPH CARD -->
    <div class="col-md-4 mb-4">
        <div class="content-box h-100">
            <h6 class="fw-bold text-dark mb-4">Submission Breakdown</h6>
            <div class="d-flex flex-column align-items-center justify-content-center py-3">
                <div style="width: 170px; height: 170px; border-radius: 50%; background: conic-gradient(#10b981 0% 65%, #f59e0b 65% 85%, #ef4444 85% 100%); display: flex; align-items: center; justify-content: center; position: relative; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
                    <div style="width: 125px; height: 125px; background: white; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: inset 0 2px 10px rgba(0,0,0,0.05);">
                        <span class="fw-bold text-dark" style="font-size: 22px;"><?php echo number_format($total_submissions); ?></span>
                        <span class="text-muted" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Submissions</span>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex justify-content-center gap-3 text-muted" style="font-size: 12px; font-weight: 600;">
                <div><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#10b981; margin-right:5px;"></span> Approved</div>
                <div><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#f59e0b; margin-right:5px;"></span> Pending</div>
                <div><span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#ef4444; margin-right:5px;"></span> Rejected</div>
            </div>
        </div>
    </div>

    <!-- RECENT SUBMISSIONS TABLE -->
    <div class="col-md-8 mb-4">
        <div class="content-box h-100">
            <h6 class="fw-bold text-dark mb-3">Recent Submissions</h6>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_submissions && $recent_submissions->num_rows > 0): ?>
                            <?php while($row = $recent_submissions->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($row['name']); ?></div>
                                        <div class="text-muted" style="font-size: 12px;"><?php echo htmlspecialchars(explode(' ', $row['department'])[0]); ?></div>
                                    </td>
                                    <td class="fw-medium"><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                    <td class="text-muted" style="font-size: 13px;">
                                        <i class="far fa-calendar-alt me-1"></i>
                                        <?php echo date('d M, Y', strtotime($row['submitted_at'])); ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $badge_class = 'badge-pending';
                                            if($row['status'] == 'Approved') $badge_class = 'badge-approved';
                                            if($row['status'] == 'Rejected') $badge_class = 'badge-rejected';
                                        ?>
                                        <span class="badge-status <?php echo $badge_class; ?>"><?php echo $row['status']; ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No recent submissions found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
