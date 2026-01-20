<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$business_id = $_SESSION['business_id'];

// Handle status change
if ($_POST && isset($_POST['change_status'])) {
    $task_id = (int)$_POST['task_id'];
    $new_status = $_POST['new_status'];

    // Verify task belongs to user
    $verify_stmt = $db->prepare("SELECT id FROM tasks WHERE id = ? AND assigned_to = ?");
    $verify_stmt->bind_param("ii", $task_id, $user_id);
    $verify_stmt->execute();

    if ($verify_stmt->get_result()->num_rows > 0) {
        if ($new_status === 'completed') {
            $update_stmt = $db->prepare("UPDATE tasks SET status = ?, completed_at = NOW() WHERE id = ?");
        } else {
            $update_stmt = $db->prepare("UPDATE tasks SET status = ?, completed_at = NULL WHERE id = ?");
        }
        $update_stmt->bind_param("si", $new_status, $task_id);
        $update_stmt->execute();

        header('Location: tasks.php?msg=Status updated successfully');
        exit;
    }
}

// Get filter from URL
$status_filter = $_GET['status'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query with filters
$where_conditions = ["ta.assigned_to = ?", "p.business_id = ?"];
$params = [$user_id, $business_id];
$param_types = "ii";

if ($status_filter !== 'all') {
    $where_conditions[] = "ta.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if ($priority_filter !== 'all') {
    $where_conditions[] = "ta.priority = ?";
    $params[] = $priority_filter;
    $param_types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(ta.name LIKE ? OR ta.description LIKE ? OR p.name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= "sss";
}

$where_clause = implode(" AND ", $where_conditions);

// Get all tasks
$tasks_query = "
    SELECT ta.*, p.name as project_name, p.id as project_id,
           pp.name as phase_name,
           DATEDIFF(ta.due_date, CURDATE()) as days_until_due
    FROM tasks ta
    JOIN projects p ON ta.project_id = p.id
    LEFT JOIN project_phases pp ON ta.phase_id = pp.id
    WHERE $where_clause
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
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$tasks = $stmt->get_result();

// Get task statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_tasks,
        COUNT(CASE WHEN ta.status = 'completed' THEN 1 END) as completed_tasks,
        COUNT(CASE WHEN ta.status = 'in_progress' THEN 1 END) as in_progress_tasks,
        COUNT(CASE WHEN ta.status = 'pending' THEN 1 END) as pending_tasks,
        COUNT(CASE WHEN ta.status = 'blocked' THEN 1 END) as blocked_tasks,
        COUNT(CASE WHEN ta.due_date < CURDATE() AND ta.status != 'completed' THEN 1 END) as overdue_tasks,
        COUNT(CASE WHEN ta.priority = 'high' OR ta.priority = 'urgent' THEN 1 END) as high_priority_tasks
    FROM tasks ta
    JOIN projects p ON ta.project_id = p.id
    WHERE ta.assigned_to = ? AND p.business_id = ?
";

$stmt = $db->prepare($stats_query);
$stmt->bind_param("ii", $user_id, $business_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>My Tasks - TimeTrack Pro</title>
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
        $current_page = 'tasks.php';
        include 'sidebar.php';
        ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden bg-background relative">
            <!-- Top Header -->
            <div class="md:hidden flex items-center justify-between p-4 border-b border-border-subtle bg-card">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">assignment</span>
                    <h1 class="text-lg font-bold">My Tasks</h1>
                </div>
                <button onclick="toggleSidebar()" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto">
                <div class="max-w-[1400px] w-full mx-auto p-4 md:p-6 lg:p-8 flex flex-col gap-6 md:gap-8">
                    <!-- Page Header -->
                    <header>
                        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mb-2">My Tasks</h1>
                        <p class="text-sm text-text-secondary">Manage and track all your assigned tasks</p>
                    </header>

                    <?php if (isset($_GET['msg'])): ?>
                        <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                                <span><?php echo htmlspecialchars($_GET['msg']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 md:gap-4">
                        <div class="bg-card border border-border-subtle rounded-xl p-4">
                            <div class="flex flex-col">
                                <span class="material-symbols-outlined text-blue-600 text-[24px] mb-2">assignment</span>
                                <p class="text-2xl font-bold text-slate-900"><?php echo $stats['total_tasks']; ?></p>
                                <p class="text-xs text-text-secondary">Total</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border-subtle rounded-xl p-4">
                            <div class="flex flex-col">
                                <span class="material-symbols-outlined text-slate-600 text-[24px] mb-2">pending_actions</span>
                                <p class="text-2xl font-bold text-slate-900"><?php echo $stats['pending_tasks']; ?></p>
                                <p class="text-xs text-text-secondary">Pending</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border-subtle rounded-xl p-4">
                            <div class="flex flex-col">
                                <span class="material-symbols-outlined text-yellow-600 text-[24px] mb-2">hourglass_top</span>
                                <p class="text-2xl font-bold text-slate-900"><?php echo $stats['in_progress_tasks']; ?></p>
                                <p class="text-xs text-text-secondary">In Progress</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border-subtle rounded-xl p-4">
                            <div class="flex flex-col">
                                <span class="material-symbols-outlined text-green-600 text-[24px] mb-2">check_circle</span>
                                <p class="text-2xl font-bold text-slate-900"><?php echo $stats['completed_tasks']; ?></p>
                                <p class="text-xs text-text-secondary">Completed</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border-subtle rounded-xl p-4">
                            <div class="flex flex-col">
                                <span class="material-symbols-outlined text-red-600 text-[24px] mb-2">block</span>
                                <p class="text-2xl font-bold text-slate-900"><?php echo $stats['blocked_tasks']; ?></p>
                                <p class="text-xs text-text-secondary">Blocked</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border-subtle rounded-xl p-4">
                            <div class="flex flex-col">
                                <span class="material-symbols-outlined text-orange-600 text-[24px] mb-2">warning</span>
                                <p class="text-2xl font-bold text-slate-900"><?php echo $stats['overdue_tasks']; ?></p>
                                <p class="text-xs text-text-secondary">Overdue</p>
                            </div>
                        </div>

                        <div class="bg-card border border-border-subtle rounded-xl p-4">
                            <div class="flex flex-col">
                                <span class="material-symbols-outlined text-purple-600 text-[24px] mb-2">priority_high</span>
                                <p class="text-2xl font-bold text-slate-900"><?php echo $stats['high_priority_tasks']; ?></p>
                                <p class="text-xs text-text-secondary">High Priority</p>
                            </div>
                        </div>
                    </div>

                    <!-- Filters and Search -->
                    <div class="bg-card border border-border-subtle rounded-xl p-4">
                        <form method="GET" class="flex flex-col md:flex-row gap-4">
                            <!-- Search -->
                            <div class="flex-1">
                                <div class="relative">
                                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary">search</span>
                                    <input
                                        type="text"
                                        name="search"
                                        value="<?php echo htmlspecialchars($search); ?>"
                                        placeholder="Search tasks, projects..."
                                        class="w-full pl-10 pr-4 py-2 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary focus:border-primary" />
                                </div>
                            </div>

                            <!-- Status Filter -->
                            <div class="w-full md:w-48">
                                <select name="status" class="w-full px-4 py-2 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="blocked" <?php echo $status_filter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                                </select>
                            </div>

                            <!-- Priority Filter -->
                            <div class="w-full md:w-48">
                                <select name="priority" class="w-full px-4 py-2 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                    <option value="all" <?php echo $priority_filter === 'all' ? 'selected' : ''; ?>>All Priority</option>
                                    <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                            </div>

                            <!-- Filter Button -->
                            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                                Apply Filters
                            </button>

                            <?php if ($status_filter !== 'all' || $priority_filter !== 'all' || !empty($search)): ?>
                                <a href="tasks.php" class="px-6 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors font-medium text-center">
                                    Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Tasks List -->
                    <div>
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
                                    <div class="bg-card border <?php echo $is_overdue ? 'border-red-200' : 'border-border-subtle'; ?> rounded-xl p-5 hover:shadow-md transition-all duration-200">
                                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                            <!-- Left: Task Info -->
                                            <div class="flex-1 cursor-pointer" onclick="window.location.href='task_detail.php?id=<?php echo $task['id']; ?>'">
                                                <div class="flex items-start gap-3 mb-3">
                                                    <div class="flex-1">
                                                        <h3 class="font-semibold text-slate-900 text-lg mb-1"><?php echo htmlspecialchars($task['name']); ?></h3>
                                                        <?php if ($task['description']): ?>
                                                            <p class="text-sm text-text-secondary line-clamp-2"><?php echo htmlspecialchars($task['description']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <!-- Meta Info -->
                                                <div class="flex flex-wrap items-center gap-4 text-sm">
                                                    <div class="flex items-center gap-2 text-text-secondary">
                                                        <span class="material-symbols-outlined text-[18px]">work</span>
                                                        <span><?php echo htmlspecialchars($task['project_name']); ?></span>
                                                    </div>

                                                    <?php if ($task['phase_name']): ?>
                                                        <div class="flex items-center gap-2 text-text-secondary">
                                                            <span class="material-symbols-outlined text-[18px]">layers</span>
                                                            <span><?php echo htmlspecialchars($task['phase_name']); ?></span>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($task['due_date']): ?>
                                                        <div class="flex items-center gap-2 <?php echo $is_overdue ? 'text-red-600 font-semibold' : ($is_due_soon ? 'text-orange-600' : 'text-text-secondary'); ?>">
                                                            <span class="material-symbols-outlined text-[18px]">
                                                                <?php echo $is_overdue ? 'warning' : ($is_due_soon ? 'schedule' : 'calendar_today'); ?>
                                                            </span>
                                                            <span>Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Right: Status, Priority, and Actions -->
                                            <div class="flex flex-col items-end gap-3">
                                                <!-- Badges -->
                                                <div class="flex flex-wrap justify-end gap-2">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium border <?php echo $status_colors[$task['status']] ?? $status_colors['pending']; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                                    </span>

                                                    <?php if ($task['priority']): ?>
                                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium border <?php echo $priority_colors[$task['priority']] ?? $priority_colors['medium']; ?>">
                                                            <?php echo ucfirst($task['priority']); ?>
                                                        </span>
                                                    <?php endif; ?>

                                                    <?php if ($is_overdue): ?>
                                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium bg-red-100 text-red-700 border border-red-200">
                                                            <span class="material-symbols-outlined text-[16px] mr-1">warning</span>
                                                            Overdue
                                                        </span>
                                                    <?php elseif ($is_due_soon): ?>
                                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium bg-orange-100 text-orange-700 border border-orange-200">
                                                            <span class="material-symbols-outlined text-[16px] mr-1">schedule</span>
                                                            Due Soon
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Status Change Dropdown -->
                                                <div class="relative" onclick="event.stopPropagation();">
                                                    <button
                                                        onclick="toggleStatusMenu(<?php echo $task['id']; ?>)"
                                                        class="inline-flex items-center gap-2 px-3 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors text-sm font-medium">
                                                        <span class="material-symbols-outlined text-[18px]">sync_alt</span>
                                                        <span>Change Status</span>
                                                        <span class="material-symbols-outlined text-[18px]">expand_more</span>
                                                    </button>

                                                    <div id="status-menu-<?php echo $task['id']; ?>" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-border-subtle z-10">
                                                        <form method="POST" class="p-2">
                                                            <input type="hidden" name="change_status" value="1">
                                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">

                                                            <?php if ($task['status'] !== 'pending'): ?>
                                                                <button type="submit" name="new_status" value="pending" class="w-full text-left px-3 py-2 hover:bg-slate-100 rounded text-sm flex items-center gap-2">
                                                                    <span class="material-symbols-outlined text-[18px] text-slate-600">pending_actions</span>
                                                                    <span>Mark as Pending</span>
                                                                </button>
                                                            <?php endif; ?>

                                                            <?php if ($task['status'] !== 'in_progress'): ?>
                                                                <button type="submit" name="new_status" value="in_progress" class="w-full text-left px-3 py-2 hover:bg-yellow-50 rounded text-sm flex items-center gap-2">
                                                                    <span class="material-symbols-outlined text-[18px] text-yellow-600">hourglass_top</span>
                                                                    <span>Mark In Progress</span>
                                                                </button>
                                                            <?php endif; ?>

                                                            <?php if ($task['status'] !== 'completed'): ?>
                                                                <button type="submit" name="new_status" value="completed" class="w-full text-left px-3 py-2 hover:bg-green-50 rounded text-sm flex items-center gap-2">
                                                                    <span class="material-symbols-outlined text-[18px] text-green-600">check_circle</span>
                                                                    <span>Mark Completed</span>
                                                                </button>
                                                            <?php endif; ?>

                                                            <?php if ($task['status'] !== 'blocked'): ?>
                                                                <button type="submit" name="new_status" value="blocked" class="w-full text-left px-3 py-2 hover:bg-red-50 rounded text-sm flex items-center gap-2">
                                                                    <span class="material-symbols-outlined text-[18px] text-red-600">block</span>
                                                                    <span>Mark as Blocked</span>
                                                                </button>
                                                            <?php endif; ?>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="bg-card border border-border-subtle rounded-xl p-12 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-100 mb-4">
                                    <span class="material-symbols-outlined text-slate-400 text-[32px]">assignment_outline</span>
                                </div>
                                <h3 class="text-lg font-semibold text-slate-900 mb-2">No Tasks Found</h3>
                                <p class="text-text-secondary mb-4">
                                    <?php if ($status_filter !== 'all' || $priority_filter !== 'all' || !empty($search)): ?>
                                        No tasks match your current filters. Try adjusting your search criteria.
                                    <?php else: ?>
                                        You don't have any tasks assigned to you yet.
                                    <?php endif; ?>
                                </p>
                                <?php if ($status_filter !== 'all' || $priority_filter !== 'all' || !empty($search)): ?>
                                    <a href="tasks.php" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                                        <span class="material-symbols-outlined text-[18px]">clear</span>
                                        Clear Filters
                                    </a>
                                <?php endif; ?>
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

        function toggleStatusMenu(taskId) {
            const menu = document.getElementById('status-menu-' + taskId);
            const allMenus = document.querySelectorAll('[id^="status-menu-"]');

            // Close all other menus
            allMenus.forEach(m => {
                if (m.id !== 'status-menu-' + taskId) {
                    m.classList.add('hidden');
                }
            });

            // Toggle current menu
            menu.classList.toggle('hidden');
        }

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            const menus = document.querySelectorAll('[id^="status-menu-"]');
            const buttons = document.querySelectorAll('[onclick^="toggleStatusMenu"]');

            let clickedButton = false;
            buttons.forEach(button => {
                if (button.contains(event.target)) {
                    clickedButton = true;
                }
            });

            if (!clickedButton) {
                menus.forEach(menu => {
                    if (!menu.contains(event.target)) {
                        menu.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</body>

</html>