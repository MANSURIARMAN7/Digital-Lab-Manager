<?php
session_start();
include '../db.php';

// 1. Admin Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch Admin Details for Profile Pill
$admin_id = $_SESSION['user_id'];
$admin_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query ? $admin_query->fetch_assoc() : null;
$admin_name = $admin_data['name'] ?? 'System Administrator';

// ==========================================
// 🔍 REAL SEARCH & STATUS FILTER LOGIC
// ==========================================
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$where_clauses = [];

// Status filter condition
if ($filter_status !== 'all' && $filter_status !== '') {
    $safe_status = $conn->real_escape_string($filter_status);
    $where_clauses[] = "sub.status = '$safe_status'";
}

// Search filter condition (searches student name, enrollment/email, or subject name)
if (!empty($search_query)) {
    $safe_search = $conn->real_escape_string($search_query);
    $where_clauses[] = "(u.name LIKE '%$safe_search%' OR u.email LIKE '%$safe_search%' OR sub.subject_name LIKE '%$safe_search%')";
}

// Build final SQL WHERE clause
$sql_where = "";
if (count($where_clauses) > 0) {
    $sql_where = "WHERE " . implode(" AND ", $where_clauses);
}

// Fetch Real Submissions with Filter applied
$submissions_query = "
    SELECT sub.*, 
           COALESCE(NULLIF(u.name, ''), 'Unknown Student') as student_name, 
           COALESCE(NULLIF(u.email, ''), 'N/A') as student_enrollment, 
           COALESCE(NULLIF(u.department, ''), 'Computer Engineering') as department 
    FROM student_submissions sub 
    LEFT JOIN users u ON sub.student_id = u.user_id 
    $sql_where 
    ORDER BY sub.submitted_at DESC
