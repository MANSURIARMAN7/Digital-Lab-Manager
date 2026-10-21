<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Lab Manager - K.D. Polytechnic</title>
    
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Premium Font: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4338ca;
            --primary-light: #6366f1;
            --primary-dark: #312e81;
            --surface: #ffffff;
            --bg-body: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --radius-xl: 20px;
            --shadow-float: 0 20px 40px -10px rgba(67, 56, 202, 0.15);
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-body); color: var(--text-main); overflow-x: hidden; }

        /* 🌐 NAVBAR */
        .navbar-custom { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(226, 232, 240, 0.8); padding: 15px 0; z-index: 1000; transition: 0.3s; }
        .navbar-custom .navbar-brand { display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 20px; color: var(--primary-dark); }
        .navbar-custom .navbar-brand img { width: 45px; height: 45px; border-radius: 50%; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        .btn-login-nav { background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; border: none; padding: 10px 25px; border-radius: 50px; font-weight: 700; letter-spacing: 0.5px; transition: var(--transition-bounce); box-shadow: 0 4px 15px rgba(67, 56, 202, 0.3); }
        .btn-login-nav:hover { transform: translateY(-2px); color: white; box-shadow: 0 8px 25px rgba(67, 56, 202, 0.4); }

        /* 🚀 HERO SECTION */
        .hero-section { position: relative; padding: 120px 0 80px; background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.1), transparent 40%), radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.05), transparent 40%); overflow: hidden; min-height: 85vh; display: flex; align-items: center;}
        
        .hero-title { font-size: 55px; font-weight: 900; line-height: 1.1; margin-bottom: 25px; color: var(--text-main); letter-spacing: -1px; animation: fadeUp 0.8s forwards;}
        .hero-title span { background: linear-gradient(135deg, var(--primary), #10b981); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero-subtitle { font-size: 19px; font-weight: 500; color: var(--text-muted); margin-bottom: 40px; animation: fadeUp 0.8s 0.2s forwards; opacity: 0;}
        
        .hero-btn-group { animation: fadeUp 0.8s 0.4s forwards; opacity: 0; }
        .btn-hero-main { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; padding: 18px 40px; border-radius: 12px; font-size: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; transition: var(--transition-bounce); border: none; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 10px 25px rgba(67, 56, 202, 0.3);}
        .btn-hero-main:hover { transform: translateY(-5px); color: white; box-shadow: 0 15px 35px rgba(67, 56, 202, 0.4); }
        .btn-hero-outline { background: white; color: var(--primary); padding: 16px 35px; border-radius: 12px; font-size: 16px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; transition: var(--transition-bounce); border: 2px solid #e2e8f0; display: inline-flex; align-items: center; gap: 10px;}
        .btn-hero-outline:hover { background: #f8fafc; border-color: var(--primary); transform: translateY(-5px); color: var(--primary);}

        /* 💻 DASHBOARD PREVIEW MOCKUP */
        .mockup-container { position: relative; animation: fadeUp 1s 0.6s forwards; opacity: 0; perspective: 1000px;}
        .mockup-image { width: 100%; border-radius: var(--radius-xl); box-shadow: var(--shadow-float); border: 8px solid white; transform: rotateY(-5deg) rotateX(5deg); transition: var(--transition-bounce); }
        .mockup-container:hover .mockup-image { transform: rotateY(0) rotateX(0); }

        /* ✨ FEATURES SECTION */
        .features-section { padding: 100px 0; background: white; }
        .section-header { text-align: center; margin-bottom: 70px; }
        .section-header h2 { font-size: 35px; font-weight: 800; color: var(--text-main); }
        
        .feature-card { background: var(--bg-body); padding: 40px 30px; border-radius: var(--radius-xl); border: 1px solid #e2e8f0; transition: var(--transition-bounce); height: 100%; }
        .feature-card:hover { transform: translateY(-10px); background: white; box-shadow: var(--shadow-float); border-color: var(--primary-light); }
        .feature-icon { width: 65px; height: 65px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 25px; }
        
        .icon-blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .icon-green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .icon-purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

        .feature-card h4 { font-weight: 800; font-size: 20px; margin-bottom: 15px; color: var(--text-main);}
        .feature-card p { font-weight: 500; font-size: 15px; color: var(--text-muted); line-height: 1.6; margin: 0;}

        /* 📋 FOOTER */
        .footer { background: var(--primary-dark); color: white; padding: 70px 0 30px; }
        .footer-heading { font-size: 18px; font-weight: 800; margin-bottom: 25px; color: #818cf8; text-transform: uppercase; letter-spacing: 1px;}
        .footer-text { font-size: 14.5px; font-weight: 400; color: #cbd5e1; line-height: 1.8; }
        .footer-links { list-style: none; padding: 0; margin: 0; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { color: #cbd5e1; text-decoration: none; font-weight: 500; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px;}
        .footer-links a:hover { color: white; padding-left: 5px; }
        .contact-item { display: flex; gap: 15px; margin-bottom: 20px; color: #cbd5e1; font-size: 14.5px;}
        .contact-item i { color: #818cf8; font-size: 20px; margin-top: 3px;}
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.1); padding-top: 25px; margin-top: 40px; text-align: center; }

        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(40px); } 100% { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-custom fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/college-logo.png" alt="KDP Logo">
                Digital Lab Manager
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-3">
                    <li class="nav-item"><a class="nav-link fw-bold text-dark" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link fw-bold text-dark" href="#contact">Contact</a></li>
                    <li class="nav-item ms-lg-3">
                        <a href="login.php" class="btn-login-nav"><i class="fas fa-sign-in-alt me-2"></i> Portal Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="badge bg-light text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-bold mb-4 shadow-sm" style="animation: fadeUp 0.8s forwards;">
                        <i class="fas fa-certificate me-1"></i> Computer Engineering Dept.
                    </div>
                    <h1 class="hero-title">Welcome to the <br><span>Future of Labs</span></h1>
                    <p class="hero-subtitle">Experience a 100% paperless, secure, and smart lab management system designed specifically for K.D. Polytechnic.</p>
                    
                    <div class="hero-btn-group d-flex gap-3 flex-wrap">
                        <a href="login.php" class="btn-hero-main"><i class="fas fa-rocket"></i> Get Started</a>
                        <a href="#features" class="btn-hero-outline"><i class="fas fa-compass"></i> Explore Features</a>
                    </div>
                    
                    <div class="mt-5 d-flex align-items-center gap-3 text-muted fw-bold small" style="animation: fadeUp 0.8s 0.6s forwards; opacity:0;">
                        <i class="fas fa-shield-check text-success fs-5"></i> Secure & Managed by Admin
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="mockup-container">
                        <!-- Udated with comp.png -->
                        <img src="assets/images/comp.png" alt="K.D. Polytechnic Computer Dept" class="mockup-image" onerror="this.src='https://via.placeholder.com/800x550/4338ca/ffffff?text=Image+Not+Found'">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="section-header">
                <h2>Why use Digital Lab Manager?</h2>
                <p class="text-muted fw-semibold mt-2">Modernizing the way technical education is delivered and evaluated.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon icon-blue"><i class="fas fa-cloud-upload-alt"></i></div>
                        <h4>Digital Submissions</h4>
                        <p>Students can upload their lab manuals and assignments digitally from anywhere, eliminating paper waste and saving time.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon icon-green"><i class="fas fa-check-double"></i></div>
                        <h4>Smart Evaluation</h4>
                        <p>Faculties have a dedicated dashboard to review, grade (20-marks rubric), and approve student submissions with one-click feedback.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon icon-purple"><i class="fas fa-chart-pie"></i></div>
                        <h4>Instant Analytics</h4>
                        <p>Generate term-work reports instantly and track real-time progress for all branches dynamically via the admin panel.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer id="contact" class="footer">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <img src="assets/images/college-logo.png" alt="Logo" style="width: 50px; background:white; border-radius:50%; padding:2px;">
                        <h4 class="mb-0 fw-bold">K.D. Polytechnic</h4>
                    </div>
                    <p class="footer-text">Digital Lab Manager is an initiative to digitize the manual submission and evaluation process for the Computer Engineering Department, promoting a paperless academic environment.</p>
                </div>
                <div class="col-lg-4">
                    <h5 class="footer-heading">Contact Information</h5>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div><strong>Address:</strong><br>Opp. T. B. Hospital, Hemchandracharya North Gujarat University Road, Patan - 384265, Gujarat, India.</div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone-alt"></i>
                        <div><strong>Phone:</strong> (02766) 220419</div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div><strong>Email:</strong> kdp-patan-dte@gujarat.gov.in</div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <h5 class="footer-heading">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="https://www.kdppatan.ac.in/" target="_blank"><i class="fas fa-globe"></i> Official KDP Website</a></li>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> System Login Portal</a></li>
                        <li><a href="#"><i class="fas fa-shield-alt"></i> Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p class="mb-0 fw-bold">&copy; <?php echo date('Y'); ?> K.D. Polytechnic, Patan. All Rights Reserved.</p>
                <p class="text-white-50 small mt-1">Designed & Developed for Computer Engineering Department</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>