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
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data['name'] ?? 'System Administrator';
$name_parts = explode(' ', trim($admin_name));

// 🔍 REAL SEARCH & STATUS FILTER LOGIC
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : 'All';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$where_clauses = [];

// Status filter condition
if ($filter_status !== 'All' && $filter_status !== '') {
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

// Fetch real submissions with filters applied
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
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;}
        .page-header { background: linear-gradient(135deg, #2563eb, #4f46e5); color: white; padding: 30px; border-radius: 16px; margin-bottom: 25px; box-shadow: 0 10px 25px rgba(67, 56, 202, 0.15); display: flex; justify-content: space-between; align-items: center;}
        
        .icon-box-blue { width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px; }

        .filter-panel { background: white; padding: 20px; border-radius: 12px; border: 1px solid #cbd5e1; margin-bottom: 25px; }
        .form-select, .form-control { border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 600; font-size: 14px;}
        .form-select:focus, .form-control:focus { border-color: var(--primary); outline: none; background: white; box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);}
        
        .search-wrapper { position: relative; width: 100%; }
        .search-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-wrapper input { width: 100%; padding: 10px 15px 10px 40px; }

        .content-box { background: var(--surface); border-radius: 16px; padding: 0; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 25px;}
        
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; }
        
        .badge-modern { padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;}
        .status-Pending { background: #fef3c7; color: #d97706;}
        .status-Approved { background: #d1fae5; color: #059669;}
        .status-Rejected { background: #fee2e2; color: #dc2626;}

        .btn-action-view { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-action-view:hover { background: #2563eb; color: white; transform: translateY(-2px); }
        .btn-action-edit { background: rgba(16, 185, 129, 0.1); color: #10b981; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 5px; }
        .btn-action-edit:hover { background: #10b981; color: white; transform: translateY(-2px); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Admin Portal</div>
        </div>
        <ul class="nav-links">
            <li class="active" onclick="window.location.href='dashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book-open"></i> Subject Mgmt</li>
            <li onclick="window.location.href='Profile.php'"><i class="fas fa-user-shield"></i> Profile</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <div class="topbar">
            <h3 class="fw-bold m-0">Submissions Data</h3>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-outline-dark fw-bold btn-sm px-3"><i class="fas fa-print me-1"></i> Print List</button>
                <button onclick="exportTableToCSV('Student_Submissions.csv')" class="btn btn-success fw-bold btn-sm px-3"><i class="fas fa-file-excel me-1"></i> Export Data</button>
            </div>
        </div>

        <div class="page-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box-blue"><i class="fas fa-inbox"></i></div>
                <div>
                    <h3 class="fw-bold mb-1">Student Submissions</h3>
                    <p class="mb-0 fw-medium opacity-75">Track and manage practical files submitted by students.</p>
                </div>
            </div>
            <?php if (!empty($search_query) || $filter_status !== 'All'): ?>
                <a href="Submissions.php" class="btn btn-outline-light fw-bold px-4 py-2" style="border-radius: 8px;"><i class="fas fa-times me-1"></i> Clear Filters</a>
            <?php endif; ?>
        </div>

        <!-- FILTER PANEL -->
        <div class="filter-panel">
            <form method="GET" action="Submissions.php" class="row align-items-end g-3">
                <div class="col-md-6">
                    <label class="fw-bold text-muted small text-uppercase mb-1"><i class="fas fa-search me-1"></i> Search Student or Subject</label>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="form-control" placeholder="Search by name, enrollment, or subject..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="fw-bold text-muted small text-uppercase mb-1">Filter by Status</label>
                    <select name="status" class="form-select">
                        <option value="All" <?php echo ($filter_status == 'All') ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="Pending" <?php echo ($filter_status == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo ($filter_status == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="Rejected" <?php echo ($filter_status == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary fw-bold w-100 py-2"><i class="fas fa-filter me-1"></i> Apply</button>
                </div>
            </form>
        </div>

        <!-- TABLE SECTION -->
        <div class="content-box">
            <div class="p-4 border-bottom bg-light d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-list text-primary me-2"></i> Submissions List</h5>
                <span class="badge bg-primary rounded-pill px-3 py-2 fw-bold" style="font-size: 13px;">Total: <?php echo $submissions_result ? $submissions_result->num_rows : 0; ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-custom mb-0" id="submissionsTable">
                    <thead>
                        <tr>
                            <th class="ps-4">Student Details</th>
                            <th>Subject & Practical</th>
                            <th>Submitted Date</th>
                            <th>Marks</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($submissions_result && $submissions_result->num_rows > 0): ?>
                            <?php while($row = $submissions_result->fetch_assoc()): 
                                $sub_id = $row['submission_id'] ?? 0;
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark" style="font-size: 14.5px;"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                        <div class="text-primary fw-bold mt-1" style="font-size: 12px; letter-spacing: 0.5px;"><i class="fas fa-id-badge me-1"></i> <?php echo htmlspecialchars($row['student_enrollment']); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 14px;"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                                        <div class="text-muted fw-bold small mt-1">Prac No: <?php echo htmlspecialchars($row['practical_no']); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-muted" style="font-size: 13px;"><i class="fas fa-clock text-warning me-1"></i> <?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?></div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark" style="font-size: 14px;"><?php echo $row['marks']; ?> <small class="text-muted fw-normal">/ 20</small></span>
                                    </td>
                                    <td>
                                        <span class="badge-modern status-<?php echo htmlspecialchars($row['status']); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php 
                                            // SMART FIX FOR PDF PATH ERROR
                                            $raw_path = $row['file_path'];
                                            $pos = strpos($raw_path, 'uploads/');
                                            if ($pos !== false) {
                                                $safe_pdf_path = '../' . substr($raw_path, $pos);
                                            } else {
                                                $safe_pdf_path = '../' . $raw_path;
                                            }
                                        ?>
                                        <a href="<?php echo htmlspecialchars($safe_pdf_path); ?>" target="_blank" class="btn-action-view me-1" title="View PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <!-- Review & Marks link points to your existing script -->
                                        <a href="Review & Marks.php?id=<?php echo $sub_id; ?>" class="btn-action-edit" title="Evaluate Submission">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-search mb-3" style="font-size: 40px; opacity: 0.3; color: var(--primary);"></i><br>
                                    <span class="fw-bold text-dark fs-5">No submissions found</span><br>
                                    <span class="small opacity-75">Try adjusting your search or filter criteria.</span>
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
        // Live Clock
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const clockEl = document.getElementById('liveClock');
            if(clockEl) clockEl.innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Export to CSV Script
        function exportTableToCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("#submissionsTable tr");
            
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                // Skip the last column (Action column)
                for (var j = 0; j < cols.length - 1; j++) { 
                    var data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ");
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
        }
    </script>
</body>
</html>