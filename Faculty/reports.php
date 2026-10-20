<?php
session_start();
include '../db.php';

// 1. SECURE LOGIN CHECK
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_name = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Faculty';

// FETCH MAPPED SUBJECTS FOR FILTER
$fac_name_safe = $conn->real_escape_string($faculty_name);
$assigned_subjects = [];
$sub_query = $conn->query("SELECT DISTINCT subject_name, semester FROM subjects WHERE faculty_name LIKE '%$fac_name_safe%'");
if ($sub_query && $sub_query->num_rows > 0) {
    while($r = $sub_query->fetch_assoc()) { 
        $assigned_subjects[] = $r; 
    }
}

// GET FILTER VALUES
$selected_subject = isset($_GET['subject']) ? $conn->real_escape_string($_GET['subject']) : '';
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// BUILD QUERY (Strict Mapping to Faculty's Subjects)
$sub_names = array_map(function($sub) use ($conn) { return "'" . $conn->real_escape_string($sub['subject_name']) . "'"; }, $assigned_subjects);
$in_clause = empty($sub_names) ? "''" : implode(",", $sub_names);

$report_sql = "
    SELECT s.student_id, u.name as student_name, s.subject_name, s.practical_no, s.status, s.marks, s.submitted_at 
    FROM student_submissions s 
    JOIN users u ON s.student_id = u.user_id 
    WHERE s.subject_name IN ($in_clause)
";

if (!empty($selected_subject)) {
    $report_sql .= " AND s.subject_name = '$selected_subject'";
}
if (!empty($search_query)) {
    $report_sql .= " AND (u.name LIKE '%$search_query%' OR s.student_id LIKE '%$search_query%')";
}
$report_sql .= " ORDER BY s.subject_name ASC, s.practical_no ASC, u.name ASC";

$reports = $conn->query($report_sql);

// CALCULATE ANALYTICS
$total_subs = 0;
$total_pass = 0;
if ($reports && $reports->num_rows > 0) {
    $total_subs = $reports->num_rows;
    while($row = $reports->fetch_assoc()) {
        if ($row['status'] == 'Approved' && (int)$row['marks'] >= 7) { // Assuming passing marks > 7 out of 20
            $total_pass++;
        }
    }
    $reports->data_seek(0); // Reset pointer for table rendering
}
$pass_percentage = ($total_subs > 0) ? round(($total_pass / $total_subs) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics & Reports - KDP Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-width: 270px; --primary: #4338ca; --bg-body: #f8fafc; --surface: #ffffff; --text-main: #0f172a; --text-muted: #64748b; }
        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); transition: 0.3s;}
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 13px; color: #bfdbfe; margin-top: 5px; font-weight: 500;} 
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}
        
        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        
        .stat-card-rad { background: var(--surface); border-radius: 12px; padding: 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0;}
        .stat-info h4 { font-size: 26px; font-weight: 900; color: var(--text-main); margin: 0; line-height: 1;}
        .stat-info span { font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; margin-bottom: 5px; display: block;}
        
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 12px 10px; border-bottom: 1px solid #f1f5f9; }

        @media print {
            .sidebar, .topbar, .btn-print, .btn-export, .filter-form { display: none !important; }
            .main { padding: 0 !important; width: 100% !important; overflow: visible !important; height: auto !important;}
            .content-box { border: none !important; box-shadow: none !important; }
            .table-custom th { background: #e2e8f0 !important; color: #000 !important; }
        }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Faculty Portal</div>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='faculty_dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='manage_manuals.php'"><i class="fas fa-file-pdf"></i> Upload Manuals</li>
            <li onclick="window.location.href='labmanual_list.php'"><i class="fas fa-check-double"></i> Review & Evaluate</li>
            <li class="active" onclick="window.location.href='reports.php'"><i class="fas fa-chart-pie"></i> Reports</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="d-flex justify-content-between align-items-center mb-4 topbar">
            <div>
                <h3 class="fw-bold text-dark mb-1" style="font-size: 26px;">Term-work Reports</h3>
                <p class="text-muted fw-semibold small mb-0">Generate, print, and export subject-wise analytics.</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-dark fw-bold btn-print"><i class="fas fa-print me-2"></i> Print Report</button>
                <button onclick="exportTableToCSV('Student_Termwork_Report.csv')" class="btn btn-success fw-bold btn-export"><i class="fas fa-file-excel me-2"></i> Export CSV</button>
            </div>
        </div>

        <div class="row g-4 mb-4 filter-form">
            <div class="col-md-8">
                <form method="GET" class="d-flex gap-2 p-3 bg-white rounded-3 border">
                    <select name="subject" class="form-select fw-bold bg-light" style="width: 250px;">
                        <option value="">All Assigned Subjects</option>
                        <?php foreach($assigned_subjects as $sub): ?>
                            <option value="<?php echo htmlspecialchars($sub['subject_name']); ?>" <?php if($selected_subject == $sub['subject_name']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($sub['subject_name']); ?> (Sem <?php echo htmlspecialchars($sub['semester']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="search" class="form-control fw-bold bg-light" placeholder="Search Student ID or Name..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-primary fw-bold px-4">Generate</button>
                </form>
            </div>
            <div class="col-md-4">
                <div class="stat-card-rad h-100 p-3">
                    <div class="stat-info"><span>Overall Pass Rate</span><h4><?php echo $pass_percentage; ?>%</h4></div>
                    <div style="width: 45px; height: 45px; background:rgba(16, 185, 129, 0.1); color:#10b981; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:20px;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-box p-0 overflow-hidden" id="printableTable">
            <div class="p-3 border-bottom bg-light">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-table text-primary me-2"></i> Performance Data</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom mb-0" id="reportTable">
                    <thead>
                        <tr>
                            <th class="ps-4">Enrollment No.</th>
                            <th>Student Name</th>
                            <th>Subject</th>
                            <th>Prac No.</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Score (Out of 20)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($reports && $reports->num_rows > 0): ?>
                            <?php while($row = $reports->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-primary"><?php echo htmlspecialchars($row['student_id']); ?></td>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                    <td class="fw-semibold text-muted"><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                    <td class="fw-semibold text-muted">Prac <?php echo htmlspecialchars($row['practical_no']); ?></td>
                                    <td>
                                        <span class="badge <?php echo ($row['status'] == 'Approved') ? 'bg-success' : (($row['status'] == 'Pending') ? 'bg-warning text-dark' : 'bg-danger'); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4 fw-bold <?php echo ((int)$row['marks'] < 7 && $row['status'] != 'Pending') ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo ($row['status'] == 'Pending') ? '--' : $row['marks']; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted fw-bold">No evaluation records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportTableToCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("#reportTable tr");
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                for (var j = 0; j < cols.length; j++) { 
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
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
        }
    </script>
</body>
</html>