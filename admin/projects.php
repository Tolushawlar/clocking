<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];

// Handle project creation
if (isset($_POST['create_project'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $client_name = trim($_POST['client_name']);
    $team_id = (!empty($_POST['team_id']) && $_POST['team_id'] !== '') ? (int)$_POST['team_id'] : null;
    $budget_hours = (!empty($_POST['budget_hours']) && $_POST['budget_hours'] !== '') ? (int)$_POST['budget_hours'] : null;

    // Handle dates - set to NULL if empty or invalid
    $start_date = null;
    $end_date = null;

    if (!empty($_POST['start_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['start_date'])) {
        $start_date = $_POST['start_date'];
    }

    if (!empty($_POST['end_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['end_date'])) {
        $end_date = $_POST['end_date'];
    }

    try {
        // Get the first user from this business to use as created_by
        $user_stmt = $db->prepare("SELECT id FROM users WHERE business_id = ? LIMIT 1");
        $user_stmt->bind_param("i", $business_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result()->fetch_assoc();
        $created_by = $user_result ? $user_result['id'] : 1;
        
        $stmt = $db->prepare("INSERT INTO projects (business_id, name, description, client_name, team_id, start_date, end_date, budget_hours, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("isssissii", $business_id, $name, $description, $client_name, $team_id, $start_date, $end_date, $budget_hours, $created_by);

        if ($stmt->execute()) {
            header('Location: projects.php?msg=Project created successfully');
            exit;
        }
    } catch (Exception $e) {
        error_log("Project creation error: " . $e->getMessage());
        error_log("Values - start_date: " . var_export($start_date, true) . ", end_date: " . var_export($end_date, true));
        header('Location: projects.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

// Get projects
$stmt = $db->prepare("
    SELECT p.*, u.firstname, u.lastname, t.name as team_name,
           COUNT(DISTINCT ta.id) as task_count,
           COUNT(DISTINCT CASE WHEN ta.status = 'completed' THEN ta.id END) as completed_tasks,
           COUNT(DISTINCT pp.id) as phase_count,
           COUNT(DISTINCT CASE WHEN pp.status = 'completed' THEN pp.id END) as completed_phases
    FROM projects p 
    LEFT JOIN users u ON p.created_by = u.id
    LEFT JOIN teams t ON p.team_id = t.id
    LEFT JOIN tasks ta ON p.id = ta.project_id
    LEFT JOIN project_phases pp ON p.id = pp.project_id
    WHERE p.business_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$projects = $stmt->get_result();

// Get teams for assignment
$teams_stmt = $db->prepare("SELECT id, name FROM teams WHERE business_id = ? ORDER BY name");
$teams_stmt->bind_param("i", $business_id);
$teams_stmt->execute();
$teams = $teams_stmt->get_result();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>TimeTrack Pro - Projects</title>
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
        $current_page = 'projects.php';
        include 'sidebar.php';
        ?>

        <!-- Main Content Wrapper -->
        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <?php include 'header.php'; ?>

            <!-- Scrollable Page Content -->
            <div class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark">
                <div class="max-w-6xl mx-auto px-6 py-8 flex flex-col gap-8">
                    <!-- Page Header -->
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div class="flex flex-col gap-1">
                            <h1 class="text-3xl font-bold text-text-main dark:text-white tracking-tight">Project Management</h1>
                            <p class="text-text-secondary">Create and manage your projects</p>
                        </div>
                        <button onclick="openCreateModal()" class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-sm shadow-blue-200 dark:shadow-none">
                            <span class="material-symbols-outlined text-lg">add</span>
                            Create Project
                        </button>
                    </div>

                    <?php if (isset($_GET['msg'])): ?>
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-lg">
                            <?php echo htmlspecialchars($_GET['msg']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['error'])): ?>
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-lg">
                            Error: <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Projects Grid -->
                    <?php if ($projects->num_rows > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php while ($project = $projects->fetch_assoc()): ?>
                                <?php
                                $progress = $project['task_count'] > 0 ? round(($project['completed_tasks'] / $project['task_count']) * 100) : 0;
                                $status_colors = [
                                    'active' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                    'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                    'on_hold' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                    'planning' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
                                ];
                                ?>
                                <div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-text-main dark:text-white"><?php echo htmlspecialchars($project['name']); ?></h3>
                                            <p class="text-sm text-text-secondary mt-1"><?php echo htmlspecialchars(substr($project['description'] ?? '', 0, 80)); ?><?php echo strlen($project['description'] ?? '') > 80 ? '...' : ''; ?></p>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $status_colors[$project['status']] ?? $status_colors['planning']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                        </span>
                                    </div>

                                    <div class="space-y-3">
                                        <?php if ($project['team_name']): ?>
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-sm">group</span>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-text-main dark:text-white">Team</p>
                                                    <p class="text-xs text-text-secondary"><?php echo htmlspecialchars($project['team_name']); ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($project['client_name']): ?>
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-primary text-sm">business</span>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-text-main dark:text-white">Client</p>
                                                    <p class="text-xs text-text-secondary"><?php echo htmlspecialchars($project['client_name']); ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-gray-100 dark:bg-slate-700 rounded-full flex items-center justify-center">
                                                <span class="material-symbols-outlined text-text-secondary text-sm">task_alt</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-text-main dark:text-white"><?php echo $project['task_count']; ?> Tasks</p>
                                                <p class="text-xs text-text-secondary"><?php echo $project['completed_tasks']; ?> completed (<?php echo $progress; ?>%)</p>
                                            </div>
                                        </div>

                                        <?php if ($project['phase_count'] > 0): ?>
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-sm">view_timeline</span>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-text-main dark:text-white"><?php echo $project['phase_count']; ?> Phases</p>
                                                    <p class="text-xs text-text-secondary"><?php echo $project['completed_phases']; ?> completed</p>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($project['end_date']): ?>
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-orange-600 dark:text-orange-400 text-sm">schedule</span>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-text-main dark:text-white">Due Date</p>
                                                    <p class="text-xs text-text-secondary"><?php echo date('M j, Y', strtotime($project['end_date'])); ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mt-4 pt-4 border-t border-border-light dark:border-border-dark">
                                        <a href="project_details.php?id=<?php echo $project['id']; ?>" class="text-primary text-sm font-medium hover:underline">View Details →</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="flex flex-col items-center justify-center py-16 px-4">
                            <div class="w-32 h-32 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-full flex items-center justify-center mb-6 shadow-lg">
                                <span class="material-symbols-outlined text-primary dark:text-blue-400" style="font-size: 64px;">folder_open</span>
                            </div>
                            <h3 class="text-2xl font-bold text-text-main dark:text-white mb-2">No Projects Yet</h3>
                            <p class="text-text-secondary text-center max-w-md mb-8">
                                Start organizing your work by creating your first project. Track progress, manage teams, and deliver exceptional results to your clients.
                            </p>
                            <button onclick="openCreateModal()" class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-6 py-3 rounded-lg text-sm font-semibold transition-all shadow-lg shadow-blue-200 dark:shadow-none">
                                <span class="material-symbols-outlined text-lg">add</span>
                                Create Your First Project
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Project Modal -->
    <div id="createModal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl max-w-md w-full p-6 border border-border-light dark:border-border-dark">
                <h3 class="text-lg font-semibold mb-4 text-text-main dark:text-white">Create New Project</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="create_project" value="1">
                    <div>
                        <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">Project Name</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">Description</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">Client Name</label>
                        <input type="text" name="client_name" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">Assign Team</label>
                        <select name="team_id" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">No Team</option>
                            <?php
                            $teams->data_seek(0);
                            while ($team = $teams->fetch_assoc()):
                            ?>
                                <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">Start Date</label>
                            <input type="date" name="start_date" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">End Date</label>
                            <input type="date" name="end_date" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">Budget Hours</label>
                        <input type="number" name="budget_hours" step="0.5" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" onclick="closeCreateModal()" class="flex-1 px-4 py-2 border border-border-light dark:border-border-dark text-text-secondary rounded-lg hover:bg-background-light dark:hover:bg-slate-700 transition-colors">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">Create Project</button>
                    </div>
                </form>
            </div>
        </div>
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

    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
        }

        // Search functionality
        document.getElementById('searchProjects').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const projectCards = document.querySelectorAll('.grid > div');

            projectCards.forEach(card => {
                const projectName = card.querySelector('h3')?.textContent.toLowerCase() || '';
                const projectDesc = card.querySelector('.text-text-secondary')?.textContent.toLowerCase() || '';
                const clientName = card.querySelector('.text-xs')?.textContent.toLowerCase() || '';

                if (projectName.includes(searchTerm) || projectDesc.includes(searchTerm) || clientName.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>