<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'];
$schedule_date = $_GET['date'] ?? date('Y-m-d');

// Handle schedule creation
if (isset($_POST['create_schedule'])) {
    $task_id = $_POST['task_id'] ?: null;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $activity_type = $_POST['activity_type'];
    $location = trim($_POST['location']);
    
    $stmt = $db->prepare("INSERT INTO daily_schedules (user_id, task_id, schedule_date, start_time, end_time, activity_type, title, description, location, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssssssi", $user_id, $task_id, $schedule_date, $start_time, $end_time, $activity_type, $title, $description, $location, $user_id);
    
    if ($stmt->execute()) {
        header("Location: daily_schedule.php?date=$schedule_date&msg=Schedule item added successfully");
        exit;
    }
}

// Handle status update
if (isset($_POST['update_status'])) {
    $schedule_id = $_POST['schedule_id'];
    $status = $_POST['status'];
    
    $stmt = $db->prepare("UPDATE daily_schedules SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sii", $status, $schedule_id, $user_id);
    $stmt->execute();
    
    header("Location: daily_schedule.php?date=$schedule_date&msg=Status updated successfully");
    exit;
}

// Get user's schedule for the day
$schedule_query = "
    SELECT ds.*, t.name as task_name, p.name as project_name
    FROM daily_schedules ds
    LEFT JOIN tasks t ON ds.task_id = t.id
    LEFT JOIN projects p ON t.project_id = p.id
    WHERE ds.user_id = ? AND ds.schedule_date = ?
    ORDER BY ds.start_time
";

$stmt = $db->prepare($schedule_query);
$stmt->bind_param("is", $user_id, $schedule_date);
$stmt->execute();
$schedule_items = $stmt->get_result();

// Get user's assigned tasks
$tasks_query = "
    SELECT t.*, p.name as project_name
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    WHERE t.assigned_to = ? AND t.status != 'completed'
    ORDER BY t.due_date, t.priority DESC
";

$stmt = $db->prepare($tasks_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$available_tasks = $stmt->get_result();

// Calculate daily stats
$stats_query = "
    SELECT 
        COUNT(*) as total_items,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_items,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_items,
        SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time)))) as total_scheduled_time
    FROM daily_schedules 
    WHERE user_id = ? AND schedule_date = ?
";

