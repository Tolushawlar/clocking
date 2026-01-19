<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'] ?? 1;
$project_id = $_GET['id'] ?? 0;

// Get user permissions
$stmt = $db->prepare("SELECT category as role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result()->fetch_assoc();
$user = ['role' => $user_result['role'] ?? 'admin', 'can_create_projects' => 1, 'can_manage_team' => 1];

// Get project details
$stmt = $db->prepare("
    SELECT p.*, u.firstname, u.lastname,
           COUNT(DISTINCT t.id) as total_tasks,
           COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks
    FROM projects p 
    LEFT JOIN users u ON p.created_by = u.id
    LEFT JOIN tasks t ON p.id = t.project_id
    WHERE p.id = ? AND p.business_id = ?
    GROUP BY p.id
");
$stmt->bind_param("ii", $project_id, $business_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    header('Location: projects.php');
    exit;
}

// Get project phases (using tasks as phases for now)
$phases_stmt = $db->prepare("
    SELECT t.*, u.firstname, u.lastname
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.project_id = ?
    ORDER BY t.created_at ASC
");
$phases_stmt->bind_param("i", $project_id);
$phases_stmt->execute();
$phases = $phases_stmt->get_result();

// Get project phases from project_phases table
$project_phases_stmt = $db->prepare("
    SELECT pp.*, 
           COUNT(DISTINCT t.id) as task_count,
           COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks
    FROM project_phases pp
    LEFT JOIN tasks t ON pp.id = t.phase_id
    WHERE pp.project_id = ?
    GROUP BY pp.id
    ORDER BY pp.id ASC
");
$project_phases_stmt->bind_param("i", $project_id);
$project_phases_stmt->execute();
$project_phases = $project_phases_stmt->get_result();

$progress = $project['total_tasks'] > 0 ? round(($project['completed_tasks'] / $project['total_tasks']) * 100) : 0;
$days_remaining = $project['end_date'] ? max(0, ceil((strtotime($project['end_date']) - time()) / (60 * 60 * 24))) : 0;
$project['hours_logged'] = 0; // Default value since time_logs table doesn't exist
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>TimeTrack Pro - Project Details</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#135bec",
                        "primary-hover": "#1d4ed8",
                        "background-light": "#f6f6f8",
                        "background-dark": "#101622",
                        "surface-light": "#ffffff",
                        "surface-dark": "#1e293b",
                        "text-main": "#0d121b",
                        "text-secondary": "#4c669a",
                        "border-light": "#e7ebf3",
                        "border-dark": "#334155",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "2xl": "1rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .material-symbols-outlined.filled {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
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
                        <p class="text-text-secondary text-xs font-medium">Enterprise Edition</p>
                    </div>
                </div>
                <nav class="flex flex-col gap-2">
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-main dark:text-gray-300 hover:bg-background-light dark:hover:bg-slate-700 transition-colors group" href="dashboard.php">
                        <span class="material-symbols-outlined text-text-secondary group-hover:text-primary transition-colors">dashboard</span>
                        <span class="text-sm font-medium">Dashboard</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-main dark:text-gray-300 hover:bg-background-light dark:hover:bg-slate-700 transition-colors group" href="teams.php">
                        <span class="material-symbols-outlined text-text-secondary group-hover:text-primary transition-colors">group</span>
                        <span class="text-sm font-medium">Teams</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary transition-colors" href="projects.php">
                        <span class="material-symbols-outlined filled">work</span>
                        <span class="text-sm font-semibold">Projects</span>
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
                <div class="relative w-full max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-text-secondary">search</span>
                    </div>
                    <input id="searchTasks" class="block w-full pl-10 pr-3 py-2 border-none rounded-lg leading-5 bg-background-light dark:bg-slate-800 text-text-main dark:text-white placeholder-text-secondary focus:outline-none focus:ring-1 focus:ring-primary sm:text-sm" placeholder="Search tasks..." type="text"/>
                </div>
            </div>
        </header>

        <!-- Scrollable Page Content -->
        <div class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark">
            <div class="max-w-6xl mx-auto px-6 py-8 flex flex-col gap-8">
                <!-- Breadcrumbs -->
                <nav class="flex text-sm font-medium text-text-secondary">
                    <a class="hover:text-primary transition-colors" href="projects.php">Projects</a>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-text-main dark:text-white"><?php echo htmlspecialchars($project['name']); ?></span>
                </nav>

                <!-- Page Header -->
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <h1 class="text-3xl font-bold text-text-main dark:text-white tracking-tight">
                            <?php echo htmlspecialchars($project['name']); ?>
                            <?php if ($project['client_name']): ?>
                                - <?php echo htmlspecialchars($project['client_name']); ?>
                            <?php endif; ?>
                        </h1>
                        <div class="flex items-center gap-3 text-sm">
                            <?php
                            $status_class = '';
                            $status_text = ucfirst(str_replace('_', ' ', $project['status']));
                            switch($project['status']) {
                                case 'active':
                                    $status_class = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800';
                                    break;
                                case 'completed':
                                    $status_class = 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200 dark:border-blue-800';
                                    break;
                                case 'on_hold':
                                    $status_class = 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400 border-gray-200 dark:border-gray-800';
                                    break;
                                default:
                                    $status_class = 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800';
                            }
                            ?>
                            <span class="px-2.5 py-0.5 rounded-full <?php echo $status_class; ?> font-semibold text-xs border"><?php echo $status_text; ?></span>
                            <?php if ($project['client_name']): ?>
                            <span class="text-text-secondary">Client: <span class="text-text-main dark:text-gray-200 font-medium"><?php echo htmlspecialchars($project['client_name']); ?></span></span>
                            <?php endif; ?>
                            <!-- <span class="text-gray-300 dark:text-gray-600">|</span> -->
                            <!-- <span class="text-text-secondary">ID: #PROJ-<?php echo str_pad($project['id'], 6, '0', STR_PAD_LEFT); ?></span> -->
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <?php if ($user['can_create_projects'] || in_array($user['role'], ['admin', 'supervisor'])): ?>
                        <button onclick="editProject()" class="px-4 py-2 bg-white dark:bg-slate-800 border border-border-light dark:border-border-dark rounded-lg text-sm font-semibold text-text-main dark:text-white shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">edit</span>
                            Edit Project
                        </button>
                        <a href="project_phases.php?id=<?php echo $project_id; ?>" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">view_timeline</span>
                            Manage Phases
                        </a>
                        <button onclick="deleteProject()" class="px-4 py-2 bg-white dark:bg-slate-800 border border-border-light dark:border-border-dark rounded-lg text-sm font-semibold text-red-600 shadow-sm hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">delete</span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Overview & Stats Grid -->
                <!-- <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm flex flex-col justify-between">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-text-secondary text-sm font-medium">Total Budget</p>
                                <p class="text-2xl font-bold text-text-main dark:text-white mt-1">
                                    <?php echo $project['budget_hours'] ? $project['budget_hours'] . ' Hours' : 'No Budget Set'; ?>
                                </p>
                            </div>
                            <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-primary">
                                <span class="material-symbols-outlined">account_balance_wallet</span>
                            </div>
                        </div>
                        <?php if ($project['budget_hours']): ?>
                        <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-primary h-1.5 rounded-full" style="width: 100%"></div>
                        </div>
                        <p class="text-xs text-text-secondary mt-2">Fixed budget allocation</p>
                        <?php endif; ?>
                    </div>

                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm flex flex-col justify-between">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-text-secondary text-sm font-medium">Hours Used</p>
                                <p class="text-2xl font-bold text-text-main dark:text-white mt-1"><?php echo round($project['hours_logged'], 1); ?> Hours</p>
                            </div>
                            <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg text-orange-600 dark:text-orange-400">
                                <span class="material-symbols-outlined">timelapse</span>
                            </div>
                        </div>
                        <?php if ($project['budget_hours']): ?>
                        <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-orange-500 h-1.5 rounded-full" style="width: <?php echo min(100, ($project['hours_logged'] / $project['budget_hours']) * 100); ?>%"></div>
                        </div>
                        <p class="text-xs text-text-secondary mt-2"><?php echo round(($project['hours_logged'] / $project['budget_hours']) * 100, 1); ?>% of budget consumed</p>
                        <?php endif; ?>
                    </div>

                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm flex flex-col justify-between">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-text-secondary text-sm font-medium">Deadline</p>
                                <p class="text-2xl font-bold text-text-main dark:text-white mt-1">
                                    <?php echo $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : 'No Deadline'; ?>
                                </p>
                            </div>
                            <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-purple-600 dark:text-purple-400">
                                <span class="material-symbols-outlined">event</span>
                            </div>
                        </div>
                        <?php if ($project['end_date']): ?>
                        <div class="flex items-center gap-2 text-sm text-text-secondary">
                            <span class="material-symbols-outlined text-base">schedule</span>
                            <span><?php echo $days_remaining; ?> days remaining</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div> -->

                <!-- Description Card -->
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm p-6">
                    <h3 class="text-lg font-bold text-text-main dark:text-white mb-3">Project Description</h3>
                    <p class="text-text-secondary dark:text-gray-300 leading-relaxed max-w-4xl">
                        <?php echo $project['description'] ? nl2br(htmlspecialchars($project['description'])) : 'No description provided.'; ?>
                    </p>
                </div>

                <!-- Project Phases Section -->
                <?php if ($project_phases->num_rows > 0): ?>
                <section class="flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-text-main dark:text-white">Project Phases</h2>
                    </div>
                    <div class="space-y-4">
                        <?php while ($phase = $project_phases->fetch_assoc()): 
                            $phase_progress = $phase['task_count'] > 0 ? round(($phase['completed_tasks'] / $phase['task_count']) * 100) : 0;
                        ?>
                        <div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-text-main dark:text-white"><?php echo htmlspecialchars($phase['name']); ?></h3>
                                    <p class="text-sm text-text-secondary mt-1"><?php echo htmlspecialchars($phase['description'] ?? ''); ?></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <?php
                                    $phase_status_class = '';
                                    switch($phase['status']) {
                                        case 'completed':
                                            $phase_status_class = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                                            break;
                                        case 'in_progress':
                                            $phase_status_class = 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
                                            break;
                                        default:
                                            $phase_status_class = 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
                                    }
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium <?php echo $phase_status_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $phase['status'])); ?>
                                    </span>
                                    <?php if ($phase['status'] !== 'completed'): ?>
                                    <button onclick="markPhaseComplete(<?php echo $phase['id']; ?>, 'completed')" class="text-text-secondary hover:text-green-600 p-1 rounded transition-colors" title="Mark Phase as Complete">
                                        <span class="material-symbols-outlined">check_circle</span>
                                    </button>
                                    <?php else: ?>
                                    <button onclick="markPhaseComplete(<?php echo $phase['id']; ?>, 'not_completed')" class="text-text-secondary hover:text-blue-600 p-1 rounded transition-colors" title="Mark as Not Complete">
                                        <span class="material-symbols-outlined">replay</span>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-base text-text-secondary">task</span>
                                    <span class="text-text-secondary"><?php echo $phase['completed_tasks']; ?> / <?php echo $phase['task_count']; ?> tasks completed</span>
                                </div>
                                <div class="flex-1 bg-gray-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                                    <div class="bg-primary h-2 rounded-full transition-all" style="width: <?php echo $phase_progress; ?>%"></div>
                                </div>
                                <span class="text-text-secondary font-medium"><?php echo $phase_progress; ?>%</span>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Tasks/Phases Section -->
                <section class="flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-text-main dark:text-white">Project Tasks</h2>
                        <?php if ($user['can_create_projects'] || in_array($user['role'], ['admin', 'supervisor', 'team_leader'])): ?>
                        <button onclick="addTask()" class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-sm shadow-blue-200 dark:shadow-none">
                            <span class="material-symbols-outlined text-lg">add</span>
                            Add New Task
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-slate-800/50 border-b border-border-light dark:border-border-dark">
                                        <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider w-1/3">Task Name</th>
                                        <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                                        <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider">Hours Logged</th>
                                        <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider">Assigned To</th>
                                        <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border-light dark:divide-border-dark">
                                    <?php while ($task = $phases->fetch_assoc()): ?>
                                    <tr class="group hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors <?php echo $task['status'] == 'in_progress' ? 'bg-blue-50/30 dark:bg-blue-900/10' : ''; ?>">
                                        <td class="py-4 px-6">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-semibold text-text-main dark:text-white"><?php echo htmlspecialchars($task['name'] ?? ''); ?></span>
                                                <span class="text-xs text-text-secondary mt-0.5"><?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'No due date'; ?></span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <?php
                                            $task_status_class = '';
                                            $task_status_text = ucfirst(str_replace('_', ' ', $task['status']));
                                            switch($task['status']) {
                                                case 'completed':
                                                    $task_status_class = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800';
                                                    break;
                                                case 'in_progress':
                                                    $task_status_class = 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200 dark:border-blue-800';
                                                    break;
                                                default:
                                                    $task_status_class = 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border-gray-200 dark:border-gray-700';
                                            }
                                            ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?php echo $task_status_class; ?> border">
                                                <span class="w-1.5 h-1.5 rounded-full <?php echo $task['status'] == 'in_progress' ? 'animate-pulse bg-blue-500' : ($task['status'] == 'completed' ? 'bg-green-500' : 'bg-gray-400'); ?>"></span>
                                                <?php echo $task_status_text; ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="text-sm font-medium text-text-main dark:text-gray-200">0 hrs</span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <?php if ($task['assigned_to']): ?>
                                            <div class="flex items-center gap-3">
                                                <div class="size-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold">
                                                    <?php echo strtoupper(substr($task['firstname'], 0, 1) . substr($task['lastname'], 0, 1)); ?>
                                                </div>
                                                <span class="text-sm text-text-main dark:text-white font-medium"><?php echo htmlspecialchars($task['firstname'] . ' ' . $task['lastname']); ?></span>
                                            </div>
                                            <?php else: ?>
                                            <div class="flex items-center text-text-secondary text-sm italic">
                                                <span class="material-symbols-outlined text-base mr-1">person_add</span>
                                                Unassigned
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <?php if ($task['status'] !== 'completed'): ?>
                                                <button onclick="markComplete(<?php echo $task['id']; ?>, 'completed')" class="text-text-secondary hover:text-green-600 p-1 rounded transition-colors" title="Mark as Complete">
                                                    <span class="material-symbols-outlined text-xl">check_circle</span>
                                                </button>
                                                <?php else: ?>
                                                <button onclick="markComplete(<?php echo $task['id']; ?>, 'in_progress')" class="text-text-secondary hover:text-blue-600 p-1 rounded transition-colors" title="Mark as In Progress">
                                                    <span class="material-symbols-outlined text-xl">replay</span>
                                                </button>
                                                <?php endif; ?>
                                                <button onclick="editTask(<?php echo $task['id']; ?>)" class="text-text-secondary hover:text-primary p-1 rounded transition-colors">
                                                    <span class="material-symbols-outlined text-xl">edit</span>
                                                </button>
                                                <?php if ($user['can_create_projects'] || in_array($user['role'], ['admin', 'supervisor'])): ?>
                                                <button onclick="deleteTask(<?php echo $task['id']; ?>)" class="text-text-secondary hover:text-red-500 p-1 rounded transition-colors opacity-0 group-hover:opacity-100">
                                                    <span class="material-symbols-outlined text-xl">delete</span>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>

<script>
function editProject() {
    window.location.href = `edit_project.php?id=<?php echo $project_id; ?>`;
}

function deleteProject() {
    if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
        window.location.href = `delete_project.php?id=<?php echo $project_id; ?>`;
    }
}

function addTask() {
    window.location.href = `add_task.php?project_id=<?php echo $project_id; ?>`;
}

function editTask(taskId) {
    window.location.href = `edit_task.php?id=${taskId}`;
}

function deleteTask(taskId) {
    if (confirm('Are you sure you want to delete this task?')) {
        window.location.href = `delete_task.php?id=${taskId}`;
    }
}

function markComplete(taskId, status) {
    const message = status === 'completed' ? 'Mark this task as completed?' : 'Mark this task as in progress?';
    if (confirm(message)) {
        window.location.href = `mark_complete.php?type=task&id=${taskId}&status=${status}&redirect=project_details.php?id=<?php echo $project_id; ?>`;
    }
}

function markPhaseComplete(phaseId, status) {
    const message = status === 'completed' ? 'Mark this phase as completed?' : 'Mark this phase as pending?';
    if (confirm(message)) {
        window.location.href = `mark_complete.php?type=phase&id=${phaseId}&status=${status}&redirect=project_details.php?id=<?php echo $project_id; ?>`;
    }
}

// Search functionality
document.getElementById('searchTasks').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const taskRows = document.querySelectorAll('tbody tr');
    
    taskRows.forEach(row => {
        const taskName = row.querySelector('td:first-child span')?.textContent.toLowerCase() || '';
        const assignedTo = row.querySelector('td:nth-child(4) span')?.textContent.toLowerCase() || '';
        const status = row.querySelector('td:nth-child(2) span')?.textContent.toLowerCase() || '';
        
        if (taskName.includes(searchTerm) || assignedTo.includes(searchTerm) || status.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

</body>
</html>