document.addEventListener("DOMContentLoaded", function() {
    // Check if canvas exists before drawing
    const canvasElement = document.getElementById('statusChart');
    
    if (canvasElement) {
        const ctx = canvasElement.getContext('2d');
        
        const statusChart = new Chart(ctx, {
            type: 'doughnut', 
            data: {
                labels: ['Approved', 'Pending', 'Rejected'],
                datasets: [{
                    label: 'Lab Manuals',
                    data: [95, 18, 7], // Dummy data
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    hoverOffset: 4,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { family: "'Plus Jakarta Sans', sans-serif", size: 13 }
                        }
                    }
                }
            }
        });
    } else {
        console.error("Canvas element 'statusChart' not found!");
    }
});