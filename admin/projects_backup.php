<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    // For testing, set default session values
    $_SESSION['business_id'] = 1;
    $_SESSION['user_id'] = 1;
    $_SESSION['firstname'] = 'Admin';
    $_SESSION['lastname'] = 'User';
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Location: ../index.php');
    exit;
}

// Get user permissions (fallback if columns don't exist)
$user = ['role' => 'admin', 'can_create_projects' => 1, 'can_manage_team' => 1];
try {
    $stmt = $db->prepare("SELECT category as role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result) {
        $user['role'] = $result['role'];
    }
} catch (Exception $e) {
    // Use defaults if query fails
}

// Handle project creation
if (isset($_POST['create_project'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $client_name = trim($_POST['client_name']);
    $start_date = $_POST['start_date'] ?: null;
    $end_date = $_POST['end_date'] ?: null;
    $budget_hours = $_POST['budget_hours'] ?: null;
    
    $stmt = $db->prepare("INSERT INTO projects (business_id, name, description, client_name, start_date, end_date, budget_hours, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssdi", $business_id, $name, $description, $client_name, $start_date, $end_date, $budget_hours, $user_id);
    
    if ($stmt->execute()) {
        $project_id = $db->insert_id;
        // Add creator as project owner
        $stmt = $db->prepare("INSERT INTO project_members (project_id, user_id, role, added_by) VALUES (?, ?, 'owner', ?)");
        $stmt->bind_param("iii", $project_id, $user_id, $user_id);
        $stmt->execute();
        
        header('Location: projects.php?msg=Project created successfully');
        exit;
    }
}

// Get projects with stats
$projects_query = "
    SELECT p.*, 
           u.firstname, u.lastname,
           COUNT(DISTINCT t.id) as total_tasks,
           COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
           COUNT(DISTINCT CASE WHEN t.status = 'in_progress' THEN t.id END) as active_tasks,
           COUNT(DISTINCT pm.user_id) as team_size,
           COALESCE(SUM(t.actual_hours), 0) as total_hours
    FROM projects p 
    LEFT JOIN users u ON p.created_by = u.id
    LEFT JOIN tasks t ON p.id = t.project_id
    LEFT JOIN project_members pm ON p.id = pm.project_id
    WHERE p.business_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
";

$stmt = $db->prepare($projects_query);
$stmt->bind_param("i", $business_id);
$stmt->execute();
$projects = $stmt->get_result();

// Get project statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_projects,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_projects,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_projects,
        COUNT(CASE WHEN status = 'on_hold' OR (end_date < CURDATE() AND status != 'completed') THEN 1 END) as delayed_projects
    FROM projects 
    WHERE business_id = ?
";

$stmt = $db->prepare($stats_query);
$stmt->bind_param("i", $business_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Projects List - TimeTrack Pro</title>
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
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-[#0d121b] dark:text-white antialiased overflow-x-hidden">
<div class="min-h-screen flex flex-col">
    <!-- Top Navigation -->
    <header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-[#e7ebf3] dark:border-[#2a3447] bg-white dark:bg-[#1a202c] px-6 py-3 sticky top-0 z-50 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="size-8 text-primary flex items-center justify-center rounded-lg bg-primary/10">
                <span class="material-symbols-outlined text-2xl">schedule</span>
            </div>
            <h2 class="text-[#0d121b] dark:text-white text-lg font-bold leading-tight tracking-[-0.015em]">TimeTrack Pro</h2>
        </div>
        <nav class="hidden md:flex items-center gap-8">
            <a class="text-[#4c669a] dark:text-[#94a3b8] hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="dashboard.php">Dashboard</a>
            <a class="text-[#4c669a] dark:text-[#94a3b8] hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="../index.php">Time Logs</a>
            <a class="text-primary text-sm font-bold leading-normal" href="projects.php">Projects</a>
            <a class="text-[#4c669a] dark:text-[#94a3b8] hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="reports.php">Reports</a>
            <a class="text-[#4c669a] dark:text-[#94a3b8] hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="settings.php">Settings</a>
        </nav>
        <div class="flex items-center gap-4">
            <button class="flex items-center justify-center rounded-lg size-10 hover:bg-[#f8f9fc] dark:hover:bg-[#2d3748] text-[#4c669a] dark:text-[#94a3b8] transition-colors relative">
                <span class="material-symbols-outlined">notifications</span>
                <span class="absolute top-2 right-2 size-2 rounded-full bg-red-500 border border-white dark:border-[#1a202c]"></span>
            </button>
            <div class="h-8 w-[1px] bg-[#e7ebf3] dark:bg-[#2a3447] mx-1"></div>
            <div class="flex items-center gap-3">
                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 border border-[#e7ebf3] dark:border-[#2a3447]" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBSBfpdqQBQgGld3Icgsto2cnz_krZW7C4cA3fku_S3QIKlg3UPP360tqJ1Z5pvCC5bNIB8ij9qFLfFZR-DsyrHtyaXMh6EFuvoOKYTeP_bfjdb9GnAak8Rq5AN1ATMFC062CwzQhylg8k1QfRx5pH9CMoLSnR_u9WjmyqdbD8CLiWzHMGGq8wn_qsJuGBzxRRNgD-0NwHiH5o4RccYyduyA5i4WGKTPsE4soDPa74x3T2K5rJa2Jq70WS7PouvLrUbKjcVaW3e5iY");'></div>
                <div class="hidden lg:block text-left">
                    <p class="text-sm font-medium text-[#0d121b] dark:text-white"><?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?></p>
                    <p class="text-xs text-[#4c669a] dark:text-[#94a3b8]"><?php echo ucfirst($user['role']); ?></p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 w-full max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumbs -->
        <div class="mb-6 flex items-center gap-2 text-sm">
            <a class="text-[#4c669a] dark:text-[#94a3b8] hover:text-primary transition-colors" href="dashboard.php">Home</a>
            <span class="text-[#cfd7e7] dark:text-[#4b5563]">/</span>
            <span class="text-[#0d121b] dark:text-white font-medium">Projects</span>
        </div>

        <!-- Header & Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-[#0d121b] dark:text-white mb-2">Projects</h1>
                <p class="text-[#4c669a] dark:text-[#94a3b8] text-base">Manage ongoing initiatives, track progress, and assign resources.</p>
            </div>
            <?php if (true): ?>
            <button onclick="openCreateModal()" class="flex items-center justify-center gap-2 bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg shadow-sm hover:shadow-md transition-all font-medium text-sm whitespace-nowrap">
                <span class="material-symbols-outlined text-[20px]">add</span>
                <span>Create New Project</span>
            </button>
            <?php endif; ?>
        </div>

        <!-- KPI Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Total Projects -->
            <div class="bg-white dark:bg-[#1a202c] rounded-xl p-5 border border-[#e7ebf3] dark:border-[#2a3447] shadow-sm flex flex-col justify-between h-32">
                <div class="flex items-center justify-between">
                    <span class="text-[#4c669a] dark:text-[#94a3b8] text-sm font-medium">Total Projects</span>
                    <div class="size-8 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-lg">folder</span>
                    </div>
                </div>
                <div>
                    <span class="text-3xl font-bold text-[#0d121b] dark:text-white"><?php echo $stats['total_projects']; ?></span>
                </div>
            </div>

            <!-- Active Projects -->
            <div class="bg-white dark:bg-[#1a202c] rounded-xl p-5 border border-[#e7ebf3] dark:border-[#2a3447] shadow-sm flex flex-col justify-between h-32">
                <div class="flex items-center justify-between">
                    <span class="text-[#4c669a] dark:text-[#94a3b8] text-sm font-medium">Active</span>
                    <div class="size-8 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600">
                        <span class="material-symbols-outlined text-lg">bolt</span>
                    </div>
                </div>
                <div>
                    <span class="text-3xl font-bold text-[#0d121b] dark:text-white"><?php echo $stats['active_projects']; ?></span>
                </div>
            </div>

            <!-- Delayed Projects -->
            <div class="bg-white dark:bg-[#1a202c] rounded-xl p-5 border border-[#e7ebf3] dark:border-[#2a3447] shadow-sm flex flex-col justify-between h-32">
                <div class="flex items-center justify-between">
                    <span class="text-[#4c669a] dark:text-[#94a3b8] text-sm font-medium">Delayed</span>
                    <div class="size-8 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-600">
                        <span class="material-symbols-outlined text-lg">warning</span>
                    </div>
                </div>
                <div>
                    <span class="text-3xl font-bold text-[#0d121b] dark:text-white"><?php echo $stats['delayed_projects']; ?></span>
                </div>
            </div>

            <!-- Completed Projects -->
            <div class="bg-white dark:bg-[#1a202c] rounded-xl p-5 border border-[#e7ebf3] dark:border-[#2a3447] shadow-sm flex flex-col justify-between h-32">
                <div class="flex items-center justify-between">
                    <span class="text-[#4c669a] dark:text-[#94a3b8] text-sm font-medium">Completed</span>
                    <div class="size-8 rounded-full bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600">
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                    </div>
                </div>
                <div>
                    <span class="text-3xl font-bold text-[#0d121b] dark:text-white"><?php echo $stats['completed_projects']; ?></span>
                </div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white dark:bg-[#1a202c] p-4 rounded-xl border border-[#e7ebf3] dark:border-[#2a3447] shadow-sm mb-6 flex flex-col lg:flex-row gap-4 items-center justify-between">
            <!-- Search -->
            <div class="relative w-full lg:w-96">
                <input class="w-full h-10 pl-10 pr-4 rounded-lg border border-[#cfd7e7] dark:border-[#4a5568] bg-[#f8f9fc] dark:bg-[#2d3748] text-[#0d121b] dark:text-white text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder:text-[#4c669a] outline-none transition-all" placeholder="Search by project name..." type="text" id="searchInput"/>
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#4c669a] text-[20px]">search</span>
            </div>
            <!-- Filters -->
            <div class="flex items-center gap-3 w-full lg:w-auto overflow-x-auto pb-2 lg:pb-0">
                <select class="h-10 pl-3 pr-8 rounded-lg border border-[#cfd7e7] dark:border-[#4a5568] bg-white dark:bg-[#2d3748] text-[#0d121b] dark:text-white text-sm focus:ring-primary focus:border-primary outline-none cursor-pointer" id="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="on_hold">On Hold</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>

        <!-- Projects Data Grid -->
        <div class="bg-white dark:bg-[#1a202c] rounded-xl border border-[#e7ebf3] dark:border-[#2a3447] shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#f8fafc] dark:bg-[#2d3748] border-b border-[#e7ebf3] dark:border-[#4a5568]">
                            <th class="py-4 px-6 text-xs font-semibold text-[#4c669a] dark:text-[#94a3b8] uppercase tracking-wider">Project Name</th>
                            <th class="py-4 px-6 text-xs font-semibold text-[#4c669a] dark:text-[#94a3b8] uppercase tracking-wider">Status</th>
                            <th class="py-4 px-6 text-xs font-semibold text-[#4c669a] dark:text-[#94a3b8] uppercase tracking-wider">Created By</th>
                            <th class="py-4 px-6 text-xs font-semibold text-[#4c669a] dark:text-[#94a3b8] uppercase tracking-wider">End Date</th>
                            <th class="py-4 px-6 text-xs font-semibold text-[#4c669a] dark:text-[#94a3b8] uppercase tracking-wider w-48">Progress</th>
                            <th class="py-4 px-6 text-xs font-semibold text-[#4c669a] dark:text-[#94a3b8] uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e7ebf3] dark:divide-[#4a5568]" id="projectsTableBody">
                        <?php while ($project = $projects->fetch_assoc()): 
                            $progress = $project['total_tasks'] > 0 ? round(($project['completed_tasks'] / $project['total_tasks']) * 100) : 0;
                            $status_class = '';
                            $status_text = ucfirst(str_replace('_', ' ', $project['status']));
                            
                            switch($project['status']) {
                                case 'active':
                                    $status_class = 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 border-emerald-200 dark:border-emerald-800';
                                    break;
                                case 'completed':
                                    $status_class = 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 border-indigo-200 dark:border-indigo-800';
                                    break;
                                case 'on_hold':
                                    $status_class = 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600';
                                    break;
                                default:
                                    $status_class = 'bg-primary/10 text-primary border-primary/20';
                            }
                            
                            $is_delayed = $project['end_date'] && strtotime($project['end_date']) < time() && $project['status'] != 'completed';
                        ?>
                        <tr class="group hover:bg-[#f8f9fc] dark:hover:bg-[#2d3748]/50 transition-colors" data-status="<?php echo $project['status']; ?>" data-name="<?php echo strtolower($project['name']); ?>">
                            <td class="py-4 px-6">
                                <div class="flex flex-col">
                                    <span class="text-[#0d121b] dark:text-white font-semibold text-sm"><?php echo htmlspecialchars($project['name']); ?></span>
                                    <span class="text-[#4c669a] dark:text-[#94a3b8] text-xs"><?php echo $project['client_name'] ? 'Client: ' . htmlspecialchars($project['client_name']) : 'Internal'; ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?> border">
                                    <?php echo $is_delayed ? 'Delayed' : $status_text; ?>
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="size-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold">
                                        <?php echo strtoupper(substr($project['firstname'], 0, 1) . substr($project['lastname'], 0, 1)); ?>
                                    </div>
                                    <span class="text-sm text-[#0d121b] dark:text-white font-medium"><?php echo htmlspecialchars($project['firstname'] . ' ' . $project['lastname']); ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-sm <?php echo $is_delayed ? 'text-red-600 font-medium' : 'text-[#0d121b] dark:text-white'; ?>">
                                    <?php echo $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : 'TBD'; ?>
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex justify-between text-xs">
                                        <span class="font-medium text-[#0d121b] dark:text-white"><?php echo $progress; ?>%</span>
                                        <?php if ($is_delayed): ?>
                                        <span class="text-red-500 font-medium">Behind</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="h-2 w-full bg-[#e7ebf3] dark:bg-[#4a5568] rounded-full overflow-hidden">
                                        <div class="h-full <?php echo $is_delayed ? 'bg-red-500' : ($project['status'] == 'completed' ? 'bg-indigo-500' : ($project['status'] == 'on_hold' ? 'bg-gray-400' : 'bg-primary')); ?> rounded-full" style="width: <?php echo $progress; ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="relative inline-block">
                                    <button onclick="toggleDropdown(<?php echo $project['id']; ?>)" class="text-[#4c669a] hover:text-primary dark:text-[#94a3b8] dark:hover:text-white p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">more_vert</span>
                                    </button>
                                    <div id="dropdown-<?php echo $project['id']; ?>" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-[#1a202c] rounded-lg shadow-lg border border-[#e7ebf3] dark:border-[#2a3447] z-10">
                                        <a href="project_details.php?id=<?php echo $project['id']; ?>" class="block px-4 py-2 text-sm text-[#0d121b] dark:text-white hover:bg-[#f8f9fc] dark:hover:bg-[#2d3748] rounded-t-lg">View Details</a>
                                        <?php if ($user['can_create_projects'] || in_array($user['role'], ['admin', 'supervisor'])): ?>
                                        <button onclick="editProject(<?php echo $project['id']; ?>)" class="block w-full text-left px-4 py-2 text-sm text-[#0d121b] dark:text-white hover:bg-[#f8f9fc] dark:hover:bg-[#2d3748]">Edit Project</button>
                                        <button onclick="deleteProject(<?php echo $project['id']; ?>)" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-b-lg">Delete Project</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Create Project Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#1a202c] rounded-xl max-w-md w-full p-6 border border-[#e7ebf3] dark:border-[#2a3447]">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-[#0d121b] dark:text-white">Create New Project</h3>
            <button onclick="closeCreateModal()" class="text-[#4c669a] hover:text-[#0d121b] dark:text-[#94a3b8] dark:hover:text-white">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-[#0d121b] dark:text-white mb-1">Project Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-[#cfd7e7] dark:border-[#4a5568] rounded-lg bg-white dark:bg-[#2d3748] text-[#0d121b] dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-[#0d121b] dark:text-white mb-1">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-[#cfd7e7] dark:border-[#4a5568] rounded-lg bg-white dark:bg-[#2d3748] text-[#0d121b] dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#0d121b] dark:text-white mb-1">Client Name (Optional)</label>
                <input type="text" name="client_name" class="w-full px-3 py-2 border border-[#cfd7e7] dark:border-[#4a5568] rounded-lg bg-white dark:bg-[#2d3748] text-[#0d121b] dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-[#0d121b] dark:text-white mb-1">Start Date</label>
                    <input type="date" name="start_date" class="w-full px-3 py-2 border border-[#cfd7e7] dark:border-[#4a5568] rounded-lg bg-white dark:bg-[#2d3748] text-[#0d121b] dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#0d121b] dark:text-white mb-1">End Date</label>
                    <input type="date" name="end_date" class="w-full px-3 py-2 border border-[#cfd7e7] dark:border-[#4a5568] rounded-lg bg-white dark:bg-[#2d3748] text-[#0d121b] dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-[#0d121b] dark:text-white mb-1">Budget Hours (Optional)</label>
                <input type="number" name="budget_hours" min="0" step="0.5" class="w-full px-3 py-2 border border-[#cfd7e7] dark:border-[#4a5568] rounded-lg bg-white dark:bg-[#2d3748] text-[#0d121b] dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeCreateModal()" class="flex-1 px-4 py-2 border border-[#cfd7e7] dark:border-[#4a5568] text-[#4c669a] dark:text-[#94a3b8] rounded-lg hover:bg-[#f8f9fc] dark:hover:bg-[#2d3748] transition-colors">Cancel</button>
                <button type="submit" name="create_project" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition-colors">Create Project</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

function toggleDropdown(projectId) {
    const dropdown = document.getElementById(`dropdown-${projectId}`);
    const allDropdowns = document.querySelectorAll('[id^="dropdown-"]');
    
    // Close all other dropdowns
    allDropdowns.forEach(d => {
        if (d.id !== `dropdown-${projectId}`) {
            d.classList.add('hidden');
        }
    });
    
    dropdown.classList.toggle('hidden');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('[onclick^="toggleDropdown"]')) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(d => d.classList.add('hidden'));
    }
});

// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#projectsTableBody tr');
    
    rows.forEach(row => {
        const projectName = row.dataset.name;
        if (projectName.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Status filter
document.getElementById('statusFilter').addEventListener('change', function() {
    const selectedStatus = this.value;
    const rows = document.querySelectorAll('#projectsTableBody tr');
    
    rows.forEach(row => {
        const rowStatus = row.dataset.status;
        if (!selectedStatus || rowStatus === selectedStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

function editProject(projectId) {
    // Implement edit functionality
    window.location.href = `edit_project.php?id=${projectId}`;
}

function deleteProject(projectId) {
    if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
        window.location.href = `delete_project.php?id=${projectId}`;
    }
}

// Show success message if present
<?php if (isset($_GET['msg'])): ?>
alert('<?php echo addslashes($_GET['msg']); ?>');
<?php endif; ?>
</script>

</body>
</html> endif; ?>
        </div>

        <!-- KPI Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-[#1a202c] rounded-xl p-5 border border-[#e7ebf3] dark:border-[#2a3447] shadow-sm flex flex-col justify-between h-32">
                <div class="flex items-center justify-between">
                    <span class="text-[#4c669a] dark:text-[#94a3b8] text-sm font-medium">Total Projects</span>
                    <div class="size-8 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-lg">folder</span>
                    </div>
                </div>
                <div>
                    <span class="text-3xl font-bold text-[#0d121b] dark:text-white"><?php echo $stats['total_projects']; ?></span>
                </div>
            </div>
            
            <div class="bg-white dark:bg-[#1a202c] rounded-xl p-5 border border-[#e7ebf3] dark:border-[#2a3447] shadow-sm flex flex-col justify-between h-32">
                <div class="flex items-center justify-between">
                    <span class="text-[#4c669a] dark:text-[#94a3b8] text-sm font-medium">Active</span>
                    <div class="size-8 rounded-full bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-emerald-600">
                        <span class="material-symbols-outlined text-lg">bolt</span>
                    </div>
                </div>
                <div>
                    <span class="text-3xl font-bold text-[#0d121b] dark:text-white"><?php echo $stats['active_projects']; ?></span>
                </div>
            </div>
            
            <div class="bg-white dark:bg-[#1a202c] rounded-xl p-5 border border-[#e7ebf3] dark:border-[#2a3447] shadow-sm flex flex-col justify-between h-32">
                <div class="flex items-center justify-between">
                    <span class="text-[#4c669a] dark:text-[#94a3b8] text-sm font-medium">Delayed</span>
                    <div class="size-8 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-600">
                        <span class="material-symbols-outlined text-lg">warning</span>
                    </div>
                </div>
                <div>
                    <span class="text-3xl font-bold text-[#0d121b] dark:text-white"><?php echo $stats['delayed_projects']; ?></span>
                </div>
            </div>
            
            <div class="bg-white dark:bg-[#1a202c] rounded-xl p-5 border border-[#e7ebf3] dark:border-[#2a3447] shadow-sm flex flex-col justify-between h-32">
                <div class="flex items-center justify-between">
                    <span class="text-[#4c669a] dark:text-[#94a3b8] text-sm font-medium">Completed</span>
                    <div class="size-8 rounded-full bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center text-indigo-600">
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                    </div>
                </div>
                <div>
                    <span class="text-3xl font-bold text-[#0d121b] dark:text-white"><?php echo $stats['completed_projects']; ?></span>
                </div>
            </div>
        </div>

        <!-- Projects Data Grid -->
        <div class="bg-white dark:bg-[#1a202c] rounded-xl border border-[#e7ebf3] dark:border-[#2a3447] shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#f8fafc] dark:bg-[#2d3748] border-b border-[#e7ebf3] dark:border-[#4a5568]">
                            <th class="py-4 px-6 text-xs font-semibold text-[#4c669a] dark:text-[#94a3b8] uppercase tracking-wider">Project Name</th>
                            <th class="py-4 px-6 text-xs font-semibold text-[#4c669a] dark:text-[#94a3b8] uppercase tracking-wider">Status</th>
                            <th class="py-4 px-6 text-xs font-semibold text-[#4c669a] dark:text-[#94a3b8] uppercase tracking-wider">Team Leader</th>
                            <th class="py-4 px-6 text-xs font-semibold text-[#4c669a] dark:text-[#94a3b8] uppercase tracking-wider">Deadline</th>
                            <th class="py-4 px-6 text-xs font-semibold text-[#4c669a] dark:text-[#94a3b8] uppercase tracking-wider w-48">Progress</th>
                            <th class="py-4 px-6 text-xs font-semibold text-[#4c669a] dark:text-[#94a3b8] uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e7ebf3] dark:divide-[#4a5568]">
                        <?php while ($project = $projects->fetch_assoc()): ?>
                        <?php 
                            $progress = $project['total_tasks'] > 0 ? round(($project['completed_tasks'] / $project['total_tasks']) * 100) : 0;
                            $status_colors = [
                                'planning' => 'bg-gray-50 dark:bg-gray-900/20 text-gray-600 border-gray-200 dark:border-gray-800',
                                'active' => 'bg-primary/10 text-primary border-primary/20',
                                'on_hold' => 'bg-orange-50 dark:bg-orange-900/20 text-orange-600 border-orange-200 dark:border-orange-800',
                                'completed' => 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 border-emerald-200 dark:border-emerald-800',
                                'cancelled' => 'bg-red-50 dark:bg-red-900/20 text-red-600 border-red-200 dark:border-red-800'
                            ];
                        ?>
                        <tr class="group hover:bg-[#f8f9fc] dark:hover:bg-[#2d3748]/50 transition-colors">
                            <td class="py-4 px-6">
                                <div class="flex flex-col">
                                    <a href="project_detail.php?id=<?php echo $project['id']; ?>" class="text-[#0d121b] dark:text-white font-semibold text-sm hover:text-primary transition-colors">
                                        <?php echo htmlspecialchars($project['name']); ?>
                                    </a>
                                    <span class="text-[#4c669a] dark:text-[#94a3b8] text-xs">
                                        <?php echo $project['client_name'] ? 'Client: ' . htmlspecialchars($project['client_name']) : 'Internal'; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border <?php echo $status_colors[$project['status']]; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-sm text-[#0d121b] dark:text-white font-medium">
                                    <?php echo htmlspecialchars($project['firstname'] . ' ' . $project['lastname']); ?>
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-sm text-[#0d121b] dark:text-white">
                                    <?php echo $project['end_date'] ? date('M j, Y', strtotime($project['end_date'])) : 'No deadline'; ?>
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex flex-col gap-1.5">
                                    <div class="flex justify-between text-xs">
                                        <span class="font-medium text-[#0d121b] dark:text-white"><?php echo $progress; ?>%</span>
                                        <span class="text-[#4c669a] dark:text-[#94a3b8]"><?php echo $project['completed_tasks']; ?>/<?php echo $project['total_tasks']; ?> tasks</span>
                                    </div>
                                    <div class="h-2 w-full bg-[#e7ebf3] dark:bg-[#4a5568] rounded-full overflow-hidden">
                                        <div class="h-full bg-primary rounded-full" style="width: <?php echo $progress; ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="project_detail.php?id=<?php echo $project['id']; ?>" class="text-[#4c669a] hover:text-primary dark:text-[#94a3b8] dark:hover:text-white p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="View Details">
                                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                                    </a>
                                    <a href="team_management.php?project_id=<?php echo $project['id']; ?>" class="text-[#4c669a] hover:text-primary dark:text-[#94a3b8] dark:hover:text-white p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Manage Team">
                                        <span class="material-symbols-outlined text-[20px]">group</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Create Project Modal -->
<div id="createModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-[#1a202c] rounded-xl p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-[#0d121b] dark:text-white">Create New Project</h3>
            <button onclick="closeCreateModal()" class="text-[#4c669a] hover:text-[#0d121b] dark:hover:text-white">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-[#0d121b] dark:text-white mb-2">Project Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-[#e7ebf3] dark:border-[#2a3447] rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-[#2d3748] dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-[#0d121b] dark:text-white mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-[#e7ebf3] dark:border-[#2a3447] rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-[#2d3748] dark:text-white"></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-[#0d121b] dark:text-white mb-2">Client Name (Optional)</label>
                <input type="text" name="client_name" class="w-full px-3 py-2 border border-[#e7ebf3] dark:border-[#2a3447] rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-[#2d3748] dark:text-white">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-[#0d121b] dark:text-white mb-2">Start Date</label>
                    <input type="date" name="start_date" class="w-full px-3 py-2 border border-[#e7ebf3] dark:border-[#2a3447] rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-[#2d3748] dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-[#0d121b] dark:text-white mb-2">End Date</label>
                    <input type="date" name="end_date" class="w-full px-3 py-2 border border-[#e7ebf3] dark:border-[#2a3447] rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-[#2d3748] dark:text-white">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-[#0d121b] dark:text-white mb-2">Budget Hours</label>
                <input type="number" name="budget_hours" step="0.5" class="w-full px-3 py-2 border border-[#e7ebf3] dark:border-[#2a3447] rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-[#2d3748] dark:text-white">
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeCreateModal()" class="flex-1 px-4 py-2 border border-[#e7ebf3] dark:border-[#2a3447] text-[#4c669a] dark:text-[#94a3b8] rounded-lg hover:bg-[#f8f9fc] dark:hover:bg-[#2d3748] transition-colors">
                    Cancel
                </button>
                <button type="submit" name="create_project" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Create Project
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
    document.getElementById('createModal').classList.add('flex');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
    document.getElementById('createModal').classList.remove('flex');
}

// Close modal when clicking outside
document.getElementById('createModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateModal();
    }
});
</script>
</body>
</html>