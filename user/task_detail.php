<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$business_id = $_SESSION['business_id'];
$task_id = $_GET['id'] ?? 0;

// Handle status update
if ($_POST && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];

    // Verify task belongs to user
    $verify_stmt = $db->prepare("SELECT id FROM tasks WHERE id = ? AND assigned_to = ?");
    $verify_stmt->bind_param("ii", $task_id, $user_id);
    $verify_stmt->execute();

    if ($verify_stmt->get_result()->num_rows > 0) {
        if ($new_status === 'completed') {
            $update_stmt = $db->prepare("UPDATE tasks SET status = ?, completed_at = NOW(), completed_by = ? WHERE id = ?");
            $update_stmt->bind_param("sii", $new_status, $user_id, $task_id);
        } else {
            $update_stmt = $db->prepare("UPDATE tasks SET status = ?, completed_at = NULL, completed_by = NULL WHERE id = ?");
            $update_stmt->bind_param("si", $new_status, $task_id);
        }
        $update_stmt->execute();

        header('Location: task_detail.php?id=' . $task_id . '&msg=Status updated successfully');
        exit;
    }
}

// Handle time log
if ($_POST && isset($_POST['log_time'])) {
    $hours_spent = (float)$_POST['hours_spent'];

    if ($hours_spent > 0) {
        // Update actual hours
        $update_stmt = $db->prepare("UPDATE tasks SET actual_hours = actual_hours + ? WHERE id = ? AND assigned_to = ?");
        $update_stmt->bind_param("dii", $hours_spent, $task_id, $user_id);
        $update_stmt->execute();

        header('Location: task_detail.php?id=' . $task_id . '&msg=Time logged successfully');
        exit;
    }
}

// Get task details with related information
$task_query = "
    SELECT 
        t.*,
        p.name as project_name,
        p.id as project_id,
        ph.name as phase_name,
        assigned_user.firstname as assigned_firstname,
        assigned_user.lastname as assigned_lastname,
        creator.firstname as creator_firstname,
        creator.lastname as creator_lastname,
        completer.firstname as completer_firstname,
        completer.lastname as completer_lastname,
        DATEDIFF(t.due_date, CURDATE()) as days_until_due
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN project_phases ph ON t.phase_id = ph.id
    LEFT JOIN users assigned_user ON t.assigned_to = assigned_user.id
    LEFT JOIN users creator ON t.created_by = creator.id
    LEFT JOIN users completer ON t.completed_by = completer.id
    WHERE t.id = ? AND p.business_id = ?
";

$stmt = $db->prepare($task_query);
$stmt->bind_param("ii", $task_id, $business_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    header('Location: tasks.php');
    exit;
}

// Check if user can edit this task
$can_edit = ($task['assigned_to'] == $user_id);

// Status badge colors
$status_colors = [
    'pending' => 'bg-slate-100 text-slate-700',
    'in_progress' => 'bg-yellow-100 text-yellow-700',
    'completed' => 'bg-green-100 text-green-700',
    'blocked' => 'bg-red-100 text-red-700'
];

// Priority badge colors
$priority_colors = [
    'low' => 'bg-blue-100 text-blue-700',
    'medium' => 'bg-yellow-100 text-yellow-700',
    'high' => 'bg-orange-100 text-orange-700',
    'urgent' => 'bg-red-100 text-red-700'
];

// Status icons
$status_icons = [
    'pending' => 'pending_actions',
    'in_progress' => 'hourglass_top',
    'completed' => 'check_circle',
    'blocked' => 'block'
];

