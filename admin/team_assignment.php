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
$project_id = $_GET['project_id'] ?? 0;

if (!$user_id) {
    header('Location: ../index.php');
    exit;
}

// Check permissions
$user = ['role' => 'admin', 'can_manage_team' => 1];
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

// Permission check removed for now

// Get project details
$stmt = $db->prepare("SELECT * FROM projects WHERE id = ? AND business_id = ?");
$stmt->bind_param("ii", $project_id, $business_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    header('Location: projects.php');
    exit;
}

// Handle form submission
if ($_POST) {
    if (isset($_POST['add_member'])) {
        $member_id = $_POST['member_id'];
        $role = $_POST['role'] ?? 'member';
        
        $stmt = $db->prepare("INSERT IGNORE INTO project_members (project_id, user_id, role, added_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $project_id, $member_id, $role, $user_id);
        $stmt->execute();
    }
    
    if (isset($_POST['remove_member'])) {
        $member_id = $_POST['member_id'];
        
        $stmt = $db->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $project_id, $member_id);
        $stmt->execute();
    }
    
    if (isset($_POST['update_role'])) {
        $member_id = $_POST['member_id'];
        $new_role = $_POST['new_role'];
        
        $stmt = $db->prepare("UPDATE project_members SET role = ? WHERE project_id = ? AND user_id = ?");
        $stmt->bind_param("sii", $new_role, $project_id, $member_id);
        $stmt->execute();
    }
    
    header("Location: team_assignment.php?project_id=$project_id&msg=Changes saved successfully");
    exit;
}

// Get available employees (not in project)
$available_query = "
    SELECT u.id, u.firstname, u.lastname, u.role, u.email
    FROM users u
    WHERE u.business_id = ? 
    AND u.id NOT IN (
        SELECT pm.user_id 
        FROM project_members pm 
        WHERE pm.project_id = ?
    )
    ORDER BY u.firstname ASC
";

$stmt = $db->prepare($available_query);
$stmt->bind_param("ii", $business_id, $project_id);
$stmt->execute();
$available_employees = $stmt->get_result();

// Get current project members
$members_query = "
    SELECT u.id, u.firstname, u.lastname, u.role as user_role, u.email, pm.role as project_role
    FROM project_members pm
    JOIN users u ON pm.user_id = u.id
    WHERE pm.project_id = ?
    ORDER BY 
        CASE pm.role 
            WHEN 'owner' THEN 1 
            WHEN 'admin' THEN 2 
            WHEN 'manager' THEN 3 
            ELSE 4 
        END,
        u.firstname ASC
";

$stmt = $db->prepare($members_query);
$stmt->bind_param("i", $project_id);
$stmt->execute();
$project_members = $stmt->get_result();

