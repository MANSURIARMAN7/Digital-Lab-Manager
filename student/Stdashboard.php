<?php
session_start();
include '../db.php';

// 1. Student Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_query = $conn->query("SELECT name, email, department FROM users WHERE user_id = '$student_id'");
$student_data = $student_query ? $student_query->fetch_assoc() : null;
$student_name = $student_data['name'] ?? 'Student';
$student_enrollment = $student_data['email'] ?? 'N/A';

// Ensure student_submissions table exists
$conn->query("CREATE TABLE IF NOT EXISTS student_submissions (
    submission_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    manual_id INT NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    practical_no VARCHAR(50) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'Pending',
    marks INT DEFAULT 0,
    feedback TEXT DEFAULT NULL
)");

// ==========================================
// 🚀 HANDLE FILE UPLOAD BY STUDENT
// ==========================================
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_manual'])) {
    $subject_name = $conn->real_escape_string($_POST['subject_name']);
    $practical_no = $conn->real_escape_string($_POST['practical_no']);
    
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
            $sql = "INSERT INTO student_submissions (student_id, manual_id, subject_name, practical_no, file_path, status) 
                    VALUES ('$student_id', 1, '$subject_name', '$practical_no', '$destination', 'Pending')";
            if ($conn->query($sql)) {
                $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>Practical file submitted successfully for review!<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            } else {
                $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Database Error: " . $conn->error . "<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
            }
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Failed to upload file to server folder.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
        }
    } else {
        $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>Please select a valid PDF file to upload.<button type='button' class='btn-close' data-bs-dismiss='alert'></button></div>";
    }
}

// Fetch subjects and admin-uploaded lab manuals
$manuals_list = $conn->query("SELECT * FROM lab_manuals ORDER BY uploaded_at DESC");
$subjects_list = $conn->query("SELECT DISTINCT subject_name FROM subjects UNION SELECT DISTINCT subject_name FROM lab_manuals");

