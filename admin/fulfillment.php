<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'];
$fulfillment_date = $_GET['date'] ?? date('Y-m-d');

// Handle fulfillment marking
if (isset($_POST['mark_fulfillment'])) {
    $slot_id = $_POST['slot_id'];
    $status = $_POST['status'];
    $actual_start_time = $_POST['actual_start_time'] ?: null;
    $actual_end_time = $_POST['actual_end_time'] ?: null;
    $notes = trim($_POST['notes']);
    
    // Check if fulfillment record exists
    $check_stmt = $db->prepare("SELECT id FROM timetable_fulfillment WHERE slot_id = ? AND fulfillment_date = ?");
    $check_stmt->bind_param("is", $slot_id, $fulfillment_date);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        // Update existing record
        $stmt = $db->prepare("UPDATE timetable_fulfillment SET status = ?, actual_start_time = ?, actual_end_time = ?, notes = ?, marked_by = ?, marked_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssssii", $status, $actual_start_time, $actual_end_time, $notes, $user_id, $existing['id']);
    } else {
        // Create new record
        $stmt = $db->prepare("INSERT INTO timetable_fulfillment (slot_id, fulfillment_date, status, actual_start_time, actual_end_time, notes, marked_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $slot_id, $fulfillment_date, $status, $actual_start_time, $actual_end_time, $notes, $user_id);
    }
    
    if ($stmt->execute()) {
        header("Location: fulfillment.php?date=$fulfillment_date&msg=Activity updated successfully");
        exit;
    }
}

// Get user's active timetable
$timetable_query = "SELECT * FROM timetables WHERE business_id = ? AND user_id = ? AND is_active = 1 ORDER BY created_at DESC LIMIT 1";
$stmt = $db->prepare($timetable_query);
$stmt->bind_param("ii", $business_id, $user_id);
$stmt->execute();
$active_timetable = $stmt->get_result()->fetch_assoc();

$day_of_week = strtolower(date('l', strtotime($fulfillment_date)));
$schedule_items = [];

if ($active_timetable) {
    // Get today's schedule with fulfillment status
    $schedule_query = "
        SELECT ts.*, tf.status as fulfillment_status, tf.actual_start_time, tf.actual_end_time, tf.notes as fulfillment_notes, tf.marked_at
        FROM timetable_slots ts
        LEFT JOIN timetable_fulfillment tf ON ts.id = tf.slot_id AND tf.fulfillment_date = ?
        WHERE ts.timetable_id = ? AND ts.day_of_week = ?
        ORDER BY ts.start_time
    ";
    
    $stmt = $db->prepare($schedule_query);
    $stmt->bind_param("sis", $fulfillment_date, $active_timetable['id'], $day_of_week);
    $stmt->execute();
    $schedule_items = $stmt->get_result();
}

// Calculate daily stats
$stats = [
    'total_items' => 0,
    'completed_items' => 0,
    'in_progress_items' => 0,
    'total_scheduled_time' => '0:00'
];

if ($active_timetable) {
    $stats_query = "
        SELECT 
            COUNT(ts.id) as total_items,
            COUNT(CASE WHEN tf.status = 'completed' THEN 1 END) as completed_items,
            COUNT(CASE WHEN tf.status = 'in_progress' THEN 1 END) as in_progress_items,
            SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(ts.end_time, ts.start_time)))) as total_scheduled_time
        FROM timetable_slots ts
        LEFT JOIN timetable_fulfillment tf ON ts.id = tf.slot_id AND tf.fulfillment_date = ?
        WHERE ts.timetable_id = ? AND ts.day_of_week = ?
    ";
    
    $stmt = $db->prepare($stats_query);
    $stmt->bind_param("sis", $fulfillment_date, $active_timetable['id'], $day_of_week);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
}

