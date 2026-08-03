<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Agar user login nahi hai, YA uska role 'admin' nahi hai
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Load users database
$users_file = '../users.json';
$users = [];
if (file_exists($users_file)) {
    $users = json_decode(file_get_contents($users_file), true);
    if (!is_array($users)) {
        $users = [];
    }
}

// Handle Faculty CRUD actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_faculty') {
        $name = trim($_POST['name'] ?? '');
        $user_id = trim($_POST['user_id'] ?? '');
        $password = trim($_POST['password'] ?? 'faculty123');
        $designation = trim($_POST['designation'] ?? 'Assistant Professor');
        $department = trim($_POST['department'] ?? 'CE');
        $email = trim($_POST['email'] ?? ($user_id . '@uni.edu'));
        $phone = trim($_POST['phone'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        $joined_date = trim($_POST['joined_date'] ?? date('M Y'));
        $subjects_str = trim($_POST['subjects'] ?? '');
        
        $subjects = array_filter(array_map('trim', explode(',', $subjects_str)));
        
        // Validation
        $exists = false;
        foreach ($users as $u) {
            if ($u['user_id'] === $user_id) {
                $exists = true;
                break;
            }
        }
        
        if (empty($name) || empty($user_id)) {
            $_SESSION['error_message'] = "Name and Employee ID are required!";
        } elseif ($exists) {
            $_SESSION['error_message'] = "A user with Employee ID '$user_id' already exists!";
        } else {
            // Find max id
            $max_id = 1;
            foreach ($users as $u) {
                if (isset($u['id']) && $u['id'] > $max_id) {
                    $max_id = $u['id'];
                }
            }
            $new_id = $max_id + 1;
            
            $new_faculty = [
                "id" => $new_id,
                "name" => $name,
                "user_id" => $user_id,
                "password" => $password,
                "role" => "faculty",
                "email" => $email,
                "phone" => $phone,
                "designation" => $designation,
                "department" => $department,
                "status" => $status,
                "joined_date" => $joined_date,
                "subjects" => $subjects
            ];
            
            $users[] = $new_faculty;
            if (file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT))) {
                $_SESSION['success_message'] = "Faculty '$name' added successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to save to users.json!";
            }
        }
        header("Location: Faculty Mgmt.php");
        exit();
    }
    
    if ($_POST['action'] === 'edit_faculty') {
        $original_user_id = trim($_POST['original_user_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $user_id = trim($_POST['user_id'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $designation = trim($_POST['designation'] ?? 'Assistant Professor');
        $department = trim($_POST['department'] ?? 'CE');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        $joined_date = trim($_POST['joined_date'] ?? '');
        $subjects_str = trim($_POST['subjects'] ?? '');
        
        $subjects = array_filter(array_map('trim', explode(',', $subjects_str)));
        
        if (empty($name) || empty($user_id) || empty($original_user_id)) {
            $_SESSION['error_message'] = "Name and Employee ID are required!";
        } else {
            $updated = false;
            foreach ($users as &$u) {
                if ($u['user_id'] === $original_user_id) {
                    $u['name'] = $name;
                    $u['user_id'] = $user_id;
                    if (!empty($password)) {
                        $u['password'] = $password;
                    }
                    $u['designation'] = $designation;
                    $u['department'] = $department;
                    $u['email'] = $email;
                    $u['phone'] = $phone;
                    $u['status'] = $status;
                    if (!empty($joined_date)) {
                        $u['joined_date'] = $joined_date;
                    }
                    $u['subjects'] = $subjects;
                    $updated = true;
                    break;
                }
            }
            
            if ($updated) {
                if (file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT))) {
                    $_SESSION['success_message'] = "Faculty '$name' updated successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to save to users.json!";
                }
            } else {
                $_SESSION['error_message'] = "Faculty member not found!";
            }
        }
        header("Location: Faculty Mgmt.php");
        exit();
    }
    
    if ($_POST['action'] === 'delete_faculty') {
        $user_id = trim($_POST['user_id'] ?? '');
        if (empty($user_id)) {
            $_SESSION['error_message'] = "User ID is required to delete!";
        } else {
            $initial_count = count($users);
            $users = array_filter($users, function($u) use ($user_id) {
                return !($u['role'] === 'faculty' && $u['user_id'] === $user_id);
            });
            $users = array_values($users); // reindex
            
            if (count($users) < $initial_count) {
                if (file_put_contents($users_file, json_encode($users, JSON_PRETTY_PRINT))) {
                    $_SESSION['success_message'] = "Faculty member removed successfully!";
                } else {
                    $_SESSION['error_message'] = "Failed to save to users.json!";
                }
            } else {
                $_SESSION['error_message'] = "Faculty member not found!";
            }
        }
        header("Location: Faculty Mgmt.php");
        exit();
    }
}

