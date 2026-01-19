<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    // For testing, set default session values
    $_SESSION['business_id'] = 1;
    $_SESSION['user_id'] = 1;
    $_SESSION['firstname'] = 'Admin';
    $_SESSION['lastname'] = 'User';
}

$business_id = $_SESSION['business_id'];

// Get business settings
$stmt = $db->prepare("SELECT * FROM business WHERE id = ?");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$business = $stmt->get_result()->fetch_assoc();

// Handle settings update
if (isset($_POST['update_settings'])) {
    $clocking_enabled = isset($_POST['clocking_enabled']) ? 1 : 0;
    $reporting_enabled = isset($_POST['reporting_enabled']) ? 1 : 0;
    
    $stmt = $db->prepare("UPDATE business SET clocking_enabled = ?, reporting_enabled = ? WHERE id = ?");
    $stmt->bind_param("iii", $clocking_enabled, $reporting_enabled, $business_id);
    $stmt->execute();
    
    header('Location: dashboard.php?msg=Settings updated successfully');
    exit;
}

// Handle user permission update
if (isset($_POST['update_permission'])) {
    $user_id = $_POST['user_id'];
    $can_clock = isset($_POST['can_clock']) ? 1 : 0;
    
    $stmt = $db->prepare("UPDATE users SET can_clock = ? WHERE id = ? AND business_id = ?");
    $stmt->bind_param("iii", $can_clock, $user_id, $business_id);
    $stmt->execute();
    
    header('Location: dashboard.php?msg=User permissions updated successfully');
    exit;
}

