<?php
require_once 'lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $db->prepare("SELECT firstname, lastname, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get today's date
$today = date('Y-m-d');

// Get today's tasks with time logs
$tasks_query = "
    SELECT t.*, p.name as project_name,
           COALESCE(SUM(CASE WHEN tl.end_time IS NOT NULL THEN TIMESTAMPDIFF(SECOND, tl.start_time, tl.end_time) / 3600 ELSE 0 END), 0) as hours_logged,
           COUNT(CASE WHEN tl.end_time IS NULL THEN 1 END) as active_sessions
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN time_logs tl ON t.id = tl.task_id AND DATE(tl.start_time) = ?
    WHERE t.assigned_to = ? AND (t.due_date = ? OR t.status = 'in_progress')
    GROUP BY t.id
    ORDER BY 
        CASE WHEN t.status = 'in_progress' THEN 1 ELSE 2 END,
        t.due_date ASC, t.priority DESC
";

$stmt = $db->prepare($tasks_query);
$stmt->bind_param("sis", $today, $user_id, $today);
$stmt->execute();
$tasks = $stmt->get_result();

// Get daily stats
$stats_query = "
    SELECT 
        COUNT(*) as total_tasks,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks,
        COALESCE(SUM(CASE WHEN tl.end_time IS NOT NULL THEN TIMESTAMPDIFF(SECOND, tl.start_time, tl.end_time) / 3600 ELSE 0 END), 0) as total_hours,
        COALESCE(SUM(CASE WHEN tl.end_time IS NOT NULL AND p.client_name IS NOT NULL THEN TIMESTAMPDIFF(SECOND, tl.start_time, tl.end_time) / 3600 ELSE 0 END), 0) as billable_hours
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN time_logs tl ON t.id = tl.task_id AND DATE(tl.start_time) = ?
    WHERE t.assigned_to = ? AND (t.due_date = ? OR t.status = 'in_progress')
";

$stmt = $db->prepare($stats_query);
$stmt->bind_param("sis", $today, $user_id, $today);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Check if user is currently clocked in
$active_session_query = "SELECT * FROM time_logs WHERE user_id = ? AND end_time IS NULL ORDER BY start_time DESC LIMIT 1";
$stmt = $db->prepare($active_session_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_session = $stmt->get_result()->fetch_assoc();

$progress = $stats['total_tasks'] > 0 ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) : 0;
$pending_tasks = $stats['total_tasks'] - $stats['completed_tasks'];

// Format hours
function formatHours($hours) {
    $h = floor($hours);
    $m = round(($hours - $h) * 60);
    return $h . 'h ' . $m . 'm';
}

