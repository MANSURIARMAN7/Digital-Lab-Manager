<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}

$faculty_subjects = isset($_SESSION['subjects']) ? $_SESSION['subjects'] : [];
$faculty_sub_names = [];
foreach ($faculty_subjects as $sub) {
    $faculty_sub_names[] = is_array($sub) ? $sub['name'] : $sub;
}

$json_file = 'submissions.json'; 
if (!file_exists($json_file)) { file_put_contents($json_file, json_encode([], JSON_PRETTY_PRINT)); }
$all_submissions = json_decode(file_get_contents($json_file), true);
if (!is_array($all_submissions)) { $all_submissions = []; }

$overall_stats = ['total' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0];
$subject_stats = [];

foreach ($faculty_sub_names as $sub) {
    $subject_stats[$sub] = ['total' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0];
}

foreach ($all_submissions as $row) {
    if (isset($row['subject']) && in_array($row['subject'], $faculty_sub_names)) {
        $status = strtolower($row['status']);
        $sub_name = $row['subject'];

        $overall_stats['total']++;
        if (isset($overall_stats[$status])) $overall_stats[$status]++;

        $subject_stats[$sub_name]['total']++;
        if (isset($subject_stats[$sub_name][$status])) $subject_stats[$sub_name][$status]++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - KDP Faculty</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/faculty_dashboard.css?v=602">
    <!-- Chart.js link -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Modern Report Design CSS */
        .report-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px dashed #e2e8f0; }
        
        .btn-print { background: #0f172a; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.3s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .btn-print:hover { background: #334155; transform: translateY(-1px); }
        
        .progress-bar-container { width: 100%; background: #f1f5f9; border-radius: 10px; height: 8px; margin-top: 8px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05); }
        .progress-bar { height: 100%; background: linear-gradient(90deg, #10b981, #34d399); border-radius: 10px; }
        
        .charts-container { display: flex; gap: 20px; margin-top: 25px; margin-bottom: 30px; }
        
        /* 🔥 FIX: Graphs ko limit mein rakhne ka design */
        .chart-box { background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); flex: 1; border: 1px solid #f1f5f9; display: flex; flex-direction: column; }
        .canvas-wrapper { position: relative; height: 260px; width: 100%; margin-top: 15px; } /* Ye Line Graph ko bada hone se rokegi */

        /* Enhanced Table Design */
        .table-section { background: white; padding: 25px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); border: 1px solid #f1f5f9; margin-bottom: 30px;}
        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        th { background: #f8fafc; padding: 15px; text-align: left; color: #475569; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        @media print {
            .sidebar, .faculty-profile, .btn-print, .report-header p { display: none !important; }
            .main { margin-left: 0 !important; width: 100% !important; padding: 0 !important; background: white !important; }
            body { background: white; }
            .card, .table-section, .chart-box { box-shadow: none !important; border: 1px solid #ddd !important; break-inside: avoid; }
            .charts-container { display: block; }
            .chart-box { margin-bottom: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- SIDEBAR -->
        <div class="sidebar" style="display: flex; flex-direction: column; height: 100vh;">
            <div class="sidebar-logo-container">
                <img src="../assets/images/KDP-Logo.png" alt="Logo" class="sidebar-logo">
                <div class="sidebar-title">
                    <h2>K.D. Polytechnic</h2>
                    <p>Faculty Portal</p>
                </div>
            </div>
            <div class="sidebar-divider"></div>
            <ul style="list-style: none; padding: 0; display: flex; flex-direction: column; flex-grow: 1;">
                <li onclick="window.location.href='faculty_dashboard.php'" style="cursor: pointer;"><span class="menu-icon">🏠</span> Dashboard</li>
                <li onclick="window.location.href='labmanual_list.php'" style="cursor: pointer;"><span class="menu-icon">📘</span> Lab Manuals</li>
                <li onclick="window.location.href='reports.php'" class="active" style="cursor: pointer;"><span class="menu-icon">📄</span> Reports</li>
                <li onclick="window.location.href='../logout.php'" style="cursor: pointer; margin-top: auto; color: #ff8ba7;"><span class="menu-icon" style="color: #ff8ba7;">➔</span> Logout</li>
            </ul>
        </div>

        <div class="main">
            <div class="report-header">
                <div>
                    <h2 style="font-size: 24px; color: #0f172a; margin-bottom: 5px;">Analytics & Reports</h2>
                    <p style="color: #64748b; font-size: 14px; margin: 0;">Subject-wise performance and visual submission records.</p>
                </div>
                <div style="display: flex; gap: 20px; align-items: center;">
                    <button class="btn-print" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                    <div class="faculty-profile">
                        <img src="https://ui-avatars.com/api/?name=Faculty&background=2563eb&color=fff" alt="Profile" class="profile-pic" style="border-radius: 50%; width: 45px; height: 45px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    </div>
                </div>
            </div>

            <!-- CARDS (Cleaner Design) -->
            <div class="cards" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <div class="card" style="border:none; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-radius: 12px; background: #fff; padding: 20px; position:relative; overflow:hidden;">
                    <div style="position:relative; z-index:1;">
                        <h3 style="color:#64748b; font-size:14px; margin:0 0 10px 0;">Total Submissions</h3>
                        <p style="font-size:28px; font-weight:700; color:#0f172a; margin:0;"><?php echo $overall_stats['total']; ?></p>
                    </div>
                    <i class="fas fa-file-alt" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); font-size:40px; color:#f1f5f9; z-index:0;"></i>
                </div>
                <div class="card" style="border:none; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-radius: 12px; background: #fff; padding: 20px; position:relative; overflow:hidden; border-bottom: 4px solid #10b981;">
                    <div style="position:relative; z-index:1;">
                        <h3 style="color:#64748b; font-size:14px; margin:0 0 10px 0;">Approved</h3>
                        <p style="font-size:28px; font-weight:700; color:#0f172a; margin:0;"><?php echo $overall_stats['approved']; ?></p>
                    </div>
                    <i class="fas fa-check-circle" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); font-size:40px; color:rgba(16, 185, 129, 0.1); z-index:0;"></i>
                </div>
                <div class="card" style="border:none; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-radius: 12px; background: #fff; padding: 20px; position:relative; overflow:hidden; border-bottom: 4px solid #f59e0b;">
                    <div style="position:relative; z-index:1;">
                        <h3 style="color:#64748b; font-size:14px; margin:0 0 10px 0;">Pending</h3>
                        <p style="font-size:28px; font-weight:700; color:#0f172a; margin:0;"><?php echo $overall_stats['pending']; ?></p>
                    </div>
                    <i class="fas fa-clock" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); font-size:40px; color:rgba(245, 158, 11, 0.1); z-index:0;"></i>
                </div>
                <div class="card" style="border:none; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-radius: 12px; background: #fff; padding: 20px; position:relative; overflow:hidden; border-bottom: 4px solid #ef4444;">
                    <div style="position:relative; z-index:1;">
                        <h3 style="color:#64748b; font-size:14px; margin:0 0 10px 0;">Rejected</h3>
                        <p style="font-size:28px; font-weight:700; color:#0f172a; margin:0;"><?php echo $overall_stats['rejected']; ?></p>
                    </div>
                    <i class="fas fa-times-circle" style="position:absolute; right:15px; top:50%; transform:translateY(-50%); font-size:40px; color:rgba(239, 68, 68, 0.1); z-index:0;"></i>
                </div>
            </div>

            <!-- GRAPHICAL CHARTS USING CHART.JS -->
            <div class="charts-container">
                <div class="chart-box" style="flex: 0.35;">
                    <h3 style="color: #0f172a; font-size: 16px; margin: 0; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">Overall Status</h3>
                    <!-- FIX: Ye canvas wrapper class hi magic kar rahi hai -->
                    <div class="canvas-wrapper">
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
                <div class="chart-box" style="flex: 0.65;">
                    <h3 style="color: #0f172a; font-size: 16px; margin: 0; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">Subject-wise Approval</h3>
                    <div class="canvas-wrapper">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="table-section">
                <h3 style="color: #0f172a; font-size: 18px; margin: 0 0 20px 0;">📚 Subject-wise Breakdown</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Subject Name</th>
                            <th>Total Students</th>
                            <th>Approved</th>
                            <th>Pending</th>
                            <th>Rejected</th>
                            <th>Approval Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $labels = []; $approvedData = []; $pendingData = [];
                        foreach ($subject_stats as $sub_name => $stat) { 
                            $rate = ($stat['total'] > 0) ? round(($stat['approved'] / $stat['total']) * 100) : 0;
                            $labels[] = $sub_name;
                            $approvedData[] = $stat['approved'];
                            $pendingData[] = $stat['pending'];
                            ?>
                            <tr>
                                <td style="font-weight: 600; color: #2563eb;"><?php echo $sub_name; ?></td>
                                <td style="font-weight: 500;"><?php echo $stat['total']; ?></td>
                                <td style="color: #10b981; font-weight: 600;"><?php echo $stat['approved']; ?></td>
                                <td style="color: #f59e0b; font-weight: 600;"><?php echo $stat['pending']; ?></td>
                                <td style="color: #ef4444; font-weight: 600;"><?php echo $stat['rejected']; ?></td>
                                <td style="width: 150px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; font-size: 12px; font-weight: 600; color: #475569;">
                                        <span>Progress</span>
                                        <span><?php echo $rate; ?>%</span>
                                    </div>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar" style="width: <?php echo $rate; ?>%;"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            
            <div style="display: none; text-align: center; margin-top: 50px; color: #64748b; font-size: 12px; border-top: 1px dashed #ccc; padding-top: 20px;" id="printFooter">
                <strong>K.D. Polytechnic</strong><br>
                Report generated from Digital Lab Manual System by <?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Faculty'; ?> on <?php echo date("d-M-Y h:i A"); ?>.
            </div>

            <script>
                window.onbeforeprint = function() { document.getElementById('printFooter').style.display = 'block'; };
                window.onafterprint = function() { document.getElementById('printFooter').style.display = 'none'; };

                // PHP Data to JS
                const pieData = [<?php echo $overall_stats['approved']; ?>, <?php echo $overall_stats['pending']; ?>, <?php echo $overall_stats['rejected']; ?>];
                const barLabels = <?php echo json_encode($labels); ?>;
                const barApproved = <?php echo json_encode($approvedData); ?>;
                const barPending = <?php echo json_encode($pendingData); ?>;

                // Configure Font Family
                Chart.defaults.font.family = "'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
                Chart.defaults.color = '#64748b';

                // 1. Pie Chart Configuration
                new Chart(document.getElementById('pieChart'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Approved', 'Pending', 'Rejected'],
                        datasets: [{
                            data: pieData,
                            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                            hoverOffset: 5,
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false, 
                        cutout: '75%',
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } }
                        }
                    }
                });

                // 2. Bar Chart Configuration
                new Chart(document.getElementById('barChart'), {
                    type: 'bar',
                    data: {
                        labels: barLabels,
                        datasets: [
                            { label: 'Approved', data: barApproved, backgroundColor: '#10b981', borderRadius: 6, barThickness: 25 },
                            { label: 'Pending', data: barPending, backgroundColor: '#f59e0b', borderRadius: 6, barThickness: 25 }
                        ]
                    },
                    options: { 
                        responsive: true, 
                        maintainAspectRatio: false, 
                        scales: { 
                            y: { beginAtZero: true, grid: { color: '#f1f5f9', drawBorder: false } },
                            x: { grid: { display: false, drawBorder: false } }
                        },
                        plugins: {
                            legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true } }
                        }
                    }
                });
            </script>
        </div>
    </div>
</body>
</html>