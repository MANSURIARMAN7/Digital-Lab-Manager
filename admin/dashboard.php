<?php
include 'header.php';
?>

<h4 class="fw-bold text-dark mb-4">
    Digital Lab Manager Dashboard
</h4>

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
                    <span class="text-danger">Rejected Submissions</span>
                    <h3 class="fw-bold text-dark mb-0 mt-1">49</h3>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
        </div>
    </div>

</div>

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
                            <td>Pathan Rehan Khan (CE)</td>
                            <td>DS Lab</td>
                            <td>Today, 10:30 AM</td>
                            <td>
                                <span class="status-badge badge-pending">Pending</span>
                            </td>
                        </tr>
                        <tr>
                            <td>Belim Hamza (CE)</td>
                            <td>RDBMS Lab</td>
                            <td>Yesterday</td>
                            <td>
                                <span class="status-badge badge-approved">Approved</span>
                            </td>
                        </tr>
                        <tr>
                            <td>Sheikh Sohan (CE)</td>
                            <td>IML Lab</td>
                            <td>2 Days ago</td>
                            <td>
                                <span class="status-badge badge-rejected">Rejected</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    // Initialize Doughnut Chart
    document.addEventListener('DOMContentLoaded', function() {
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
                plugins: {
                    legend: {
                        display: false
                    }
                },
                cutout: '70%'
            }
        });
    });
</script>

<?php
include 'footer.php';
?>
