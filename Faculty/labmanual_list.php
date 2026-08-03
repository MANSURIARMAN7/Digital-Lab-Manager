<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_subjects = isset($_SESSION['subjects']) ? $_SESSION['subjects'] : [];
$faculty_sub_names = [];
foreach ($faculty_subjects as $sub) {
    $faculty_sub_names[] = is_array($sub) ? $sub['name'] : $sub;
}

$json_file = 'submissions.json'; 
if (!file_exists($json_file)) { file_put_contents($json_file, json_encode([], JSON_PRETTY_PRINT)); }
$all_submissions = json_decode(file_get_contents($json_file), true);
if (!is_array($all_submissions)) { $all_submissions = []; }

$my_submissions = [];
foreach ($all_submissions as $row) {
    if (isset($row['subject']) && in_array($row['subject'], $faculty_sub_names)) {
        $my_submissions[] = $row; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Manuals - KDP Faculty</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/faculty_dashboard.css?v=600">
    <style>
        .filter-bar { display: flex; gap: 15px; margin-bottom: 25px; background: #fff; padding: 15px 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); align-items: center; }
        .filter-bar select, .filter-bar input { padding: 8px 15px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- SIDEBAR -->
        <div class="sidebar" style="display: flex; flex-direction: column; height: 100vh;">
            <div class="sidebar-logo-container">
                <img src="../assets/images/KDP-Logo.png" alt="Logo" class="sidebar-logo">
                <div class="sidebar-title">
                    <h2>K.D. Polytechnic</h2>
                    <p>Faculty Portal</p>
                </div>
            </div>
            <div class="sidebar-divider"></div>
            <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; flex-grow: 1;">
                <li onclick="window.location.href='faculty_dashboard.php'" style="cursor: pointer;">
                    <span class="menu-icon">🏠</span> Dashboard
                </li>
                <li onclick="window.location.href='labmanual_list.php'" class="active" style="cursor: pointer;">
                    <span class="menu-icon">📘</span> Lab Manuals
                </li>
                <li onclick="window.location.href='reports.php'" style="cursor: pointer;">
                    <span class="menu-icon">📄</span> Reports
                </li>
                <li onclick="window.location.href='../logout.php'" style="cursor: pointer; margin-top: auto; color: #ff8ba7;">
                    <span class="menu-icon" style="color: #ff8ba7;">➔</span> Logout
                </li>
            </ul>
        </div>

        <div class="main">
            <div class="header">
                <div>
                    <h2>All Lab Manuals</h2>
                    <p style="color: #64748b; font-size: 14px; margin-top: 5px;">Manage and review all submitted manuals.</p>
                </div>
                <div class="faculty-profile">
                    <div class="profile-info">
                        <img src="https://ui-avatars.com/api/?name=Faculty&background=2563eb&color=fff" alt="Profile" class="profile-pic">
                        <span class="faculty-name">Welcome, <?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Sir'; ?></span>
                    </div>
                </div>
            </div>

            <div class="filter-bar">
                <h3 style="color: #1e293b; font-size: 15px; margin: 0; margin-right: 10px;"><i class="fas fa-filter"></i> Filters:</h3>
                <input type="text" id="searchTable" placeholder="Search Name/Enrollment..." onkeyup="filterTable()">
                <select id="statusFilter" onchange="filterTable()">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select id="subjectFilter" onchange="filterTable()">
                    <option value="all">All My Subjects</option>
                    <?php foreach($faculty_sub_names as $sub) { ?>
                        <option value="<?php echo strtolower($sub); ?>"><?php echo $sub; ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="table-section">
                <table id="manualsTable">
                    <thead>
                        <tr>
                            <th>Student</th><th>Enrollment</th><th>Subject</th><th>Sem</th><th>Date</th><th>Status</th><th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if(count($my_submissions) == 0) {
                            echo "<tr id='noDataRow'><td colspan='7' style='text-align:center; padding: 20px; color: #64748b;'>No submissions found.</td></tr>";
                        } else {
                            foreach ($my_submissions as $row) {
                                $status_class = strtolower($row['status']); 
                                $sem = isset($row['semester']) ? $row['semester'] : '-';
                                $date = isset($row['date']) ? $row['date'] : '-';
                                ?>
                                <tr class="data-row" data-status="<?php echo $status_class; ?>" data-subject="<?php echo strtolower($row['subject']); ?>">
                                    <td class="student-name"><?php echo $row['name']; ?></td>
                                    <td class="enrollment"><?php echo $row['enrollment']; ?></td>
                                    <td><span class='subject-tag'><?php echo $row['subject']; ?></span></td>
                                    <td style="font-weight: 500; color:#475569;"><?php echo $sem; ?></td>
                                    <td style="font-size: 13px; color:#64748b;"><?php echo $date; ?></td>
                                    <td><span class='badge <?php echo $status_class; ?>'><?php echo $row['status']; ?></span></td>
                                    <td style='display:flex; gap:8px;'>
                                        <button class='btn-view' title='View Manual' onclick='openModal("<?php echo $row['name']; ?>", "<?php echo $row['subject']; ?>")'><i class='fas fa-eye'></i> View</button>
                                        <?php if($status_class == 'pending') { ?>
                                            <button class='btn-action-sm check' title='Approve' onclick='openGradeModal("<?php echo $row['enrollment']; ?>", "<?php echo $row['name']; ?>")'><i class='fas fa-check'></i></button>
                                            <button class='btn-action-sm times' title='Reject' onclick='openRejectModal("<?php echo $row['enrollment']; ?>", "<?php echo $row['name']; ?>")'><i class='fas fa-times'></i></button>
                                        <?php } else { ?>
                                            <span style="font-size: 12px; color: #94a3b8; font-weight: bold; margin-top: 5px;">Reviewed</span>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } 
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODALS JO DASHBOARD MEIN THE WO YAHAN BHI ADD KIYE -->
    <div id="studentModal" class="modal-overlay">
        <div class="modal-content">
            <h2 id="modalName" style="color:#0f172a;">Student Name</h2>
            <p id="modalSubject" style="color:#64748b; font-size:14px; margin-bottom:15px;">Subject Details</p>
            <button onclick="closeModal()" style="width:100%; background:#2563eb; color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; font-weight:bold;">Close</button>
        </div>
    </div>

    <div id="gradeModal" class="modal-overlay">
        <div class="modal-content">
            <h2 style="color:#0f172a;">Approve Manual</h2>
            <p style="color:#64748b; font-size:14px; margin-bottom:15px;">Give marks to <strong id="gradeStudentName"></strong></p>
            <input type="hidden" id="gradeStudentId">
            <input type="number" id="marksInput" class="modal-input" placeholder="Enter Marks (out of 10)" min="1" max="10">
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button onclick="submitGrade()" style="flex:1; background:#10b981; color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; font-weight:bold;">Approve</button>
                <button onclick="closeGradeModal()" style="flex:1; background:#64748b; color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; font-weight:bold;">Cancel</button>
            </div>
        </div>
    </div>

    <div id="rejectModal" class="modal-overlay">
        <div class="modal-content">
            <h2 style="color:#ef4444;">Reject Manual</h2>
            <p style="color:#64748b; font-size:14px; margin-bottom:15px;">State reason for <strong id="rejectStudentName"></strong></p>
            <input type="hidden" id="rejectStudentId">
            <textarea id="remarkInput" class="modal-input" rows="3" placeholder="E.g., Missing screenshots, Plagiarism detected..."></textarea>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button onclick="submitReject()" style="flex:1; background:#ef4444; color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; font-weight:bold;">Reject</button>
                <button onclick="closeRejectModal()" style="flex:1; background:#64748b; color:white; border:none; padding:10px; border-radius:8px; cursor:pointer; font-weight:bold;">Cancel</button>
            </div>
        </div>
    </div>

    <div id="toastBox" class="toast-container"></div>
    
    <!-- YAHAN WAHI SAME DASHBOARD WALI SCRIPT LINK KI HAI TAAKI MODALS CHALEIN -->
    <script src="../assets/js/faculty_dashboard.js?v=600"></script>
    
    <script>
        // Custom Filter Logic
        function filterTable() {
            let searchInput = document.getElementById('searchTable').value.toLowerCase();
            let statusFilter = document.getElementById('statusFilter').value;
            let subjectFilter = document.getElementById('subjectFilter').value;
            
            let rows = document.querySelectorAll('.data-row');
            rows.forEach(row => {
                let name = row.querySelector('.student-name').innerText.toLowerCase();
                let enroll = row.querySelector('.enrollment').innerText.toLowerCase();
                let rowStatus = row.getAttribute('data-status');
                let rowSubject = row.getAttribute('data-subject');
                
                let matchesSearch = name.includes(searchInput) || enroll.includes(searchInput);
                let matchesStatus = (statusFilter === 'all' || rowStatus === statusFilter);
                let matchesSubject = (subjectFilter === 'all' || rowSubject === subjectFilter);
                
                row.style.display = (matchesSearch && matchesStatus && matchesSubject) ? '' : 'none';
            });
        }
    </script>
</body>
</html>