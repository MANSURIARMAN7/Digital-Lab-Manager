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

// Ensure lab_manuals table exists
$conn->query("CREATE TABLE IF NOT EXISTS lab_manuals (
    manual_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    semester INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ==========================================
// 🚀 UPLOAD / ADD NEW LAB MANUAL LOGIC
// ==========================================
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_manual'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $subject_name = $conn->real_escape_string($_POST['subject_name']);
    $semester = (int)$_POST['semester'];
    
    // File upload handling
    if (isset($_FILES['manual_file']) && $_FILES['manual_file']['error'] == 0) {
        $file_name = $_FILES['manual_file']['name'];
        $file_tmp = $_FILES['manual_file']['tmp_name'];
        
        $upload_dir = '../uploads/manuals/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $new_file_name = time() . '_' . preg_replace("/\s+/", "_", $file_name);
        $destination = $upload_dir . $new_file_name;
        
        if (move_uploaded_file($file_tmp, $destination)) {
            $sql = "INSERT INTO lab_manuals (title, subject_name, semester, file_path) 
                    VALUES ('$title', '$subject_name', '$semester', '$destination')";
            if ($conn->query($sql)) {
                $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Lab Manual uploaded successfully!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Database Error: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Failed to move uploaded file to server folder.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } else {
        $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Please select a valid PDF file to upload.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// ==========================================
// 🗑️ DELETE LAB MANUAL LOGIC
// ==========================================
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    // Optional: fetch file path to unlink from folder if needed
    $res = $conn->query("SELECT file_path FROM lab_manuals WHERE manual_id='$del_id'");
    if($res && $row = $res->fetch_assoc()) {
        if(file_exists($row['file_path'])) {
            @unlink($row['file_path']);
        }
    }
    $conn->query("DELETE FROM lab_manuals WHERE manual_id='$del_id'");
    header("Location: Lab_Manuals.php");
    exit();
}

// Fetch Subjects for dropdown
$subjects_list = $conn->query("SELECT DISTINCT subject_name, semester FROM subjects ORDER BY semester ASC, subject_name ASC");

// Fetch Lab Manuals from Database
$manuals_list = $conn->query("SELECT * FROM lab_manuals ORDER BY uploaded_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Manuals Management - Admin Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --sidebar-width: 260px; --bg-color: #f4f7fe; --sidebar-bg: #1a365d; --accent-blue: #2563eb; }
        body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        .sidebar { width: var(--sidebar-width); background-color: var(--sidebar-bg); color: #ffffff; display: flex; flex-direction: column; z-index: 10; overflow-y: auto; }
        .sidebar-logo-container { padding: 30px 20px 20px 20px; display: flex; flex-direction: column; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center; }
        .sidebar-logo-container img { width: 90px; height: 90px; object-fit: contain; margin-bottom: 15px; border-radius: 50%; padding: 5px; background: rgba(255,255,255,0.1); }
        .sidebar-title h2 { font-size: 18px; font-weight: 700; margin: 0; line-height: 1.2; letter-spacing: 0.5px; color: #fff;}
        .sidebar-subtitle { font-size: 13px; color: #94a3b8; margin-top: 5px; font-weight: 500;}
        .nav-links { list-style: none; padding: 20px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 12px 20px; margin: 5px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 500; color: #a0aec0; transition: all 0.3s ease; }
        .nav-links li:hover { color: white; background: rgba(255,255,255,0.08); }
        .nav-links li.active { background: var(--accent-blue); color: white; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4); font-weight: 600; }
        .main { flex: 1; padding: 30px 40px; overflow-y: auto; }
        
        .topbar { background: transparent; padding: 0 0 10px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px;}
        .search-box { background: #fff; border-radius: 8px; padding: 10px 15px; display: flex; align-items: center; gap: 10px; width: 350px; border: 1px solid #e2e8f0; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .search-box input { border: none; background: transparent; outline: none; font-size: 14px; width: 100%; color: #334155; }
        
        .profile-pill { display: flex; align-items: center; background-color: #ffffff; padding: 6px 16px 6px 20px; border-radius: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; cursor: pointer; text-decoration: none; color: inherit; transition: all 0.2s;}
        .profile-text { text-align: right; margin-right: 15px; }
        .profile-welcome { display: block; font-size: 9.5px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 14px; color: #1e293b; font-weight: 700; }
        .profile-avatar { width: 42px; height: 42px; background-color: var(--accent-blue); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; box-shadow: 0 3px 8px rgba(37, 99, 235, 0.4); letter-spacing: 1px;}

        .content-box { background: white; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
        .table-custom th { background: #f8fafc; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; padding: 14px; }
        .table-custom td { vertical-align: middle; font-size: 14px; padding: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        .badge-sem { background: rgba(37,99,235,0.1); color: var(--accent-blue); border: 1px solid rgba(37,99,235,0.2); padding: 4px 10px; border-radius: 6px; font-size: 11.5px; font-weight: 600; }
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
            <li onclick="window.location.href='dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li onclick="window.location.href='Student_Mgmt.php'"><i class="fas fa-user-graduate"></i> Student Mgmt</li>
            <li onclick="window.location.href='faculty_mgmt.php'"><i class="fas fa-chalkboard-teacher"></i> Faculty Mgmt</li>
            <li onclick="window.location.href='subject_mgmt.php'"><i class="fas fa-book"></i> Subject Mgmt</li>
            <li class="active" onclick="window.location.href='Lab_Manuals.php'"><i class="fas fa-file-alt"></i> Lab Manuals</li>
            <li onclick="window.location.href='Submissions.php'"><i class="fas fa-folder-open"></i> Submissions</li>
            <li onclick="window.location.href='Review & Marks.php'"><i class="fas fa-check-circle"></i> Review & Marks</li>
            <li onclick="window.location.href='Reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- TOPBAR -->
        <div class="topbar mb-3">
            <div class="search-box">
                <i class="fas fa-search text-muted"></i>
                <input type="text" placeholder="Search lab manuals...">
            </div>
            
            <div class="d-flex align-items-center gap-4">
                <div class="position-relative" style="cursor: pointer; padding: 8px; background: white; border-radius: 8px; border: 1px solid #e2e8f0;" onclick="window.location.href='Submissions.php'">
                    <i class="far fa-bell text-secondary fs-5"></i>
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
                    <div class="profile-avatar">HOD</div>
                </a>
            </div>
        </div>

        <?php echo $message; ?>

        <!-- PAGE HEADER -->
        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1" style="font-size: 24px;">Lab Manuals Management</h3>
            <p class="text-muted small mb-0">Upload and manage official practical lab manuals for students and faculty.</p>
        </div>

        <!-- TWO COLUMN LAYOUT -->
        <div class="row g-4">
            <!-- LEFT COLUMN: UPLOAD MANUAL FORM -->
            <div class="col-md-4">
                <div class="content-box">
                    <h5 class="fw-bold text-dark mb-3" style="font-size: 16px;"><i class="fas fa-upload text-primary me-2"></i> Upload Lab Manual</h5>
                    
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Manual Title / Practical Name</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. Database Lab Manual - Sem 1">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Select Subject</label>
                            <select name="subject_name" class="form-select" required>
                                <option value="">Choose Subject...</option>
                                <?php if($subjects_list && $subjects_list->num_rows > 0): ?>
                                    <?php while($sub = $subjects_list->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($sub['subject_name']); ?>">
                                            <?php echo htmlspecialchars($sub['subject_name']); ?> (Sem <?php echo $sub['semester']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <option value="Mathematics-I">Mathematics-I</option>
                                    <option value="Physics">Physics</option>
                                    <option value="Computer Programming Fundamentals (CPF)">Computer Programming Fundamentals (CPF)</option>
                                    <option value="Basics of Electronics (BOE)">Basics of Electronics (BOE)</option>
                                    <option value="Computer Systems & Environment (CSE)">Computer Systems & Environment (CSE)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Semester</label>
                            <select name="semester" class="form-select" required>
                                <option value="1" selected>Semester 1</option>
                                <option value="2">Semester 2</option>
                                <option value="3">Semester 3</option>
                                <option value="4">Semester 4</option>
                                <option value="5">Semester 5</option>
                                <option value="6">Semester 6</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">Upload PDF File</label>
                            <input type="file" name="manual_file" class="form-control" accept=".pdf" required>
                            <small class="text-muted" style="font-size: 11px;">Only PDF files allowed (Max 10MB).</small>
                        </div>
                        
                        <button type="submit" name="add_manual" class="btn btn-primary w-100 fw-bold py-2" style="background: var(--accent-blue); border-radius: 8px;">
                            <i class="fas fa-cloud-upload-alt me-1"></i> Upload Manual
                        </button>
                    </form>
                </div>
            </div>

            <!-- RIGHT COLUMN: LAB MANUALS TABLE -->
            <div class="col-md-8">
                <div class="content-box">
                    <h5 class="fw-bold text-dark mb-3" style="font-size: 16px;"><i class="fas fa-file-pdf text-success me-2"></i> Published Lab Manuals</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Manual Title & Subject</th>
                                    <th>Semester</th>
                                    <th>Uploaded Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($manuals_list && $manuals_list->num_rows > 0): ?>
                                    <?php while($row = $manuals_list->fetch_assoc()): 
                                        $man_id = $row['manual_id'] ?? ($row['id'] ?? 0);
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['title'] ?? ''); ?></div>
                                                <small class="text-primary"><?php echo htmlspecialchars($row['subject_name'] ?? ''); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge-sem">Sem <?php echo htmlspecialchars($row['semester'] ?? '1'); ?></span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><i class="far fa-clock me-1"></i> <?php echo date('d M Y', strtotime($row['uploaded_at'])); ?></small>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-outline-primary btn-sm me-1" style="border-radius: 6px; padding: 4px 8px;" title="View PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                                <a href="Lab_Manuals.php?delete_id=<?php echo $man_id; ?>" 
                                                   class="btn btn-outline-danger btn-sm" 
                                                   style="border-radius: 6px; padding: 4px 8px;"
                                                   onclick="return confirm('Delete this lab manual?');" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-5">
                                            <i class="fas fa-folder-open mb-2" style="font-size: 32px; color: #cbd5e1;"></i><br>
                                            <span>No lab manuals uploaded yet. Use the upload form on the left.</span>
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
</body>
</html>