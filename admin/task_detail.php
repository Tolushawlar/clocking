<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'];
$task_id = $_GET['id'] ?? 0;

// Get user permissions
$stmt = $db->prepare("SELECT role, can_create_projects, can_manage_team FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get task details with project and assignee info
$stmt = $db->prepare("
    SELECT t.*, p.name as project_name, u.firstname, u.lastname, u.role as assignee_role,
           COALESCE(SUM(CASE WHEN tl.end_time IS NOT NULL THEN TIMESTAMPDIFF(SECOND, tl.start_time, tl.end_time) / 3600 ELSE 0 END), 0) as hours_logged
    FROM tasks t
    LEFT JOIN projects p ON t.project_id = p.id
    LEFT JOIN users u ON t.assigned_to = u.id
    LEFT JOIN time_logs tl ON t.id = tl.task_id
    WHERE t.id = ? AND p.business_id = ?
    GROUP BY t.id
");
$stmt->bind_param("ii", $task_id, $business_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

if (!$task) {
    header('Location: projects.php');
    exit;
}

// Get task deliverables (using a simple table structure)
$deliverables = [
    ['id' => 1, 'title' => 'Draft 3 initial concepts', 'completed' => 1, 'attachment' => 'drafts_v1.fig'],
    ['id' => 2, 'title' => 'Finalize copywriting for headlines', 'completed' => 0, 'status' => 'Pending Approval'],
    ['id' => 3, 'title' => 'Export assets for dev team', 'completed' => 0, 'note' => 'SVG and Optimized PNGs required.']
];

$completed_deliverables = count(array_filter($deliverables, fn($d) => $d['completed']));
$progress = count($deliverables) > 0 ? round(($completed_deliverables / count($deliverables)) * 100) : 0;

// Get comments (simplified - you might want to create a comments table)
$comments = [
    ['id' => 1, 'user' => 'Sarah J.', 'message' => 'Hey Alex, are we sticking to the blue color palette for the hero buttons?', 'time' => '2h ago', 'avatar' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDzOH_ZMahYEpc_g5WLtsmilgyVPVZOyJle7Oqwk4abAbXJT_ohqKdOv_MAVC5wPsgifyXIAt0YclYrUv7e_BMhsoxBMirsLvTGlKfFuuzM37rexGJOL6FAehH931rrtWwbmJjRleq_7QOc2WGBBp_pFNINLWmQCwjAQB2Y9NhcsJsM1T-eCubMFUp-Cal4xHvY4sQDxwl1tvUVRRZ-NAOIprWuW62KrTPytaUm22kziM2qXaovVmaK3wS0HYQ5S81dkCcMFKv7r3k'],
    ['id' => 2, 'user' => 'Alex Rivera', 'message' => 'Yes, the primary blue #135BEC should be dominant.', 'time' => '1h ago', 'avatar' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCzH5OwBgSe8p0DhykoS2zktrjmuyuYYwhiBy19nB7wPDjtN9XvrRDT5RHd1WqxJFEOripZOouD93YKhAhsQvRVx4vf9FzsThMBTrZfIr50YhXOgbAkoiFAUd4yvUu29ZHS67A2fGzJUnWNYmhCLnjFtyqlhgyhv_K93Uhk2S14M1L9dEuCVtbBt4v6RPi-gsRQvRCGZTypufxROtSzMD1HWfKCgPT66GxAm7c5QFUtra5DiYlfrdZWVxlVwQnMJ7yLRmspepKpmPo', 'is_current_user' => true]
];

$estimated_hours = $task['estimated_hours'] ?? 8;
$progress_percentage = $estimated_hours > 0 ? min(100, ($task['hours_logged'] / $estimated_hours) * 100) : 0;
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Task Detail & Deliverables - TimeTrack Pro</title>
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
                    borderRadius: { "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px" },
                },
            },
        }
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-white font-display antialiased">
    <!-- Top Navigation -->
    <div class="w-full bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <header class="flex items-center justify-between h-16">
                <div class="flex items-center gap-8">
                    <div class="flex items-center gap-2">
                        <div class="text-primary">
                            <span class="material-symbols-outlined text-3xl">schedule</span>
                        </div>
                        <h2 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">TimeTrack Pro</h2>
                    </div>
                    <nav class="hidden md:flex items-center gap-6">
                        <a class="text-slate-600 dark:text-slate-400 hover:text-primary dark:hover:text-primary text-sm font-medium transition-colors" href="dashboard.php">Dashboard</a>
                        <a class="text-slate-600 dark:text-slate-400 hover:text-primary dark:hover:text-primary text-sm font-medium transition-colors" href="projects.php">Projects</a>
                        <a class="text-primary text-sm font-medium" href="task_details.php?project_id=<?php echo $task['project_id']; ?>">Tasks</a>
                        <a class="text-slate-600 dark:text-slate-400 hover:text-primary dark:hover:text-primary text-sm font-medium transition-colors" href="reports.php">Reports</a>
                    </nav>
                </div>
                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex relative items-center">
                        <span class="material-symbols-outlined absolute left-3 text-slate-400">search</span>
                        <input class="pl-10 pr-4 py-2 bg-slate-100 dark:bg-slate-800 border-none rounded-lg text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-primary w-64 placeholder-slate-400" placeholder="Search..." type="text"/>
                    </div>
                    <div class="size-9 rounded-full bg-cover bg-center cursor-pointer ring-2 ring-slate-100 dark:ring-slate-700" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuBSBfpdqQBQgGld3Icgsto2cnz_krZW7C4cA3fku_S3QIKlg3UPP360tqJ1Z5pvCC5bNIB8ij9qFLfFZR-DsyrHtyaXMh6EFuvoOKYTeP_bfjdb9GnAak8Rq5AN1ATMFC062CwzQhylg8k1QfRx5pH9CMoLSnR_u9WjmyqdbD8CLiWzHMGGq8wn_qsJuGBzxRRNgD-0NwHiH5o4RccYyduyA5i4WGKTPsE4soDPa74x3T2K5rJa2Jq70WS7PouvLrUbKjcVaW3e5iY');"></div>
                </div>
            </header>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumbs -->
        <nav class="flex mb-6 text-sm font-medium text-slate-500 dark:text-slate-400">
            <a class="hover:text-primary transition-colors" href="projects.php">Projects</a>
            <span class="mx-2">/</span>
            <a class="hover:text-primary transition-colors" href="project_details.php?id=<?php echo $task['project_id']; ?>"><?php echo htmlspecialchars($task['project_name']); ?></a>
            <span class="mx-2">/</span>
            <span class="text-slate-900 dark:text-white"><?php echo htmlspecialchars($task['title']); ?></span>
        </nav>

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-8">
            <div class="flex flex-col gap-2">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-black tracking-tight text-slate-900 dark:text-white"><?php echo htmlspecialchars($task['title']); ?></h1>
                    <?php
                    $status_class = '';
                    $status_text = ucfirst(str_replace('_', ' ', $task['status']));
                    switch($task['status']) {
                        case 'completed':
                            $status_class = 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300';
                            break;
                        case 'in_progress':
                            $status_class = 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
                            break;
                        default:
                            $status_class = 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
                    }
                    ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $status_class; ?>">
                        <?php echo $status_text; ?>
                    </span>
                </div>
                <p class="text-slate-500 dark:text-slate-400 text-sm">Task ID: #TSK-<?php echo str_pad($task['id'], 4, '0', STR_PAD_LEFT); ?></p>
            </div>
            <div class="flex items-center gap-3">
                <?php if ($user['can_create_projects'] || in_array($user['role'], ['admin', 'supervisor']) || $task['assigned_to'] == $user_id): ?>
                <button onclick="editTask()" class="flex items-center justify-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-[20px]">edit</span>
                    Edit Task
                </button>
                <?php if ($user['can_create_projects'] || in_array($user['role'], ['admin', 'supervisor'])): ?>
                <button onclick="deleteTask()" class="flex items-center justify-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-[20px]">delete</span>
                    Delete
                </button>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Main Task Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Description Card -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Description</h3>
                    <div class="prose prose-slate dark:prose-invert max-w-none text-slate-600 dark:text-slate-300">
                        <?php if ($task['description']): ?>
                            <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                        <?php else: ?>
                            <p>No description provided for this task.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Progress & Deliverables -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Deliverables</h3>
                        <button onclick="addDeliverable()" class="text-primary text-sm font-semibold hover:underline flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">add</span> Add Item
                        </button>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-8">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Progress</span>
                            <span class="text-sm font-bold text-primary"><?php echo $progress; ?>%</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                            <div class="bg-primary h-2.5 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                    </div>

                    <!-- Deliverables Checklist -->
                    <div class="space-y-3">
                        <?php foreach ($deliverables as $deliverable): ?>
                        <div class="group flex items-start gap-3 p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors border border-transparent hover:border-slate-200 dark:hover:border-slate-700">
                            <div class="flex items-center h-6">
                                <input <?php echo $deliverable['completed'] ? 'checked' : ''; ?> class="size-5 rounded border-slate-300 text-primary focus:ring-primary dark:border-slate-600 dark:bg-slate-800" type="checkbox" onchange="toggleDeliverable(<?php echo $deliverable['id']; ?>)"/>
                            </div>
                            <div class="flex-1">
                                <label class="text-sm font-medium <?php echo $deliverable['completed'] ? 'text-slate-400 line-through dark:text-slate-500' : 'text-slate-900 dark:text-white'; ?> block">
                                    <?php echo htmlspecialchars($deliverable['title']); ?>
                                </label>
                                <div class="flex items-center gap-2 mt-1">
                                    <?php if ($deliverable['completed']): ?>
                                        <span class="text-xs text-slate-400 dark:text-slate-600 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">Completed Today</span>
                                        <?php if (isset($deliverable['attachment'])): ?>
                                        <a class="text-xs text-primary hover:underline flex items-center gap-0.5" href="#">
                                            <span class="material-symbols-outlined text-[14px]">attachment</span> <?php echo htmlspecialchars($deliverable['attachment']); ?>
                                        </a>
                                        <?php endif; ?>
                                    <?php elseif (isset($deliverable['status'])): ?>
                                        <span class="text-xs text-orange-600 bg-orange-50 dark:bg-orange-900/20 px-2 py-0.5 rounded"><?php echo htmlspecialchars($deliverable['status']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (isset($deliverable['note'])): ?>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1"><?php echo htmlspecialchars($deliverable['note']); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!$deliverable['completed']): ?>
                            <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                                <button class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                                    <span class="material-symbols-outlined text-lg">more_horiz</span>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column: Sidebar Meta -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Task Details Widget -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 space-y-6">
                    <!-- Assignee -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">Assignee</h4>
                        <?php if ($task['assigned_to']): ?>
                        <div class="flex items-center gap-3">
                            <div class="size-10 rounded-full bg-primary/10 flex items-center justify-center text-primary text-sm font-bold">
                                <?php echo strtoupper(substr($task['firstname'], 0, 1) . substr($task['lastname'], 0, 1)); ?>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($task['firstname'] . ' ' . $task['lastname']); ?></p>
                                <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo ucfirst($task['assignee_role']); ?></p>
                            </div>
                        </div>
                        <?php else: ?>
                        <p class="text-sm text-slate-500 dark:text-slate-400 italic">Unassigned</p>
                        <?php endif; ?>
                    </div>

                    <div class="h-px bg-slate-100 dark:bg-slate-800"></div>

                    <!-- Dates -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">Dates</h4>
                        <div class="space-y-3">
                            <?php if ($task['created_at']): ?>
                            <div class="flex items-center gap-3 text-sm">
                                <div class="size-8 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400">
                                    <span class="material-symbols-outlined text-lg">calendar_today</span>
                                </div>
                                <div>
                                    <p class="text-slate-500 dark:text-slate-400 text-xs">Start Date</p>
                                    <p class="font-medium text-slate-900 dark:text-white"><?php echo date('M d, Y', strtotime($task['created_at'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($task['due_date']): ?>
                            <div class="flex items-center gap-3 text-sm">
                                <div class="size-8 rounded-lg <?php echo strtotime($task['due_date']) < time() && $task['status'] != 'completed' ? 'bg-red-50 dark:bg-red-900/20 text-red-500' : 'bg-slate-50 dark:bg-slate-800 text-slate-400'; ?> flex items-center justify-center">
                                    <span class="material-symbols-outlined text-lg">event_busy</span>
                                </div>
                                <div>
                                    <p class="text-slate-500 dark:text-slate-400 text-xs">Due Date</p>
                                    <p class="font-medium text-slate-900 dark:text-white"><?php echo date('M d, Y', strtotime($task['due_date'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="h-px bg-slate-100 dark:bg-slate-800"></div>

                    <!-- Time Tracking -->
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">Time Logged</h4>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-2xl font-bold text-slate-900 dark:text-white">
                                <?php 
                                $hours = floor($task['hours_logged']);
                                $minutes = round(($task['hours_logged'] - $hours) * 60);
                                echo $hours . 'h ' . $minutes . 'm';
                                ?>
                            </span>
                            <span class="text-xs font-medium text-slate-500">of <?php echo $estimated_hours; ?>h 00m</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                            <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo min(100, $progress_percentage); ?>%"></div>
                        </div>
                        <div class="mt-4">
                            <button onclick="toggleTimer()" class="w-full py-2 px-4 bg-primary text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors shadow-sm flex items-center justify-center gap-2">
                                <span class="material-symbols-outlined text-[18px]">play_circle</span>
                                Clock In
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Comments Feed -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 flex flex-col h-[420px]">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Comments</h3>
                        <span class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold px-2 py-0.5 rounded-full"><?php echo count($comments); ?></span>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 space-y-4">
                        <?php foreach ($comments as $comment): ?>
                        <div class="flex gap-3 <?php echo isset($comment['is_current_user']) ? 'flex-row-reverse' : ''; ?>">
                            <div class="size-8 rounded-full bg-cover bg-center flex-shrink-0" style="background-image: url('<?php echo $comment['avatar']; ?>');"></div>
                            <div class="flex-1">
                                <div class="<?php echo isset($comment['is_current_user']) ? 'bg-blue-50 dark:bg-blue-900/20 rounded-tr-none' : 'bg-slate-50 dark:bg-slate-800 rounded-tl-none'; ?> p-3 rounded-lg">
                                    <div class="flex justify-between items-baseline mb-1">
                                        <span class="text-sm font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($comment['user']); ?></span>
                                        <span class="text-xs text-slate-400"><?php echo $comment['time']; ?></span>
                                    </div>
                                    <p class="text-sm text-slate-600 dark:text-slate-300"><?php echo htmlspecialchars($comment['message']); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 rounded-b-xl">
                        <div class="relative">
                            <input id="commentInput" class="w-full pl-4 pr-10 py-2.5 text-sm bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg focus:ring-1 focus:ring-primary focus:border-primary shadow-sm" placeholder="Write a comment..." type="text"/>
                            <button onclick="addComment()" class="absolute right-2 top-1.5 p-1 text-primary hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded transition-colors">
                                <span class="material-symbols-outlined text-[20px]">send</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    function editTask() {
        window.location.href = `edit_task.php?id=<?php echo $task_id; ?>`;
    }

    function deleteTask() {
        if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
            window.location.href = `delete_task.php?id=<?php echo $task_id; ?>`;
        }
    }

    function toggleDeliverable(id) {
        // In a real implementation, this would make an AJAX call to update the deliverable status
        console.log('Toggle deliverable:', id);
    }

    function addDeliverable() {
        const title = prompt('Enter deliverable title:');
        if (title) {
            // In a real implementation, this would make an AJAX call to add the deliverable
            console.log('Add deliverable:', title);
        }
    }

    function toggleTimer() {
        // In a real implementation, this would start/stop time tracking
        const button = event.target.closest('button');
        const icon = button.querySelector('.material-symbols-outlined');
        const text = button.querySelector('span:last-child') || button.lastChild;
        
        if (icon.textContent === 'play_circle') {
            icon.textContent = 'pause_circle';
            button.innerHTML = '<span class="material-symbols-outlined text-[18px]">pause_circle</span> Clock Out';
            button.classList.remove('bg-primary', 'hover:bg-blue-700');
            button.classList.add('bg-red-600', 'hover:bg-red-700');
        } else {
            icon.textContent = 'play_circle';
            button.innerHTML = '<span class="material-symbols-outlined text-[18px]">play_circle</span> Clock In';
            button.classList.remove('bg-red-600', 'hover:bg-red-700');
            button.classList.add('bg-primary', 'hover:bg-blue-700');
        }
    }

    function addComment() {
        const input = document.getElementById('commentInput');
        const comment = input.value.trim();
        if (comment) {
            // In a real implementation, this would make an AJAX call to add the comment
            console.log('Add comment:', comment);
            input.value = '';
        }
    }

    // Allow Enter key to submit comment
    document.getElementById('commentInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            addComment();
        }
    });
    </script>
</body>
</html>