$stmt = $db->prepare($stats_query);
$stmt->bind_param("is", $user_id, $schedule_date);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

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
    <title>My Daily Schedule - TimeTrack Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 20px;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #475569;
        }
        
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .material-symbols-outlined.filled {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-white font-display overflow-hidden">
<div class="flex h-screen flex-col overflow-hidden">
    <!-- Top Navigation -->
    <header class="flex items-center justify-between whitespace-nowrap border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-[#1a202c] px-6 py-3 z-20">
        <div class="flex items-center gap-4 text-slate-900 dark:text-white">
            <div class="size-8 text-primary">
                <span class="material-symbols-outlined !text-3xl">schedule</span>
            </div>
            <h2 class="text-lg font-bold leading-tight tracking-tight">TimeTrack Pro</h2>
        </div>
        <div class="flex flex-1 justify-end gap-6 items-center">
            <!-- Search (Desktop) -->
            <div class="hidden md:flex relative max-w-xs w-full">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <span class="material-symbols-outlined !text-xl">search</span>
                </span>
                <input class="w-full bg-slate-100 dark:bg-slate-800 border-none rounded-lg py-2 pl-10 pr-4 text-sm focus:ring-2 focus:ring-primary text-slate-700 dark:text-slate-200" placeholder="Search tasks, projects..." type="text"/>
            </div>
            <div class="flex gap-2">
                <button class="flex items-center justify-center rounded-lg size-10 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 transition-colors">
                    <span class="material-symbols-outlined">notifications</span>
                </button>
                <button class="flex items-center justify-center rounded-lg size-10 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 transition-colors">
                    <span class="material-symbols-outlined">settings</span>
                </button>
            </div>
            <div class="flex items-center gap-3 border-l border-slate-200 dark:border-slate-700 pl-6">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo ucfirst($user['role']); ?></p>
                </div>
                <div class="bg-center bg-no-repeat bg-cover rounded-full size-10 ring-2 ring-slate-100 dark:ring-slate-700" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuChlMamga9FeLFzlUwaRvgIVGC2zqZmNSkUHXeEPGSZY7cg7HgMJnaZ4UVcq9NAjg2HfFClYEfaiDWtRIhrDouqIt-Wxfd7taVzUUjY4i8T8cyMcolx26mVz-495aJB9oYFswWE7UgHpm-tfcfwWTcYSgv5R9SgudvIr22HOkrG0J46rNcJEO9G4fO49vN3sLMidYlDAkmPjzqSUqgbHULhw8ccK88Ji7Peu7lKhdP9jaAup8P3WgtcZwrBWZR-qFGSWOy0CeZJ1Ok");'>
                </div>
            </div>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar Navigation -->
        <nav class="hidden lg:flex w-64 flex-col justify-between border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-[#1a202c] p-4">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col gap-1 px-2">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Main Menu</p>
                    <a class="flex items-center gap-3 px-3 py-2.5 mt-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group" href="dashboard.php">
                        <span class="material-symbols-outlined text-slate-400 group-hover:text-primary transition-colors">dashboard</span>
                        <span class="text-sm font-medium">Dashboard</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary dark:text-primary dark:bg-primary/20 transition-colors" href="daily_schedule.php">
                        <span class="material-symbols-outlined filled">calendar_month</span>
                        <span class="text-sm font-medium">My Schedule</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group" href="projects.php">
                        <span class="material-symbols-outlined text-slate-400 group-hover:text-primary transition-colors">folder</span>
                        <span class="text-sm font-medium">Projects</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group" href="reports.php">
                        <span class="material-symbols-outlined text-slate-400 group-hover:text-primary transition-colors">bar_chart</span>
                        <span class="text-sm font-medium">Reports</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group" href="team_management.php">
                        <span class="material-symbols-outlined text-slate-400 group-hover:text-primary transition-colors">group</span>
                        <span class="text-sm font-medium">Team</span>
                    </a>
                </div>
            </div>
            <div class="rounded-xl bg-gradient-to-br from-primary to-blue-600 p-4 text-white">
                <div class="flex items-center gap-3 mb-3">
                    <div class="rounded-full bg-white/20 p-2">
                        <span class="material-symbols-outlined">workspace_premium</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold">Pro Plan</p>
                        <p class="text-xs text-blue-100">Expires in 12 days</p>
                    </div>
                </div>
                <button class="w-full rounded-lg bg-white py-2 text-xs font-bold text-primary hover:bg-blue-50 transition-colors">
                    Manage Plan
                </button>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark p-4 md:p-8 custom-scrollbar">
            <div class="mx-auto max-w-6xl flex flex-col gap-6">
                <!-- Page Header & Actions -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">My Daily Schedule</h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-1">Manage your tasks and track your time efficiently for <?php echo date('M j, Y', strtotime($schedule_date)); ?>.</p>
                    </div>
                    <div class="flex gap-3">
                        <input type="date" value="<?php echo $schedule_date; ?>" onchange="window.location.href='daily_schedule.php?date='+this.value" class="h-10 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#1a202c] text-sm font-medium text-slate-700 dark:text-slate-200 shadow-sm">
                        <button onclick="openScheduleModal()" class="flex h-10 items-center justify-center gap-2 rounded-lg bg-primary px-5 text-sm font-bold text-white shadow-md shadow-primary/20 hover:bg-blue-700 transition-colors">
                            <span class="material-symbols-outlined !text-lg">add</span>
                            <span>Add Personal Task</span>
                        </button>
                    </div>
                </div>

                <!-- Progress & Greeting Strip -->
                <div class="rounded-2xl bg-white dark:bg-[#1a202c] p-6 shadow-sm border border-slate-200 dark:border-slate-800">
                    <div class="flex flex-col md:flex-row gap-6 items-center">
                        <div class="flex-1 w-full">
                            <h2 class="text-xl font-bold text-slate-900 dark:text-white">Good <?php echo date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening'); ?>, <?php echo htmlspecialchars($user['firstname']); ?>! 👋</h2>
                            <p class="text-slate-500 text-sm mt-1">You have completed <?php echo $stats['completed_items']; ?> out of <?php echo $stats['total_items']; ?> scheduled items today.</p>
                            <div class="mt-4 h-2.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full bg-primary transition-all duration-500 ease-out" style="width: <?php echo $stats['total_items'] > 0 ? round(($stats['completed_items'] / $stats['total_items']) * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-6 divide-x divide-slate-200 dark:divide-slate-700 w-full md:w-auto">
                            <div class="px-4 text-center first:pl-0">
                                <p class="text-xs font-medium uppercase text-slate-400 tracking-wider">Total Time</p>
                                <p class="text-xl font-bold text-slate-900 dark:text-white mt-1"><?php echo $stats['total_scheduled_time'] ?: '0:00'; ?></p>
                            </div>
                            <div class="px-4 text-center">
                                <p class="text-xs font-medium uppercase text-slate-400 tracking-wider">In Progress</p>
                                <p class="text-xl font-bold text-primary mt-1"><?php echo $stats['in_progress_items']; ?></p>
                            </div>
                            <div class="px-4 text-center last:pr-0">
                                <p class="text-xs font-medium uppercase text-slate-400 tracking-wider">Pending</p>
                                <p class="text-xl font-bold text-orange-500 mt-1"><?php echo $stats['total_items'] - $stats['completed_items'] - $stats['in_progress_items']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Grid Layout -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <!-- Left Column: Context & Timer (Span 4) -->
                    <div class="lg:col-span-4 flex flex-col gap-6">
                        <!-- Clock In/Out Widget -->
                        <div class="relative overflow-hidden rounded-2xl bg-[#101622] text-white shadow-lg">
                            <div class="absolute inset-0 z-0">
                                <img alt="Abstract blurry blue office background" class="h-full w-full object-cover opacity-40" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD2YLTTCAW0h3UmwDaLuCJb6V4yZhTc4v05AmQ1YRXS5SfsIPtCxKzZpL03lzV9JBLIIXKVUsoejfxuSI3MgfSmHg8ZLAwyxYQM4jxp5wY5uPNHKHbbOlDPTW7hTodfayP7n_aOSwh3pAZqzUpuQqnfJmGfx2OziNrIlJMVyg1Y73tIbOqY6CXL16NAfZ-yiTl7K9bcBEHWDEOLr8hYTKeg1-XXiwNXYoFsKnzw0Q1feZfFasnm7YcM5zAgoAg1-6b85DjfUMHagic"/>
                                <div class="absolute inset-0 bg-gradient-to-t from-[#101622] via-[#101622]/60 to-transparent"></div>
                            </div>
                            <div class="relative z-10 flex flex-col p-6">
                                <div class="flex justify-between items-start mb-8">
                                    <span class="inline-flex items-center rounded-full bg-green-500/20 px-2.5 py-1 text-xs font-medium text-green-300 ring-1 ring-inset ring-green-500/40">
                                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-green-400 animate-pulse"></span>
                                        Ready to Work
                                    </span>
                                    <span class="text-slate-300 text-sm font-medium"><?php echo date('M j, Y', strtotime($schedule_date)); ?></span>
                                </div>
                                <div class="flex flex-col gap-1 mb-6">
                                    <p class="text-sm font-medium text-slate-300">Current Time</p>
                                    <p class="text-5xl font-mono font-bold tracking-tight text-white" id="current-time"><?php echo date('H:i:s'); ?></p>
                                    <p class="text-sm text-slate-400 mt-1">Local Time</p>
                                </div>
                                <button class="w-full rounded-lg bg-white py-3 text-sm font-bold text-[#101622] shadow hover:bg-slate-100 transition-colors">
                                    Start Working
                                </button>
                            </div>
                        </div>

                        <!-- Mini Calendar / Upcoming -->
                        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#1a202c] p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="font-bold text-slate-900 dark:text-white">Upcoming</h3>
                                <a class="text-xs font-bold text-primary hover:underline" href="#">View Calendar</a>
                            </div>
                            <div class="flex flex-col gap-3">
                                <?php
                                $upcoming_query = "
                                    SELECT * FROM daily_schedules 
                                    WHERE user_id = ? AND schedule_date > ? AND status != 'completed'
                                    ORDER BY schedule_date, start_time 
                                    LIMIT 3
                                ";
                                $stmt = $db->prepare($upcoming_query);
                                $stmt->bind_param("is", $user_id, $schedule_date);
                                $stmt->execute();
                                $upcoming = $stmt->get_result();
                                
                                while ($item = $upcoming->fetch_assoc()): ?>
                                <div class="flex gap-3 items-start p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer group">
                                    <div class="flex flex-col items-center justify-center h-12 w-12 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 shrink-0">
                                        <span class="text-xs font-bold"><?php echo strtoupper(date('M', strtotime($item['schedule_date']))); ?></span>
                                        <span class="text-lg font-bold leading-none"><?php echo date('j', strtotime($item['schedule_date'])); ?></span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors"><?php echo htmlspecialchars($item['title']); ?></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo date('g:i A', strtotime($item['start_time'])); ?> • <?php echo htmlspecialchars($item['location'] ?: 'No location'); ?></p>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Task List (Span 8) -->
                    <div class="lg:col-span-8 flex flex-col gap-6">
                        <!-- Date Strip & Filter -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-between items-center bg-white dark:bg-[#1a202c] p-2 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                            <div class="flex items-center gap-1 overflow-x-auto max-w-full custom-scrollbar pb-1 sm:pb-0">
                                <?php for ($i = -3; $i <= 3; $i++): 
                                    $date = date('Y-m-d', strtotime("$schedule_date $i days"));
                                    $is_today = $date === date('Y-m-d');
                                    $is_selected = $date === $schedule_date;
                                ?>
                                <a href="daily_schedule.php?date=<?php echo $date; ?>" class="flex flex-col items-center justify-center w-12 h-14 rounded-lg <?php echo $is_selected ? 'bg-primary text-white shadow-md shadow-primary/30 transform scale-105' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500'; ?> transition-all">
                                    <span class="text-[10px] font-bold uppercase"><?php echo $is_today ? 'Today' : date('D', strtotime($date)); ?></span>
                                    <span class="text-lg font-bold"><?php echo date('j', strtotime($date)); ?></span>
                                </a>
                                <?php endfor; ?>
                            </div>
                            <div class="flex items-center gap-2 pr-2">
                                <select class="form-select text-sm border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:ring-primary focus:border-primary py-1.5 pl-3 pr-8">
                                    <option>All Items</option>
                                    <option>Tasks</option>
                                    <option>Meetings</option>
                                    <option>Personal</option>
                                </select>
                            </div>
                        </div>

                        <!-- Schedule Items -->
                        <div class="space-y-6">
                            <?php
                            $current_period = '';
                            $schedule_items->data_seek(0);
                            while ($item = $schedule_items->fetch_assoc()):
                                $hour = (int)date('H', strtotime($item['start_time']));
                                $period = $hour < 12 ? 'Morning' : ($hour < 17 ? 'Afternoon' : 'Evening');
                                
                                if ($period !== $current_period):
                                    $current_period = $period;
                            ?>
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3 px-1"><?php echo $period; ?></h3>
                                <div class="flex flex-col gap-3">
                            <?php endif; ?>
                            
                            <div class="group flex items-center justify-between p-4 <?php echo $item['status'] === 'completed' ? 'bg-slate-50 dark:bg-slate-800/50 opacity-75' : 'bg-white dark:bg-[#1a202c]'; ?> rounded-xl border <?php echo $item['status'] === 'in_progress' ? 'border-l-4 border-l-primary border-y-slate-200 border-r-slate-200 dark:border-slate-700' : 'border-slate-200 dark:border-slate-700'; ?> shadow-sm hover:shadow-md transition-all">
                                <div class="flex items-center gap-4">
                                    <div class="relative flex items-center justify-center">
                                        <input <?php echo $item['status'] === 'completed' ? 'checked' : ''; ?> class="peer h-6 w-6 cursor-pointer appearance-none rounded-md border-2 border-slate-300 bg-white checked:border-primary checked:bg-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800 transition-all" type="checkbox" onchange="updateStatus(<?php echo $item['id']; ?>, this.checked ? 'completed' : 'scheduled')"/>
                                        <span class="material-symbols-outlined pointer-events-none absolute text-white opacity-0 peer-checked:opacity-100 text-base">check</span>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold <?php echo $item['status'] === 'completed' ? 'text-slate-500 dark:text-slate-400 line-through' : 'text-slate-900 dark:text-white'; ?>"><?php echo htmlspecialchars($item['title']); ?></h4>
                                        <div class="flex items-center gap-3 text-xs <?php echo $item['status'] === 'completed' ? 'text-slate-400' : 'text-slate-500 dark:text-slate-400'; ?> mt-1">
                                            <?php if ($item['task_name']): ?>
                                            <span class="inline-flex items-center rounded-md bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 font-medium text-primary border border-blue-100 dark:border-blue-800"><?php echo htmlspecialchars($item['project_name']); ?></span>
                                            <?php else: ?>
                                            <span class="inline-flex items-center rounded-md bg-purple-50 dark:bg-purple-900/30 px-2 py-0.5 font-medium text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-800">Personal</span>
                                            <?php endif; ?>
                                            <span class="flex items-center gap-1"><span class="material-symbols-outlined !text-sm">schedule</span> <?php echo date('g:i A', strtotime($item['start_time'])) . ' - ' . date('g:i A', strtotime($item['end_time'])); ?></span>
                                            <?php if ($item['location']): ?>
                                            <span class="flex items-center gap-1"><span class="material-symbols-outlined !text-sm">location_on</span> <?php echo htmlspecialchars($item['location']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <?php
                                        $duration = (strtotime($item['end_time']) - strtotime($item['start_time'])) / 3600;
                                        ?>
                                        <span class="block text-lg font-bold text-slate-900 dark:text-white"><?php echo number_format($duration, 1); ?>h</span>
                                        <span class="block text-xs text-slate-400">Duration</span>
                                    </div>
                                    <button class="rounded-full p-2 text-slate-400 hover:text-primary hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                        <span class="material-symbols-outlined">more_vert</span>
                                    </button>
                                </div>
                            </div>
                            
                            <?php 
                            // Check if this is the last item or if the next item is in a different period
                            $schedule_items_copy = $schedule_items;
                            $next_item = $schedule_items_copy->fetch_assoc();
                            if ($next_item) {
                                $next_hour = (int)date('H', strtotime($next_item['start_time']));
                                $next_period = $next_hour < 12 ? 'Morning' : ($next_hour < 17 ? 'Afternoon' : 'Evening');
                                if ($next_period !== $current_period) {
                                    echo '</div></div>';
                                }
                            } else {
                                echo '</div></div>';
                            }
                            endwhile; 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Schedule Modal -->
<div id="scheduleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-[#1a202c] rounded-xl p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Add Schedule Item</h3>
            <button onclick="closeScheduleModal()" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Link to Task (Optional)</label>
                <select name="task_id" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                    <option value="">No Task</option>
                    <?php while ($task = $available_tasks->fetch_assoc()): ?>
                        <option value="<?php echo $task['id']; ?>"><?php echo htmlspecialchars($task['project_name'] . ' - ' . $task['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Title</label>
                <input type="text" name="title" required class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Description</label>
                <textarea name="description" rows="2" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white"></textarea>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Start Time</label>
                    <input type="time" name="start_time" required class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">End Time</label>
                    <input type="time" name="end_time" required class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Activity Type</label>
                    <select name="activity_type" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                        <option value="task">Task</option>
                        <option value="meeting">Meeting</option>
                        <option value="break">Break</option>
                        <option value="personal">Personal</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Location</label>
                    <input type="text" name="location" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                </div>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeScheduleModal()" class="flex-1 px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    Cancel
                </button>
                <button type="submit" name="create_schedule" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Add to Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openScheduleModal() {
    document.getElementById('scheduleModal').classList.remove('hidden');
    document.getElementById('scheduleModal').classList.add('flex');
}

function closeScheduleModal() {
    document.getElementById('scheduleModal').classList.add('hidden');
    document.getElementById('scheduleModal').classList.remove('flex');
}

function updateStatus(scheduleId, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="update_status" value="1">
        <input type="hidden" name="schedule_id" value="${scheduleId}">
        <input type="hidden" name="status" value="${status}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Update current time
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', { hour12: false });
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

setInterval(updateTime, 1000);

// Close modal when clicking outside
document.getElementById('scheduleModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeScheduleModal();
    }
});
</script>
</body>
</html>