// Fetch student's own submissions
$my_submissions = $conn->query("SELECT * FROM student_submissions WHERE student_id = '$student_id' ORDER BY submitted_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Lab Manual Portal</title>
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
        .profile-pill { display: flex; align-items: center; background-color: #ffffff; padding: 6px 16px 6px 20px; border-radius: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); border: 1px solid #e2e8f0; cursor: pointer; text-decoration: none; color: inherit; }
        .profile-text { text-align: right; margin-right: 15px; }
        .profile-welcome { display: block; font-size: 9.5px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 14px; color: #1e293b; font-weight: 700; }
        .profile-avatar { width: 42px; height: 42px; background-color: var(--accent-blue); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; letter-spacing: 1px;}

        .content-box { background: white; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
        .table-custom th { background: #f8fafc; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; padding: 14px; }
        .table-custom td { vertical-align: middle; font-size: 14px; padding: 14px; color: #334155; border-bottom: 1px solid #f1f5f9; }
        
        .badge-pending { background: rgba(245,158,11,0.1); color: #d97706; border: 1px solid rgba(245,158,11,0.2); padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .badge-approved { background: rgba(16,185,129,0.1); color: #059669; border: 1px solid rgba(16,185,129,0.2); padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .badge-rejected { background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.2); padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo-container">
            <img src="../assets/images/college-logo.png" alt="KDP Logo">
            <div class="sidebar-title"><h2>K.D. Polytechnic</h2></div>
            <div class="sidebar-subtitle">Student Portal</div>
        </div>
        <ul class="nav-links">
            <li class="active"><i class="fas fa-home"></i> Dashboard</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <div class="topbar">
            <div>
                <h3 class="fw-bold text-dark mb-1" style="font-size: 24px;">Welcome, <?php echo htmlspecialchars($student_name); ?>!</h3>
                <p class="text-muted small mb-0">Student Email/ID: <strong><?php echo htmlspecialchars($student_enrollment); ?></strong></p>
            </div>
            
            <a href="#" class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Student Portal</span>
                    <h4 class="profile-name"><?php echo htmlspecialchars($student_name); ?></h4>
                </div>
                <div class="profile-avatar">ST</div>
            </a>
        </div>

        <?php echo $message; ?>

        <!-- UPLOAD SECTION & AVAILABLE MANUALS -->
        <div class="row g-4 mb-4">
            <!-- UPLOAD FORM -->
            <div class="col-md-5">
                <div class="content-box h-100">
                    <h5 class="fw-bold text-dark mb-3" style="font-size: 16px;"><i class="fas fa-upload text-primary me-2"></i> Upload Practical PDF</h5>
                    
                    <form action="Stdashboard.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Select Subject</label>
                            <select name="subject_name" class="form-select" required>
                                <option value="">Choose Subject...</option>
                                <?php if($subjects_list && $subjects_list->num_rows > 0): ?>
                                    <?php while($sub = $subjects_list->fetch_assoc()): ?>
                                        <option value="<?php echo htmlspecialchars($sub['subject_name']); ?>">
                                            <?php echo htmlspecialchars($sub['subject_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Practical Number / Name</label>
                            <input type="text" name="practical_no" class="form-control" required placeholder="e.g. Exp 1 - SQL Queries">
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">Upload PDF File</label>
                            <input type="file" name="manual_file" class="form-control" accept=".pdf" required>
                            <small class="text-muted" style="font-size: 11px;">Only PDF files allowed (Max: 10MB).</small>
                        </div>
                        
                        <button type="submit" name="upload_manual" class="btn btn-primary w-100 fw-bold py-2" style="background: var(--accent-blue); border-radius: 8px;">
                            <i class="fas fa-paper-plane me-1"></i> Submit Practical for Review
                        </button>
                    </form>
                </div>
            </div>

            <!-- AVAILABLE LAB MANUALS BY ADMIN -->
            <div class="col-md-7">
                <div class="content-box h-100">
                    <h5 class="fw-bold text-dark mb-3" style="font-size: 16px;"><i class="fas fa-book-reader text-success me-2"></i> Available Lab Manuals (From Admin)</h5>
                    
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Manual Title & Subject</th>
                                    <th>Sem</th>
                                    <th class="text-end">Download</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($manuals_list && $manuals_list->num_rows > 0): ?>
                                    <?php while($man = $manuals_list->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark" style="font-size: 13.5px;"><?php echo htmlspecialchars($man['title']); ?></div>
                                                <small class="text-primary"><?php echo htmlspecialchars($man['subject_name']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">Sem <?php echo $man['semester']; ?></span>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?php echo htmlspecialchars($man['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius: 6px; padding: 3px 8px;" title="View Manual">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4 small">
                                            <span>No lab manuals uploaded by admin yet.</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- MY SUBMISSIONS TABLE -->
        <div class="content-box">
            <h5 class="fw-bold text-dark mb-3" style="font-size: 16px;"><i class="fas fa-history text-success me-2"></i> My Submissions & Marks Status</h5>
            
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Subject & Practical</th>
                            <th>Submitted Date</th>
                            <th>Marks (Out of 20)</th>
                            <th>Faculty Feedback</th>
                            <th>Status</th>
                            <th class="text-end">File</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($my_submissions && $my_submissions->num_rows > 0): ?>
                            <?php while($row = $my_submissions->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($row['subject_name']); ?></div>
                                        <small class="text-secondary"><?php echo htmlspecialchars($row['practical_no']); ?></small>
                                    </td>
                                    <td>
                                        <div class="text-dark fw-medium" style="font-size: 13px;"><i class="far fa-clock text-muted me-1"></i> <?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?></div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark"><?php echo $row['marks']; ?> / 20</span>
                                    </td>
                                    <td>
                                        <span class="text-muted small"><?php echo !empty($row['feedback']) ? htmlspecialchars($row['feedback']) : 'No feedback yet'; ?></span>
                                    </td>
                                    <td>
                                        <?php if($row['status'] == 'Approved'): ?>
                                            <span class="badge-approved">Approved</span>
                                        <?php elseif($row['status'] == 'Rejected'): ?>
                                            <span class="badge-rejected">Rejected</span>
                                        <?php else: ?>
                                            <span class="badge-pending">Pending Review</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="View PDF" style="border-radius: 6px; padding: 4px 8px;">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-folder-open mb-2" style="font-size: 32px; color: #cbd5e1;"></i><br>
                                    <span>You haven't submitted any practical files yet. Use the upload form above!</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>