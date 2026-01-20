<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$user_id = $_GET['id'] ?? 0;

// Handle permission update
if (isset($_POST['update_clock_others'])) {
    $can_clock_others = isset($_POST['can_clock_others']) ? 1 : 0;

    // Check if column exists first
    $stmt = $db->prepare("SHOW COLUMNS FROM users LIKE 'can_clock_others'");
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        $stmt = $db->prepare("UPDATE users SET can_clock_others = ? WHERE id = ? AND business_id = ?");
        $stmt->bind_param("iii", $can_clock_others, $user_id, $business_id);
        $stmt->execute();

        header('Location: user.php?id=' . $user_id . '&msg=Permission updated successfully');
    } else {
        header('Location: user.php?id=' . $user_id . '&msg=Database needs migration - run migration_clock_others.sql');
    }
    exit;
}

// Get user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND business_id = ?");
$stmt->bind_param("ii", $user_id, $business_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: dashboard.php');
    exit;
}

// Get user reports
$stmt = $db->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY report_date DESC LIMIT 30");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reports = $stmt->get_result();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>TimeTrack Pro - <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?> History</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#135bec",
                        "primary-dark": "#0e45b5",
                        "background-light": "#f8f9fc",
                        "background-dark": "#101622",
                        "surface-light": "#ffffff",
                        "surface-dark": "#1a2231",
                        "text-main": "#0d121b",
                        "text-secondary": "#4c669a",
                        "border-color": "#e7ebf3",
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
        body {
            font-family: 'Inter', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .material-symbols-outlined.filled {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>

<body class="bg-background-light dark:bg-background-dark font-display text-text-main dark:text-white overflow-hidden">
    <div class="flex h-screen w-full overflow-hidden">
        <?php
        $current_page = 'users.php';
        include 'sidebar.php';
        ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <?php include 'header.php'; ?>

            <div class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark">
                <div class="max-w-[1280px] mx-auto px-4 sm:px-6 md:px-10 py-8">
                    <!-- Breadcrumbs -->
                    <nav class="flex items-center gap-2 mb-6 text-sm">
                        <a class="text-text-secondary hover:text-primary transition-colors font-medium" href="dashboard.php">Dashboard</a>
                        <span class="text-text-secondary/50 font-medium">/</span>
                        <a class="text-text-secondary hover:text-primary transition-colors font-medium" href="users.php">Staff Directory</a>
                        <span class="text-text-secondary/50 font-medium">/</span>
                        <span class="text-text-main dark:text-white font-medium"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></span>
                    </nav>

                    <?php if (isset($_GET['msg'])): ?>
                        <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                            <?php echo htmlspecialchars($_GET['msg']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Page Heading -->
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
                        <div class="flex flex-col gap-2">
                            <h1 class="text-text-main dark:text-white text-3xl md:text-4xl font-black leading-tight tracking-tight"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?> - Attendance History</h1>
                            <p class="text-text-secondary text-base font-normal max-w-2xl">Review attendance records, monitor punctuality, and manage user permissions.</p>
                        </div>
                        <div class="flex gap-3">
                            <button onclick="togglePermissions()" class="bg-primary hover:bg-primary-dark text-white shadow-lg shadow-primary/30 px-5 py-2.5 rounded-lg font-bold text-sm flex items-center gap-2 transition-all">
                                <span class="material-symbols-outlined text-[20px]">admin_panel_settings</span>
                                <span>Permissions</span>
                            </button>
                        </div>
                    </div>

                    <!-- User Info Card -->
                    <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm border border-border-color p-6 mb-8">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="flex items-center gap-4">
                                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-full size-16 flex items-center justify-center text-white font-bold text-xl">
                                    <?php echo strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1)); ?>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-text-main dark:text-white"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h3>
                                    <p class="text-text-secondary"><?php echo htmlspecialchars($user['email']); ?></p>
                                    <p class="text-xs text-text-secondary font-mono">ID: <?php echo htmlspecialchars($user['barcode']); ?></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-text-secondary uppercase tracking-wider font-semibold mb-1">Category</p>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $user['category'] == 'staff' ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'bg-orange-50 text-orange-700 border border-orange-100'; ?>">
                                        <?php echo ucfirst($user['category']); ?>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-xs text-text-secondary uppercase tracking-wider font-semibold mb-1">Status</p>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?php echo $user['is_active'] ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100'; ?>">
                                        <span class="size-1.5 rounded-full <?php echo $user['is_active'] ? 'bg-green-500' : 'bg-red-500'; ?>"></span>
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs text-text-secondary uppercase tracking-wider font-semibold mb-2">Permissions</p>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="size-2 rounded-full <?php echo isset($user['can_clock']) && $user['can_clock'] ? 'bg-green-500' : 'bg-gray-300'; ?>"></span>
                                        <span class="text-sm text-text-main dark:text-white">Can Clock In/Out</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="size-2 rounded-full <?php echo isset($user['can_clock_others']) && $user['can_clock_others'] ? 'bg-green-500' : 'bg-gray-300'; ?>"></span>
                                        <span class="text-sm text-text-main dark:text-white">Can Clock Others</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions Form (Hidden by default) -->
                    <div id="permissions-form" class="hidden mb-8">
                        <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm border border-border-color p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-text-main dark:text-white">Manage Permissions</h3>
                                <button onclick="togglePermissions()" class="text-text-secondary hover:text-text-main">
                                    <span class="material-symbols-outlined">close</span>
                                </button>
                            </div>
                            <form method="POST" class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-background-light rounded-lg">
                                    <div>
                                        <h4 class="text-sm font-medium text-text-main dark:text-white">Clock Other Users</h4>
                                        <p class="text-sm text-text-secondary">Allow this user to clock in/out other staff members</p>
                                    </div>
                                    <input type="checkbox" name="can_clock_others" <?php echo isset($user['can_clock_others']) && $user['can_clock_others'] ? 'checked' : ''; ?> class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                                </div>
                                <button type="submit" name="update_clock_others" class="px-6 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg transition-colors">
                                    Update Permissions
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="border-b border-border-color mb-8">
                        <div class="flex gap-8 overflow-x-auto">
                            <button onclick="switchTab('history')" class="tab-btn group flex items-center gap-2 border-b-[3px] border-primary px-1 pb-3 pt-2" data-tab="history">
                                <span class="material-symbols-outlined text-primary text-[20px]">history</span>
                                <p class="text-primary text-sm font-bold whitespace-nowrap">Clocking History</p>
                            </button>
                            <button onclick="switchTab('plans')" class="tab-btn group flex items-center gap-2 border-b-[3px] border-transparent hover:border-text-secondary/30 px-1 pb-3 pt-2 transition-colors" data-tab="plans">
                                <span class="material-symbols-outlined text-text-secondary group-hover:text-text-main text-[20px]">calendar_month</span>
                                <p class="text-text-secondary group-hover:text-text-main text-sm font-bold whitespace-nowrap transition-colors">Work Plans</p>
                            </button>
                            <button onclick="switchTab('reports')" class="tab-btn group flex items-center gap-2 border-b-[3px] border-transparent hover:border-text-secondary/30 px-1 pb-3 pt-2 transition-colors" data-tab="reports">
                                <span class="material-symbols-outlined text-text-secondary group-hover:text-text-main text-[20px]">description</span>
                                <p class="text-text-secondary group-hover:text-text-main text-sm font-bold whitespace-nowrap transition-colors">Work Reports</p>
                            </button>
                        </div>
                    </div>

                    <!-- Content Card -->
                    <div class="bg-surface-light dark:bg-surface-dark rounded-xl shadow-sm border border-border-color overflow-hidden">
                        <!-- Clocking History Tab -->
                        <div id="history-tab" class="tab-content">
                            <!-- Filter Toolbar -->
                            <div class="p-4 md:p-5 border-b border-border-color bg-white dark:bg-surface-dark flex flex-col md:flex-row gap-4 justify-between items-center">
                                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                                    <div class="relative group">
                                        <button class="flex h-10 items-center justify-between gap-x-2 rounded-lg border border-border-color bg-white dark:bg-background-dark px-3 hover:border-primary transition-colors min-w-[200px]">
                                            <div class="flex items-center gap-2">
                                                <span class="material-symbols-outlined text-text-secondary text-[20px]">calendar_today</span>
                                                <span class="text-text-main dark:text-white text-sm font-medium">Last 30 Days</span>
                                            </div>
                                            <span class="material-symbols-outlined text-text-secondary text-[20px]">expand_more</span>
                                        </button>
                                    </div>
                                    <div class="relative group">
                                        <button class="flex h-10 items-center justify-between gap-x-2 rounded-lg border border-border-color bg-white dark:bg-background-dark px-3 hover:border-primary transition-colors min-w-[140px]">
                                            <div class="flex items-center gap-2">
                                                <span class="material-symbols-outlined text-text-secondary text-[20px]">filter_list</span>
                                                <span class="text-text-main dark:text-white text-sm font-medium">Status: All</span>
                                            </div>
                                            <span class="material-symbols-outlined text-text-secondary text-[20px]">expand_more</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex gap-3 w-full md:w-auto justify-end">
                                    <button class="flex h-10 items-center justify-center gap-2 rounded-lg border border-border-color bg-white dark:bg-background-dark px-4 text-text-main dark:text-white font-semibold text-sm hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">print</span>
                                        Print
                                    </button>
                                    <button class="flex h-10 items-center justify-center gap-2 rounded-lg bg-primary/10 hover:bg-primary/20 text-primary px-4 font-bold text-sm transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">download</span>
                                        Export CSV
                                    </button>
                                </div>
                            </div>

                            <!-- Data Table -->
                            <div class="overflow-x-auto w-full">
                                <table class="w-full text-left border-collapse">
                                    <thead>
                                        <tr class="bg-gray-50/50 dark:bg-background-dark/50 border-b border-border-color text-xs uppercase text-text-secondary font-semibold tracking-wider">
                                            <th class="px-6 py-4">Date</th>
                                            <th class="px-6 py-4">Clock In</th>
                                            <th class="px-6 py-4">Clock Out</th>
                                            <th class="px-6 py-4">Total Hours</th>
                                            <th class="px-6 py-4">Status</th>
                                            <th class="px-6 py-4 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border-color bg-white dark:bg-surface-dark">
                                        <?php
                                        $reports->data_seek(0); // Reset pointer
                                        while ($report = $reports->fetch_assoc()):
                                            $clockIn = $report['clock_in_time'] ? strtotime($report['clock_in_time']) : null;
                                            $clockOut = $report['clock_out_time'] ? strtotime($report['clock_out_time']) : null;
                                            $totalHours = ($clockIn && $clockOut) ? round(($clockOut - $clockIn) / 3600, 1) : 0;

                                            $statusClass = 'bg-gray-100 text-gray-600';
                                            $statusText = 'Absent';
                                            if ($report['status'] == 'clocked_out') {
                                                $statusClass = 'bg-green-100 text-green-700';
                                                $statusText = 'Normal';
                                            } elseif ($report['status'] == 'clocked_in') {
                                                $statusClass = 'bg-orange-100 text-orange-700';
                                                $statusText = 'In Progress';
                                            }
                                        ?>
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors group">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center gap-3">
                                                        <div class="bg-gray-100 dark:bg-gray-800 rounded p-1.5 text-text-secondary">
                                                            <span class="material-symbols-outlined text-[18px]">event</span>
                                                        </div>
                                                        <div class="flex flex-col">
                                                            <span class="text-sm font-semibold text-text-main dark:text-white"><?php echo date('M j, Y', strtotime($report['report_date'])); ?></span>
                                                            <span class="text-xs text-text-secondary"><?php echo date('l', strtotime($report['report_date'])); ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php if ($report['clock_in_time']): ?>
                                                        <span class="text-sm font-medium text-primary bg-primary/5 px-2 py-1 rounded"><?php echo date('h:i A', strtotime($report['clock_in_time'])); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-sm font-medium text-text-secondary">--:--</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php if ($report['clock_out_time']): ?>
                                                        <span class="text-sm font-medium text-text-main dark:text-white"><?php echo date('h:i A', strtotime($report['clock_out_time'])); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-sm font-medium text-text-secondary">--:--</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="text-sm font-bold text-text-main dark:text-white"><?php echo $totalHours > 0 ? $totalHours . 'h' : '0h'; ?></span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold <?php echo $statusClass; ?> border">
                                                        <span class="size-1.5 rounded-full bg-current"></span>
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                                    <button onclick="viewDetails(<?php echo $report['id']; ?>)" class="text-text-secondary hover:text-primary p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors opacity-0 group-hover:opacity-100">
                                                        <span class="material-symbols-outlined text-[20px]">more_vert</span>
                                                    </button>
                                                </td>
                                            </tr>
                                            <!-- Details Row -->
                                            <tr id="details-<?php echo $report['id']; ?>" class="hidden">
                                                <td colspan="6" class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50">
                                                    <div class="space-y-4">
                                                        <?php if ($report['plan']): ?>
                                                            <div>
                                                                <h4 class="text-sm font-semibold text-text-main dark:text-white mb-2">Work Plan</h4>
                                                                <p class="text-sm text-text-secondary bg-white dark:bg-surface-dark p-3 rounded border"><?php echo nl2br(htmlspecialchars($report['plan'])); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if ($report['daily_report']): ?>
                                                            <div>
                                                                <h4 class="text-sm font-semibold text-text-main dark:text-white mb-2">Daily Report</h4>
                                                                <p class="text-sm text-text-secondary bg-white dark:bg-surface-dark p-3 rounded border"><?php echo nl2br(htmlspecialchars($report['daily_report'])); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Work Plans Tab -->
                        <div id="plans-tab" class="tab-content hidden">
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-lg font-semibold text-text-main dark:text-white mb-2">Work Plans Submitted</h3>
                                    <p class="text-text-secondary text-sm">Daily work plans submitted by <?php echo htmlspecialchars($user['firstname']); ?></p>
                                </div>
                                <div class="space-y-4">
                                    <?php
                                    $reports->data_seek(0); // Reset pointer
                                    $hasPlans = false;
                                    while ($report = $reports->fetch_assoc()):
                                        if ($report['plan']):
                                            $hasPlans = true;
                                    ?>
                                            <div class="bg-white dark:bg-background-dark border border-border-color rounded-lg p-4">
                                                <div class="flex items-start justify-between mb-3">
                                                    <div class="flex items-center gap-3">
                                                        <div class="bg-blue-100 dark:bg-blue-900/30 rounded p-2">
                                                            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-[20px]">calendar_month</span>
                                                        </div>
                                                        <div>
                                                            <h4 class="text-sm font-semibold text-text-main dark:text-white"><?php echo date('M j, Y', strtotime($report['report_date'])); ?></h4>
                                                            <p class="text-xs text-text-secondary"><?php echo date('l', strtotime($report['report_date'])); ?></p>
                                                        </div>
                                                    </div>
                                                    <?php if ($report['plan_submitted_at']): ?>
                                                        <span class="text-xs text-text-secondary bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">
                                                            Submitted at <?php echo date('h:i A', strtotime($report['plan_submitted_at'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="bg-gray-50 dark:bg-gray-800/50 rounded p-3">
                                                    <p class="text-sm text-text-main dark:text-white whitespace-pre-wrap"><?php echo htmlspecialchars($report['plan']); ?></p>
                                                </div>
                                            </div>
                                        <?php
                                        endif;
                                    endwhile;
                                    if (!$hasPlans):
                                        ?>
                                        <div class="text-center py-12">
                                            <div class="bg-gray-100 dark:bg-gray-800 rounded-full size-16 flex items-center justify-center mx-auto mb-4">
                                                <span class="material-symbols-outlined text-gray-400 text-[32px]">calendar_month</span>
                                            </div>
                                            <h3 class="text-lg font-medium text-text-main dark:text-white mb-2">No Work Plans</h3>
                                            <p class="text-text-secondary">This user hasn't submitted any work plans yet.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Work Reports Tab -->
                        <div id="reports-tab" class="tab-content hidden">
                            <div class="p-6">
                                <div class="mb-6">
                                    <h3 class="text-lg font-semibold text-text-main dark:text-white mb-2">Work Reports Submitted</h3>
                                    <p class="text-text-secondary text-sm">Daily work reports submitted by <?php echo htmlspecialchars($user['firstname']); ?></p>
                                </div>
                                <div class="space-y-4">
                                    <?php
                                    $reports->data_seek(0); // Reset pointer
                                    $hasReports = false;
                                    while ($report = $reports->fetch_assoc()):
                                        if ($report['daily_report']):
                                            $hasReports = true;
                                    ?>
                                            <div class="bg-white dark:bg-background-dark border border-border-color rounded-lg p-4">
                                                <div class="flex items-start justify-between mb-3">
                                                    <div class="flex items-center gap-3">
                                                        <div class="bg-green-100 dark:bg-green-900/30 rounded p-2">
                                                            <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-[20px]">description</span>
                                                        </div>
                                                        <div>
                                                            <h4 class="text-sm font-semibold text-text-main dark:text-white"><?php echo date('M j, Y', strtotime($report['report_date'])); ?></h4>
                                                            <p class="text-xs text-text-secondary"><?php echo date('l', strtotime($report['report_date'])); ?></p>
                                                        </div>
                                                    </div>
                                                    <?php if ($report['report_submitted_at']): ?>
                                                        <span class="text-xs text-text-secondary bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">
                                                            Submitted at <?php echo date('h:i A', strtotime($report['report_submitted_at'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="bg-gray-50 dark:bg-gray-800/50 rounded p-3">
                                                    <p class="text-sm text-text-main dark:text-white whitespace-pre-wrap"><?php echo htmlspecialchars($report['daily_report']); ?></p>
                                                </div>
                                            </div>
                                        <?php
                                        endif;
                                    endwhile;
                                    if (!$hasReports):
                                        ?>
                                        <div class="text-center py-12">
                                            <div class="bg-gray-100 dark:bg-gray-800 rounded-full size-16 flex items-center justify-center mx-auto mb-4">
                                                <span class="material-symbols-outlined text-gray-400 text-[32px]">description</span>
                                            </div>
                                            <h3 class="text-lg font-medium text-text-main dark:text-white mb-2">No Work Reports</h3>
                                            <p class="text-text-secondary">This user hasn't submitted any work reports yet.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
        </main>

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
        </script>

        <script>
            function togglePermissions() {
                const form = document.getElementById('permissions-form');
                form.classList.toggle('hidden');
            }

            function viewDetails(reportId) {
                const row = document.getElementById('details-' + reportId);
                row.classList.toggle('hidden');
            }

            function switchTab(tabName) {
                // Update tab buttons
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    const icon = btn.querySelector('.material-symbols-outlined');
                    const text = btn.querySelector('p');

                    btn.classList.remove('border-primary');
                    btn.classList.add('border-transparent', 'hover:border-text-secondary/30');
                    icon.classList.remove('text-primary');
                    icon.classList.add('text-text-secondary', 'group-hover:text-text-main');
                    text.classList.remove('text-primary');
                    text.classList.add('text-text-secondary', 'group-hover:text-text-main');
                });

                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.add('hidden');
                });

                // Show active tab
                const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
                const activeIcon = activeBtn.querySelector('.material-symbols-outlined');
                const activeText = activeBtn.querySelector('p');

                activeBtn.classList.add('border-primary');
                activeBtn.classList.remove('border-transparent', 'hover:border-text-secondary/30');
                activeIcon.classList.add('text-primary');
                activeIcon.classList.remove('text-text-secondary', 'group-hover:text-text-main');
                activeText.classList.add('text-primary');
                activeText.classList.remove('text-text-secondary', 'group-hover:text-text-main');

                document.getElementById(tabName + '-tab').classList.remove('hidden');
            }

            // Initialize with history tab active
            document.addEventListener('DOMContentLoaded', function() {
                switchTab('history');
            });
        </script>
</body>

</html>