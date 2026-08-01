git<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Lab Manual & Expense Tracker</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans min-h-screen">

    <!-- Navbar Header -->
    <nav class="bg-indigo-600 text-white p-4 shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold flex items-center gap-2">
                <i data-lucide="book-open"></i> Digital Lab Manual & Expense Manager
            </h1>
            <div class="flex items-center gap-3">
                <span class="bg-indigo-800 text-indigo-100 text-xs px-3 py-1 rounded-full font-semibold">Branch: feature/expense</span>
                <span class="bg-green-500 text-white text-xs px-3 py-1 rounded-full font-medium flex items-center gap-1">
                    <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span> Frontend Mode
                </span>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- ================= PAGE 1: STUDENT PROGRESS (LEFT 2 COLUMNS) ================= -->
        <section class="lg:col-span-2 space-y-6">
            
            <!-- Progress Overview Card -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-indigo-600 flex items-center gap-2">
                        <i data-lucide="user-check"></i> Student Progress Tracker
                    </h2>
                    <span class="text-xs font-semibold text-slate-500">Student ID: #ST-8092</span>
                </div>
                
                <!-- Progress Bar -->
                <div class="mb-6">
                    <div class="flex justify-between text-sm font-semibold mb-2">
                        <span>Lab Completion Rate</span>
                        <span id="progressText" class="text-indigo-600">75%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-3.5 p-0.5 border border-slate-200">
                        <div id="progressBar" class="bg-indigo-600 h-2.5 rounded-full transition-all duration-500" style="width: 75%"></div>
                    </div>
                </div>

                <!-- Stats Badges -->
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl">
                        <p class="text-xs text-blue-600 font-semibold uppercase tracking-wider">Total Labs</p>
                        <p class="text-2xl font-bold text-blue-800">12</p>
                    </div>
                    <div class="p-3 bg-emerald-50 border border-emerald-100 rounded-xl">
                        <p class="text-xs text-emerald-600 font-semibold uppercase tracking-wider">Completed</p>
                        <p class="text-2xl font-bold text-emerald-800">9</p>
                    </div>
                    <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl">
                        <p class="text-xs text-amber-600 font-semibold uppercase tracking-wider">Pending</p>
                        <p class="text-2xl font-bold text-amber-800">3</p>
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

            <!-- Admin & Settings Quick Panel -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-md font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <i data-lucide="settings"></i> Admin & Module Settings
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 border border-slate-200 rounded-xl bg-slate-50">
                        <p class="text-sm font-semibold text-slate-700">Currency & Export Settings</p>
                        <p class="text-xs text-slate-500 mb-3">Set default currency and report format.</p>
                        <button onclick="alert('Exporting Report as PDF...')" class="bg-indigo-600 text-white text-xs px-3 py-1.5 rounded-lg font-medium hover:bg-indigo-700">Export PDF Report</button>
                    </div>
                    <div class="p-4 border border-slate-200 rounded-xl bg-slate-50">
                        <p class="text-sm font-semibold text-slate-700">Clear Local Cache</p>
                        <p class="text-xs text-slate-500 mb-3">Reset demo expenses data.</p>
                        <button onclick="resetData()" class="bg-red-500 text-white text-xs px-3 py-1.5 rounded-lg font-medium hover:bg-red-600">Reset Expenses</button>
                    </div>
                </div>
            </div>

        </section>

        <!-- ================= PAGE 2: EXPENSE MODULE (RIGHT COLUMN) ================= -->
        <section class="space-y-6">
            
            <!-- Expense Summary Card -->
            <div class="bg-gradient-to-br from-indigo-600 to-indigo-700 text-white p-6 rounded-2xl shadow-md">
                <p class="text-xs text-indigo-200 uppercase tracking-wider font-semibold">Total Module Expense</p>
                <h3 class="text-3xl font-extrabold mt-1" id="totalExpenseAmount">₹0.00</h3>
                <p class="text-xs text-indigo-200 mt-2">Tracked automatically from added items.</p>
            </div>

            <!-- Add Expense Form -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-md font-bold text-indigo-600 mb-4 flex items-center gap-2">
                    <i data-lucide="plus-circle"></i> Add New Expense
                </h3>
                
                <form id="expenseForm" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Item Title</label>
                        <input type="text" id="expTitle" required placeholder="e.g. Arduino UNO Board" class="w-full p-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Amount (₹)</label>
                            <input type="number" id="expAmount" required placeholder="450" class="w-full p-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Category</label>
                            <select id="expCategory" class="w-full p-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                                <option>Components</option>
                                <option>Printouts</option>
                                <option>Software</option>
                                <option>Other</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition flex justify-center items-center gap-2">
                        <i data-lucide="plus"></i> Add Expense Item
                    </button>
                </form>
            </div>

            <!-- Expense History List -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-md font-bold text-slate-700 mb-4 flex items-center gap-2">
                    <i data-lucide="history"></i> Expense History
                </h3>
                <div id="expenseList" class="space-y-3 max-h-72 overflow-y-auto pr-1">
                    <!-- Items inserted dynamically via JavaScript -->
                </div>
            </div>

        </section>

    </main>

    <!-- JavaScript logic -->
    <script>
        lucide.createIcons();

        // Load expenses from Local Storage
        let expenses = JSON.parse(localStorage.getItem('lab_expenses')) || [
            { id: 1, title: 'Lab Manual Binding & Printout', amount: 150, category: 'Printouts' },
            { id: 2, title: 'Breadboard & Connecting Wires', amount: 280, category: 'Components' }
        ];

        function renderExpenses() {
            const list = document.getElementById('expenseList');
            const totalElement = document.getElementById('totalExpenseAmount');
            list.innerHTML = '';

            let total = 0;

            if(expenses.length === 0) {
                list.innerHTML = <p class="text-xs text-slate-400 text-center py-4">No expenses added yet.</p>;
            } else {
                expenses.forEach((item, index) => {
                    total += parseFloat(item.amount);
                    const div = document.createElement('div');
                    div.className = "flex justify-between items-center p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-slate-200 transition";
                    div.innerHTML = `
                        <div>
                            <p class="font-medium text-slate-800 text-sm">${item.title}</p>
                            <span class="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded-md font-medium">${item.category}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-bold text-indigo-600 text-sm">₹${item.amount}</span>
                            <button onclick="deleteExpense(${index})" class="text-slate-400 hover:text-red-500">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    `;
                    list.appendChild(div);
                });
            }

            totalElement.innerText = ₹${total.toFixed(2)};
            localStorage.setItem('lab_expenses', JSON.stringify(expenses));
            lucide.createIcons();
        }

        // Add Expense Function
        document.getElementById('expenseForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const title = document.getElementById('expTitle').value;
            const amount = document.getElementById('expAmount').value;
            const category = document.getElementById('expCategory').value;

            expenses.unshift({ id: Date.now(), title, amount: parseFloat(amount), category });
            renderExpenses();

            // Clear inputs
            document.getElementById('expTitle').value = '';
            document.getElementById('expAmount').value = '';
        });

        // Delete Expense Function
        function deleteExpense(index) {
            expenses.splice(index, 1);
            renderExpenses();
        }

        // Reset Data
        function resetData() {
            if(confirm("Are you sure you want to clear all expenses?")) {
                expenses = [];
                renderExpenses();
            }
        }

        // Initialize Render
        renderExpenses();
    </script>
</body>
</html>