<?php
include 'header.php';
?>

<style>
    /* Dashboard specific CSS */
    .stat-card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); height: 100%; }
    .content-card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); height: 100%; }
    .stat-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .chart-container { position: relative; height: 250px; width: 100%; display: flex; justify-content: center; align-items: center;}
    .chart-center-text { position: absolute; text-align: center; pointer-events: none; }
    .chart-center-text .number { font-size: 28px; font-weight: bold; color: #374151; }
    
    /* Naye bado buttons ke liye hover effect */
    .clickable-card { cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
    .clickable-card:hover { transform: translateY(-3px); box-shadow: 0 6px 15px rgba(0,0,0,0.1); }
</style>

<!-- Chart.js link (Zaruri hai taaki doughnut chart dikhe) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid mt-4">
    <h4 class="fw-bold text-dark mb-4">
        Digital Lab Manager Dashboard
    </h4>

    <!-- Upar Wale 4 Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card" style="border-left: 4px solid #3b82f6;">
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
            <div class="stat-card" style="border-left: 4px solid #10b981;">
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
            <div class="stat-card" style="border-left: 4px solid #f59e0b;">
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
            <div class="stat-card" style="border-left: 4px solid #ef4444;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small">Rejected Submissions</span>
                        <h3 class="fw-bold text-dark mb-0 mt-1">49</h3>
                    </div>
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Niche wala hissa (Chart aur Naye Bade Buttons) -->
    <div class="row g-4">
        <!-- Chart -->
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

        <!-- Student Management ke Bade Buttons -->
        <div class="col-lg-7">
            <div class="content-card">
                <h5 class="fw-bold text-dark mb-4">Manage Students by Year</h5>
                
                <div class="row g-3">
                    <!-- 1st Year Card -->
                    <div class="col-12">
                        <div class="stat-card clickable-card" style="border-left: 4px solid #3b82f6;" onclick="openYearPopup('1st Year')">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small">Manage Classes A & B</span>
                                    <h4 class="fw-bold text-dark mb-0 mt-1">1st Year Students</h4>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2nd Year Card -->
                    <div class="col-12">
                        <div class="stat-card clickable-card" style="border-left: 4px solid #10b981;" onclick="openYearPopup('2nd Year')">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small">Manage Classes A & B</span>
                                    <h4 class="fw-bold text-dark mb-0 mt-1">2nd Year Students</h4>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10 text-success">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3rd Year Card -->
                    <div class="col-12">
                        <div class="stat-card clickable-card" style="border-left: 4px solid #f59e0b;" onclick="openYearPopup('3rd Year')">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted small">Manage Classes A & B</span>
                                    <h4 class="fw-bold text-dark mb-0 mt-1">3rd Year Students</h4>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Year Selection Modal (Popup) -->
<div class="modal fade" id="studentYearModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-primary" id="studentYearModalLabel">Year</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalDynamicContent">
                <!-- Content will be loaded here via JS -->
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Doughnut Chart
    document.addEventListener('DOMContentLoaded', function() {
        if(document.getElementById('submissionsDoughnut')){
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
                    plugins: { legend: { display: false } },
                    cutout: '75%'
                }
            });
        }
    });

    // Student Database (Sab batch aur saal ke naam alag-alag hain)
    const studentDB = {
        '1st Year': {
            'A1': ['Aarav Patel', 'Diya Sharma', 'Vihaan Singh'],
            'A2': ['Aanya Gupta', 'Aditya Verma', 'Zara Khan'],
            'A3': ['Sai Joshi', 'Isha Reddy', 'Arjun Das'],
            'A4': ['Riya Nair', 'Dev Mehta', 'Mira Iyer'],
            'B1': ['Neha Kapoor', 'Rahul Desai', 'Kavya Sen'],
            'B2': ['Neil Bhatia', 'Rohan Chawla', 'Tanvi Menon'],
            'B3': ['Aryan Joshi', 'Kabir Tiwari', 'Shruti Jain'],
            'B4': ['Rishi Saxena', 'Kriti Agarwal', 'Sarthak Rajput']
        },
        '2nd Year': {
            'A1': ['Shaurya Mhatre', 'Ananya Kadam', 'Daksh Rajput'],
            'A2': ['Ishaan Kulkarni', 'Prisha More', 'Dhruv Joshi'],
            'A3': ['Nandini Gadkari', 'Sarthak Shinde', 'Mitali Pawar'],
            'A4': ['Pranav Bhagat', 'Pooja Bharti', 'Harsh Nehra'],
            'B1': ['Vedant Vardhan', 'Megha Chopra', 'Yash Singh'],
            'B2': ['Sneha Kapoor', 'Karan Ali', 'Sagar Kumar'],
            'B3': ['Vikram Kapoor', 'Anjali Bhatt', 'Amit Dhawan'],
            'B4': ['Vishal Malhotra', 'Sonali Advani', 'Nitin Johar']
        },
        '3rd Year': {
            'A1': ['Tanishq Tata', 'Aditi Ambani', 'Samar Adani'],
            'A2': ['Vivaan Pichai', 'Suhani Nadella', 'Darshan Nooyi'],
            'A3': ['Mahi Kohli', 'Avni Dhoni', 'Khushi Sharma'],
            'A4': ['Chirag Bumrah', 'Jatin Pandya', 'Kunal Pant'],
            'B1': ['Nikhil Mandhana', 'Tarun Kaur', 'Divya Raj'],
            'B2': ['Priya Sindhu', 'Rachna Nehwal', 'Swati Kom'],
            'B3': ['Tanya Chopra', 'Manish Bindra', 'Ritu Kumar'],
            'B4': ['Deepak Chhetri', 'Sonali Bhutia', 'Vishal Singh']
        }
    };

    // Modal Logic
    let yearModal;

    function openYearPopup(year) {
        document.getElementById('studentYearModalLabel').innerText = year + " Students";

        let content = `
            <div class="mb-3 text-center">
                <h6 class="text-muted mb-3">Select Class:</h6>
                <button class="btn btn-primary px-4 me-2" onclick="showBatches('${year}', 'A')">Class A</button>
                <button class="btn btn-primary px-4" onclick="showBatches('${year}', 'B')">Class B</button>
            </div>
            <div id="batchSection" class="mb-3 text-center"></div>
            <div id="studentListSection"></div>
        `;
        document.getElementById('modalDynamicContent').innerHTML = content;

        if (!yearModal) {
            yearModal = new bootstrap.Modal(document.getElementById('studentYearModal'));
        }
        yearModal.show();
    }

    function showBatches(year, className) {
        let content = `
            <hr>
            <h6 class="text-muted mb-3">Select Batch for Class ${className}:</h6>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <button class="btn btn-outline-secondary btn-sm" onclick="showStudents('${year}', '${className}', '${className}1')">Batch ${className}1</button>
                <button class="btn btn-outline-secondary btn-sm" onclick="showStudents('${year}', '${className}', '${className}2')">Batch ${className}2</button>
                <button class="btn btn-outline-secondary btn-sm" onclick="showStudents('${year}', '${className}', '${className}3')">Batch ${className}3</button>
                <button class="btn btn-outline-secondary btn-sm" onclick="showStudents('${year}', '${className}', '${className}4')">Batch ${className}4</button>
            </div>
        `;
        document.getElementById('batchSection').innerHTML = content;
        document.getElementById('studentListSection').innerHTML = ''; 
    }

    function showStudents(year, className, batchName) {
        // Javascript Data se Unique naam lana
        let students = studentDB[year][batchName] || [];

        let content = `
            <hr>
            <h6 class="fw-bold text-dark">Students of Batch ${batchName}</h6>
            <ul class="list-group mt-2">
        `;
        
        students.forEach(name => {
            content += `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    ${name} 
                    <span class="badge bg-success rounded-pill">Active</span>
                </li>
            `;
        });

        content += `</ul>`;
        document.getElementById('studentListSection').innerHTML = content;
    }
</script>

<?php
include 'footer.php';
?>