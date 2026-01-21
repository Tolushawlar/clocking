<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    $_SESSION['business_id'] = 1;
    $_SESSION['firstname'] = 'Admin';
    $_SESSION['lastname'] = 'User';
}

$business_id = $_SESSION['business_id'];

// Handle team creation
if (isset($_POST['create_team'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $team_leader_id = $_POST['team_leader_id'];

    $stmt = $db->prepare("INSERT INTO teams (business_id, name, description, team_leader_id, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issii", $business_id, $name, $description, $team_leader_id, $business_id);

    if ($stmt->execute()) {
        $team_id = $db->insert_id;
        // Add team leader as member
        $stmt = $db->prepare("INSERT INTO team_members (team_id, user_id, added_by) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $team_id, $team_leader_id, $business_id);
        $stmt->execute();

        // Update user role
        $stmt = $db->prepare("UPDATE users SET user_role = 'team_leader' WHERE id = ?");
        $stmt->bind_param("i", $team_leader_id);
        $stmt->execute();

        header('Location: teams.php?msg=Team created successfully');
        exit;
    }
}

// Handle team deletion
if (isset($_GET['delete']) && $_GET['delete']) {
    $delete_team_id = $_GET['delete'];

    // Check if team has projects assigned
    $stmt = $db->prepare("SELECT COUNT(*) as project_count FROM projects WHERE team_id = ?");
    $stmt->bind_param("i", $delete_team_id);
    $stmt->execute();
    $project_check = $stmt->get_result()->fetch_assoc();

    // Check if team members have tasks assigned
    $stmt = $db->prepare("SELECT COUNT(*) as task_count FROM tasks WHERE assigned_to IN (SELECT user_id FROM team_members WHERE team_id = ?)");
    $stmt->bind_param("i", $delete_team_id);
    $stmt->execute();
    $task_check = $stmt->get_result()->fetch_assoc();

    if ($project_check['project_count'] > 0 || $task_check['task_count'] > 0) {
        header('Location: teams.php?msg=Cannot delete team: Team has active projects or tasks assigned');
        exit;
    }

    // Get current team leader to reset their role
    $stmt = $db->prepare("SELECT team_leader_id FROM teams WHERE id = ? AND business_id = ?");
    $stmt->bind_param("ii", $delete_team_id, $business_id);
    $stmt->execute();
    $team_result = $stmt->get_result()->fetch_assoc();

    if ($team_result && $team_result['team_leader_id']) {
        // Reset team leader role to NULL
        $stmt = $db->prepare("UPDATE users SET user_role = NULL WHERE id = ?");
        $stmt->bind_param("i", $team_result['team_leader_id']);
        $stmt->execute();
    }

    // Reset all team members' roles to NULL
    $stmt = $db->prepare("UPDATE users SET user_role = NULL WHERE id IN (SELECT user_id FROM team_members WHERE team_id = ?)");
    $stmt->bind_param("i", $delete_team_id);
    $stmt->execute();

    // Delete team members
    $stmt = $db->prepare("DELETE FROM team_members WHERE team_id = ?");
    $stmt->bind_param("i", $delete_team_id);
    $stmt->execute();

    // Delete the team
    $stmt = $db->prepare("DELETE FROM teams WHERE id = ? AND business_id = ?");
    $stmt->bind_param("ii", $delete_team_id, $business_id);
    $stmt->execute();

    header('Location: teams.php?msg=Team deleted successfully');
    exit;
}

// Get teams
$stmt = $db->prepare("
    SELECT t.*, 
           u.firstname as leader_firstname, u.lastname as leader_lastname,
           COUNT(tm.user_id) as member_count
    FROM teams t 
    LEFT JOIN users u ON t.team_leader_id = u.id
    LEFT JOIN team_members tm ON t.id = tm.team_id
    WHERE t.business_id = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$teams = $stmt->get_result();

// Get all users for team leaders
$stmt = $db->prepare("SELECT id, firstname, lastname FROM users WHERE business_id = ?");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$available_users = $stmt->get_result();
?>
<!DOCTYPE html>
<html class="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>TimeTrack Pro - Teams</title>
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
        $current_page = 'teams.php';
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
                            <h1 class="text-3xl font-bold text-text-main dark:text-white tracking-tight">Team Management</h1>
                            <p class="text-text-secondary">Create teams and assign team leaders</p>
                        </div>
                        <button onclick="openCreateModal()" class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition-all shadow-sm shadow-blue-200 dark:shadow-none">
                            <span class="material-symbols-outlined text-lg">add</span>
                            Create Team
                        </button>
                    </div>

                    <?php if (isset($_GET['msg'])): ?>
                        <?php
                        $is_error = strpos($_GET['msg'], 'Cannot') !== false || strpos($_GET['msg'], 'Error') !== false;
                        $msg_class = $is_error ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400' : 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-700 dark:text-green-400';
                        ?>
                        <div class="<?php echo $msg_class; ?> border px-4 py-3 rounded-lg">
                            <?php echo htmlspecialchars($_GET['msg']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Teams Grid -->
                    <?php if ($teams->num_rows > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php while ($team = $teams->fetch_assoc()): ?>
                                <div class="bg-surface-light dark:bg-surface-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm p-6 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-text-main dark:text-white"><?php echo htmlspecialchars($team['name']); ?></h3>
                                            <p class="text-sm text-text-secondary mt-1"><?php echo htmlspecialchars($team['description']); ?></p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <!-- <button onclick="openMemberModal(<?php echo $team['id']; ?>)" class="text-text-secondary hover:text-primary transition-colors">
                                        <span class="material-symbols-outlined">person_add</span>
                                    </button> -->
                                            <button onclick="deleteTeam(<?php echo $team['id']; ?>)" class="text-red-600 hover:text-red-700 transition-colors">
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                                                <span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-sm">star</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-text-main dark:text-white">Team Leader</p>
                                                <p class="text-xs text-text-secondary"><?php echo htmlspecialchars($team['leader_firstname'] . ' ' . $team['leader_lastname']); ?></p>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                                                <span class="material-symbols-outlined text-green-600 dark:text-green-400 text-sm">group</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-text-main dark:text-white"><?php echo $team['member_count']; ?> Members</p>
                                                <p class="text-xs text-text-secondary">Including team leader</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 pt-4 border-t border-border-light dark:border-border-dark">
                                        <a href="team_details.php?id=<?php echo $team['id']; ?>" class="text-primary text-sm font-medium hover:underline">View Details →</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="flex flex-col items-center justify-center py-16 px-4">
                            <div class="w-32 h-32 bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 rounded-full flex items-center justify-center mb-6 shadow-lg">
                                <span class="material-symbols-outlined text-primary dark:text-blue-400" style="font-size: 64px;">groups</span>
                            </div>
                            <h3 class="text-2xl font-bold text-text-main dark:text-white mb-2">No Teams Yet</h3>
                            <p class="text-text-secondary text-center max-w-md mb-8">
                                Get started by creating your first team. Organize your staff into teams with designated leaders to streamline collaboration and project management.
                            </p>
                            <button onclick="openCreateModal()" class="inline-flex items-center justify-center gap-2 bg-primary hover:bg-primary-hover text-white px-6 py-3 rounded-lg text-sm font-semibold transition-all shadow-lg shadow-blue-200 dark:shadow-none">
                                <span class="material-symbols-outlined text-lg">add</span>
                                Create Your First Team
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Create Team Modal -->
    <div id="createModal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-surface-light dark:bg-surface-dark rounded-xl max-w-md w-full p-6 border border-border-light dark:border-border-dark">
                <h3 class="text-lg font-semibold mb-4 text-text-main dark:text-white">Create New Team</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="create_team" value="1">
                    <div>
                        <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">Team Name</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">Description</label>
                        <textarea name="description" rows="3" class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2 text-text-main dark:text-white">Team Leader</label>
                        <select name="team_leader_id" required class="w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-surface-light dark:bg-slate-800 text-text-main dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            <option value="">Select Team Leader</option>
                            <?php
                            $available_users->data_seek(0);
                            while ($user = $available_users->fetch_assoc()):
                            ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" onclick="closeCreateModal()" class="flex-1 px-4 py-2 border border-border-light dark:border-border-dark text-text-secondary rounded-lg hover:bg-background-light dark:hover:bg-slate-700 transition-colors">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-hover transition-colors">Create Team</button>
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

        function openMemberModal(teamId) {
            window.location.href = 'team_details.php?id=' + teamId;
        }

        function deleteTeam(teamId) {
            if (confirm('Are you sure you want to delete this team? This will also delete all associated projects and remove all team members. This action cannot be undone.')) {
                window.location.href = 'teams.php?delete=' + teamId;
            }
        }

        // Search functionality
        document.getElementById('searchTeams').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const teamCards = document.querySelectorAll('.grid > div');

            teamCards.forEach(card => {
                const teamName = card.querySelector('h3')?.textContent.toLowerCase() || '';
                const teamDesc = card.querySelector('.text-text-secondary')?.textContent.toLowerCase() || '';

                if (teamName.includes(searchTerm) || teamDesc.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>