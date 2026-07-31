<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< Updated upstream
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
=======
    <title>Digital Lab Manual & Expense Tracker</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen">

    <!-- Navbar Header -->
    <nav class="bg-indigo-600 text-white p-4 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold flex items-center gap-2">
                <i data-lucide="book-open"></i> Digital Lab Manual & Expense Manager
            </h1>
            <div class="flex items-center gap-3">
                <span class="bg-indigo-800 text-indigo-100 text-xs px-3 py-1 rounded-full font-semibold">Branch: feature/expense</span>
                <span class="bg-green-500 text-white text-xs px-3 py-1 rounded-full font-medium flex items-center gap-1">
                    <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span> Frontend Mode
                </span>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ================= PAGE 1: STUDENT PROGRESS (LEFT 2 COLUMNS) ================= -->
        <section class="lg:col-span-2 space-y-6">
            
            <!-- Progress Overview Card -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-indigo-600 flex items-center gap-2">
                        <i data-lucide="user-check"></i> Student Progress Tracker
                    </h2>
                    <span class="text-xs font-semibold text-slate-500">Student ID: #ST-8092</span>
                </div>
                
                <!-- Progress Bar -->
                <div class="mb-6">
                    <div class="flex justify-between text-sm font-semibold mb-2">
                        <span>Lab Completion Rate</span>
                        <span id="progressText" class="text-indigo-600">75%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-3.5 p-0.5 border border-slate-200">
                        <div id="progressBar" class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500" style="width: 75%"></div>
                    </div>
                </div>

                <!-- Stats Badges -->
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl">
                        <p class="text-xs text-blue-600 font-semibold uppercase tracking-wider">Total Labs</p>
                        <p class="text-2xl font-bold text-blue-800">12</p>
                    </div>
                    <div class="p-3 bg-emerald-50 border border-emerald-100 rounded-xl">
                        <p class="text-xs text-emerald-600 font-semibold uppercase tracking-wider">Completed</p>
                        <p class="text-2xl font-bold text-emerald-800">9</p>
                    </div>
                    <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl">
                        <p class="text-xs text-amber-600 font-semibold uppercase tracking-wider">Pending</p>
                        <p class="text-2xl font-bold text-amber-800">3</p>
                    </div>
                </div>
            </div>

            <!-- Experiment Submission List -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-md font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <i data-lucide="file-text"></i> Experiment Checklist & Status
                </h3>
                <div class="space-y-3">
                    <div class="p-3 bg-slate-50 rounded-xl flex justify-between items-center border border-slate-100">
                        <div>
                            <p class="font-medium text-slate-800 text-sm">Exp 01: Basic Circuit Breadboard Assembly</p>
                            <p class="text-xs text-slate-400">Submitted on: Aug 10 • Score: 10/10</p>
                        </div>
                        <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 text-xs rounded-lg font-semibold">Verified</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl flex justify-between items-center border border-slate-100">
                        <div>
                            <p class="font-medium text-slate-800 text-sm">Exp 02: Microcontroller Interfacing & LED Matrix</p>
                            <p class="text-xs text-slate-400">Submitted on: Aug 14 • Score: 9/10</p>
                        </div>
                        <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 text-xs rounded-lg font-semibold">Verified</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl flex justify-between items-center border border-slate-100">
                        <div>
                            <p class="font-medium text-slate-800 text-sm">Exp 03: Sensor Calibration & Data Logging</p>
                            <p class="text-xs text-slate-400">Submitted on: Yesterday</p>
                        </div>
                        <span class="px-2.5 py-1 bg-amber-100 text-amber-700 text-xs rounded-lg font-semibold">In Review</span>
                    </div>
                </div>
            </div>

            <!-- Admin & Settings Quick Panel -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-md font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <i data-lucide="settings"></i> Admin & Module Settings
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 border border-slate-200 rounded-xl bg-slate-50">
                        <p class="text-sm font-semibold text-slate-700">Currency & Export Settings</p>
                        <p class="text-xs text-slate-500 mb-3">Set default currency and report format.</p>
                        <button onclick="alert('Exporting Report as PDF...')" class="bg-indigo-600 text-white text-xs px-3 py-1.5 rounded-lg font-medium hover:bg-indigo-700">Export PDF Report</button>
                    </div>
                    <div class="p-4 border border-slate-200 rounded-xl bg-slate-50">
                        <p class="text-sm font-semibold text-slate-700">Clear Local Cache</p>
                        <p class="text-xs text-slate-500 mb-3">Reset demo expenses data.</p>
                        <button onclick="resetData()" class="bg-red-500 text-white text-xs px-3 py-1.5 rounded-lg font-medium hover:bg-red-600">Reset Expenses</button>
                    </div>
                </div>
            </div>

        </section>

        <!-- ================= PAGE 2: EXPENSE MODULE (RIGHT COLUMN) ================= -->
        <section class="space-y-6">
            
            <!-- Expense Summary Card -->
            <div class="bg-gradient-to-br from-indigo-600 to-indigo-700 text-white p-6 rounded-2xl shadow-md">
                <p class="text-xs text-indigo-200 uppercase tracking-wider font-semibold">Total Module Expense</p>
                <h3 class="text-3xl font-extrabold mt-1" id="totalExpenseAmount">₹0.00</h3>
                <p class="text-xs text-indigo-200 mt-2">Tracked automatically from added items.</p>
            </div>

            <!-- Add Expense Form -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-md font-bold text-indigo-600 mb-4 flex items-center gap-2">
                    <i data-lucide="plus-circle"></i> Add New Expense
                </h3>
                
                <form id="expenseForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Item Title</label>
                        <input type="text" id="expTitle" required placeholder="e.g. Arduino UNO Board" class="w-full p-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Amount (₹)</label>
                            <input type="number" id="expAmount" required placeholder="450" class="w-full p-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Category</label>
                            <select id="expCategory" class="w-full p-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                                <option>Components</option>
                                <option>Printouts</option>
                                <option>Software</option>
                                <option>Other</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition flex justify-center items-center gap-2">
                        <i data-lucide="plus"></i> Add Expense Item
                    </button>
                </form>
            </div>

            <!-- Expense History List -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-md font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <i data-lucide="history"></i> Expense History
                </h3>
                <div id="expenseList" class="space-y-3 max-h-72 overflow-y-auto pr-1">
                    <!-- Items inserted dynamically via JavaScript -->
                </div>
            </div>

        </section>

    </main>

    <!-- JavaScript logic -->
    <script>
        lucide.createIcons();

        // Load expenses from Local Storage
        let expenses = JSON.parse(localStorage.getItem('lab_expenses')) || [
            { id: 1, title: 'Lab Manual Binding & Printout', amount: 150, category: 'Printouts' },
            { id: 2, title: 'Breadboard & Connecting Wires', amount: 280, category: 'Components' }
        ];

        function renderExpenses() {
            const list = document.getElementById('expenseList');
            const totalElement = document.getElementById('totalExpenseAmount');
            list.innerHTML = '';

            let total = 0;

            if(expenses.length === 0) {
                list.innerHTML = <p class="text-xs text-slate-400 text-center py-4">No expenses added yet.</p>;
            } else {
                expenses.forEach((item, index) => {
                    total += parseFloat(item.amount);
                    const div = document.createElement('div');
                    div.className = "flex justify-between items-center p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-slate-200 transition";
                    div.innerHTML = `
                        <div>
                            <p class="font-medium text-slate-800 text-sm">${item.title}</p>
                            <span class="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded-md font-medium">${item.category}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-indigo-600 text-sm">₹${item.amount}</span>
                            <button onclick="deleteExpense(${index})" class="text-slate-400 hover:text-red-500">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    `;
                    list.appendChild(div);
                });
            }

            totalElement.innerText = ₹${total.toFixed(2)};
            localStorage.setItem('lab_expenses', JSON.stringify(expenses));
            lucide.createIcons();
        }

        // Add Expense Function
        document.getElementById('expenseForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const title = document.getElementById('expTitle').value;
            const amount = document.getElementById('expAmount').value;
            const category = document.getElementById('expCategory').value;

            expenses.unshift({ id: Date.now(), title, amount: parseFloat(amount), category });
            renderExpenses();

            // Clear inputs
            document.getElementById('expTitle').value = '';
            document.getElementById('expAmount').value = '';
        });

        // Delete Expense Function
        function deleteExpense(index) {
            expenses.splice(index, 1);
            renderExpenses();
        }

        // Reset Data
        function resetData() {
            if(confirm("Are you sure you want to clear all expenses?")) {
                expenses = [];
                renderExpenses();
            }
        }

        // Initialize Render
        renderExpenses();
>>>>>>> Stashed changes
    </script>
</body>
</html>