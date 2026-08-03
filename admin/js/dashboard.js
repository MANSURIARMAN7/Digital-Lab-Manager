document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle for Mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    // Display Current Date and Time
    function updateDateTime() {
        const now = new Date();
        const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
        
        document.getElementById('currentDate').textContent = now.toLocaleDateString(undefined, dateOptions);
        document.getElementById('currentTime').textContent = now.toLocaleTimeString(undefined, timeOptions);
    }

    setInterval(updateDateTime, 1000);
    updateDateTime();

    // Chart.js - Submission Overview
    const ctx = document.getElementById('submissionChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Manual A', 'Manual B', 'Manual C', 'Manual D', 'Manual E'],
            datasets: [{
                label: 'Submitted',
                data: [45, 52, 38, 65, 48],
                backgroundColor: '#0d6efd',
                borderRadius: 5,
            }, {
                label: 'Approved',
                data: [35, 48, 30, 55, 40],
                backgroundColor: '#198754',
                borderRadius: 5,
            }, {
                label: 'Pending',
                data: [7, 3, 5, 8, 5],
                backgroundColor: '#ffc107',
                borderRadius: 5,
            }, {
                label: 'Rejected',
                data: [3, 1, 3, 2, 3],
                backgroundColor: '#dc3545',
                borderRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        drawBorder: false,
                        color: '#f1f5f9'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});