";
$submissions_result = $conn->query($submissions_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Submissions - Admin Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --sidebar-width: 270px; 
            --primary: #4338ca; 
            --primary-hover: #3730a3;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --shadow-float: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            --radius-xl: 16px;
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; overflow-y: auto; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0; letter-spacing: 0.5px; color: #ffffff;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); border-left: 3px solid transparent; }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; border-left: 4px solid #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);}

        .content-box { background: var(--surface); border-radius: var(--radius-xl); padding: 30px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); margin-bottom: 25px;}
        .content-box:hover { box-shadow: var(--shadow-float); }
        .icon-box { width: 60px; height: 60px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 26px; }
        .blue-box { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }

        .btn-gradient { background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; border: none; font-weight: 700; padding: 12px 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); transition: var(--transition-bounce); }
        .btn-gradient:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4); color: white; }
        
        .btn-outline-modern { background: white; color: var(--text-main); font-weight: 700; padding: 10px 18px; border-radius: 10px; border: 1px solid #cbd5e1; transition: var(--transition-bounce); }
        .btn-outline-modern:hover { background: #f8fafc; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }

        input.form-control, select.form-select { border-radius: 10px; padding: 12px; border: 1px solid #cbd5e1; font-weight: 500; font-size: 14px; transition: var(--transition-bounce); }
        input.form-control:focus, select.form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); transform: scale(1.01); }

        .table-custom th { background: transparent; font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; }
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; transition: background-color 0.2s; }
        .table-custom tbody tr:hover td { background-color: #f8fafc; }
        
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .bg-success-soft { background: rgba(16, 185, 129, 0.1); color: #059669; }
        .bg-warning-soft { background: rgba(245, 158, 11, 0.1); color: #d97706; }
        .bg-danger-soft { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        
        .btn-action-view { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 8px 12px; border-radius: 8px; transition: var(--transition-bounce); margin-right: 5px; text-decoration: none;}
        .btn-action-view:hover { background: #2563eb; color: white; transform: scale(1.05); }
        .btn-action-edit { background: rgba(16, 185, 129, 0.1); color: #059669; border: none; padding: 8px 12px; border-radius: 8px; transition: var(--transition-bounce); text-decoration: none;}
        .btn-action-edit:hover { background: #059669; color: white; transform: scale(1.05); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        @media print {
            body { background: white !important; }
            .sidebar, .topbar, .page-header, .filter-section, .action-col { display: none !important; }
            .main { padding: 0 !important; margin: 0 !important; animation: none !important; height: auto !important; overflow: visible !important; }
            .content-box { border: none !important; box-shadow: none !important; padding: 0 !important; }
            .table-custom th { color: #000; border-bottom: 2px solid #000; }
            .table-custom td { color: #000; border-bottom: 1px solid #ccc; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-pdf"></i> Lab Manuals</li>
            <li class="active" onclick="window.location.href='Submissions.php'"><i class="fas fa-inbox"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-double"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge">
                    <i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span>
                </div>

                <button class="btn-outline-modern ms-3" onclick="window.print()" title="Print Submissions List">
                    <i class="fas fa-print text-primary me-2"></i> Print List
                </button>
                
                <button class="btn-outline-modern ms-2" onclick="exportTableToCSV('Student_Submissions.csv')" title="Download Excel File">
                    <i class="fas fa-file-excel text-success me-2"></i> Export Data
                </button>
            </div>
            
            <a href="Profile.php" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Welcome Back,</span>
                    <h4 class="profile-name">
                        <?php 
                            $name_parts = explode(' ', $admin_name);
                            echo (count($name_parts) > 1) ? mb_substr($name_parts[0], 0, 1) . '. ' . $name_parts[count($name_parts)-1] : 'Admin';
                        ?>
                    </h4>
                </div>
                <div class="profile-avatar">AD</div>
            </a>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 page-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box blue-box"><i class="fas fa-inbox"></i></div>
                <div>
                    <h3 class="fw-bold mb-1" style="font-size: 28px; color: var(--text-main);">Student Submissions</h3>
                    <p class="text-muted fw-semibold small mb-0">Track and manage practical files submitted by students.</p>
                </div>
            </div>
            <?php if(!empty($search_query) || $filter_status !== 'all'): ?>
            <a href="Submissions.php" class="btn btn-outline-danger fw-bold" style="border-radius: 10px; padding: 10px 20px;">
                <i class="fas fa-times me-1"></i> Clear Filters
            </a>
            <?php endif; ?>
        </div>

        <div class="content-box mb-4 py-3 px-4 filter-section" style="background: linear-gradient(135deg, rgba(67, 56, 202, 0.03), rgba(59, 130, 246, 0.05)); border: 1px solid rgba(67, 56, 202, 0.1);">
            <form method="GET" action="Submissions.php" class="row align-items-end g-3">
                <div class="col-md-7">
                    <label class="form-label fw-bold small text-primary text-uppercase letter-spacing-1 mb-2">Search Student or Subject</label>
                    <div class="input-group shadow-sm" style="border-radius: 10px; overflow: hidden;">
                        <span class="input-group-text bg-white border-end-0 text-muted px-3"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search by name, enrollment, or subject..." value="<?php echo htmlspecialchars($search_query); ?>" style="box-shadow: none;">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-primary text-uppercase letter-spacing-1 mb-2">Filter by Status</label>
                    <select name="status" class="form-select shadow-sm fw-bold text-dark" style="border-color: #cbd5e1; cursor: pointer;">
                        <option value="all" <?php echo ($filter_status == 'all') ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="Pending" <?php echo ($filter_status == 'Pending') ? 'selected' : ''; ?>>⏳ Pending</option>
                        <option value="Approved" <?php echo ($filter_status == 'Approved') ? 'selected' : ''; ?>>✅ Approved</option>
                        <option value="Rejected" <?php echo ($filter_status == 'Rejected') ? 'selected' : ''; ?>>❌ Rejected</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn-gradient w-100 py-2">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>
                </div>
            </form>
        </div>

        <div class="content-box">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="box-title mb-0"><i class="fas fa-list text-success me-2"></i> Submissions List</h5>
                <span class="badge bg-primary text-white action-col" style="border-radius: 20px; padding: 5px 12px;">Total: <?php echo $submissions_result ? $submissions_result->num_rows : 0; ?></span>
            </div>
            <hr class="mb-4 action-col" style="border-color: #e2e8f0;">
            
            <div class="table-responsive">
                <table class="table table-custom mb-0" id="submissionTable">
                    <thead>
                        <tr>
                            <th>Student Details</th>
                            <th>Subject & Practical</th>
                            <th>Submitted Date</th>
                            <th>Marks</th>
                            <th>Status</th>
                            <th class="text-end action-col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($submissions_result && $submissions_result->num_rows > 0): ?>
                            <?php while($row = $submissions_result->fetch_assoc()): 
                                $sub_id = $row['submission_id'] ?? 0;
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold" style="font-size: 14.5px; color: var(--text-main);"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                        <div class="text-muted fw-semibold mt-1" style="font-size: 11.5px; letter-spacing: 0.5px;"><?php echo htmlspecialchars($row['student_enrollment']); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold" style="font-size: 13.5px; color: var(--primary);"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                                        <div class="text-muted fw-semibold mt-1" style="font-size: 11.5px;"><?php echo htmlspecialchars($row['practical_no']); ?></div>
                                    </td>
                                    <td>
                                        <div style="font-size: 12.5px; color: var(--text-muted);" class="fw-semibold">
                                            <i class="far fa-clock text-warning me-1"></i> <?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold" style="font-size: 14px; color: var(--text-main);"><?php echo $row['marks']; ?> / 20</span>
                                    </td>
                                    <td>
                                        <?php if($row['status'] == 'Approved'): ?>
                                            <span class="badge-modern bg-success-soft">Approved</span>
                                        <?php elseif($row['status'] == 'Rejected'): ?>
                                            <span class="badge-modern bg-danger-soft">Rejected</span>
                                        <?php else: ?>
                                            <span class="badge-modern bg-warning-soft">Pending Review</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end action-col">
                                        <?php 
                                            // 🛠️ SMART FIX FOR 404 PDF ERROR
                                            $raw_path = $row['file_path'];
                                            $pos = strpos($raw_path, 'uploads/');
                                            if ($pos !== false) {
                                                $safe_pdf_path = '../' . substr($raw_path, $pos);
                                            } else {
                                                $safe_pdf_path = '../' . $raw_path;
                                            }
                                        ?>
                                        <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" target="_blank" class="btn-action-view" title="View PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <a href="Review & Marks.php?id=<?php echo $sub_id; ?>" class="btn-action-edit" title="Evaluate Submission">
                                            <i class="fas fa-edit"></i> Evaluate
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5 action-col">
                                    <i class="fas fa-search mb-3" style="font-size: 40px; color: #cbd5e1;"></i><br>
                                    <span class="fw-bold fs-5 text-dark">No submissions found.</span><br>
                                    <small class="fw-semibold">Try adjusting your search or filter criteria.</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 1. Live Clock Script
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // 2. EXPORT TO CSV / EXCEL SCRIPT
        function exportTableToCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("#submissionTable tr");
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                
                // Skip the last column (Action column) which is index 5
                for (var j = 0; j < cols.length - 1; j++) {
                    var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " - ");
                    row.push('"' + data + '"');
                }
                csv.push(row.join(","));
            }

            var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
            var downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>