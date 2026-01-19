<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$business_id = $_SESSION['business_id'];

// Get business settings
$stmt = $db->prepare("SELECT * FROM business WHERE id = ?");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$business = $stmt->get_result()->fetch_assoc();

// Get user permissions (admin or can_clock_others)
$user_permissions = ['can_clock_self' => 0]; // default to disabled
try {
    $stmt = $db->prepare("SELECT category, can_clock_others FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        // Only admin or users with can_clock_others permission can clock in
        $user_permissions['can_clock_self'] = ($user_data['category'] === 'admin' || $user_data['can_clock_others'] == 1) ? 1 : 0;
    }
} catch (Exception $e) {
    // Column doesn't exist, use default
}

// Get today's report for this user
$today = TODAY;
$stmt = $db->prepare("SELECT * FROM reports WHERE user_id = ? AND report_date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_report = $stmt->get_result()->fetch_assoc();

$current_time = date('H:i:s');
$message = '';
$error = '';

// Handle barcode scan (clock in)
if (isset($_POST['clock_in']) && $business['clocking_enabled'] && $user_permissions['can_clock_self']) {
    $barcode = trim($_POST['barcode']);

    // Verify barcode belongs to logged-in user
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND barcode = ?");
    $stmt->bind_param("is", $user_id, $barcode);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        if (!$today_report) {
            $stmt = $db->prepare("INSERT INTO reports (user_id, report_date, clock_in_time, status) VALUES (?, ?, NOW(), 'clocked_in')");
            $stmt->bind_param("is", $user_id, $today);
            $stmt->execute();
            $message = 'Successfully clocked in!';
            header('Location: dashboard.php?msg=' . urlencode($message));
            exit;
        } else {
            $error = 'You have already clocked in today.';
        }
    } else {
        $error = 'Invalid barcode for your account.';
    }
}

// Handle plan editing
if (isset($_POST['edit_plan']) && $business['reporting_enabled']) {
    if ($today_report && $today_report['clock_in_time'] && !$today_report['clock_out_time']) {
        $plan = trim($_POST['plan']);
        $stmt = $db->prepare("UPDATE reports SET plan = ? WHERE id = ?");
        $stmt->bind_param("si", $plan, $today_report['id']);
        $stmt->execute();
        $message = 'Plan updated successfully!';
        header('Location: dashboard.php?msg=' . urlencode($message));
        exit;
    }
}

// Handle report editing
if (isset($_POST['edit_report']) && $business['reporting_enabled']) {
    if ($today_report && $today_report['clock_in_time'] && !$today_report['clock_out_time']) {
        $daily_report = trim($_POST['daily_report']);
        $stmt = $db->prepare("UPDATE reports SET daily_report = ? WHERE id = ?");
        $stmt->bind_param("si", $daily_report, $today_report['id']);
        $stmt->execute();
        $message = 'Report updated successfully!';
        header('Location: dashboard.php?msg=' . urlencode($message));
        exit;
    }
}

// Handle plan submission
if (isset($_POST['submit_plan']) && $business['reporting_enabled']) {
    if ($today_report && $today_report['clock_in_time']) {
        $plan = trim($_POST['plan']);
        $stmt = $db->prepare("UPDATE reports SET plan = ?, plan_submitted_at = NOW(), status = 'plan_submitted' WHERE id = ?");
        $stmt->bind_param("si", $plan, $today_report['id']);
        $stmt->execute();
        $message = 'Plan submitted successfully!';
        header('Location: dashboard.php?msg=' . urlencode($message));
        exit;
    } else {
        $error = 'You must clock in before submitting a plan.';
    }
}

// Handle report submission
if (isset($_POST['submit_report']) && $business['reporting_enabled']) {
    if ($today_report && $today_report['plan_submitted_at']) {
        $daily_report = trim($_POST['daily_report']);
        $stmt = $db->prepare("UPDATE reports SET daily_report = ?, report_submitted_at = NOW(), status = 'report_submitted' WHERE id = ?");
        $stmt->bind_param("si", $daily_report, $today_report['id']);
        $stmt->execute();
        $message = 'Daily report submitted successfully!';
        header('Location: dashboard.php?msg=' . urlencode($message));
        exit;
    } else {
        $error = 'You must submit a plan before submitting a report.';
    }
}

// Handle clock out
if (isset($_POST['clock_out']) && $business['clocking_enabled'] && $user_permissions['can_clock_self']) {
    // Clock out is now handled by admin only
}