// Get current session duration if active
$session_duration = '';
if ($active_session) {
    $start_time = new DateTime($active_session['start_time']);
    $now = new DateTime();
    $diff = $now->diff($start_time);
    $session_duration = sprintf('%02d:%02d:%02d', 
        $diff->h + ($diff->days * 24), 
        $diff->i, 
        $diff->s
    );
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Daily Schedule - TimeTrack Pro</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
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
                <div class="bg-center bg-no-repeat bg-cover rounded-full size-10 ring-2 ring-slate-100 dark:ring-slate-700" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBSBfpdqQBQgGld3Icgsto2cnz_krZW7C4cA3fku_S3QIKlg3UPP360tqJ1Z5pvCC5bNIB8ij9qFLfFZR-DsyrHtyaXMh6EFuvoOKYTeP_bfjdb9GnAak8Rq5AN1ATMFC062CwzQhylg8k1QfRx5pH9CMoLSnR_u9WjmyqdbD8CLiWzHMGGq8wn_qsJuGBzxRRNgD-0NwHiH5o4RccYyduyA5i4WGKTPsE4soDPa74x3T2K5rJa2Jq70WS7PouvLrUbKjcVaW3e5iY");'></div>
            </div>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <!-- Sidebar Navigation -->
        <nav class="hidden lg:flex w-64 flex-col justify-between border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-[#1a202c] p-4">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col gap-1 px-2">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Main Menu</p>
                    <a class="flex items-center gap-3 px-3 py-2.5 mt-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group" href="admin/dashboard.php">
                        <span class="material-symbols-outlined text-slate-400 group-hover:text-primary transition-colors">dashboard</span>
                        <span class="text-sm font-medium">Dashboard</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary dark:text-primary dark:bg-primary/20 transition-colors" href="schedule.php">
                        <span class="material-symbols-outlined filled">calendar_month</span>
                        <span class="text-sm font-medium">My Schedule</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group" href="admin/projects.php">
                        <span class="material-symbols-outlined text-slate-400 group-hover:text-primary transition-colors">folder</span>
                        <span class="text-sm font-medium">Projects</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group" href="admin/reports.php">
                        <span class="material-symbols-outlined text-slate-400 group-hover:text-primary transition-colors">bar_chart</span>
                        <span class="text-sm font-medium">Reports</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors group" href="admin/team.php">
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
                        <p class="text-slate-500 dark:text-slate-400 mt-1">Manage your tasks and track your time efficiently.</p>
                    </div>
                    <div class="flex gap-3">
                        <button class="hidden md:flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#1a202c] px-4 text-sm font-bold text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 transition-colors">
                            <span class="material-symbols-outlined !text-lg">download</span>
                            <span>Export</span>
                        </button>
                        <button onclick="addPersonalTask()" class="flex h-10 items-center justify-center gap-2 rounded-lg bg-primary px-5 text-sm font-bold text-white shadow-md shadow-primary/20 hover:bg-blue-700 transition-colors">
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
                            <p class="text-slate-500 text-sm mt-1">You have completed <?php echo $stats['completed_tasks']; ?> out of <?php echo $stats['total_tasks']; ?> tasks today.</p>
                            <div class="mt-4 h-2.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full w-[<?php echo $progress; ?>%] rounded-full bg-primary transition-all duration-500 ease-out" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-6 divide-x divide-slate-200 dark:divide-slate-700 w-full md:w-auto">
                            <div class="px-4 text-center first:pl-0">
                                <p class="text-xs font-medium uppercase text-slate-400 tracking-wider">Total Time</p>
                                <p class="text-xl font-bold text-slate-900 dark:text-white mt-1"><?php echo formatHours($stats['total_hours']); ?></p>
                            </div>
                            <div class="px-4 text-center">
                                <p class="text-xs font-medium uppercase text-slate-400 tracking-wider">Billable</p>
                                <p class="text-xl font-bold text-primary mt-1"><?php echo formatHours($stats['billable_hours']); ?></p>
                            </div>
                            <div class="px-4 text-center last:pr-0">
                                <p class="text-xs font-medium uppercase text-slate-400 tracking-wider">Pending</p>
                                <p class="text-xl font-bold text-orange-500 mt-1"><?php echo $pending_tasks; ?> Tasks</p>
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
                                    <?php if ($active_session): ?>
                                    <span class="inline-flex items-center rounded-full bg-green-500/20 px-2.5 py-1 text-xs font-medium text-green-300 ring-1 ring-inset ring-green-500/40">
                                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-green-400 animate-pulse"></span>
                                        Clocked In
                                    </span>
                                    <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-slate-500/20 px-2.5 py-1 text-xs font-medium text-slate-300 ring-1 ring-inset ring-slate-500/40">
                                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                        Clocked Out
                                    </span>
                                    <?php endif; ?>
                                    <span class="text-slate-300 text-sm font-medium"><?php echo date('M d, Y'); ?></span>
                                </div>
                                <div class="flex flex-col gap-1 mb-6">
                                    <p class="text-sm font-medium text-slate-300">Session Duration</p>
                                    <p class="text-5xl font-mono font-bold tracking-tight text-white" id="sessionTimer">
                                        <?php echo $active_session ? $session_duration : '00:00:00'; ?>
                                    </p>
                                    <?php if ($active_session): ?>
                                    <p class="text-sm text-slate-400 mt-1">Started at <?php echo date('h:i A', strtotime($active_session['start_time'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                <button onclick="toggleClock()" class="w-full rounded-lg bg-white py-3 text-sm font-bold text-[#101622] shadow hover:bg-slate-100 transition-colors">
                                    <?php echo $active_session ? 'Clock Out' : 'Clock In'; ?>
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
                                <!-- Sample upcoming events -->
                                <div class="flex gap-3 items-start p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer group">
                                    <div class="flex flex-col items-center justify-center h-12 w-12 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 shrink-0">
                                        <span class="text-xs font-bold">OCT</span>
                                        <span class="text-lg font-bold leading-none">25</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors">Sprint Review</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">10:00 AM • Zoom Meeting</p>
                                    </div>
                                </div>
                                <div class="flex gap-3 items-start p-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer group">
                                    <div class="flex flex-col items-center justify-center h-12 w-12 rounded-lg bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 shrink-0">
                                        <span class="text-xs font-bold">OCT</span>
                                        <span class="text-lg font-bold leading-none">25</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white group-hover:text-primary transition-colors">Design Workshop</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">02:00 PM • Conference Room A</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Task List (Span 8) -->
                    <div class="lg:col-span-8 flex flex-col gap-6">
                        <!-- Date Strip & Filter -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-between items-center bg-white dark:bg-[#1a202c] p-2 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                            <div class="flex items-center gap-1 overflow-x-auto max-w-full custom-scrollbar pb-1 sm:pb-0">
                                <?php
                                for ($i = -3; $i <= 3; $i++) {
                                    $date = date('Y-m-d', strtotime("$i days"));
                                    $day = date('D', strtotime($date));
                                    $dayNum = date('j', strtotime($date));
                                    $isToday = $date === $today;
                                ?>
                                <button class="flex flex-col items-center justify-center w-12 h-14 rounded-lg <?php echo $isToday ? 'bg-primary text-white shadow-md shadow-primary/30 transform scale-105' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500'; ?> <?php echo $i > 0 ? 'opacity-50' : ''; ?>">
                                    <span class="text-[10px] font-bold uppercase"><?php echo $isToday ? 'Today' : $day; ?></span>
                                    <span class="text-lg font-bold"><?php echo $dayNum; ?></span>
                                </button>
                                <?php } ?>
                            </div>
                            <div class="flex items-center gap-2 pr-2">
                                <select class="form-select text-sm border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:ring-primary focus:border-primary py-1.5 pl-3 pr-8">
                                    <option>All Tasks</option>
                                    <option>Projects</option>
                                    <option>Personal</option>
                                    <option>Completed</option>
                                </select>
                            </div>
                        </div>

                        <!-- Tasks List -->
                        <div class="flex flex-col gap-6">
                            <?php
                            $morning_tasks = [];
                            $afternoon_tasks = [];
                            
                            while ($task = $tasks->fetch_assoc()) {
                                $hour = $task['due_date'] ? date('H', strtotime($task['due_date'])) : 12;
                                if ($hour < 12) {
                                    $morning_tasks[] = $task;
                                } else {
                                    $afternoon_tasks[] = $task;
                                }
                            }
                            ?>

                            <!-- Morning Schedule Section -->
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3 px-1">Morning</h3>
                                <div class="flex flex-col gap-3">
                                    <?php foreach ($morning_tasks as $task): ?>
                                    <div class="group flex items-center justify-between p-4 <?php echo $task['status'] == 'completed' ? 'bg-slate-50 dark:bg-slate-800/50 opacity-75' : 'bg-white dark:bg-[#1a202c] border-l-4 border-l-primary'; ?> rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:shadow-md transition-all">
                                        <div class="flex items-center gap-4">
                                            <div class="relative flex items-center justify-center">
                                                <input <?php echo $task['status'] == 'completed' ? 'checked' : ''; ?> class="peer h-6 w-6 cursor-pointer appearance-none rounded-md border-2 border-slate-300 bg-white checked:border-primary checked:bg-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800 transition-all" type="checkbox" onchange="toggleTask(<?php echo $task['id']; ?>)"/>
                                                <span class="material-symbols-outlined pointer-events-none absolute text-white opacity-0 peer-checked:opacity-100 text-base">check</span>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold <?php echo $task['status'] == 'completed' ? 'text-slate-500 dark:text-slate-400 line-through' : 'text-slate-900 dark:text-white'; ?>"><?php echo htmlspecialchars($task['title']); ?></h4>
                                                <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                    <span class="inline-flex items-center rounded-md <?php echo $task['project_name'] ? 'bg-blue-50 dark:bg-blue-900/30 text-primary border border-blue-100 dark:border-blue-800' : 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-800'; ?> px-2 py-0.5 font-medium">
                                                        <?php echo $task['project_name'] ?: 'Personal'; ?>
                                                    </span>
                                                    <?php if ($task['priority'] == 'high'): ?>
                                                    <span class="flex items-center gap-1 text-orange-500 font-medium">
                                                        <span class="material-symbols-outlined !text-sm">flag</span> High Priority
                                                    </span>
                                                    <?php endif; ?>
                                                    <?php if ($task['due_date']): ?>
                                                    <span class="flex items-center gap-1">
                                                        <span class="material-symbols-outlined !text-sm">schedule</span> 
                                                        <?php echo date('h:i A', strtotime($task['due_date'])); ?>
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-sm font-bold <?php echo $task['status'] == 'completed' ? 'text-green-600 dark:text-green-400' : 'text-slate-600 dark:text-slate-400'; ?>">
                                            <?php echo formatHours($task['hours_logged'] ?: ($task['estimated_hours'] ?: 1)); ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Afternoon Schedule Section -->
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-3 px-1">Afternoon</h3>
                                <div class="flex flex-col gap-3">
                                    <?php foreach ($afternoon_tasks as $task): ?>
                                    <div class="group flex items-center justify-between p-4 bg-white dark:bg-[#1a202c] rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm hover:border-primary/50 transition-colors">
                                        <div class="flex items-center gap-4">
                                            <div class="relative flex items-center justify-center">
                                                <input <?php echo $task['status'] == 'completed' ? 'checked' : ''; ?> class="peer h-6 w-6 cursor-pointer appearance-none rounded-md border-2 border-slate-300 bg-white checked:border-primary checked:bg-primary focus:ring-2 focus:ring-primary/20 dark:border-slate-600 dark:bg-slate-800 transition-all" type="checkbox" onchange="toggleTask(<?php echo $task['id']; ?>)"/>
                                                <span class="material-symbols-outlined pointer-events-none absolute text-white opacity-0 peer-checked:opacity-100 text-base">check</span>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($task['title']); ?></h4>
                                                <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                    <span class="inline-flex items-center rounded-md <?php echo $task['project_name'] ? 'bg-blue-50 dark:bg-blue-900/30 text-primary border border-blue-100 dark:border-blue-800' : 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 border border-purple-100 dark:border-purple-800'; ?> px-2 py-0.5 font-medium">
                                                        <?php echo $task['project_name'] ?: 'Personal'; ?>
                                                    </span>
                                                    <?php if ($task['due_date']): ?>
                                                    <span class="flex items-center gap-1">
                                                        <span class="material-symbols-outlined !text-sm">schedule</span> 
                                                        <?php echo date('h:i A', strtotime($task['due_date'])); ?>
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-sm font-bold text-slate-600 dark:text-slate-400">
                                            <?php echo formatHours($task['estimated_hours'] ?: 1); ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>

                                    <!-- Add Task Button -->
                                    <button onclick="addPersonalTask()" class="flex items-center justify-center gap-2 p-3 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700 text-slate-400 hover:border-primary/50 hover:text-primary transition-all">
                                        <span class="material-symbols-outlined text-xl">add</span>
                                        <span class="text-sm font-bold">Add Task to Afternoon</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="h-10"></div>
            </div>
        </main>
    </div>
</div>

<script>
let sessionStartTime = <?php echo $active_session ? "'" . $active_session['start_time'] . "'" : 'null'; ?>;

function updateTimer() {
    if (!sessionStartTime) return;
    
    const start = new Date(sessionStartTime);
    const now = new Date();
    const diff = now - start;
    
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);
    
    document.getElementById('sessionTimer').textContent = 
        String(hours).padStart(2, '0') + ':' + 
        String(minutes).padStart(2, '0') + ':' + 
        String(seconds).padStart(2, '0');
}

if (sessionStartTime) {
    setInterval(updateTimer, 1000);
}

function toggleClock() {
    window.location.href = 'toggle_clock.php';
}

function toggleTask(taskId) {
    fetch('toggle_task.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'task_id=' + taskId
    }).then(() => location.reload());
}

function addPersonalTask() {
    const title = prompt('Enter task title:');
    if (title) {
        fetch('add_personal_task.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'title=' + encodeURIComponent(title)
        }).then(() => location.reload());
    }
}
</script>

</body>
</html>