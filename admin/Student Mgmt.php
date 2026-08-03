<?php
include 'header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-dark mb-0">
        👨‍🎓 Student Management & Lab Manual Tracker
    </h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="fa-solid fa-plus me-1"></i> Add New Student
    </button>
</div>

<!-- STUDENT CARD 1: Pathan Rehan Khan -->
<div class="student-card" id="studentCard-7131">
    <div class="student-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3 flex-grow-1" data-bs-toggle="collapse" data-bs-target="#studentSubjects7131">
            <img src="https://ui-avatars.com/api/?name=Pathan+Rehan+Khan&background=2563eb&color=fff" class="rounded-circle" width="42" alt="Rehan">
            <div>
                <h6 class="fw-bold mb-0 text-dark">Pathan Rehan Khan</h6>
                <small class="text-muted">Enrollment: 7131 | Branch: CE (sem5) | Batch B1</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary rounded-pill px-3 py-2" data-bs-toggle="collapse" data-bs-target="#studentSubjects7131">5 Lab Manuals</span>
            <button class="btn btn-sm btn-outline-danger" onclick="removeStudent('studentCard-7131', 'Pathan Rehan Khan')">
                <i class="fa-solid fa-user-minus me-1"></i> Cancel Admission
            </button>
            <i class="fa-solid fa-chevron-down text-muted" data-bs-toggle="collapse" data-bs-target="#studentSubjects7131"></i>
        </div>
    </div>
    <div id="studentSubjects7131" class="collapse show">
        <div class="subject-list-container">
            <p class="text-muted small fw-semibold mb-2">SUBJECT WISE LAB MANUAL STATUS:</p>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon approved">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">RDBMS</span>
                        <small class="text-muted d-block">Relational Database Management System</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon approved">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">DS</span>
                        <small class="text-muted d-block">Data Structures & Algorithms</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon not-submitted">
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">IML</span>
                        <small class="text-muted d-block">Introduction to Machine Learning</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-danger bg-opacity-10 text-danger">Not Submitted ❌</span>
                    <button class="btn btn-sm btn-outline-danger" disabled>No File</button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon pending">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">RWPD</span>
                        <small class="text-muted d-block">Responsive Web Program Development</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-warning bg-opacity-10 text-warning">Under Review ⏳</span>
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-file-pdf"></i> Review
                    </button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon not-submitted">
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">SE</span>
                        <small class="text-muted d-block">Software Engineering</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-danger bg-opacity-10 text-danger">Not Submitted ❌</span>
                    <button class="btn btn-sm btn-outline-danger" disabled>No File</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- STUDENT CARD 2: Belim Hamza -->
<div class="student-card" id="studentCard-7003">
    <div class="student-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3 flex-grow-1" data-bs-toggle="collapse" data-bs-target="#studentSubjects7003">
            <img src="https://ui-avatars.com/api/?name=Belim+Hamza&background=10b981&color=fff" class="rounded-circle" width="42" alt="Hamza">
            <div>
                <h6 class="fw-bold mb-0 text-dark">Belim Hamza</h6>
                <small class="text-muted">Enrollment: 7003 | Branch: CE (sem5) | Batch A1</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary rounded-pill px-3 py-2" data-bs-toggle="collapse" data-bs-target="#studentSubjects7003">5 Lab Manuals</span>
            <button class="btn btn-sm btn-outline-danger" onclick="removeStudent('studentCard-7003', 'Belim Hamza')">
                <i class="fa-solid fa-user-minus me-1"></i> Cancel Admission
            </button>
            <i class="fa-solid fa-chevron-down text-muted" data-bs-toggle="collapse" data-bs-target="#studentSubjects7003"></i>
        </div>
    </div>
    <div id="studentSubjects7003" class="collapse">
        <div class="subject-list-container">
            <p class="text-muted small fw-semibold mb-2">SUBJECT WISE LAB MANUAL STATUS:</p>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon approved">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">RDBMS</span>
                        <small class="text-muted d-block">Relational Database Management System</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon approved">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">DS</span>
                        <small class="text-muted d-block">Data Structures & Algorithms</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon pending">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">IML</span>
                        <small class="text-muted d-block">Introduction to Machine Learning</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-warning bg-opacity-10 text-warning">Under Review ⏳</span>
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-file-pdf"></i> Review
                    </button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon approved">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">RWPD</span>
                        <small class="text-muted d-block">Responsive Web Program Development</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon not-submitted">
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">SE</span>
                        <small class="text-muted d-block">Software Engineering</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-danger bg-opacity-10 text-danger">Not Submitted ❌</span>
                    <button class="btn btn-sm btn-outline-danger" disabled>No File</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- STUDENT CARD 3: Sheikh Sohan -->
