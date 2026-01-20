<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$business_id = $_SESSION['business_id'];
$project_id = $_GET['project_id'] ?? 0;

// Check if user is a team leader
$stmt = $db->prepare("SELECT user_role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

if ($user_data['user_role'] !== 'team_leader') {
    header('Location: projects.php');
    exit;
}

// Get project details and verify user is team leader
$project_query = "
    SELECT p.*, t.name as team_name, t.team_leader_id, t.id as team_id
    FROM projects p
    LEFT JOIN teams t ON p.team_id = t.id
    WHERE p.id = ? AND p.business_id = ?
";

$stmt = $db->prepare($project_query);
$stmt->bind_param("ii", $project_id, $business_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    header('Location: projects.php');
    exit;
}

// Verify user is the team leader for this project
if ($project['team_leader_id'] != $user_id) {
    header('Location: project_detail.php?id=' . $project_id);
    exit;
}

// Handle task creation
if ($_POST && isset($_POST['create_task'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $phase_id = (!empty($_POST['phase_id']) && $_POST['phase_id'] !== '') ? (int)$_POST['phase_id'] : null;
    $assigned_to = (!empty($_POST['assigned_to']) && $_POST['assigned_to'] !== '') ? (int)$_POST['assigned_to'] : null;
    $priority = $_POST['priority'] ?? 'medium';
    $due_date = (!empty($_POST['due_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['due_date'])) ? $_POST['due_date'] : null;
    $estimated_hours = (!empty($_POST['estimated_hours']) && $_POST['estimated_hours'] !== '') ? (float)$_POST['estimated_hours'] : null;

    try {
        $stmt = $db->prepare("
            INSERT INTO tasks (project_id, phase_id, name, description, status, priority, due_date, estimated_hours, assigned_to, created_by) 
            VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iissssdii", $project_id, $phase_id, $name, $description, $priority, $due_date, $estimated_hours, $assigned_to, $user_id);

        if ($stmt->execute()) {
            header('Location: project_detail.php?id=' . $project_id . '&msg=Task created successfully');
            exit;
        }
    } catch (Exception $e) {
        $error = 'Error creating task: ' . $e->getMessage();
    }
}

// Get project phases
$phases_stmt = $db->prepare("SELECT id, name FROM project_phases WHERE project_id = ? ORDER BY order_index, created_at");
$phases_stmt->bind_param("i", $project_id);
$phases_stmt->execute();
$phases = $phases_stmt->get_result();

// Get team members for assignment
$members_stmt = $db->prepare("
    SELECT u.id, u.firstname, u.lastname 
    FROM users u
    JOIN team_members tm ON u.id = tm.user_id
    WHERE tm.team_id = ? AND u.is_active = 1
    ORDER BY u.firstname, u.lastname
");
$members_stmt->bind_param("i", $project['team_id']);
$members_stmt->execute();
$members = $members_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Create Task - <?php echo htmlspecialchars($project['name']); ?></title>
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
        $current_page = 'projects.php';
        include 'sidebar.php';
        ?>

        <main class="flex-1 flex flex-col h-full overflow-hidden bg-background relative">
            <!-- Top Header -->
            <div class="md:hidden flex items-center justify-between p-4 border-b border-border-subtle bg-card">
                <div class="flex items-center gap-2">
                    <a href="project_detail.php?id=<?php echo $project_id; ?>" class="p-2 -ml-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">arrow_back</span>
                    </a>
                    <h1 class="text-lg font-bold">Create Task</h1>
                </div>
                <button onclick="toggleSidebar()" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto">
                <div class="max-w-[800px] w-full mx-auto p-4 md:p-6 lg:p-8 flex flex-col gap-6">
                    <!-- Breadcrumbs -->
                    <nav class="flex items-center gap-2 text-sm">
                        <a href="projects.php" class="text-text-secondary hover:text-primary transition-colors">My Projects</a>
                        <span class="material-symbols-outlined text-text-secondary text-[16px]">chevron_right</span>
                        <a href="project_detail.php?id=<?php echo $project_id; ?>" class="text-text-secondary hover:text-primary transition-colors"><?php echo htmlspecialchars($project['name']); ?></a>
                        <span class="material-symbols-outlined text-text-secondary text-[16px]">chevron_right</span>
                        <span class="text-slate-900 font-medium">Create Task</span>
                    </nav>

                    <?php if (isset($error)): ?>
                        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-lg">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">error</span>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Form Card -->
                    <div class="bg-card border border-border-subtle rounded-xl p-6">
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold text-slate-900">Create New Task</h1>
                            <p class="text-sm text-text-secondary mt-1">Add a task to <?php echo htmlspecialchars($project['name']); ?></p>
                        </div>

                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="create_task" value="1">

                            <!-- Task Name -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Task Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="name"
                                    required
                                    class="w-full px-4 py-2.5 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                                    placeholder="Enter task name" />
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Description
                                </label>
                                <textarea
                                    name="description"
                                    rows="4"
                                    class="w-full px-4 py-2.5 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors resize-none"
                                    placeholder="Describe the task..."></textarea>
                            </div>

                            <!-- Phase Selection -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Project Phase
                                </label>
                                <select
                                    name="phase_id"
                                    class="w-full px-4 py-2.5 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                                    <option value="">No Phase</option>
                                    <?php while ($phase = $phases->fetch_assoc()): ?>
                                        <option value="<?php echo $phase['id']; ?>"><?php echo htmlspecialchars($phase['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Two Column Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Assign To -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Assign To
                                    </label>
                                    <select
                                        name="assigned_to"
                                        class="w-full px-4 py-2.5 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                                        <option value="">Unassigned</option>
                                        <?php while ($member = $members->fetch_assoc()): ?>
                                            <option value="<?php echo $member['id']; ?>">
                                                <?php echo htmlspecialchars($member['firstname'] . ' ' . $member['lastname']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <!-- Priority -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Priority
                                    </label>
                                    <select
                                        name="priority"
                                        class="w-full px-4 py-2.5 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>

                                <!-- Due Date -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Due Date
                                    </label>
                                    <input
                                        type="date"
                                        name="due_date"
                                        class="w-full px-4 py-2.5 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors" />
                                </div>

                                <!-- Estimated Hours -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                                        Estimated Hours
                                    </label>
                                    <input
                                        type="number"
                                        name="estimated_hours"
                                        min="0"
                                        step="0.5"
                                        class="w-full px-4 py-2.5 border border-border-subtle rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors"
                                        placeholder="e.g., 8" />
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-border-subtle">
                                <button
                                    type="submit"
                                    class="flex-1 sm:flex-initial px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-[20px]">add</span>
                                    Create Task
                                </button>
                                <a
                                    href="project_detail.php?id=<?php echo $project_id; ?>"
                                    class="flex-1 sm:flex-initial px-6 py-2.5 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors font-medium text-center">
                                    Cancel
                                </a>
                            </div>
                        </form>
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
    </script>
</body>

</html>