// Priority icons
$priority_icons = [
    'low' => 'arrow_downward',
    'medium' => 'remove',
    'high' => 'arrow_upward',
    'urgent' => 'priority_high'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title><?php echo htmlspecialchars($task['name']); ?> - Task Details</title>
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
                    <a href="tasks.php" class="p-2 -ml-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                    <h1 class="text-lg font-bold">Task Details</h1>
                </div>
                <button onclick="toggleSidebar()" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto">
                <div class="max-w-[1200px] w-full mx-auto p-4 md:p-6 lg:p-8 flex flex-col gap-6">
                    <!-- Breadcrumbs -->
                    <nav class="flex items-center gap-2 text-sm flex-wrap">
                        <a href="projects.php" class="text-text-secondary hover:text-primary transition-colors">My Projects</a>
                        <span class="material-symbols-outlined text-text-secondary text-[16px]">chevron_right</span>
                        <a href="project_detail.php?id=<?php echo $task['project_id']; ?>" class="text-text-secondary hover:text-primary transition-colors"><?php echo htmlspecialchars($task['project_name']); ?></a>
                        <span class="material-symbols-outlined text-text-secondary text-[16px]">chevron_right</span>
                        <span class="text-slate-900 font-medium">Task Details</span>
                    </nav>

                    <?php if (isset($_GET['msg'])): ?>
                        <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">check_circle</span>
                                <span><?php echo htmlspecialchars($_GET['msg']); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Task Header -->
                    <div class="bg-card border border-border-subtle rounded-xl p-6">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-4">
                            <div class="flex-1">
                                <h1 class="text-2xl md:text-3xl font-bold text-slate-900 mb-3"><?php echo htmlspecialchars($task['name']); ?></h1>
                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium <?php echo $status_colors[$task['status']]; ?>">
                                        <span class="material-symbols-outlined text-[18px]"><?php echo $status_icons[$task['status']]; ?></span>
                                        <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium <?php echo $priority_colors[$task['priority']]; ?>">
                                        <span class="material-symbols-outlined text-[18px]"><?php echo $priority_icons[$task['priority']]; ?></span>
                                        <?php echo ucfirst($task['priority']); ?> Priority
                                    </span>
                                    <?php if ($task['due_date']): ?>
                                        <?php
                                        $due_class = 'bg-blue-100 text-blue-700';
                                        if ($task['status'] !== 'completed') {
                                            if ($task['days_until_due'] < 0) {
                                                $due_class = 'bg-red-100 text-red-700';
                                            } elseif ($task['days_until_due'] <= 3) {
                                                $due_class = 'bg-orange-100 text-orange-700';
                                            }
                                        }
                                        ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium <?php echo $due_class; ?>">
                                            <span class="material-symbols-outlined text-[18px]">event</span>
                                            <?php if ($task['status'] !== 'completed' && $task['days_until_due'] < 0): ?>
                                                Overdue by <?php echo abs($task['days_until_due']); ?> days
                                            <?php elseif ($task['status'] !== 'completed' && $task['days_until_due'] == 0): ?>
                                                Due Today
                                            <?php elseif ($task['status'] !== 'completed' && $task['days_until_due'] <= 3): ?>
                                                Due in <?php echo $task['days_until_due']; ?> days
                                            <?php else: ?>
                                                Due <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($can_edit): ?>
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <button onclick="toggleStatusModal()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                        Change Status
                                    </button>
                                    <button onclick="toggleTimeModal()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors font-medium flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined text-[20px]">schedule</span>
                                        Log Time
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Main Content Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left Column - Details -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Description -->
                            <div class="bg-card border border-border-subtle rounded-xl p-6">
                                <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">description</span>
                                    Description
                                </h2>
                                <?php if (!empty($task['description'])): ?>
                                    <p class="text-text-secondary leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($task['description']); ?></p>
                                <?php else: ?>
                                    <p class="text-text-secondary italic">No description provided</p>
                                <?php endif; ?>
                            </div>

                            <!-- Time Tracking -->
                            <div class="bg-card border border-border-subtle rounded-xl p-6">
                                <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">schedule</span>
                                    Time Tracking
                                </h2>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="p-4 bg-blue-50 rounded-lg">
                                        <p class="text-sm text-blue-600 font-medium mb-1">Estimated Hours</p>
                                        <p class="text-2xl font-bold text-blue-900">
                                            <?php echo $task['estimated_hours'] ? number_format($task['estimated_hours'], 1) : '—'; ?>
                                        </p>
                                    </div>
                                    <div class="p-4 bg-green-50 rounded-lg">
                                        <p class="text-sm text-green-600 font-medium mb-1">Actual Hours</p>
                                        <p class="text-2xl font-bold text-green-900">
                                            <?php echo number_format($task['actual_hours'], 1); ?>
                                        </p>
                                    </div>
                                </div>
                                <?php if ($task['estimated_hours'] && $task['estimated_hours'] > 0): ?>
                                    <div class="mt-4">
                                        <div class="flex justify-between text-sm mb-2">
                                            <span class="text-text-secondary">Progress</span>
                                            <span class="font-medium text-slate-900">
                                                <?php echo min(100, round(($task['actual_hours'] / $task['estimated_hours']) * 100)); ?>%
                                            </span>
                                        </div>
                                        <div class="w-full bg-slate-200 rounded-full h-2">
                                            <div class="bg-primary h-2 rounded-full transition-all" style="width: <?php echo min(100, ($task['actual_hours'] / $task['estimated_hours']) * 100); ?>%"></div>
                                        </div>
                                        <?php if ($task['actual_hours'] > $task['estimated_hours']): ?>
                                            <p class="text-sm text-orange-600 mt-2 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[16px]">warning</span>
                                                Over estimated by <?php echo number_format($task['actual_hours'] - $task['estimated_hours'], 1); ?> hours
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Activity Timeline -->
                            <div class="bg-card border border-border-subtle rounded-xl p-6">
                                <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">history</span>
                                    Activity Timeline
                                </h2>
                                <div class="space-y-4">
                                    <?php if ($task['completed_at']): ?>
                                        <div class="flex gap-3">
                                            <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                <span class="material-symbols-outlined text-green-600 text-[18px]">check_circle</span>
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-slate-900">Task Completed</p>
                                                <p class="text-xs text-text-secondary">
                                                    <?php if ($task['completer_firstname']): ?>
                                                        by <?php echo htmlspecialchars($task['completer_firstname'] . ' ' . $task['completer_lastname']); ?> •
                                                    <?php endif; ?>
                                                    <?php echo date('M d, Y g:i A', strtotime($task['completed_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="flex gap-3">
                                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <span class="material-symbols-outlined text-blue-600 text-[18px]">add_circle</span>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-slate-900">Task Created</p>
                                            <p class="text-xs text-text-secondary">
                                                <?php if ($task['creator_firstname']): ?>
                                                    by <?php echo htmlspecialchars($task['creator_firstname'] . ' ' . $task['creator_lastname']); ?> •
                                                <?php endif; ?>
                                                <?php echo date('M d, Y g:i A', strtotime($task['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Metadata -->
                        <div class="space-y-6">
                            <!-- Project Info -->
                            <div class="bg-card border border-border-subtle rounded-xl p-6">
                                <h2 class="text-lg font-bold text-slate-900 mb-4">Details</h2>
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-sm text-text-secondary mb-1">Project</p>
                                        <a href="project_detail.php?id=<?php echo $task['project_id']; ?>" class="text-sm font-medium text-primary hover:text-primary-dark flex items-center gap-1">
                                            <?php echo htmlspecialchars($task['project_name']); ?>
                                            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                        </a>
                                    </div>

                                    <?php if ($task['phase_name']): ?>
                                        <div>
                                            <p class="text-sm text-text-secondary mb-1">Phase</p>
                                            <p class="text-sm font-medium text-slate-900"><?php echo htmlspecialchars($task['phase_name']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($task['assigned_firstname']): ?>
                                        <div>
                                            <p class="text-sm text-text-secondary mb-1">Assigned To</p>
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white font-medium text-sm">
                                                    <?php echo strtoupper(substr($task['assigned_firstname'], 0, 1) . substr($task['assigned_lastname'], 0, 1)); ?>
                                                </div>
                                                <p class="text-sm font-medium text-slate-900">
                                                    <?php echo htmlspecialchars($task['assigned_firstname'] . ' ' . $task['assigned_lastname']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($task['due_date']): ?>
                                        <div>
                                            <p class="text-sm text-text-secondary mb-1">Due Date</p>
                                            <p class="text-sm font-medium text-slate-900"><?php echo date('F d, Y', strtotime($task['due_date'])); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <p class="text-sm text-text-secondary mb-1">Created</p>
                                        <p class="text-sm font-medium text-slate-900"><?php echo date('M d, Y', strtotime($task['created_at'])); ?></p>
                                    </div>

                                    <div>
                                        <p class="text-sm text-text-secondary mb-1">Last Updated</p>
                                        <p class="text-sm font-medium text-slate-900"><?php echo date('M d, Y', strtotime($task['updated_at'])); ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="bg-card border border-border-subtle rounded-xl p-6">
                                <h2 class="text-lg font-bold text-slate-900 mb-4">Quick Actions</h2>
                                <div class="space-y-2">
                                    <a href="tasks.php" class="flex items-center gap-2 p-3 rounded-lg hover:bg-slate-50 transition-colors text-text-secondary hover:text-primary">
                                        <span class="material-symbols-outlined text-[20px]">list</span>
                                        <span class="text-sm font-medium">View All Tasks</span>
                                    </a>
                                    <a href="project_detail.php?id=<?php echo $task['project_id']; ?>" class="flex items-center gap-2 p-3 rounded-lg hover:bg-slate-50 transition-colors text-text-secondary hover:text-primary">
                                        <span class="material-symbols-outlined text-[20px]">folder</span>
                                        <span class="text-sm font-medium">View Project</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-slate-900">Change Task Status</h3>
                <button onclick="toggleStatusModal()" class="p-1 text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="update_status" value="1">
                <div class="space-y-3 mb-6">
                    <label class="flex items-center gap-3 p-3 border-2 border-border-subtle rounded-lg cursor-pointer hover:border-primary transition-colors">
                        <input type="radio" name="status" value="pending" class="text-primary focus:ring-primary" <?php echo $task['status'] === 'pending' ? 'checked' : ''; ?>>
                        <span class="material-symbols-outlined text-slate-600">pending_actions</span>
                        <span class="font-medium">Pending</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border-2 border-border-subtle rounded-lg cursor-pointer hover:border-primary transition-colors">
                        <input type="radio" name="status" value="in_progress" class="text-primary focus:ring-primary" <?php echo $task['status'] === 'in_progress' ? 'checked' : ''; ?>>
                        <span class="material-symbols-outlined text-yellow-600">hourglass_top</span>
                        <span class="font-medium">In Progress</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border-2 border-border-subtle rounded-lg cursor-pointer hover:border-primary transition-colors">
                        <input type="radio" name="status" value="completed" class="text-primary focus:ring-primary" <?php echo $task['status'] === 'completed' ? 'checked' : ''; ?>>
                        <span class="material-symbols-outlined text-green-600">check_circle</span>
                        <span class="font-medium">Completed</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border-2 border-border-subtle rounded-lg cursor-pointer hover:border-primary transition-colors">
                        <input type="radio" name="status" value="blocked" class="text-primary focus:ring-primary" <?php echo $task['status'] === 'blocked' ? 'checked' : ''; ?>>
                        <span class="material-symbols-outlined text-red-600">block</span>
                        <span class="font-medium">Blocked</span>
                    </label>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        Update Status
                    </button>
                    <button type="button" onclick="toggleStatusModal()" class="flex-1 px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors font-medium">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Time Log Modal -->
    <div id="timeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-slate-900">Log Time Spent</h3>
                <button onclick="toggleTimeModal()" class="p-1 text-slate-400 hover:text-slate-600">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="log_time" value="1">
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        Hours Spent
                    </label>
                    <input
                        type="number"
                        name="hours_spent"
                        step="0.5"
                        min="0.5"
                        max="24"
                        required
                        class="w-full px-4 py-2.5 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                        placeholder="e.g., 2.5" />
                    <p class="text-xs text-text-secondary mt-1">Enter the number of hours you spent on this task</p>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        Log Time
                    </button>
                    <button type="button" onclick="toggleTimeModal()" class="flex-1 px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors font-medium">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
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

        function toggleStatusModal() {
            const modal = document.getElementById('statusModal');
            modal.classList.toggle('hidden');
        }

        function toggleTimeModal() {
            const modal = document.getElementById('timeModal');
            modal.classList.toggle('hidden');
        }

        // Close modals when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                toggleStatusModal();
            }
        });

        document.getElementById('timeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                toggleTimeModal();
            }
        });

        // Close modals on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('statusModal').classList.add('hidden');
                document.getElementById('timeModal').classList.add('hidden');
            }
        });
    </script>
</body>

</html>