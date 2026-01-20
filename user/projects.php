<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$business_id = $_SESSION['business_id'];

// Get projects where user is assigned to tasks or is part of the team
$projects_query = "
    SELECT DISTINCT p.*, 
           t.name as team_name,
           COUNT(DISTINCT ta.id) as total_tasks,
           COUNT(DISTINCT CASE WHEN ta.assigned_to = ? AND ta.status = 'completed' THEN ta.id END) as my_completed_tasks,
           COUNT(DISTINCT CASE WHEN ta.assigned_to = ? THEN ta.id END) as my_tasks
    FROM projects p
    LEFT JOIN teams t ON p.team_id = t.id
    LEFT JOIN tasks ta ON p.id = ta.project_id
    LEFT JOIN team_members tm ON t.id = tm.team_id
    WHERE p.business_id = ? 
    AND (ta.assigned_to = ? OR tm.user_id = ?)
    AND p.status = 'active'
    GROUP BY p.id
    ORDER BY p.created_at DESC
";

$stmt = $db->prepare($projects_query);
$stmt->bind_param("iiiii", $user_id, $user_id, $business_id, $user_id, $user_id);
$stmt->execute();
$projects = $stmt->get_result();

// Get all tasks assigned to the user
$tasks_query = "
    SELECT ta.*, p.name as project_name, p.id as project_id,
           DATEDIFF(ta.due_date, CURDATE()) as days_until_due
    FROM tasks ta
    JOIN projects p ON ta.project_id = p.id
    WHERE ta.assigned_to = ? AND p.business_id = ?
    ORDER BY 
        CASE 
            WHEN ta.status = 'in_progress' THEN 1
            WHEN ta.status = 'pending' THEN 2
            WHEN ta.status = 'completed' THEN 3
            ELSE 4
        END,
        ta.due_date ASC
    LIMIT 10
";

$stmt = $db->prepare($tasks_query);
$stmt->bind_param("ii", $user_id, $business_id);
$stmt->execute();
$recent_tasks = $stmt->get_result();

// Get task statistics for the user
$stats_query = "
    SELECT 
        COUNT(*) as total_tasks,
        COUNT(CASE WHEN ta.status = 'completed' THEN 1 END) as completed_tasks,
        COUNT(CASE WHEN ta.status = 'in_progress' THEN 1 END) as in_progress_tasks,
        COUNT(CASE WHEN ta.status = 'pending' THEN 1 END) as pending_tasks,
        COUNT(CASE WHEN ta.due_date < CURDATE() AND ta.status != 'completed' THEN 1 END) as overdue_tasks
    FROM tasks ta
    JOIN projects p ON ta.project_id = p.id
    WHERE ta.assigned_to = ? AND p.business_id = ?
";

$stmt = $db->prepare($stats_query);
$stmt->bind_param("ii", $user_id, $business_id);
$stmt->execute();
$task_stats = $stmt->get_result()->fetch_assoc();

