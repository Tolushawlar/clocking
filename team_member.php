<?php
require_once 'lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    $_SESSION['business_id'] = 1;
    $_SESSION['user_id'] = 3; // Team member ID
    $_SESSION['firstname'] = 'Team';
    $_SESSION['lastname'] = 'Member';
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'];

// Handle task completion
if (isset($_POST['complete_task'])) {
    $task_id = $_POST['task_id'];
    
    $stmt = $db->prepare("UPDATE tasks SET status = 'completed', completed_by = ?, completed_at = NOW() WHERE id = ? AND assigned_to = ?");
    $stmt->bind_param("iii", $user_id, $task_id, $user_id);
    
    if ($stmt->execute()) {
        header('Location: team_member.php?msg=Task marked as completed');
        exit;
    }
}

// Get assigned tasks
$stmt = $db->prepare("
    SELECT t.*, p.name as project_name, p.description as project_description,
           u.firstname as creator_firstname, u.lastname as creator_lastname,
           DATEDIFF(t.deadline, CURDATE()) as days_remaining
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.created_by = u.id
    WHERE t.assigned_to = ?
    ORDER BY 
        CASE t.status 
            WHEN 'pending' THEN 1 
            WHEN 'in_progress' THEN 2 
            ELSE 3 
        END,
        t.deadline ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result();

// Get task statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_tasks,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_tasks,
        COUNT(CASE WHEN deadline < CURDATE() AND status != 'completed' THEN 1 END) as overdue_tasks
    FROM tasks 
    WHERE assigned_to = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>My Tasks - TimeTrack Pro</title>
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
                },
            },
        }
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-white">
<div class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="flex items-center justify-between px-6 py-3 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-4">
            <div class="size-8 text-primary flex items-center justify-center rounded-lg bg-primary/10">
                <span class="material-symbols-outlined text-2xl">schedule</span>
            </div>
            <h2 class="text-lg font-bold">TimeTrack Pro - My Tasks</h2>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-slate-500">Welcome, <?php echo htmlspecialchars($_SESSION['firstname']); ?></span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 p-6">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-blue-600">task</span>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $stats['total_tasks']; ?></p>
                            <p class="text-sm text-slate-500">Total Tasks</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-green-600">check_circle</span>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $stats['completed_tasks']; ?></p>
                            <p class="text-sm text-slate-500">Completed</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-yellow-600">pending</span>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $stats['pending_tasks']; ?></p>
                            <p class="text-sm text-slate-500">Pending</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-red-600">warning</span>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $stats['overdue_tasks']; ?></p>
                            <p class="text-sm text-slate-500">Overdue</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tasks List -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">My Assigned Tasks</h2>
                </div>
                
                <div class="divide-y divide-slate-200 dark:divide-slate-700">
                    <?php while ($task = $tasks->fetch_assoc()): 
                        $is_overdue = $task['days_remaining'] < 0 && $task['status'] != 'completed';
                        $is_due_soon = $task['days_remaining'] <= 2 && $task['days_remaining'] >= 0 && $task['status'] != 'completed';
                    ?>
                    <div class="p-6 <?php echo $task['status'] == 'completed' ? 'opacity-60' : ''; ?>">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white <?php echo $task['status'] == 'completed' ? 'line-through' : ''; ?>">
                                        <?php echo htmlspecialchars($task['name']); ?>
                                    </h3>
                                    
                                    <?php if ($task['status'] == 'completed'): ?>
                                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Completed</span>
                                    <?php elseif ($is_overdue): ?>
                                        <span class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">Overdue</span>
                                    <?php elseif ($is_due_soon): ?>
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">Due Soon</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Pending</span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="text-sm text-slate-600 dark:text-slate-400 mb-2"><?php echo htmlspecialchars($task['description']); ?></p>
                                
                                <div class="flex items-center gap-4 text-sm text-slate-500">
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">folder</span>
                                        <?php echo htmlspecialchars($task['project_name']); ?>
                                    </span>
                                    
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">calendar_today</span>
                                        Due: <?php echo date('M j, Y', strtotime($task['deadline'])); ?>
                                    </span>
                                    
                                    <?php if ($task['days_remaining'] >= 0): ?>
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[16px]">schedule</span>
                                            <?php echo $task['days_remaining']; ?> days left
                                        </span>
                                    <?php else: ?>
                                        <span class="flex items-center gap-1 text-red-600">
                                            <span class="material-symbols-outlined text-[16px]">warning</span>
                                            <?php echo abs($task['days_remaining']); ?> days overdue
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($task['completed_at']): ?>
                                        <span class="flex items-center gap-1 text-green-600">
                                            <span class="material-symbols-outlined text-[16px]">check_circle</span>
                                            Completed <?php echo date('M j', strtotime($task['completed_at'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="ml-4">
                                <?php if ($task['status'] != 'completed'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                        <button name="complete_task" type="submit" onclick="return confirm('Mark this task as completed?')" 
                                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                                            <span class="material-symbols-outlined text-[18px]">check</span>
                                            Mark Complete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="flex items-center gap-2 text-green-600">
                                        <span class="material-symbols-outlined">check_circle</span>
                                        <span class="text-sm font-medium">Completed</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    
                    <?php if ($tasks->num_rows == 0): ?>
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="material-symbols-outlined text-slate-400 text-2xl">task</span>
                        </div>
                        <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">No tasks assigned</h3>
                        <p class="text-slate-500">You don't have any tasks assigned to you yet.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg">
    <?php echo htmlspecialchars($_GET['msg']); ?>
</div>
<script>
setTimeout(() => {
    const msg = document.querySelector('.fixed.bottom-4');
    if (msg) msg.remove();
}, 3000);
</script>
<?php endif; ?>
</body>
</html>