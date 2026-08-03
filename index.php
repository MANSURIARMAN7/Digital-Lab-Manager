<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K.D. Polytechnic - Digital Lab Manager</title>
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Link to the new CSS file -->
    <link rel="stylesheet" href="assets/css/home.css">
</head>

<body>

    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="#" class="nav-logo">
            <img src="assets/images/KDP-Logo.png" alt="KDP Logo">
            <h2>K.D. Polytechnic</h2>
        </a>
        
        <!-- Yahan path theek kar diya hai -->
        <a href="login.php" class="nav-btn">
            <i class="fas fa-user-lock"></i> Login Portal
        </a>
    </nav>

    <!-- Hero Section (Main Attraction) -->
    <section class="hero">
        <h1>Welcome to <span>Digital Lab Manager</span></h1>
        <p>A smart, secure, and paperless solution for KDP students and faculties to manage lab manuals, track submissions, and evaluate performance seamlessly.</p>
        <div class="cta-buttons">
            <!-- Yahan bhi path theek kar diya hai -->
            <a href="login.php" class="btn-primary">
                Get Started <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </section>

    <!-- Features Overview -->
    <section class="features">
        <div class="feature-card">
            <i class="fas fa-file-upload"></i>
            <h3>Digital Submissions</h3>
            <p>Students can upload their lab manuals and assignments digitally from anywhere, eliminating paper waste.</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-check-double"></i>
            <h3>Smart Evaluation</h3>
            <p>Faculties have a dedicated dashboard to review, grade, and approve student submissions with one click.</p>
        </div>
        <div class="feature-card">
            <i class="fas fa-chart-pie"></i>
            <h3>Instant Reports</h3>
            <p>Generate term-work reports and track real-time progress for all branches dynamically.</p>
        </div>
    </section>

    <!-- Footer -->
    <div class="footer">
        &copy; <?php echo date("Y"); ?> K.D. Polytechnic, Patan. All Rights Reserved.
    </div>

</body>

</html>