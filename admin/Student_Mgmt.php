<?php
include 'header.php';
?>

<style>
    /* Category Cards CSS */
    .year-card {
        border-radius: 10px;
        border: 1px solid #eaedf1;
        background: #ffffff;
        transition: all 0.3s ease;
        cursor: pointer;
        display: block;
    }
    .year-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.05);
    }
    
    .card-1st-year { border-left: 6px solid #3b82f6 !important; } 
    .icon-bg-1st { background-color: #eff6ff; color: #3b82f6; }

    .card-2nd-year { border-left: 6px solid #10b981 !important; }
    .icon-bg-2nd { background-color: #ecfdf5; color: #10b981; }

    .card-3rd-year { border-left: 6px solid #f59e0b !important; }
    .icon-bg-3rd { background-color: #fffbeb; color: #f59e0b; }

    .icon-circle {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 1.3rem;
    }

    /* Modal Selection Buttons CSS */
    .btn-class-select {
        width: 120px;
        border-radius: 8px;
        font-weight: 600;
    }
    .btn-batch-select {
        border-radius: 8px;
        background-color: #ffffff;
        border: 1px solid #d1d5db;
        color: #4b5563;
        font-weight: 500;
        transition: all 0.2s;
    }
    .btn-batch-select:hover, .btn-batch-select.active-batch {
        border-color: #3b82f6;
        color: #3b82f6;
        background-color: #eff6ff;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">👨‍🎓 Student Management & Lab Manual Tracker</h4>
        <p class="text-muted small mb-0">Track student laboratory manuals, academic branch details, and submission progress.</p>
    </div>
    <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
        <i class="fa-solid fa-plus me-1"></i> Add New Student
    </button>
</div>

<!-- ========================================== -->
<!--       MAIN SCREEN: DASHBOARD LAYOUT        -->
<!-- ========================================== -->
<div class="row">
    
    <!-- LEFT COLUMN: Year Categories -->
    <div class="col-md-7 col-lg-6 mb-4">
        <h5 class="fw-bold text-dark mb-3 mt-2">Manage Students by Year</h5>
        
        <!-- 1st Year Card -->
        <div class="year-card card-1st-year mb-3 p-3" data-bs-toggle="modal" data-bs-target="#modal1stYear">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted mb-1 d-block">Manage Classes A & B</small>
                    <h4 class="fw-bold mb-0 text-dark">1st Year Students</h4>
                </div>
                <div class="icon-circle icon-bg-1st">
                    <i class="fa-solid fa-user-group"></i>
                </div>
            </div>
        </div>

        <!-- 2nd Year Card -->
        <div class="year-card card-2nd-year mb-3 p-3" data-bs-toggle="modal" data-bs-target="#modal2ndYear">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted mb-1 d-block">Manage Classes A & B</small>
                    <h4 class="fw-bold mb-0 text-dark">2nd Year Students</h4>
                </div>
                <div class="icon-circle icon-bg-2nd">
                    <i class="fa-solid fa-user-group"></i>
                </div>
            </div>
        </div>

        <!-- 3rd Year Card -->
        <div class="year-card card-3rd-year mb-3 p-3" data-bs-toggle="modal" data-bs-target="#modal3rdYear">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted mb-1 d-block">Manage Classes A & B</small>
                    <h4 class="fw-bold mb-0 text-dark">3rd Year Students</h4>
                </div>
                <div class="icon-circle icon-bg-3rd">
                    <i class="fa-solid fa-user-group"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Quick Search & Notice Board -->
    <div class="col-md-5 col-lg-6 mb-4">
        <h5 class="fw-bold text-dark mb-3 mt-2">Quick Actions & Updates</h5>
        
        <!-- Quick Search Widget -->
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 10px;">
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fa-solid fa-magnifying-glass text-primary me-2"></i> Quick Student Search
                </h6>
                <div class="input-group mb-2">
                    <input type="text" class="form-control bg-light border-0" placeholder="Enter Enrollment No..." aria-label="Search Student">
                    <button class="btn btn-primary px-4" type="button">Search</button>
                </div>
                <small class="text-muted">Directly find any student from any year.</small>
            </div>
        </div>

        <!-- Notice Board Widget -->
        <div class="card shadow-sm border-0" style="border-radius: 10px;">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-bullhorn text-warning me-2"></i> Notice Board
                </h6>
            </div>
            <div class="card-body p-4">
                
                <!-- Alert 1: Warning -->
                <div class="alert alert-warning border-0 d-flex align-items-center mb-3" role="alert" style="border-radius: 8px;">
                    <i class="fa-solid fa-triangle-exclamation fs-4 me-3"></i>
                    <div>
                        <span class="fw-bold d-block text-dark">Submission Deadline</span>
                        <small class="text-dark opacity-75">1st Year (Sem 1) submission ends tomorrow.</small>
                    </div>
                </div>
                
                <!-- Alert 2: Info -->
                <div class="alert alert-info border-0 d-flex align-items-center mb-3" role="alert" style="border-radius: 8px;">
                    <i class="fa-solid fa-circle-info fs-4 me-3"></i>
                    <div>
                        <span class="fw-bold d-block text-dark">Lab Schedule Updated</span>
                        <small class="text-dark opacity-75">New timetable for 2nd Year (Class A).</small>
                    </div>
                </div>

                <!-- Alert 3: Success -->
                <div class="alert alert-success border-0 d-flex align-items-center mb-0" role="alert" style="border-radius: 8px;">
                    <i class="fa-solid fa-check-circle fs-4 me-3"></i>
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 13.5px; line-height: 1.2;">Prof. M. C. Thakor</div>
                        <div class="text-muted" style="font-size: 11.5px;">University Tech</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div> <!-- End of Row -->

<!-- ========================================== -->
<!--       POPUP (MODAL) FOR 1ST YEAR           -->
<!-- ========================================== -->
<div class="modal fade" id="modal1stYear" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="background-color: #f8f9fa;">
            <div class="modal-header border-bottom pb-3 bg-white">
                <h5 class="fw-bold text-primary mb-0">1st Year Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetSelections('1')"></button>
            </div>
            <div class="modal-body pt-4">
                
                <div class="text-center mb-4">
                    <span class="text-muted d-block mb-3">Select Class:</span>
                    <button class="btn btn-primary btn-class-select me-2 class-btn-1" onclick="showBatches('1', 'A', this)">Class A</button>
                    <button class="btn btn-primary btn-class-select class-btn-1" onclick="showBatches('1', 'B', this)">Class B</button>
                </div>
                <hr class="text-muted opacity-25">

                <div id="batches-1-A" class="text-center mb-4 d-none batch-container-1">
                    <span class="text-muted d-block mb-3">Select Batch for Class A:</span>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button class="btn px-4 py-2 btn-batch-select batch-btn-1" onclick="showStudents('1', 'A1', this)">Batch A1</button>
                        <button class="btn px-4 py-2 btn-batch-select batch-btn-1" onclick="showStudents('1', 'A2', this)">Batch A2</button>
                        <button class="btn px-4 py-2 btn-batch-select batch-btn-1" onclick="showStudents('1', 'A3', this)">Batch A3</button>
                        <button class="btn px-4 py-2 btn-batch-select batch-btn-1" onclick="showStudents('1', 'A4', this)">Batch A4</button>
                    </div>
                </div>

                <div id="batches-1-B" class="text-center mb-4 d-none batch-container-1">
                    <span class="text-muted d-block mb-3">Select Batch for Class B:</span>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button class="btn px-4 py-2 btn-batch-select batch-btn-1" onclick="showStudents('1', 'B1', this)">Batch B1</button>
                        <button class="btn px-4 py-2 btn-batch-select batch-btn-1" onclick="showStudents('1', 'B2', this)">Batch B2</button>
                    </div>
                </div>

                <div id="divider-1" class="d-none"><hr class="text-muted opacity-25"></div>

                <div id="students-container-1" class="d-none">
                    <h6 class="fw-bold mb-3 text-dark" id="student-title-1">Students of Batch A1</h6>
                    
                    <div id="students-1-A1" class="student-group-1 d-none">
                        <!-- Student 1: Aarav Patel -->
                        <div class="student-card shadow-sm mb-3" id="studentCard-1001">
                            <div class="student-header d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3 flex-grow-1" data-bs-toggle="collapse" data-bs-target="#studentSubjects1001">
                                    <img src="https://ui-avatars.com/api/?name=Aarav+Patel&background=2563eb&color=fff" class="rounded-circle" width="42" alt="Aarav">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">Aarav Patel</h6>
                                        <small class="text-muted">Enrollment: 1001 | Branch: CE (sem1) | Batch A1</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <button class="btn btn-sm btn-outline-danger px-3 py-1 me-1" style="border-radius: 15px;">Remove</button>
                                    <span class="badge bg-success rounded-pill px-3 py-2">Active</span>
                                    <span class="badge bg-primary rounded-pill px-3 py-2" data-bs-toggle="collapse" data-bs-target="#studentSubjects1001">5 Lab Manuals</span>
                                    <i class="fa-solid fa-chevron-down text-muted" data-bs-toggle="collapse" data-bs-target="#studentSubjects1001"></i>
                                </div>
                            </div>
                            <div id="studentSubjects1001" class="collapse show">
                                <div class="subject-list-container">
                                    <p class="text-muted small fw-semibold mb-2 mt-3">SUBJECT WISE LAB MANUAL STATUS (1st Year):</p>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">RWPD</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">BOE</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">MATHEMATIC-I</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon not-submitted"><i class="fa-solid fa-xmark"></i></div>
                                            <div><span class="fw-bold text-dark">CPF</span></div>
                                        </div>
                                        <span class="badge-status bg-danger bg-opacity-10 text-danger">Not Submitted ❌</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon pending"><i class="fa-solid fa-clock"></i></div>
                                            <div><span class="fw-bold text-dark">MODERN PHYSICS</span></div>
                                        </div>
                                        <span class="badge-status bg-warning bg-opacity-10 text-warning">Under Review ⏳</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Student 2: Diya Sharma -->
                        <div class="student-card shadow-sm mb-3" id="studentCard-1001-2">
                            <div class="student-header d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3 flex-grow-1" data-bs-toggle="collapse" data-bs-target="#studentSubjects1001-2">
                                    <img src="https://ui-avatars.com/api/?name=Diya+Sharma&background=2563eb&color=fff" class="rounded-circle" width="42" alt="Diya">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">Diya Sharma</h6>
                                        <small class="text-muted">Enrollment: 1002 | Branch: CE (sem1) | Batch A1</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <button class="btn btn-sm btn-outline-danger px-3 py-1 me-1" style="border-radius: 15px;">Remove</button>
                                    <span class="badge bg-success rounded-pill px-3 py-2">Active</span>
                                    <span class="badge bg-primary rounded-pill px-3 py-2" data-bs-toggle="collapse" data-bs-target="#studentSubjects1001-2">5 Lab Manuals</span>
                                    <i class="fa-solid fa-chevron-down text-muted" data-bs-toggle="collapse" data-bs-target="#studentSubjects1001-2"></i>
                                </div>
                            </div>
                            <div id="studentSubjects1001-2" class="collapse">
                                <div class="subject-list-container">
                                    <p class="text-muted small fw-semibold mb-2 mt-3">SUBJECT WISE LAB MANUAL STATUS (1st Year):</p>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">RWPD</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">BOE</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">MATHEMATIC-I</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">CPF</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">MODERN PHYSICS</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="students-1-A2" class="student-group-1 d-none">
                        <!-- Student 1: Aanya Gupta -->
                        <div class="student-card shadow-sm" id="studentCard-1002">
                            <div class="student-header d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3 flex-grow-1" data-bs-toggle="collapse" data-bs-target="#studentSubjects1002">
                                    <img src="https://ui-avatars.com/api/?name=Aanya+Gupta&background=10b981&color=fff" class="rounded-circle" width="42" alt="Aanya">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">Aanya Gupta</h6>
                                        <small class="text-muted">Enrollment: 1004 | Branch: CE (sem1) | Batch A2</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <button class="btn btn-sm btn-outline-danger px-3 py-1 me-1" style="border-radius: 15px;">Remove</button>
                                    <span class="badge bg-success rounded-pill px-3 py-2">Active</span>
                                    <span class="badge bg-primary rounded-pill px-3 py-2" data-bs-toggle="collapse" data-bs-target="#studentSubjects1002">5 Lab Manuals</span>
                                    <i class="fa-solid fa-chevron-down text-muted" data-bs-toggle="collapse" data-bs-target="#studentSubjects1002"></i>
                                </div>
                            </div>
                            <div id="studentSubjects1002" class="collapse">
                                <div class="subject-list-container">
                                    <p class="text-muted small fw-semibold mb-2 mt-3">SUBJECT WISE LAB MANUAL STATUS (1st Year):</p>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">RWPD</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">BOE</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon pending"><i class="fa-solid fa-clock"></i></div>
                                            <div><span class="fw-bold text-dark">MATHEMATIC-I</span></div>
                                        </div>
                                        <span class="badge-status bg-warning bg-opacity-10 text-warning">Under Review ⏳</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">CPF</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">MODERN PHYSICS</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="students-1-A3" class="student-group-1 d-none text-center py-4"><p class="text-muted">No students assigned to Batch A3 yet.</p></div>
                    <div id="students-1-A4" class="student-group-1 d-none text-center py-4"><p class="text-muted">No students assigned to Batch A4 yet.</p></div>
                    <div id="students-1-B1" class="student-group-1 d-none text-center py-4"><p class="text-muted">No students assigned to Batch B1 yet.</p></div>
                    <div id="students-1-B2" class="student-group-1 d-none text-center py-4"><p class="text-muted">No students assigned to Batch B2 yet.</p></div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!--       POPUP (MODAL) FOR 2ND YEAR           -->
<!-- ========================================== -->
<div class="modal fade" id="modal2ndYear" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="background-color: #f8f9fa;">
            <div class="modal-header border-bottom pb-3 bg-white">
                <h5 class="fw-bold text-success mb-0">2nd Year Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetSelections('2')"></button>
            </div>
            <div class="modal-body pt-4">
                
                <div class="text-center mb-4">
                    <span class="text-muted d-block mb-3">Select Class:</span>
                    <button class="btn btn-primary btn-class-select me-2 class-btn-2" onclick="showBatches('2', 'A', this)">Class A</button>
                    <button class="btn btn-primary btn-class-select class-btn-2" onclick="showBatches('2', 'B', this)">Class B</button>
                </div>
                <hr class="text-muted opacity-25">

                <div id="batches-2-A" class="text-center mb-4 d-none batch-container-2">
                    <span class="text-muted d-block mb-3">Select Batch for Class A:</span>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button class="btn px-4 py-2 btn-batch-select batch-btn-2" onclick="showStudents('2', 'A1', this)">Batch A1</button>
                        <button class="btn px-4 py-2 btn-batch-select batch-btn-2" onclick="showStudents('2', 'A2', this)">Batch A2</button>
                    </div>
                </div>

                <div id="batches-2-B" class="text-center mb-4 d-none batch-container-2">
                    <span class="text-muted d-block mb-3">Select Batch for Class B:</span>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button class="btn px-4 py-2 btn-batch-select batch-btn-2" onclick="showStudents('2', 'B1', this)">Batch B1</button>
                    </div>
                </div>

                <div id="divider-2" class="d-none"><hr class="text-muted opacity-25"></div>

                <div id="students-container-2" class="d-none">
                    <h6 class="fw-bold mb-3 text-dark" id="student-title-2">Students of Batch A1</h6>
                    
                    <div id="students-2-A1" class="student-group-2 d-none">
                        <!-- Student 1: Shaurya Mhatre -->
                        <div class="student-card shadow-sm" id="studentCard-2001">
                            <div class="student-header d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3 flex-grow-1" data-bs-toggle="collapse" data-bs-target="#studentSubjects2001">
                                    <img src="https://ui-avatars.com/api/?name=Shaurya+Mhatre&background=f59e0b&color=fff" class="rounded-circle" width="42" alt="Shaurya">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">Shaurya Mhatre</h6>
                                        <small class="text-muted">Enrollment: 2001 | Branch: CE (sem3) | Batch A1</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <button class="btn btn-sm btn-outline-danger px-3 py-1 me-1" style="border-radius: 15px;">Remove</button>
                                    <span class="badge bg-success rounded-pill px-3 py-2">Active</span>
                                    <span class="badge bg-primary rounded-pill px-3 py-2" data-bs-toggle="collapse" data-bs-target="#studentSubjects2001">5 Lab Manuals</span>
                                    <i class="fa-solid fa-chevron-down text-muted" data-bs-toggle="collapse" data-bs-target="#studentSubjects2001"></i>
                                </div>
                            </div>
                            <div id="studentSubjects2001" class="collapse">
                                <div class="subject-list-container">
                                    <p class="text-muted small fw-semibold mb-2 mt-3">SUBJECT WISE LAB MANUAL STATUS (2nd Year):</p>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">FOS</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">CN</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">SWPD</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">DS</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">DBMS</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="students-2-A2" class="student-group-2 d-none">
                        <!-- Student 1: Ishaan Kulkarni -->
                        <div class="student-card shadow-sm" id="studentCard-2002">
                            <div class="student-header d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3 flex-grow-1" data-bs-toggle="collapse" data-bs-target="#studentSubjects2002">
                                    <img src="https://ui-avatars.com/api/?name=Ishaan+Kulkarni&background=8b5cf6&color=fff" class="rounded-circle" width="42" alt="Ishaan">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">Ishaan Kulkarni</h6>
                                        <small class="text-muted">Enrollment: 2004 | Branch: CE (sem3) | Batch A2</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <button class="btn btn-sm btn-outline-danger px-3 py-1 me-1" style="border-radius: 15px;">Remove</button>
                                    <span class="badge bg-success rounded-pill px-3 py-2">Active</span>
                                    <span class="badge bg-primary rounded-pill px-3 py-2" data-bs-toggle="collapse" data-bs-target="#studentSubjects2002">5 Lab Manuals</span>
                                    <i class="fa-solid fa-chevron-down text-muted" data-bs-toggle="collapse" data-bs-target="#studentSubjects2002"></i>
                                </div>
                            </div>
                            <div id="studentSubjects2002" class="collapse">
                                <div class="subject-list-container">
                                    <p class="text-muted small fw-semibold mb-2 mt-3">SUBJECT WISE LAB MANUAL STATUS (2nd Year):</p>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon not-submitted"><i class="fa-solid fa-xmark"></i></div>
                                            <div><span class="fw-bold text-dark">FOS</span></div>
                                        </div>
                                        <span class="badge-status bg-danger bg-opacity-10 text-danger">Not Submitted ❌</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">CN</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">SWPD</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">DS</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">DBMS</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="students-2-B1" class="student-group-2 d-none text-center py-4"><p class="text-muted">No students assigned to Batch B1 yet.</p></div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!--       POPUP (MODAL) FOR 3RD YEAR           -->
<!-- ========================================== -->
<div class="modal fade" id="modal3rdYear" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="background-color: #f8f9fa;">
            <div class="modal-header border-bottom pb-3 bg-white">
                <h5 class="fw-bold text-warning mb-0">3rd Year Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="resetSelections('3')"></button>
            </div>
            <div class="modal-body pt-4">
                
                <div class="text-center mb-4">
                    <span class="text-muted d-block mb-3">Select Class:</span>
                    <button class="btn btn-primary btn-class-select me-2 class-btn-3" onclick="showBatches('3', 'A', this)">Class A</button>
                    <button class="btn btn-primary btn-class-select class-btn-3" onclick="showBatches('3', 'B', this)">Class B</button>
                </div>
                <hr class="text-muted opacity-25">

                <div id="batches-3-A" class="text-center mb-4 d-none batch-container-3">
                    <span class="text-muted d-block mb-3">Select Batch for Class A:</span>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button class="btn px-4 py-2 btn-batch-select batch-btn-3" onclick="showStudents('3', 'A1', this)">Batch A1</button>
                    </div>
                </div>

                <div id="batches-3-B" class="text-center mb-4 d-none batch-container-3">
                    <span class="text-muted d-block mb-3">Select Batch for Class B:</span>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button class="btn px-4 py-2 btn-batch-select batch-btn-3" onclick="showStudents('3', 'B1', this)">Batch B1</button>
                        <button class="btn px-4 py-2 btn-batch-select batch-btn-3" onclick="showStudents('3', 'B2', this)">Batch B2</button>
                    </div>
                </div>

                <div id="divider-3" class="d-none"><hr class="text-muted opacity-25"></div>

                <div id="students-container-3" class="d-none">
                    <h6 class="fw-bold mb-3 text-dark" id="student-title-3">Students of Batch B1</h6>
                    
                    <div id="students-3-A1" class="student-group-3 d-none text-center py-4"><p class="text-muted">No students assigned to Batch A1 yet.</p></div>

                    <div id="students-3-B1" class="student-group-3 d-none">
                        <!-- Student 1: Nikhil Mandhana -->
                        <div class="student-card shadow-sm" id="studentCard-3001">
                            <div class="student-header d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3 flex-grow-1" data-bs-toggle="collapse" data-bs-target="#studentSubjects3001">
                                    <img src="https://ui-avatars.com/api/?name=Nikhil+Mandhana&background=ec4899&color=fff" class="rounded-circle" width="42" alt="Nikhil">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">Nikhil Mandhana</h6>
                                        <small class="text-muted">Enrollment: 3001 | Branch: CE (sem5) | Batch B1</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <button class="btn btn-sm btn-outline-danger px-3 py-1 me-1" style="border-radius: 15px;">Remove</button>
                                    <span class="badge bg-success rounded-pill px-3 py-2">Active</span>
                                    <span class="badge bg-primary rounded-pill px-3 py-2" data-bs-toggle="collapse" data-bs-target="#studentSubjects3001">3 Lab Manuals</span>
                                    <i class="fa-solid fa-chevron-down text-muted" data-bs-toggle="collapse" data-bs-target="#studentSubjects3001"></i>
                                </div>
                            </div>
                            <div id="studentSubjects3001" class="collapse">
                                <div class="subject-list-container">
                                    <p class="text-muted small fw-semibold mb-2 mt-3">SUBJECT WISE LAB MANUAL STATUS (3rd Year):</p>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">IS</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">CHASM</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">ADDVC</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="students-3-B2" class="student-group-3 d-none">
                        <!-- Student 1: Priya Sindhu -->
                        <div class="student-card shadow-sm" id="studentCard-3002">
                            <div class="student-header d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3 flex-grow-1" data-bs-toggle="collapse" data-bs-target="#studentSubjects3002">
                                    <img src="https://ui-avatars.com/api/?name=Priya+Sindhu&background=14b8a6&color=fff" class="rounded-circle" width="42" alt="Priya">
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">Priya Sindhu</h6>
                                        <small class="text-muted">Enrollment: 3004 | Branch: CE (sem5) | Batch B2</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <button class="btn btn-sm btn-outline-danger px-3 py-1 me-1" style="border-radius: 15px;">Remove</button>
                                    <span class="badge bg-success rounded-pill px-3 py-2">Active</span>
                                    <span class="badge bg-primary rounded-pill px-3 py-2" data-bs-toggle="collapse" data-bs-target="#studentSubjects3002">3 Lab Manuals</span>
                                    <i class="fa-solid fa-chevron-down text-muted" data-bs-toggle="collapse" data-bs-target="#studentSubjects3002"></i>
                                </div>
                            </div>
                            <div id="studentSubjects3002" class="collapse">
                                <div class="subject-list-container">
                                    <p class="text-muted small fw-semibold mb-2 mt-3">SUBJECT WISE LAB MANUAL STATUS (3rd Year):</p>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">IS</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">CHASM</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                    <div class="subject-item d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="status-icon approved"><i class="fa-solid fa-check"></i></div>
                                            <div><span class="fw-bold text-dark">ADDVC</span></div>
                                        </div>
                                        <span class="badge-status bg-success bg-opacity-10 text-success">Approved ✅</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!--            ADD STUDENT MODAL               -->
<!-- ========================================== -->
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
    // --- Dynamic Modal Selection Logic ---
    function showBatches(year, className, btnElement) {
        let containers = document.querySelectorAll('.batch-container-' + year);
        containers.forEach(el => el.classList.add('d-none'));
        document.getElementById('students-container-' + year).classList.add('d-none');
        document.getElementById('divider-' + year).classList.add('d-none');

        document.getElementById('batches-' + year + '-' + className).classList.remove('d-none');

        let classBtns = document.querySelectorAll('.class-btn-' + year);
        classBtns.forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });
        btnElement.classList.remove('btn-outline-primary');
        btnElement.classList.add('btn-primary');
        
        let batchBtns = document.querySelectorAll('.batch-btn-' + year);
        batchBtns.forEach(b => b.classList.remove('active-batch'));
    }

    function showStudents(year, batchName, btnElement) {
        document.getElementById('divider-' + year).classList.remove('d-none');
        document.getElementById('students-container-' + year).classList.remove('d-none');
        
        document.getElementById('student-title-' + year).innerText = "Students of Batch " + batchName;

        let studentGroups = document.querySelectorAll('.student-group-' + year);
        studentGroups.forEach(el => el.classList.add('d-none'));

        let selectedGroup = document.getElementById('students-' + year + '-' + batchName);
        if(selectedGroup) {
            selectedGroup.classList.remove('d-none');
        }

        let batchBtns = document.querySelectorAll('.batch-btn-' + year);
        batchBtns.forEach(b => b.classList.remove('active-batch'));
        btnElement.classList.add('active-batch');
    }

    function resetSelections(year) {
        document.querySelectorAll('.batch-container-' + year).forEach(el => el.classList.add('d-none'));
        document.getElementById('students-container-' + year).classList.add('d-none');
        document.getElementById('divider-' + year).classList.add('d-none');
        
        document.querySelectorAll('.class-btn-' + year).forEach(btn => {
            btn.classList.add('btn-primary');
            btn.classList.remove('btn-outline-primary');
        });
        
        document.querySelectorAll('.batch-btn-' + year).forEach(b => b.classList.remove('active-batch'));
    }
</script>

<?php
include 'footer.php';
?>