// Refresh today's report after any updates
$stmt = $db->prepare("SELECT * FROM reports WHERE user_id = ? AND report_date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$today_report = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Staff Dashboard - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#2563eb",
                        "primary-hover": "#1d4ed8",
                        "background": "#f8fafc",
                        "card": "#ffffff",
                        "border-subtle": "#e2e8f0",
                        "text-main": "#0f172a",
                        "text-muted": "#64748b",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "2xl": "1rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        .material-symbols-outlined.filled {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>

<body class="bg-background font-display text-text-main antialiased transition-colors duration-200">
    <div class="flex h-screen w-full overflow-hidden">
        <?php
        // Include sidebar component
        $current_page = 'dashboard.php';
        include 'sidebar.php';
        ?>

        <main class="flex-1 flex flex-col h-full overflow-y-auto bg-background relative">
            <div class="md:hidden flex items-center justify-between p-4 border-b border-border-subtle bg-card">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">schedule</span>
                    <span class="font-bold text-slate-800">TimeTrack Pro</span>
                </div>
                <button onclick="toggleSidebar()" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>

            <div class="layout-content-container flex flex-col max-w-[1200px] w-full mx-auto p-4 md:p-6 lg:p-8 gap-6 md:gap-8">
                <?php if (isset($_GET['msg'])): ?>
                    <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                        <?php echo htmlspecialchars($_GET['msg']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <header class="flex flex-wrap justify-between items-end gap-4">
                    <div class="flex flex-col gap-2">
                        <h1 class="text-3xl md:text-4xl font-extrabold leading-tight tracking-tight text-slate-900">
                            Good <?php echo date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening'); ?>, <?php echo explode(' ', $_SESSION['user_name'])[0]; ?>
                        </h1>
                        <div class="flex items-center gap-2 text-slate-500">
                            <span class="material-symbols-outlined text-[20px]">calendar_today</span>
                            <p class="text-base font-medium"><?php echo date('l, F j, Y'); ?></p>
                            <span class="mx-1 text-slate-300">•</span>
                            <p class="text-base font-medium" id="current-time"><?php echo date('h:i A'); ?></p>
                        </div>
                    </div>
                    <div class="flex h-12 items-center gap-x-3 rounded-full bg-white border <?php echo $today_report && $today_report['clock_in_time'] && !$today_report['clock_out_time'] ? 'border-emerald-100' : 'border-slate-200'; ?> shadow-sm px-6">
                        <div class="relative flex h-3 w-3">
                            <?php if ($today_report && $today_report['clock_in_time'] && !$today_report['clock_out_time']): ?>
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            <?php else: ?>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-slate-400"></span>
                            <?php endif; ?>
                        </div>
                        <p class="<?php echo $today_report && $today_report['clock_in_time'] && !$today_report['clock_out_time'] ? 'text-emerald-700' : 'text-slate-600'; ?> text-sm font-bold tracking-wide">
                            STATUS: <?php echo $today_report && $today_report['clock_in_time'] && !$today_report['clock_out_time'] ? 'CLOCKED IN' : 'CLOCKED OUT'; ?>
                        </p>
                    </div>
                </header>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 flex flex-col gap-6">
                        <!-- Current Session Card -->
                        <div class="relative overflow-hidden rounded-2xl bg-white border border-border-subtle shadow-sm p-6 md:p-8">
                            <div class="absolute top-0 right-0 -mt-24 -mr-24 h-80 w-80 rounded-full bg-blue-50 blur-3xl opacity-60"></div>
                            <div class="relative flex flex-col gap-8">
                                <div class="flex justify-between items-start">
                                    <div class="flex flex-col gap-1">
                                        <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-500">Current Session</h2>
                                        <p class="text-slate-900 font-bold text-xl">Regular Shift</p>
                                    </div>
                                    <div class="rounded-lg bg-blue-50 p-2 text-primary">
                                        <span class="material-symbols-outlined">timelapse</span>
                                    </div>
                                </div>

                                <?php if ($today_report && $today_report['clock_in_time'] && !$today_report['clock_out_time']):
                                    $clockInTime = strtotime($today_report['clock_in_time']);
                                    $currentTime = time();
                                    $elapsed = $currentTime - $clockInTime;
                                    $hours = floor($elapsed / 3600);
                                    $minutes = floor(($elapsed % 3600) / 60);
                                    $seconds = $elapsed % 60;
                                ?>
                                    <div class="flex items-end gap-2 md:gap-4 justify-center py-6 bg-slate-50/50 rounded-2xl border border-slate-100" id="timer-display">
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="flex h-20 w-20 md:h-24 md:w-24 items-center justify-center rounded-xl bg-white border border-slate-200 shadow-sm">
                                                <p class="text-4xl md:text-5xl font-mono font-bold tracking-tighter text-slate-900" id="hours"><?php echo sprintf('%02d', $hours); ?></p>
                                            </div>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Hours</p>
                                        </div>
                                        <div class="flex h-20 md:h-24 items-center pb-6">
                                            <span class="text-3xl md:text-4xl font-bold text-slate-300">:</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="flex h-20 w-20 md:h-24 md:w-24 items-center justify-center rounded-xl bg-white border border-slate-200 shadow-sm">
                                                <p class="text-4xl md:text-5xl font-mono font-bold tracking-tighter text-slate-900" id="minutes"><?php echo sprintf('%02d', $minutes); ?></p>
                                            </div>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Minutes</p>
                                        </div>
                                        <div class="flex h-20 md:h-24 items-center pb-6">
                                            <span class="text-3xl md:text-4xl font-bold text-slate-300">:</span>
                                        </div>
                                        <div class="flex flex-col items-center gap-2">
                                            <div class="flex h-20 w-20 md:h-24 md:w-24 items-center justify-center rounded-xl bg-white border border-blue-200 shadow-sm ring-2 ring-blue-50">
                                                <p class="text-primary text-4xl md:text-5xl font-mono font-bold tracking-tighter" id="seconds"><?php echo sprintf('%02d', $seconds); ?></p>
                                            </div>
                                            <p class="text-xs font-bold text-primary uppercase tracking-widest">Seconds</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="flex items-end gap-2 md:gap-4 justify-center py-6 bg-slate-50/50 rounded-2xl border border-slate-100">
                                        <div class="text-center py-8">
                                            <p class="text-slate-500 text-lg font-medium">Not clocked in</p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-6 mt-2">
                                    <div class="flex flex-col gap-1">
                                        <p class="text-slate-500 text-sm font-medium">Last Clock In</p>
                                        <p class="text-slate-900 text-2xl font-bold">
                                            <?php echo $today_report && $today_report['clock_in_time'] ? date('h:i A', strtotime($today_report['clock_in_time'])) : '--:--'; ?>
                                        </p>
                                    </div>
                                    <div class="flex flex-col gap-1 border-l border-slate-100 pl-6">
                                        <p class="text-slate-500 text-sm font-medium">Shift Total</p>
                                        <p class="text-slate-900 text-2xl font-bold">
                                            <?php
                                            if ($today_report && $today_report['clock_in_time']) {
                                                $clockInTime = strtotime($today_report['clock_in_time']);
                                                $endTime = $today_report['clock_out_time'] ? strtotime($today_report['clock_out_time']) : time();
                                                $totalMinutes = floor(($endTime - $clockInTime) / 60);
                                                $totalHours = floor($totalMinutes / 60);
                                                $remainingMinutes = $totalMinutes % 60;
                                                echo $totalHours . 'h ' . $remainingMinutes . 'm';
                                            } else {
                                                echo '0h 0m';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Clock In/Out Buttons -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php if ($business['clocking_enabled'] && $user_permissions['can_clock_self'] && (!$today_report || !$today_report['clock_in_time'])): ?>
                                <form method="POST" class="contents">
                                    <input type="hidden" name="barcode" id="barcode-input">
                                    <button type="button" onclick="promptBarcode('clock_in')" class="group relative flex h-36 w-full flex-col items-center justify-center gap-3 rounded-2xl bg-white border-2 border-emerald-100 hover:border-emerald-200 hover:bg-emerald-50 hover:shadow-md transition-all duration-200">
                                        <div class="rounded-full bg-emerald-100 p-4 text-emerald-600 group-hover:scale-110 group-hover:bg-emerald-200 transition-all shadow-sm">
                                            <span class="material-symbols-outlined text-[32px]">play_arrow</span>
                                        </div>
                                        <div class="flex flex-col items-center">
                                            <p class="text-xl font-bold text-emerald-600 group-hover:text-emerald-700">Clock In</p>
                                            <p class="text-sm text-slate-500 font-medium">Start your shift</p>
                                        </div>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="group relative flex h-36 w-full flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 opacity-60 cursor-not-allowed transition-all" disabled>
                                    <div class="rounded-full bg-white p-4 text-slate-400 shadow-sm ring-1 ring-slate-100">
                                        <span class="material-symbols-outlined text-[32px]">play_arrow</span>
                                    </div>
                                    <div class="flex flex-col items-center">
                                        <p class="text-lg font-bold text-slate-400">Clock In</p>
                                        <p class="text-sm text-slate-400 font-medium"><?php echo $today_report && $today_report['clock_in_time'] ? 'Already clocked in' : 'Admin only'; ?></p>
                                    </div>
                                </button>
                            <?php endif; ?>

                            <button class="group relative flex h-36 w-full flex-col items-center justify-center gap-3 rounded-2xl bg-white border-2 border-rose-100 hover:border-rose-200 hover:bg-rose-50 hover:shadow-md transition-all duration-200 opacity-60 cursor-not-allowed" disabled>
                                <div class="rounded-full bg-rose-100 p-4 text-rose-600 group-hover:scale-110 group-hover:bg-rose-200 transition-all shadow-sm">
                                    <span class="material-symbols-outlined text-[32px]">stop</span>
                                </div>
                                <div class="flex flex-col items-center">
                                    <p class="text-xl font-bold text-rose-600 group-hover:text-rose-700">Clock Out</p>
                                    <p class="text-sm text-slate-500 font-medium">Admin only</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col gap-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900">Daily Tasks</h3>
                        </div>

                        <div class="flex flex-col gap-4">
                            <!-- Work Plan Card -->
                            <?php if ($business['reporting_enabled'] && $today_report && $today_report['clock_in_time'] && $today_report['plan_submitted_at']): ?>
                                <div class="flex flex-col rounded-xl bg-white border border-border-subtle p-5 shadow-sm">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="rounded-lg bg-emerald-50 p-2.5 text-emerald-600 ring-1 ring-emerald-100">
                                            <span class="material-symbols-outlined">assignment_turned_in</span>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Submitted</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-slate-900">Work Plan</h4>
                                    <p class="text-sm text-slate-500 mt-1 mb-4">Daily tasks and goals for today.</p>
                                    <div class="mt-auto pt-3 border-t border-slate-50">
                                        <div class="flex items-center gap-2 text-xs font-medium text-emerald-600 bg-emerald-50 w-fit px-2 py-1 rounded">
                                            <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                            Submitted at <?php echo date('h:i A', strtotime($today_report['plan_submitted_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($business['reporting_enabled'] && $today_report && $today_report['clock_in_time'] && !$today_report['plan_submitted_at']): ?>
                                <button onclick="showPlanForm()" class="flex flex-col text-left rounded-xl bg-white border border-border-subtle p-5 shadow-sm group hover:border-primary hover:shadow-md transition-all duration-200">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="rounded-lg bg-amber-50 p-2.5 text-amber-600 group-hover:bg-amber-100 transition-colors ring-1 ring-amber-100">
                                            <span class="material-symbols-outlined">assignment</span>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">Pending</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-slate-900 group-hover:text-primary transition-colors">Work Plan</h4>
                                    <p class="text-sm text-slate-500 mt-1 mb-4">Daily tasks and goals for today.</p>
                                    <div class="w-full rounded-lg bg-primary py-3 text-center text-sm font-semibold text-white shadow hover:bg-primary-hover hover:shadow-lg transition-all">
                                        Create Plan
                                    </div>
                                </button>
                            <?php endif; ?>

                            <!-- Daily Report Card -->
                            <?php if ($business['reporting_enabled'] && $today_report && $today_report['plan_submitted_at'] && $today_report['report_submitted_at']): ?>
                                <div class="flex flex-col rounded-xl bg-white border border-border-subtle p-5 shadow-sm">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="rounded-lg bg-emerald-50 p-2.5 text-emerald-600 ring-1 ring-emerald-100">
                                            <span class="material-symbols-outlined">summarize</span>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Submitted</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-slate-900">Daily Report</h4>
                                    <p class="text-sm text-slate-500 mt-1 mb-4">End of day summary and outcomes.</p>
                                    <div class="mt-auto pt-3 border-t border-slate-50">
                                        <div class="flex items-center gap-2 text-xs font-medium text-emerald-600 bg-emerald-50 w-fit px-2 py-1 rounded">
                                            <span class="material-symbols-outlined text-[14px]">check_circle</span>
                                            Submitted at <?php echo date('h:i A', strtotime($today_report['report_submitted_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($business['reporting_enabled'] && $today_report && $today_report['plan_submitted_at'] && !$today_report['report_submitted_at']): ?>
                                <button onclick="showReportForm()" class="flex flex-col text-left rounded-xl bg-white border border-border-subtle p-5 shadow-sm group hover:border-primary hover:shadow-md transition-all duration-200">
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="rounded-lg bg-amber-50 p-2.5 text-amber-600 group-hover:bg-amber-100 transition-colors ring-1 ring-amber-100">
                                            <span class="material-symbols-outlined">summarize</span>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">Pending</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-slate-900 group-hover:text-primary transition-colors">Daily Report</h4>
                                    <p class="text-sm text-slate-500 mt-1 mb-4">End of day summary and outcomes.</p>
                                    <div class="w-full rounded-lg bg-primary py-3 text-center text-sm font-semibold text-white shadow hover:bg-primary-hover hover:shadow-lg transition-all">
                                        Create Report
                                    </div>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Plan Form Modal -->
    <div id="plan-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900">Submit Daily Plan</h3>
                <button onclick="hidePlanForm()" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Your plan for today:</label>
                    <textarea name="plan" rows="4" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Describe your plan for today..." required></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" name="<?php echo $today_report && $today_report['plan'] ? 'edit_plan' : 'submit_plan'; ?>" class="flex-1 px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg transition-colors">
                        <?php echo $today_report && $today_report['plan'] ? 'Update Plan' : 'Submit Plan'; ?>
                    </button>
                    <button type="button" onclick="hidePlanForm()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Form Modal -->
    <div id="report-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-900">Submit Daily Report</h3>
                <button onclick="hideReportForm()" class="text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <?php if ($today_report && $today_report['plan']): ?>
                <div class="mb-4 p-3 bg-slate-50 rounded-lg">
                    <p class="text-sm font-medium text-slate-700 mb-1">Your Plan:</p>
                    <p class="text-sm text-slate-600"><?php echo nl2br(htmlspecialchars($today_report['plan'])); ?></p>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Your report for today:</label>
                    <textarea name="daily_report" rows="4" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Describe what you accomplished today..." required></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" name="<?php echo $today_report && $today_report['daily_report'] ? 'edit_report' : 'submit_report'; ?>" class="flex-1 px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg transition-colors">
                        <?php echo $today_report && $today_report['daily_report'] ? 'Update Report' : 'Submit Report'; ?>
                    </button>
                    <button type="button" onclick="hideReportForm()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle mobile sidebar
        function toggleSidebar() {
            const mobileSidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (mobileSidebar.classList.contains('-translate-x-full')) {
                mobileSidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                mobileSidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }

        // Update time every second
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        setInterval(updateTime, 1000);

        // Update timer if clocked in
        <?php if ($today_report && $today_report['clock_in_time'] && !$today_report['clock_out_time']): ?>
            const clockInTime = new Date('<?php echo date('c', strtotime($today_report['clock_in_time'])); ?>');

            function updateTimer() {
                const now = new Date();
                const elapsed = Math.floor((now - clockInTime) / 1000);

                const hours = Math.floor(elapsed / 3600);
                const minutes = Math.floor((elapsed % 3600) / 60);
                const seconds = elapsed % 60;

                document.getElementById('hours').textContent = String(hours).padStart(2, '0');
                document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
                document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
            }

            setInterval(updateTimer, 1000);
            updateTimer();
        <?php endif; ?>

        function promptBarcode(action) {
            const barcode = prompt('Please scan or enter your barcode:');
            if (barcode) {
                document.getElementById('barcode-input').value = barcode;
                if (action === 'clock_in') {
                    document.querySelector('input[name="clock_in"]').click();
                }
            }
        }

        function showPlanForm() {
            document.getElementById('plan-modal').classList.remove('hidden');
        }

        function hidePlanForm() {
            document.getElementById('plan-modal').classList.add('hidden');
        }

        function showReportForm() {
            document.getElementById('report-modal').classList.remove('hidden');
        }

        function hideReportForm() {
            document.getElementById('report-modal').classList.add('hidden');
        }

        function editPlan() {
            document.getElementById('plan-modal').classList.remove('hidden');
            document.querySelector('textarea[name="plan"]').value = '<?php echo addslashes($today_report['plan'] ?? ''); ?>';
        }

        function editReport() {
            document.getElementById('report-modal').classList.remove('hidden');
            document.querySelector('textarea[name="daily_report"]').value = '<?php echo addslashes($today_report['daily_report'] ?? ''); ?>';
        }

        // Add hidden submit buttons for form submission
        document.addEventListener('DOMContentLoaded', function() {
            const clockInForm = document.querySelector('form[method="POST"]');
            if (clockInForm) {
                const hiddenSubmit = document.createElement('input');
                hiddenSubmit.type = 'submit';
                hiddenSubmit.name = 'clock_in';
                hiddenSubmit.style.display = 'none';
                clockInForm.appendChild(hiddenSubmit);
            }
        });
    </script>
</body>

</html>