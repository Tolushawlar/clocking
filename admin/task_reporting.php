<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'];
$report_date = $_GET['date'] ?? date('Y-m-d');

// Handle task report submission
if (isset($_POST['submit_report'])) {
    $task_id = $_POST['task_id'];
    $hours_worked = $_POST['hours_worked'];
    $progress_percentage = $_POST['progress_percentage'];
    $status_flag = $_POST['status_flag'];
    $activity_notes = trim($_POST['activity_notes']);
    $blockers = trim($_POST['blockers']);
    
    // Check if report already exists for today
    $check_stmt = $db->prepare("SELECT id FROM task_reports WHERE user_id = ? AND task_id = ? AND report_date = ?");
    $check_stmt->bind_param("iis", $user_id, $task_id, $report_date);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        // Update existing report
        $stmt = $db->prepare("UPDATE task_reports SET hours_worked = ?, progress_percentage = ?, status_flag = ?, activity_notes = ?, blockers = ? WHERE id = ?");
        $stmt->bind_param("disssi", $hours_worked, $progress_percentage, $status_flag, $activity_notes, $blockers, $existing['id']);
    } else {
        // Create new report
        $stmt = $db->prepare("INSERT INTO task_reports (user_id, task_id, report_date, hours_worked, progress_percentage, status_flag, activity_notes, blockers) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisdisss", $user_id, $task_id, $report_date, $hours_worked, $progress_percentage, $status_flag, $activity_notes, $blockers);
    }
    
    if ($stmt->execute()) {
        // Update task actual hours
        $update_task = $db->prepare("UPDATE tasks SET actual_hours = actual_hours + ? WHERE id = ?");
        $update_task->bind_param("di", $hours_worked, $task_id);
        $update_task->execute();
        
        header("Location: task_reporting.php?date=$report_date&msg=Report submitted successfully");
        exit;
    }
}

// Get user's assigned tasks
$tasks_query = "
    SELECT t.*, p.name as project_name, p.client_name,
           tr.hours_worked, tr.progress_percentage, tr.status_flag, tr.activity_notes, tr.blockers
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    LEFT JOIN task_reports tr ON t.id = tr.task_id AND tr.user_id = ? AND tr.report_date = ?
    WHERE t.assigned_to = ? AND t.status IN ('pending', 'in_progress')
    ORDER BY t.priority DESC, t.due_date
";

$stmt = $db->prepare($tasks_query);
$stmt->bind_param("isi", $user_id, $report_date, $user_id);
$stmt->execute();
$assigned_tasks = $stmt->get_result();