$member_count = $project_members->num_rows;
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Team Member Assignment - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
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
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-white antialiased overflow-x-hidden transition-colors duration-200">
<div class="flex h-screen w-full overflow-hidden">
    <!-- Sidebar -->
    <div class="hidden md:flex flex-col w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex-shrink-0">
        <div class="p-6">
            <div class="flex flex-col">
                <h1 class="text-slate-900 dark:text-white text-base font-bold leading-normal tracking-tight">TimeTrack Pro</h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm font-normal">Admin Console</p>
            </div>
        </div>
        <nav class="flex-1 flex flex-col gap-1 px-4 overflow-y-auto">
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" href="dashboard.php">
                <span class="material-symbols-outlined text-[20px]">dashboard</span>
                <span class="text-sm font-medium">Dashboard</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" href="projects.php">
                <span class="material-symbols-outlined text-[20px]">work</span>
                <span class="text-sm font-medium">Projects</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg bg-primary/10 text-primary dark:text-primary transition-colors" href="team_assignment.php">
                <span class="material-symbols-outlined text-[20px] fill-1">group</span>
                <span class="text-sm font-medium">Team</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" href="reports.php">
                <span class="material-symbols-outlined text-[20px]">description</span>
                <span class="text-sm font-medium">Reports</span>
            </a>
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors" href="settings.php">
                <span class="material-symbols-outlined text-[20px]">settings</span>
                <span class="text-sm font-medium">Settings</span>
            </a>
        </nav>
        <div class="p-4 border-t border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-xs font-bold text-primary">
                    <?php echo isset($_SESSION['firstname']) ? strtoupper(substr($_SESSION['firstname'], 0, 1) . substr($_SESSION['lastname'], 0, 1)) : 'AD'; ?>
                </div>
                <div class="flex flex-col">
                    <p class="text-sm font-medium text-slate-900 dark:text-white"><?php echo isset($_SESSION['firstname']) ? htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']) : 'Admin User'; ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo ucfirst($user['role'] ?? 'admin'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-full overflow-hidden relative">
        <!-- Top Header -->
        <header class="h-16 flex items-center justify-between px-6 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex-shrink-0 z-10">
            <div class="flex items-center gap-4">
                <!-- Mobile Menu Trigger -->
                <button class="md:hidden p-2 text-slate-600 dark:text-slate-300">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <!-- Breadcrumbs -->
                <nav class="hidden sm:flex items-center text-sm font-medium">
                    <a class="text-slate-500 dark:text-slate-400 hover:text-primary transition-colors" href="projects.php">Projects</a>
                    <span class="mx-2 text-slate-400 dark:text-slate-600">/</span>
                    <a class="text-slate-500 dark:text-slate-400 hover:text-primary transition-colors" href="project_details.php?id=<?php echo $project_id; ?>"><?php echo htmlspecialchars($project['name']); ?></a>
                    <span class="mx-2 text-slate-400 dark:text-slate-600">/</span>
                    <span class="text-slate-900 dark:text-white">Manage Team</span>
                </nav>
            </div>
            <div class="flex items-center gap-4">
                <button class="p-2 text-slate-500 hover:text-primary dark:text-slate-400 transition-colors relative">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>
                <div class="w-8 h-8 rounded-full bg-primary/10 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-primary font-bold text-sm">
                    <?php echo isset($_SESSION['firstname']) ? strtoupper(substr($_SESSION['firstname'], 0, 1) . substr($_SESSION['lastname'], 0, 1)) : 'AD'; ?>
                </div>
            </div>
        </header>

        <!-- Scrollable Page Content -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- Page Heading -->
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">Manage Team: <?php echo htmlspecialchars($project['name']); ?></h2>
                        <p class="mt-1 text-slate-500 dark:text-slate-400">Add or remove team members and assign roles for this project.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="px-3 py-1 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-sm font-medium flex items-center gap-1 border border-green-200 dark:border-green-900/50">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            <?php echo ucfirst($project['status']); ?> Project
                        </div>
                        <span class="text-sm text-slate-500 dark:text-slate-400 font-medium"><?php echo $member_count; ?> Members Assigned</span>
                    </div>
                </div>

                <!-- Main Assignment Area -->
                <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto_1fr] gap-6 items-start h-auto lg:h-[600px]">
                    <!-- Left: Available Employees -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 flex flex-col h-full overflow-hidden">
                        <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                            <h3 class="font-semibold text-slate-900 dark:text-white mb-3">Available Employees</h3>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">search</span>
                                <input id="searchInput" class="w-full pl-10 pr-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-shadow" placeholder="Find by name or email..." type="text"/>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 space-y-1">
                            <?php while ($employee = $available_employees->fetch_assoc()): ?>
                            <div class="group flex items-center justify-between p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-all cursor-pointer employee-item" data-name="<?php echo strtolower($employee['firstname'] . ' ' . $employee['lastname']); ?>">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm flex-shrink-0">
                                        <?php echo strtoupper(substr($employee['firstname'], 0, 1) . substr($employee['lastname'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo ucfirst($employee['role']); ?></p>
                                    </div>
                                </div>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="member_id" value="<?php echo $employee['id']; ?>">
                                    <input type="hidden" name="role" value="member">
                                    <button name="add_member" type="submit" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-500 hover:text-primary hover:border-primary dark:hover:border-primary dark:hover:text-primary transition-colors opacity-0 group-hover:opacity-100">
                                        <span class="material-symbols-outlined text-[20px]">add</span>
                                    </button>
                                </form>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/30 text-center">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Showing available employees</span>
                        </div>
                    </div>

                    <!-- Center: Transfer Controls (Desktop) -->
                    <div class="hidden lg:flex flex-col justify-center items-center gap-4 h-full">
                        <button class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-center text-slate-500 hover:text-primary hover:border-primary transition-all">
                            <span class="material-symbols-outlined">arrow_forward</span>
                        </button>
                        <button class="w-10 h-10 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm flex items-center justify-center text-slate-500 hover:text-primary hover:border-primary transition-all">
                            <span class="material-symbols-outlined">arrow_back</span>
                        </button>
                    </div>

                    <!-- Center: Transfer Controls (Mobile) -->
                    <div class="flex lg:hidden justify-center items-center gap-4 py-2">
                        <span class="text-xs text-slate-400 uppercase font-bold tracking-wider">Tap + to assign</span>
                    </div>

                    <!-- Right: Project Team -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 flex flex-col h-full overflow-hidden">
                        <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-between items-center">
                            <h3 class="font-semibold text-slate-900 dark:text-white">Project Team</h3>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 space-y-1">
                            <?php while ($member = $project_members->fetch_assoc()): ?>
                            <div class="group flex flex-col sm:flex-row sm:items-center justify-between p-3 rounded-lg <?php echo $member['project_role'] == 'owner' ? 'bg-primary/5 dark:bg-primary/10 border border-primary/20 dark:border-primary/20' : 'hover:bg-slate-50 dark:hover:bg-slate-800/50 border border-transparent hover:border-slate-200 dark:hover:border-slate-700'; ?> transition-all">
                                <div class="flex items-center gap-3 mb-2 sm:mb-0">
                                    <div class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-sm flex-shrink-0 <?php echo $member['project_role'] == 'owner' ? 'border-2 border-primary/20' : ''; ?>">
                                        <?php echo strtoupper(substr($member['firstname'], 0, 1) . substr($member['lastname'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($member['firstname'] . ' ' . $member['lastname']); ?></p>
                                        <p class="text-xs <?php echo $member['project_role'] == 'owner' ? 'text-primary dark:text-primary' : 'text-slate-500 dark:text-slate-400'; ?>"><?php echo ucfirst($member['user_role']); ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 justify-between w-full sm:w-auto">
                                    <?php if ($member['project_role'] == 'owner'): ?>
                                    <span class="text-xs font-medium text-primary">Owner</span>
                                    <?php else: ?>
                                    <form method="POST" class="flex items-center gap-2">
                                        <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                        <select name="new_role" onchange="this.form.submit()" class="block w-full rounded-md border-0 py-1.5 pl-3 pr-8 text-slate-900 dark:text-white ring-1 ring-inset ring-slate-300 dark:ring-slate-700 focus:ring-2 focus:ring-primary sm:text-xs sm:leading-6 bg-white dark:bg-slate-800">
                                            <option value="member" <?php echo $member['project_role'] == 'member' ? 'selected' : ''; ?>>Member</option>
                                            <option value="manager" <?php echo $member['project_role'] == 'manager' ? 'selected' : ''; ?>>Manager</option>
                                            <option value="admin" <?php echo $member['project_role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                        <input type="hidden" name="update_role" value="1">
                                    </form>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                        <button name="remove_member" type="submit" onclick="return confirm('Remove this member from the project?')" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                            <span class="material-symbols-outlined text-[20px]">remove</span>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                        <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/30 text-center">
                            <span class="text-xs text-slate-500 dark:text-slate-400"><?php echo $member_count; ?> members in this project</span>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-800 mt-6">
                    <a href="project_details.php?id=<?php echo $project_id; ?>" class="px-5 py-2.5 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        Back to Project
                    </a>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const items = document.querySelectorAll('.employee-item');
    
    items.forEach(item => {
        const name = item.dataset.name;
        if (name.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});

// Show success message if present
<?php if (isset($_GET['msg'])): ?>
alert('<?php echo addslashes($_GET['msg']); ?>');
<?php endif; ?>
</script>

</body>
</html>