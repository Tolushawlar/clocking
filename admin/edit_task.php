<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$task_id = $_GET['id'] ?? 0;

if ($_POST) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $assigned_to = $_POST['assigned_to'] ?: null;
    $due_date = $_POST['due_date'] ?: null;

    $stmt = $db->prepare("UPDATE tasks SET name = ?, description = ?, status = ?, assigned_to = ?, due_date = ? WHERE id = ?");
    $stmt->bind_param("sssisi", $title, $description, $status, $assigned_to, $due_date, $task_id);
    $stmt->execute();

    // Get project_id to redirect back
    $stmt = $db->prepare("SELECT project_id FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $project_id = $stmt->get_result()->fetch_assoc()['project_id'];

    header("Location: project_details.php?id=$project_id");
    exit;
}

// Get task
$stmt = $db->prepare("SELECT t.*, p.name as project_name, p.team_id FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

// Get users (team members if project has team, otherwise all users)
if ($task['team_id']) {
    $users_stmt = $db->prepare("
        SELECT u.id, u.firstname, u.lastname 
        FROM users u 
        JOIN team_members tm ON u.id = tm.user_id 
        WHERE tm.team_id = ? AND u.is_active = 1
        ORDER BY u.firstname, u.lastname
    ");
    $users_stmt->bind_param("i", $task['team_id']);
} else {
    $users_stmt = $db->prepare("SELECT id, firstname, lastname FROM users WHERE business_id = ? AND is_active = 1 ORDER BY firstname, lastname");
    $users_stmt->bind_param("i", $business_id);
}
$users_stmt->execute();
$users = $users_stmt->get_result();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Edit Task - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
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

<body class="bg-background-light dark:bg-background-dark font-display text-text-main dark:text-white overflow-hidden selection:bg-primary selection:text-white">
    <div class="flex h-screen w-full overflow-hidden">
        <?php
        // Include sidebar component
        $current_page = 'edit_task.php';
        include 'sidebar.php';
        ?>

        <!-- Main Content Wrapper -->
        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <?php include 'header.php'; ?>

            <!-- Scrollable Page Content -->
            <div class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark">
                <div class="max-w-4xl mx-auto px-6 py-8">
                    <div class="mb-6">
                        <h1 class="text-3xl font-bold text-text-main dark:text-white">Edit Task</h1>
                        <p class="text-sm text-text-secondary mt-1">Update task details and assignments</p>
                    </div>

                    <form method="POST" class="bg-white dark:bg-surface-dark rounded-xl border border-slate-200 dark:border-border-dark shadow-sm p-6 space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-text-main dark:text-white mb-2">Task Title</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($task['name'] ?? $task['title'] ?? ''); ?>" required class="w-full px-4 py-2.5 border border-slate-200 dark:border-border-dark bg-white dark:bg-slate-800 text-text-main dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-text-main dark:text-white mb-2">Description</label>
                            <textarea name="description" rows="4" class="w-full px-4 py-2.5 border border-slate-200 dark:border-border-dark bg-white dark:bg-slate-800 text-text-main dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-text-main dark:text-white mb-2">Status</label>
                                <select name="status" class="w-full px-4 py-2.5 border border-slate-200 dark:border-border-dark bg-white dark:bg-slate-800 text-text-main dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer">
                                    <option value="pending" <?php echo $task['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="in_progress" <?php echo $task['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $task['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="blocked" <?php echo $task['status'] == 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-text-main dark:text-white mb-2">Due Date</label>
                                <input type="date" name="due_date" value="<?php echo $task['due_date']; ?>" class="w-full px-4 py-2.5 border border-slate-200 dark:border-border-dark bg-white dark:bg-slate-800 text-text-main dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-text-main dark:text-white mb-2">Assign To</label>
                            <select name="assigned_to" class="w-full px-4 py-2.5 border border-slate-200 dark:border-border-dark bg-white dark:bg-slate-800 text-text-main dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary cursor-pointer">
                                <option value="">Unassigned</option>
                                <?php while ($user = $users->fetch_assoc()): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $task['assigned_to'] == $user['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="flex gap-3 pt-4 border-t border-slate-200 dark:border-border-dark">
                            <button type="submit" class="flex items-center justify-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-lg transition-colors font-semibold">
                                <span class="material-symbols-outlined text-[20px]">check</span>
                                <span>Update Task</span>
                            </button>
                            <a href="project_details.php?id=<?php echo $task['project_id']; ?>" class="flex items-center justify-center gap-2 px-6 py-2.5 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition-colors font-semibold">
                                <span class="material-symbols-outlined text-[20px]">close</span>
                                <span>Cancel</span>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Update time
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', {
                hour12: true,
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>

</html>