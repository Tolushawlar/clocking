<?php
require_once 'lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    $_SESSION['business_id'] = 1;
    $_SESSION['user_id'] = 2; // Team leader ID
    $_SESSION['firstname'] = 'Team';
    $_SESSION['lastname'] = 'Leader';
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'];

// Handle project creation
if (isset($_POST['create_project'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $team_id = $_POST['team_id'];
    
    $stmt = $db->prepare("INSERT INTO projects (business_id, name, description, team_id, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issii", $business_id, $name, $description, $team_id, $user_id);
    
    if ($stmt->execute()) {
        header('Location: team_leader.php?msg=Project created successfully');
        exit;
    }
}

// Handle task creation
if (isset($_POST['create_task'])) {
    $project_id = $_POST['project_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $assigned_to = $_POST['assigned_to'];
    $deadline = $_POST['deadline'];
    
    $stmt = $db->prepare("INSERT INTO tasks (project_id, name, description, assigned_to, deadline, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issisi", $project_id, $name, $description, $assigned_to, $deadline, $user_id);
    
    if ($stmt->execute()) {
        header('Location: team_leader.php?msg=Task created successfully');
        exit;
    }
}

// Get teams led by this user
$stmt = $db->prepare("SELECT * FROM teams WHERE team_leader_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teams = $stmt->get_result();

// Get projects for teams
$stmt = $db->prepare("
    SELECT p.*, t.name as team_name,
           COUNT(DISTINCT ta.id) as task_count,
           COUNT(DISTINCT CASE WHEN ta.status = 'completed' THEN ta.id END) as completed_tasks
    FROM projects p
    JOIN teams t ON p.team_id = t.id
    LEFT JOIN tasks ta ON p.id = ta.project_id
    WHERE t.team_leader_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects = $stmt->get_result();

// Get team members
$stmt = $db->prepare("
    SELECT DISTINCT u.id, u.firstname, u.lastname, t.name as team_name
    FROM team_members tm
    JOIN users u ON tm.user_id = u.id
    JOIN teams t ON tm.team_id = t.id
    WHERE t.team_leader_id = ? AND u.id != ?
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$team_members = $stmt->get_result();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Team Leader Dashboard - TimeTrack Pro</title>
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
            <h2 class="text-lg font-bold">TimeTrack Pro - Team Leader</h2>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-slate-500">Welcome, <?php echo htmlspecialchars($_SESSION['firstname']); ?></span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 p-6">
        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Quick Actions -->
            <div class="flex flex-wrap gap-4">
                <button onclick="openProjectModal()" class="bg-primary hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">add</span>
                    Create Project
                </button>
                <button onclick="openTaskModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-[20px]">task</span>
                    Assign Task
                </button>
            </div>

            <!-- Projects -->
            <div>
                <h2 class="text-2xl font-bold mb-4">My Projects</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while ($project = $projects->fetch_assoc()): ?>
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700 shadow-sm">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900 dark:text-white"><?php echo htmlspecialchars($project['name']); ?></h3>
                                <p class="text-sm text-slate-500"><?php echo htmlspecialchars($project['team_name']); ?></p>
                            </div>
                            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full"><?php echo ucfirst($project['status']); ?></span>
                        </div>
                        
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4"><?php echo htmlspecialchars($project['description']); ?></p>
                        
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500"><?php echo $project['completed_tasks']; ?>/<?php echo $project['task_count']; ?> tasks completed</span>
                            <a href="project_tasks.php?id=<?php echo $project['id']; ?>" class="text-primary hover:underline">View Tasks →</a>
                        </div>
                        
                        <div class="mt-3 bg-slate-100 dark:bg-slate-700 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" style="width: <?php echo $project['task_count'] > 0 ? ($project['completed_tasks'] / $project['task_count']) * 100 : 0; ?>%"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Create Project Modal -->
<div id="projectModal" class="fixed inset-0 bg-black/50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-4">Create New Project</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="create_project" value="1">
                <div>
                    <label class="block text-sm font-medium mb-2">Project Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Team</label>
                    <select name="team_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Select Team</option>
                        <?php 
                        $teams->data_seek(0);
                        while ($team = $teams->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeProjectModal()" class="flex-1 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700">Create Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Task Modal -->
<div id="taskModal" class="fixed inset-0 bg-black/50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-4">Assign New Task</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="create_task" value="1">
                <div>
                    <label class="block text-sm font-medium mb-2">Project</label>
                    <select name="project_id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Select Project</option>
                        <?php 
                        $projects->data_seek(0);
                        while ($project = $projects->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Task Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Assign To</label>
                    <select name="assigned_to" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Select Team Member</option>
                        <?php 
                        $team_members->data_seek(0);
                        while ($member = $team_members->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $member['id']; ?>"><?php echo htmlspecialchars($member['firstname'] . ' ' . $member['lastname']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Deadline</label>
                    <input type="date" name="deadline" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeTaskModal()" class="flex-1 px-4 py-2 border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Assign Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openProjectModal() {
    document.getElementById('projectModal').classList.remove('hidden');
}

function closeProjectModal() {
    document.getElementById('projectModal').classList.add('hidden');
}

function openTaskModal() {
    document.getElementById('taskModal').classList.remove('hidden');
}

function closeTaskModal() {
    document.getElementById('taskModal').classList.add('hidden');
}
</script>
</body>
</html>