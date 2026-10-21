<?php
session_start();
include '../db.php';

// 1. Admin login check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// 2. Fetch Admin Details for Profile Pill
$admin_query = $conn->query("SELECT name, department FROM users WHERE user_id = '$admin_id'");
$admin_data = $admin_query->fetch_assoc();
$admin_name = $admin_data['name'] ?? 'System Administrator';
$name_parts = explode(' ', trim($admin_name));

$msg = "";

// ==========================================
// UPLOAD NEW LAB MANUAL LOGIC (With Deadline)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_manual'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $department = $conn->real_escape_string($_POST['department']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $subject_name = $conn->real_escape_string($_POST['subject_name']);
    
    // Convert empty date to NULL
    $end_date = empty($_POST['end_date']) ? $conn->real_escape_string($_POST['end_date']) : NULL;
    $end_date_val = $end_date ? "'$end_date'" : "NULL";

    // FILE UPLOAD HANDLING
    if (isset($_FILES['manual_file']) && $_FILES['manual_file']['error'] == 0) {
        $upload_dir = "../uploads/lab_manuals/";

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.-]/", "_", basename($_FILES['manual_file']['name']));
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['manual_file']['tmp_name'], $target_file)) {
            $db_path = "uploads/lab_manuals/" . $file_name;
            
            $sql = "INSERT INTO lab_manuals (title, department, semester, subject_name, file_path, end_date) 
                    VALUES ('$title', '$department', '$semester', '$subject_name', '$db_path', $end_date_val)";
            
            if ($conn->query($sql)) {
                $msg = "<div class='alert alert-success alert-dismissible fade show mb-4 shadow-sm' style='border-radius:10px; font-weight:600;'><i class='fas fa-check-circle me-2'></i>Lab Manual uploaded successfully with deadline!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $msg = "<div class='alert alert-danger alert-dismissible fade show mb-4 shadow-sm' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i>Database Error: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } else {
            $msg = "<div class='alert alert-danger alert-dismissible fade show mb-4 shadow-sm' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i>Failed to move uploaded file. Check folder permissions.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show mb-4 shadow-sm' style='border-radius:10px;'><i class='fas fa-exclamation-triangle me-2'></i>Please select a valid file to upload.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// DELETE LAB MANUAL LOGIC
// ==========================================
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    
    // Delete file from folder
    $get_file = $conn->query("SELECT file_path FROM lab_manuals WHERE id='$del_id'");
    if($get_file->num_rows > 0) {
        $file_data = $get_file->fetch_assoc();
        $full_path = "../" . $file_data['file_path'];
        if(file_exists($full_path)) {
            unlink($full_path);
        }
    }

    $conn->query("DELETE FROM lab_manuals WHERE id='$del_id'");
    header("Location: Lab_Manuals.php");
    exit();
}

// ==========================================
// FETCH SEARCH RESULTS
// ==========================================
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql_search = "";
if (!empty($search_query)) {
    $safe_search = $conn->real_escape_string($search_query);
    $sql_search = " WHERE title LIKE '%$safe_search%' OR subject_name LIKE '%$safe_search%' OR department LIKE '%$safe_search%'";
}

$manuals_list = $conn->query("SELECT * FROM lab_manuals $sql_search ORDER BY uploaded_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Manuals - Admin Portal</title>
    
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
        .sidebar-subtitle { font-size: 13px; color: #bfdbfe; margin-top: 5px; font-weight: 500;} 
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #ffffff;}

        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;}
        .page-header { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 30px; border-radius: 16px; margin-bottom: 25px; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.15); display: flex; justify-content: space-between; align-items: center;}
        .icon-box-blue { width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px; }

        .content-box { background: var(--surface); border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 25px;}
        .box-title { font-size: 17px; font-weight: 800; color: var(--text-main); margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center;}

        .form-label { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px; letter-spacing: 0.5px;}
        .form-select, .form-control { border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 600; font-size: 14px; padding: 12px 15px;}
        .form-select:focus, .form-control:focus { border-color: var(--primary); outline: none; background: white; box-shadow: 0 0 0 3px rgba(67, 56, 202, 0.1);}
        
        .search-wrapper { position: relative; width: 100%; max-width: 350px;}
        .search-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-wrapper input { width: 100%; padding: 10px 15px 10px 40px; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-weight: 600; font-size: 14px;}

        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 15px 10px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 15px 10px; border-bottom: 1px solid #f1f5f9; }
        
        .btn-action-view { background: rgba(59, 130, 246, 0.1); color: #2563eb; border: none; padding: 8px 12px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
        .btn-action-view:hover { background: #2563eb; color: white; transform: translateY(-2px); }
        
        .btn-action-delete { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: none; padding: 8px 12px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 6px; }
        .btn-action-delete:hover { background: #dc2626; color: white; transform: translateY(-2px); }

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
            <h3 class="fw-bold m-0">Lab Manuals Repository</h3>
            <div class="d-flex align-items-center gap-3 clock-badge bg-white px-3 py-2 rounded-pill shadow-sm border text-primary fw-bold" style="font-size: 13px;">
                <i class="far fa-clock"></i> <span id="liveClock">Loading time...</span>
            </div>
        </div>

        <div class="page-header">
            <div class="d-flex align-items-center gap-3">
                <div class="icon-box-blue"><i class="fas fa-file-pdf"></i></div>
                <div>
                    <h3 class="fw-bold mb-1">Official Lab Manuals</h3>
                    <p class="mb-0 fw-medium opacity-75">Upload, manage, and set deadlines for department practicals.</p>
                </div>
            </div>
            <div class="ms-auto">
                <a href="dashboard.php" class="btn btn-light fw-bold text-dark px-4 py-2" style="border-radius: 10px;"><i class="fas fa-arrow-left me-2"></i> Back Home</a>
            </div>
        </div>

        <?php echo $msg; ?>

        <!-- TWO COLUMN LAYOUT -->
        <div class="row g-4">
            
            <!-- LEFT COLUMN: UPLOAD FORM -->
            <div class="col-lg-4">
                <div class="content-box h-100">
                    <h5 class="box-title"><i class="fas fa-cloud-upload-alt text-primary me-2"></i> Upload Manual</h5>
                    
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Manual Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. DBMS Practical 1-10" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Branch / Department</label>
                            <select name="department" id="department" class="form-select" required onchange="fetchSubjects()">
                                <option value="" disabled selected>-- Select Branch --</option>
                                <option value="Computer Engineering">Computer Engineering</option>
                                <option value="IT Engineering">IT Engineering</option>
                                <option value="Civil Engineering">Civil Engineering</option>
                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Semester</label>
                            <select name="semester" id="semester" class="form-select" required onchange="fetchSubjects()">
                                <option value="" disabled selected>-- Select Semester --</option>
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                                <option value="3">Semester 3</option>
                                <option value="4">Semester 4</option>
                                <option value="5">Semester 5</option>
                                <option value="6">Semester 6</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject_name" id="subject_name" class="form-select" required>
                                <option value="" disabled selected>-- Select Branch & Sem First --</option>
                            </select>
                        </div>

                        <!-- DEADLINE FIELD ADDED -->
                        <div class="mb-3">
                            <label class="form-label">Submission Deadline <span class="text-muted fw-normal">(Optional)</span></label>
                            <input type="date" name="end_date" class="form-control">
                            <small class="text-muted mt-1 d-block" style="font-size: 11px;">Leave blank if no strict due date.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Upload File (PDF/DOC)</label>
                            <input type="file" name="manual_file" class="form-control p-2" required accept=".pdf, .doc, .docx">
                        </div>

                        <button type="submit" name="upload_manual" class="btn btn-primary fw-bold w-100 py-2" style="border-radius: 8px;">
                            <i class="fas fa-upload me-2"></i> Upload Manual
                        </button>
                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN: MANUALS TABLE -->
            <div class="col-lg-8">
                <div class="content-box h-100 d-flex flex-column p-0">
                    <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-light">
                        <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-folder-open text-primary me-2"></i> Uploaded Manuals</h5>
                        <div class="d-flex align-items-center gap-3">
                            <form method="GET" class="search-wrapper mb-0">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Search manuals..." value="<?php echo htmlspecialchars($search_query); ?>" style="width: 250px; padding: 8px 15px 8px 40px;">
                            </form>
                            <span class="badge bg-primary px-3 py-2 rounded-pill fw-bold" style="font-size: 13px;">Total: <?php echo $manuals_list->num_rows; ?></span>
                        </div>
                    </div>
                    
                    <div class="table-responsive flex-grow-1">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4 py-3">Manual Details</th>
                                    <th class="py-3">Subject & Dept</th>
                                    <th class="py-3">Important Dates</th>
                                    <th class="text-end pe-4 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($manuals_list && $manuals_list->num_rows > 0): ?>
                                    <?php while($row = $manuals_list->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-danger text-white rounded p-2 text-center" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-file-pdf fs-5"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark" style="font-size: 14.5px;"><?php echo htmlspecialchars($row['title']); ?></div>
                                                        <span class="badge bg-light text-muted border mt-1">General</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-primary" style="font-size: 14px;"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                                                <div class="text-muted fw-bold small mt-1">
                                                    <?php echo htmlspecialchars($row['department']); ?> (Sem <?php echo htmlspecialchars($row['semester']); ?>)
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-muted" style="font-size: 13px;">
                                                    <i class="fas fa-upload text-success me-1"></i> <?php echo date('d M Y', strtotime($row['uploaded_at'])); ?>
                                                </div>
                                                <div class="fw-semibold mt-1" style="font-size: 12px;">
                                                    <?php if(!empty($row['end_date']) && $row['end_date'] != '0000-00-00'): ?>
                                                        <span class="text-danger"><i class="fas fa-flag-checkered me-1"></i> Due: <?php echo date('d M Y', strtotime($row['end_date'])); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">No deadline</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-end pe-4">
                                                <a href="../<?php echo $row['file_path']; ?>" target="_blank" class="btn-action-view" title="View PDF">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="?delete_id=<?php echo $row['id']; ?>" class="btn-action-delete" title="Delete Manual" onclick="return confirm('Are you sure you want to delete this manual?');">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="fas fa-box-open mb-3" style="font-size: 40px; opacity: 0.3; color: var(--primary);"></i><br>
                                            <span class="fw-bold text-dark fs-5">No manuals uploaded yet</span><br>
                                            <span class="small opacity-75">Use the form on the left to upload lab manuals.</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Live Clock Script
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const clockEl = document.getElementById('liveClock');
            if(clockEl) clockEl.innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Fetch Subjects AJAX Script
        function fetchSubjects() {
            var branch = document.getElementById('department').value;
            var semester = document.getElementById('semester').value;
            var subjectDropdown = document.getElementById('subject_name');

            if(branch && semester) {
                subjectDropdown.innerHTML = '<option value="">Loading subjects...</option>';
                
                fetch('Lab_Manuals.php?ajax_subjects=1&branch=' + encodeURIComponent(branch) + '&semester=' + encodeURIComponent(semester))
                .then(response => response.text())
                .then(data => {
                    subjectDropdown.innerHTML = data;
                })
                .catch(error => {
                    console.error('Error fetching subjects:', error);
                    subjectDropdown.innerHTML = '<option value="">Error loading subjects</option>';
                });
            } else {
                subjectDropdown.innerHTML = '<option value="" disabled selected>-- Select Branch & Sem First --</option>';
            }
        }
    </script>
</body>
</html>