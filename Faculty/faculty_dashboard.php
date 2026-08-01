<?php
$json_file = 'submissions.json';
if (!file_exists($json_file)) {
    $default_data = [
        ["id" => "1", "name" => "Arman", "enrollment" => "246310307055", "subject" => "Software Engineering", "date" => "18-07-2026", "status" => "Pending"],
        ["id" => "2", "name" => "Hamza", "enrollment" => "246310307003", "subject" => "Machine Learning", "date" => "17-07-2026", "status" => "Approved", "marks" => "9"]
    ];
    file_put_contents($json_file, json_encode($default_data, JSON_PRETTY_PRINT));
}

$recent_submissions = json_decode(file_get_contents($json_file), true);
if (!is_array($recent_submissions))
    $recent_submissions = [];

$total_students = count($recent_submissions);
$pending = 0;
$approved = 0;
$rejected = 0;

foreach ($recent_submissions as $row) {
    if (isset($row['status'])) {
        if ($row['status'] == 'Pending')
            $pending++;
        if ($row['status'] == 'Approved')
            $approved++;
        if ($row['status'] == 'Rejected')
            $rejected++;
    }
}
$approval_rate = ($total_students > 0) ? round(($approved / $total_students) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - KDP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Cache hack: Version updated to 503 so browser loads fresh CSS -->
    <link rel="stylesheet" href="assets/css/faculty_dashboard.css?v=503">
</head>

<body>

    <div class="container">
        
        <!-- YAHAN SE NAYA SIDEBAR START HOTA HAI -->
        <div class="sidebar" style="display: flex; flex-direction: column; height: 100vh;">
            
            <!-- Logo Section matching Student Portal -->
            <div class="sidebar-logo-container">
                <img src="assets/images/KDP-Logo.png" alt="K.D. Polytechnic Logo" class="sidebar-logo">
                <div class="sidebar-title">
                    <h2>K.D. Polytechnic</h2>
                    <p>Faculty Portal</p>
                </div>
            </div>
            
            <!-- Divider Line -->
            <div class="sidebar-divider"></div>

            <!-- Menu Items using Emojis to match Student Portal -->
            <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; flex-grow: 1;">
                
                <li onclick="window.location.href='faculty_dashboard.php'" class="active" style="cursor: pointer;">
                    <span class="menu-icon">🏠</span> Dashboard
                </li>
                
                <li onclick="window.location.href='labmanual_list.php'" style="cursor: pointer;">
                    <span class="menu-icon">📘</span> Lab Manuals
                </li>
                
                <li onclick="window.location.href='reports.php'" style="cursor: pointer;">
                    <span class="menu-icon">📄</span> Reports
                </li>
                
                <li onclick="alert('Logging out...')" style="cursor: pointer; margin-top: auto; color: #ff8ba7; font-weight: 400;">
                    <span class="menu-icon" style="color: #ff8ba7; font-size: 16px;">➔</span> Logout
                </li>
            </ul>
        </div>
        <!-- SIDEBAR END -->

        <div class="main">
            <div class="header">
                <div>
                    <h2>Faculty Dashboard</h2>
                    <p style="color: #64748b; font-size: 14px; margin-top: 5px;">Overview of lab manual submissions.</p>
                </div>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search enrollment or name...">
                </div>
                <div class="faculty-profile">
                    <i class="fas fa-moon dark-mode-toggle" id="themeToggle" title="Switch Theme"></i>
                    <div class="profile-info">
                        <img src="https://ui-avatars.com/api/?name=Faculty&background=2563eb&color=fff" alt="Profile"
                            class="profile-pic">
                        <span class="faculty-name">Welcome, Faculty</span>
                    </div>
                </div>
            </div>

            <div class="cards">
                <div class="card">
                    <div class="card-content">
                        <h3>Total Students</h3>
                        <p><?php echo $total_students; ?></p>
                    </div>
                    <i class="fas fa-users bg-icon"></i>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3>Pending</h3>
                        <p><?php echo $pending; ?></p>
                    </div>
                    <i class="fas fa-clock bg-icon"></i>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3>Approved</h3>
                        <p><?php echo $approved; ?></p>
                    </div>
                    <i class="fas fa-check-circle bg-icon"></i>
                </div>
                <div class="card">
                    <div class="card-content">
                        <h3>Rejected</h3>
                        <p><?php echo $rejected; ?></p>
                    </div>
                    <i class="fas fa-times-circle bg-icon"></i>
                </div>
            </div>

            <div class="table-section">
                <div class="table-header">
                    <h3>Recent Submissions</h3>
                </div>
                <table>
                    <tr>
                        <th>Student</th>
                        <th>Enrollment</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Marks</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($recent_submissions as $row) {
                        $status_class = strtolower($row['status']); ?>
                        <tr>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['enrollment']; ?></td>
                            <td><span class='subject-tag'><?php echo $row['subject']; ?></span></td>
                            <td><span class='badge <?php echo $status_class; ?>'><?php echo $row['status']; ?></span></td>
                            <td style="font-weight:bold; color:#2563eb;">
                                <?php echo isset($row['marks']) && $row['marks'] != '' ? $row['marks'] . '/10' : '-'; ?></td>

                            <td style='display:flex; gap:8px;'>
                                <button class='btn-view' title='View Manual'
                                    onclick='openModal("<?php echo $row['name']; ?>", "<?php echo $row['subject']; ?>")'><i
                                        class='fas fa-eye'></i> View</button>
                                <button class='btn-action-sm check' title='Approve & Grade'
                                    onclick='openGradeModal("<?php echo $row['enrollment']; ?>", "<?php echo $row['name']; ?>")'><i
                                        class='fas fa-check'></i></button>
                                <button class='btn-action-sm times' title='Reject & Feedback'
                                    onclick='openRejectModal("<?php echo $row['enrollment']; ?>", "<?php echo $row['name']; ?>")'><i
                                        class='fas fa-times'></i></button>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>

    <!-- View Student Modal (Duplicate wala remove kar diya) -->
    <div id="studentModal" class="modal-overlay">
        <div class="modal-content">
            <h2 id="modalName" style="color:#0f172a;">Student Name</h2>
            <p id="modalSubject" style="color:#64748b; font-size:14px; margin-bottom:15px;">Subject Details</p>
            <button onclick="closeModal()"
                style="width:100%; background:#2563eb; color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; font-weight:bold;">Close</button>
        </div>
    </div>

    <!-- Grading Modal -->
    <div id="gradeModal" class="modal-overlay">
        <div class="modal-content">
            <h2 style="color:#0f172a;">Approve Manual</h2>
            <p style="color:#64748b; font-size:14px; margin-bottom:15px;">Give marks to <strong
                    id="gradeStudentName"></strong></p>
            <input type="hidden" id="gradeStudentId">
            <input type="number" id="marksInput" class="modal-input" placeholder="Enter Marks (out of 10)" min="1"
                max="10">
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button onclick="submitGrade()"
                    style="flex:1; background:#10b981; color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; font-weight:bold;">Approve</button>
                <button onclick="closeGradeModal()"
                    style="flex:1; background:#64748b; color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; font-weight:bold;">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal-overlay">
        <div class="modal-content">
            <h2 style="color:#ef4444;">Reject Manual</h2>
            <p style="color:#64748b; font-size:14px; margin-bottom:15px;">State reason for <strong
                    id="rejectStudentName"></strong></p>
            <input type="hidden" id="rejectStudentId">
            <textarea id="remarkInput" class="modal-input" rows="3"
                placeholder="E.g., Missing screenshots, Plagiarism detected..."></textarea>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button onclick="submitReject()"
                    style="flex:1; background:#ef4444; color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; font-weight:bold;">Reject</button>
                <button onclick="closeRejectModal()"
                    style="flex:1; background:#64748b; color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; font-weight:bold;">Cancel</button>
            </div>
        </div>
    </div>

    <div id="toastBox" class="toast-container"></div>
    <script src="assets/js/faculty_dashboard.js?v=503"></script>
</body>

</html>