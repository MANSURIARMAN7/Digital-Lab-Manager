<?php
$json_file = 'submissions.json';
$recent_submissions = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
if (!is_array($recent_submissions))
    $recent_submissions = [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Manuals - KDP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Version 503 kar diya taaki naya design cache bypass karke load ho -->
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
                
                <!-- Is page par Lab Manuals active rahega -->
                <li onclick="window.location.href='labmanual_list.php'" class="active" style="cursor: pointer;">
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
                    <h2>Lab Manuals Directory</h2>
                    <p style="color: #64748b; font-size: 14px; margin-top: 5px;">View, preview, and evaluate all student
                        submissions.</p>
                </div>
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search manuals...">
                </div>
                <div class="faculty-profile">
                    <i class="fas fa-moon dark-mode-toggle" id="themeToggle"></i>
                    <div class="profile-info">
                        <img src="https://ui-avatars.com/api/?name=Faculty&background=2563eb&color=fff" alt="Profile"
                            class="profile-pic">
                        <span class="faculty-name">Welcome, Faculty</span>
                    </div>
                </div>
            </div>

            <div class="filter-section" style="display:flex; gap:15px; margin-bottom:25px;">
                <select id="subjectFilter" class="filter-select">
                    <option value="All">All Subjects</option>
                    <option value="Software Engineering">Software Engineering</option>
                    <option value="Data Structures">Data Structures</option>
                    <option value="Operating System">Operating System</option>
                </select>
                <button id="applyFilterBtn" class="btn-export"><i class="fas fa-filter"></i> Apply Filters</button>
                <button id="bulkApproveBtn" class="btn-export" style="background:#10b981; margin-left:auto;"><i
                        class="fas fa-check-double"></i> Bulk Approve (Give 10/10)</button>
            </div>

            <div class="table-section">
                <table id="manualsTable">
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Student Info</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($recent_submissions as $manual) {
                        $status_class = strtolower($manual['status']); ?>
                        <tr class='manual-row' data-subject='<?php echo $manual['subject']; ?>'>
                            <td><input type='checkbox' class='manual-checkbox'
                                    data-id='<?php echo $manual['enrollment']; ?>'></td>
                            <td>
                                <div style='font-weight:600; color:#2563eb;'><?php echo $manual['name']; ?></div>
                                <div style='font-size:12px; color:#64748b;'><?php echo $manual['enrollment']; ?></div>
                            </td>
                            <td><span class='subject-tag'><?php echo $manual['subject']; ?></span></td>
                            <td><span class='badge <?php echo $status_class; ?>'><?php echo $manual['status']; ?></span>
                            </td>
                            <td style='display:flex; gap:8px;'>
                                <button class='btn-action-sm check' title='Approve'
                                    onclick='openGradeModal("<?php echo $manual['enrollment']; ?>", "<?php echo $manual['name']; ?>")'><i
                                        class='fas fa-check'></i> Grade</button>
                                <button class='btn-action-sm times' title='Reject'
                                    onclick='openRejectModal("<?php echo $manual['enrollment']; ?>", "<?php echo $manual['name']; ?>")'><i
                                        class='fas fa-times'></i> Reject</button>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
    </div>

    <!-- View Student Modal (Fix: Only one instance now) -->
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