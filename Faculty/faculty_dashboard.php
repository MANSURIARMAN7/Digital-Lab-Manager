<?php
session_start();

// Agar user login nahi hai, YA uska role 'faculty' nahi hai
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

// Faculty ke subjects nikalo
$faculty_subjects = isset($_SESSION['subjects']) ? $_SESSION['subjects'] : [];

// Check karo ki konsa subject selected hai (Default pehla subject dikhao)
$selected_subject = isset($_GET['subject']) ? $_GET['subject'] : (count($faculty_subjects) > 0 ? $faculty_subjects[0] : '');

$json_file = 'submissions.json'; 
if (!file_exists($json_file)) {
    $default_data = [];
    file_put_contents($json_file, json_encode($default_data, JSON_PRETTY_PRINT));
}

$all_submissions = json_decode(file_get_contents($json_file), true);
if (!is_array($all_submissions)) {
    $all_submissions = [];
}

// ADVANCED FILTER: Sirf selected subject ke students dikhao
$recent_submissions = [];
foreach ($all_submissions as $row) {
    if ($row['subject'] === $selected_subject) {
        $recent_submissions[] = $row; 
    }
}

// Ab saari counting sirf selected subject ki hogi (Mix nahi hoga!)
$total_students = count($recent_submissions);
$pending = 0;
$approved = 0;
$rejected = 0;

foreach ($recent_submissions as $row) {
    if (isset($row['status'])) {
        if ($row['status'] == 'Pending') $pending++;
        if ($row['status'] == 'Approved') $approved++;
        if ($row['status'] == 'Rejected') $rejected++;
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
    <link rel="stylesheet" href="../assets/css/faculty_dashboard.css?v=504">
</head>

<body>

    <div class="container">
        
        <!-- SIDEBAR -->
        <div class="sidebar" style="display: flex; flex-direction: column; height: 100vh;">
            <div class="sidebar-logo-container">
                <img src="../assets/images/KDP-Logo.png" alt="K.D. Polytechnic Logo" class="sidebar-logo">
                <div class="sidebar-title">
                    <h2>K.D. Polytechnic</h2>
                    <p>Faculty Portal</p>
                </div>
            </div>
            
            <div class="sidebar-divider"></div>

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
                <li onclick="window.location.href='../logout.php'" style="cursor: pointer; margin-top: auto; color: #ff8ba7; font-weight: 400;">
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
                        <img src="https://ui-avatars.com/api/?name=Faculty&background=2563eb&color=fff" alt="Profile" class="profile-pic">
                        <span class="faculty-name">Welcome, <?php echo $_SESSION['name']; ?></span>
                    </div>
                </div>
            </div>

            <!-- NAYA DROPDOWN SECTION (Subject Select Karne Ke Liye) -->
            <div style="background: white; padding: 15px 25px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                <h3 style="color: #1e293b; font-size: 16px; margin: 0;">📚 Select Subject:</h3>
                <?php if(count($faculty_subjects) > 0) { ?>
                    <select onchange="window.location.href='?subject='+this.value" style="padding: 10px 15px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 15px; font-weight: 600; color: #113460; background: #f8fafc; cursor: pointer; outline: none; min-width: 250px;">
                        <?php foreach($faculty_subjects as $sub) { ?>
                            <option value="<?php echo $sub; ?>" <?php if($selected_subject == $sub) echo 'selected'; ?>>
                                <?php echo $sub; ?>
                            </option>
                        <?php } ?>
                    </select>
                <?php } else { ?>
                    <span style="color: #ef4444; font-weight: 600;">No subjects assigned yet.</span>
                <?php } ?>
            </div>

            <!-- CARDS -->
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
                    <h3>Recent Submissions for <span style="color: #2563eb;"><?php echo $selected_subject; ?></span></h3>
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
                    <?php 
                    if(count($recent_submissions) == 0) {
                        echo "<tr><td colspan='6' style='text-align:center; padding: 20px; color: #64748b;'>No submissions found for $selected_subject.</td></tr>";
                    } else {
                        foreach ($recent_submissions as $row) {
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
                                        onclick='openModal("<?php echo $row['name']; ?>", "<?php echo $row['subject']; ?>")'><i class='fas fa-eye'></i> View</button>
                                    <button class='btn-action-sm check' title='Approve & Grade'
                                        onclick='openGradeModal("<?php echo $row['enrollment']; ?>", "<?php echo $row['name']; ?>")'><i class='fas fa-check'></i></button>
                                    <button class='btn-action-sm times' title='Reject & Feedback'
                                        onclick='openRejectModal("<?php echo $row['enrollment']; ?>", "<?php echo $row['name']; ?>")'><i class='fas fa-times'></i></button>
                                </td>
                            </tr>
                        <?php } 
                    } ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Modals same as before -->
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
    <script src="../assets/js/faculty_dashboard.js?v=504"></script>
</body>

</html>