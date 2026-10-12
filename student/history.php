<?php
session_start();
include '../db.php';

// 1. Secure Student Login Check
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $conn->real_escape_string((string)$_SESSION['user_id']);
$student_name = $_SESSION['name'] ?? 'Student';

// Generate Initials
$name_parts = explode(' ', trim($student_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

// 📊 FETCH STATS FOR STUDENT
$t_total = 0; $t_pending = 0; $t_approved = 0; $t_rejected = 0;

$stat_q = $conn->query("SELECT status FROM student_submissions WHERE student_id = '$student_id'");
if($stat_q) {
    while($st = $stat_q->fetch_assoc()){
        $t_total++;
        if($st['status'] == 'Pending') $t_pending++;
        if($st['status'] == 'Approved') $t_approved++;
        if($st['status'] == 'Rejected') $t_rejected++;
    }
}

// 📋 FETCH SUBMISSION HISTORY
$history_data = [];
$q = "SELECT * FROM student_submissions WHERE student_id = '$student_id' ORDER BY submitted_at DESC";
$res = $conn->query($q);
if ($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        $history_data[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - KDP Student</title>
    
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
            --shadow-float: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            --transition-bounce: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; overflow: hidden; margin: 0; color: var(--text-main); }
        
        /* 🔥 SIDEBAR */
        .sidebar { width: var(--sidebar-width); background: linear-gradient(195deg, #1e3a8a 0%, #4338ca 100%); color: #ffffff; display: flex; flex-direction: column; z-index: 10; box-shadow: 4px 0 24px rgba(0,0,0,0.08); }
        .sidebar-logo-container { padding: 35px 20px 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .sidebar-logo-container img { width: 85px; height: 85px; margin-bottom: 15px; border-radius: 50%; padding: 4px; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); }
        .sidebar-title h2 { font-size: 19px; font-weight: 800; margin: 0;}
        .sidebar-subtitle { font-size: 12px; color: #bfdbfe; margin-top: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;}
        
        .nav-links { list-style: none; padding: 25px 15px; margin: 0; flex-grow: 1; }
        .nav-links li { padding: 13px 20px; margin: 8px 0; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 15px; font-size: 14.5px; font-weight: 600; color: #dbeafe; transition: var(--transition-bounce); }
        .nav-links li:hover { color: #ffffff; background: rgba(255,255,255,0.1); transform: translateX(5px); }
        .nav-links li.active { background: rgba(255, 255, 255, 0.2); color: #ffffff; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid white;}
        .nav-links li i { font-size: 18px; }
        .nav-links li.mt-auto { color: #fca5a5 !important; }

        /* MAIN CONTENT */
        .main { flex: 1; padding: 30px 45px; overflow-y: auto; height: 100vh; animation: fadeUp 0.8s forwards; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }

        /* 🌐 TOPBAR */
        .topbar { padding: 0 0 15px 0; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;}
        .clock-badge { background: var(--surface); border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 18px; color: #475569; font-weight: 700; font-size: 13px; box-shadow: var(--shadow-float); }
        
        .profile-pill { display: flex; align-items: center; background-color: var(--surface); padding: 8px 18px 8px 24px; border-radius: 50px; border: 1px solid rgba(226, 232, 240, 0.8); cursor: pointer; text-decoration: none; color: inherit; transition: var(--transition-bounce); box-shadow: var(--shadow-float); }
        .profile-pill:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 15px 25px -5px rgba(0,0,0,0.1); border-color: #cbd5e1;}
        .profile-text { text-align: right; margin-right: 18px; }
        .profile-welcome { display: block; font-size: 10px; color: var(--primary); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .profile-name { margin: 0; font-size: 15px; color: var(--text-main); font-weight: 800; }
        .profile-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);}

        /* 📊 MINI STATS RIBBON */
        .stats-ribbon { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap;}
        .stat-badge { flex: 1; min-width: 150px; background: white; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);}
        .stat-badge .icon-box { width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-badge .info h4 { margin: 0; font-size: 28px; font-weight: 900; line-height: 1; color: var(--text-main);}
        .stat-badge .info span { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 5px; display: block;}

        /* TABLE */
        .content-box { background: var(--surface); border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); overflow: hidden;}
        .table-custom th { font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); border-bottom: 2px solid #e2e8f0; padding: 18px 15px; background: #f8fafc;}
        .table-custom td { vertical-align: middle; font-size: 14px; font-weight: 600; padding: 18px 15px; color: var(--text-main); border-bottom: 1px solid #f1f5f9; }
        .table-custom tbody tr:hover td { background-color: #f8fafc; }
        
        .badge-modern { padding: 6px 14px; border-radius: 8px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-Pending { background: #fef3c7; color: #d97706; border: 1px solid #fde68a;}
        .status-Approved { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0;}
        .status-Rejected { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;}

        .btn-score { background: rgba(16, 185, 129, 0.1); color: #059669; border: 1px solid rgba(16, 185, 129, 0.2); padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 800; transition: var(--transition-bounce); display: inline-flex; align-items: center; gap: 6px;}
        .btn-score:hover { background: #059669; color: white; transform: translateY(-2px); }
        .btn-redo { background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.2); padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 800; transition: var(--transition-bounce); display: inline-flex; align-items: center; gap: 6px; text-decoration: none;}
        .btn-redo:hover { background: #dc2626; color: white; transform: translateY(-2px); }

        /* 🌟 MODALS STYLING */
        .rubric-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); z-index: 9999; align-items: center; justify-content: center; }
        .rubric-modal-overlay.active { display: flex; }
        .rubric-modal { background: white; border-radius: 20px; width: 100%; max-width: 450px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2);}
        .modal-header-custom { padding: 20px 25px; color: white; display: flex; justify-content: space-between; align-items: center;}
        .btn-close-custom { background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s;}
        .btn-close-custom:hover { background: rgba(255,255,255,0.4); }

        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
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
            <li onclick="window.location.href='Stdashboard.php'"><i class="fas fa-border-all"></i> Dashboard</li>
            <li class="active" onclick="window.location.href='history.php'"><i class="fas fa-history"></i> My Submissions</li>
            <li class="mt-auto" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main">
        
        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex align-items-center gap-3">
                <div class="clock-badge"><i class="far fa-clock text-primary me-2"></i><span id="liveClock">Loading time...</span></div>
            </div>
            
            <div class="profile-pill">
                <div class="profile-text">
                    <span class="profile-welcome">Student</span>
                    <h4 class="profile-name"><?php echo htmlspecialchars($student_name); ?></h4>
                </div>
                <div class="profile-avatar"><?php echo $initials; ?></div>
            </div>
        </div>

        <div class="mb-4">
            <h3 class="fw-bold text-dark mb-1" style="font-size: 26px;">Submission History</h3>
            <p class="text-muted small fw-semibold mb-0">Track your uploaded lab manuals and view faculty feedback.</p>
        </div>

        <!-- 📊 QUICK STATS RIBBON -->
        <div class="stats-ribbon">
            <div class="stat-badge">
                <div class="icon-box" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-folder-open"></i></div>
                <div class="info"><span>Total Uploads</span><h4><?php echo $t_total; ?></h4></div>
            </div>
            <div class="stat-badge">
                <div class="icon-box" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;"><i class="fas fa-clock"></i></div>
                <div class="info"><span>Pending Review</span><h4><?php echo $t_pending; ?></h4></div>
            </div>
            <div class="stat-badge">
                <div class="icon-box" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-check-circle"></i></div>
                <div class="info"><span>Approved</span><h4 class="text-success"><?php echo $t_approved; ?></h4></div>
            </div>
            <div class="stat-badge">
                <div class="icon-box" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="info"><span>Needs Redo</span><h4 class="text-danger"><?php echo $t_rejected; ?></h4></div>
            </div>
        </div>

        <!-- 📋 SUBMISSIONS TABLE -->
        <div class="content-box p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Subject</th>
                            <th style="width: 15%;">Practical No.</th>
                            <th style="width: 20%;">Date Submitted</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 15%;">Total Marks</th>
                            <th style="width: 10%;" class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history_data)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i><br>
                                    <h5 class="fw-bold text-dark mb-1">No History Found</h5>
                                    <p class="small mb-0">You haven't submitted any practicals yet.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($history_data as $row): ?>
                            <tr>
                                <td><div class="fw-bold text-dark"><?php echo htmlspecialchars($row['subject_name']); ?></div></td>
                                <td>
                                    <span class="bg-light border px-2 py-1 rounded fw-bold text-dark" style="font-size:12px;">
                                        <i class="fas fa-file-code text-muted me-1"></i> <?php echo htmlspecialchars($row['practical_no']); ?>
                                    </span>
                                </td>
                                <td><span class="text-muted fw-bold small"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d M Y', strtotime($row['submitted_at'])); ?></span></td>
                                <td>
                                    <span class="badge-modern status-<?php echo htmlspecialchars($row['status']); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="<?php echo ($row['status'] == 'Approved') ? 'text-success' : (($row['status'] == 'Rejected') ? 'text-danger' : 'text-muted'); ?>" style="font-size:16px;">
                                        <?php echo ($row['status'] == 'Pending') ? '--' : $row['marks'] . ' <small class="text-muted fw-normal" style="font-size: 12px;">/ 20</small>'; ?>
                                    </strong>
                                </td>
                                <td class="text-end pe-4">
                                    <?php 
                                        $rubric_data = json_encode([
                                            'reg' => $row['mark_reg'], 'und' => $row['mark_und'],
                                            'obs' => $row['mark_obs'], 'viva' => $row['mark_viva'],
                                            'total' => $row['marks'], 'feedback' => $row['feedback'],
                                            'prac' => $row['practical_no'], 'sub' => $row['subject_name'],
                                            'status' => $row['status']
                                        ]);
                                    ?>
                                    
                                    <?php if($row['status'] == 'Pending'): ?>
                                        <span class="text-muted small fw-bold"><i class="fas fa-hourglass-half"></i> Awaiting</span>
                                    <?php elseif($row['status'] == 'Approved'): ?>
                                        <button type="button" class="btn-score shadow-sm" onclick='viewScorecard(<?php echo htmlspecialchars($rubric_data, ENT_QUOTES, 'UTF-8'); ?>)'>
                                            <i class="fas fa-award"></i> View Score
                                        </button>
                                    <?php elseif($row['status'] == 'Rejected'): ?>
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn-action shadow-sm" style="color:#475569; background:#f1f5f9; border:1px solid #cbd5e1;" title="View Reason" onclick='viewScorecard(<?php echo htmlspecialchars($rubric_data, ENT_QUOTES, 'UTF-8'); ?>)'>
                                                <i class="fas fa-comment-dots"></i>
                                            </button>
                                            <a href="upload_manual.php?subject=<?php echo urlencode($row['subject_name']); ?>&prac=<?php echo urlencode($row['practical_no']); ?>" class="btn-redo shadow-sm">
                                                <i class="fas fa-redo"></i> Re-Upload
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 🌟 MODAL: SCORECARD PREVIEW -->
    <div id="scoreModalOverlay" class="rubric-modal-overlay">
        <div class="rubric-modal">
            <div class="modal-header-custom" id="score_header_bg" style="background: linear-gradient(135deg, #059669, #10b981);">
                <h5 class="mb-0" id="score_title"><i class="fas fa-award me-2"></i> Result Scorecard</h5>
                <button type="button" class="btn-close-custom" onclick="closeModal('scoreModalOverlay')"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 bg-white">
                <div class="text-center mb-4">
                    <h5 id="score_subject_name" class="fw-bold text-dark mb-1">Subject Name</h5>
                    <p id="score_prac_no" class="text-muted small fw-bold mb-0">Practical Details</p>
                </div>
                
                <div class="bg-light border rounded-3 p-3 mb-4">
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><span class="text-muted fw-bold">Regularity</span><span id="score_reg" class="text-primary fw-bold"></span></div>
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><span class="text-muted fw-bold">Understanding</span><span id="score_und" class="text-primary fw-bold"></span></div>
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2"><span class="text-muted fw-bold">Observation</span><span id="score_obs" class="text-primary fw-bold"></span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted fw-bold">Viva / Quiz</span><span id="score_viva" class="text-primary fw-bold"></span></div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center bg-dark text-white p-3 rounded-3 mb-3 shadow-sm">
                    <span class="text-uppercase fw-bold" style="letter-spacing: 1px;">Total Marks Received</span>
                    <h4 id="score_total" class="mb-0 fw-bold"></h4>
                </div>
                
                <div class="p-3 bg-light border-start border-4 rounded" id="score_remark_box">
                    <span class="text-muted small fw-bold d-block mb-1">Faculty Remark / Reason:</span>
                    <div id="score_remark" class="fst-italic text-dark"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('liveClock').innerText = now.toLocaleDateString('en-IN', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        function viewScorecard(marksData) {
            document.getElementById('score_subject_name').innerText = marksData.sub;
            document.getElementById('score_prac_no').innerText = "Practical: " + marksData.prac;
            document.getElementById('score_reg').innerText = marksData.reg + " / 5";
            document.getElementById('score_und').innerText = marksData.und + " / 5";
            document.getElementById('score_obs').innerText = marksData.obs + " / 5";
            document.getElementById('score_viva').innerText = marksData.viva + " / 5";
            
            let totalBox = document.getElementById('score_total');
            let headerBg = document.getElementById('score_header_bg');
            let remarkBox = document.getElementById('score_remark_box');
            let title = document.getElementById('score_title');
            
            totalBox.innerText = marksData.total + " / 20";
            
            if (marksData.status === 'Rejected') {
                headerBg.style.background = 'linear-gradient(135deg, #dc2626, #ef4444)';
                title.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i> Revision Required';
                totalBox.classList.remove('text-warning', 'text-success');
                totalBox.classList.add('text-danger');
                remarkBox.classList.add('border-danger');
                remarkBox.classList.remove('border-success');
            } else {
                headerBg.style.background = 'linear-gradient(135deg, #059669, #10b981)';
                title.innerHTML = '<i class="fas fa-award me-2"></i> Final Scorecard';
                totalBox.classList.remove('text-danger');
                totalBox.classList.add('text-warning');
                remarkBox.classList.add('border-success');
                remarkBox.classList.remove('border-danger');
            }

            let remarkText = document.getElementById('score_remark');
            if(marksData.feedback && marksData.feedback.trim() !== '') {
                remarkText.innerText = '"' + marksData.feedback + '"';
            } else {
                remarkText.innerHTML = '<span class="text-muted">No specific remarks provided.</span>';
            }

            document.getElementById('scoreModalOverlay').classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }
    </script>
</body>
</html>