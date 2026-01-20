<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$project_id = $_GET['id'] ?? 0;

if ($_POST) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $client_name = trim($_POST['client_name']);
    $status = $_POST['status'];
    $end_date = $_POST['end_date'] ?: null;
    $budget_hours = (int)$_POST['budget_hours'];

    $stmt = $db->prepare("UPDATE projects SET name = ?, description = ?, client_name = ?, status = ?, end_date = ?, budget_hours = ? WHERE id = ? AND business_id = ?");
    $stmt->bind_param("sssssiis", $name, $description, $client_name, $status, $end_date, $budget_hours, $project_id, $business_id);
    $stmt->execute();

    header("Location: project_details.php?id=$project_id");
    exit;
}

$stmt = $db->prepare("SELECT * FROM projects WHERE id = ? AND business_id = ?");
$stmt->bind_param("ii", $project_id, $business_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    header('Location: projects.php');
    exit;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Edit Project - TimeTrack Pro</title>
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
        $current_page = 'projects.php';
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
                        <span class="text-slate-600">Edit</span>
                    </nav>

                    <div class="bg-white dark:bg-surface-dark rounded-xl shadow-sm border border-slate-200 dark:border-slate-700">
                        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Edit Project</h1>
                            <p class="text-sm text-slate-500 mt-1">Update project details and settings</p>
                        </div>
                        <div class="p-6">
                            <form method="POST" class="space-y-6">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Project Name *</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($project['name']); ?>" required
                                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Description</label>
                                    <textarea name="description" rows="4"
                                        class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors resize-none"><?php echo htmlspecialchars($project['description']); ?></textarea>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Client Name</label>
                                        <input type="text" name="client_name" value="<?php echo htmlspecialchars($project['client_name']); ?>"
                                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Status</label>
                                        <select name="status"
                                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
                                            <option value="planning" <?php echo $project['status'] == 'planning' ? 'selected' : ''; ?>>Planning</option>
                                            <option value="active" <?php echo $project['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="on_hold" <?php echo $project['status'] == 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                                            <option value="completed" <?php echo $project['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">End Date</label>
                                        <input type="date" name="end_date" value="<?php echo $project['end_date']; ?>"
                                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Budget Hours</label>
                                        <input type="number" name="budget_hours" value="<?php echo $project['budget_hours']; ?>"
                                            class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary/50 focus:border-primary transition-colors">
                                    </div>
                                </div>

                                <div class="flex gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                                    <button type="submit"
                                        class="flex items-center gap-2 px-6 py-2.5 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg transition-colors shadow-sm">
                                        <span class="material-symbols-outlined text-[20px]">save</span>
                                        Update Project
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