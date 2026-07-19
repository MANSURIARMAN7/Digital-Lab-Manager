?php
include("../includes/db.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Tracker | Digital Lab Manual</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/expense.css">
</head>

<body>

<div class="container-fluid py-4">

    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Expense Tracker</h2>
            <p class="text-muted mb-0">
                Track and manage all laboratory expenses.
            </p>
        </div>

        <button class="btn btn-primary">
            <i class="bi bi-plus-circle"></i>
            Add Expense
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4">

        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 expense-card">
                <div class="card-body">

                    <h6 class="text-muted">
                        Total Expenses
                    </h6>

                    <h3 class="fw-bold mt-2">
                        ₹ 45,800
                    </h3>

                    <small class="text-success">
                        +12% this month
                    </small>

                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 expense-card">
                <div class="card-body">

                    <h6 class="text-muted">
                        Pending Requests
                    </h6>

                    <h3 class="fw-bold text-warning mt-2">
                        08
                    </h3>

                    <small class="text-warning">
                        Waiting for approval
                    </small>

                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 expense-card">
                <div class="card-body">

                    <h6 class="text-muted">
                        Approved
                    </h6>

                    <h3 class="fw-bold text-success mt-2">
                        31
                    </h3>

                    <small class="text-success">
                        Successfully Approved
                    </small>

                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card shadow-sm border-0 expense-card">
                <div class="card-body">

                    <h6 class="text-muted">
                        Rejected
                    </h6>

                    <h3 class="fw-bold text-danger mt-2">
                        04
                    </h3>

                    <small class="text-danger">
                        Rejected Requests
                    </small>

                </div>
            </div>
        </div>

    </div>

    <!-- Search & Filter -->
    <div class="card shadow-sm border-0 mt-4">

        <div class="card-body">

            <div class="row">

                <div class="col-md-4 mb-3">
                    <input type="text"
                           class="form-control"
                           placeholder="Search Expense...">
                </div>

                <div class="col-md-3 mb-3">

                    <select class="form-select">

                        <option>All Category</option>
                   …<option>Equipment</option>
                        <option>Stationery</option>
                        <option>Maintenance</option>
                        <option>Software</option>

                    </select>

                </div>

                <div class="col-md-3 mb-3">

                    <select class="form-select">

                        <option>All Status</option>
                        <option>Approved</option>
                        <option>Pending</option>
                        <option>Rejected</option>

                    </select>

                </div>

                <div class="col-md-2 mb-3">

                    <button class="btn btn-success w-100">
                        Search
                    </button>

                </div>

            </div>

        </div>

    </div>

    <!-- Expense Table -->
    <div class="card shadow-sm border-0 mt-4">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Expense List
            </h5>

        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                <tr>

                    <th>ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>

                </tr>

                </thead>

                <tbody>

                <!-- Database Data Next Part -->

                </tbody>

            </table>

        </div>

    </div>

</div>

<script src="js/expense.js"></script>

</body>
</html>
