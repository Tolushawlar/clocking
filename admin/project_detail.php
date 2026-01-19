<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id']) || !isset($_GET['id'])) {
    header('Location: projects.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'];
$project_id = (int)$_GET['id'];

// Get project details
$stmt = $db->prepare("
    SELECT p.*, u.firstname, u.lastname,
           COUNT(DISTINCT t.id) as total_tasks,
           COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
           COALESCE(SUM(t.actual_hours), 0) as total_hours
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

// Handle phase creation
if (isset($_POST['create_phase'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $start_date = $_POST['start_date'] ?: null;
    $end_date = $_POST['end_date'] ?: null;
    $estimated_hours = $_POST['estimated_hours'] ?: null;
    
    $stmt = $db->prepare("INSERT INTO project_phases (project_id, name, description, start_date, end_date, estimated_hours) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssd", $project_id, $name, $description, $start_date, $end_date, $estimated_hours);
    
    if ($stmt->execute()) {
        header("Location: project_detail.php?id=$project_id&msg=Phase created successfully");
        exit;
    }
}

// Handle task creation
if (isset($_POST['create_task'])) {
    $phase_id = $_POST['phase_id'] ?: null;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'] ?: null;
    $estimated_hours = $_POST['estimated_hours'] ?: null;
    $assigned_to = $_POST['assigned_to'] ?: null;
    
    $stmt = $db->prepare("INSERT INTO tasks (project_id, phase_id, name, description, priority, due_date, estimated_hours, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissssdii", $project_id, $phase_id, $name, $description, $priority, $due_date, $estimated_hours, $assigned_to, $user_id);
    
    if ($stmt->execute()) {
        header("Location: project_detail.php?id=$project_id&msg=Task created successfully");
        exit;
    }
}

// Get project phases with task counts
$phases_query = "
    SELECT ph.*, 
           COUNT(DISTINCT t.id) as total_tasks,
           COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
           COALESCE(SUM(t.actual_hours), 0) as total_hours
    FROM project_phases ph
    LEFT JOIN tasks t ON ph.id = t.phase_id
    WHERE ph.project_id = ?
    GROUP BY ph.id
    ORDER BY ph.order_index, ph.created_at
";

$stmt = $db->prepare($phases_query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$phases = $stmt->get_result();

// Get project team members
$team_query = "
    SELECT pm.*, u.firstname, u.lastname, u.email, u.role as user_role
    FROM project_members pm
    JOIN users u ON pm.user_id = u.id
    WHERE pm.project_id = ?
    ORDER BY pm.role, u.firstname
";

$stmt = $db->prepare($team_query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$team_members = $stmt->get_result();

// Get available users for assignment
$available_users_query = "
    SELECT id, firstname, lastname, email
    FROM users 
    WHERE business_id = ? AND id NOT IN (
        SELECT user_id FROM project_members WHERE project_id = ?
    )
    ORDER BY firstname, lastname
";

$stmt = $db->prepare($available_users_query);
$stmt->bind_param("ii", $business_id, $project_id);
$stmt->execute();
$available_users = $stmt->get_result();

$progress = $project['total_tasks'] > 0 ? round(($project['completed_tasks'] / $project['total_tasks']) * 100) : 0;
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>TimeTrack Pro - Project Details</title>
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
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-main dark:text-gray-300 hover:bg-background-light dark:hover:bg-slate-700 transition-colors group" href="../index.php">
                        <span class="material-symbols-outlined text-text-secondary group-hover:text-primary transition-colors">schedule</span>
                        <span class="text-sm font-medium">Time Logs</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary transition-colors" href="projects.php">
                        <span class="material-symbols-outlined filled">work</span>
                        <span class="text-sm font-semibold">Projects</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-main dark:text-gray-300 hover:bg-background-light dark:hover:bg-slate-700 transition-colors group" href="reports.php">
                        <span class="material-symbols-outlined text-text-secondary group-hover:text-primary transition-colors">bar_chart</span>
                        <span class="text-sm font-medium">Reports</span>
                    </a>
                    <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-main dark:text-gray-300 hover:bg-background-light dark:hover:bg-slate-700 transition-colors group" href="team_management.php">
                        <span class="material-symbols-outlined text-text-secondary group-hover:text-primary transition-colors">group</span>
                        <span class="text-sm font-medium">Team</span>
                    </a>
                </nav>
            </div>
            <div class="p-4 border-t border-border-light dark:border-border-dark">
                <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-text-secondary hover:text-text-main dark:hover:text-white transition-colors" href="settings.php">
                    <span class="material-symbols-outlined">settings</span>
                    <span class="text-sm font-medium">Settings</span>
                </a>
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
                <nav class="flex items-center text-sm font-medium text-text-secondary">
                    <a class="hover:text-primary transition-colors" href="projects.php">Projects</a>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-text-main dark:text-white"><?php echo htmlspecialchars($project['name']); ?></span>
                </nav>
            </div>
            
            <div class="flex items-center gap-4">
                <button class="p-2 text-text-secondary hover:bg-background-light dark:hover:bg-slate-700 rounded-full transition-colors relative">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-surface-light dark:border-surface-dark"></span>
                </button>
                <div class="h-8 w-8 rounded-full bg-cover bg-center border border-border-light" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuD3tcAhr5nMGfxyO6M1xdoI4krwAidzgz3WZmn4Jv_r6w_92lRWN0HS1tlgsR2huK4yWyRz9uYKQeaXcAaPWUdv6wAAJmz415yZ6tW5CFVLZiD_G45W8ReUHx_iXxt436H8VAPei_BVyNsr68crCK-I8rog7UAhYb2YOYzfwPIKRUBC-jHZid5hfGuJQaLVY8XzrEWh65Uc8O9i54kO1O4uouQAAjTOIZk5owF36N8UKqNTXYca1FVdI38uMspbEhyIzfgLIr4EyLg');"></div>
            </div>
        </header>

        <!-- Scrollable Page Content -->
        <div class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark">
            <div class="max-w-6xl mx-auto px-6 py-8 flex flex-col gap-8">
                <!-- Page Header -->
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <h1 class="text-3xl font-bold text-text-main dark:text-white tracking-tight"><?php echo htmlspecialchars($project['name']); ?></h1>
                        <div class="flex items-center gap-3 text-sm">
                            <?php
                            $status_colors = [
                                'planning' => 'bg-gray-100 text-gray-700 dark:bg-gray-900/30 dark:text-gray-400 border-gray-200 dark:border-gray-800',
                                'active' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800',
                                'on_hold' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 border-orange-200 dark:border-orange-800',
                                'completed' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200 dark:border-blue-800',
                                'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800'
                            ];
                            ?>
                            <span class="px-2.5 py-0.5 rounded-full font-semibold text-xs border <?php echo $status_colors[$project['status']]; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                            </span>
                            <span class="text-text-secondary">Client: <span class="text-text-main dark:text-gray-200 font-medium"><?php echo $project['client_name'] ?: 'Internal'; ?></span></span>
                            <span class="text-gray-300 dark:text-gray-600">|</span>
                            <span class="text-text-secondary">ID: #PROJ-<?php echo str_pad($project['id'], 4, '0', STR_PAD_LEFT); ?></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button onclick="openPhaseModal()" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-semibold shadow-sm hover:bg-primary-hover transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">add</span>
                            Add Phase
                        </button>
                        <button onclick="openTaskModal()" class="px-4 py-2 bg-white dark:bg-slate-800 border border-border-light dark:border-border-dark rounded-lg text-sm font-semibold text-text-main dark:text-white shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">assignment</span>
                            Add Task
                        </button>
                    </div>
                </div>

                <!-- Overview & Stats Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm flex flex-col justify-between">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-text-secondary text-sm font-medium">Total Budget</p>
                                <p class="text-2xl font-bold text-text-main dark:text-white mt-1"><?php echo $project['budget_hours'] ? $project['budget_hours'] . ' Hours' : 'No Budget Set'; ?></p>
                            </div>
                            <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-primary">
                                <span class="material-symbols-outlined">account_balance_wallet</span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-primary h-1.5 rounded-full" style="width: <?php echo $project['budget_hours'] ? min(($project['total_hours'] / $project['budget_hours']) * 100, 100) : 0; ?>%"></div>
                        </div>
                        <p class="text-xs text-text-secondary mt-2"><?php echo number_format($project['total_hours'], 1); ?> hours used</p>
                    </div>

                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm flex flex-col justify-between">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-text-secondary text-sm font-medium">Progress</p>
                                <p class="text-2xl font-bold text-text-main dark:text-white mt-1"><?php echo $progress; ?>%</p>
                            </div>
                            <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg text-green-600 dark:text-green-400">
                                <span class="material-symbols-outlined">trending_up</span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden">
                            <div class="bg-green-500 h-1.5 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <p class="text-xs text-text-secondary mt-2"><?php echo $project['completed_tasks']; ?> of <?php echo $project['total_tasks']; ?> tasks completed</p>
                    </div>

                    <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm flex flex-col justify-between">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-text-secondary text-sm font-medium">Deadline</p>
                                <p class="text-2xl font-bold text-text-main dark:text-white mt-1"><?php echo $project['end_date'] ? date('M j, Y', strtotime($project['end_date'])) : 'No Deadline'; ?></p>
                            </div>
                            <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-purple-600 dark:text-purple-400">
                                <span class="material-symbols-outlined">event</span>
                            </div>
                        </div>
                        <?php if ($project['end_date']): ?>
                        <div class="flex items-center gap-2 text-sm text-text-secondary">
                            <span class="material-symbols-outlined text-base">schedule</span>
                            <?php
                            $days_remaining = ceil((strtotime($project['end_date']) - time()) / (60 * 60 * 24));
                            if ($days_remaining > 0) {
                                echo "<span>$days_remaining days remaining</span>";
                            } elseif ($days_remaining == 0) {
                                echo "<span class='text-orange-600'>Due today</span>";
                            } else {
                                echo "<span class='text-red-600'>" . abs($days_remaining) . " days overdue</span>";
                            }
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Description Card -->
                <?php if ($project['description']): ?>
                <div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm p-6">
                    <h3 class="text-lg font-bold text-text-main dark:text-white mb-3">Project Description</h3>
                    <p class="text-text-secondary dark:text-gray-300 leading-relaxed max-w-4xl">
                        <?php echo nl2br(htmlspecialchars($project['description'])); ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Phases Section -->
                <section class="flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-bold text-text-main dark:text-white">Project Phases</h2>
                    </div>
                    <div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-slate-800/50 border-b border-border-light dark:border-border-dark">
                                        <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider w-1/3">Phase Name</th>
                                        <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider">Status</th>
                                        <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider">Duration</th>
                                        <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider">Progress</th>
                                        <th class="py-4 px-6 text-xs font-semibold text-text-secondary uppercase tracking-wider text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border-light dark:divide-border-dark">
                                    <?php while ($phase = $phases->fetch_assoc()): ?>
                                    <?php $phase_progress = $phase['total_tasks'] > 0 ? round(($phase['completed_tasks'] / $phase['total_tasks']) * 100) : 0; ?>
                                    <tr class="group hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <td class="py-4 px-6">
                                            <div class="flex flex-col">
                                                <a href="phase_detail.php?id=<?php echo $phase['id']; ?>" class="text-sm font-semibold text-text-main dark:text-white hover:text-primary transition-colors">
                                                    <?php echo htmlspecialchars($phase['name']); ?>
                                                </a>
                                                <span class="text-xs text-text-secondary mt-0.5">
                                                    <?php echo $phase['start_date'] && $phase['end_date'] ? 
                                                        date('M j', strtotime($phase['start_date'])) . ' - ' . date('M j, Y', strtotime($phase['end_date'])) : 
                                                        'No dates set'; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <?php
                                            $phase_status_colors = [
                                                'pending' => 'bg-gray-50 dark:bg-gray-900/30 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-800',
                                                'active' => 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                                                'completed' => 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800',
                                                'cancelled' => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800'
                                            ];
                                            ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border <?php echo $phase_status_colors[$phase['status']]; ?>">
                                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                                <?php echo ucfirst($phase['status']); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center gap-2">
                                                <span class="text-sm font-medium text-text-main dark:text-gray-200"><?php echo number_format($phase['total_hours'], 1); ?>h</span>
                                                <span class="text-xs text-text-secondary">logged</span>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex flex-col gap-1.5 w-24">
                                                <div class="flex justify-between text-xs mb-1">
                                                    <span class="font-medium text-text-main dark:text-gray-200"><?php echo $phase_progress; ?>%</span>
                                                    <span class="text-text-secondary"><?php echo $phase['completed_tasks']; ?>/<?php echo $phase['total_tasks']; ?></span>
                                                </div>
                                                <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-1.5">
                                                    <div class="bg-primary h-1.5 rounded-full" style="width: <?php echo $phase_progress; ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <a href="phase_detail.php?id=<?php echo $phase['id']; ?>" class="text-text-secondary hover:text-primary p-1 rounded transition-colors">
                                                <span class="material-symbols-outlined text-xl">visibility</span>
                                            </a>
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

<!-- Create Phase Modal -->
<div id="phaseModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-surface-dark rounded-xl p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-text-main dark:text-white">Add New Phase</h3>
            <button onclick="closePhaseModal()" class="text-text-secondary hover:text-text-main dark:hover:text-white">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Phase Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white"></textarea>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Start Date</label>
                    <input type="date" name="start_date" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">End Date</label>
                    <input type="date" name="end_date" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Estimated Hours</label>
                <input type="number" name="estimated_hours" step="0.5" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closePhaseModal()" class="flex-1 px-4 py-2 border border-border-light dark:border-border-dark text-text-secondary rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    Cancel
                </button>
                <button type="submit" name="create_phase" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">
                    Create Phase
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Create Task Modal -->
<div id="taskModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-surface-dark rounded-xl p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-text-main dark:text-white">Add New Task</h3>
            <button onclick="closeTaskModal()" class="text-text-secondary hover:text-text-main dark:hover:text-white">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Phase (Optional)</label>
                <select name="phase_id" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                    <option value="">No Phase</option>
                    <?php
                    $phases->data_seek(0);
                    while ($phase = $phases->fetch_assoc()): ?>
                        <option value="<?php echo $phase['id']; ?>"><?php echo htmlspecialchars($phase['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Task Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white"></textarea>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Priority</label>
                    <select name="priority" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Due Date</label>
                    <input type="date" name="due_date" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Estimated Hours</label>
                    <input type="number" name="estimated_hours" step="0.5" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-text-main dark:text-white mb-2">Assign To</label>
                    <select name="assigned_to" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                        <option value="">Unassigned</option>
                        <?php
                        $team_members->data_seek(0);
                        while ($member = $team_members->fetch_assoc()): ?>
                            <option value="<?php echo $member['user_id']; ?>"><?php echo htmlspecialchars($member['firstname'] . ' ' . $member['lastname']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeTaskModal()" class="flex-1 px-4 py-2 border border-border-light dark:border-border-dark text-text-secondary rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                    Cancel
                </button>
                <button type="submit" name="create_task" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">
                    Create Task
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPhaseModal() {
    document.getElementById('phaseModal').classList.remove('hidden');
    document.getElementById('phaseModal').classList.add('flex');
}

function closePhaseModal() {
    document.getElementById('phaseModal').classList.add('hidden');
    document.getElementById('phaseModal').classList.remove('flex');
}

function openTaskModal() {
    document.getElementById('taskModal').classList.remove('hidden');
    document.getElementById('taskModal').classList.add('flex');
}

function closeTaskModal() {
    document.getElementById('taskModal').classList.add('hidden');
    document.getElementById('taskModal').classList.remove('flex');
}

// Close modals when clicking outside
document.getElementById('phaseModal').addEventListener('click', function(e) {
    if (e.target === this) closePhaseModal();
});

document.getElementById('taskModal').addEventListener('click', function(e) {
    if (e.target === this) closeTaskModal();
});
</script>
</body>
</html>