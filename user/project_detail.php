<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$business_id = $_SESSION['business_id'];
$project_id = $_GET['id'] ?? 0;

// Check if user is a team leader
$stmt = $db->prepare("SELECT user_role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$is_team_leader = ($user_data['user_role'] === 'team_leader');

// Get project details
$project_query = "
    SELECT p.*, 
           t.name as team_name, t.team_leader_id,
           u.firstname, u.lastname,
           COUNT(DISTINCT ta.id) as total_tasks,
           COUNT(DISTINCT CASE WHEN ta.status = 'completed' THEN ta.id END) as completed_tasks,
           COUNT(DISTINCT CASE WHEN ta.assigned_to = ? THEN ta.id END) as my_tasks,
           COUNT(DISTINCT CASE WHEN ta.assigned_to = ? AND ta.status = 'completed' THEN ta.id END) as my_completed_tasks
    FROM projects p
    LEFT JOIN teams t ON p.team_id = t.id
    LEFT JOIN users u ON p.created_by = u.id
    LEFT JOIN tasks ta ON p.id = ta.project_id
    WHERE p.id = ? AND p.business_id = ?
    GROUP BY p.id
";

$stmt = $db->prepare($project_query);
$stmt->bind_param("iiii", $user_id, $user_id, $project_id, $business_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    header('Location: projects.php');
    exit;
}

// Check if user has access (is assigned tasks or is part of the team)
$access_query = "
    SELECT COUNT(*) as has_access
    FROM (
        SELECT 1 FROM tasks WHERE project_id = ? AND assigned_to = ?
        UNION
        SELECT 1 FROM team_members tm 
        JOIN teams t ON tm.team_id = t.id 
        WHERE t.id = ? AND tm.user_id = ?
    ) AS access_check
";

$stmt = $db->prepare($access_query);
$stmt->bind_param("iiii", $project_id, $user_id, $project['team_id'], $user_id);
$stmt->execute();
$access = $stmt->get_result()->fetch_assoc();

if ($access['has_access'] == 0) {
    header('Location: projects.php');
    exit;
}

// Get project phases
$phases_query = "
    SELECT pp.*,
           COUNT(DISTINCT ta.id) as task_count,
           COUNT(DISTINCT CASE WHEN ta.status = 'completed' THEN ta.id END) as completed_count
    FROM project_phases pp
    LEFT JOIN tasks ta ON pp.id = ta.phase_id
    WHERE pp.project_id = ?
    GROUP BY pp.id
    ORDER BY pp.order_index, pp.created_at
";

$stmt = $db->prepare($phases_query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$phases = $stmt->get_result();

// Get my tasks for this project
$tasks_query = "
    SELECT ta.*, pp.name as phase_name,
           DATEDIFF(ta.due_date, CURDATE()) as days_until_due
    FROM tasks ta
    LEFT JOIN project_phases pp ON ta.phase_id = pp.id
    WHERE ta.project_id = ? AND ta.assigned_to = ?
    ORDER BY 
        CASE 
            WHEN ta.status = 'in_progress' THEN 1
            WHEN ta.status = 'pending' THEN 2
            WHEN ta.status = 'blocked' THEN 3
            WHEN ta.status = 'completed' THEN 4
            ELSE 5
        END,
        ta.due_date ASC
";

$stmt = $db->prepare($tasks_query);
$stmt->bind_param("ii", $project_id, $user_id);
$stmt->execute();
$tasks = $stmt->get_result();

// Calculate progress
$overall_progress = $project['total_tasks'] > 0
    ? round(($project['completed_tasks'] / $project['total_tasks']) * 100)
    : 0;

$my_progress = $project['my_tasks'] > 0
    ? round(($project['my_completed_tasks'] / $project['my_tasks']) * 100)
    : 0;

// Check if user is team leader of this project
$can_create_task = $is_team_leader && ($project['team_leader_id'] == $user_id);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title><?php echo htmlspecialchars($project['name']); ?> - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#135bec",
                        "primary-dark": "#0d47b8",
                        background: "#f8fafc",
                        card: "#ffffff",
                        "text-main": "#1e293b",
                        "text-secondary": "#64748b",
                        "border-subtle": "#e2e8f0",
                    },
                    fontFamily: {
                        display: ["Inter", "sans-serif"]
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
        $current_page = 'projects.php';
        include 'sidebar.php';
        ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden bg-background relative">
            <!-- Top Header -->
            <div class="md:hidden flex items-center justify-between p-4 border-b border-border-subtle bg-card">
                <div class="flex items-center gap-2">
                    <a href="projects.php" class="p-2 -ml-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                    <h1 class="text-lg font-bold">Project Details</h1>
                </div>
                <button onclick="toggleSidebar()" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto">
                <div class="max-w-[1400px] w-full mx-auto p-4 md:p-6 lg:p-8 flex flex-col gap-6">
                    <!-- Breadcrumbs -->
                    <nav class="flex items-center gap-2 text-sm">
                        <a href="projects.php" class="text-text-secondary hover:text-primary transition-colors">My Projects</a>
                        <span class="material-symbols-outlined text-text-secondary text-[16px]">chevron_right</span>
                        <span class="text-slate-900 font-medium"><?php echo htmlspecialchars($project['name']); ?></span>
                    </nav>

                    <!-- Project Header -->
                    <div class="bg-card border border-border-subtle rounded-xl p-6">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-6">
                            <div class="flex-1">
                                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2"><?php echo htmlspecialchars($project['name']); ?></h1>
                                <?php if ($project['description']): ?>
                                    <p class="text-text-secondary"><?php echo htmlspecialchars($project['description']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-green-100 text-green-700 border border-green-200">
                                    <span class="material-symbols-outlined text-[16px] mr-1">check_circle</span>
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Project Meta Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <?php if ($project['team_name']): ?>
                                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg">
                                    <div class="bg-blue-100 rounded-lg p-2">
                                        <span class="material-symbols-outlined text-blue-600 text-[20px]">group</span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-text-secondary">Team</p>
                                        <p class="font-medium text-slate-900"><?php echo htmlspecialchars($project['team_name']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($project['client_name']): ?>
                                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg">
                                    <div class="bg-purple-100 rounded-lg p-2">
                                        <span class="material-symbols-outlined text-purple-600 text-[20px]">business</span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-text-secondary">Client</p>
                                        <p class="font-medium text-slate-900"><?php echo htmlspecialchars($project['client_name']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($project['start_date']): ?>
                                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg">
                                    <div class="bg-green-100 rounded-lg p-2">
                                        <span class="material-symbols-outlined text-green-600 text-[20px]">event</span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-text-secondary">Start Date</p>
                                        <p class="font-medium text-slate-900"><?php echo date('M d, Y', strtotime($project['start_date'])); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($project['end_date']): ?>
                                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg">
                                    <div class="bg-orange-100 rounded-lg p-2">
                                        <span class="material-symbols-outlined text-orange-600 text-[20px]">event_available</span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-text-secondary">End Date</p>
                                        <p class="font-medium text-slate-900"><?php echo date('M d, Y', strtotime($project['end_date'])); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Progress Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Overall Progress -->
                        <div class="bg-card border border-border-subtle rounded-xl p-6">
                            <h3 class="font-semibold text-slate-900 mb-4">Overall Project Progress</h3>
                            <div class="flex items-center gap-4 mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm text-text-secondary">Completion</span>
                                        <span class="text-2xl font-bold text-slate-900"><?php echo $overall_progress; ?>%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                                        <div class="bg-primary h-full rounded-full transition-all duration-300" style="width: <?php echo $overall_progress; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-text-secondary"><?php echo $project['completed_tasks']; ?> of <?php echo $project['total_tasks']; ?> tasks completed</span>
                            </div>
                        </div>

                        <!-- My Progress -->
                        <div class="bg-card border border-border-subtle rounded-xl p-6">
                            <h3 class="font-semibold text-slate-900 mb-4">My Progress</h3>
                            <div class="flex items-center gap-4 mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm text-text-secondary">Your Tasks</span>
                                        <span class="text-2xl font-bold text-slate-900"><?php echo $my_progress; ?>%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                                        <div class="bg-green-600 h-full rounded-full transition-all duration-300" style="width: <?php echo $my_progress; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-text-secondary"><?php echo $project['my_completed_tasks']; ?> of <?php echo $project['my_tasks']; ?> tasks completed</span>
                            </div>
                        </div>
                    </div>

                    <!-- Project Phases -->
                    <?php if ($phases->num_rows > 0): ?>
                        <div class="bg-card border border-border-subtle rounded-xl p-6">
                            <h2 class="text-xl font-semibold text-slate-900 mb-4">Project Phases</h2>
                            <div class="space-y-3">
                                <?php while ($phase = $phases->fetch_assoc()):
                                    $phase_progress = $phase['task_count'] > 0
                                        ? round(($phase['completed_count'] / $phase['task_count']) * 100)
                                        : 0;
                                    $phase_status_colors = [
                                        'pending' => 'bg-slate-100 text-slate-700',
                                        'in_progress' => 'bg-yellow-100 text-yellow-700',
                                        'completed' => 'bg-green-100 text-green-700'
                                    ];
                                ?>
                                    <div class="border border-border-subtle rounded-lg p-4">
                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-slate-900"><?php echo htmlspecialchars($phase['name']); ?></h3>
                                                <?php if ($phase['description']): ?>
                                                    <p class="text-sm text-text-secondary mt-1"><?php echo htmlspecialchars($phase['description']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium <?php echo $phase_status_colors[$phase['status']] ?? $phase_status_colors['pending']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $phase['status'])); ?>
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-4">
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between mb-1 text-xs">
                                                    <span class="text-text-secondary"><?php echo $phase['task_count']; ?> tasks</span>
                                                    <span class="font-semibold text-slate-900"><?php echo $phase_progress; ?>%</span>
                                                </div>
                                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                                    <div class="bg-primary h-full rounded-full transition-all duration-300" style="width: <?php echo $phase_progress; ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- My Tasks -->
                    <div class="bg-card border border-border-subtle rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold text-slate-900">My Tasks (<?php echo $project['my_tasks']; ?>)</h2>
                            <?php if ($can_create_task): ?>
                                <a
                                    href="create_task.php?project_id=<?php echo $project_id; ?>"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium text-sm">
                                    <span class="material-symbols-outlined text-[20px]">add</span>
                                    <span class="hidden sm:inline">Create Task</span>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ($tasks->num_rows > 0): ?>
                            <div class="space-y-3">
                                <?php
                                $status_colors = [
                                    'pending' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    'in_progress' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'completed' => 'bg-green-100 text-green-700 border-green-200',
                                    'blocked' => 'bg-red-100 text-red-700 border-red-200'
                                ];
                                $priority_colors = [
                                    'low' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'medium' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
                                    'high' => 'bg-orange-50 text-orange-700 border-orange-200',
                                    'urgent' => 'bg-red-50 text-red-700 border-red-200'
                                ];

                                while ($task = $tasks->fetch_assoc()):
                                    $is_overdue = $task['days_until_due'] < 0 && $task['status'] != 'completed';
                                    $is_due_soon = $task['days_until_due'] >= 0 && $task['days_until_due'] <= 3 && $task['status'] != 'completed';
                                ?>
                                    <div class="border <?php echo $is_overdue ? 'border-red-200 bg-red-50/30' : 'border-border-subtle'; ?> rounded-lg p-4 hover:shadow-sm transition-all duration-200 cursor-pointer" onclick="window.location.href='task_detail.php?id=<?php echo $task['id']; ?>'">
                                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-slate-900 mb-1"><?php echo htmlspecialchars($task['name']); ?></h3>
                                                <?php if ($task['description']): ?>
                                                    <p class="text-sm text-text-secondary mb-3"><?php echo htmlspecialchars($task['description']); ?></p>
                                                <?php endif; ?>

                                                <div class="flex flex-wrap items-center gap-3 text-sm">
                                                    <?php if ($task['phase_name']): ?>
                                                        <div class="flex items-center gap-1 text-text-secondary">
                                                            <span class="material-symbols-outlined text-[16px]">layers</span>
                                                            <span><?php echo htmlspecialchars($task['phase_name']); ?></span>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($task['due_date']): ?>
                                                        <div class="flex items-center gap-1 <?php echo $is_overdue ? 'text-red-600 font-semibold' : ($is_due_soon ? 'text-orange-600' : 'text-text-secondary'); ?>">
                                                            <span class="material-symbols-outlined text-[16px]">
                                                                <?php echo $is_overdue ? 'warning' : 'calendar_today'; ?>
                                                            </span>
                                                            <span>Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap items-start gap-2">
                                                <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium border <?php echo $status_colors[$task['status']] ?? $status_colors['pending']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                                </span>

                                                <?php if ($task['priority']): ?>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium border <?php echo $priority_colors[$task['priority']] ?? $priority_colors['medium']; ?>">
                                                        <?php echo ucfirst($task['priority']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
                                    <span class="material-symbols-outlined text-slate-400 text-[32px]">assignment_outline</span>
                                </div>
                                <h3 class="text-lg font-semibold text-slate-900 mb-2">No Tasks Assigned</h3>
                                <p class="text-text-secondary">You don't have any tasks assigned in this project yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
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
    </script>
</body>

</html>