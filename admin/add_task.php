<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$project_id = $_GET['project_id'] ?? 0;

if ($_POST) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $assigned_to = $_POST['assigned_to'] ?: null;
    $due_date = $_POST['due_date'] ?: null;

    $stmt = $db->prepare("INSERT INTO tasks (project_id, title, description, assigned_to, due_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issisi", $project_id, $title, $description, $assigned_to, $due_date, $business_id);
    $stmt->execute();

    header("Location: project_details.php?id=$project_id");
    exit;
}

// Get project
$stmt = $db->prepare("SELECT p.name, p.team_id FROM projects p WHERE p.id = ? AND p.business_id = ?");
$stmt->bind_param("ii", $project_id, $business_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

// Get users for assignment (team members if project has team, otherwise all users)
if ($project['team_id']) {
    $users_stmt = $db->prepare("
        SELECT u.id, u.firstname, u.lastname 
        FROM users u 
        JOIN team_members tm ON u.id = tm.user_id 
        WHERE tm.team_id = ? AND u.is_active = 1
        ORDER BY u.firstname, u.lastname
    ");
    $users_stmt->bind_param("i", $project['team_id']);
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
    <title>Add Task - TimeTrack Pro</title>
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
        $current_page = 'add_task.php';
        include 'sidebar.php';
        ?>

        <!-- Main Content Wrapper -->
        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <?php include 'header.php'; ?>

            <!-- Scrollable Page Content -->
            <div class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark">
                <div class="max-w-4xl mx-auto px-6 py-8">
                    <!-- Breadcrumb -->
                    <nav class="flex mb-6 text-sm">
                        <a href="projects.php" class="text-primary hover:underline">Projects</a>
                        <span class="mx-2 text-slate-400">/</span>
                        <a href="project_details.php?id=<?php echo $project_id; ?>" class="text-primary hover:underline"><?php echo htmlspecialchars($project['name']); ?></a>
                        <span class="mx-2 text-slate-400">/</span>
                        <span class="text-slate-600">Add Task</span>
                    </nav>

                    <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Add Task</h1>
                            <p class="text-sm text-slate-500 mt-1">Create a new task for <?php echo htmlspecialchars($project['name']); ?></p>
                        </div>
                        <div class="p-6">
                            <form method="POST" class="space-y-6">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Task Title *</label>
                                    <input type="text" name="title" required
                                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Description</label>
                                    <textarea name="description" rows="4"
                                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors resize-none"></textarea>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Assign To</label>
                                        <select name="assigned_to"
                                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
                                            <option value="">Unassigned</option>
                                            <?php while ($user = $users->fetch_assoc()): ?>
                                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Due Date</label>
                                        <input type="date" name="due_date"
                                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
                                    </div>
                                </div>

                                <div class="flex gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                                    <button type="submit"
                                        class="flex items-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg transition-colors shadow-sm">
                                        <span class="material-symbols-outlined text-[20px]">add_task</span>
                                        Add Task
                                    </button>
                                    <a href="project_details.php?id=<?php echo $project_id; ?>"
                                        class="flex items-center gap-2 px-6 py-2.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-medium rounded-lg transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">cancel</span>
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>