<!DOCTYPE html>
<html lang="gu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Lab Tracker - K.D. Polytechnic Patan</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
      * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;        
      }

      body {
        background-color: #f8fafc;
        position: relative;
        min-height: 100vh;
      }

      /* --- Background Watermark --- */
      body::before {
        content: "K.D. POLYTECHNIC PATAN";
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-25deg);
        font-size: 3.8rem;
        font-weight: 800;
        color: rgba(0, 0, 0, 0.04);
        white-space: nowrap;
        pointer-events: none;
        z-index: 0;
      }

      /* Custom Colors for Dynamic Logic */
      .progress-green { background-color: #22c55e !important; } /* >= 70% */
      .progress-yellow { background-color: #eab308 !important; } /* 50% - 69% */
      .progress-red { background-color: #ef4444 !important; }    /* < 50% */
    </style>
</head>

<body class="text-slate-800 min-h-screen">

  <?php
    // Student & Lab Data Variables
    $student_name = "Rahul Patel";
    $student_id = "#ST-8092";
    $total_labs = 12;
    $completed_labs = 9; // E.g., 9 Completed

    // Dynamic Calculations
    $pending_labs = $total_labs - $completed_labs;
    $percentage = round(($completed_labs / $total_labs) * 100);

    // 3-Color Condition Logic
    if ($percentage >= 70) {
        $color_class = "progress-green";
    } else if ($percentage >= 50) {
        $color_class = "progress-yellow";
    } else {
        $color_class = "progress-red";
    }
  ?>

    <!-- Navbar Header -->
    <nav class="bg-indigo-600 text-white p-4 shadow-lg sticky top-0 z-50">
        <div class="max-w-5xl mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold flex items-center gap-2">
                <i data-lucide="book-open"></i> Digital Lab Tracker
            </h1>
            <div class="flex items-center gap-3">
                <span class="bg-indigo-800 text-indigo-100 text-xs px-3 py-1 rounded-full font-semibold">
                    K.D. Polytechnic Patan
                </span>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="max-w-5xl mx-auto p-6 relative z-10 space-y-6">

        <!-- Progress Overview Card -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-lg font-bold text-indigo-600 flex items-center gap-2">
                        <i data-lucide="user-check"></i> Student Progress Tracker
                    </h2>
                    <p class="text-sm text-slate-500 font-medium mt-1">
                        Student: <span class="text-slate-800 font-semibold"><?php echo $student_name; ?></span>
                    </p>
                </div>
                <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-lg">
                    ID: <?php echo $student_id; ?>
                </span>
            </div>
            
            <!-- Dynamic 3-Color Progress Bar -->
            <div class="mb-6">
                <div class="flex justify-between text-sm font-semibold mb-2">
                    <span class="text-slate-600">Lab Completion Rate</span>
                    <span class="text-indigo-600 font-bold"><?php echo $percentage; ?>%</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-4 p-0.5 border border-slate-200 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 <?php echo $color_class; ?>" style="width: <?php echo $percentage; ?>%;"></div>
                </div>
            </div>

            <!-- Stats Badges -->
            <div class="grid grid-cols-3 gap-4 text-center">
                <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl">
                    <p class="text-xs text-blue-600 font-semibold uppercase tracking-wider">Total Labs</p>
                    <p class="text-2xl font-bold text-blue-800"><?php echo $total_labs; ?></p>
                </div>
                <div class="p-3 bg-emerald-50 border border-emerald-100 rounded-xl">
                    <p class="text-xs text-emerald-600 font-semibold uppercase tracking-wider">Completed</p>
                    <p class="text-2xl font-bold text-emerald-800"><?php echo $completed_labs; ?></p>
                </div>
                <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl">
                    <p class="text-xs text-amber-600 font-semibold uppercase tracking-wider">Pending</p>
                    <p class="text-2xl font-bold text-amber-800"><?php echo $pending_labs; ?></p>
                </div>
            </div>
        </div>

        <!-- Experiment Submission List -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="text-md font-bold text-slate-700 mb-4 flex items-center gap-2">
                <i data-lucide="file-text"></i> Experiment Checklist & Status
            </h3>
            <div class="space-y-3">
                <div class="p-3 bg-slate-50 rounded-xl flex justify-between items-center border border-slate-100">
                    <div>
                        <p class="font-medium text-slate-800 text-sm">Exp 01: Basic Circuit Breadboard Assembly</p>
                        <p class="text-xs text-slate-400">Submitted on: Aug 10 • Score: 10/10</p>
                    </div>
                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 text-xs rounded-lg font-semibold">Verified</span>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl flex justify-between items-center border border-slate-100">
                    <div>
                        <p class="font-medium text-slate-800 text-sm">Exp 02: Microcontroller Interfacing & LED Matrix</p>
                        <p class="text-xs text-slate-400">Submitted on: Aug 14 • Score: 9/10</p>
                    </div>
                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 text-xs rounded-lg font-semibold">Verified</span>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl flex justify-between items-center border border-slate-100">
                    <div>
                        <p class="font-medium text-slate-800 text-sm">Exp 03: Sensor Calibration & Data Logging</p>
                        <p class="text-xs text-slate-400">Submitted on: Yesterday</p>
                    </div>
                    <span class="px-2.5 py-1 bg-amber-100 text-amber-700 text-xs rounded-lg font-semibold">In Review</span>
                </div>
            </div>
        </div>

        <!-- Admin Quick Panel -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="text-md font-bold text-slate-700 mb-4 flex items-center gap-2">
                <i data-lucide="settings"></i> Reports & Actions
            </h3>
            <div class="p-4 border border-slate-200 rounded-xl bg-slate-50 flex justify-between items-center">
                <div>
                    <p class="text-sm font-semibold text-slate-700">Export Student Progress</p>
                    <p class="text-xs text-slate-500">Download the detailed lab performance summary as PDF.</p>
                </div>
                <button onclick="alert('Exporting PDF Report...')" class="bg-indigo-600 text-white text-xs px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition">
                    Export PDF
                </button>
            </div>
        </div>

    </main>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
    </script>
</body>
</html