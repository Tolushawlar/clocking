<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$business_id = $_SESSION['business_id'];

// Check if user can manage projects
$stmt = $db->prepare("SELECT can_manage_projects, is_team_leader, is_supervisor, category FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_perms = $stmt->get_result()->fetch_assoc();

$can_manage = $user_perms['can_manage_projects'] || $user_perms['category'] === 'admin';

// Get project statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_projects,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_projects,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects,
        SUM(CASE WHEN status = 'on_hold' OR (end_date < CURDATE() AND status != 'completed') THEN 1 ELSE 0 END) as delayed_projects
    FROM projects 
    WHERE business_id = ?
";
$stmt = $db->prepare($stats_query);
$stmt->bind_param("i", $business_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get projects with team leader info
$projects_query = "
    SELECT p.*, 
           CONCAT(u.firstname, ' ', u.lastname) as created_by_name,
           (SELECT COUNT(*) FROM project_team pt WHERE pt.project_id = p.id) as team_count,
           (SELECT AVG(pp.estimated_hours) FROM project_phases pp WHERE pp.project_id = p.id) as avg_phase_hours
    FROM projects p
    LEFT JOIN users u ON p.created_by = u.id
    WHERE p.business_id = ?
    ORDER BY p.created_at DESC
";
$stmt = $db->prepare($projects_query);
$stmt->bind_param("i", $business_id);
$stmt->execute();
$projects = $stmt->get_result();
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
            <a class="text-[#4c669a] dark:text-[#94a3b8] hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="../user/dashboard.php">Dashboard</a>
            <a class="text-[#4c669a] dark:text-[#94a3b8] hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="#">Time Logs</a>
            <a class="text-primary text-sm font-bold leading-normal" href="#">Projects</a>
            <a class="text-[#4c669a] dark:text-[#94a3b8] hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="#">Reports</a>
            <a class="text-[#4c669a] dark:text-[#94a3b8] hover:text-primary dark:hover:text-primary transition-colors text-sm font-medium leading-normal" href="#">Settings</a>
        </nav>
        <div class="flex items-center gap-4">
            <button class="flex items-center justify-center rounded-lg size-10 hover:bg-[#f8f9fc] dark:hover:bg-[#2d3748] text-[#4c669a] dark:text-[#94a3b8] transition-colors relative">
                <span class="material-symbols-outlined">notifications</span>
                <span class="absolute top-2 right-2 size-2 rounded-full bg-red-500 border border-white dark:border-[#1a202c]"></span>
            </button>
            <div class="h-8 w-[1px] bg-[#e7ebf3] dark:bg-[#2a3447] mx-1"></div>
            <div class="flex items-center gap-3">
                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-full size-9 shrink-0 ring-2 ring-white dark:ring-[#1a202c] flex items-center justify-center text-white font-semibold text-sm">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 2)); ?>
                </div>
                <div class="hidden lg:block text-left">
                    <p class="text-sm font-medium text-[#0d121b] dark:text-white"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                    <p class="text-xs text-[#4c669a] dark:text-[#94a3b8]"><?php echo ucfirst($_SESSION['category']); ?></p>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 w-full max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumbs -->
        <div class="mb-6 flex items-center gap-2 text-sm">
            <a class="text-[#4c669a] dark:text-[#94a3b8] hover:text-primary transition-colors" href="../user/dashboard.php">Home</a>
            <span class="text-[#cfd7e7] dark:text-[#4b5563]">/</span>
            <span class="text-[#0d121b] dark:text-white font-medium">Projects</span>
        </div>

        <!-- Header & Actions -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-[#0d121b] dark:text-white mb-2">Projects</h1>
                <p class="text-[#4c669a] dark:text-[#94a3b8] text-base">Manage ongoing initiatives, track progress, and assign resources.</p>
            </div>
            <?php if ($can_manage): ?>
            <button class="flex items-center justify-center gap-2 bg-primary hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg shadow-sm hover:shadow-md transition-all font-medium text-sm whitespace-nowrap">
                <span class="material-symbols-outlined text-[20px]">add</span>
                <span>Create New Project</span>
            </button>
            <?php endif; ?>
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
                    <span class="ml-2 text-xs font-medium text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-1.5 py-0.5 rounded">+2%</span>
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
                    <span class="ml-2 text-xs font-medium text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-1.5 py-0.5 rounded">+5%</span>
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
                    <span class="ml-2 text-xs font-medium text-red-600 bg-red-50 dark:bg-red-900/20 px-1.5 py-0.5 rounded">-1%</span>
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
                    <span class="ml-2 text-xs font-medium text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-1.5 py-0.5 rounded">+12%</span>
                </div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white dark:bg-[#1a202c] p-4 rounded-xl border border-[#e7ebf3] dark:border-[#2a3447] shadow-sm mb-6 flex flex-col lg:flex-row gap-4 items-center justify-between">
            <div class="relative w-full lg:w-96">
                <input class="w-full h-10 pl-10 pr-4 rounded-lg border border-[#cfd7e7] dark:border-[#4a5568] bg-[#f8f9fc] dark:bg-[#2d3748] text-[#0d121b] dark:text-white text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder:text-[#4c669a] outline-none transition-all" placeholder="Search by project name..." type="text"/>
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#4c669a] text-[20px]">search</span>
            </div>
            <div class="flex items-center gap-3 w-full lg:w-auto overflow-x-auto pb-2 lg:pb-0">
                <select class="h-10 pl-3 pr-8 rounded-lg border border-[#cfd7e7] dark:border-[#4a5568] bg-white dark:bg-[#2d3748] text-[#0d121b] dark:text-white text-sm focus:ring-primary focus:border-primary outline-none cursor-pointer">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                </select>
                <select class="h-10 pl-3 pr-8 rounded-lg border border-[#cfd7e7] dark:border-[#4a5568] bg-white dark:bg-[#2d3748] text-[#0d121b] dark:text-white text-sm focus:ring-primary focus:border-primary outline-none cursor-pointer">
                    <option value="">Team Leader</option>
                    <option value="sarah">Sarah J.</option>
                    <option value="mike">Mike T.</option>
                </select>
                <button class="h-10 px-4 rounded-lg border border-[#cfd7e7] dark:border-[#4a5568] bg-white dark:bg-[#2d3748] text-[#0d121b] dark:text-white text-sm hover:bg-[#f8f9fc] dark:hover:bg-[#4a5568] transition-colors flex items-center gap-2 whitespace-nowrap">
                    <span class="material-symbols-outlined text-[18px]">filter_list</span>
                    <span>More Filters</span>
                </button>
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
                        <?php if ($projects->num_rows > 0): ?>
                            <?php while ($project = $projects->fetch_assoc()): ?>
                            <tr class="group hover:bg-[#f8f9fc] dark:hover:bg-[#2d3748]/50 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex flex-col">
                                        <span class="text-[#0d121b] dark:text-white font-semibold text-sm"><?php echo htmlspecialchars($project['name']); ?></span>
                                        <span class="text-[#4c669a] dark:text-[#94a3b8] text-xs">
                                            <?php echo $project['client_name'] ? 'Client: ' . htmlspecialchars($project['client_name']) : 'Internal'; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <?php
                                    $status_colors = [
                                        'active' => 'bg-primary/10 text-primary border-primary/20',
                                        'planning' => 'bg-orange-50 text-orange-600 border-orange-200 dark:bg-orange-900/20 dark:text-orange-400 dark:border-orange-800',
                                        'completed' => 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800',
                                        'on_hold' => 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700'
                                    ];
                                    $status_class = $status_colors[$project['status']] ?? $status_colors['planning'];
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $status_class; ?> border">
                                        <?php echo ucfirst(str_replace('_', ' ', $project['status'])); ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="size-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                                            <?php echo strtoupper(substr($project['created_by_name'], 0, 2)); ?>
                                        </div>
                                        <span class="text-sm text-[#0d121b] dark:text-white font-medium"><?php echo htmlspecialchars($project['created_by_name']); ?></span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="text-sm text-[#0d121b] dark:text-white">
                                        <?php echo $project['end_date'] ? date('M j, Y', strtotime($project['end_date'])) : 'TBD'; ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex justify-between text-xs">
                                            <span class="font-medium text-[#0d121b] dark:text-white">75%</span>
                                        </div>
                                        <div class="h-2 w-full bg-[#e7ebf3] dark:bg-[#4a5568] rounded-full overflow-hidden">
                                            <div class="h-full bg-primary rounded-full" style="width: 75%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <button class="text-[#4c669a] hover:text-primary dark:text-[#94a3b8] dark:hover:text-white p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                        <span class="material-symbols-outlined text-[20px]">more_vert</span>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="size-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-slate-400">folder_open</span>
                                        </div>
                                        <p class="text-slate-500 dark:text-slate-400">No projects found</p>
                                        <?php if ($can_manage): ?>
                                        <button class="text-primary hover:underline text-sm font-medium">Create your first project</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>
</body>
</html>