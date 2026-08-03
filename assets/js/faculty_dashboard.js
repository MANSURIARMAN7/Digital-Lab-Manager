// ==========================================
// 1. REALISTIC TOAST NOTIFICATION
// ==========================================
function showToast(msg, type = 'success') {
    const toastBox = document.getElementById('toastBox');
    if (!toastBox) return;
    const toast = document.createElement('div');
    toast.classList.add('toast', type);
    let icon = type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>';
    toast.innerHTML = `${icon} <span>${msg}</span>`;
    toastBox.appendChild(toast);
    setTimeout(() => { toast.remove(); }, 3000);
}

// ==========================================
// 2. MODALS LOGIC (VIEW, GRADING & REMARKS)
// ==========================================

// -- NAYA ADD KIYA HAI: View Modal ke liye --
window.openModal = function(name, sub) {
    const mName = document.getElementById('modalName');
    const mSub = document.getElementById('modalSubject');
    if(mName && mSub) {
        mName.innerText = name;
        mSub.innerText = "Subject: " + sub;
        document.getElementById('studentModal').style.display = 'flex';
    }
};

window.closeModal = function() {
    const modal = document.getElementById('studentModal');
    if(modal) modal.style.display = 'none';
};
// ------------------------------------------

window.openGradeModal = function(id, name) {
    document.getElementById('gradeStudentId').value = id;
    document.getElementById('gradeStudentName').innerText = name;
    document.getElementById('gradeModal').style.display = 'flex';
};

window.closeGradeModal = function() {
    document.getElementById('gradeModal').style.display = 'none';
    document.getElementById('marksInput').value = '';
};

window.submitGrade = function() {
    let id = document.getElementById('gradeStudentId').value;
    let marks = document.getElementById('marksInput').value;
    if(marks === '' || marks < 1 || marks > 10) {
        alert("Please enter valid marks between 1 and 10.");
        return;
    }
    updateStatus([id], 'Approved', marks, '');
    closeGradeModal();
};

window.openRejectModal = function(id, name) {
    document.getElementById('rejectStudentId').value = id;
    document.getElementById('rejectStudentName').innerText = name;
    document.getElementById('rejectModal').style.display = 'flex';
};

window.closeRejectModal = function() {
    document.getElementById('rejectModal').style.display = 'none';
    document.getElementById('remarkInput').value = '';
};

window.submitReject = function() {
    let id = document.getElementById('rejectStudentId').value;
    let remark = document.getElementById('remarkInput').value;
    if(remark.trim() === '') {
        alert("Please provide a reason for rejection.");
        return;
    }
    updateStatus([id], 'Rejected', '', remark);
    closeRejectModal();
};

// ==========================================
// 3. BACKEND AJAX
// ==========================================
function updateStatus(ids, newStatus, marks = '', remark = '') {
    let formData = new FormData();
    formData.append('id', ids.join(',')); 
    formData.append('status', newStatus);
    if(marks !== '') formData.append('marks', marks);
    if(remark !== '') formData.append('remark', remark);

    fetch('update_status.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        if(data.trim() === 'Success') {
            showToast(`Data Updated Successfully!`, 'success');
            setTimeout(() => { location.reload(); }, 1500); 
        } else {
            showToast('Error updating database!', 'error');
        }
    }).catch(() => showToast('Server connection failed!', 'error'));
}

// ==========================================
// 4. EXPORT TO CSV (FOR REPORTS PAGE)
// ==========================================
function downloadCSV() {
    let csv = [];
    let rows = document.querySelectorAll("table tr");
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll("td, th");
        for (let j = 0; j < cols.length; j++) {
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        }
        csv.push(row.join(","));
    }
    
    let csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
    let downloadLink = document.createElement("a");
    downloadLink.download = "GTU_Term_Work_Report.csv";
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
}

// ==========================================
// DOM LOAD EVENTS
// ==========================================
document.addEventListener("DOMContentLoaded", function() {

    // -- CSV EXPORT LISTENER --
    const downloadBtn = document.getElementById('downloadCSV');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', downloadCSV);
    }

    // -- BULK CHECKBOX LOGIC --
    const selectAllBtn = document.getElementById('selectAll');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('change', function() {
            document.querySelectorAll('.manual-checkbox').forEach(cb => cb.checked = this.checked);
        });
    }

    // -- NAYA ADD KIYA HAI: Bulk Approve Fix --
    const bulkApproveBtn = document.getElementById('bulkApproveBtn');
    if (bulkApproveBtn) {
        bulkApproveBtn.addEventListener('click', function() {
            let ids = [];
            document.querySelectorAll('.manual-checkbox:checked').forEach(cb => ids.push(cb.getAttribute('data-id')));
            
            if(ids.length > 0) {
                let confirmAction = confirm(`Are you sure you want to approve and give 10/10 to ${ids.length} students?`);
                if (confirmAction) {
                    bulkApproveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    updateStatus(ids, 'Approved', '10', '');
                }
            } else {
                showToast('Please select at least one manual by checking the box!', 'error');
            }
        });
    }

    // -- LAB MANUAL FILTERS --
    const applyFilterBtn = document.getElementById('applyFilterBtn');
    if (applyFilterBtn) {
        applyFilterBtn.addEventListener('click', function() {
            const sub = document.getElementById('subjectFilter').value;
            document.querySelectorAll('.manual-row').forEach(row => {
                row.style.display = (sub === 'All' || sub === row.dataset.subject) ? '' : 'none';
            });
        });
    }

    // -- LIVE SEARCH --
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.table-section table tr').forEach((row, index) => {
                if(index === 0) return; 
                row.style.display = row.innerText.toLowerCase().includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // -- DARK MODE --
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        if(localStorage.getItem('dark-mode') === 'enabled') {
            document.body.classList.add('dark-theme');
            themeToggle.classList.replace('fa-moon', 'fa-sun');
        }
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-theme');
            let isDark = document.body.classList.contains('dark-theme');
            localStorage.setItem('dark-mode', isDark ? 'enabled' : 'disabled');
            themeToggle.className = isDark ? 'fas fa-sun dark-mode-toggle' : 'fas fa-moon dark-mode-toggle';
        });
    }
    
    // -- CHART.JS INIT (Taaki Dashboard ka graph load ho) --
    const canvasElement = document.getElementById('statusChart');
    if (canvasElement) {
        const ctx = canvasElement.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: { labels: ['Approved', 'Pending', 'Rejected'], datasets: [{ data: [95, 18, 7], backgroundColor: ['#10b981', '#f59e0b', '#ef4444'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '75%', plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8' } } } }
        });
    }
});