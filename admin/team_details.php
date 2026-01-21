<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$team_id = $_GET['id'] ?? 0;
$business_id = $_SESSION['business_id'];

// Handle form submissions
if ($_POST) {
    if (isset($_POST['edit_team'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $leader_id = $_POST['leader_id'];

        // Get current team leader to reset their role
        $stmt = $db->prepare("SELECT team_leader_id FROM teams WHERE id = ? AND business_id = ?");
        $stmt->bind_param("ii", $team_id, $business_id);
        $stmt->execute();
        $current_team = $stmt->get_result()->fetch_assoc();

        // Reset old team leader role if different from new one
        if ($current_team && $current_team['team_leader_id'] && $current_team['team_leader_id'] != $leader_id) {
            $stmt = $db->prepare("UPDATE users SET user_role = NULL WHERE id = ?");
            $stmt->bind_param("i", $current_team['team_leader_id']);
            $stmt->execute();
        }

        // Update team
        $stmt = $db->prepare("UPDATE teams SET name = ?, description = ?, team_leader_id = ? WHERE id = ? AND business_id = ?");
        $stmt->bind_param("ssiii", $name, $description, $leader_id, $team_id, $business_id);
        $stmt->execute();

        // Set new team leader role
        $stmt = $db->prepare("UPDATE users SET user_role = 'team_leader' WHERE id = ?");
        $stmt->bind_param("i", $leader_id);
        $stmt->execute();

        header('Location: team_details.php?id=' . $team_id . '&msg=Team updated successfully');
        exit;
    }

    if (isset($_POST['add_member'])) {
        $member_ids = $_POST['member_ids'] ?? [];

        if (!empty($member_ids)) {
            // Get a valid user ID for added_by
            $user_stmt = $db->prepare("SELECT id FROM users WHERE business_id = ? LIMIT 1");
            $user_stmt->bind_param("i", $business_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result()->fetch_assoc();
            $added_by = $user_result ? $user_result['id'] : 1;
            
            $added_count = 0;
            foreach ($member_ids as $member_id) {
                $member_id = (int)$member_id;

                $stmt = $db->prepare("INSERT IGNORE INTO team_members (team_id, user_id, added_by) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $team_id, $member_id, $added_by);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $added_count++;
                }

                $stmt = $db->prepare("UPDATE users SET user_role = 'team_member' WHERE id = ?");
                $stmt->bind_param("i", $member_id);
                $stmt->execute();
            }

            $msg = $added_count > 1 ? "$added_count members added successfully" : "Member added successfully";
            header('Location: team_details.php?id=' . $team_id . '&msg=' . urlencode($msg));
            exit;
        }
    }

    if (isset($_POST['create_project'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        $stmt = $db->prepare("INSERT INTO projects (business_id, team_id, name, description, start_date, end_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, 'active', ?)");
        $stmt->bind_param("iissssi", $business_id, $team_id, $name, $description, $start_date, $end_date, $business_id);
        $stmt->execute();

        header('Location: team_details.php?id=' . $team_id . '&msg=Project created successfully');
        exit;
    }
}

// Get team details
$stmt = $db->prepare("SELECT t.*, u.firstname as leader_firstname, u.lastname as leader_lastname FROM teams t LEFT JOIN users u ON t.team_leader_id = u.id WHERE t.id = ? AND t.business_id = ?");
$stmt->bind_param("ii", $team_id, $business_id);
$stmt->execute();
$result = $stmt->get_result();
$team = $result->fetch_assoc();

if (!$team) {
    header('Location: teams.php');
    exit();
}

// Get team members
$stmt = $db->prepare("SELECT u.id, u.firstname, u.lastname, u.email, u.user_role FROM users u JOIN team_members tm ON u.id = tm.user_id WHERE tm.team_id = ? AND u.business_id = ?");
$stmt->bind_param("ii", $team_id, $business_id);
$stmt->execute();
$result = $stmt->get_result();
$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

// Get available users for adding to team
$stmt = $db->prepare("SELECT u.id, u.firstname, u.lastname FROM users u WHERE u.business_id = ? AND u.id NOT IN (SELECT tm.user_id FROM team_members tm WHERE tm.team_id = ?)");
$stmt->bind_param("ii", $business_id, $team_id);
$stmt->execute();
$result = $stmt->get_result();
$available_users = [];
while ($row = $result->fetch_assoc()) {
    $available_users[] = $row;
}

// Get users for team leader selection (current team members + available users)
$stmt = $db->prepare("SELECT id, firstname, lastname FROM users WHERE business_id = ? AND (user_role IS NULL OR user_role = '' OR id IN (SELECT user_id FROM team_members WHERE team_id = ?))");
$stmt->bind_param("ii", $business_id, $team_id);
$stmt->execute();
$result = $stmt->get_result();
$all_users = [];
while ($row = $result->fetch_assoc()) {
    $all_users[] = $row;
}

// Get team projects
$stmt = $db->prepare("SELECT * FROM projects WHERE team_id = ? AND business_id = ? ORDER BY created_at DESC");
$stmt->bind_param("ii", $team_id, $business_id);
$stmt->execute();
$result = $stmt->get_result();
$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>TimeTrack Pro - Team Details</title>
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
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

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
        $current_page = 'team_details.php';
        include 'sidebar.php';
        ?>

        <!-- Main Content Wrapper -->
        <main class="flex-1 flex flex-col h-full overflow-hidden relative">
            <?php include 'header.php'; ?>

            <!-- Scrollable Page Content -->
            <div class="flex-1 overflow-y-auto bg-background-light dark:bg-background-dark">
                <div class="max-w-6xl mx-auto px-6 py-8 flex flex-col gap-8">
                    <!-- Breadcrumbs -->
                    <nav class="flex text-sm font-medium text-text-secondary">
                        <a class="hover:text-primary transition-colors" href="teams.php">Teams</a>
                        <span class="mx-2 text-gray-400">/</span>
                        <span class="text-text-main dark:text-white"><?php echo htmlspecialchars($team['name']); ?></span>
                    </nav>

                    <!-- Page Header -->
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                        <div class="flex flex-col gap-1">
                            <h1 class="text-3xl font-bold text-text-main dark:text-white tracking-tight"><?php echo htmlspecialchars($team['name']); ?></h1>
                            <div class="flex items-center gap-3 text-sm">
                                <span class="text-text-secondary">Team Leader: <span class="text-text-main dark:text-gray-200 font-medium"><?php echo htmlspecialchars(($team['leader_firstname'] ?? '') . ' ' . ($team['leader_lastname'] ?? 'Not assigned')); ?></span></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button onclick="openEditModal()" class="px-4 py-2 bg-white dark:bg-slate-800 border border-border-light dark:border-border-dark rounded-lg text-sm font-semibold text-text-main dark:text-white shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg">edit</span>
                                Edit Team
                            </button>
                            <button onclick="deleteTeam()" class="px-4 py-2 bg-white dark:bg-slate-800 border border-border-light dark:border-border-dark rounded-lg text-sm font-semibold text-red-600 shadow-sm hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors flex items-center gap-2">
                                <span class="material-symbols-outlined text-lg">delete</span>
                                Delete Team
                            </button>
                            <a href="teams.php" class="px-4 py-2 bg-white dark:bg-slate-800 border border-border-light dark:border-border-dark rounded-lg text-sm font-semibold text-text-secondary shadow-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                                Back to Teams
                            </a>
                        </div>
                    </div>

                    <?php if (isset($_GET['msg'])): ?>
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-lg">
                            <?php echo htmlspecialchars($_GET['msg']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm flex flex-col justify-between">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <p class="text-text-secondary text-sm font-medium">Team Members</p>
                                    <p class="text-2xl font-bold text-text-main dark:text-white mt-1"><?php echo count($members); ?></p>
                                </div>
                                <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-primary">
                                    <span class="material-symbols-outlined">people</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-surface-light dark:bg-surface-dark p-6 rounded-xl border border-border-light dark:border-border-dark shadow-sm flex flex-col justify-between">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <p class="text-text-secondary text-sm font-medium">Active Projects</p>
                                    <p class="text-2xl font-bold text-text-main dark:text-white mt-1"><?php echo count($projects); ?></p>
                                </div>
                                <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded-lg text-green-600 dark:text-green-400">
                                    <span class="material-symbols-outlined">work</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Team Members Section -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-text-main dark:text-white">Team Members</h2>
                            <button onclick="openAddMemberModal()" class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-sm shadow-blue-200 dark:shadow-none">
                                <span class="material-symbols-outlined text-lg">person_add</span>
                                Add Member
                            </button>
                        </div>
                        <div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm overflow-hidden">
                            <?php if (empty($members)): ?>
                                <div class="text-center py-12">
                                    <span class="material-symbols-outlined text-text-secondary text-4xl mb-2">people_outline</span>
                                    <p class="text-text-secondary">No team members assigned yet</p>
                                </div>
                            <?php else: ?>
                                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <?php foreach ($members as $member): ?>
                                        <div class="border border-border-light dark:border-border-dark rounded-lg p-4 hover:shadow-md transition-shadow">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                                    <span class="text-primary text-sm font-bold"><?php echo strtoupper(substr($member['firstname'], 0, 1) . substr($member['lastname'], 0, 1)); ?></span>
                                                </div>
                                                <div class="flex-1">
                                                    <h3 class="font-medium text-text-main dark:text-white"><?php echo htmlspecialchars($member['firstname'] . ' ' . $member['lastname']); ?></h3>
                                                    <p class="text-sm text-text-secondary"><?php echo htmlspecialchars($member['email']); ?></p>
                                                    <span class="inline-block px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 text-xs rounded-full mt-1">
                                                        <?php echo ucfirst($member['user_role'] ?? 'member'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- Projects Section -->
                    <section class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-text-main dark:text-white">Team Projects</h2>
                            <!-- <button onclick="openProjectModal()" class="inline-flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-sm">
                            <span class="material-symbols-outlined text-lg">add</span>
                            New Project
                        </button> -->
                        </div>
                        <div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm overflow-hidden">
                            <?php if (empty($projects)): ?>
                                <div class="text-center py-12">
                                    <span class="material-symbols-outlined text-text-secondary text-4xl mb-2">folder_open</span>
                                    <p class="text-text-secondary">No projects created yet</p>
                                </div>
                            <?php else: ?>
                                <div class="p-6 space-y-4">
                                    <?php foreach ($projects as $project): ?>
                                        <div class="border border-border-light dark:border-border-dark rounded-lg p-4 hover:shadow-md transition-shadow">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <h3 class="font-medium text-text-main dark:text-white"><?php echo htmlspecialchars($project['name']); ?></h3>
                                                    <p class="text-sm text-text-secondary mt-1"><?php echo htmlspecialchars($project['description']); ?></p>
                                                    <div class="flex items-center gap-4 mt-2">
                                                        <span class="text-xs text-text-secondary">
                                                            Due: <?php echo date('M j, Y', strtotime($project['end_date'])); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="px-2 py-1 bg-<?php echo $project['status'] === 'active' ? 'green' : 'gray'; ?>-100 dark:bg-<?php echo $project['status'] === 'active' ? 'green' : 'gray'; ?>-900/30 text-<?php echo $project['status'] === 'active' ? 'green' : 'gray'; ?>-800 dark:text-<?php echo $project['status'] === 'active' ? 'green' : 'gray'; ?>-400 text-xs rounded-full">
                                                        <?php echo ucfirst($project['status']); ?>
                                                    </span>
                                                    <a href="project_details.php?id=<?php echo $project['id']; ?>" class="p-2 text-text-secondary hover:text-primary transition-colors">
                                                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Team Modal -->
    <div id="editModal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl max-w-md w-full p-6 border border-border-light dark:border-border-dark">
                <h3 class="text-lg font-semibold mb-4 text-text-main dark:text-white">Edit Team</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="edit_team" value="1">
                    <div>
                        <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">Team Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($team['name']); ?>" required class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">Description</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary"><?php echo htmlspecialchars($team['description']); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">Team Leader</label>
                        <select name="leader_id" required class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <?php foreach ($all_users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo $user['id'] == $team['team_leader_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 border border-border-light dark:border-border-dark text-text-secondary rounded-lg hover:bg-background-light dark:hover:bg-slate-700 transition-colors">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">Update Team</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div id="addMemberModal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl max-w-md w-full p-6 border border-border-light dark:border-border-dark">
                <h3 class="text-lg font-semibold mb-4 text-text-main dark:text-white">Add Team Members</h3>
                <?php if (empty($available_users)): ?>
                    <p class="text-text-secondary text-center py-4">No available users to add to this team.</p>
                    <button onclick="closeAddMemberModal()" class="w-full px-4 py-2 bg-background-light dark:bg-slate-700 text-text-secondary rounded-lg hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">Close</button>
                <?php else: ?>
                    <form method="POST" class="space-y-4" id="addMemberForm">
                        <input type="hidden" name="add_member" value="1">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-sm font-medium text-text-main dark:text-white">Select Users</label>
                                <button type="button" onclick="toggleSelectAll()" class="text-xs text-primary hover:underline font-medium">Select All</button>
                            </div>
                            <div class="border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 max-h-64 overflow-y-auto">
                                <?php foreach ($available_users as $index => $user): ?>
                                    <label class="flex items-center gap-3 px-4 py-3 hover:bg-background-light dark:hover:bg-slate-700 cursor-pointer transition-colors <?php echo $index > 0 ? 'border-t border-border-light dark:border-border-dark' : ''; ?>">
                                        <input type="checkbox" name="member_ids[]" value="<?php echo $user['id']; ?>" class="member-checkbox rounded border-gray-300 text-primary focus:ring-primary/20 cursor-pointer w-4 h-4" onchange="updateSelectedCount()">
                                        <div class="flex items-center gap-2 flex-1">
                                            <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center flex-shrink-0">
                                                <span class="text-primary text-xs font-bold"><?php echo strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1)); ?></span>
                                            </div>
                                            <span class="text-sm text-text-main dark:text-white font-medium"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="text-xs text-text-secondary mt-2">
                                <span id="selectedCount">0</span> user(s) selected
                            </p>
                        </div>
                        <div class="flex gap-3 pt-4">
                            <button type="button" onclick="closeAddMemberModal()" class="flex-1 px-4 py-2 border border-border-light dark:border-border-dark text-text-secondary rounded-lg hover:bg-background-light dark:hover:bg-slate-700 transition-colors">Cancel</button>
                            <button type="submit" id="addMembersBtn" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <span id="addBtnText">Add Members</span>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Project Modal -->
    <div id="projectModal" class="fixed inset-0 bg-black/50 z-50 hidden">
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
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">Start Date</label>
                            <input type="date" name="start_date" required class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">End Date</label>
                            <input type="date" name="end_date" required class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        </div>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" onclick="closeProjectModal()" class="flex-1 px-4 py-2 border border-border-light dark:border-border-dark text-text-secondary rounded-lg hover:bg-background-light dark:hover:bg-slate-700 transition-colors">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Create Project</button>
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
        function openEditModal() {
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function openAddMemberModal() {
            document.getElementById('addMemberModal').classList.remove('hidden');
        }

        function closeAddMemberModal() {
            document.getElementById('addMemberModal').classList.add('hidden');
            // Reset form and checkboxes
            const form = document.getElementById('addMemberForm');
            if (form) {
                form.reset();
                updateSelectedCount();
            }
        }

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.member-checkbox:checked');
            const count = checkboxes.length;
            const countEl = document.getElementById('selectedCount');
            const btn = document.getElementById('addMembersBtn');
            const btnText = document.getElementById('addBtnText');

            if (countEl) countEl.textContent = count;
            if (btn) btn.disabled = count === 0;
            if (btnText) btnText.textContent = count === 1 ? 'Add Member' : `Add ${count} Members`;
        }

        function toggleSelectAll() {
            const checkboxes = document.querySelectorAll('.member-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);

            checkboxes.forEach(cb => {
                cb.checked = !allChecked;
            });

            updateSelectedCount();
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateSelectedCount();
        });
    </script>

    function openProjectModal() {
    document.getElementById('projectModal').classList.remove('hidden');
    }

    function closeProjectModal() {
    document.getElementById('projectModal').classList.add('hidden');
    }

    function deleteTeam() {
    if (confirm('Are you sure you want to delete this team? This will also delete all associated projects and remove all team members. This action cannot be undone.')) {
    window.location.href = 'teams.php?delete=<?php echo $team_id; ?>';
    }
    }
    </script>
</body>

</html>