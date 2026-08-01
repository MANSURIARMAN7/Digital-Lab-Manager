<?php
$json_file = 'submissions.json';
$submissions = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
if (!is_array($submissions))
    $submissions = [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Term Work Reports - KDP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Version 503 for Cache Bypass -->
    <link rel="stylesheet" href="assets/css/faculty_dashboard.css?v=503">
</head>

<body>

    <div class="container">
        
        <!-- NAYA SIDEBAR START (Logo + Emojis) -->
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

            <!-- Menu Items using Emojis -->
            <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; flex-grow: 1;">
                
                <li onclick="window.location.href='faculty_dashboard.php'" style="cursor: pointer;">
                    <span class="menu-icon">🏠</span> Dashboard
                </li>
                
                <li onclick="window.location.href='labmanual_list.php'" style="cursor: pointer;">
                    <span class="menu-icon">📘</span> Lab Manuals
                </li>
                
                <!-- Is page par Reports active rahega -->
                <li onclick="window.location.href='reports.php'" class="active" style="cursor: pointer;">
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
                    <h2>Term Work & Reports</h2>
                    <p style="color: #64748b; font-size: 14px; margin-top: 5px;">Generate end-semester term work reports
                        for GTU.</p>
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

            <div class="table-section">
                <div class="table-header">
                    <h3>Final Evaluated List</h3>
                    <div class="table-actions">
                        <button class="btn-export" id="downloadCSV" style="background:#10b981; padding: 10px 20px;"><i
                                class="fas fa-download"></i> Generate Term Work (Excel)</button>
                    </div>
                </div>
                <table>
                    <tr>
                        <th>Enrollment</th>
                        <th>Student Name</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Marks</th>
                        <th>Remarks</th>
                    </tr>
                    <?php foreach ($submissions as $row) {
                        $status_class = strtolower($row['status']); ?>
                        <tr>
                            <td style="font-weight: 600;"><?php echo $row['enrollment']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><span class='subject-tag'><?php echo $row['subject']; ?></span></td>
                            <td><span class='badge <?php echo $status_class; ?>'><?php echo $row['status']; ?></span></td>
                            <td style="font-weight:bold; color:#2563eb;">
                                <?php echo isset($row['marks']) && $row['marks'] != '' ? $row['marks'] . '/10' : '-'; ?></td>
                            <td style="font-size: 13px; color:#ef4444;">
                                <?php echo isset($row['remark']) ? $row['remark'] : '-'; ?></td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>
    <div id="toastBox" class="toast-container"></div>
    <script src="assets/js/faculty_dashboard.js?v=503"></script>
</body>

</html>