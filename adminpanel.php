<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Lab Manual & ERP System - Admin Dashboard</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-hover: #2563eb;
            --body-bg: #f8fafc;
            --card-radius: 12px;
        }

        body {
            background-color: var(--body-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            background-color: var(--sidebar-bg);
            min-height: 100vh;
            color: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            transition: all 0.3s;
        }

        .sidebar .brand {
            padding: 20px;
            font-size: 1.1rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar ul.nav-links {
            list-style: none;
            padding: 15px 10px;
            margin: 0;
        }

        .sidebar ul.nav-links li {
            margin-bottom: 3px;
        }

        .sidebar ul.nav-links a {
            color: #94a3b8;
            text-decoration: none;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .sidebar ul.nav-links a:hover, 
        .sidebar ul.nav-links a.active {
            background-color: var(--sidebar-hover);
            color: #ffffff;
        }

        /* Main Wrapper */
        .main-wrapper {
            margin-left: 260px;
            padding: 25px;
        }

        /* Top Bar */
        .top-bar {
            background: #ffffff;
            padding: 12px 24px;
            border-radius: var(--card-radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .stat-card {
            background: #ffffff;
            border: none;
            border-radius: var(--card-radius);
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .content-card {
            background: #ffffff;
            border-radius: var(--card-radius);
            padding: 22px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 25px;
        }

        /* Chart Center Text Overlay */
        .chart-container {
            position: relative;
            height: 240px;
            width: 100%;
        }

        .chart-center-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -60%);
            text-align: center;
            pointer-events: none;
        }

        .chart-center-text .number {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0f172a;
        }

        /* Status Badges */
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-active, .badge-approved { background-color: #dcfce7; color: #15803d; }
        .badge-pending { background-color: #fef3c7; color: #d97706; }
        .badge-inactive, .badge-rejected { background-color: #fee2e2; color: #b91c1c; }

        .tab-content-section {
            display: none;
        }

        .tab-content-section.active {
            display: block;
        }
    </style>
</head>
<body>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="brand">
            <i class="fa-solid fa-microscope text-primary fs-4"></i>
            <span>Lab & ERP System</span>
        </div>
        <ul class="nav-links">
            <li><a class="nav-item active" onclick="switchTab('dashboard-tab', this)"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
            <li><a class="nav-item" onclick="switchTab('student-tab', this)"><i class="fa-solid fa-user-graduate"></i> Student Mgmt</a></li>
            <li><a class="nav-item" onclick="switchTab('faculty-tab', this)"><i class="fa-solid fa-chalkboard-user"></i> Faculty Mgmt</a></li>
            <li><a class="nav-item" onclick="switchTab('subject-tab', this)"><i class="fa-solid fa-book-open"></i> Subject Mgmt</a></li>
            <li><a class="nav-item" onclick="switchTab('lab-tab', this)"><i class="fa-solid fa-file-code"></i> Lab Manuals</a></li>
            <li><a class="nav-item" onclick="switchTab('submission-tab', this)"><i class="fa-solid fa-upload"></i> Submissions</a></li>
            <li><a class="nav-item" onclick="switchTab('review-tab', this)"><i class="fa-solid fa-circle-check"></i> Review & Marks</a></li>
            <li><a class="nav-item" onclick="switchTab('reports-tab', this)"><i class="fa-solid fa-file-invoice"></i> Reports</a></li>
            <li><a class="nav-item" onclick="switchTab('expense-tab', this)"><i class="fa-solid fa-wallet"></i> Expense Mgmt</a></li>
        </ul>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        
        <!-- Top Navbar -->
        <div class="top-bar d-flex justify-content-between align-items-center">
            <div style="width: 280px;">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-0" placeholder="Search globally...">
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <i class="fa-regular fa-bell fs-5 text-secondary cursor-pointer"></i>
                <div class="d-flex align-items-center gap-2">
                    <img src="https://ui-avatars.com/api/?name=Admin+Manager&background=2563eb&color=fff" class="rounded-circle" width="36" alt="User">
                    <div>
                        <div class="fw-semibold text-dark" style="font-size: 0.88rem;">System Administrator</div>
                        <small class="text-muted d-block" style="font-size: 0.72rem; margin-top: -3px;">University Tech</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== 1. MAIN DASHBOARD TAB ==================== -->
        <div id="dashboard-tab" class="tab-content-section active">
            <h4 class="fw-bold text-dark mb-4">University Lab Manager Dashboard</h4>

            <!-- Metric Stat Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small">Total Students</span>
                                <h3 class="fw-bold text-dark mb-0 mt-1">1,245</h3>
                            </div>
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-user-graduate"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small">Active Faculty</span>
                                <h3 class="fw-bold text-dark mb-0 mt-1">48</h3>
                            </div>
                            <div class="stat-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-chalkboard-user"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small">Pending Reviews</span>
                                <h3 class="fw-bold text-dark mb-0 mt-1">128</h3>
                            </div>
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small">Monthly Expense</span>
                                <h3 class="fw-bold text-dark mb-0 mt-1">₹45,200</h3>
                            </div>
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                                <i class="fa-solid fa-indian-rupee-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart & Submissions Overview -->
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="content-card">
                        <h5 class="fw-bold text-dark mb-3">Submission Breakdown</h5>
                        <div class="chart-container">
                            <canvas id="submissionsDoughnut"></canvas>
                            <div class="chart-center-text">
                                <div class="number">1,250</div>
                                <div class="text-muted small">Submissions</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="content-card">
                        <h5 class="fw-bold text-dark mb-3">Recent Student Manual Submissions</h5>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Subject</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Rahul Sharma (BTech CS)</td>
                                        <td>Data Structures</td>
                                        <td>Today, 10:30 AM</td>
                                        <td><span class="status-badge badge-pending">Pending</span></td>
                                    </tr>
                                    <tr>
                                        <td>Priya Patel (BTech IT)</td>
                                        <td>DBMS Lab</td>
                                        <td>Yesterday</td>
                                        <td><span class="status-badge badge-approved">Approved</span></td>
                                    </tr>
                                    <tr>
                                        <td>Aman Verma (BTech CS)</td>
                                        <td>Operating Systems</td>
                                        <td>2 Days ago</td>
                                        <td><span class="status-badge badge-rejected">Rejected</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== 2. STUDENT MANAGEMENT TAB ==================== -->
        <div id="student-tab" class="tab-content-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold text-dark mb-0">👨‍🎓 Student Management</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal"><i class="fa-solid fa-plus me-1"></i> Add New Student</button>
            </div>
            <div class="content-card">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Student ID</th>
                                <th>Enrollment No</th>
                                <th>Name</th>
                                <th>Branch & Sem</th>
                                <th>Batch</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#STU-101</td>
                                <td>EN2024001</td>
                                <td>Rahul Sharma<br><small class="text-muted">rahul@univ.edu</small></td>
                                <td>CSE - Sem 4</td>
                                <td>Batch B1</td>
                                <td><span class="status-badge badge-active">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></button>
                                    <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ==================== 3. FACULTY MANAGEMENT TAB ==================== -->
        <div id="faculty-tab" class="tab-content-section">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold text-dark mb-0">👨‍🏫 Faculty Management</h4>
                <button class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Add Faculty</button>
            </div>
            <div class="content-card">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Faculty ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Assigned Subject</th>
                                <th>Mobile</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>#FAC-201</td>
                                <td>Dr. Anit Kapoor</td>
                                <td>Computer Science</td>
                                <td>Data Structures, DBMS</td>
                                <td>+91 9876543210</td>
                                <td><span class="status-badge badge-active">Active</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ==================== 4. SUBJECT MANAGEMENT TAB ==================== -->
        <div id="subject-tab" class="tab-content-section">
            <h4 class="fw-bold text-dark mb-4">📚 Subject Management</h4>
            <div class="content-card">
                <table class="table">
                    <thead class="table-light">
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Semester</th>
                            <th>Assigned Faculty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>CS401</td>
                            <td>Database Management Systems</td>
                            <td>Semester 4</td>
                            <td>Dr. Anit Kapoor</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ==================== 5. LAB MANUAL MANAGEMENT TAB ==================== -->
        <div id="lab-tab" class="tab-content-section">
            <h4 class="fw-bold text-dark mb-4">📄 Lab Manuals (Practicals)</h4>
            <div class="content-card">
                <button class="btn btn-primary mb-3"><i class="fa-solid fa-upload me-1"></i> Upload Practical Template</button>
                <table class="table">
                    <thead class="table-light">
                        <tr>
                            <th>Practical No.</th>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Deadline</th>
                            <th>PDF Template</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Exp #01</td>
                            <td>SQL Queries Implementation</td>
                            <td>DBMS Lab</td>
                            <td>15 Aug 2026</td>
                            <td><a href="#" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-file-pdf"></i> View PDF</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ==================== 6. SUBMISSION MANAGEMENT TAB ==================== -->
        <div id="submission-tab" class="tab-content-section">
            <h4 class="fw-bold text-dark mb-4">📤 Student Submissions List</h4>
            <div class="content-card">
                <table class="table">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Practical No</th>
                            <th>Upload Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Priya Patel</td>
                            <td>Exp #01 - SQL Queries</td>
                            <td>Today, 11:00 AM</td>
                            <td><span class="status-badge badge-pending">Submitted</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ==================== 7. REVIEW MANAGEMENT TAB ==================== -->
        <div id="review-tab" class="tab-content-section">
            <h4 class="fw-bold text-dark mb-4">✅ Faculty Review & Evaluation</h4>
            <div class="content-card">
                <div class="row">
                    <div class="col-md-6 border-end">
                        <h6 class="fw-bold mb-3">Student Submitted Manual (PDF View)</h6>
                        <div class="p-4 bg-light text-center border rounded">
                            <i class="fa-solid fa-file-pdf text-danger display-3"></i>
                            <p class="mt-2 text-muted mb-0">Student_Manual_Rahul_Exp1.pdf</p>
                            <button class="btn btn-sm btn-primary mt-2">Open Full Screen PDF</button>
                        </div>
                    </div>
                    <div class="col-md-6 ps-4">
                        <h6 class="fw-bold mb-3">Faculty Action Box</h6>
                        <div class="mb-3">
                            <label class="form-label">Give Marks (out of 10)</label>
                            <input type="number" class="form-control" placeholder="e.g. 9">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" rows="3" placeholder="Good work, neat diagrams..."></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success flex-fill"><i class="fa-solid fa-check"></i> Approve</button>
                            <button class="btn btn-danger flex-fill"><i class="fa-solid fa-xmark"></i> Reject</button>
                            <button class="btn btn-warning flex-fill text-white"><i class="fa-solid fa-rotate-right"></i> Re-submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== 8. REPORTS TAB ==================== -->
        <div id="reports-tab" class="tab-content-section">
            <h4 class="fw-bold text-dark mb-4">📊 Reports & Exports</h4>
            <div class="content-card">
                <div class="d-flex gap-2 mb-4">
                    <button class="btn btn-outline-danger"><i class="fa-solid fa-file-pdf me-1"></i> Export PDF</button>
                    <button class="btn btn-outline-success"><i class="fa-solid fa-file-excel me-1"></i> Export Excel</button>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border p-3 rounded">
                            <h6>Student Academic Report</h6>
                            <small class="text-muted">Total Active/Inactive Record</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border p-3 rounded">
                            <h6>Submission Status Report</h6>
                            <small class="text-muted">Pending vs Approved Stats</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
    <!-- ADD STUDENT MODAL -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-2"><label class="form-label">Full Name</label><input type="text" class="form-control"></div>
                        <div class="mb-2"><label class="form-label">Enrollment No.</label><input type="text" class="form-control"></div>
                        <div class="mb-2"><label class="form-label">Email</label><input type="email" class="form-control"></div>
                        <div class="row">
                            <div class="col"><label class="form-label">Branch</label><input type="text" class="form-control"></div>
                            <div class="col"><label class="form-label">Semester</label><input type="text" class="form-control"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button class="btn btn-primary">Save Student</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS & Chart Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Navigation Switcher Function
        function switchTab(tabId, element) {
            document.querySelectorAll('.tab-content-section').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.sidebar .nav-links a').forEach(nav => nav.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            element.classList.add('active');
        }

        // Initialize Chart
        const ctx = document.getElementById('submissionsDoughnut').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending', 'Rejected'],
                datasets: [{
                    data: [650, 400, 200],
                    backgroundColor: ['#22c55e', '#f59e0b', '#ef4444'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                cutout: '70%'
            }
        });
    </script>
</body>
</html