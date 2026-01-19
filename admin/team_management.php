<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'];
$project_id = $_GET['project_id'] ?? null;

// Get user permissions
$stmt = $db->prepare("SELECT role, can_manage_team FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user['can_manage_team'] && !in_array($user['role'], ['admin', 'supervisor', 'team_leader'])) {
    header('Location: projects.php?error=Access denied');
    exit;
}

// Handle team member addition
if (isset($_POST['add_member']) && $project_id) {
    $member_user_id = $_POST['user_id'];
    $role = $_POST['role'];
    
    $stmt = $db->prepare("INSERT INTO project_members (project_id, user_id, role, added_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisi", $project_id, $member_user_id, $role, $user_id);
    
    if ($stmt->execute()) {
        header("Location: team_management.php?project_id=$project_id&msg=Team member added successfully");
        exit;
    }
}

// Handle team member removal
if (isset($_POST['remove_member']) && $project_id) {
    $member_id = $_POST['member_id'];
    
    $stmt = $db->prepare("DELETE FROM project_members WHERE id = ? AND project_id = ?");
    $stmt->bind_param("ii", $member_id, $project_id);
    $stmt->execute();
    
    header("Location: team_management.php?project_id=$project_id&msg=Team member removed successfully");
    exit;
}

// Handle role update
if (isset($_POST['update_role']) && $project_id) {
    $member_id = $_POST['member_id'];
    $new_role = $_POST['role'];
    
    $stmt = $db->prepare("UPDATE project_members SET role = ? WHERE id = ? AND project_id = ?");
    $stmt->bind_param("sii", $new_role, $member_id, $project_id);
    $stmt->execute();
    
    header("Location: team_management.php?project_id=$project_id&msg=Role updated successfully");
    exit;
}

// Get project details if project_id is provided
$project = null;
if ($project_id) {
    $stmt = $db->prepare("SELECT * FROM projects WHERE id = ? AND business_id = ?");
    $stmt->bind_param("ii", $project_id, $business_id);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
}

// Get available employees
$available_query = "
    SELECT u.id, u.firstname, u.lastname, u.email, u.role as user_role
    FROM users u 
    WHERE u.business_id = ?";

if ($project_id) {
    $available_query .= " AND u.id NOT IN (
        SELECT user_id FROM project_members WHERE project_id = ?
    )";
}

$available_query .= " ORDER BY u.firstname, u.lastname";

$stmt = $db->prepare($available_query);
if ($project_id) {
    $stmt->bind_param("ii", $business_id, $project_id);
} else {
    $stmt->bind_param("i", $business_id);
}
$stmt->execute();
$available_employees = $stmt->get_result();

// Get current team members if project_id is provided
$team_members = null;
if ($project_id) {
    $team_query = "
        SELECT pm.*, u.firstname, u.lastname, u.email, u.role as user_role
        FROM project_members pm
        JOIN users u ON pm.user_id = u.id
        WHERE pm.project_id = ?
        ORDER BY pm.role, u.firstname
    ";
    
    $stmt = $db->prepare($team_query);
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $team_members = $stmt->get_result();
}

