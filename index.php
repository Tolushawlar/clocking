<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>TimeTrack Pro - The Modern Way Businesses Track Time</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-white text-slate-900 font-sans antialiased">
    <!-- 1. NAVIGATION BAR -->
    <nav class="sticky top-0 z-50 w-full bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-[#3c83f6] flex items-center justify-center text-white">
                    <span class="material-symbols-outlined text-xl">schedule</span>
                </div>
                <span class="text-xl font-bold tracking-tight text-slate-900">TimeTrack Pro</span>
            </div>
            <div class="hidden md:flex items-center gap-8">
                <a class="text-sm font-medium text-slate-700 hover:text-[#3c83f6] transition-colors" href="#features">Features</a>
                <a class="text-sm font-medium text-slate-700 hover:text-[#3c83f6] transition-colors" href="#solutions">Solutions</a>
                <a class="text-sm font-medium text-slate-700 hover:text-[#3c83f6] transition-colors" href="#pricing">Pricing</a>
                <a class="text-sm font-medium text-slate-700 hover:text-[#3c83f6] transition-colors" href="#resources">Resources</a>
            </div>
            <div class="flex items-center gap-4">
                <a class="hidden sm:block text-sm font-medium text-slate-700 hover:text-slate-900" href="login.php">Login</a>
                <a class="flex items-center justify-center h-10 px-6 rounded-full bg-[#3c83f6] hover:bg-[#2563eb] text-white text-sm font-bold transition-all" href="contact.php">
                    Get Started
                </a>
            </div>
        </div>
    </nav>

    <!-- 2. HERO SECTION -->
    <section class="relative py-24 bg-gradient-to-b from-slate-50 to-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <h1 class="text-5xl md:text-6xl font-black text-slate-900 leading-tight mb-6">
                The Modern Way<br />Businesses Track Time
            </h1>
            <p class="text-xl text-slate-600 max-w-3xl mx-auto mb-10">
                Manage attendance, teams, and projects in one platform. MySQL-powered with a web-based interface designed for efficiency.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                <a class="px-8 py-4 rounded-full bg-[#3c83f6] hover:bg-[#2563eb] text-white text-lg font-bold transition-all shadow-lg" href="contact.php">
                    Get Started
                </a>
                <a class="px-8 py-4 rounded-full border-2 border-slate-300 hover:border-[#3c83f6] text-slate-700 hover:text-[#3c83f6] text-lg font-bold transition-all" href="#demo">
                    Request Demo
                </a>
            </div>
        </div>
    </section>

    <!-- 3. DASHBOARD PREVIEW SECTION -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="bg-slate-50 rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
                <!-- Dashboard Header -->
                <div class="bg-white border-b border-slate-200 p-4 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-[#3c83f6] flex items-center justify-center text-white">
                            <span class="material-symbols-outlined">dashboard</span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">Dashboard Overview</h3>
                    </div>
                    <div class="flex gap-2">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                        <div class="w-3 h-3 rounded-full bg-green-400"></div>
                    </div>
                </div>

                <!-- Dashboard Content -->
                <div class="p-8">
                    <!-- Metrics Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white rounded-xl p-6 shadow-md border border-slate-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-slate-600">Project Hours</span>
                                <span class="material-symbols-outlined text-[#E74C3C]">schedule</span>
                            </div>
                            <div class="text-3xl font-bold text-slate-900">2,450</div>
                            <div class="text-xs text-green-600 mt-1">↑ 12% from last month</div>
                        </div>
                        <div class="bg-white rounded-xl p-6 shadow-md border border-slate-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-slate-600">Staff Attendance</span>
                                <span class="material-symbols-outlined text-[#27AE60]">people</span>
                            </div>
                            <div class="text-3xl font-bold text-slate-900">94.5%</div>
                            <div class="text-xs text-green-600 mt-1">↑ 3.2% improvement</div>
                        </div>
                        <div class="bg-white rounded-xl p-6 shadow-md border border-slate-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-slate-600">Active Projects</span>
                                <span class="material-symbols-outlined text-[#F39C12]">work</span>
                            </div>
                            <div class="text-3xl font-bold text-slate-900">42</div>
                            <div class="text-xs text-slate-600 mt-1">8 completed this month</div>
                        </div>
                        <div class="bg-white rounded-xl p-6 shadow-md border border-slate-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-slate-600">Team Members</span>
                                <span class="material-symbols-outlined text-[#3498DB]">groups</span>
                            </div>
                            <div class="text-3xl font-bold text-slate-900">156</div>
                            <div class="text-xs text-green-600 mt-1">12 new this quarter</div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Bar Chart -->
                        <div class="lg:col-span-2 bg-white rounded-xl p-6 shadow-md border border-slate-200">
                            <h4 class="text-lg font-bold text-slate-900 mb-4">Weekly Activity</h4>
                            <div class="flex items-end justify-between h-48 gap-2">
                                <div class="flex-1 bg-[#27AE60] rounded-t-lg" style="height: 60%"></div>
                                <div class="flex-1 bg-[#27AE60] rounded-t-lg" style="height: 75%"></div>
                                <div class="flex-1 bg-[#27AE60] rounded-t-lg" style="height: 85%"></div>
                                <div class="flex-1 bg-[#27AE60] rounded-t-lg" style="height: 70%"></div>
                                <div class="flex-1 bg-[#27AE60] rounded-t-lg" style="height: 90%"></div>
                                <div class="flex-1 bg-[#27AE60] rounded-t-lg" style="height: 65%"></div>
                                <div class="flex-1 bg-[#27AE60] rounded-t-lg" style="height: 55%"></div>
                            </div>
                            <div class="flex justify-between text-xs text-slate-500 mt-2">
                                <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                            </div>
                        </div>

                        <!-- Donut Chart -->
                        <div class="bg-white rounded-xl p-6 shadow-md border border-slate-200">
                            <h4 class="text-lg font-bold text-slate-900 mb-4">Project Status</h4>
                            <div class="flex items-center justify-center h-48">
                                <div class="relative w-32 h-32">
                                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                                        <circle cx="18" cy="18" r="16" fill="none" stroke="#E5E7EB" stroke-width="3"></circle>
                                        <circle cx="18" cy="18" r="16" fill="none" stroke="#27AE60" stroke-width="3" stroke-dasharray="60 100" stroke-linecap="round"></circle>
                                        <circle cx="18" cy="18" r="16" fill="none" stroke="#E74C3C" stroke-width="3" stroke-dasharray="25 100" stroke-dashoffset="-60" stroke-linecap="round"></circle>
                                        <circle cx="18" cy="18" r="16" fill="none" stroke="#F39C12" stroke-width="3" stroke-dasharray="15 100" stroke-dashoffset="-85" stroke-linecap="round"></circle>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-2xl font-bold text-slate-900">100%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2 mt-4">
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full bg-[#27AE60]"></div>
                                        <span>Completed</span>
                                    </div>
                                    <span class="font-semibold">60%</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full bg-[#E74C3C]"></div>
                                        <span>In Progress</span>
                                    </div>
                                    <span class="font-semibold">25%</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full bg-[#F39C12]"></div>
                                        <span>Pending</span>
                                    </div>
                                    <span class="font-semibold">15%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Records -->
                    <div class="bg-white rounded-xl p-6 shadow-md border border-slate-200 mt-6">
                        <h4 class="text-lg font-bold text-slate-900 mb-4">Recent Records</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-sm text-green-600">check</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-900">John Doe clocked in</div>
                                        <div class="text-xs text-slate-500">2 minutes ago</div>
                                    </div>
                                </div>
                                <span class="text-xs text-slate-500">08:30 AM</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-sm text-blue-600">task</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-900">New task assigned to Marketing Team</div>
                                        <div class="text-xs text-slate-500">15 minutes ago</div>
                                    </div>
                                </div>
                                <span class="text-xs text-slate-500">08:15 AM</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                                        <span class="material-symbols-outlined text-sm text-purple-600">folder</span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-900">Project "Website Redesign" updated</div>
                                        <div class="text-xs text-slate-500">1 hour ago</div>
                                    </div>
                                </div>
                                <span class="text-xs text-slate-500">07:30 AM</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. ABOUT US SECTION -->
    <section class="py-24 bg-white" id="about">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <!-- Left Column - Images -->
                <div class="relative">
                    <div class="grid grid-cols-2 gap-4">
                        <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&h=600&fit=crop" alt="Team collaborating" class="rounded-xl shadow-lg w-full h-64 object-cover" />
                        <img src="https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=800&h=600&fit=crop" alt="Using the system" class="rounded-xl shadow-lg w-full h-64 object-cover mt-8" />
                    </div>
                </div>

                <!-- Right Column - Text -->
                <div>
                    <h2 class="text-4xl font-bold text-slate-900 mb-6">About Us</h2>
                    <p class="text-lg text-slate-600 leading-relaxed mb-6">
                        TimeTrack Pro is a comprehensive workforce management platform that helps businesses streamline attendance tracking, project management, and team collaboration. Our solution combines powerful features with an intuitive interface, making it easy for organizations of all sizes to manage their workforce efficiently.
                    </p>
                    <p class="text-lg text-slate-600 leading-relaxed mb-8">
                        Built with cutting-edge technology and designed with user experience in mind, TimeTrack Pro empowers businesses to make data-driven decisions, improve productivity, and achieve their goals.
                    </p>
                    <a class="inline-flex items-center justify-center px-8 py-4 rounded-full bg-[#3c83f6] hover:bg-[#2563eb] text-white font-bold transition-all shadow-lg" href="#features">
                        Learn More
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. HOW IT WORKS SECTION -->
    <section class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-slate-900 mb-4">How it works</h2>
                <p class="text-xl text-slate-600 max-w-3xl mx-auto">
                    Getting started with TimeTrack Pro takes less than 5 minutes. Here's how our platform helps you manage your workforce effectively.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="bg-white rounded-xl p-8 shadow-md text-center">
                    <div class="w-16 h-16 rounded-full bg-[#3c83f6] flex items-center justify-center text-white mx-auto mb-6">
                        <span class="material-symbols-outlined text-3xl">schedule</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-4">Set Up Your Schedule</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Configure your business hours, time windows for clock-in/out, and attendance rules. Set up barcode-based employee identification for seamless tracking.
                    </p>
                </div>

                <!-- Card 2 -->
                <div class="bg-white rounded-xl p-8 shadow-md text-center">
                    <div class="w-16 h-16 rounded-full bg-[#27AE60] flex items-center justify-center text-white mx-auto mb-6">
                        <span class="material-symbols-outlined text-3xl">trending_up</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-4">Manage Daily Activities Effortlessly</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Track real-time attendance, manage projects and tasks, coordinate teams, and monitor progress. Everything in one centralized dashboard.
                    </p>
                </div>

                <!-- Card 3 -->
                <div class="bg-white rounded-xl p-8 shadow-md text-center">
                    <div class="w-16 h-16 rounded-full bg-[#F39C12] flex items-center justify-center text-white mx-auto mb-6">
                        <span class="material-symbols-outlined text-3xl">bar_chart</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-4">Monitor and Improve Performance</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Generate comprehensive reports, analyze team productivity, track project hours, and make data-driven decisions to optimize your workforce.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. WHY YOU SHOULD USE SECTION -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <h2 class="text-4xl font-bold text-slate-900 mb-4">WHY YOU SHOULD USE TIMETRACK PRO</h2>
            <p class="text-xl text-slate-600 max-w-3xl mx-auto">
                Our platform provides everything you need to manage your workforce efficiently, from attendance tracking to comprehensive project management.
            </p>
        </div>
    </section>

    <!-- 7. FEATURES SHOWCASE -->
    <section class="py-24 bg-slate-50" id="features">
        <div class="max-w-7xl mx-auto px-6 space-y-24">
            <!-- Feature 1: Time & Attendance -->
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="bg-white rounded-xl p-8 shadow-lg">
                    <div class="bg-slate-50 rounded-lg p-8">
                        <h4 class="text-sm font-semibold text-slate-600 mb-4">Weekly Attendance</h4>
                        <div class="flex items-end justify-between h-64 gap-2">
                            <div class="flex-1 bg-[#27AE60] rounded-t" style="height: 85%"></div>
                            <div class="flex-1 bg-[#27AE60] rounded-t" style="height: 92%"></div>
                            <div class="flex-1 bg-[#27AE60] rounded-t" style="height: 88%"></div>
                            <div class="flex-1 bg-[#27AE60] rounded-t" style="height: 95%"></div>
                            <div class="flex-1 bg-[#27AE60] rounded-t" style="height: 90%"></div>
                            <div class="flex-1 bg-[#27AE60] rounded-t" style="height: 78%"></div>
                            <div class="flex-1 bg-[#27AE60] rounded-t" style="height: 70%"></div>
                        </div>
                        <div class="flex justify-between text-xs text-slate-500 mt-2">
                            <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-3xl font-bold text-slate-900 mb-6">Attendance Management</h3>
                    <p class="text-lg text-slate-600 leading-relaxed mb-6">
                        Track employee clock-in/out with barcode scanning. Monitor real-time status, view attendance history, and manage time windows with flexible permissions.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[#27AE60] mt-0.5">check_circle</span>
                            <span class="text-slate-700">Barcode-based identification</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[#27AE60] mt-0.5">check_circle</span>
                            <span class="text-slate-700">Real-time attendance tracking</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[#27AE60] mt-0.5">check_circle</span>
                            <span class="text-slate-700">Configurable time windows</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Feature 2: Smart Attendance Analytics -->
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="order-2 lg:order-1">
                    <h3 class="text-3xl font-bold text-slate-900 mb-6">Smart Attendance Insights</h3>
                    <p class="text-lg text-slate-600 leading-relaxed mb-6">
                        Get instant visibility into attendance patterns, identify trends, and ensure compliance. Real-time dashboards keep you informed of who's in, who's out, and attendance rates.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[#27AE60] mt-0.5">check_circle</span>
                            <span class="text-slate-700">Attendance rate monitoring</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[#27AE60] mt-0.5">check_circle</span>
                            <span class="text-slate-700">Trend analysis and reporting</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[#27AE60] mt-0.5">check_circle</span>
                            <span class="text-slate-700">Real-time status updates</span>
                        </li>
                    </ul>
                </div>
                <div class="order-1 lg:order-2 bg-white rounded-xl p-8 shadow-lg flex items-center justify-center">
                    <div class="relative w-64 h-64">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="16" fill="none" stroke="#E5E7EB" stroke-width="3.5"></circle>
                            <circle cx="18" cy="18" r="16" fill="none" stroke="#3c83f6" stroke-width="3.5" stroke-dasharray="87 100" stroke-linecap="round"></circle>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-5xl font-bold text-[#3c83f6]">87.2%</span>
                            <span class="text-sm text-slate-600 mt-2">Attendance Rate</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature 3: Project Management -->
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="bg-white rounded-xl p-8 shadow-lg">
                    <div class="space-y-4">
                        <div class="border border-slate-200 rounded-lg p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-green-600">check</span>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900">Website Redesign</div>
                                    <div class="text-xs text-slate-500">Phase 3 of 5</div>
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-green-600">Completed</span>
                        </div>
                        <div class="border border-slate-200 rounded-lg p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-blue-600">schedule</span>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900">Mobile App Development</div>
                                    <div class="text-xs text-slate-500">Phase 2 of 4</div>
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-blue-600">In Progress</span>
                        </div>
                        <div class="border border-slate-200 rounded-lg p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-yellow-600">pending</span>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900">Marketing Campaign</div>
                                    <div class="text-xs text-slate-500">Phase 1 of 3</div>
                                </div>
                            </div>
                            <span class="text-xs font-semibold text-yellow-600">Planning</span>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-3xl font-bold text-slate-900 mb-6">Comprehensive Project Management</h3>
                    <p class="text-lg text-slate-600 leading-relaxed mb-6">
                        Manage multi-phase projects, create tasks, assign team members, track progress, and monitor budgets vs actual hours. Everything organized in one place.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[#27AE60] mt-0.5">check_circle</span>
                            <span class="text-slate-700">Multi-phase project tracking</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[#27AE60] mt-0.5">check_circle</span>
                            <span class="text-slate-700">Task assignment and management</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[#27AE60] mt-0.5">check_circle</span>
                            <span class="text-slate-700">Budget vs actual monitoring</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Feature 4: Team Collaboration -->
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="order-2 lg:order-1">
                    <h3 class="text-3xl font-bold text-slate-900 mb-6">Powerful Mobile Access</h3>
                    <p class="text-lg text-slate-600 leading-relaxed mb-6">
                        Access TimeTrack Pro on the go. Clock in/out, submit reports, view tasks, and manage your team from anywhere. Full functionality at your fingertips.
                    </p>
                    <ul class="space-y-3">
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[#27AE60] mt-0.5">check_circle</span>
                            <span class="text-slate-700">Mobile clock in/out</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[#27AE60] mt-0.5">check_circle</span>
                            <span class="text-slate-700">Task and project access</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[#27AE60] mt-0.5">check_circle</span>
                            <span class="text-slate-700">Team coordination on-the-go</span>
                        </li>
                    </ul>
                </div>
                <div class="order-1 lg:order-2 bg-white rounded-xl p-8 shadow-lg">
                    <div class="bg-slate-50 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="font-semibold text-slate-900">Today's Schedule</h4>
                            <span class="text-xs text-slate-500">Jan 21, 2026</span>
                        </div>
                        <div class="space-y-3">
                            <div class="flex gap-3">
                                <div class="w-1 rounded-full bg-blue-500"></div>
                                <div class="flex-1">
                                    <div class="font-medium text-sm">Team Meeting</div>
                                    <div class="text-xs text-slate-500">09:00 AM - 10:00 AM</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-1 rounded-full bg-green-500"></div>
                                <div class="flex-1">
                                    <div class="font-medium text-sm">Project Review</div>
                                    <div class="text-xs text-slate-500">11:00 AM - 12:00 PM</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-1 rounded-full bg-purple-500"></div>
                                <div class="flex-1">
                                    <div class="font-medium text-sm">Client Presentation</div>
                                    <div class="text-xs text-slate-500">02:00 PM - 03:30 PM</div>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="w-1 rounded-full bg-orange-500"></div>
                                <div class="flex-1">
                                    <div class="font-medium text-sm">Development Sprint</div>
                                    <div class="text-xs text-slate-500">04:00 PM - 06:00 PM</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 8. INTEGRATED APPS & LEARNING SECTION -->
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid md:grid-cols-2 gap-12">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-4">Integrated Apps & Learning</h3>
                    <p class="text-slate-600 leading-relaxed">
                        TimeTrack Pro offers powerful API integration capabilities, allowing you to connect with your existing tools and systems. Extend functionality, automate workflows, and build custom solutions tailored to your business needs.
                    </p>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-4">Team Mobile App</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Empower your team with mobile accessibility. Our responsive web interface works seamlessly across all devices, ensuring your workforce can stay connected and productive from anywhere, at any time.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 9. WHO IS IT FOR SECTION -->
    <section class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <h2 class="text-4xl font-bold text-slate-900 mb-6">Who is it For?</h2>
            <p class="text-xl text-slate-600 max-w-4xl mx-auto leading-relaxed">
                TimeTrack Pro is designed for businesses of all sizes - from small teams to large enterprises. Whether you're managing 10 employees or 1,000, our platform scales with your needs. Perfect for companies that value efficiency, accountability, and data-driven workforce management.
            </p>
        </div>
    </section>

    <!-- 10. TESTIMONIALS SECTION -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-6">
            <h2 class="text-4xl font-bold text-slate-900 text-center mb-16">What Our Customers Say</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Testimonial 1 -->
                <div class="bg-white rounded-xl p-6 shadow-md border border-slate-200">
                    <div class="flex flex-col items-center text-center">
                        <img src="https://i.pravatar.cc/150?img=1" alt="Sarah Johnson" class="w-20 h-20 rounded-full mb-4" />
                        <h4 class="font-bold text-slate-900">Sarah Johnson</h4>
                        <p class="text-sm text-slate-500 mb-4">CEO, TechStart Inc.</p>
                        <p class="text-sm text-slate-600 italic">
                            "TimeTrack Pro transformed how we manage our remote team. The barcode system is incredibly efficient and the reports are invaluable."
                        </p>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="bg-white rounded-xl p-6 shadow-md border border-slate-200">
                    <div class="flex flex-col items-center text-center">
                        <img src="https://i.pravatar.cc/150?img=8" alt="Michael Chen" class="w-20 h-20 rounded-full mb-4" />
                        <h4 class="font-bold text-slate-900">Michael Chen</h4>
                        <p class="text-sm text-slate-500 mb-4">HR Manager, GlobalCorp</p>
                        <p class="text-sm text-slate-600 italic">
                            "We've saved countless hours on attendance tracking and payroll processing. The system just works flawlessly."
                        </p>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="bg-white rounded-xl p-6 shadow-md border border-slate-200">
                    <div class="flex flex-col items-center text-center">
                        <img src="https://i.pravatar.cc/150?img=5" alt="Emily Rodriguez" class="w-20 h-20 rounded-full mb-4" />
                        <h4 class="font-bold text-slate-900">Emily Rodriguez</h4>
                        <p class="text-sm text-slate-500 mb-4">Team Leader, Innovate Labs</p>
                        <p class="text-sm text-slate-600 italic">
                            "The project management features are fantastic. We can track multiple projects effortlessly and meet all our deadlines."
                        </p>
                    </div>
                </div>

                <!-- Testimonial 4 -->
                <div class="bg-white rounded-xl p-6 shadow-md border border-slate-200">
                    <div class="flex flex-col items-center text-center">
                        <img src="https://i.pravatar.cc/150?img=12" alt="David Thompson" class="w-20 h-20 rounded-full mb-4" />
                        <h4 class="font-bold text-slate-900">David Thompson</h4>
                        <p class="text-sm text-slate-500 mb-4">COO, BuildCo</p>
                        <p class="text-sm text-slate-600 italic">
                            "The ROI has been incredible. We've improved productivity by 30% and reduced administrative overhead significantly."
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 11. OPINIONS SECTION -->
    <section class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-slate-900 mb-4">Opinions</h2>
                <p class="text-xl text-slate-600">What Business Leaders & Experts Say About Workforce Management</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- Opinion 1 -->
                <div class="bg-white rounded-xl p-6 shadow-md border border-slate-200 flex gap-6">
                    <img src="https://i.pravatar.cc/150?img=32" alt="Expert" class="w-24 h-24 rounded-lg object-cover flex-shrink-0" />
                    <div>
                        <p class="text-slate-700 mb-4 italic">
                            "Modern workforce management systems like TimeTrack Pro are essential for businesses looking to optimize operations and improve employee accountability in today's digital workplace."
                        </p>
                        <h5 class="font-bold text-slate-900">Dr. James Anderson</h5>
                        <p class="text-sm text-slate-600">HR Technology Consultant</p>
                        <p class="text-sm text-slate-500">Forbes Contributor</p>
                    </div>
                </div>

                <!-- Opinion 2 -->
                <div class="bg-white rounded-xl p-6 shadow-md border border-slate-200 flex gap-6">
                    <img src="https://i.pravatar.cc/150?img=27" alt="Expert" class="w-24 h-24 rounded-lg object-cover flex-shrink-0" />
                    <div>
                        <p class="text-slate-700 mb-4 italic">
                            "The integration of barcode technology with comprehensive project management creates a powerful solution that addresses real business pain points."
                        </p>
                        <h5 class="font-bold text-slate-900">Lisa Martinez</h5>
                        <p class="text-sm text-slate-600">Business Operations Director</p>
                        <p class="text-sm text-slate-500">McKinsey & Company</p>
                    </div>
                </div>

                <!-- Opinion 3 -->
                <div class="bg-white rounded-xl p-6 shadow-md border border-slate-200 flex gap-6">
                    <img src="https://i.pravatar.cc/150?img=33" alt="Expert" class="w-24 h-24 rounded-lg object-cover flex-shrink-0" />
                    <div>
                        <p class="text-slate-700 mb-4 italic">
                            "Small business owners need tools that are both powerful and easy to use. Systems that reduce complexity while increasing visibility are game-changers."
                        </p>
                        <h5 class="font-bold text-slate-900">Robert Kim</h5>
                        <p class="text-sm text-slate-600">Small Business Advisor</p>
                        <p class="text-sm text-slate-500">Entrepreneur Magazine</p>
                    </div>
                </div>

                <!-- Opinion 4 -->
                <div class="bg-white rounded-xl p-6 shadow-md border border-slate-200 flex gap-6">
                    <img src="https://i.pravatar.cc/150?img=44" alt="Expert" class="w-24 h-24 rounded-lg object-cover flex-shrink-0" />
                    <div>
                        <p class="text-slate-700 mb-4 italic">
                            "Data-driven workforce management is no longer optional. Companies that embrace these technologies see measurable improvements in productivity and employee satisfaction."
                        </p>
                        <h5 class="font-bold text-slate-900">Amanda Foster</h5>
                        <p class="text-sm text-slate-600">Workforce Analytics Expert</p>
                        <p class="text-sm text-slate-500">Harvard Business Review</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 12. FREQUENTLY ASKED QUESTIONS -->
    <section class="py-24 bg-white" id="faq">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid lg:grid-cols-2 gap-16">
                <!-- Left Column - FAQ List -->
                <div>
                    <h2 class="text-4xl font-bold text-slate-900 mb-8">Frequently Asked Questions</h2>
                    <div class="space-y-4">
                        <details class="group">
                            <summary class="flex items-center justify-between cursor-pointer p-4 bg-slate-50 rounded-lg font-semibold text-slate-900">
                                What is TimeTrack Pro?
                                <span class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                            </summary>
                            <p class="p-4 text-slate-600">
                                TimeTrack Pro is a comprehensive workforce management platform that combines attendance tracking, project management, and team collaboration tools in one integrated solution.
                            </p>
                        </details>
                        <details class="group">
                            <summary class="flex items-center justify-between cursor-pointer p-4 bg-slate-50 rounded-lg font-semibold text-slate-900">
                                How secure is the platform?
                                <span class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                            </summary>
                            <p class="p-4 text-slate-600">
                                We use bank-grade encryption, password hashing, and prepared SQL statements to protect your data. Our platform is built with security best practices at every level.
                            </p>
                        </details>
                        <details class="group">
                            <summary class="flex items-center justify-between cursor-pointer p-4 bg-slate-50 rounded-lg font-semibold text-slate-900">
                                What pricing plans are available?
                                <span class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                            </summary>
                            <p class="p-4 text-slate-600">
                                We offer flexible pricing tiers from Starter to Enterprise, designed to accommodate businesses of all sizes. Contact us for custom enterprise solutions.
                            </p>
                        </details>
                        <details class="group">
                            <summary class="flex items-center justify-between cursor-pointer p-4 bg-slate-50 rounded-lg font-semibold text-slate-900">
                                Can I integrate with other tools?
                                <span class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                            </summary>
                            <p class="p-4 text-slate-600">
                                Yes! TimeTrack Pro offers API access for integration with payroll systems, HR platforms, and other business tools.
                            </p>
                        </details>
                        <details class="group">
                            <summary class="flex items-center justify-between cursor-pointer p-4 bg-slate-50 rounded-lg font-semibold text-slate-900">
                                Does it work on mobile devices?
                                <span class="material-symbols-outlined group-open:rotate-180 transition-transform">expand_more</span>
                            </summary>
                            <p class="p-4 text-slate-600">
                                Absolutely! Our responsive web interface works seamlessly on all devices, providing full functionality on smartphones and tablets.
                            </p>
                        </details>
                    </div>
                </div>

                <!-- Right Column - Featured FAQ Card -->
                <div>
                    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
                        <div class="bg-[#3c83f6] text-white p-8">
                            <h3 class="text-2xl font-bold mb-2">Want to know more?</h3>
                            <p class="text-white/90">Download our comprehensive product guide or schedule a personalized demo.</p>
                        </div>
                        <div class="p-8">
                            <p class="text-slate-600 mb-6">
                                Get detailed information about features, implementation, pricing, and success stories from businesses like yours.
                            </p>
                            <div class="space-y-4">
                                <a href="#" class="block w-full px-6 py-3 rounded-lg bg-[#3c83f6] hover:bg-[#2563eb] text-white font-bold text-center transition-all">
                                    Download Product Guide
                                </a>
                                <a href="#" class="block w-full px-6 py-3 rounded-lg border-2 border-slate-300 hover:border-[#3c83f6] text-slate-700 hover:text-[#3c83f6] font-bold text-center transition-all">
                                    Schedule Demo
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 13. FINAL CTA SECTION -->
    <section class="py-24 bg-gradient-to-r from-[#3c83f6] to-[#2563eb] relative overflow-hidden">
        <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <!-- Left Side - Phone Mockup -->
                <div class="flex justify-center">
                    <div class="relative">
                        <div class="w-72 h-[600px] bg-slate-900 rounded-[3rem] p-3 shadow-2xl">
                            <div class="w-full h-full bg-white rounded-[2.5rem] overflow-hidden">
                                <div class="bg-slate-50 h-full p-6">
                                    <div class="text-center mb-6">
                                        <div class="w-16 h-16 rounded-full bg-[#3c83f6] mx-auto flex items-center justify-center text-white mb-3">
                                            <span class="material-symbols-outlined text-3xl">schedule</span>
                                        </div>
                                        <h4 class="font-bold text-slate-900">TimeTrack Pro</h4>
                                        <p class="text-xs text-slate-500">Mobile Access</p>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="bg-white rounded-lg p-4 shadow">
                                            <div class="text-sm font-semibold text-slate-900 mb-1">Clock In/Out</div>
                                            <div class="text-xs text-slate-500">Instant attendance tracking</div>
                                        </div>
                                        <div class="bg-white rounded-lg p-4 shadow">
                                            <div class="text-sm font-semibold text-slate-900 mb-1">View Tasks</div>
                                            <div class="text-xs text-slate-500">Access your assignments</div>
                                        </div>
                                        <div class="bg-white rounded-lg p-4 shadow">
                                            <div class="text-sm font-semibold text-slate-900 mb-1">Team Updates</div>
                                            <div class="text-xs text-slate-500">Stay connected</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side - CTA Content -->
                <div class="text-white">
                    <h2 class="text-5xl font-black mb-6">Ready to Transform Your Workflow?</h2>
                    <p class="text-2xl mb-8 text-white/90">Get started today</p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="contact.php" class="px-8 py-4 rounded-full bg-white hover:bg-slate-100 text-[#E74C3C] text-lg font-bold transition-all shadow-lg text-center">
                            Get Started
                        </a>
                        <a href="#about" class="px-8 py-4 rounded-full border-2 border-white hover:bg-white/10 text-white text-lg font-bold transition-all text-center">
                            Learn More
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 14. FOOTER -->
    <footer class="bg-slate-900 text-slate-300 py-16">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                <!-- Column 1: Branding & Contact -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-[#3c83f6] flex items-center justify-center text-white">
                            <span class="material-symbols-outlined text-xl">schedule</span>
                        </div>
                        <span class="text-xl font-bold text-white">TimeTrack Pro</span>
                    </div>
                    <p class="text-sm mb-4">The modern way businesses track time, manage teams, and deliver projects.</p>
                    <div class="space-y-2 text-sm">
                        <p>Email: support@timetrackpro.com</p>
                        <p>Phone: +1 (555) 123-4567</p>
                    </div>
                    <div class="flex gap-4 mt-4">
                        <a href="#" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-[#E74C3C] flex items-center justify-center transition-colors">
                            <span class="text-sm">𝕏</span>
                        </a>
                        <a href="#" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-[#E74C3C] flex items-center justify-center transition-colors">
                            <span class="text-sm">in</span>
                        </a>
                        <a href="#" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-[#E74C3C] flex items-center justify-center transition-colors">
                            <span class="text-sm">f</span>
                        </a>
                    </div>
                </div>

                <!-- Column 2: Product -->
                <div>
                    <h4 class="text-white font-bold mb-4">Product</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#features" class="hover:text-white transition-colors">Features</a></li>
                        <li><a href="#pricing" class="hover:text-white transition-colors">Pricing</a></li>
                        <li><a href="#solutions" class="hover:text-white transition-colors">Solutions</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Security</a></li>
                    </ul>
                </div>

                <!-- Column 3: Support -->
                <div>
                    <h4 class="text-white font-bold mb-4">Support</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">API Docs</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">System Status</a></li>
                    </ul>
                </div>

                <!-- Column 4: Company -->
                <div>
                    <h4 class="text-white font-bold mb-4">Company</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#about" class="hover:text-white transition-colors">About Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Careers</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Press Kit</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="pt-8 border-t border-slate-800 flex flex-col sm:flex-row justify-between items-center gap-4 text-sm">
                <p class="text-slate-500">© 2026 TimeTrack Pro. All rights reserved.</p>
                <div class="flex gap-6">
                    <a href="#" class="text-slate-500 hover:text-white transition-colors">Privacy Policy</a>
                    <a href="#" class="text-slate-500 hover:text-white transition-colors">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
</body>

</html>