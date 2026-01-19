<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'] ?? null;
$project_id = $_GET['id'] ?? 0;

// Check if user_id is valid - if not, set default values like in projects.php
if (!$user_id) {
    $_SESSION['user_id'] = 1;
    $_SESSION['firstname'] = 'Admin';
    $_SESSION['lastname'] = 'User';
    $user_id = 1;
    // Keep the existing business_id from session
}

// Handle phase creation
if (isset($_POST['add_phase'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    $stmt = $db->prepare("INSERT INTO project_phases (project_id, name, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $project_id, $name, $description);
    $stmt->execute();

    header("Location: project_phases.php?id=$project_id&msg=Phase added successfully");
    exit;
}

// Handle subtask creation
if (isset($_POST['add_subtask'])) {
    $phase_id = (int)$_POST['phase_id'];
    $name = trim($_POST['title']);
    $description = trim($_POST['description']);
    $assigned_to = $_POST['assigned_to'] ? (int)$_POST['assigned_to'] : null;

    $stmt = $db->prepare("INSERT INTO tasks (project_id, phase_id, name, description, assigned_to, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissii", $project_id, $phase_id, $name, $description, $assigned_to, $user_id);

    if ($stmt->execute()) {
        header("Location: project_phases.php?id=$project_id&msg=Task added successfully");
    } else {
        header("Location: project_phases.php?id=$project_id&msg=Error adding task");
    }
    exit;
}

// Get project details
$stmt = $db->prepare("SELECT p.*, t.name as team_name FROM projects p LEFT JOIN teams t ON p.team_id = t.id WHERE p.id = ? AND p.business_id = ?");
$stmt->bind_param("ii", $project_id, $business_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    header('Location: projects.php');
    exit;
}

// Get team members for task assignment
$team_members = [];
if ($project['team_id']) {
    $members_stmt = $db->prepare("
        SELECT u.id, u.firstname, u.lastname 
        FROM users u 
        JOIN team_members tm ON u.id = tm.user_id 
        WHERE tm.team_id = ? AND u.is_active = 1
        ORDER BY u.firstname, u.lastname
    ");
    $members_stmt->bind_param("i", $project['team_id']);
    $members_stmt->execute();
    $team_members = $members_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get phases with tasks
$phases_stmt = $db->prepare("
    SELECT p.*, 
           COUNT(DISTINCT t.id) as task_count,
           COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks
    FROM project_phases p
    LEFT JOIN tasks t ON p.id = t.phase_id
    WHERE p.project_id = ?
    GROUP BY p.id
    ORDER BY p.id ASC
");
$phases_stmt->bind_param("i", $project_id);
$phases_stmt->execute();
$phases = $phases_stmt->get_result();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Project Phases - TimeTrack Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
        $current_page = 'project_phases.php';
        include 'sidebar.php';
        ?>

        <!-- Main Content Wrapper -->
        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <!-- Top Navigation -->
            <header class="flex-shrink-0 bg-surface-light dark:bg-surface-dark border-b border-border-light dark:border-border-dark px-6 py-3 flex items-center justify-between z-10">
                <div class="flex items-center gap-4 md:hidden">
                    <button class="text-text-secondary">
                        <span class="material-symbols-outlined">menu</span>
                    </button>
                    <span class="text-lg font-bold">TimeTrack Pro</span>
                </div>
                <div class="hidden md:flex flex-1">
                    <div class="relative w-full max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="material-symbols-outlined text-text-secondary">search</span>
                        </div>
                        <input class="block w-full pl-10 pr-3 py-2 border-none rounded-lg leading-5 bg-background-light dark:bg-slate-800 text-text-main dark:text-white placeholder-text-secondary focus:outline-none focus:ring-1 focus:ring-primary sm:text-sm" placeholder="Search phases and tasks..." type="text" />
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <button class="p-2 text-text-secondary hover:bg-background-light dark:hover:bg-slate-700 rounded-full transition-colors relative">
                        <span class="material-symbols-outlined">notifications</span>
                        <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-surface-light dark:border-surface-dark"></span>
                    </button>
                    <div class="h-8 w-8 rounded-full bg-cover bg-center border border-border-light" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBSBfpdqQBQgGld3Icgsto2cnz_krZW7C4cA3fku_S3QIKlg3UPP360tqJ1Z5pvCC5bNIB8ij9qFLfFZR-DsyrHtyaXMh6EFuvoOKYTeP_bfjdb9GnAak8Rq5AN1ATMFC062CwzQhylg8k1QfRx5pH9CMoLSnR_u9WjmyqdbD8CLiWzHMGGq8wn_qsJuGBzxRRNgD-0NwHiH5o4RccYyduyA5i4WGKTPsE4soDPa74x3T2K5rJa2Jq70WS7PouvLrUbKjcVaW3e5iY");'></div>
                </div>
            </header>

            <!-- Scrollable Page Content -->
            <div class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark">
                <div class="max-w-6xl mx-auto px-6 py-8 flex flex-col gap-8">
                    <!-- Breadcrumbs -->
                    <nav class="flex text-sm font-medium text-text-secondary">
                        <a class="hover:text-primary transition-colors" href="projects.php">Projects</a>
                        <span class="mx-2 text-gray-400">/</span>
                        <span class="text-text-main dark:text-white"><?php echo htmlspecialchars($project['name']); ?> - Phases</span>
                    </nav>

                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-3xl font-bold text-text-main dark:text-white tracking-tight"><?php echo htmlspecialchars($project['name']); ?> - Phases</h1>
                            <p class="text-text-secondary mt-1">Manage project phases and tasks</p>
                        </div>
                        <button onclick="showAddPhaseModal()" class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-sm shadow-blue-200 dark:shadow-none">
                            <span class="material-symbols-outlined text-lg">add</span>
                            Add Phase
                        </button>
                    </div>

                    <?php if (isset($_GET['msg'])): ?>
                        <div class="p-4 bg-green-50 border border-green-200 text-green-800 rounded-lg mb-6">
                            <?php echo htmlspecialchars($_GET['msg']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="space-y-6">
                        <?php while ($phase = $phases->fetch_assoc()): ?>
                            <div class="bg-white rounded-lg shadow-sm border">
                                <div class="p-6 border-b border-gray-200">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($phase['name']); ?></h3>
                                            <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($phase['description']); ?></p>
                                            <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                                                <span><?php echo $phase['task_count']; ?> tasks</span>
                                                <span><?php echo $phase['completed_tasks']; ?> completed</span>
                                                <!-- <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">Phase #<?php echo $phase['id']; ?></span> -->
                                            </div>
                                        </div>
                                        <button onclick="showAddTaskModal(<?php echo $phase['id']; ?>)" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">add</span>
                                            Add Task
                                        </button>
                                    </div>
                                </div>

                                <div class="p-6">
                                    <?php
                                    // Get tasks for this phase
                                    $tasks_stmt = $db->prepare("
                        SELECT t.*, u.firstname, u.lastname
                        FROM tasks t
                        LEFT JOIN users u ON t.assigned_to = u.id
                        WHERE t.phase_id = ?
                        ORDER BY t.created_at ASC
                    ");
                                    $tasks_stmt->bind_param("i", $phase['id']);
                                    $tasks_stmt->execute();
                                    $tasks = $tasks_stmt->get_result();
                                    ?>

                                    <?php if ($tasks->num_rows > 0): ?>
                                        <div class="space-y-3">
                                            <?php while ($task = $tasks->fetch_assoc()): ?>
                                                <div class="border border-gray-200 rounded-lg p-4">
                                                    <div class="flex justify-between items-start mb-2">
                                                        <div>
                                                            <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($task['name']); ?></h4>
                                                            <p class="text-gray-600 text-sm mt-1"><?php echo htmlspecialchars($task['description']); ?></p>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
                                                                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-8 text-gray-500">
                                            <span class="material-symbols-outlined text-4xl mb-2 opacity-50">task_alt</span>
                                            <p>No tasks in this phase yet</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Phase Modal -->
    <div id="add-phase-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Add New Phase</h3>
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phase Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" name="add_phase" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Add Phase</button>
                    <button type="button" onclick="closeModal('add-phase-modal')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Task Modal -->
    <div id="add-task-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4" id="task-modal-title">Add New Task</h3>
            <form method="POST">
                <input type="hidden" name="phase_id" id="task-phase-id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Task Title</label>
                    <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <?php if (!empty($team_members)): ?>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Assign to Team Member</label>
                        <select name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Unassigned</option>
                            <?php foreach ($team_members as $member): ?>
                                <option value="<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['firstname'] . ' ' . $member['lastname']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="flex gap-3">
                    <button type="submit" name="add_subtask" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">Add Task</button>
                    <button type="button" onclick="closeModal('add-task-modal')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddPhaseModal() {
            document.getElementById('add-phase-modal').classList.remove('hidden');
        }

        function showAddTaskModal(phaseId) {
            document.getElementById('task-phase-id').value = phaseId;
            document.getElementById('task-modal-title').textContent = 'Add Task';
            document.getElementById('add-task-modal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
</body>

</html>