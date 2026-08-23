<?php
include '../db.php';
include 'header.php';

// Fetch submissions with student info
$submissions = [];
$filter_status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$filter_subject = isset($_GET['subject']) ? $conn->real_escape_string($_GET['subject']) : '';

$query = "SELECT s.*, u.name as student_name FROM submissions s LEFT JOIN users u ON s.enrollment = u.user_id WHERE 1=1";
if ($filter_status) $query .= " AND s.status = '$filter_status'";
if ($filter_subject) $query .= " AND s.subject = '$filter_subject'";
$query .= " ORDER BY s.upload_date DESC";

$res = $conn->query($query);
if ($res) { while ($row = $res->fetch_assoc()) { $submissions[] = $row; } }

// Count by status
$counts = ['Total' => 0, 'Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
foreach($submissions as $s) {
    $counts['Total']++;
    $counts[$s['status']] = ($counts[$s['status']] ?? 0) + 1;
}
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Student Submissions</h1>
        <p class="page-subtitle">Monitor, review, and evaluate practical manual submissions.</p>
    </div>
    <a href="Review & Marks.php" class="btn btn-primary">
        <i class="fas fa-pen-to-square"></i> Open Evaluator
    </a>
</div>

<!-- MINI STATS -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px;">
    <?php
    $statConfig = [
        ['label' => 'Total', 'count' => $counts['Total'], 'color' => '#3b82f6', 'bg' => '#eff6ff', 'icon' => 'fa-inbox'],
        ['label' => 'Pending', 'count' => $counts['Pending'], 'color' => '#f59e0b', 'bg' => '#fffbeb', 'icon' => 'fa-clock'],
        ['label' => 'Approved', 'count' => $counts['Approved'], 'color' => '#10b981', 'bg' => '#ecfdf5', 'icon' => 'fa-circle-check'],
        ['label' => 'Rejected', 'count' => $counts['Rejected'], 'color' => '#ef4444', 'bg' => '#fef2f2', 'icon' => 'fa-circle-xmark'],
    ];
    foreach($statConfig as $sc): ?>
    <div style="background: #fff; border-radius: 12px; border: 1px solid var(--card-border); box-shadow: var(--card-shadow); padding: 16px 18px; display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; border-radius: 10px; background: <?php echo $sc['bg']; ?>; color: <?php echo $sc['color']; ?>; display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0;">
            <i class="fas <?php echo $sc['icon']; ?>"></i>
        </div>
        <div>
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;"><?php echo $sc['label']; ?></div>
            <div style="font-size: 22px; font-weight: 800; color: var(--text-primary); line-height: 1.1;"><?php echo $sc['count']; ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- TABLE -->
<div class="content-box" style="padding: 0; overflow: hidden;">
    <!-- Filters -->
    <div style="padding: 14px 22px; border-bottom: 1px solid var(--card-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <div class="section-title mb-0"><i class="fas fa-list-check"></i> All Submissions</div>
        <form method="GET" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <div class="search-box" style="width: 220px; height: 36px;">
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" placeholder="Search student..." style="font-size: 12.5px;">
            </div>
            <select name="status" class="form-select" style="width: auto; font-size: 12.5px; padding: 7px 30px 7px 12px;" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="Pending" <?php if($filter_status=='Pending') echo 'selected';?>>Pending</option>
                <option value="Approved" <?php if($filter_status=='Approved') echo 'selected';?>>Approved</option>
                <option value="Rejected" <?php if($filter_status=='Rejected') echo 'selected';?>>Rejected</option>
            </select>
        </form>
    </div>

    <table class="table-custom" style="margin: 0;">
        <thead>
            <tr>
                <th style="padding-left: 22px;">Student</th>
                <th>Subject</th>
                <th>Upload Date</th>
                <th>Status</th>
                <th class="text-end" style="padding-right: 22px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($submissions)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 50px; color: var(--text-muted);">
                        <i class="fas fa-folder-open" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 10px;"></i>
                        No submissions found.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach($submissions as $sub): ?>
                <tr>
                    <td style="padding-left: 22px;">
                        <div style="display: flex; align-items: center; gap: 11px;">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($sub['student_name'] ?? $sub['enrollment']); ?>&background=10b981&color=fff&bold=true&size=48"
                                 style="width: 36px; height: 36px; border-radius: 9px;" alt="">
                            <div>
                                <div style="font-weight: 600; font-size: 13.5px;"><?php echo htmlspecialchars($sub['student_name'] ?? 'Unknown'); ?></div>
                                <div style="font-size: 11.5px; color: var(--text-muted);"><?php echo htmlspecialchars($sub['enrollment']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size: 13.5px; font-weight: 500; color: var(--text-secondary);"><?php echo htmlspecialchars($sub['subject']); ?></td>
                    <td style="font-size: 12.5px; color: var(--text-muted);">
                        <i class="far fa-calendar" style="margin-right: 5px;"></i>
                        <?php echo date('d M Y, h:i A', strtotime($sub['upload_date'])); ?>
                    </td>
                    <td>
                        <?php
                            $badge = 'badge-pending';
                            if($sub['status'] == 'Approved') $badge = 'badge-approved';
                            if($sub['status'] == 'Rejected') $badge = 'badge-rejected';
                        ?>
                        <span class="badge-status <?php echo $badge; ?>"><?php echo $sub['status']; ?></span>
                    </td>
                    <td class="text-end" style="padding-right: 22px;">
                        <a href="Review & Marks.php?id=<?php echo $sub['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-pen-to-square"></i> Evaluate
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'footer.php'; ?>