// Handle user update
if (isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'];
    $barcode = trim($_POST['barcode']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $category = $_POST['category'];
    
    $stmt = $db->prepare("UPDATE users SET barcode = ?, firstname = ?, lastname = ?, email = ?, category = ? WHERE id = ? AND business_id = ?");
    $stmt->bind_param("sssssii", $barcode, $firstname, $lastname, $email, $category, $user_id, $business_id);
    
    if ($stmt->execute()) {
        header('Location: dashboard.php?msg=User updated successfully');
    } else {
        $error = 'Error updating user. Barcode or email may already exist.';
    }
}

// Handle user creation
if (isset($_POST['create_user'])) {
    $barcode = trim($_POST['barcode']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $category = $_POST['category'];
    
    $stmt = $db->prepare("INSERT INTO users (business_id, barcode, firstname, lastname, email, password, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $business_id, $barcode, $firstname, $lastname, $email, $password, $category);
    
    if ($stmt->execute()) {
        header('Location: dashboard.php?msg=User created successfully');
    } else {
        $error = 'Error creating user. Barcode or email may already exist.';
    }
}

// Get users
$stmt = $db->prepare("SELECT * FROM users WHERE business_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$users = $stmt->get_result();

// Get today's reports
$today = TODAY;
$stmt = $db->prepare("SELECT r.*, u.firstname, u.lastname, u.barcode FROM reports r JOIN users u ON r.user_id = u.id WHERE u.business_id = ? AND r.report_date = ? ORDER BY r.created_at DESC");
$stmt->bind_param("is", $business_id, $today);
$stmt->execute();
$today_reports = $stmt->get_result();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Admin Dashboard - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#135bec",
                        "background-light": "#f6f6f8",
                        "background-dark": "#101622",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        .status-clocked_in { @apply bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium; }
        .status-plan_submitted { @apply bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium; }
        .status-report_submitted { @apply bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium; }
        .status-clocked_out { @apply bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-text-main dark:text-white overflow-hidden selection:bg-primary selection:text-white">
<div class="flex h-screen w-full overflow-hidden">
    <!-- Side Navigation -->
    <aside class="w-64 flex-shrink-0 bg-surface-light dark:bg-surface-dark border-r border-border-light dark:border-border-dark flex flex-col justify-between transition-colors duration-200 hidden md:flex">
        <div class="flex flex-col h-full">
            <div class="p-6">
                <div class="flex items-center gap-2 mb-8">
                    <div class="text-primary">
                        <span class="material-symbols-outlined filled" style="font-size: 32px;">schedule</span>
                    </div>
                    <div>
                        <h1 class="text-text-main dark:text-white text-lg font-bold leading-tight">TimeTrack Pro</h1>
                        <p class="text-text-secondary text-xs font-medium"><?php echo htmlspecialchars($business['name']); ?></p>
                    </div>
                </div>
                <nav class="flex flex-col gap-2">
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-main dark:text-gray-300 hover:bg-background-light dark:hover:bg-slate-700 transition-colors group" href="scanner.php">
                        <span class="material-symbols-outlined text-text-secondary group-hover:text-primary transition-colors">barcode_reader</span>
                        <span class="text-sm font-medium">Scanner</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-main dark:text-gray-300 hover:bg-background-light dark:hover:bg-slate-700 transition-colors group" href="teams.php">
                        <span class="material-symbols-outlined text-text-secondary group-hover:text-primary transition-colors">group</span>
                        <span class="text-sm font-medium">Teams</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-main dark:text-gray-300 hover:bg-background-light dark:hover:bg-slate-700 transition-colors group" href="projects.php">
                        <span class="material-symbols-outlined text-text-secondary group-hover:text-primary transition-colors">work</span>
                        <span class="text-sm font-medium">Projects</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary transition-colors" href="dashboard.php">
                        <span class="material-symbols-outlined filled">assessment</span>
                        <span class="text-sm font-semibold">Reports</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-main dark:text-gray-300 hover:bg-background-light dark:hover:bg-slate-700 transition-colors group" href="dashboard.php#users">
                        <span class="material-symbols-outlined text-text-secondary group-hover:text-primary transition-colors">people</span>
                        <span class="text-sm font-medium">Users</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-main dark:text-gray-300 hover:bg-background-light dark:hover:bg-slate-700 transition-colors group" href="dashboard.php#settings">
                        <span class="material-symbols-outlined text-text-secondary group-hover:text-primary transition-colors">settings</span>
                        <span class="text-sm font-medium">Settings</span>
                    </a>
                </nav>
            </div>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
        <!-- Top Navigation -->
        <header class="flex-shrink-0 bg-surface-light dark:bg-surface-dark border-b border-border-light dark:border-border-dark px-6 py-3 flex items-center justify-between z-10">
            <div class="flex items-center gap-4 md:hidden">
                <button class="text-text-secondary">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <span class="text-lg font-bold">TimeTrack Pro</span>
            </div>
            <div class="hidden md:flex flex-1">
                <h2 class="text-xl font-semibold text-text-main dark:text-white">Admin Dashboard</h2>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <div class="text-lg font-bold text-text-main dark:text-white" id="current-time"></div>
                    <div class="text-xs text-text-secondary" id="current-date"></div>
                </div>
                <a href="../logout.php" class="p-2 text-text-secondary hover:bg-background-light dark:hover:bg-slate-700 rounded-full transition-colors">
                    <span class="material-symbols-outlined">logout</span>
                </a>
            </div>
        </header>

        <!-- Scrollable Page Content -->
        <div class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark">
            <div class="max-w-6xl mx-auto px-6 py-8">
        <?php if (isset($_GET['msg'])): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Quick Navigation Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <a href="teams.php" class="bg-white rounded-xl p-6 border border-slate-200 hover:border-primary/30 hover:shadow-lg transition-all group">
                <div class="flex items-center gap-4">
                    <div class="size-12 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-2xl text-purple-600 group-hover:text-white">groups</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-900">Teams</h3>
                        <p class="text-sm text-slate-500">Create & manage teams</p>
                    </div>
                </div>
            </a>
            <a href="projects.php" class="bg-white rounded-xl p-6 border border-slate-200 hover:border-primary/30 hover:shadow-lg transition-all group">
                <div class="flex items-center gap-4">
                    <div class="size-12 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-colors">
                        <span class="material-symbols-outlined text-2xl text-blue-600 group-hover:text-white">assignment</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-900">Projects</h3>
                        <p class="text-sm text-slate-500">View all projects</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Tabs -->
        <div class="flex space-x-1 mb-8 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg">
            <button onclick="switchTab('reports')" class="tab-btn flex-1 px-4 py-2 text-sm font-medium rounded-md transition-colors" data-tab="reports">
                <span class="material-symbols-outlined text-lg mr-2">assessment</span>
                Reports
            </button>
            <button onclick="switchTab('users')" class="tab-btn flex-1 px-4 py-2 text-sm font-medium rounded-md transition-colors" data-tab="users">
                <span class="material-symbols-outlined text-lg mr-2">people</span>
                Users
            </button>
            <button onclick="switchTab('settings')" class="tab-btn flex-1 px-4 py-2 text-sm font-medium rounded-md transition-colors" data-tab="settings">
                <span class="material-symbols-outlined text-lg mr-2">settings</span>
                Settings
            </button>
        </div>

<!-- Reports Tab -->
        <div id="reports" class="tab-content hidden">
            <!-- Header Section -->
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-slate-900 text-3xl font-bold tracking-tight">Today's Reports</h1>
                        <p class="text-slate-500 text-sm mt-1">Monitor daily attendance and activity for <?php echo TODAY; ?></p>
                    </div>
                    <div class="flex gap-3">
                        <button class="flex cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-5 bg-white hover:bg-slate-50 transition-colors text-slate-700 gap-2 text-sm font-semibold shadow-sm border border-slate-200">
                            <span class="material-symbols-outlined text-[20px]">download</span>
                            <span>Export</span>
                        </button>
                        <button onclick="location.reload()" class="flex cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-5 bg-primary hover:bg-primary/90 transition-colors text-white gap-2 text-sm font-semibold shadow-sm">
                            <span class="material-symbols-outlined text-[20px]">refresh</span>
                            <span>Refresh</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters & Toolbar -->
            <div class="mb-6">
                <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row gap-4 justify-between items-center shadow-sm">
                    <div class="flex flex-1 w-full sm:w-auto gap-3">
                        <div class="relative flex-1 max-w-md">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-[20px]">search</span>
                            <input class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm text-slate-900 placeholder:text-slate-400" placeholder="Search by name..." type="text"/>
                        </div>
                        <div class="relative min-w-[140px] hidden sm:block">
                            <select class="w-full appearance-none pl-4 pr-10 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm text-slate-900 cursor-pointer">
                                <option value="">All Status</option>
                                <option value="clocked_in">Clocked In</option>
                                <option value="plan_submitted">Plan Submitted</option>
                                <option value="report_submitted">Report Submitted</option>
                                <option value="clocked_out">Clocked Out</option>
                            </select>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none material-symbols-outlined text-[20px]">expand_more</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Table -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="py-3 px-4 w-12">
                                <input class="rounded border-gray-300 text-primary focus:ring-primary/20 cursor-pointer w-4 h-4" type="checkbox"/>
                            </th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Employee</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Clock In</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Plan</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Report</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Clock Out</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php 
                        $today_reports->data_seek(0); // Reset pointer
                        while ($report = $today_reports->fetch_assoc()): 
                        ?>
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-4">
                                <input class="rounded border-gray-300 text-primary focus:ring-primary/20 cursor-pointer w-4 h-4" type="checkbox"/>
                            </td>
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="size-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                                        <?php echo strtoupper(substr($report['firstname'], 0, 1) . substr($report['lastname'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($report['firstname'] . ' ' . $report['lastname']); ?></p>
                                        <p class="text-xs text-slate-500"><?php echo htmlspecialchars($report['barcode']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                <?php if ($report['clock_in_time']): ?>
                                    <span class="text-sm font-medium text-green-700 bg-green-50 px-2 py-1 rounded border border-green-200">
                                        <?php echo date('h:i A', strtotime($report['clock_in_time'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-sm font-medium text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <?php if ($report['plan_submitted_at']): ?>
                                    <span class="text-sm font-medium text-blue-700 bg-blue-50 px-2 py-1 rounded border border-blue-200">
                                        <?php echo date('h:i A', strtotime($report['plan_submitted_at'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-sm font-medium text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <?php if ($report['report_submitted_at']): ?>
                                    <span class="text-sm font-medium text-purple-700 bg-purple-50 px-2 py-1 rounded border border-purple-200">
                                        <?php echo date('h:i A', strtotime($report['report_submitted_at'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-sm font-medium text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <?php if ($report['clock_out_time']): ?>
                                    <span class="text-sm font-medium text-red-700 bg-red-50 px-2 py-1 rounded border border-red-200">
                                        <?php echo date('h:i A', strtotime($report['clock_out_time'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-sm font-medium text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-4">
                                <?php 
                                $statusColors = [
                                    'clocked_in' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    'plan_submitted' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'report_submitted' => 'bg-green-100 text-green-800 border-green-200',
                                    'clocked_out' => 'bg-gray-100 text-gray-800 border-gray-200'
                                ];
                                $statusClass = $statusColors[$report['status']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?> border">
                                    <span class="size-1.5 rounded-full bg-current"></span>
                                    <?php echo ucfirst(str_replace('_', ' ', $report['status'])); ?>
                                </span>
                            </td>
                            <td class="py-4 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button onclick="viewReportDetails(<?php echo $report['id']; ?>)" class="size-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 hover:text-primary transition-colors" title="View Details">
                                        <span class="material-symbols-outlined text-[18px]">visibility</span>
                                    </button>
                                    <button class="size-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 hover:text-primary transition-colors" title="More Options">
                                        <span class="material-symbols-outlined text-[18px]">more_vert</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <!-- Details Row -->
                        <tr id="report-details-<?php echo $report['id']; ?>" class="hidden">
                            <td colspan="8" class="p-0">
                                <div class="bg-slate-50 p-6 border-t border-slate-200">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <?php if ($report['plan']): ?>
                                        <div>
                                            <h4 class="text-sm font-semibold text-slate-900 mb-2 flex items-center gap-2">
                                                <span class="material-symbols-outlined text-blue-600 text-[16px]">assignment</span>
                                                Work Plan
                                            </h4>
                                            <div class="bg-white p-3 rounded border text-sm text-slate-700">
                                                <?php echo nl2br(htmlspecialchars($report['plan'])); ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($report['daily_report']): ?>
                                        <div>
                                            <h4 class="text-sm font-semibold text-slate-900 mb-2 flex items-center gap-2">
                                                <span class="material-symbols-outlined text-green-600 text-[16px]">summarize</span>
                                                Daily Report
                                            </h4>
                                            <div class="bg-white p-3 rounded border text-sm text-slate-700">
                                                <?php echo nl2br(htmlspecialchars($report['daily_report'])); ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Users Tab -->
        <div id="users" class="tab-content hidden">
            <!-- Header Section -->
            <div class="mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-text-primary text-3xl font-bold tracking-tight">Staff Directory</h1>
                        <p class="text-text-secondary text-sm mt-1">Manage employees, roles, and access permissions.</p>
                    </div>
                    <button onclick="toggleCreateForm()" class="flex cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-5 bg-primary hover:bg-primary/90 transition-colors text-white gap-2 text-sm font-semibold shadow-sm">
                        <span class="material-symbols-outlined text-[20px]">add</span>
                        <span>Add New Staff</span>
                    </button>
                </div>
            </div>

            <!-- Filters & Toolbar -->
            <div class="mb-6">
                <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col sm:flex-row gap-4 justify-between items-center shadow-sm">
                    <div class="flex flex-1 w-full sm:w-auto gap-3">
                        <div class="relative flex-1 max-w-md">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 material-symbols-outlined text-[20px]">search</span>
                            <input class="w-full pl-10 pr-4 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm text-slate-900 placeholder:text-slate-400" placeholder="Search by name or ID..." type="text"/>
                        </div>
                        <div class="relative min-w-[140px] hidden sm:block">
                            <select class="w-full appearance-none pl-4 pr-10 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm text-slate-900 cursor-pointer">
                                <option value="">All Categories</option>
                                <option value="staff">Staff</option>
                                <option value="student">Student</option>
                            </select>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none material-symbols-outlined text-[20px]">expand_more</span>
                        </div>
                        <div class="relative min-w-[140px] hidden sm:block">
                            <select class="w-full appearance-none pl-4 pr-10 py-2 rounded-lg border border-slate-200 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary text-sm text-slate-900 cursor-pointer">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none material-symbols-outlined text-[20px]">expand_more</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <button class="flex items-center gap-2 px-3 py-2 text-slate-500 hover:text-slate-700 hover:bg-slate-50 rounded-lg transition-colors border border-transparent hover:border-slate-200 text-sm font-medium">
                            <span class="material-symbols-outlined text-[18px]">download</span>
                            <span class="hidden sm:inline">Export</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Create User Form (Hidden by default) -->
            <div id="create-user-form" class="hidden mb-6">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-slate-900">Create New User</h3>
                        <button onclick="toggleCreateForm()" class="text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">First Name</label>
                            <input type="text" name="firstname" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Last Name</label>
                            <input type="text" name="lastname" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                            <input type="email" name="email" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Password</label>
                            <input type="password" name="password" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Barcode</label>
                            <input type="text" name="barcode" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Category</label>
                            <select name="category" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="staff">Staff</option>
                                <option value="student">Student</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" name="create_user" class="px-6 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg transition-colors">
                                Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Staff Table -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead class="sticky top-0 bg-slate-50 z-10">
                            <tr class="border-b border-slate-200">
                                <th class="py-3 px-4 w-12">
                                    <input class="rounded border-gray-300 text-primary focus:ring-primary/20 cursor-pointer w-4 h-4" type="checkbox"/>
                                </th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider min-w-[200px]">Employee</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider min-w-[120px]">Staff ID</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider min-w-[100px]">Role</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider min-w-[100px]">Status</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center min-w-[100px]">Barcode</th>
                                <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right min-w-[120px]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <?php while ($user = $users->fetch_assoc()): ?>
                            <tr class="group hover:bg-slate-50/50 transition-colors">
                                <td class="py-4 px-4">
                                    <input class="rounded border-gray-300 text-primary focus:ring-primary/20 cursor-pointer w-4 h-4" type="checkbox"/>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="size-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm flex-shrink-0">
                                            <?php echo strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1)); ?>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-900 truncate"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></p>
                                            <p class="text-xs text-slate-500 truncate"><?php echo htmlspecialchars($user['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="text-sm font-medium text-slate-500 font-mono"><?php echo htmlspecialchars($user['barcode']); ?></span>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap <?php echo $user['category'] == 'staff' ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'bg-orange-50 text-orange-700 border border-orange-100'; ?>">
                                        <?php echo ucfirst($user['category']); ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap <?php echo $user['is_active'] ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100'; ?>">
                                        <span class="size-1.5 rounded-full <?php echo $user['is_active'] ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <button class="text-slate-400 hover:text-slate-600 transition-colors inline-flex flex-col items-center group/barcode" title="View Barcode">
                                        <span class="material-symbols-outlined text-[20px]">qr_code_2</span>
                                        <span class="text-[10px] opacity-0 group-hover/barcode:opacity-100 transition-opacity">View</span>
                                    </button>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="user.php?id=<?php echo $user['id']; ?>" class="size-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 hover:text-primary transition-colors" title="View Details">
                                            <span class="material-symbols-outlined text-[18px]">visibility</span>
                                        </a>
                                        <button onclick="toggleEdit(<?php echo $user['id']; ?>)" class="size-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 hover:text-primary transition-colors" title="Edit Staff">
                                            <span class="material-symbols-outlined text-[18px]">edit</span>
                                        </button>
                                        <button class="size-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 hover:text-primary transition-colors" title="More Options">
                                            <span class="material-symbols-outlined text-[18px]">more_vert</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Edit Form Row -->
                            <tr id="edit-row-<?php echo $user['id']; ?>" class="hidden">
                                <td colspan="7" class="p-0">
                                    <div class="bg-slate-50 p-6 border-t border-slate-200">
                                        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 mb-2">Barcode</label>
                                                <input type="text" name="barcode" value="<?php echo htmlspecialchars($user['barcode']); ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent font-mono">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 mb-2">First Name</label>
                                                <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 mb-2">Last Name</label>
                                                <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 mb-2">Category</label>
                                                <select name="category" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                                    <option value="staff" <?php echo $user['category'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                                    <option value="student" <?php echo $user['category'] == 'student' ? 'selected' : ''; ?>>Student</option>
                                                </select>
                                            </div>
                                            <div class="flex items-end gap-2">
                                                <button type="submit" name="update_user" class="px-4 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg transition-colors text-sm">
                                                    Update
                                                </button>
                                                <button type="button" onclick="toggleEdit(<?php echo $user['id']; ?>)" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg transition-colors text-sm">
                                                    Cancel
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Settings Tab -->
        <div id="settings" class="tab-content hidden">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-slate-200 dark:border-slate-700 max-w-2xl">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">System Settings</h3>
                </div>
                <div class="p-6">
                    <form method="POST" class="space-y-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-medium text-slate-900 dark:text-white">Enable Clocking</h4>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Allow staff to clock in and out</p>
                                </div>
                                <input type="checkbox" name="clocking_enabled" <?php echo $business['clocking_enabled'] ? 'checked' : ''; ?> class="h-4 w-4 text-primary focus:ring-primary border-slate-300 rounded">
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-medium text-slate-900 dark:text-white">Enable Reporting</h4>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Allow staff to submit reports</p>
                                </div>
                                <input type="checkbox" name="reporting_enabled" <?php echo $business['reporting_enabled'] ? 'checked' : ''; ?> class="h-4 w-4 text-primary focus:ring-primary border-slate-300 rounded">
                            </div>
                        </div>
                        <button type="submit" name="update_settings" class="px-6 py-2 bg-primary hover:bg-primary/90 text-white rounded-lg transition-colors">
                            Update Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
            </div>
        </div>
    </main>
</div>

    <script>
        // Update time
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Tab switching
        function switchTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('bg-white', 'dark:bg-slate-700', 'text-primary', 'shadow-sm');
                btn.classList.add('text-slate-600', 'dark:text-slate-400', 'hover:text-slate-900', 'dark:hover:text-white');
            });
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
            activeBtn.classList.add('bg-white', 'dark:bg-slate-700', 'text-primary', 'shadow-sm');
            activeBtn.classList.remove('text-slate-600', 'dark:text-slate-400', 'hover:text-slate-900', 'dark:hover:text-white');
            
            document.getElementById(tabName).classList.remove('hidden');
        }

        // Toggle create form
        function toggleCreateForm() {
            const form = document.getElementById('create-user-form');
            form.classList.toggle('hidden');
        }

        // Toggle edit form
        function toggleEdit(userId) {
            const editRow = document.getElementById('edit-row-' + userId);
            editRow.classList.toggle('hidden');
        }

        // View report details
        function viewReportDetails(reportId) {
            const detailsRow = document.getElementById('report-details-' + reportId);
            detailsRow.classList.toggle('hidden');
        }

        // Initialize first tab
        switchTab('reports');

    </script>
</body>
</html>