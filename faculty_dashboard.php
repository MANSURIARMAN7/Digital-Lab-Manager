<?php
// ==========================================
// MOCK DATA 
// ==========================================
$total_students = 120;
$pending = 18;
$approved = 95;
$rejected = 7;
$approval_rate = 79; // Percentage

$recent_submissions = [
    ["name" => "Arman", "enrollment" => "246310307055", "subject" => "Software Engineering", "date" => "18-07-2026", "status" => "Pending"],
    ["name" => "Hamza", "enrollment" => "246310307003", "subject" => "Machine Learning", "date" => "17-07-2026", "status" => "Approved"],
    ["name" => "Sohan", "enrollment" => "246310307038", "subject" => "Operating System", "date" => "16-07-2026", "status" => "Rejected"],
    ["name" => "Rehan", "enrollment" => "246310307151", "subject" => "Data Structures", "date" => "15-07-2026", "status" => "Approved"]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - KDP</title>
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js for Graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/faculty_dashboard.css?v=200">
</head>
<body>

<div class="container">
    <!-- 1. Floating Sidebar -->
    <div class="sidebar">
        <h2>KDP</h2>
        <ul>
            <li class="active"><i class="fas fa-home"></i> Dashboard</li>
            <li><a href="labmanual_list.php" style="color: white; text-decoration: none;"><i class="fas fa-book"></i> Lab Manuals</a></li>
            <li><i class="fas fa-file-alt"></i> Reports</li>
            <li><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- 2. Main Content Area -->
    <div class="main">
        
        <!-- Header -->
        <div class="header">
            <div>
                <h2>Faculty Dashboard</h2>
                <p style="color: #64748b; font-size: 14px; margin-top: 5px;">Overview of lab manual submissions for this semester.</p>
            </div>
            
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search enrollment or name...">
            </div>
            
            <div class="faculty-profile">
                <i class="fas fa-moon dark-mode-toggle" id="themeToggle" title="Switch to Dark Mode"></i>
                <i class="fas fa-bell notification-icon"></i>
                <div class="profile-info">
                    <img src="https://ui-avatars.com/api/?name=Faculty&background=2563eb&color=fff" alt="Profile" class="profile-pic">
                    <span class="faculty-name">Welcome, Faculty</span>
                    <i class="fas fa-chevron-down dropdown-icon" style="color: #64748b; font-size: 12px;"></i>
                </div>
            </div>
        </div>

        <!-- 3. Dynamic Statistics Cards -->
        <div class="cards">
            <div class="card">
                <div class="card-content">
                    <h3>Total Students</h3>
                    <p><?php echo $total_students; ?></p>
                    <span class="trend up"><i class="fas fa-arrow-up"></i> 100% Registered</span>
                </div>
                <i class="fas fa-users bg-icon"></i>
            </div>
            <div class="card">
                <div class="card-content">
                    <h3>Pending Manuals</h3>
                    <p><?php echo $pending; ?></p>
                    <span class="trend down"><i class="fas fa-arrow-down"></i> Needs Review</span>
                </div>
                <i class="fas fa-clock bg-icon"></i>
            </div>
            <div class="card">
                <div class="card-content">
                    <h3>Approved</h3>
                    <p><?php echo $approved; ?></p>
                    <span class="trend up"><i class="fas fa-arrow-up"></i> <?php echo $approval_rate; ?>% Approval Rate</span>
                </div>
                <i class="fas fa-check-circle bg-icon"></i>
            </div>
            <div class="card">
                <div class="card-content">
                    <h3>Rejected</h3>
                    <p><?php echo $rejected; ?></p>
                    <span class="trend down"><i class="fas fa-exclamation-circle"></i> Needs Correction</span>
                </div>
                <i class="fas fa-times-circle bg-icon"></i>
            </div>
        </div>

        <!-- 4. Analytics & Timeline Section -->
        <div class="dashboard-widgets">
            <div class="widget-box chart-box">
                <h3>Submission Statistics</h3>
                <div class="canvas-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            
            <div class="widget-box timeline-box">
                <h3>Recent Activities</h3>
                <ul class="timeline">
                    <li>
                        <div class="time-icon success"><i class="fas fa-check"></i></div>
                        <div class="time-content">
                            <p><strong>Rehan</strong>'s DS Manual Approved</p>
                            <span>10 mins ago</span>
                        </div>
                    </li>
                    <li>
                        <div class="time-icon warning"><i class="fas fa-file-upload"></i></div>
                        <div class="time-content">
                            <p><strong>Arman</strong> submitted SE Manual</p>
                            <span>2 hours ago</span>
                        </div>
                    </li>
                    <li>
                        <div class="time-icon danger"><i class="fas fa-times"></i></div>
                        <div class="time-content">
                            <p><strong>Sohan</strong>'s OS Manual Rejected</p>
                            <span>Yesterday</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- 5. Dynamic PHP Table Fetch -->
        <div class="table-section">
            <div class="table-header">
                <h3>Recent Submissions</h3>
                <div class="table-actions">
                    <button class="btn-export"><i class="fas fa-file-excel"></i> Export Data</button>
                    <button class="btn-view" style="background: #2563eb; color: white;">View All <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
            <table>
                <tr>
                    <th>Student</th>
                    <th>Enrollment</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                
                <?php
                foreach ($recent_submissions as $row) {
                    $status_class = strtolower($row['status']); 
                    echo "<tr>";
                    echo "<td>{$row['name']}</td>";
                    echo "<td>{$row['enrollment']}</td>";
                    echo "<td><span class='subject-tag'>{$row['subject']}</span></td>";
                    echo "<td>{$row['date']}</td>";
                    echo "<td><span class='badge {$status_class}'>{$row['status']}</span></td>";
                    
                    // NAYE BUTTONS YAHAN HAIN
                    echo "<td style='display:flex; gap:8px;'>
                            <button class='btn-view' onclick='openModal(\"{$row['name']}\", \"{$row['subject']}\")'><i class='fas fa-eye'></i> View</button>
                            <button class='btn-action-sm check' title='Approve'><i class='fas fa-check'></i></button>
                            <button class='btn-action-sm times' title='Reject'><i class='fas fa-times'></i></button>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </table>
        </div>
    </div>
</div>

<!-- Modal Popup Window (Ye hona zaroori tha!) -->
<div id="studentModal" class="modal-overlay">
    <div class="modal-content">
        <h2 id="modalName">Student Name</h2>
        <p id="modalSubject" style="color:#64748b; margin:10px 0; font-weight:500;">Subject Details</p>
        <button onclick="closeModal()" class="btn-close">Close</button>
    </div>
</div>

<script src="assets/js/faculty_dashboard.js?v=200"></script>
</body>
</html>