include 'header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">👨‍🏫 Faculty & Staff Management</h4>
        <p class="text-muted small mb-0">Manage teaching staff, lab assistants, and department roles.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-light border shadow-sm text-secondary" onclick="exportFacultyCSV()">
            <i class="fa-solid fa-file-export me-1"></i> Export CSV
        </button>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
            <i class="fa-solid fa-plus me-1"></i> Add New Faculty
        </button>
    </div>
</div>

<!-- Main Content Card -->
<div class="content-card border-0 shadow-sm">
    
    <!-- Advanced Search & Filters -->
    <div class="row g-3 align-items-center mb-4">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-0 py-2">
                    <i class="fa-solid fa-magnifying-glass text-muted"></i>
                </span>
                <input type="text" id="facultySearchInput" class="form-control bg-light border-0 shadow-none py-2" placeholder="Search by name, employee ID, or email..." oninput="filterFacultyTable()">
            </div>
        </div>
        <div class="col-md-7 d-flex justify-content-md-end gap-2">
            <select id="facultyDeptFilter" class="form-select bg-light border-0 shadow-none w-auto text-muted font-sm py-2" onchange="filterFacultyTable()">
                <option value="">All Departments</option>
                <option value="CE">Computer Engineering (CE)</option>
                <option value="IT">Information Tech. (IT)</option>
                <option value="ME">Mechanical Eng. (ME)</option>
            </select>
            <select id="facultyStatusFilter" class="form-select bg-light border-0 shadow-none w-auto text-muted font-sm py-2" onchange="filterFacultyTable()">
                <option value="">Status: All</option>
                <option value="active">🟢 Active</option>
                <option value="leave">🟡 On Leave</option>
                <option value="inactive">🔴 Inactive</option>
            </select>
        </div>
    </div>

    <!-- Professional Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
            <thead class="table-light text-muted">
                <tr>
                    <th class="fw-semibold pb-3">Faculty Profile</th>
                    <th class="fw-semibold pb-3">Emp ID</th>
                    <th class="fw-semibold pb-3">Designation / Dept.</th>
                    <th class="fw-semibold pb-3">Contact Information</th>
                    <th class="fw-semibold pb-3">Status</th>
                    <th class="fw-semibold text-end pb-3">Actions</th>
                </tr>
            </thead>
            <tbody id="facultyTableBody" class="border-top-0">
                <?php
                $faculty_users = array_filter($users, function($u) {
                    return isset($u['role']) && $u['role'] === 'faculty';
                });
                $faculty_count = count($faculty_users);
                
                if ($faculty_count === 0):
                ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-users-slash display-6 mb-3 d-block text-secondary opacity-50"></i>
                            <h6 class="fw-bold mb-1">No Faculty Members Found</h6>
                            <p class="small text-muted mb-0">Click "Add New Faculty" to register a new teaching staff member.</p>
                        </td>
                    </tr>
                <?php
                else:
                    foreach ($faculty_users as $f):
                        $f_name = $f['name'] ?? 'Unknown';
                        $f_id = $f['user_id'] ?? '';
                        $f_designation = $f['designation'] ?? 'Assistant Professor';
                        $f_dept = $f['department'] ?? 'CE';
                        $f_email = $f['email'] ?? ($f_id . '@uni.edu');
                        $f_phone = $f['phone'] ?? '+91 98765 43210';
                        $f_status = $f['status'] ?? 'active';
                        $f_joined = $f['joined_date'] ?? 'Aug 2020';
                        $f_subjects = isset($f['subjects']) ? implode(', ', $f['subjects']) : '';
                        
                        // Status Badge styling
                        $status_badge_class = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                        $status_dot_class = 'bg-success';
                        $status_text = 'Active';
                        
                        if ($f_status === 'leave') {
                            $status_badge_class = 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25';
                            $status_dot_class = 'bg-warning';
                            $status_text = 'On Leave';
                        } elseif ($f_status === 'inactive') {
                            $status_badge_class = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                            $status_dot_class = 'bg-danger';
                            $status_text = 'Inactive';
                        }
                ?>
                        <tr data-name="<?php echo htmlspecialchars(strtolower($f_name)); ?>"
                            data-empid="<?php echo htmlspecialchars(strtolower($f_id)); ?>"
                            data-email="<?php echo htmlspecialchars(strtolower($f_email)); ?>"
                            data-dept="<?php echo htmlspecialchars($f_dept); ?>"
                            data-status="<?php echo htmlspecialchars($f_status); ?>">
                            <td class="py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="position-relative">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($f_name); ?>&background=0f172a&color=fff&bold=true" class="rounded-circle shadow-sm" width="45" alt="Faculty">
                                        <span class="position-absolute bottom-0 end-0 p-1 <?php echo $status_dot_class; ?> border border-white rounded-circle"></span>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($f_name); ?></h6>
                                        <small class="text-primary fw-medium">Joined: <?php echo htmlspecialchars($f_joined); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1"><?php echo htmlspecialchars($f_id); ?></span></td>
                            <td>
                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($f_designation); ?></div>
                                <small class="text-muted">
                                    <?php
                                    if ($f_dept === 'CE') echo 'Computer Engineering (CE)';
                                    elseif ($f_dept === 'IT') echo 'Information Tech. (IT)';
                                    elseif ($f_dept === 'ME') echo 'Mechanical Eng. (ME)';
                                    else echo htmlspecialchars($f_dept);
                                    ?>
                                </small>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <a href="mailto:<?php echo htmlspecialchars($f_email); ?>" class="text-decoration-none text-muted small"><i class="fa-solid fa-envelope me-2 text-secondary"></i><?php echo htmlspecialchars($f_email); ?></a>
                                    <a href="tel:<?php echo htmlspecialchars($f_phone); ?>" class="text-decoration-none text-muted small"><i class="fa-solid fa-phone me-2 text-secondary"></i><?php echo htmlspecialchars($f_phone); ?></a>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo $status_badge_class; ?> px-3 py-2 rounded-pill"><?php echo $status_text; ?></span>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light text-muted border-0 shadow-none" type="button" data-bs-toggle="dropdown">
                                        <i class="fa-solid fa-ellipsis-vertical px-1"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                        <li>
                                            <a class="dropdown-item small" href="#" onclick="openEditFacultyModal(<?php echo htmlspecialchars(json_encode([
                                                'name' => $f_name,
                                                'user_id' => $f_id,
                                                'password' => $f['password'] ?? '',
                                                'designation' => $f_designation,
                                                'department' => $f_dept,
                                                'email' => $f_email,
                                                'phone' => $f_phone,
                                                'status' => $f_status,
                                                'joined_date' => $f_joined,
                                                'subjects' => $f_subjects
                                            ])); ?>); return false;">
                                                <i class="fa-regular fa-pen-to-square me-2 text-muted"></i> Edit Details
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item small text-danger" href="#" onclick="deleteFaculty('<?php echo htmlspecialchars($f_id); ?>', '<?php echo htmlspecialchars($f_name); ?>'); return false;">
                                                <i class="fa-solid fa-trash-can me-2"></i> Remove Faculty
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                <?php
                    endforeach;
                endif;
                ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
        <p class="text-muted small mb-0" id="facultyShowingCount">Showing <strong><?php echo $faculty_count; ?></strong> of <strong><?php echo $faculty_count; ?></strong> entries</p>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item disabled"><a class="page-link text-muted border-0" href="#">Previous</a></li>
                <li class="page-item active"><a class="page-link border-0 shadow-sm" href="#">1</a></li>
                <li class="page-item disabled"><a class="page-link text-muted border-0" href="#">Next</a></li>
            </ul>
        </nav>
    </div>

