<?php
include '../db.php';
include 'header.php';

// Data Fetching
$student_count_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student'");
$total_students = ($student_count_res) ? $student_count_res->fetch_assoc()['total'] : 0;

$faculty_count_res = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='faculty'");
$active_faculty = ($faculty_count_res) ? $faculty_count_res->fetch_assoc()['total'] : 0;

$pending_res = $conn->query("SELECT COUNT(*) as total FROM submissions WHERE status='Pending'");
$pending_reviews = ($pending_res) ? $pending_res->fetch_assoc()['total'] : 0;

$rejected_res = $conn->query("SELECT COUNT(*) as total FROM submissions WHERE status='Rejected'");
$rejected_submissions = ($rejected_res) ? $rejected_res->fetch_assoc()['total'] : 0;

$approved_res = $conn->query("SELECT COUNT(*) as total FROM submissions WHERE status='Approved'");
$approved_count = ($approved_res) ? $approved_res->fetch_assoc()['total'] : 0;

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

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard Overview</h1>
        <p class="page-subtitle">Welcome back, Admin — here's what's happening today.</p>
    </div>
    <div class="d-flex gap-2">
        <span style="font-size: 12px; color: var(--text-muted); background: #fff; padding: 8px 14px; border-radius: 10px; border: 1px solid var(--card-border); font-weight: 500;">
            <i class="far fa-calendar-alt me-2" style="color: var(--brand-accent);"></i>
            <?php echo date('D, d M Y'); ?>
        </span>
    </div>
</div>

<!-- STATS GRID -->
<div class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-icon-wrap">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="stat-info">
            <div class="stat-title">Total Students</div>
            <div class="stat-value"><?php echo number_format($total_students); ?></div>
            <div class="stat-change"><i class="fas fa-circle" style="font-size:6px; color: var(--green); margin-right:4px;"></i>Enrolled</div>
        </div>
    </div>

    <div class="stat-card success">
        <div class="stat-icon-wrap">
            <i class="fas fa-chalkboard-user"></i>
        </div>
        <div class="stat-info">
            <div class="stat-title">Active Faculty</div>
            <div class="stat-value"><?php echo number_format($active_faculty); ?></div>
            <div class="stat-change"><i class="fas fa-circle" style="font-size:6px; color: var(--green); margin-right:4px;"></i>Teaching Staff</div>
        </div>
    </div>

    <div class="stat-card warning">
        <div class="stat-icon-wrap">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-info">
            <div class="stat-title">Pending Reviews</div>
            <div class="stat-value"><?php echo number_format($pending_reviews); ?></div>
            <div class="stat-change"><i class="fas fa-circle" style="font-size:6px; color: var(--yellow); margin-right:4px;"></i>Awaiting Review</div>
        </div>
    </div>

    <div class="stat-card danger">
        <div class="stat-icon-wrap">
            <i class="fas fa-circle-xmark"></i>
        </div>
        <div class="stat-info">
            <div class="stat-title">Rejected</div>
            <div class="stat-value"><?php echo number_format($rejected_submissions); ?></div>
            <div class="stat-change"><i class="fas fa-circle" style="font-size:6px; color: var(--red); margin-right:4px;"></i>Needs Re-submission</div>
        </div>
    </div>
</div>

<!-- LOWER ROW -->
<div class="row g-4">
    <!-- CHART -->
    <div class="col-md-4">
        <div class="content-box h-100" style="display: flex; flex-direction: column;">
            <div class="section-title"><i class="fas fa-chart-donut"></i> Submission Status</div>

            <div class="flex-center" style="flex: 1; flex-direction: column; padding: 12px 0;">
                <div style="position: relative; width: 160px; height: 160px;">
                    <canvas id="submissionChart" width="160" height="160"></canvas>
                    <div style="position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none;">
                        <span style="font-size: 28px; font-weight: 800; color: var(--text-primary); letter-spacing: -1px;"><?php echo $total_submissions; ?></span>
                        <span style="font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Total</span>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 20px; width: 100%;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: var(--green-light); border-radius: 8px;">
                        <span style="font-size: 12.5px; font-weight: 600; color: #065f46;"><i class="fas fa-circle" style="font-size: 7px; margin-right: 6px;"></i>Approved</span>
                        <strong style="font-size: 13px; color: #065f46;"><?php echo $approved_count; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: var(--yellow-light); border-radius: 8px;">
                        <span style="font-size: 12.5px; font-weight: 600; color: #92400e;"><i class="fas fa-circle" style="font-size: 7px; margin-right: 6px;"></i>Pending</span>
                        <strong style="font-size: 13px; color: #92400e;"><?php echo $pending_reviews; ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: var(--red-light); border-radius: 8px;">
                        <span style="font-size: 12.5px; font-weight: 600; color: #991b1b;"><i class="fas fa-circle" style="font-size: 7px; margin-right: 6px;"></i>Rejected</span>
                        <strong style="font-size: 13px; color: #991b1b;"><?php echo $rejected_submissions; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RECENT SUBMISSIONS TABLE -->
    <div class="col-md-8">
        <div class="content-box h-100">
            <div class="d-flex justify-content-between align-items-center" style="margin-bottom: 18px;">
                <div class="section-title mb-0"><i class="fas fa-clock-rotate-left"></i> Recent Submissions</div>
                <a href="Submissions.php" class="btn btn-outline btn-sm" style="font-size: 12px;">
                    View All <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
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
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['name']); ?>&background=3b82f6&color=fff&bold=true&size=48" 
                                                 style="width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;" alt="">
                                            <div>
                                                <div style="font-weight: 600; font-size: 13.5px;"><?php echo htmlspecialchars($row['name']); ?></div>
                                                <div style="font-size: 11.5px; color: var(--text-muted);"><?php echo htmlspecialchars($row['department']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="font-weight: 500; color: var(--text-secondary);"><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                    <td style="color: var(--text-muted); font-size: 12.5px;">
                                        <i class="far fa-calendar" style="margin-right: 5px;"></i>
                                        <?php echo date('d M, Y', strtotime($row['submitted_at'])); ?>
                                    </td>
                                    <td>
                                        <?php
                                            $badge = 'badge-pending';
                                            if($row['status'] == 'Approved') $badge = 'badge-approved';
                                            if($row['status'] == 'Rejected') $badge = 'badge-rejected';
                                        ?>
                                        <span class="badge-status <?php echo $badge; ?>"><?php echo $row['status']; ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <i class="fas fa-inbox" style="font-size: 28px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                                    No recent submissions found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('submissionChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Approved', 'Pending', 'Rejected'],
        datasets: [{
            data: [<?php echo $approved_count; ?>, <?php echo $pending_reviews; ?>, <?php echo $rejected_submissions; ?>],
            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
            borderWidth: 0,
            hoverOffset: 6,
        }]
    },
    options: {
        cutout: '72%',
        plugins: { legend: { display: false }, tooltip: { enabled: true } },
        animation: { animateRotate: true, duration: 800 }
    }
});
</script>

<?php include 'footer.php'; ?>