<div class="student-card" id="studentCard-7038">
    <div class="student-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3 flex-grow-1" data-bs-toggle="collapse" data-bs-target="#studentSubjects7038">
            <img src="https://ui-avatars.com/api/?name=Sheikh+Sohan&background=f59e0b&color=fff" class="rounded-circle" width="42" alt="Sohan">
            <div>
                <h6 class="fw-bold mb-0 text-dark">Sheikh Sohan</h6>
                <small class="text-muted">Enrollment: 7038 | Branch: CE (sem5) | Batch A1</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary rounded-pill px-3 py-2" data-bs-toggle="collapse" data-bs-target="#studentSubjects7038">5 Lab Manuals</span>
            <button class="btn btn-sm btn-outline-danger" onclick="removeStudent('studentCard-7038', 'Sheikh Sohan')">
                <i class="fa-solid fa-user-minus me-1"></i> Cancel Admission
            </button>
            <i class="fa-solid fa-chevron-down text-muted" data-bs-toggle="collapse" data-bs-target="#studentSubjects7038"></i>
        </div>
    </div>
    <div id="studentSubjects7038" class="collapse">
        <div class="subject-list-container">
            <p class="text-muted small fw-semibold mb-2">SUBJECT WISE LAB MANUAL STATUS:</p>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon approved">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">RDBMS</span>
                        <small class="text-muted d-block">Relational Database Management System</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon pending">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">DS</span>
                        <small class="text-muted d-block">Data Structures & Algorithms</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-warning bg-opacity-10 text-warning">Under Review ⏳</span>
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-file-pdf"></i> Review
                    </button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon approved">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">IML</span>
                        <small class="text-muted d-block">Introduction to Machine Learning</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon not-submitted">
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">RWPD</span>
                        <small class="text-muted d-block">Responsive Web Program Development</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-danger bg-opacity-10 text-danger">Not Submitted ❌</span>
                    <button class="btn btn-sm btn-outline-danger" disabled>No File</button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon approved">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">SE</span>
                        <small class="text-muted d-block">Software Engineering</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- STUDENT CARD 4: MANSURI ARMAN -->
<div class="student-card" id="studentCard-7055">
    <div class="student-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3 flex-grow-1" data-bs-toggle="collapse" data-bs-target="#studentSubjects7055">
            <img src="https://ui-avatars.com/api/?name=MANSURI+ARMAN&background=8b5cf6&color=fff" class="rounded-circle" width="42" alt="Arman">
            <div>
                <h6 class="fw-bold mb-0 text-dark">MANSURI ARMAN</h6>
                <small class="text-muted">Enrollment: 7055 | Branch: CE (sem5) | Batch A1</small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary rounded-pill px-3 py-2" data-bs-toggle="collapse" data-bs-target="#studentSubjects7055">5 Lab Manuals</span>
            <button class="btn btn-sm btn-outline-danger" onclick="removeStudent('studentCard-7055', 'MANSURI ARMAN')">
                <i class="fa-solid fa-user-minus me-1"></i> Cancel Admission
            </button>
            <i class="fa-solid fa-chevron-down text-muted" data-bs-toggle="collapse" data-bs-target="#studentSubjects7055"></i>
        </div>
    </div>
    <div id="studentSubjects7055" class="collapse">
        <div class="subject-list-container">
            <p class="text-muted small fw-semibold mb-2">SUBJECT WISE LAB MANUAL STATUS:</p>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon approved">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">RDBMS</span>
                        <small class="text-muted d-block">Relational Database Management System</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon approved">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">DS</span>
                        <small class="text-muted d-block">Data Structures & Algorithms</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon pending">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">IML</span>
                        <small class="text-muted d-block">Introduction to Machine Learning</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-warning bg-opacity-10 text-warning">Under Review ⏳</span>
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-file-pdf"></i> Review
                    </button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon approved">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">RWPD</span>
                        <small class="text-muted d-block">Responsive Web Program Development</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                    <button class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                </div>
            </div>
            <div class="subject-item">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon not-submitted">
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">SE</span>
                        <small class="text-muted d-block">Software Engineering</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge-status bg-danger bg-opacity-10 text-danger">Not Submitted ❌</span>
                    <button class="btn btn-sm btn-outline-danger" disabled>No File</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ADD STUDENT MODAL -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="mb-2">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Enrollment No.</label>
                        <input type="text" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control">
                    </div>
                    <div class="row">
                        <div class="col">
                            <label class="form-label">Branch</label>
                            <input type="text" class="form-control">
                        </div>
                        <div class="col">
                            <label class="form-label">Semester</label>
                            <input type="text" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary">Save Student</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Student Remove / Cancel Admission Functionality
    function removeStudent(cardId, studentName) {
        if (confirm("Kya aap " + studentName + " ka admission cancel/remove karna chahte hain?")) {
            const card = document.getElementById(cardId);
            if (card) {
                card.style.transition = "all 0.3s ease";
                card.style.opacity = "0";
                card.style.transform = "scale(0.95)";
                setTimeout(() => {
                    card.remove();
                }, 300);
            }
        }
    }
</script>

<?php
include 'footer.php';
?>