// Get user info
$stmt = $db->prepare("SELECT firstname, lastname, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Calculate daily stats
$daily_stats_query = "
    SELECT 
        COUNT(DISTINCT tr.task_id) as tasks_reported,
        SUM(tr.hours_worked) as total_hours,
        AVG(tr.progress_percentage) as avg_progress
    FROM task_reports tr
    JOIN tasks t ON tr.task_id = t.id
    WHERE tr.user_id = ? AND tr.report_date = ?
";

$stmt = $db->prepare($daily_stats_query);
$stmt->bind_param("is", $user_id, $report_date);
$stmt->execute();
$daily_stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Task Reporting - TimeTrack Pro</title>
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
                <div class="bg-center bg-no-repeat bg-cover rounded-lg size-10 shadow-sm" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuApFaVKdjjOP6JLo0mlmZt7GcPRFHRpl_mDNOq7IHXf1m31ILlpY1wE6DC_WFm3LdEhe_5-SsQ_5IF2ZDl9BiOjArHRroMuZWOEVOp_uIQfnEr81OLOfWS0oUu8MHlhiPNVmqRcKubUH-t5_vpQ0gnaNPHu2bQz69bwreJgjKmqsijP1uFLduPvru4Q1geNI32hFt5CAmbPKUGV9_m1MDpx4ldw2Gd_iE5IpjodgDu8QvaSPE0AXo4wn23D4sKq8Q_AKBTkZBsqd1M");'></div>
                <div class="flex flex-col">
                    <h1 class="text-slate-900 dark:text-white text-base font-bold leading-tight">TimeTrack Pro</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-xs font-medium">Enterprise Edition</p>
                </div>
            </div>
            <!-- Navigation -->
            <nav class="flex flex-col gap-2">
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group" href="dashboard.php">
                    <span class="material-symbols-outlined text-slate-500 group-hover:text-primary dark:text-slate-400 dark:group-hover:text-primary">dashboard</span>
                    <span class="text-sm font-medium">Dashboard</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 text-primary dark:bg-primary/20" href="task_reporting.php">
                    <span class="material-symbols-outlined fill-1">check_box</span>
                    <span class="text-sm font-bold">Tasks</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group" href="projects.php">
                    <span class="material-symbols-outlined text-slate-500 group-hover:text-primary dark:text-slate-400 dark:group-hover:text-primary">folder</span>
                    <span class="text-sm font-medium">Projects</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group" href="daily_schedule.php">
                    <span class="material-symbols-outlined text-slate-500 group-hover:text-primary dark:text-slate-400 dark:group-hover:text-primary">calendar_month</span>
                    <span class="text-sm font-medium">Schedule</span>
                </a>
                <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group" href="reports.php">
                    <span class="material-symbols-outlined text-slate-500 group-hover:text-primary dark:text-slate-400 dark:group-hover:text-primary">bar_chart</span>
                    <span class="text-sm font-medium">Reports</span>
                </a>
            </nav>
        </div>
        <div class="p-6 border-t border-slate-200 dark:border-slate-800">
            <a class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors group" href="settings.php">
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
                <input type="date" value="<?php echo $report_date; ?>" onchange="window.location.href='task_reporting.php?date='+this.value" class="hidden sm:flex items-center justify-center h-10 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-bold transition-colors">
                <div class="flex items-center gap-3 pl-6 border-l border-slate-200 dark:border-slate-700">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo ucfirst($user['role']); ?></p>
                    </div>
                    <div class="bg-center bg-no-repeat bg-cover rounded-full size-10 border-2 border-white dark:border-slate-800 shadow-sm" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuB2j445mnB7NBbQm_kbKhXwgIrSG2iisRaNr1yfaWjoVGKbk5XyAKLwRWDVV5Jgp6FlgohvLjB6GR1Ag3dr-LRrACjFB6UOHpVnMqsJ10i8RUfRvTBRXzNvKznnu3aWk8LNnfNDWZ90wEsWyJeOIolf6PVvGvRRjKmAVBMfHhUTm6dkSa_tYvwM4qdhDG6fw70EOJbxvzVNj9Ik0I7mK3S5ehf0KJIbhApk7R-qj7CRfYGbi5Wqfj9BYe20ZtFBguL0iiGozIRD28M");'></div>
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
                        <p class="text-slate-500 dark:text-slate-400 mt-2 flex items-center gap-2">
                            <span class="material-symbols-outlined text-green-500 text-[20px]">timer</span>
                            Report your progress for <span class="font-bold text-slate-900 dark:text-white"><?php echo date('M j, Y', strtotime($report_date)); ?></span>
                        </p>
                    </div>
                    <div class="text-sm font-medium text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-800 px-4 py-2 rounded-full shadow-sm border border-slate-200 dark:border-slate-700">
                        <?php echo $daily_stats['tasks_reported'] ?: 0; ?> tasks reported today
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    <!-- Left Column: Task List -->
                    <div class="lg:col-span-4 flex flex-col gap-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Assigned Tasks</h3>
                            <span class="bg-primary/10 text-primary text-xs font-bold px-2 py-1 rounded-full">
                                <?php echo $assigned_tasks->num_rows; ?> Active
                            </span>
                        </div>
                        <div class="flex flex-col gap-3">
                            <?php while ($task = $assigned_tasks->fetch_assoc()): ?>
                            <?php 
                                $has_report = !is_null($task['hours_worked']);
                                $priority_colors = [
                                    'low' => 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300',
                                    'medium' => 'bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400',
                                    'high' => 'bg-orange-100 dark:bg-orange-900/20 text-orange-600 dark:text-orange-400',
                                    'urgent' => 'bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400'
                                ];
                            ?>
                            <div onclick="selectTask(<?php echo $task['id']; ?>, '<?php echo htmlspecialchars($task['name']); ?>', '<?php echo htmlspecialchars($task['project_name']); ?>')" class="group cursor-pointer flex flex-col gap-3 p-4 rounded-xl bg-white dark:bg-slate-800 border <?php echo $has_report ? 'border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/10' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'; ?> transition-all <?php echo !$has_report ? 'hover:shadow-md' : ''; ?>">
                                <div class="flex items-start justify-between">
                                    <div class="flex gap-3">
                                        <div class="<?php echo $priority_colors[$task['priority']]; ?> p-2 rounded-lg h-fit">
                                            <span class="material-symbols-outlined text-[20px]">
                                                <?php echo $task['priority'] === 'urgent' ? 'priority_high' : 'assignment'; ?>
                                            </span>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 dark:text-white text-sm"><?php echo htmlspecialchars($task['project_name']); ?></p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?php echo htmlspecialchars($task['name']); ?></p>
                                        </div>
                                    </div>
                                    <?php if ($has_report): ?>
                                    <span class="flex size-2 bg-green-500 rounded-full mt-2"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-xs font-semibold <?php echo $has_report ? 'text-green-700 bg-green-100 dark:bg-green-900/30 dark:text-green-400' : 'text-slate-500 bg-slate-100 dark:bg-slate-700'; ?> px-2 py-1 rounded">
                                        <?php echo $has_report ? 'Reported' : ucfirst($task['priority']) . ' Priority'; ?>
                                    </span>
                                    <?php if (!$has_report): ?>
                                    <span class="material-symbols-outlined text-primary text-[20px] opacity-0 group-hover:opacity-100 transition-opacity">arrow_forward</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Right Column: Reporting Form -->
                    <div class="lg:col-span-8 space-y-6">
                        <div id="no-task-selected" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm p-8 text-center">
                            <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="material-symbols-outlined text-slate-400 text-2xl">assignment</span>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Select a Task to Report</h3>
                            <p class="text-slate-500 dark:text-slate-400">Choose a task from the left panel to submit your daily progress report.</p>
                        </div>

                        <!-- Task Detail Card -->
                        <div id="task-reporting-form" class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden hidden">
                            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-[20px]">edit_note</span>
                                    <h3 class="font-bold text-slate-900 dark:text-white">Update Status: <span id="selected-task-name"></span></h3>
                                </div>
                                <span class="text-xs font-medium text-slate-500">Project: <span id="selected-project-name"></span></span>
                            </div>
                            <div class="p-6 md:p-8 space-y-8">
                                <form method="POST" class="space-y-6">
                                    <input type="hidden" name="submit_report" value="1">
                                    <input type="hidden" name="task_id" id="selected-task-id">
                                    
                                    <!-- Progress Slider -->
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center">
                                            <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Completion Percentage</label>
                                            <span class="text-primary font-bold text-lg" id="progress-display">0%</span>
                                        </div>
                                        <input id="progress-slider" name="progress_percentage" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-primary dark:bg-slate-700" max="100" min="0" type="range" value="0" oninput="document.getElementById('progress-display').textContent = this.value + '%'"/>
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
                                            <input name="hours_worked" class="block w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-primary focus:ring-primary sm:text-sm py-2.5 px-3 font-medium" type="number" step="0.25" min="0" max="24" placeholder="0.0"/>
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
                                        <textarea name="activity_notes" class="block w-full rounded-lg border-slate-200 dark:border-slate-700 dark:bg-slate-900 focus:border-primary focus:ring-primary sm:text-sm p-3" placeholder="Describe what you accomplished..." rows="4"></textarea>
                                    </div>

                                    <!-- Blockers Toggle Area -->
                                    <div class="bg-red-50 dark:bg-red-900/10 rounded-lg p-4 border border-red-100 dark:border-red-900/30">
                                        <div class="flex items-start gap-3">
                                            <input class="h-5 w-5 rounded border-slate-300 text-red-600 focus:ring-red-600 mt-0.5" id="has-blocker" type="checkbox" onchange="toggleBlockerField()"/>
                                            <div class="flex-1">
                                                <label class="text-sm font-medium text-slate-900 dark:text-white" for="has-blocker">I encountered blockers for this task</label>
                                                <div id="blocker-field" class="mt-3 hidden">
                                                    <textarea name="blockers" class="block w-full rounded-lg border-red-200 dark:border-red-800 bg-white dark:bg-slate-900 focus:border-red-500 focus:ring-red-500 sm:text-sm p-3" placeholder="Describe the blockers you encountered..." rows="3"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-end gap-3 pt-2">
                                        <button type="button" class="px-6 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-white font-semibold rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors text-sm">Save Draft</button>
                                        <button type="submit" class="px-6 py-2.5 bg-primary text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors text-sm shadow-sm shadow-primary/30">Submit Report</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function selectTask(taskId, taskName, projectName) {
    document.getElementById('selected-task-id').value = taskId;
    document.getElementById('selected-task-name').textContent = taskName;
    document.getElementById('selected-project-name').textContent = projectName;
    document.getElementById('no-task-selected').classList.add('hidden');
    document.getElementById('task-reporting-form').classList.remove('hidden');
}

function toggleBlockerField() {
    const checkbox = document.getElementById('has-blocker');
    const field = document.getElementById('blocker-field');
    
    if (checkbox.checked) {
        field.classList.remove('hidden');
    } else {
        field.classList.add('hidden');
    }
}
</script>
</body>
</html>