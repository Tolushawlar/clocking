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

$today = date('Y-m-d');

// Check if user is currently clocked in
$active_session_query = "SELECT * FROM time_logs WHERE user_id = ? AND end_time IS NULL ORDER BY start_time DESC LIMIT 1";
$stmt = $db->prepare($active_session_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_session = $stmt->get_result()->fetch_assoc();

// Get current session duration if active
$session_duration = '';
if ($active_session) {
    $start_time = new DateTime($active_session['start_time']);
    $now = new DateTime();
    $diff = $now->diff($start_time);
    $session_duration = sprintf('%dh %dm', 
        $diff->h + ($diff->days * 24), 
        $diff->i
    );
}

// Get user's tasks for today
$tasks_query = "
    SELECT t.*, p.name as project_name,
           COALESCE(SUM(CASE WHEN tl.end_time IS NOT NULL THEN TIMESTAMPDIFF(SECOND, tl.start_time, tl.end_time) / 3600 ELSE 0 END), 0) as hours_logged
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN time_logs tl ON t.id = tl.task_id AND DATE(tl.start_time) = ?
    WHERE t.assigned_to = ? AND (t.due_date = ? OR t.status = 'in_progress')
    GROUP BY t.id
    ORDER BY 
        CASE WHEN t.status = 'in_progress' THEN 1 ELSE 2 END,
        t.priority DESC, t.due_date ASC
";

$stmt = $db->prepare($tasks_query);
$stmt->bind_param("sis", $today, $user_id, $today);
$stmt->execute();
$tasks = $stmt->get_result();

// Handle form submission
if ($_POST) {
    if (isset($_POST['submit_report'])) {
        $task_id = $_POST['task_id'];
        $progress = $_POST['progress'];
        $hours = $_POST['hours'];
        $minutes = $_POST['minutes'];
        $status_flag = $_POST['status_flag'];
        $activity_notes = $_POST['activity_notes'];
        $has_blocker = isset($_POST['blocker']) ? 1 : 0;
        $daily_recap = $_POST['daily_recap'];
        
        // Update task progress
        $stmt = $db->prepare("UPDATE tasks SET progress = ?, notes = ? WHERE id = ? AND assigned_to = ?");
        $stmt->bind_param("isii", $progress, $activity_notes, $task_id, $user_id);
        $stmt->execute();
        
        // Insert daily report
        $stmt = $db->prepare("INSERT INTO daily_reports (user_id, task_id, date, hours_worked, minutes_worked, status_flag, activity_notes, has_blocker, daily_recap) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE hours_worked = VALUES(hours_worked), minutes_worked = VALUES(minutes_worked), status_flag = VALUES(status_flag), activity_notes = VALUES(activity_notes), has_blocker = VALUES(has_blocker), daily_recap = VALUES(daily_recap)");
        $stmt->bind_param("iisisssis", $user_id, $task_id, $today, $hours, $minutes, $status_flag, $activity_notes, $has_blocker, $daily_recap);
        $stmt->execute();
        
        // Clock out if requested
        if ($active_session) {
            $stmt = $db->prepare("UPDATE time_logs SET end_time = NOW() WHERE id = ?");
            $stmt->bind_param("i", $active_session['id']);
            $stmt->execute();
        }
        
        header('Location: index.php?msg=Report submitted successfully');
        exit;
    }
}

// Get active task (first in progress task)
$active_task = null;
$tasks->data_seek(0);
while ($task = $tasks->fetch_assoc()) {
    if ($task['status'] == 'in_progress') {
        $active_task = $task;
        break;
    }
}

// If no active task, get first task
if (!$active_task) {
    $tasks->data_seek(0);
    $active_task = $tasks->fetch_assoc();
}

$active_tasks_count = 0;
$tasks->data_seek(0);
while ($task = $tasks->fetch_assoc()) {
    if ($task['status'] != 'completed') $active_tasks_count++;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Task Reporting - TimeTrack Pro</title>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
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
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-white overflow-hidden">
<div class="flex h-screen w-full">
    <!-- Sidebar -->
    <aside class="w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between shrink-0 h-full overflow-y-auto hidden md:flex">
        <div class="flex flex-col p-6 gap-8">
            <!-- Brand -->
            <div class="flex gap-3 items-center">
                <div class="bg-primary/10 rounded-lg size-10 shadow-sm flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary">schedule</span>
                </div>
                <div class="flex flex-col">
                    <h1 class="text-slate-900 dark:text-white text-base font-bold leading-tight">TimeTrack Pro</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-xs font-medium">Enterprise Edition</p>
                </div>
            </div>
            <!-- Navigation -->
            <nav class="flex flex-col gap-2">
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group" href="index.php">
                    <span class="material-symbols-outlined text-slate-500 group-hover:text-primary dark:text-slate-400 dark:group-hover:text-primary">dashboard</span>
                    <span class="text-sm font-medium">Dashboard</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary dark:bg-primary/20" href="task_reporting.php">
                    <span class="material-symbols-outlined fill-1">check_box</span>
                    <span class="text-sm font-bold">Tasks</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group" href="admin/projects.php">
                    <span class="material-symbols-outlined text-slate-500 group-hover:text-primary dark:text-slate-400 dark:group-hover:text-primary">folder</span>
                    <span class="text-sm font-medium">Projects</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group" href="admin/reports.php">
                    <span class="material-symbols-outlined text-slate-500 group-hover:text-primary dark:text-slate-400 dark:group-hover:text-primary">bar_chart</span>
                    <span class="text-sm font-medium">Reports</span>
                </a>
            </nav>
        </div>
        <div class="p-6 border-t border-slate-200 dark:border-slate-800">
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group" href="admin/settings.php">
                <span class="material-symbols-outlined text-slate-500 group-hover:text-primary dark:text-slate-400 dark:group-hover:text-primary">settings</span>
                <span class="text-sm font-medium">Settings</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="flex flex-col flex-1 min-w-0 h-full bg-background-light dark:bg-background-dark">
        <!-- Header -->
        <header class="flex items-center justify-between px-6 py-4 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 shrink-0">
            <div class="flex items-center gap-4">
                <button class="md:hidden text-slate-500 hover:text-slate-700">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <h2 class="text-slate-900 dark:text-white text-lg font-bold">Task Reporting</h2>
            </div>
            <div class="flex items-center gap-6">
                <?php if ($active_session): ?>
                <button onclick="clockOut()" class="hidden sm:flex items-center justify-center h-10 px-4 rounded-lg border border-red-200 bg-red-50 text-red-600 text-sm font-bold hover:bg-red-100 transition-colors">
                    <span class="material-symbols-outlined mr-2 text-[20px]">logout</span>
                    Clock Out
                </button>
                <?php endif; ?>
                <div class="flex items-center gap-3 pl-6 border-l border-slate-200 dark:border-slate-700">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo ucfirst($user['role']); ?></p>
                    </div>
                    <div class="bg-primary/10 rounded-full size-10 border-2 border-white dark:border-slate-800 shadow-sm flex items-center justify-center text-primary font-bold">
                        <?php echo strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1)); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Scrollable Area -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="max-w-[1200px] mx-auto flex flex-col gap-8">
                <!-- Welcome Section -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4 pb-4 border-b border-slate-200 dark:border-slate-700">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
                            Good <?php echo date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening'); ?>, <?php echo htmlspecialchars($user['firstname']); ?>
                        </h1>
                        <?php if ($active_session): ?>
                        <p class="text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-green-500 text-[20px]">timer</span>
                            You have been clocked in for <span class="font-bold text-slate-900 dark:text-white"><?php echo $session_duration; ?></span>
                        </p>
                        <?php else: ?>
                        <p class="text-slate-500 dark:text-slate-400 mt-2">You are currently clocked out</p>
                        <?php endif; ?>
                    </div>
                    <div class="text-sm font-medium text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-800 px-4 py-2 rounded-full shadow-sm border border-slate-200 dark:border-slate-700">
                        Today: <span class="text-slate-900 dark:text-white font-bold"><?php echo date('M d, Y'); ?></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    <!-- Left Column: Task List -->
                    <div class="lg:col-span-4 flex flex-col gap-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Assigned Tasks</h3>
                            <span class="bg-primary/10 text-primary text-xs font-bold px-2 py-1 rounded-full"><?php echo $active_tasks_count; ?> Active</span>
                        </div>
                        <div class="flex flex-col gap-3">
                            <?php 
                            $tasks->data_seek(0);
                            while ($task = $tasks->fetch_assoc()): 
                                $is_active = $active_task && $task['id'] == $active_task['id'];
                            ?>
                            <div onclick="selectTask(<?php echo $task['id']; ?>)" class="group cursor-pointer flex flex-col gap-3 p-4 rounded-xl <?php echo $is_active ? 'bg-white dark:bg-slate-800 border-2 border-primary shadow-sm relative overflow-hidden' : ($task['status'] == 'completed' ? 'bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 opacity-75 hover:opacity-100' : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'); ?> transition-all">
                                <?php if ($is_active): ?>
                                <div class="absolute top-0 left-0 w-1 h-full bg-primary"></div>
                                <?php endif; ?>
                                <div class="flex items-start justify-between">
                                    <div class="flex gap-3">
                                        <div class="<?php echo $is_active ? 'bg-primary/10 text-primary' : ($task['status'] == 'completed' ? 'bg-green-100 text-green-600' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300'); ?> p-2 rounded-lg h-fit">
                                            <span class="material-symbols-outlined text-[20px]">
                                                <?php echo $task['status'] == 'completed' ? 'check' : ($task['project_name'] ? 'design_services' : 'task'); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white text-sm <?php echo $task['status'] == 'completed' ? 'line-through text-slate-700 dark:text-slate-300' : ''; ?>">
                                                <?php echo htmlspecialchars($task['title']); ?>
                                            </p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                                                <?php echo $task['project_name'] ? htmlspecialchars($task['project_name']) : 'Personal Task'; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php if ($is_active): ?>
                                    <span class="flex size-2 bg-green-500 rounded-full mt-2"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center justify-between mt-1">
                                    <?php
                                    $status_class = '';
                                    $status_text = ucfirst(str_replace('_', ' ', $task['status']));
                                    switch($task['status']) {
                                        case 'completed':
                                            $status_class = 'text-green-700 bg-green-100';
                                            break;
                                        case 'in_progress':
                                            $status_class = 'text-primary bg-primary/5';
                                            break;
                                        default:
                                            $status_class = 'text-slate-500 bg-slate-100 dark:bg-slate-700';
                                    }
                                    ?>
                                    <span class="text-xs font-semibold <?php echo $status_class; ?> px-2 py-1 rounded"><?php echo $status_text; ?></span>
                                    <?php if ($is_active): ?>
                                    <span class="material-symbols-outlined text-primary text-[20px]">arrow_forward</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Right Column: Reporting Form -->
                    <?php if ($active_task): ?>
                    <div class="lg:col-span-8 space-y-6">
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="task_id" value="<?php echo $active_task['id']; ?>">
                            
                            <!-- Task Detail Card -->
                            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
                                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary text-[20px]">edit_note</span>
                                        <h3 class="font-bold text-slate-900 dark:text-white">Update Status: <?php echo htmlspecialchars($active_task['title']); ?></h3>
                                    </div>
                                    <span class="text-xs font-medium text-slate-500">ID: #Tk-<?php echo str_pad($active_task['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div class="p-6 md:p-8 space-y-8">
                                    <!-- Progress Slider -->
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center">
                                            <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Completion Percentage</label>
                                            <span class="text-primary font-bold text-lg" id="progressValue"><?php echo $active_task['progress'] ?? 0; ?>%</span>
                                        </div>
                                        <input name="progress" id="progressSlider" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-primary dark:bg-slate-700" max="100" min="0" type="range" value="<?php echo $active_task['progress'] ?? 0; ?>"/>
                                        <div class="flex justify-between text-xs text-slate-400">
                                            <span>Started</span>
                                            <span>Halfway</span>
                                            <span>Done</span>
                                        </div>
                                    </div>

                                    <!-- Time & Status Grid -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
                                                <span class="material-symbols-outlined text-[18px] text-slate-400">schedule</span>
                                                Time Spent Today
                                            </label>
                                            <div class="flex gap-2">
                                                <div class="relative w-full">
                                                    <input name="hours" class="block w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-primary focus:ring-primary sm:text-sm py-2.5 px-3 font-medium" type="number" value="<?php echo floor($active_task['hours_logged']); ?>" min="0" max="24"/>
                                                    <span class="absolute right-3 top-2.5 text-slate-400 text-sm">hrs</span>
                                                </div>
                                                <div class="relative w-full">
                                                    <input name="minutes" class="block w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-primary focus:ring-primary sm:text-sm py-2.5 px-3 font-medium" type="number" value="<?php echo round(($active_task['hours_logged'] - floor($active_task['hours_logged'])) * 60); ?>" min="0" max="59"/>
                                                    <span class="absolute right-3 top-2.5 text-slate-400 text-sm">mins</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex items-center gap-2">
                                                <span class="material-symbols-outlined text-[18px] text-slate-400">flag</span>
                                                Status Flag
                                            </label>
                                            <select name="status_flag" class="block w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-primary focus:ring-primary sm:text-sm py-2.5 px-3 font-medium text-slate-700 dark:text-white">
                                                <option value="on_track">On Track</option>
                                                <option value="at_risk">At Risk</option>
                                                <option value="blocked">Blocked</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    <div class="space-y-2">
                                        <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Activity Notes</label>
                                        <textarea name="activity_notes" class="block w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-primary focus:ring-primary sm:text-sm p-3" placeholder="Describe what you accomplished..." rows="4"><?php echo htmlspecialchars($active_task['notes'] ?? ''); ?></textarea>
                                    </div>

                                    <!-- Blockers Toggle Area -->
                                    <div class="bg-red-50 dark:bg-red-900/10 rounded-lg p-4 border border-red-100 dark:border-red-900/30">
                                        <div class="flex items-center gap-3">
                                            <input name="blocker" class="h-5 w-5 rounded border-slate-300 text-red-600 focus:ring-red-600" id="blocker" type="checkbox"/>
                                            <label class="text-sm font-medium text-slate-900 dark:text-white" for="blocker">I encountered a blocker for this task</label>
                                        </div>
                                    </div>

                                    <div class="flex justify-end pt-2">
                                        <button type="button" onclick="saveDraft()" class="px-6 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-white font-semibold rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors text-sm">Save Draft</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Daily Summary Card -->
                            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-6 md:p-8 space-y-4">
                                <h3 class="font-bold text-slate-900 dark:text-white flex items-center gap-2 text-lg">
                                    <span class="material-symbols-outlined text-primary">summarize</span>
                                    Daily Recap
                                </h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Briefly summarize your day, any general challenges, or plans for tomorrow.</p>
                                <textarea name="daily_recap" class="block w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-primary focus:ring-primary sm:text-sm p-3" placeholder="Today was productive, but..." rows="3"></textarea>
                                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row gap-4 items-center justify-between">
                                    <div class="text-xs text-slate-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[16px]">info</span>
                                        Auto-saved as you type
                                    </div>
                                    <button name="submit_report" type="submit" class="w-full sm:w-auto px-8 py-3 bg-primary text-white font-bold rounded-lg shadow-lg shadow-primary/30 hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                                        <span class="material-symbols-outlined">send</span>
                                        Submit Report<?php echo $active_session ? ' & Clock Out' : ''; ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="lg:col-span-8 flex items-center justify-center">
                        <div class="text-center">
                            <div class="size-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4">
                                <span class="material-symbols-outlined text-slate-400 text-2xl">task</span>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">No Tasks Available</h3>
                            <p class="text-slate-500 dark:text-slate-400">You don't have any tasks assigned for today.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="h-20 md:h-0"></div>
        </main>
    </div>
</div>

<script>
// Progress slider update
const progressSlider = document.getElementById('progressSlider');
const progressValue = document.getElementById('progressValue');

if (progressSlider && progressValue) {
    progressSlider.addEventListener('input', function() {
        progressValue.textContent = this.value + '%';
    });
}

function selectTask(taskId) {
    window.location.href = `task_reporting.php?task=${taskId}`;
}

function saveDraft() {
    // Implement auto-save functionality
    alert('Draft saved!');
}

function clockOut() {
    if (confirm('Are you sure you want to clock out?')) {
        window.location.href = 'toggle_clock.php';
    }
}
</script>

</body>
</html>