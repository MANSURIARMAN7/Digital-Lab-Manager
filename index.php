<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K.D. Polytechnic - Digital Lab Manager</title>
    
    <!-- Bootstrap, FontAwesome & Premium Font -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --primary: #4338ca; 
            --primary-hover: #3730a3;
            --secondary: #3b82f6;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --transition-bounce: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-body); 
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* 🌐 GLASSMORPHISM NAVBAR */
        .navbar-glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            padding: 15px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .nav-logo { display: flex; align-items: center; text-decoration: none; gap: 12px; }
        .nav-logo img { width: 45px; height: 45px; border-radius: 50%; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border: 2px solid white; }
        .nav-logo h2 { font-size: 20px; font-weight: 800; margin: 0; color: var(--text-main); letter-spacing: 0.5px; transition: var(--transition-bounce); }
        .nav-logo:hover h2 { color: var(--primary); }

        .btn-nav-login {
            background: rgba(67, 56, 202, 0.1); color: var(--primary); font-weight: 700; padding: 10px 24px; border-radius: 50px; text-decoration: none; transition: var(--transition-bounce); border: 1px solid rgba(67, 56, 202, 0.2); display: flex; align-items: center; gap: 8px;
        }
        .btn-nav-login:hover { background: var(--primary); color: white; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(67, 56, 202, 0.3); }

        /* 🚀 HERO SECTION */
        .hero-section {
            padding: 160px 0 100px 0;
            background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.1), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(67, 56, 202, 0.05), transparent 40%);
            min-height: 80vh;
            display: flex;
            align-items: center;
        }

        .hero-title { font-size: 52px; font-weight: 800; line-height: 1.2; color: var(--text-main); margin-bottom: 20px; animation: fadeUp 0.8s forwards; }
        .text-gradient { background: linear-gradient(135deg, #4f46e5, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .hero-subtitle { font-size: 18px; color: var(--text-muted); font-weight: 500; line-height: 1.6; margin-bottom: 40px; animation: fadeUp 1s forwards; }

        .btn-hero {
            background: linear-gradient(135deg, #4f46e5, #3b82f6); color: white; border: none; font-weight: 700; padding: 16px 32px; border-radius: 12px; font-size: 16px; box-shadow: 0 10px 25px rgba(67, 56, 202, 0.4); transition: var(--transition-bounce); display: inline-flex; align-items: center; gap: 10px; text-decoration: none; animation: fadeUp 1.2s forwards;
        }
        .btn-hero:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(67, 56, 202, 0.5); color: white; }

        .hero-graphics { position: relative; animation: float 6s ease-in-out infinite; }
        .hero-card-mockup { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); border: 1px solid rgba(226, 232, 240, 0.8); position: relative; z-index: 2; }
        .hero-blob { position: absolute; width: 300px; height: 300px; background: linear-gradient(135deg, #60a5fa, #818cf8); border-radius: 50%; filter: blur(60px); top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 1; opacity: 0.5; }

        /* ✨ FEATURES SECTION */
        .features-section { padding: 80px 0 100px 0; background: white; }
        
        .feature-card {
            background: var(--bg-body); border-radius: 20px; padding: 40px 30px; border: 1px solid rgba(226, 232, 240, 0.8); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: var(--transition-bounce); height: 100%; text-align: center;
        }
        .feature-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.08); border-color: #cbd5e1; }
        
        .feature-icon {
            width: 80px; height: 80px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 25px auto; transition: var(--transition-bounce);
        }
        .feature-card:hover .feature-icon { transform: scale(1.1) rotate(5deg); }
        
        .icon-blue { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .icon-green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .icon-purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

        .feature-card h3 { font-size: 20px; font-weight: 800; color: var(--text-main); margin-bottom: 15px; }
        .feature-card p { font-size: 14.5px; color: var(--text-muted); font-weight: 500; margin: 0; line-height: 1.6; }

        /* 📋 PROFESSIONAL DARK FOOTER */
        .footer { background: #0f172a; color: #94a3b8; padding: 60px 0 30px 0; border-top: 4px solid var(--primary); }
        .footer-heading { color: #ffffff; font-weight: 800; font-size: 18px; margin-bottom: 20px; letter-spacing: 0.5px; }
        .footer p { font-size: 14px; line-height: 1.6; }
        .footer-links { list-style: none; padding: 0; margin: 0; }
        .footer-links li { margin-bottom: 12px; }
        .footer-links a { color: #94a3b8; text-decoration: none; font-size: 14px; transition: var(--transition-bounce); display: flex; align-items: center; gap: 8px;}
        .footer-links a:hover { color: var(--secondary); padding-left: 5px; }
        
        .contact-item { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 15px; font-size: 14px; }
        .contact-item i { color: var(--secondary); margin-top: 4px; font-size: 16px; }

        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.1); padding-top: 25px; margin-top: 40px; text-align: center; font-size: 13.5px; }
        .footer-bottom span { color: #ffffff; font-weight: 700; }

        /* KEYFRAMES */
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }
        @keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-20px); } 100% { transform: translateY(0px); } }

        @media (max-width: 768px) {
            .hero-title { font-size: 38px; }
            .hero-section { text-align: center; padding: 120px 0 60px 0; }
            .hero-graphics { margin-top: 50px; }
            .footer { text-align: center; }
            .contact-item { justify-content: center; }
            .footer-links a { justify-content: center; }
            .footer-links a:hover { padding-left: 0; color: var(--secondary); }
        }
    </style>
</head>

<body>

    <!-- 🌐 NAVIGATION BAR -->
    <nav class="navbar-glass">
        <div class="container d-flex justify-content-between align-items-center">
            <!-- 🔗 Linked to official KDP website -->
            <a href="https://www.kdppatan.ac.in/" target="_blank" class="nav-logo" title="Visit Official KDP Website">
                <img src="assets/images/college-logo.png" alt="KDP Logo" onerror="this.src='https://cdn-icons-png.flaticon.com/512/8074/8074800.png'">
                <h2>K.D. Polytechnic</h2>
            </a>
            
            <a href="login.php" class="btn-nav-login">
                <i class="fas fa-lock"></i> <span class="d-none d-sm-inline">Login Portal</span>
            </a>
        </div>
    </nav>

    <!-- 🚀 HERO SECTION -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <!-- Text Content -->
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <div class="badge bg-primary text-white px-3 py-2 rounded-pill mb-4 shadow-sm" style="font-weight: 700; letter-spacing: 1px;">
                        <i class="fas fa-star text-warning me-1"></i> Paperless Campus Initiative
                    </div>
                    <h1 class="hero-title">
                        Welcome to the <br>
                        <span class="text-gradient">Digital Lab Manager</span>
                    </h1>
                    <p class="hero-subtitle">
                        A smart, secure, and paperless solution for KDP students and faculties to manage lab manuals, track submissions, and evaluate performance seamlessly.
                    </p>
                    <a href="login.php" class="btn-hero">
                        Access Portal <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                
                <!-- Floating Graphics -->
                <div class="col-lg-6">
                    <div class="hero-graphics">
                        <div class="hero-blob"></div>
                        <div class="hero-card-mockup">
                            <div class="d-flex align-items-center mb-4">
                                <div style="width: 50px; height: 50px; background: #e0e7ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #4f46e5; font-size: 24px; margin-right: 15px;">
                                    <i class="fas fa-check-double"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-0">System Ready</h5>
                                    <small class="text-muted fw-bold">Live Sync Enabled</small>
                                </div>
                            </div>
                            <div style="height: 12px; background: #f1f5f9; border-radius: 6px; width: 100%; margin-bottom: 15px;"></div>
                            <div style="height: 12px; background: #f1f5f9; border-radius: 6px; width: 80%; margin-bottom: 15px;"></div>
                            <div style="height: 12px; background: #f1f5f9; border-radius: 6px; width: 90%; mb-4"></div>
                            
                            <div class="d-flex justify-content-between border-top pt-4 mt-4">
                                <div class="text-center">
                                    <h4 class="fw-bold text-dark mb-0">24/7</h4>
                                    <small class="text-muted fw-bold">Availability</small>
                                </div>
                                <div class="text-center">
                                    <h4 class="fw-bold text-dark mb-0">100%</h4>
                                    <small class="text-muted fw-bold">Paperless</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ✨ FEATURES OVERVIEW -->
    <section class="features-section">
        <div class="container">
            <div class="text-center mb-5 pb-3">
                <h2 class="fw-bold text-dark" style="font-size: 32px;">Why use Digital Lab Manager?</h2>
                <p class="text-muted fw-semibold">Modernizing the way technical education is delivered and evaluated.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon icon-blue">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h3>Digital Submissions</h3>
                        <p>Students can upload their lab manuals and assignments digitally from anywhere, eliminating paper waste and saving time.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon icon-green">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3>Smart Evaluation</h3>
                        <p>Faculties have a dedicated dashboard to review, grade, and approve student submissions with one-click feedback systems.</p>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon icon-purple">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h3>Instant Analytics</h3>
                        <p>Generate term-work reports instantly and track real-time progress for all branches dynamically via the admin panel.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 📋 PROFESSIONAL DARK FOOTER -->
    <div class="footer">
        <div class="container">
            <div class="row">
                <!-- Column 1: College Info -->
                <div class="col-lg-5 col-md-12 mb-5 mb-lg-0 pe-lg-5">
                    <h4 class="footer-heading d-flex align-items-center gap-2">
                        <i class="fas fa-graduation-cap text-primary"></i> K.D. Polytechnic
                    </h4>
                    <p>Digital Lab Manager is an initiative to digitize the manual submission and evaluation process for the Computer Engineering Department, promoting a paperless and efficient academic environment.</p>
                </div>

                <!-- Column 2: Contact Info (Authentic KD Patan details) -->
                <div class="col-lg-4 col-md-6 mb-5 mb-md-0">
                    <h4 class="footer-heading">Contact Information</h4>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>Address:</strong><br>
                            Opp. T. B. Hospital, Hemchandracharya North Gujarat University Road,<br>
                            Patan - 384265, Gujarat, India.
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone-alt"></i>
                        <div>
                            <strong>Phone:</strong><br>
                            (02766) 220419
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email:</strong><br>
                            kdp-patan-dte@gujarat.gov.in
                        </div>
                    </div>
                </div>

                <!-- Column 3: Quick Links & Legal -->
                <div class="col-lg-3 col-md-6">
                    <h4 class="footer-heading">Important Links</h4>
                    <ul class="footer-links">
                        <li>
                            <a href="https://www.kdppatan.ac.in/" target="_blank">
                                <i class="fas fa-globe"></i> Official KDP Website 
                                <i class="fas fa-external-link-alt ms-1" style="font-size: 10px;"></i>
                            </a>
                        </li>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> System Login Portal</a></li>
                        <li><a href="#"><i class="fas fa-file-contract"></i> Terms & Conditions</a></li>
                        <li><a href="#"><i class="fas fa-user-shield"></i> Privacy Policy</a></li>
                        <li><a href="#"><i class="fas fa-headset"></i> Help & Support</a></li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                &copy; <?php echo date("Y"); ?> <span>K.D. Polytechnic, Patan</span>. All Rights Reserved. <br>
                <small class="mt-2 d-block text-muted">Designed & Developed for Computer Engineering Department</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>