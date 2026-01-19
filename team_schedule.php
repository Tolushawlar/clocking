<?php
require_once 'lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'];

// Check if user has supervisor permissions
$stmt = $db->prepare("SELECT role, can_manage_team FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!in_array($user['role'], ['admin', 'supervisor']) && !$user['can_manage_team']) {
    header('Location: index.php');
    exit;
}

$today = date('Y-m-d');

// Get team members with their daily stats
$team_query = "
    SELECT u.id, u.firstname, u.lastname, u.role, u.email,
           COALESCE(SUM(CASE WHEN tl.end_time IS NOT NULL THEN TIMESTAMPDIFF(SECOND, tl.start_time, tl.end_time) / 3600 ELSE 0 END), 0) as hours_logged,
           COUNT(CASE WHEN tl.end_time IS NULL THEN 1 END) as is_active,
           COUNT(DISTINCT t.id) as total_tasks,
           COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks
    FROM users u
    LEFT JOIN time_logs tl ON u.id = tl.user_id AND DATE(tl.start_time) = ?
    LEFT JOIN tasks t ON u.id = t.assigned_to AND (t.due_date = ? OR t.status = 'in_progress')
    WHERE u.business_id = ? AND u.role != 'admin'
    GROUP BY u.id
    ORDER BY u.firstname ASC
";

$stmt = $db->prepare($team_query);
$stmt->bind_param("ssi", $today, $today, $business_id);
$stmt->execute();
$team_members = $stmt->get_result();

// Get today's tasks for all team members
$tasks_query = "
    SELECT t.*, u.firstname, u.lastname, p.name as project_name,
           COALESCE(SUM(CASE WHEN tl.end_time IS NOT NULL THEN TIMESTAMPDIFF(SECOND, tl.start_time, tl.end_time) / 3600 ELSE 0 END), 0) as hours_logged
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN time_logs tl ON t.id = tl.task_id AND DATE(tl.start_time) = ?
    WHERE u.business_id = ? AND (t.due_date = ? OR t.status = 'in_progress')
    GROUP BY t.id
    ORDER BY u.id, t.due_date ASC
";

$stmt = $db->prepare($tasks_query);
$stmt->bind_param("sis", $today, $business_id, $today);
$stmt->execute();
$all_tasks = $stmt->get_result();

// Organize tasks by user
$user_tasks = [];
while ($task = $all_tasks->fetch_assoc()) {
    $user_tasks[$task['assigned_to']][] = $task;
}

// Calculate team stats
$total_hours = 0;
$active_tasks = 0;
$overtime_count = 0;
$team_count = 0;

$team_members->data_seek(0);
while ($member = $team_members->fetch_assoc()) {
    $total_hours += $member['hours_logged'];
    $active_tasks += ($member['total_tasks'] - $member['completed_tasks']);
    if ($member['hours_logged'] > 8) $overtime_count++;
    $team_count++;
}

function getStatusColor($status) {
    switch($status) {
        case 'completed': return 'bg-green-500';
        case 'in_progress': return 'bg-primary';
        case 'on_hold': return 'bg-yellow-400';
        default: return 'bg-gray-300 dark:bg-gray-600';
    }
}

