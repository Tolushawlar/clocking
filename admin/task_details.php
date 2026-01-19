<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'];
$project_id = $_GET['project_id'] ?? 0;

// Get user permissions
$stmt = $db->prepare("SELECT role, can_create_projects, can_manage_team FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get project details
$stmt = $db->prepare("SELECT * FROM projects WHERE id = ? AND business_id = ?");
$stmt->bind_param("ii", $project_id, $business_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    header('Location: projects.php');
    exit;
}

// Get task statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_tasks,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tasks,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks
    FROM tasks 
    WHERE project_id = ?
";

$stmt = $db->prepare($stats_query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$task_stats = $stmt->get_result()->fetch_assoc();

// Get tasks with assignee info
$tasks_query = "
    SELECT t.*, u.firstname, u.lastname
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.project_id = ?
    ORDER BY 
        CASE 
            WHEN t.status = 'in_progress' THEN 1
            WHEN t.status = 'pending' THEN 2
            WHEN t.status = 'completed' THEN 3
            ELSE 4
        END,
        t.due_date ASC
";

$stmt = $db->prepare($tasks_query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$tasks = $stmt->get_result();

$progress = $task_stats['total_tasks'] > 0 ? round(($task_stats['completed_tasks'] / $task_stats['total_tasks']) * 100) : 0;
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Phase Detail & Tasks - TimeTrack Pro</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
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
              "surface-light": "#ffffff",
              "surface-dark": "#1a202c",
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
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-white font-display overflow-x-hidden">
<div class="relative flex h-screen w-full flex-row overflow-hidden">
    <!-- Side Navigation -->
    <aside class="flex w-64 flex-col border-r border-slate-200 dark:border-slate-800 bg-surface-light dark:bg-surface-dark flex-shrink-0 transition-all duration-300">
        <div class="flex flex-col h-full justify-between p-4">
            <div class="flex flex-col gap-6">
                <!-- Logo -->
                <div class="flex flex-col px-2">
                    <h1 class="text-slate-900 dark:text-white text-xl font-bold leading-normal">TimeTrack Pro</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-xs font-normal leading-normal">Business Suite</p>
                </div>
                <!-- Nav Items -->
                <nav class="flex flex-col gap-2">
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group" href="dashboard.php">
                        <span class="material-symbols-outlined text-slate-500 dark:text-slate-400 group-hover:text-primary transition-colors">dashboard</span>
                        <p class="text-slate-700 dark:text-slate-300 text-sm font-medium leading-normal group-hover:text-slate-900 dark:group-hover:text-white">Dashboard</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 dark:bg-primary/20 transition-colors" href="projects.php">
                        <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">business_center</span>
                        <p class="text-primary text-sm font-bold leading-normal">Projects</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group" href="../index.php">
                        <span class="material-symbols-outlined text-slate-500 dark:text-slate-400 group-hover:text-primary transition-colors">check_box</span>
                        <p class="text-slate-700 dark:text-slate-300 text-sm font-medium leading-normal group-hover:text-slate-900 dark:group-hover:text-white">Tasks</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group" href="reports.php">
                        <span class="material-symbols-outlined text-slate-500 dark:text-slate-400 group-hover:text-primary transition-colors">bar_chart</span>
                        <p class="text-slate-700 dark:text-slate-300 text-sm font-medium leading-normal group-hover:text-slate-900 dark:group-hover:text-white">Reports</p>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group" href="team.php">
                        <span class="material-symbols-outlined text-slate-500 dark:text-slate-400 group-hover:text-primary transition-colors">group</span>
                        <p class="text-slate-700 dark:text-slate-300 text-sm font-medium leading-normal group-hover:text-slate-900 dark:group-hover:text-white">Team</p>
                    </a>
                </nav>
            </div>
            <!-- User Profile -->
            <div class="flex items-center gap-3 px-3 py-2 mt-auto border-t border-slate-200 dark:border-slate-800 pt-4">
                <div class="size-8 rounded-full bg-slate-200 dark:bg-slate-700 bg-center bg-cover" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBSBfpdqQBQgGld3Icgsto2cnz_krZW7C4cA3fku_S3QIKlg3UPP360tqJ1Z5pvCC5bNIB8ij9qFLfFZR-DsyrHtyaXMh6EFuvoOKYTeP_bfjdb9GnAak8Rq5AN1ATMFC062CwzQhylg8k1QfRx5pH9CMoLSnR_u9WjmyqdbD8CLiWzHMGGq8wn_qsJuGBzxRRNgD-0NwHiH5o4RccYyduyA5i4WGKTPsE4soDPa74x3T2K5rJa2Jq70WS7PouvLrUbKjcVaW3e5iY");'></div>
                <div class="flex flex-col">
                    <p class="text-xs font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?></p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400"><?php echo ucfirst($user['role']); ?></p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <!-- Top Navigation -->
        <header class="flex items-center justify-between whitespace-nowrap border-b border-slate-200 dark:border-slate-800 bg-surface-light dark:bg-surface-dark px-6 py-3 flex-shrink-0 z-10">
            <div class="flex items-center gap-4 text-slate-900 dark:text-white">
                <button class="text-slate-500 hover:text-primary md:hidden">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <div class="hidden md:flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                    <a class="hover:text-primary transition-colors" href="projects.php">Projects</a>
                    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                    <a class="hover:text-primary transition-colors" href="project_details.php?id=<?php echo $project_id; ?>"><?php echo htmlspecialchars($project['name']); ?></a>
                    <span class="material-symbols-outlined text-[16px]">chevron_right</span>
                    <span class="font-medium text-slate-900 dark:text-white">Tasks</span>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="hidden sm:flex relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">search</span>
                    <input class="h-10 pl-10 pr-4 rounded-lg bg-slate-100 dark:bg-slate-800 border-none text-sm focus:ring-2 focus:ring-primary w-64 placeholder:text-slate-400 dark:text-white transition-all" placeholder="Search tasks..." type="text" id="searchInput"/>
                </div>
                <button class="size-10 flex items-center justify-center rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-colors relative">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute top-2.5 right-2.5 size-2 bg-red-500 rounded-full border border-white dark:border-slate-900"></span>
                </button>
                <button class="size-10 flex items-center justify-center rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 transition-colors">
                    <span class="material-symbols-outlined">settings</span>
                </button>
            </div>
        </header>

        <!-- Main Scrollable Content -->
        <main class="flex-1 overflow-y-auto p-6 md:p-8 scroll-smooth">
            <div class="max-w-[1200px] mx-auto flex flex-col gap-8">
                <!-- Page Heading & Progress -->
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col lg:flex-row justify-between lg:items-end gap-4">
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-3">
                                <?php
                                $status_class = '';
                                switch($project['status']) {
                                    case 'active':
                                        $status_class = 'bg-primary/10 text-primary';
                                        break;
                                    case 'completed':
                                        $status_class = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
                                        break;
                                    default:
                                        $status_class = 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
                                }
                                ?>
                                <span class="<?php echo $status_class; ?> px-2.5 py-0.5 rounded text-xs font-bold uppercase tracking-wider"><?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?></span>
                                <?php if ($project['end_date']): ?>
                                <span class="text-slate-500 dark:text-slate-400 text-sm font-medium flex items-center gap-1">
                                    <span class="material-symbols-outlined text-[18px]">calendar_today</span> 
                                    Due <?php echo date('M d, Y', strtotime($project['end_date'])); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <h1 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white tracking-tight"><?php echo htmlspecialchars($project['name']); ?></h1>
                            <p class="text-slate-500 dark:text-slate-400 max-w-2xl text-base"><?php echo $project['description'] ? htmlspecialchars($project['description']) : 'No description available.'; ?></p>
                        </div>
                        <div class="flex gap-3">
                            <?php if ($user['can_create_projects'] || in_array($user['role'], ['admin', 'supervisor', 'team_leader'])): ?>
                            <button onclick="editProject()" class="h-10 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-white text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors flex items-center gap-2 shadow-sm">
                                <span class="material-symbols-outlined text-[20px]">edit</span> Edit Project
                            </button>
                            <button onclick="addTask()" class="h-10 px-4 rounded-lg bg-primary text-white text-sm font-semibold hover:bg-blue-700 transition-colors flex items-center gap-2 shadow-sm shadow-primary/30">
                                <span class="material-symbols-outlined text-[20px]">add</span> Add New Task
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Progress Bar Component -->
                    <div class="bg-surface-light dark:bg-surface-dark p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col gap-3">
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-slate-900 dark:text-white font-semibold mb-1">Project Progress</p>
                                <p class="text-slate-500 dark:text-slate-400 text-sm">
                                    <?php 
                                    $remaining = $task_stats['total_tasks'] - $task_stats['completed_tasks'];
                                    echo $remaining > 0 ? "You're making good time. {$remaining} tasks remaining." : "All tasks completed!";
                                    ?>
                                </p>
                            </div>
                            <span class="text-primary font-bold text-xl"><?php echo $progress; ?>%</span>
                        </div>
                        <div class="w-full h-3 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-primary rounded-full" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-surface-light dark:bg-surface-dark p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                        <div class="flex flex-col">
                            <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">Total Tasks</p>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?php echo $task_stats['total_tasks']; ?></p>
                        </div>
                        <div class="size-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400">
                            <span class="material-symbols-outlined">list</span>
                        </div>
                    </div>
                    <div class="bg-surface-light dark:bg-surface-dark p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                        <div class="flex flex-col">
                            <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">In Progress</p>
                            <p class="text-2xl font-bold text-blue-600 mt-1"><?php echo $task_stats['in_progress_tasks']; ?></p>
                        </div>
                        <div class="size-10 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600 dark:text-blue-400">
                            <span class="material-symbols-outlined">pending</span>
                        </div>
                    </div>
                    <div class="bg-surface-light dark:bg-surface-dark p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                        <div class="flex flex-col">
                            <p class="text-slate-500 dark:text-slate-400 text-xs font-semibold uppercase tracking-wider">Completed</p>
                            <p class="text-2xl font-bold text-emerald-600 mt-1"><?php echo $task_stats['completed_tasks']; ?></p>
                        </div>
                        <div class="size-10 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                            <span class="material-symbols-outlined">check_circle</span>
                        </div>
                    </div>
                </div>

                <!-- Filters & Toolbar -->
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 bg-surface-light dark:bg-surface-dark p-2 rounded-xl border border-slate-200 dark:border-slate-800">
                    <div class="flex gap-2 w-full sm:w-auto overflow-x-auto pb-2 sm:pb-0 px-2 sm:px-0">
                        <button onclick="filterTasks('all')" class="filter-btn px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-medium hover:bg-slate-200 dark:hover:bg-slate-700 whitespace-nowrap transition-colors">All Tasks</button>
                        <button onclick="filterTasks('my')" class="filter-btn px-4 py-2 rounded-lg text-slate-500 dark:text-slate-400 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white whitespace-nowrap transition-colors">My Tasks</button>
                        <button onclick="filterTasks('due')" class="filter-btn px-4 py-2 rounded-lg text-slate-500 dark:text-slate-400 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white whitespace-nowrap transition-colors">Due Soon</button>
                    </div>
                </div>

                <!-- Tasks Table -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-800 text-xs uppercase text-slate-500 dark:text-slate-400 font-semibold tracking-wider">
                                    <th class="px-6 py-4 w-12">
                                        <input class="rounded border-slate-300 dark:border-slate-600 text-primary focus:ring-primary bg-transparent" type="checkbox" id="selectAll"/>
                                    </th>
                                    <th class="px-6 py-4">Task Name</th>
                                    <th class="px-6 py-4">Assignee</th>
                                    <th class="px-6 py-4">Due Date</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="tasksTableBody">
                                <?php while ($task = $tasks->fetch_assoc()): 
                                    $is_overdue = $task['due_date'] && strtotime($task['due_date']) < time() && $task['status'] != 'completed';
                                    $is_due_soon = $task['due_date'] && strtotime($task['due_date']) <= strtotime('+3 days') && $task['status'] != 'completed';
                                ?>
                                <tr class="group hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors task-row" 
                                    data-status="<?php echo $task['status']; ?>" 
                                    data-assignee="<?php echo $task['assigned_to']; ?>" 
                                    data-due-soon="<?php echo $is_due_soon ? '1' : '0'; ?>"
                                    data-name="<?php echo strtolower($task['title']); ?>">
                                    <td class="px-6 py-4">
                                        <input class="task-checkbox rounded border-slate-300 dark:border-slate-600 text-primary focus:ring-primary bg-transparent opacity-0 group-hover:opacity-100 transition-opacity" type="checkbox"/>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($task['title']); ?></span>
                                            <span class="text-xs text-slate-500 dark:text-slate-400">#TSK-<?php echo str_pad($task['id'], 3, '0', STR_PAD_LEFT); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($task['assigned_to']): ?>
                                        <div class="flex items-center gap-2">
                                            <div class="size-6 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold">
                                                <?php echo strtoupper(substr($task['firstname'], 0, 1) . substr($task['lastname'], 0, 1)); ?>
                                            </div>
                                            <span class="text-sm text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($task['firstname'] . ' ' . substr($task['lastname'], 0, 1) . '.'); ?></span>
                                        </div>
                                        <?php else: ?>
                                        <div class="flex items-center gap-2">
                                            <div class="size-6 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-[10px] font-bold text-slate-500 dark:text-slate-400 border border-dashed border-slate-300 dark:border-slate-600">?</div>
                                            <span class="text-sm text-slate-400 italic">Unassigned</span>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($task['due_date']): ?>
                                            <?php if ($is_overdue): ?>
                                                <span class="text-sm text-red-600 dark:text-red-400 font-medium"><?php echo date('M d', strtotime($task['due_date'])); ?> (Overdue)</span>
                                            <?php elseif ($is_due_soon): ?>
                                                <span class="text-sm text-orange-600 dark:text-orange-400 font-medium"><?php echo date('M d', strtotime($task['due_date'])); ?></span>
                                            <?php else: ?>
                                                <span class="text-sm text-slate-500 dark:text-slate-400"><?php echo date('M d, Y', strtotime($task['due_date'])); ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-sm text-slate-400">No due date</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $status_class = '';
                                        $status_text = ucfirst(str_replace('_', ' ', $task['status']));
                                        switch($task['status']) {
                                            case 'completed':
                                                $status_class = 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border-emerald-100 dark:border-emerald-800';
                                                break;
                                            case 'in_progress':
                                                $status_class = 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-100 dark:border-blue-800';
                                                break;
                                            default:
                                                $status_class = 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700';
                                        }
                                        ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold <?php echo $status_class; ?> border">
                                            <span class="size-1.5 rounded-full <?php echo $task['status'] == 'completed' ? 'bg-emerald-500' : ($task['status'] == 'in_progress' ? 'bg-blue-500' : 'bg-slate-400'); ?>"></span> 
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick="toggleTaskMenu(<?php echo $task['id']; ?>)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">more_vert</span>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- Table Footer / Pagination -->
                    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-surface-dark">
                        <p class="text-sm text-slate-500 dark:text-slate-400">Showing <?php echo $task_stats['total_tasks']; ?> tasks</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let currentFilter = 'all';
const userId = <?php echo $user_id; ?>;

function filterTasks(filter) {
    currentFilter = filter;
    const rows = document.querySelectorAll('.task-row');
    const buttons = document.querySelectorAll('.filter-btn');
    
    // Update button styles
    buttons.forEach(btn => {
        btn.className = 'filter-btn px-4 py-2 rounded-lg text-slate-500 dark:text-slate-400 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white whitespace-nowrap transition-colors';
    });
    event.target.className = 'filter-btn px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-medium hover:bg-slate-200 dark:hover:bg-slate-700 whitespace-nowrap transition-colors';
    
    rows.forEach(row => {
        let show = true;
        
        switch(filter) {
            case 'my':
                show = row.dataset.assignee == userId;
                break;
            case 'due':
                show = row.dataset.dueSoon == '1';
                break;
            case 'all':
            default:
                show = true;
        }
        
        row.style.display = show ? '' : 'none';
    });
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.task-row');
    
    rows.forEach(row => {
        const taskName = row.dataset.name;
        const matchesSearch = taskName.includes(searchTerm);
        const matchesFilter = shouldShowForCurrentFilter(row);
        
        row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
    });
});

function shouldShowForCurrentFilter(row) {
    switch(currentFilter) {
        case 'my':
            return row.dataset.assignee == userId;
        case 'due':
            return row.dataset.dueSoon == '1';
        default:
            return true;
    }
}

// Select all functionality
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.task-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

function editProject() {
    window.location.href = `edit_project.php?id=<?php echo $project_id; ?>`;
}

function addTask() {
    window.location.href = `add_task.php?project_id=<?php echo $project_id; ?>`;
}

function toggleTaskMenu(taskId) {
    // Simple implementation - could be enhanced with dropdown menus
    const actions = ['Edit Task', 'Mark Complete', 'Delete Task'];
    const action = prompt('Choose action:\n1. Edit Task\n2. Mark Complete\n3. Delete Task\n\nEnter number:');
    
    switch(action) {
        case '1':
            window.location.href = `edit_task.php?id=${taskId}`;
            break;
        case '2':
            window.location.href = `update_task_status.php?id=${taskId}&status=completed`;
            break;
        case '3':
            if (confirm('Are you sure you want to delete this task?')) {
                window.location.href = `delete_task.php?id=${taskId}`;
            }
            break;
    }
}
</script>

</body>
</html>