// Calculate completion rate
$completion_rate = $task_stats['total_tasks'] > 0
    ? round(($task_stats['completed_tasks'] / $task_stats['total_tasks']) * 100)
    : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>My Projects - TimeTrack Pro</title>
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
        // Include sidebar component
        $current_page = 'projects.php';
        include 'sidebar.php';
        ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden bg-background relative">
            <!-- Top Header -->
            <div class="md:hidden flex items-center justify-between p-4 border-b border-border-subtle bg-card">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">work</span>
                    <h1 class="text-lg font-bold">My Projects</h1>
                </div>
                <button onclick="toggleSidebar()" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto">
                <div class="max-w-[1400px] w-full mx-auto p-4 md:p-6 lg:p-8 flex flex-col gap-6 md:gap-8">
                    <!-- Page Header -->
                    <header class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-2xl md:text-3xl font-bold text-slate-900">My Projects</h1>
                                <p class="text-sm text-text-secondary mt-1">Track your assigned projects and tasks</p>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-card border border-border-subtle rounded-xl p-4">
                                <div class="flex items-center gap-3">
                                    <div class="bg-blue-50 rounded-lg p-2">
                                        <span class="material-symbols-outlined text-primary text-[24px]">task_alt</span>
                                    </div>
                                    <div>
                                        <p class="text-2xl font-bold text-slate-900"><?php echo $task_stats['total_tasks']; ?></p>
                                        <p class="text-xs text-text-secondary">Total Tasks</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-card border border-border-subtle rounded-xl p-4">
                                <div class="flex items-center gap-3">
                                    <div class="bg-yellow-50 rounded-lg p-2">
                                        <span class="material-symbols-outlined text-yellow-600 text-[24px]">pending</span>
                                    </div>
                                    <div>
                                        <p class="text-2xl font-bold text-slate-900"><?php echo $task_stats['in_progress_tasks']; ?></p>
                                        <p class="text-xs text-text-secondary">In Progress</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-card border border-border-subtle rounded-xl p-4">
                                <div class="flex items-center gap-3">
                                    <div class="bg-green-50 rounded-lg p-2">
                                        <span class="material-symbols-outlined text-green-600 text-[24px]">check_circle</span>
                                    </div>
                                    <div>
                                        <p class="text-2xl font-bold text-slate-900"><?php echo $task_stats['completed_tasks']; ?></p>
                                        <p class="text-xs text-text-secondary">Completed</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-card border border-border-subtle rounded-xl p-4">
                                <div class="flex items-center gap-3">
                                    <div class="bg-purple-50 rounded-lg p-2">
                                        <span class="material-symbols-outlined text-purple-600 text-[24px]">percent</span>
                                    </div>
                                    <div>
                                        <p class="text-2xl font-bold text-slate-900"><?php echo $completion_rate; ?>%</p>
                                        <p class="text-xs text-text-secondary">Completion Rate</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </header>

                    <!-- Projects Grid -->
                    <section>
                        <h2 class="text-lg font-semibold text-slate-900 mb-4">My Projects</h2>
                        <?php if ($projects->num_rows > 0): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php while ($project = $projects->fetch_assoc()):
                                    $project_progress = $project['my_tasks'] > 0
                                        ? round(($project['my_completed_tasks'] / $project['my_tasks']) * 100)
                                        : 0;
                                ?>
                                    <div class="bg-card border border-border-subtle rounded-xl p-5 hover:shadow-lg transition-shadow duration-200 cursor-pointer" onclick="window.location.href='project_detail.php?id=<?php echo $project['id']; ?>'">
                                        <div class="flex items-start justify-between mb-4">
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-slate-900 text-base mb-1"><?php echo htmlspecialchars($project['name']); ?></h3>
                                                <p class="text-sm text-text-secondary line-clamp-2"><?php echo htmlspecialchars($project['description'] ?? 'No description'); ?></p>
                                            </div>
                                        </div>

                                        <div class="space-y-3 mb-4">
                                            <?php if ($project['team_name']): ?>
                                                <div class="flex items-center gap-2 text-sm text-text-secondary">
                                                    <span class="material-symbols-outlined text-[18px]">group</span>
                                                    <span><?php echo htmlspecialchars($project['team_name']); ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($project['client_name']): ?>
                                                <div class="flex items-center gap-2 text-sm text-text-secondary">
                                                    <span class="material-symbols-outlined text-[18px]">business</span>
                                                    <span><?php echo htmlspecialchars($project['client_name']); ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <div class="flex items-center gap-2 text-sm text-text-secondary">
                                                <span class="material-symbols-outlined text-[18px]">assignment</span>
                                                <span><?php echo $project['my_tasks']; ?> task<?php echo $project['my_tasks'] != 1 ? 's' : ''; ?> assigned to you</span>
                                            </div>
                                        </div>

                                        <!-- Progress Bar -->
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="text-text-secondary">Your Progress</span>
                                                <span class="font-semibold text-slate-900"><?php echo $project_progress; ?>%</span>
                                            </div>
                                            <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                                <div class="bg-primary h-full rounded-full transition-all duration-300" style="width: <?php echo $project_progress; ?>%"></div>
                                            </div>
                                            <p class="text-xs text-text-secondary">
                                                <?php echo $project['my_completed_tasks']; ?> of <?php echo $project['my_tasks']; ?> completed
                                            </p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="bg-card border border-border-subtle rounded-xl p-12 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
                                    <span class="material-symbols-outlined text-slate-400 text-[32px]">work_outline</span>
                                </div>
                                <h3 class="text-lg font-semibold text-slate-900 mb-2">No Projects Yet</h3>
                                <p class="text-text-secondary">You haven't been assigned to any projects yet.</p>
                            </div>
                        <?php endif; ?>
                    </section>

                    <!-- Recent Tasks -->
                    <section>
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-slate-900">Recent Tasks</h2>
                            <a href="tasks.php" class="text-sm text-primary hover:text-primary-dark font-medium transition-colors">View All →</a>
                        </div>

                        <?php if ($recent_tasks->num_rows > 0): ?>
                            <div class="bg-card border border-border-subtle rounded-xl overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-slate-50 border-b border-border-subtle">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Task</th>
                                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Project</th>
                                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Due Date</th>
                                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Priority</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-border-subtle">
                                            <?php while ($task = $recent_tasks->fetch_assoc()):
                                                $status_colors = [
                                                    'pending' => 'bg-slate-100 text-slate-700',
                                                    'in_progress' => 'bg-yellow-100 text-yellow-700',
                                                    'completed' => 'bg-green-100 text-green-700',
                                                    'blocked' => 'bg-red-100 text-red-700'
                                                ];
                                                $priority_colors = [
                                                    'low' => 'bg-blue-100 text-blue-700',
                                                    'medium' => 'bg-yellow-100 text-yellow-700',
                                                    'high' => 'bg-orange-100 text-orange-700',
                                                    'urgent' => 'bg-red-100 text-red-700'
                                                ];

                                                $is_overdue = $task['days_until_due'] < 0 && $task['status'] != 'completed';
                                                $is_due_soon = $task['days_until_due'] >= 0 && $task['days_until_due'] <= 3 && $task['status'] != 'completed';
                                            ?>
                                                <tr class="hover:bg-slate-50 transition-colors cursor-pointer" onclick="window.location.href='task_detail.php?id=<?php echo $task['id']; ?>'">
                                                    <td class="px-6 py-4">
                                                        <div class="flex items-center gap-3">
                                                            <div class="flex-1">
                                                                <p class="font-medium text-slate-900"><?php echo htmlspecialchars($task['name']); ?></p>
                                                                <?php if ($task['description']): ?>
                                                                    <p class="text-sm text-text-secondary line-clamp-1"><?php echo htmlspecialchars(substr($task['description'], 0, 50)); ?><?php echo strlen($task['description']) > 50 ? '...' : ''; ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span class="text-sm text-text-secondary"><?php echo htmlspecialchars($task['project_name']); ?></span>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_colors[$task['status']] ?? $status_colors['pending']; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <div class="flex items-center gap-2">
                                                            <?php if ($is_overdue): ?>
                                                                <span class="material-symbols-outlined text-red-500 text-[18px]">warning</span>
                                                            <?php elseif ($is_due_soon): ?>
                                                                <span class="material-symbols-outlined text-orange-500 text-[18px]">schedule</span>
                                                            <?php endif; ?>
                                                            <span class="text-sm <?php echo $is_overdue ? 'text-red-600 font-semibold' : ($is_due_soon ? 'text-orange-600' : 'text-text-secondary'); ?>">
                                                                <?php echo $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : 'No due date'; ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <?php if ($task['priority']): ?>
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $priority_colors[$task['priority']] ?? $priority_colors['medium']; ?>">
                                                                <?php echo ucfirst($task['priority']); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-sm text-text-secondary">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="bg-card border border-border-subtle rounded-xl p-12 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
                                    <span class="material-symbols-outlined text-slate-400 text-[32px]">assignment_outline</span>
                                </div>
                                <h3 class="text-lg font-semibold text-slate-900 mb-2">No Tasks Assigned</h3>
                                <p class="text-text-secondary">You don't have any tasks assigned to you yet.</p>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </main>
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
    </script>
</body>

</html>