function getOnlineStatus($is_active, $hours_logged) {
    if ($is_active > 0) return 'bg-green-500'; // Currently active
    if ($hours_logged > 0) return 'bg-yellow-400'; // Worked today but not active
    return 'bg-gray-400'; // Offline
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Team Daily Schedule - TimeTrack Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
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
                    borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                },
            },
        }
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-900 dark:text-gray-100 antialiased overflow-hidden">
<div class="flex h-screen w-full">
    <!-- Sidebar -->
    <aside class="hidden w-64 flex-col border-r border-gray-200 bg-white dark:bg-[#151b2b] dark:border-gray-800 lg:flex shrink-0">
        <div class="flex h-full flex-col justify-between p-4">
            <div class="flex flex-col gap-6">
                <div class="flex items-center gap-3 px-2">
                    <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary">schedule</span>
                    </div>
                    <div class="flex flex-col">
                        <h1 class="text-base font-bold text-gray-900 dark:text-white leading-none">TimeTrack Pro</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Supervisor View</p>
                    </div>
                </div>
                <nav class="flex flex-col gap-1">
                    <a class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors" href="admin/dashboard.php">
                        <span class="material-symbols-outlined">dashboard</span>
                        <span class="text-sm font-medium">Dashboard</span>
                    </a>
                    <a class="flex items-center gap-3 rounded-lg bg-primary/10 px-3 py-2.5 text-primary dark:bg-primary/20 dark:text-blue-300 transition-colors" href="team_schedule.php">
                        <span class="material-symbols-outlined fill-1">groups</span>
                        <span class="text-sm font-medium">Team Schedule</span>
                    </a>
                    <a class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors" href="admin/reports.php">
                        <span class="material-symbols-outlined">description</span>
                        <span class="text-sm font-medium">Reports</span>
                    </a>
                    <a class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition-colors" href="admin/settings.php">
                        <span class="material-symbols-outlined">settings</span>
                        <span class="text-sm font-medium">Settings</span>
                    </a>
                </nav>
            </div>
            <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800">
                <div class="size-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-sm font-bold">
                    <?php echo strtoupper(substr($_SESSION['firstname'], 0, 1) . substr($_SESSION['lastname'], 0, 1)); ?>
                </div>
                <div class="flex flex-col overflow-hidden">
                    <p class="truncate text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?></p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400"><?php echo ucfirst($user['role']); ?></p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex flex-1 flex-col h-screen overflow-hidden bg-background-light dark:bg-background-dark">
        <!-- Header Sticky -->
        <header class="flex-shrink-0 border-b border-gray-200 bg-white/80 backdrop-blur-md px-6 py-4 dark:border-gray-800 dark:bg-[#151b2b]/90 z-10">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Team Daily Schedule</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Manage tasks and track daily progress for the team</p>
                </div>
                <div class="flex items-center gap-3">
                    <button class="flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">calendar_today</span>
                        <?php echo date('M d, Y'); ?>
                    </button>
                    <button onclick="addNewTask()" class="flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white shadow-lg shadow-primary/30 hover:bg-blue-600 transition-all">
                        <span class="material-symbols-outlined text-[20px]">add</span>
                        <span>Add New Task</span>
                    </button>
                </div>
            </div>
            <!-- Filters & Stats Row -->
            <div class="mt-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <!-- Search & Chips -->
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative w-full max-w-sm">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="material-symbols-outlined text-gray-400">search</span>
                        </div>
                        <input id="searchInput" class="block w-full rounded-lg border-0 bg-gray-100 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6" placeholder="Search team members..." type="text"/>
                    </div>
                    <div class="flex gap-2 overflow-x-auto pb-2 sm:pb-0">
                        <button class="flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <span class="material-symbols-outlined text-[16px]">group</span>
                            All Team
                            <span class="material-symbols-outlined text-[16px]">expand_more</span>
                        </button>
                        <button class="flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            <span class="material-symbols-outlined text-[16px]">filter_list</span>
                            Status: All
                            <span class="material-symbols-outlined text-[16px]">expand_more</span>
                        </button>
                    </div>
                </div>
                <!-- Mini Stats -->
                <div class="hidden gap-6 lg:flex">
                    <div class="flex flex-col">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Hours</span>
                        <div class="flex items-baseline gap-2">
                            <span class="text-lg font-bold text-gray-900 dark:text-white"><?php echo number_format($total_hours, 1); ?>h</span>
                            <span class="text-xs font-medium text-green-600">+5%</span>
                        </div>
                    </div>
                    <div class="h-8 w-px bg-gray-200 dark:bg-gray-700"></div>
                    <div class="flex flex-col">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Active Tasks</span>
                        <div class="flex items-baseline gap-2">
                            <span class="text-lg font-bold text-gray-900 dark:text-white"><?php echo $active_tasks; ?></span>
                        </div>
                    </div>
                    <div class="h-8 w-px bg-gray-200 dark:bg-gray-700"></div>
                    <div class="flex flex-col">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Overtime</span>
                        <div class="flex items-baseline gap-2">
                            <span class="text-lg font-bold text-gray-900 dark:text-white"><?php echo $overtime_count; ?></span>
                            <span class="text-xs font-medium text-gray-400">Alert</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Scrollable Content Area -->
        <div class="flex-1 overflow-y-auto p-6">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3" id="teamGrid">
                <?php 
                $team_members->data_seek(0);
                while ($member = $team_members->fetch_assoc()): 
                    $member_tasks = $user_tasks[$member['id']] ?? [];
                    $progress = $member['total_tasks'] > 0 ? ($member['hours_logged'] / 8) * 100 : 0;
                    $online_status = getOnlineStatus($member['is_active'], $member['hours_logged']);
                ?>
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-all hover:shadow-md dark:border-gray-700 dark:bg-[#1e293b] member-card" data-name="<?php echo strtolower($member['firstname'] . ' ' . $member['lastname']); ?>">
                    <!-- Card Header -->
                    <div class="mb-4 flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <div class="size-12 rounded-full bg-primary/10 flex items-center justify-center text-primary text-lg font-bold">
                                    <?php echo strtoupper(substr($member['firstname'], 0, 1) . substr($member['lastname'], 0, 1)); ?>
                                </div>
                                <span class="absolute bottom-0 right-0 block size-3 rounded-full <?php echo $online_status; ?> ring-2 ring-white dark:ring-[#1e293b]"></span>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($member['firstname'] . ' ' . $member['lastname']); ?></h3>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400"><?php echo ucfirst($member['role']); ?></p>
                            </div>
                        </div>
                        <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <span class="material-symbols-outlined">more_horiz</span>
                        </button>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-6">
                        <div class="mb-1 flex justify-between text-xs font-medium">
                            <span class="text-gray-600 dark:text-gray-300">Work Progress</span>
                            <span class="text-primary"><?php echo number_format($member['hours_logged'], 1); ?> / 8 hrs</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            <div class="h-full rounded-full bg-primary transition-all duration-500" style="width: <?php echo min(100, $progress); ?>%"></div>
                        </div>
                    </div>

                    <!-- Tasks Timeline -->
                    <div class="flex flex-1 flex-col gap-3">
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Today's Plan</h4>
                        
                        <?php if (empty($member_tasks)): ?>
                        <!-- No tasks assigned -->
                        <div class="flex flex-1 flex-col justify-center items-center py-6 gap-3 text-center">
                            <div class="size-12 rounded-full bg-gray-50 flex items-center justify-center dark:bg-gray-800">
                                <span class="material-symbols-outlined text-gray-400">event_busy</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    <?php echo $member['hours_logged'] > 0 ? 'No Tasks Today' : 'Offline'; ?>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <?php echo $member['hours_logged'] > 0 ? 'No tasks assigned for today.' : htmlspecialchars($member['firstname']) . " hasn't clocked in yet."; ?>
                                </p>
                            </div>
                            <button onclick="assignTask(<?php echo $member['id']; ?>)" class="mt-2 inline-flex items-center gap-1 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-600 dark:hover:bg-gray-700">
                                <span class="material-symbols-outlined text-[14px]">add_task</span>
                                Assign Task
                            </button>
                        </div>
                        <?php else: ?>
                        <!-- Task items -->
                        <?php foreach (array_slice($member_tasks, 0, 3) as $index => $task): ?>
                        <div class="group relative flex gap-3">
                            <div class="flex flex-col items-center">
                                <div class="size-2 rounded-full <?php echo getStatusColor($task['status']); ?> mt-2 <?php echo $task['status'] == 'in_progress' ? 'shadow-[0_0_0_4px_rgba(19,91,236,0.2)]' : ''; ?>"></div>
                                <?php if ($index < count($member_tasks) - 1): ?>
                                <div class="w-px flex-1 bg-gray-200 dark:bg-gray-700 my-1"></div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 rounded-lg <?php echo $task['status'] == 'completed' ? 'border border-gray-100 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50' : ($task['status'] == 'in_progress' ? 'border-l-4 border-primary bg-white shadow-sm dark:bg-gray-800' : 'border border-dashed border-gray-300 bg-white dark:border-gray-600 dark:bg-transparent'); ?> p-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium <?php echo $task['status'] == 'in_progress' ? 'text-primary font-bold' : 'text-gray-500'; ?>">
                                        <?php echo $task['due_date'] ? date('H:i', strtotime($task['due_date'])) : 'All Day'; ?>
                                    </span>
                                    <?php
                                    $status_class = '';
                                    $status_text = ucfirst(str_replace('_', ' ', $task['status']));
                                    switch($task['status']) {
                                        case 'completed':
                                            $status_class = 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400';
                                            break;
                                        case 'in_progress':
                                            $status_class = 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-400';
                                            break;
                                        default:
                                            $status_class = 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400';
                                    }
                                    ?>
                                    <span class="inline-flex items-center rounded-md <?php echo $status_class; ?> px-2 py-1 text-xs font-medium ring-1 ring-inset"><?php echo $status_text; ?></span>
                                </div>
                                <p class="mt-1 text-sm font-semibold <?php echo $task['status'] == 'completed' ? 'text-gray-700 line-through decoration-gray-400 dark:text-gray-400' : 'text-gray-900 dark:text-white'; ?>">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </p>
                                <?php if ($task['project_name']): ?>
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        <span class="material-symbols-outlined text-[12px]">folder</span> <?php echo htmlspecialchars($task['project_name']); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($member_tasks) > 3): ?>
                        <div class="text-center">
                            <button class="text-xs text-primary hover:underline">View <?php echo count($member_tasks) - 3; ?> more tasks</button>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Footer Actions -->
                    <div class="mt-5 border-t border-gray-100 pt-3 dark:border-gray-700 flex justify-between">
                        <button onclick="messageUser(<?php echo $member['id']; ?>)" class="text-xs font-medium text-gray-500 hover:text-primary dark:text-gray-400 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">chat</span> Message
                        </button>
                        <button onclick="viewLogs(<?php echo $member['id']; ?>)" class="text-xs font-medium text-gray-500 hover:text-primary dark:text-gray-400 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">history</span> View Logs
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const cards = document.querySelectorAll('.member-card');
    
    cards.forEach(card => {
        const name = card.dataset.name;
        if (name.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
});

function addNewTask() {
    window.location.href = 'admin/add_task.php';
}

function assignTask(userId) {
    window.location.href = `admin/add_task.php?assign_to=${userId}`;
}

function messageUser(userId) {
    // Implement messaging functionality
    alert('Messaging feature coming soon!');
}

function viewLogs(userId) {
    window.location.href = `admin/user_logs.php?user_id=${userId}`;
}
</script>

</body>
</html>