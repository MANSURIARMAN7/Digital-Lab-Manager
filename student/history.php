<?php
session_start();
include '../db.php';

// 1. Secure Student Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$enrollment = $conn->real_escape_string((string)$_SESSION['user_id']);

// 2. Fetch Submission History directly from Database
$history_query = $conn->query("SELECT * FROM student_submissions WHERE student_id = '$enrollment' ORDER BY submitted_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity History - K.D. Polytechnic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --sidebar-width: 260px; --bg-color: #f4f7f6; --sidebar-bg: #1b365d; --primary-blue: #3b82f6; }
        body { background-color: var(--bg-color); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; }
        
        /* SIDEBAR */
        .sidebar { width: var(--sidebar-width); background-color: var(--sidebar-bg); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar-header { padding: 30px 20px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); margin-bottom: 15px; }
        .sidebar-header img { width: 80px; height: 80px; object-fit: contain; margin-bottom: 15px; background: white; border-radius: 50%; padding: 5px; }
        .sidebar-title { font-size: 18px; font-weight: 700; margin: 0 0 5px 0; }
        
        .nav-links { list-style: none; padding: 0 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 14px 20px; margin: 5px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 500; color: #cbd5e1; transition: 0.2s; }
        .nav-links li:hover { color: white; background: rgba(255,255,255,0.05); }
        .nav-links li.active { background-color: var(--primary-blue); color: white; box-shadow: 0 4px 10px rgba(59,130,246,0.3); }

        /* MAIN CONTENT */
        .main { flex: 1; overflow-y: auto; padding: 30px 40px; }
        .header-title h2 { font-size: 26px; font-weight: 700; color: #0f172a; margin: 0 0 5px 0; }
        .header-title p { font-size: 14px; color: #64748b; margin: 0 0 30px 0; }

        /* TIMELINE STYLES (PREMIUM LOOK) */
        .timeline { position: relative; max-width: 800px; padding-left: 30px; margin-top: 20px; }
        .timeline::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: #e2e8f0; border-radius: 3px; }
        
        .timeline-item { position: relative; margin-bottom: 30px; padding-left: 30px; }
        .timeline-icon { position: absolute; left: -40px; top: 0; width: 22px; height: 22px; background: white; border: 5px solid var(--primary-blue); border-radius: 50%; box-shadow: 0 0 0 4px rgba(59,130,246,0.1); z-index: 1;}
        
        .timeline-content { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: 0.3s; }
        .timeline-content:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.05); border-color: #cbd5e1;}
        
        .timeline-date { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; display: block; letter-spacing: 0.5px;}
        .timeline-title { font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0; }
        .timeline-desc { font-size: 14px; color: #475569; margin: 0 0 15px 0; line-height: 1.5; }
        
        .badge-status { padding: 6px 14px; border-radius: 20px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;}
        .status-Pending { background: rgba(245,158,11,0.1); color: #d97706; }
        .status-Approved { background: rgba(16,185,129,0.1); color: #059669; }
        .status-Rejected { background: rgba(239,68,68,0.1); color: #dc2626; }
        
        .btn-view { background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 15px; border-radius: 6px; font-size: 12px; font-weight: 600; color: #3b82f6; text-decoration: none; transition: 0.2s;}
        .btn-view:hover { background: #3b82f6; color: white; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/images/college-logo.png" alt="Logo"> 
            <h2 class="sidebar-title">K.D. Polytechnic</h2>
        </div>
        <ul class="nav-links">
            <li onclick="window.location.href='Stdashboard.php'"><i class="fas fa-home" style="width: 20px;"></i> Dashboard</li>
            <li onclick="window.location.href='my-manuals.php'"><i class="fas fa-book" style="width: 20px;"></i> My Submissions</li>
            <li onclick="window.location.href='profile.php'"><i class="fas fa-user-circle" style="width: 20px;"></i> Profile</li>
            <li class="active" onclick="window.location.href='history.php'"><i class="fas fa-history" style="width: 20px;"></i> History</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'" style="color: #fca5a5;"><i class="fas fa-sign-out-alt" style="width: 20px;"></i> Logout</li>
        </ul>
    </div>

    <div class="main">
        <div class="header-title">
            <h2>Activity Log</h2>
            <p>A chronological timeline of your lab manual submissions and faculty reviews.</p>
        </div>

        <div class="timeline">
            <?php if($history_query && $history_query->num_rows > 0): ?>
                <?php while($row = $history_query->fetch_assoc()): ?>
                    <div class="timeline-item">
                        <!-- Timeline Dot -->
                        <div class="timeline-icon 
                            <?php 
                                if($row['status'] == 'Approved') echo 'border-success'; 
                                elseif($row['status'] == 'Rejected') echo 'border-danger'; 
                                else echo 'border-warning'; 
                            ?>">
                        </div>
                        
                        <!-- Content Card -->
                        <div class="timeline-content">
                            <span class="timeline-date"><i class="far fa-clock me-1"></i> <?php echo date('d M Y, h:i A', strtotime($row['submitted_at'])); ?></span>
                            
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                <div>
                                    <h3 class="timeline-title">Submitted <?php echo htmlspecialchars($row['practical_no']); ?></h3>
                                    <p class="timeline-desc">
                                        You successfully uploaded the practical document for <strong><?php echo htmlspecialchars($row['subject_name']); ?></strong>. 
                                    </p>
                                    
                                    <span class="badge-status status-<?php echo htmlspecialchars($row['status']); ?>">
                                        <?php 
                                            if($row['status'] == 'Pending') echo '<i class="fas fa-spinner fa-spin"></i> Pending Review';
                                            elseif($row['status'] == 'Approved') echo '<i class="fas fa-check-circle"></i> Approved';
                                            else echo '<i class="fas fa-times-circle"></i> Needs Revision';
                                        ?>
                                    </span>
                                    
                                    <?php if($row['status'] == 'Approved' && !empty($row['marks'])): ?>
                                        <span class="ms-2 badge bg-dark" style="font-size: 11.5px; padding: 6px 14px; border-radius: 20px;">
                                            <i class="fas fa-star text-warning me-1"></i> Marks: <?php echo htmlspecialchars($row['marks']); ?> / 20
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <a href="<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn-view">
                                    <i class="fas fa-eye me-1"></i> View Upload
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center text-muted py-5" style="background: white; border: 1px dashed #e2e8f0; border-radius: 12px; margin-left: 30px;">
                    <i class="fas fa-history mb-3" style="font-size: 40px; opacity: 0.3;"></i>
                    <h5 class="fw-bold text-dark">No History Found</h5>
                    <p class="mb-0">You haven't submitted any practicals yet. Your timeline will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>