// Get user info
$stmt = $db->prepare("SELECT firstname, lastname, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Teacher Activity Fulfilment - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    </style>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#135bec",
                        "primary-hover": "#0f4bc4",
                        "background-light": "#f6f6f8",
                        "background-dark": "#101622",
                        "card-light": "#ffffff",
                        "card-dark": "#1a2233",
                        "text-main": "#0d121b",
                        "text-secondary": "#4c669a",
                        "border-light": "#e7ebf3",
                        "border-dark": "#2a3447",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-text-main dark:text-gray-100 overflow-hidden">
<div class="flex h-screen flex-col overflow-hidden">
    <!-- Top Navigation -->
    <header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-border-light dark:border-border-dark px-6 py-3 bg-card-light dark:bg-card-dark shrink-0 z-20">
        <div class="flex items-center gap-4 text-text-main dark:text-white">
            <div class="size-6 text-primary">
                <svg class="w-full h-full" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                    <path d="M42.4379 44C42.4379 44 36.0744 33.9038 41.1692 24C46.8624 12.9336 42.2078 4 42.2078 4L7.01134 4C7.01134 4 11.6577 12.932 5.96912 23.9969C0.876273 33.9029 7.27094 44 7.27094 44L42.4379 44Z" fill="currentColor"></path>
                </svg>
            </div>
            <h2 class="text-text-main dark:text-white text-lg font-bold leading-tight tracking-[-0.015em]">TimeTrack Pro</h2>
        </div>
        <div class="flex flex-1 justify-end gap-6 items-center">
            <!-- Date Display -->
            <div class="hidden md:flex items-center gap-2 text-sm text-text-secondary dark:text-gray-400">
                <span class="material-symbols-outlined text-[20px]">calendar_today</span>
                <span><?php echo date('F j, Y', strtotime($fulfillment_date)); ?></span>
            </div>
            <div class="h-8 w-[1px] bg-border-light dark:bg-border-dark hidden md:block"></div>
            <button class="relative p-2 text-text-secondary hover:text-primary transition-colors">
                <span class="material-symbols-outlined">notifications</span>
                <span class="absolute top-1.5 right-1.5 size-2 bg-red-500 rounded-full border-2 border-card-light dark:border-card-dark"></span>
            </button>
            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 border-2 border-border-light dark:border-border-dark cursor-pointer hover:border-primary transition-colors" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuC8SWdaqrTPADjFN3A2yN-zTUfCJPhIRH1N1T-bAkavzOH7t6a_6PkaLH3KRvlRWFGTyKeSWWYVl9qSPhPxYtKqeDUVgF4YV9MlVvf1gEFoy-aLsh3cE_DGvoDPQqvBl3JKs0pbpa66Pw9RSxTUUwXM3oHCJ-k5s66JwRkIuv4Cmz8qBmWC-E9twE7gdI-nigTSIY-4fcz2vM15Qy09Zy8zg-lP2kJRRlf4U5y0rKPBvmqampwNapaR7QB48mMI87pv7EAFnkpP_GU");'></div>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden relative">
        <!-- Side Navigation -->
        <aside class="w-64 flex-col bg-card-light dark:bg-card-dark border-r border-border-light dark:border-border-dark hidden md:flex shrink-0">
            <div class="flex flex-col gap-4 p-4">
                <div class="flex flex-col px-2">
                    <h1 class="text-text-main dark:text-white text-base font-medium leading-normal">Main Menu</h1>
                    <p class="text-text-secondary dark:text-gray-400 text-xs font-normal leading-normal uppercase mt-1">Teacher Portal</p>
                </div>
                <nav class="flex flex-col gap-1">
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-secondary hover:bg-background-light dark:hover:bg-gray-800 transition-colors group" href="dashboard.php">
                        <span class="material-symbols-outlined text-[20px] group-hover:text-primary">dashboard</span>
                        <span class="text-sm font-medium group-hover:text-text-main dark:group-hover:text-white">Dashboard</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary dark:bg-primary/20" href="fulfillment.php">
                        <span class="material-symbols-outlined text-[20px] fill">calendar_month</span>
                        <span class="text-sm font-medium">Schedule</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-secondary hover:bg-background-light dark:hover:bg-gray-800 transition-colors group" href="timetable.php">
                        <span class="material-symbols-outlined text-[20px] group-hover:text-primary">schedule</span>
                        <span class="text-sm font-medium group-hover:text-text-main dark:group-hover:text-white">Timetable</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-secondary hover:bg-background-light dark:hover:bg-gray-800 transition-colors group" href="reports.php">
                        <span class="material-symbols-outlined text-[20px] group-hover:text-primary">bar_chart</span>
                        <span class="text-sm font-medium group-hover:text-text-main dark:group-hover:text-white">Reports</span>
                    </a>
                </nav>
                <div class="h-[1px] bg-border-light dark:bg-border-dark my-2"></div>
                <div class="px-2">
                    <p class="text-text-secondary dark:text-gray-400 text-xs font-normal leading-normal uppercase mb-2">Filters</p>
                    <div class="flex flex-col gap-2">
                        <label class="flex items-center gap-2 text-sm text-text-main dark:text-gray-300 cursor-pointer">
                            <input checked="" class="rounded border-border-light text-primary focus:ring-primary/30" type="checkbox"/>
                            <span>Regular Classes</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-text-main dark:text-gray-300 cursor-pointer">
                            <input checked="" class="rounded border-border-light text-primary focus:ring-primary/30" type="checkbox"/>
                            <span>Laboratory</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-text-main dark:text-gray-300 cursor-pointer">
                            <input class="rounded border-border-light text-primary focus:ring-primary/30" type="checkbox"/>
                            <span>Meetings</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="mt-auto p-4 border-t border-border-light dark:border-border-dark">
                <a class="flex items-center gap-3 px-3 py-2 text-text-secondary hover:text-text-main dark:hover:text-white transition-colors" href="settings.php">
                    <span class="material-symbols-outlined text-[20px]">settings</span>
                    <span class="text-sm font-medium">Settings</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark p-6 lg:p-10 relative">
            <div class="mx-auto max-w-[1000px] flex flex-col gap-6 pb-20">
                <!-- Page Header & Action -->
                <div class="flex flex-wrap justify-between items-end gap-4">
                    <div class="flex flex-col gap-1">
                        <h1 class="text-text-main dark:text-white text-3xl font-bold tracking-tight">Daily Schedule</h1>
                        <p class="text-text-secondary dark:text-gray-400 text-sm">View and manage your classes for <?php echo date('l, M j', strtotime($fulfillment_date)); ?>.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Segmented Button -->
                        <div class="flex h-9 items-center rounded-lg bg-border-light dark:bg-border-dark p-1">
                            <label class="flex cursor-pointer h-full items-center justify-center rounded-md px-3 bg-white dark:bg-card-dark shadow-sm text-primary text-xs font-semibold transition-all">
                                <span>Day</span>
                                <input checked="" class="hidden" name="view" type="radio" value="day"/>
                            </label>
                            <label class="flex cursor-pointer h-full items-center justify-center rounded-md px-3 text-text-secondary hover:text-text-main dark:text-gray-400 dark:hover:text-white text-xs font-medium transition-all">
                                <span>Week</span>
                                <input class="hidden" name="view" type="radio" value="week"/>
                            </label>
                        </div>
                        <input type="date" value="<?php echo $fulfillment_date; ?>" onchange="window.location.href='fulfillment.php?date='+this.value" class="flex items-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 h-9 rounded-lg text-sm font-medium transition-colors shadow-sm shadow-primary/20">
                    </div>
                </div>

                <!-- Progress Summary Card -->
                <div class="bg-card-light dark:bg-card-dark rounded-xl p-5 border border-border-light dark:border-border-dark shadow-sm flex flex-col sm:flex-row gap-6 items-center">
                    <div class="flex-1 w-full">
                        <div class="flex justify-between items-end mb-2">
                            <div>
                                <p class="text-text-main dark:text-white text-base font-semibold">Daily Progress</p>
                                <p class="text-text-secondary dark:text-gray-400 text-xs mt-0.5">Keep it up! You are on track.</p>
                            </div>
                            <div class="text-right">
                                <span class="text-2xl font-bold text-primary"><?php echo $stats['completed_items']; ?></span>
                                <span class="text-text-secondary dark:text-gray-500 text-sm font-medium">/ <?php echo $stats['total_items']; ?> Classes</span>
                            </div>
                        </div>
                        <div class="relative h-2.5 w-full rounded-full bg-background-light dark:bg-gray-700 overflow-hidden">
                            <div class="absolute top-0 left-0 h-full rounded-full bg-primary transition-all duration-500 ease-out" style="width: <?php echo $stats['total_items'] > 0 ? round(($stats['completed_items'] / $stats['total_items']) * 100) : 0; ?>%;"></div>
                        </div>
                    </div>
                    <div class="w-px h-12 bg-border-light dark:bg-border-dark hidden sm:block"></div>
                    <div class="flex gap-8 shrink-0 w-full sm:w-auto justify-around sm:justify-start">
                        <div class="flex flex-col items-center sm:items-start">
                            <span class="text-text-secondary dark:text-gray-500 text-xs font-medium uppercase tracking-wider">Hours Scheduled</span>
                            <span class="text-text-main dark:text-white text-lg font-semibold font-mono"><?php echo $stats['total_scheduled_time'] ?: '0:00'; ?></span>
                        </div>
                        <div class="flex flex-col items-center sm:items-start">
                            <span class="text-text-secondary dark:text-gray-500 text-xs font-medium uppercase tracking-wider">Remaining</span>
                            <span class="text-text-main dark:text-white text-lg font-semibold font-mono"><?php echo $stats['total_items'] - $stats['completed_items']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Timeline / Schedule -->
                <div class="flex flex-col gap-4 mt-2">
                    <!-- Header for timeline -->
                    <div class="flex justify-between items-center px-2">
                        <h3 class="text-sm font-semibold text-text-secondary dark:text-gray-400 uppercase tracking-wider">Today's Timeline</h3>
                        <a href="timetable.php" class="text-primary text-sm font-medium hover:underline">View Full Timetable</a>
                    </div>

                    <?php if ($schedule_items && $schedule_items->num_rows > 0): ?>
                        <?php while ($item = $schedule_items->fetch_assoc()): ?>
                        <?php 
                            $is_completed = $item['fulfillment_status'] === 'completed';
                            $is_in_progress = $item['fulfillment_status'] === 'in_progress';
                            $current_time = time();
                            $start_time = strtotime($fulfillment_date . ' ' . $item['start_time']);
                            $end_time = strtotime($fulfillment_date . ' ' . $item['end_time']);
                            $is_current = $current_time >= $start_time && $current_time <= $end_time;
                            
                            $activity_colors = [
                                'class' => 'bg-blue-50 dark:bg-blue-900/30 border-blue-500 text-blue-700 dark:text-blue-300',
                                'lab' => 'bg-green-50 dark:bg-green-900/30 border-green-500 text-green-700 dark:text-green-300',
                                'meeting' => 'bg-purple-50 dark:bg-purple-900/30 border-purple-500 text-purple-700 dark:text-purple-300',
                                'break' => 'bg-orange-50 dark:bg-orange-900/30 border-orange-500 text-orange-700 dark:text-orange-300',
                                'planning' => 'bg-slate-50 dark:bg-slate-800/50 border-slate-400 text-slate-600 dark:text-slate-300',
                                'assembly' => 'bg-amber-50 dark:bg-amber-900/30 border-amber-500 text-amber-700 dark:text-amber-300'
                            ];
                            $color_class = $activity_colors[$item['activity_type']] ?? $activity_colors['class'];
                        ?>
                        <div class="group flex gap-4 relative <?php echo $is_completed ? 'opacity-60 hover:opacity-100' : ''; ?> transition-opacity">
                            <div class="flex flex-col items-center pt-2 w-16 shrink-0">
                                <span class="text-sm font-medium <?php echo $is_current ? 'text-primary font-bold' : 'text-text-secondary dark:text-gray-500'; ?>">
                                    <?php echo date('H:i', strtotime($item['start_time'])); ?>
                                </span>
                                <div class="h-full w-0.5 <?php echo $is_current ? 'bg-primary/20 border-l-2 border-dashed border-primary' : 'bg-border-light dark:bg-border-dark'; ?> mt-2 group-last:hidden"></div>
                            </div>
                            <div class="flex-1 bg-card-light dark:bg-card-dark border <?php echo $is_current ? 'border-2 border-primary/30 ring-4 ring-primary/5' : 'border-border-light dark:border-border-dark'; ?> rounded-xl p-5 shadow-lg shadow-primary/5 flex flex-col md:flex-row gap-4 justify-between items-start md:items-center relative overflow-hidden">
                                <div class="absolute left-0 top-0 bottom-0 w-1.5 <?php echo $color_class; ?> border-l-4"></div>
                                <div class="flex flex-col gap-2 z-10">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-text-main dark:text-white font-bold text-lg <?php echo $is_completed ? 'line-through text-slate-500 dark:text-slate-400' : ''; ?>">
                                            <?php echo htmlspecialchars($item['subject'] ?: ucfirst($item['activity_type'])); ?>
                                        </h4>
                                        <?php if ($is_completed): ?>
                                        <span class="px-2.5 py-1 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-[10px] font-bold uppercase tracking-wide border border-green-200 dark:border-green-800">Completed</span>
                                        <?php elseif ($is_in_progress): ?>
                                        <span class="px-2.5 py-1 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-[10px] font-bold uppercase tracking-wide border border-blue-200 dark:border-blue-800 animate-pulse">In Progress</span>
                                        <?php elseif ($is_current): ?>
                                        <span class="px-2.5 py-1 rounded-full bg-primary/10 text-primary text-[10px] font-bold uppercase tracking-wide border border-primary/20 animate-pulse">Current</span>
                                        <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-[10px] font-bold uppercase tracking-wide border border-gray-200 dark:border-gray-700">Scheduled</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-text-secondary dark:text-gray-400 flex items-center gap-1.5">
                                        <?php if ($item['class_name']): ?>
                                        <span class="material-symbols-outlined text-[18px]">groups</span>
                                        <?php echo htmlspecialchars($item['class_name']); ?>
                                        <?php endif; ?>
                                        <?php if ($item['room']): ?>
                                        <span class="material-symbols-outlined text-[18px]">meeting_room</span>
                                        <?php echo htmlspecialchars($item['room']); ?>
                                        <?php endif; ?>
                                        <span class="material-symbols-outlined text-[18px]">schedule</span>
                                        <?php echo date('g:i A', strtotime($item['start_time'])) . ' - ' . date('g:i A', strtotime($item['end_time'])); ?>
                                    </p>
                                    <?php if ($item['fulfillment_notes']): ?>
                                    <p class="text-xs text-text-secondary dark:text-gray-500 italic mt-1">
                                        Note: <?php echo htmlspecialchars($item['fulfillment_notes']); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-3 z-10 w-full md:w-auto mt-2 md:mt-0">
                                    <?php if ($is_completed): ?>
                                    <span class="text-xs text-text-secondary dark:text-gray-500 italic mr-2">
                                        Marked at <?php echo date('g:i A', strtotime($item['marked_at'])); ?>
                                    </span>
                                    <button onclick="openFulfillmentModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['subject'] ?: ucfirst($item['activity_type'])); ?>', 'completed')" class="text-text-secondary hover:text-red-500 p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Undo">
                                        <span class="material-symbols-outlined text-[20px]">undo</span>
                                    </button>
                                    <?php elseif ($is_current || $is_in_progress): ?>
                                    <button onclick="openFulfillmentModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['subject'] ?: ucfirst($item['activity_type'])); ?>', 'completed')" class="flex-1 md:flex-none flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-md hover:shadow-lg active:scale-95">
                                        <span class="material-symbols-outlined text-[20px]">check_circle</span>
                                        <span>Mark Fulfilled</span>
                                    </button>
                                    <?php else: ?>
                                    <button onclick="openFulfillmentModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['subject'] ?: ucfirst($item['activity_type'])); ?>', 'scheduled')" class="flex items-center justify-center gap-2 bg-white dark:bg-slate-800 border border-border-light dark:border-border-dark text-text-main dark:text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm opacity-50 pointer-events-none grayscale">
                                        <span>Mark Fulfilled</span>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <!-- Empty State -->
                    <div class="flex flex-col items-center justify-center py-8 mt-4 border-t border-dashed border-border-light dark:border-border-dark">
                        <div class="size-12 rounded-full bg-background-light dark:bg-gray-800 flex items-center justify-center mb-3">
                            <span class="material-symbols-outlined text-text-secondary dark:text-gray-500">event_busy</span>
                        </div>
                        <p class="text-sm text-text-secondary dark:text-gray-500">No classes scheduled for today.</p>
                        <a href="timetable.php" class="text-primary text-sm font-medium hover:underline mt-2">Create your timetable</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Fulfillment Modal -->
    <div id="fulfillmentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-card-light dark:bg-card-dark rounded-xl p-6 w-full max-w-md mx-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-text-main dark:text-white">Mark Activity</h3>
                <button onclick="closeFulfillmentModal()" class="text-text-secondary hover:text-text-main dark:hover:text-white">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="mark_fulfillment" value="1">
                <input type="hidden" name="slot_id" id="modal_slot_id">
                
                <div>
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Activity</label>
                    <div class="p-3 bg-background-light dark:bg-background-dark rounded-lg">
                        <span id="modal_activity_name" class="text-sm font-medium text-text-main dark:text-white"></span>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Status</label>
                    <select name="status" id="modal_status" required class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="rescheduled">Rescheduled</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Actual Start Time</label>
                        <input type="time" name="actual_start_time" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Actual End Time</label>
                        <input type="time" name="actual_end_time" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Notes (Optional)</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white" placeholder="Any additional notes..."></textarea>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeFulfillmentModal()" class="flex-1 px-4 py-2 border border-border-light dark:border-border-dark text-text-secondary rounded-lg hover:bg-background-light dark:hover:bg-slate-700 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openFulfillmentModal(slotId, activityName, currentStatus) {
        document.getElementById('modal_slot_id').value = slotId;
        document.getElementById('modal_activity_name').textContent = activityName;
        document.getElementById('modal_status').value = currentStatus === 'completed' ? 'scheduled' : 'completed';
        document.getElementById('fulfillmentModal').classList.remove('hidden');
        document.getElementById('fulfillmentModal').classList.add('flex');
    }

    function closeFulfillmentModal() {
        document.getElementById('fulfillmentModal').classList.add('hidden');
        document.getElementById('fulfillmentModal').classList.remove('flex');
    }

    // Close modal when clicking outside
    document.getElementById('fulfillmentModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeFulfillmentModal();
        }
    });
    </script>
</body>
</html>