// Get all projects for selection
$projects_query = "SELECT id, name FROM projects WHERE business_id = ? ORDER BY name";
$stmt = $db->prepare($projects_query);
$stmt->bind_param("i", $business_id);
$stmt->execute();
$all_projects = $stmt->get_result();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Team Member Assignment - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
            <a class="flex items-center gap-3 px-3 py-2 rounded-lg bg-primary/10 text-primary dark:text-primary transition-colors" href="team_management.php">
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
                <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-600 dark:text-slate-300">
                    <?php echo strtoupper(substr($_SESSION['firstname'], 0, 1) . substr($_SESSION['lastname'], 0, 1)); ?>
                </div>
                <div class="flex flex-col">
                    <p class="text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo ucfirst($user['role']); ?></p>
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
                    <?php if ($project): ?>
                    <span class="mx-2 text-slate-400 dark:text-slate-600">/</span>
                    <a class="text-slate-500 dark:text-slate-400 hover:text-primary transition-colors" href="project_detail.php?id=<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['name']); ?></a>
                    <?php endif; ?>
                    <span class="mx-2 text-slate-400 dark:text-slate-600">/</span>
                    <span class="text-slate-900 dark:text-white">Manage Team</span>
                </nav>
            </div>
            <div class="flex items-center gap-4">
                <button class="p-2 text-slate-500 hover:text-primary dark:text-slate-400 transition-colors relative">
                    <span class="material-symbols-outlined">notifications</span>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>
                <div class="w-8 h-8 rounded-full bg-cover bg-center border border-slate-200 dark:border-slate-700" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAykLOkp6vRUnqsmFXL4MhHumx1fx5sSY3mkZRbdkIwPC8ZIigUuFQWl1ccCgARp1CnsFjVn5xIX5ycBaSDI875JxJVKuoT_GVS_uME7jOzA0m_UcZT-XJ1t_vFRLLLJUoubYU7OR7tJF1QYJmPwvw6tBe6Yh5fX2GwJNLW9TB9lCIGCOL39sf54aDNWUmdE82YV2sWMiVHsThWrrSrqOsTSdBxxgaDT6M6H4ZwVquoC_DlchFMkvAnf-waogzdjPRfSovZ32exQeM");'></div>
            </div>
        </header>

        <!-- Scrollable Page Content -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8">
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- Page Heading -->
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
                            <?php echo $project ? 'Manage Team: ' . htmlspecialchars($project['name']) : 'Team Management'; ?>
                        </h2>
                        <p class="mt-1 text-slate-500 dark:text-slate-400">
                            <?php echo $project ? 'Add or remove team members and assign roles for this project.' : 'Select a project to manage its team members.'; ?>
                        </p>
                    </div>
                    <?php if ($project): ?>
                    <div class="flex items-center gap-3">
                        <div class="px-3 py-1 rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-sm font-medium flex items-center gap-1 border border-green-200 dark:border-green-900/50">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            Active Project
                        </div>
                        <span class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                            <?php echo $team_members ? $team_members->num_rows : 0; ?> Members Assigned
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Project Selection -->
                <?php if (!$project): ?>
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Select Project</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php while ($proj = $all_projects->fetch_assoc()): ?>
                        <a href="team_management.php?project_id=<?php echo $proj['id']; ?>" class="p-4 border border-slate-200 dark:border-slate-700 rounded-lg hover:border-primary hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            <h4 class="font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($proj['name']); ?></h4>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Click to manage team</p>
                        </a>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php else: ?>

                <!-- Main Assignment Area -->
                <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto_1fr] gap-6 items-start h-auto lg:h-[600px]">
                    <!-- Left: Available Employees -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 flex flex-col h-full overflow-hidden">
                        <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                            <h3 class="font-semibold text-slate-900 dark:text-white mb-3">Available Employees</h3>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[20px]">search</span>
                                <input class="w-full pl-10 pr-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-shadow" placeholder="Find by name or email..." type="text"/>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 space-y-1">
                            <?php while ($employee = $available_employees->fetch_assoc()): ?>
                            <div class="group flex items-center justify-between p-3 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-all cursor-pointer">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-sm font-bold text-slate-600 dark:text-slate-300">
                                        <?php echo strtoupper(substr($employee['firstname'], 0, 1) . substr($employee['lastname'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400"><?php echo ucfirst($employee['user_role']); ?></p>
                                    </div>
                                </div>
                                <button onclick="addMember(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>')" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-500 hover:text-primary hover:border-primary dark:hover:border-primary dark:hover:text-primary transition-colors opacity-0 group-hover:opacity-100">
                                    <span class="material-symbols-outlined text-[20px]">add</span>
                                </button>
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
                        <span class="text-xs text-slate-400 uppercase font-bold tracking-wider">Drag or tap + to assign</span>
                    </div>

                    <!-- Right: Project Team -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 flex flex-col h-full overflow-hidden">
                        <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-between items-center">
                            <h3 class="font-semibold text-slate-900 dark:text-white">Project Team</h3>
                            <button class="text-xs font-medium text-primary hover:text-primary/80">Select All</button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 space-y-1">
                            <?php if ($team_members && $team_members->num_rows > 0): ?>
                                <?php while ($member = $team_members->fetch_assoc()): ?>
                                <div class="group flex flex-col sm:flex-row sm:items-center justify-between p-3 rounded-lg <?php echo $member['role'] === 'owner' ? 'bg-primary/5 dark:bg-primary/10 border border-primary/20 dark:border-primary/20' : 'hover:bg-slate-50 dark:hover:bg-slate-800/50 border border-transparent hover:border-slate-200 dark:hover:border-slate-700'; ?> transition-all">
                                    <div class="flex items-center gap-3 mb-2 sm:mb-0">
                                        <div class="w-10 h-10 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-sm font-bold text-slate-600 dark:text-slate-300 <?php echo $member['role'] === 'owner' ? 'border-2 border-primary/20' : ''; ?>">
                                            <?php echo strtoupper(substr($member['firstname'], 0, 1) . substr($member['lastname'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($member['firstname'] . ' ' . $member['lastname']); ?></p>
                                            <p class="text-xs <?php echo $member['role'] === 'owner' ? 'text-primary dark:text-primary' : 'text-slate-500 dark:text-slate-400'; ?>"><?php echo ucfirst($member['role']); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 justify-between w-full sm:w-auto">
                                        <?php if ($member['role'] !== 'owner'): ?>
                                        <form method="POST" class="flex items-center gap-2">
                                            <input type="hidden" name="update_role" value="1">
                                            <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                            <select name="role" onchange="this.form.submit()" class="block w-full rounded-md border-0 py-1.5 pl-3 pr-8 text-slate-900 dark:text-white ring-1 ring-inset ring-slate-300 dark:ring-slate-700 focus:ring-2 focus:ring-primary sm:text-xs sm:leading-6 bg-white dark:bg-slate-800">
                                                <option value="contributor" <?php echo $member['role'] === 'contributor' ? 'selected' : ''; ?>>Contributor</option>
                                                <option value="viewer" <?php echo $member['role'] === 'viewer' ? 'selected' : ''; ?>>Viewer</option>
                                                <option value="manager" <?php echo $member['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                            </select>
                                            <button type="button" onclick="removeMember(<?php echo $member['id']; ?>)" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                                <span class="material-symbols-outlined text-[20px]">remove</span>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <span class="text-xs font-medium text-primary bg-primary/10 px-2 py-1 rounded">Project Owner</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                            <div class="flex flex-col items-center justify-center py-8 text-center">
                                <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                                    <span class="material-symbols-outlined text-slate-400">group_add</span>
                                </div>
                                <p class="text-sm text-slate-500 dark:text-slate-400">No team members assigned yet</p>
                                <p class="text-xs text-slate-400 mt-1">Add members from the left panel</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/30 text-center">
                            <span class="text-xs text-slate-500 dark:text-slate-400"><?php echo $team_members ? $team_members->num_rows : 0; ?> members in this project</span>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200 dark:border-slate-800 mt-6">
                    <a href="projects.php" class="px-5 py-2.5 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                        Back to Projects
                    </a>
                    <a href="project_detail.php?id=<?php echo $project_id; ?>" class="px-5 py-2.5 rounded-lg text-sm font-medium bg-primary text-white hover:bg-primary/90 shadow-sm shadow-primary/30 flex items-center gap-2 transition-colors">
                        <span class="material-symbols-outlined text-[20px]">visibility</span>
                        View Project Details
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<!-- Add Member Modal -->
<div id="addMemberModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 w-full max-w-md mx-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Add Team Member</h3>
            <button onclick="closeAddMemberModal()" class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="add_member" value="1">
            <input type="hidden" name="user_id" id="selected_user_id">
            
            <div>
                <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Selected Member</label>
                <div class="p-3 bg-slate-50 dark:bg-slate-800 rounded-lg">
                    <span id="selected_member_name" class="text-sm font-medium text-slate-900 dark:text-white"></span>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-900 dark:text-white mb-2">Role</label>
                <select name="role" required class="w-full px-3 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent dark:bg-slate-800 dark:text-white">
                    <option value="contributor">Contributor</option>
                    <option value="viewer">Viewer</option>
                    <option value="manager">Manager</option>
                </select>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closeAddMemberModal()" class="flex-1 px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    Cancel
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                    Add Member
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function addMember(userId, memberName) {
    document.getElementById('selected_user_id').value = userId;
    document.getElementById('selected_member_name').textContent = memberName;
    document.getElementById('addMemberModal').classList.remove('hidden');
    document.getElementById('addMemberModal').classList.add('flex');
}

function closeAddMemberModal() {
    document.getElementById('addMemberModal').classList.add('hidden');
    document.getElementById('addMemberModal').classList.remove('flex');
}

function removeMember(memberId) {
    if (confirm('Are you sure you want to remove this team member?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="remove_member" value="1">
            <input type="hidden" name="member_id" value="${memberId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
document.getElementById('addMemberModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddMemberModal();
    }
});
</script>
</body>
</html>