</div>

<!-- ADD FACULTY MODAL -->
<div class="modal fade" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="addFacultyModalLabel"><i class="fa-solid fa-chalkboard-user me-2"></i>Add New Faculty</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="Faculty Mgmt.php" method="POST">
                <input type="hidden" name="action" value="add_faculty">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Full Name</label>
                        <input type="text" name="name" class="form-control shadow-none" placeholder="e.g. Dr. Amit Patel" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Employee ID / Username</label>
                            <input type="text" name="user_id" class="form-control shadow-none" placeholder="e.g. FAC-04" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Password</label>
                            <input type="password" name="password" class="form-control shadow-none" placeholder="Default: faculty123" value="faculty123" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Designation</label>
                            <select name="designation" class="form-select shadow-none">
                                <option value="Head of Department">Head of Department</option>
                                <option value="Assistant Professor" selected>Assistant Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Senior Lab Assistant">Senior Lab Assistant</option>
                                <option value="Lab Assistant">Lab Assistant</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Department</label>
                            <select name="department" class="form-select shadow-none">
                                <option value="CE">Computer Engineering (CE)</option>
                                <option value="IT">Information Tech. (IT)</option>
                                <option value="ME">Mechanical Eng. (ME)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Email Address</label>
                        <input type="email" name="email" class="form-control shadow-none" placeholder="e.g. amit.patel@uni.edu">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Phone Number</label>
                        <input type="tel" name="phone" class="form-control shadow-none" placeholder="e.g. +91 98765 43210">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Status</label>
                            <select name="status" class="form-select shadow-none">
                                <option value="active" selected>Active</option>
                                <option value="leave">On Leave</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Joined Date</label>
                            <input type="text" name="joined_date" class="form-control shadow-none" placeholder="e.g. Aug 2026" value="<?php echo date('M Y'); ?>">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-muted">Assigned Subjects (Comma Separated)</label>
                        <input type="text" name="subjects" class="form-control shadow-none" placeholder="e.g. Database Systems, Web Development">
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary shadow-sm"><i class="fa-solid fa-save me-1"></i>Save Faculty</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT FACULTY MODAL -->
<div class="modal fade" id="editFacultyModal" tabindex="-1" aria-labelledby="editFacultyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="editFacultyModalLabel"><i class="fa-solid fa-user-pen me-2"></i>Edit Faculty Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="Faculty Mgmt.php" method="POST">
                <input type="hidden" name="action" value="edit_faculty">
                <input type="hidden" id="edit_original_user_id" name="original_user_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Full Name</label>
                        <input type="text" id="edit_name" name="name" class="form-control shadow-none" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Employee ID / Username</label>
                            <input type="text" id="edit_user_id" name="user_id" class="form-control shadow-none" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Password (Leave blank to keep same)</label>
                            <input type="password" name="password" class="form-control shadow-none" placeholder="Enter new password">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Designation</label>
                            <select id="edit_designation" name="designation" class="form-select shadow-none">
                                <option value="Head of Department">Head of Department</option>
                                <option value="Assistant Professor">Assistant Professor</option>
                                <option value="Associate Professor">Associate Professor</option>
                                <option value="Senior Lab Assistant">Senior Lab Assistant</option>
                                <option value="Lab Assistant">Lab Assistant</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Department</label>
                            <select id="edit_department" name="department" class="form-select shadow-none">
                                <option value="CE">Computer Engineering (CE)</option>
                                <option value="IT">Information Tech. (IT)</option>
                                <option value="ME">Mechanical Eng. (ME)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Email Address</label>
                        <input type="email" id="edit_email" name="email" class="form-control shadow-none">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Phone Number</label>
                        <input type="tel" id="edit_phone" name="phone" class="form-control shadow-none">
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Status</label>
                            <select id="edit_status" name="status" class="form-select shadow-none">
                                <option value="active">Active</option>
                                <option value="leave">On Leave</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Joined Date</label>
                            <input type="text" id="edit_joined_date" name="joined_date" class="form-control shadow-none">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold text-muted">Assigned Subjects (Comma Separated)</label>
                        <input type="text" id="edit_subjects" name="subjects" class="form-control shadow-none">
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success shadow-sm"><i class="fa-solid fa-check me-1"></i>Update Faculty</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // --- FACULTY DIRECTORY FUNCTIONS ---

    // Dynamic Faculty Real-time search & filter
    function filterFacultyTable() {
        const searchVal = document.getElementById('facultySearchInput').value.toLowerCase().trim();
        const deptVal = document.getElementById('facultyDeptFilter').value;
        const statusVal = document.getElementById('facultyStatusFilter').value;
        
        const rows = document.querySelectorAll('#facultyTableBody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            if (row.id === 'facultyNoResultsRow' || (row.cells.length === 1 && row.cells[0].colSpan === 6)) {
                return;
            }
            
            const name = row.getAttribute('data-name') || '';
            const empid = row.getAttribute('data-empid') || '';
            const email = row.getAttribute('data-email') || '';
            const dept = row.getAttribute('data-dept') || '';
            const status = row.getAttribute('data-status') || '';
            
            const matchesSearch = name.includes(searchVal) || empid.includes(searchVal) || email.includes(searchVal);
            const matchesDept = !deptVal || dept === deptVal;
            const matchesStatus = !statusVal || status === statusVal;
            
            if (matchesSearch && matchesDept && matchesStatus) {
                row.style.setProperty('display', '', 'important');
                visibleCount++;
            } else {
                row.style.setProperty('display', 'none', 'important');
            }
        });
        
        let noResultsRow = document.getElementById('facultyNoResultsRow');
        if (visibleCount === 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.id = 'facultyNoResultsRow';
                noResultsRow.innerHTML = `
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="fa-solid fa-magnifying-glass-minus display-6 mb-3 d-block text-secondary opacity-50"></i>
                        <h6 class="fw-bold mb-1">No Matching Results</h6>
                        <p class="small text-muted mb-0">Try adjusting your search keywords or filters.</p>
                    </td>
                `;
                document.getElementById('facultyTableBody').appendChild(noResultsRow);
            } else {
                noResultsRow.style.display = '';
            }
        } else if (noResultsRow) {
            noResultsRow.style.display = 'none';
        }
        
        const countText = document.getElementById('facultyShowingCount');
        if (countText) {
            countText.innerHTML = `Showing <strong>${visibleCount}</strong> entries`;
        }
    }

    // Open Edit Faculty Modal & pre-fill the form with selected user details
    function openEditFacultyModal(f) {
        document.getElementById('edit_original_user_id').value = f.user_id;
        document.getElementById('edit_name').value = f.name;
        document.getElementById('edit_user_id').value = f.user_id;
        document.getElementById('edit_designation').value = f.designation;
        document.getElementById('edit_department').value = f.department;
        document.getElementById('edit_email').value = f.email;
        document.getElementById('edit_phone').value = f.phone;
        document.getElementById('edit_status').value = f.status;
        document.getElementById('edit_joined_date').value = f.joined_date;
        document.getElementById('edit_subjects').value = f.subjects;
        
        const editModal = new bootstrap.Modal(document.getElementById('editFacultyModal'));
        editModal.show();
    }

    // Submit form POST request to remove faculty member
    function deleteFaculty(userId, name) {
        if (confirm(`Are you sure you want to remove ${name} from the faculty registry? This action is permanent.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'Faculty Mgmt.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete_faculty';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'user_id';
            idInput.value = userId;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Generate and download a CSV of current visible faculty list
    function exportFacultyCSV() {
        const rows = document.querySelectorAll('#facultyTableBody tr');
        let csvContent = "data:text/csv;charset=utf-8,Name,Employee ID,Designation,Department,Email,Phone,Status,Joined Date,Subjects\n";
        
        let exportCount = 0;
        rows.forEach(row => {
            if (row.id === 'facultyNoResultsRow' || (row.cells.length === 1 && row.cells[0].colSpan === 6)) {
                return;
            }
            if (row.style.display === 'none') {
                return;
            }
            
            const name = row.querySelector('h6').textContent.trim();
            const empid = row.cells[1].querySelector('span').textContent.trim();
            const designation = row.cells[2].querySelector('.fw-semibold').textContent.trim();
            const dept = row.getAttribute('data-dept');
            const email = row.querySelector('a[href^="mailto:"]').textContent.trim();
            const phone = row.querySelector('a[href^="tel:"]').textContent.trim();
            const status = row.getAttribute('data-status');
            const joined = row.querySelector('small.text-primary').textContent.replace('Joined: ', '').trim();
            
            const editLink = row.querySelector('a[onclick*="openEditFacultyModal"]');
            let subjects = "";
            if (editLink) {
                const match = editLink.getAttribute('onclick').match(/openEditFacultyModal\((.*)\);/);
                if (match && match[1]) {
                    try {
                        const escapedJson = match[1].replace(/&quot;/g, '"');
                        const data = JSON.parse(escapedJson);
                        subjects = data.subjects || "";
                    } catch (e) {
                        console.error("CSV subject parse error: ", e);
                    }
                }
            }
            
            const rowData = [
                `"${name.replace(/"/g, '""')}"`,
                `"${empid.replace(/"/g, '""')}"`,
                `"${designation.replace(/"/g, '""')}"`,
                `"${dept.replace(/"/g, '""')}"`,
                `"${email.replace(/"/g, '""')}"`,
                `"${phone.replace(/"/g, '""')}"`,
                `"${status.replace(/"/g, '""')}"`,
                `"${joined.replace(/"/g, '""')}"`,
                `"${subjects.replace(/"/g, '""')}"`
            ];
            
            csvContent += rowData.join(",") + "\n";
            exportCount++;
        });
        
        if (exportCount === 0) {
            alert("No faculty records found to export.");
            return;
        }
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "faculty_directory.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>

<?php
include 'footer.php';
?>
