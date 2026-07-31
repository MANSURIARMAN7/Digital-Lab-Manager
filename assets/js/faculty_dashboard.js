// MODAL LOGIC (Global taaki button click par popup khule)
window.openModal = function(name, sub) {
    document.getElementById('modalName').innerText = name;
    document.getElementById('modalSubject').innerText = "Subject: " + sub;
    document.getElementById('studentModal').style.display = 'flex';
};

window.closeModal = function() {
    document.getElementById('studentModal').style.display = 'none';
};


document.addEventListener("DOMContentLoaded", function() {
    
    // ==========================================
    // 1. CHART.JS SETUP (Graph)
    // ==========================================
    const canvasElement = document.getElementById('statusChart');
    if (canvasElement) {
        const ctx = canvasElement.getContext('2d');
        const centerTextPlugin = {
            id: 'centerText',
            beforeDraw: function(chart) {
                let width = chart.width, height = chart.height, ctx = chart.ctx;
                ctx.restore();
                const isDark = document.body.classList.contains('dark-theme');
                let fontSize = (height / 120).toFixed(2);
                ctx.font = "bold " + fontSize + "em 'Plus Jakarta Sans', sans-serif";
                ctx.textBaseline = "middle";
                ctx.fillStyle = isDark ? "#f8fafc" : "#0f172a"; 
        
                let text = "120",
                    textX = Math.round((width - ctx.measureText(text).width) / 2),
                    textY = height / 2 - 12;
                ctx.fillText(text, textX, textY);
                
                ctx.font = "600 " + (fontSize * 0.35).toFixed(2) + "em 'Plus Jakarta Sans', sans-serif";
                ctx.fillStyle = isDark ? "#94a3b8" : "#64748b";
                let text2 = "Total Manuals",
                    text2X = Math.round((width - ctx.measureText(text2).width) / 2),
                    text2Y = height / 2 + 18;
                ctx.fillText(text2, text2X, text2Y);
                ctx.save();
            }
        };

        new Chart(ctx, {
            type: 'doughnut', 
            data: {
                labels: ['Approved', 'Pending', 'Rejected'],
                datasets: [{
                    label: 'Lab Manuals',
                    data: [95, 18, 7], 
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    hoverOffset: 12,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '75%', 
                animation: { animateScale: true, animateRotate: true },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true, padding: 25,
                            font: { family: "'Plus Jakarta Sans', sans-serif", size: 13, weight: '600' },
                            color: '#94a3b8'
                        }
                    }
                }
            },
            plugins: [centerTextPlugin]
        });
    }

    // ==========================================
    // 2. DARK MODE LOGIC
    // ==========================================
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    if (themeToggle) {
        if(localStorage.getItem('dark-mode') === 'enabled') {
            body.classList.add('dark-theme');
            themeToggle.classList.replace('fa-moon', 'fa-sun');
        }
        themeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-theme');
            if(body.classList.contains('dark-theme')) {
                localStorage.setItem('dark-mode', 'enabled');
                themeToggle.classList.replace('fa-moon', 'fa-sun');
            } else {
                localStorage.setItem('dark-mode', 'disabled');
                themeToggle.classList.replace('fa-sun', 'fa-moon');
            }
        });
    }

    // ==========================================
    // 3. LIVE SEARCH LOGIC 
    // ==========================================
    const searchInput = document.querySelector('.search-bar input');
    const tableRows = document.querySelectorAll('.table-section table tr');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            for (let i = 1; i < tableRows.length; i++) {
                const row = tableRows[i];
                const rowData = row.innerText.toLowerCase();
                if (rowData.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    }

    // ==========================================
    // 4. ACTION BUTTON ALERTS
    // ==========================================
    document.querySelectorAll('.btn-action-sm.check').forEach(btn => {
        btn.addEventListener('click', () => alert('Status updated to: Approved!'));
    });
    
    document.querySelectorAll('.btn-action-sm.times').forEach(btn => {
        btn.addEventListener('click', () => alert('Status updated to: Rejected!'));
    });
});