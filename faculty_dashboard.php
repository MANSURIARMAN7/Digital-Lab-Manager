<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - KDP</title>

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js for Graphs (Very Important) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Aapki Custom CSS (Version 5 ke sath taaki naya design load ho) -->
    <link rel="stylesheet" href="assets/css/faculty_dashboard.css?v=5">
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
            
            <div class="faculty-profile">
                <i class="fas fa-bell notification-icon"></i>
                <div class="profile-info">
                    <img src="https://ui-avatars.com/api/?name=Faculty&background=2563eb&color=fff" alt="Profile" class="profile-pic">
                    <span class="faculty-name">Welcome, Faculty</span>
                    <i class="fas fa-chevron-down dropdown-icon" style="color: #64748b; font-size: 12px;"></i>
                </div>
            </div>
        </div>

        <!-- 3. Statistics Cards -->
        <div class="cards">
            <div class="card">
                <div class="card-content">
                    <h3>Total Students</h3>
                    <p>120</p>
                    <span class="trend up"><i class="fas fa-arrow-up"></i> 100% Registered</span>
                </div>
                <i class="fas fa-users bg-icon"></i>
            </div>
            <div class="card">
                <div class="card-content">
                    <h3>Pending Manuals</h3>
                    <p>18</p>
                    <span class="trend down"><i class="fas fa-arrow-down"></i> Needs Review</span>
                </div>
                <i class="fas fa-clock bg-icon"></i>
            </div>
            <div class="card">
                <div class="card-content">
                    <h3>Approved</h3>
                    <p>95</p>
                    <span class="trend up"><i class="fas fa-arrow-up"></i> 79% Approval Rate</span>
                </div>
                <i class="fas fa-check-circle bg-icon"></i>
            </div>
            <div class="card">
                <div class="card-content">
                    <h3>Rejected</h3>
                    <p>07</p>
                    <span class="trend down"><i class="fas fa-exclamation-circle"></i> Needs Correction</span>
                </div>
                <i class="fas fa-times-circle bg-icon"></i>
            </div>
        </div>

        <!-- 4. Analytics & Timeline Section -->
        <div class="dashboard-widgets">
            <!-- Graph Box (Yahan canvas hai) -->
            <div class="widget-box chart-box">
                <h3>Submission Statistics</h3>
                <div class="canvas-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Timeline Box -->
            <div class="widget-box timeline-box">
                <h3>Recent Activities</h3>
                <ul class="timeline">
                    <li>
                        <div class="time-icon success"><i class="fas fa-check"></i></div>
                        <div class="time-content">
                            <p><strong>Rahul</strong>'s ML Manual Approved</p>
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
                            <p><strong>Priya</strong>'s OS Manual Rejected</p>
                            <span>Yesterday</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- 5. Recent Submissions Table -->
        <div class="table-section">
            <div class="table-header">
                <h3>Recent Submissions</h3>
                <button class="btn-view" style="background: #2563eb; color: white;">View All <i class="fas fa-arrow-right"></i></button>
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
                <tr>
                    <td>Arman</td>
                    <td>230120107001</td>
                    <td><span class="subject-tag">Software Engineering</span></td>
                    <td>18-07-2026</td>
                    <td><span class="badge pending">Pending</span></td>
                    <td><button class="btn-view"><i class="fas fa-eye"></i> View</button></td>
                </tr>
                <tr>
                    <td>Rahul</td>
                    <td>230120107002</td>
                    <td><span class="subject-tag">Machine Learning</span></td>
                    <td>17-07-2026</td>
                    <td><span class="badge approved">Approved</span></td>
                    <td><button class="btn-view"><i class="fas fa-eye"></i> View</button></td>
                </tr>
            </table>
        </div>

    </div>

</div>

<!-- Custom JS File (Version 20 ke sath taaki naya graph chale) -->
<script src="assets/js/faculty_dashboard.js?v=20"></script>

</body>
</html>