<?php
require_once '../lib/constant.php';
require_once 'layout.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    $_SESSION['business_id'] = 1;
    $_SESSION['user_id'] = 1;
    $_SESSION['firstname'] = 'Admin';
    $_SESSION['lastname'] = 'User';
}

$team_id = $_GET['id'] ?? 0;
$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'] ?? 1;

// Handle form submissions
if ($_POST) {
    if (isset($_POST['edit_team'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $leader_id = $_POST['leader_id'];
        
        $stmt = $db->prepare("UPDATE teams SET name = ?, description = ?, team_leader_id = ? WHERE id = ? AND business_id = ?");
        $stmt->bind_param("ssiii", $name, $description, $leader_id, $team_id, $business_id);
        $stmt->execute();
        
        header('Location: team_details.php?id=' . $team_id . '&msg=Team updated successfully');
        exit;
    }
    
    if (isset($_POST['add_member'])) {
        $member_id = $_POST['member_id'];
        
        $stmt = $db->prepare("INSERT IGNORE INTO team_members (team_id, user_id, added_by) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $team_id, $member_id, $user_id);
        $stmt->execute();
        
        $stmt = $db->prepare("UPDATE users SET user_role = 'team_member' WHERE id = ?");
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        
        header('Location: team_details.php?id=' . $team_id . '&msg=Member added successfully');
        exit;
    }
    
    if (isset($_POST['create_project'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        
        $stmt = $db->prepare("INSERT INTO projects (business_id, team_id, name, description, start_date, end_date, status, created_by) VALUES (?, ?, ?, ?, ?, ?, 'active', ?)");
        $stmt->bind_param("iissssi", $business_id, $team_id, $name, $description, $start_date, $end_date, $user_id);
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

// Get all users for team leader selection
$stmt = $db->prepare("SELECT id, firstname, lastname FROM users WHERE business_id = ?");
$stmt->bind_param("i", $business_id);
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

ob_start();
?>
<?php if (isset($_GET['msg'])): ?>
    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
        <?php echo htmlspecialchars($_GET['msg']); ?>
    </div>
<?php endif; ?>

<!-- Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($team['name']); ?></h1>
        <p class="text-gray-600">Team Leader: <?php echo htmlspecialchars(($team['leader_firstname'] ?? '') . ' ' . ($team['leader_lastname'] ?? 'Not assigned')); ?></p>
    </div>
    <div class="flex gap-3">
        <button onclick="openEditModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
            <span class="material-icons text-sm">edit</span>
            Edit Team
        </button>
        <a href="teams.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
            Back to Teams
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl shadow-sm border">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Team Members</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count($members); ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <span class="material-icons text-blue-600">people</span>
            </div>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-sm border">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Active Projects</p>
                <p class="text-2xl font-bold text-gray-900"><?php echo count($projects); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <span class="material-icons text-green-600">folder</span>
            </div>
        </div>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-sm border">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">Total Tasks</p>
                <p class="text-2xl font-bold text-gray-900">0</p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <span class="material-icons text-purple-600">task</span>
            </div>
        </div>
    </div>
</div>

<!-- Team Members -->
<div class="bg-white rounded-xl shadow-sm border mb-8">
    <div class="p-6 border-b">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Team Members</h2>
            <button onclick="openAddMemberModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                <span class="material-icons text-sm">person_add</span>
                Add Member
            </button>
        </div>
    </div>
    <div class="p-6">
        <?php if (empty($members)): ?>
            <div class="text-center py-8">
                <span class="material-icons text-gray-400 text-4xl mb-2">people_outline</span>
                <p class="text-gray-500">No team members assigned yet</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($members as $member): ?>
                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                <span class="material-icons text-gray-600">person</span>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($member['firstname'] . ' ' . $member['lastname']); ?></h3>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($member['email']); ?></p>
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full mt-1">
                                    <?php echo ucfirst($member['user_role'] ?? 'member'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Projects -->
<div class="bg-white rounded-xl shadow-sm border">
    <div class="p-6 border-b">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Team Projects</h2>
            <button onclick="openProjectModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2">
                <span class="material-icons text-sm">add</span>
                New Project
            </button>
        </div>
    </div>
    <div class="p-6">
        <?php if (empty($projects)): ?>
            <div class="text-center py-8">
                <span class="material-icons text-gray-400 text-4xl mb-2">folder_open</span>
                <p class="text-gray-500">No projects created yet</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($projects as $project): ?>
                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($project['name']); ?></h3>
                                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($project['description']); ?></p>
                                <div class="flex items-center gap-4 mt-2">
                                    <span class="text-xs text-gray-500">
                                        Due: <?php echo date('M j, Y', strtotime($project['end_date'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 bg-<?php echo $project['status'] === 'active' ? 'green' : 'gray'; ?>-100 text-<?php echo $project['status'] === 'active' ? 'green' : 'gray'; ?>-800 text-xs rounded-full">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                                <a href="project_details.php?id=<?php echo $project['id']; ?>" class="p-2 text-gray-400 hover:text-gray-600 transition-colors">
                                    <span class="material-icons text-sm">arrow_forward</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Team Modal -->
<div id="editModal" class="fixed inset-0 bg-black/50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-4">Edit Team</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="edit_team" value="1">
                <div>
                    <label class="block text-sm font-medium mb-2">Team Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($team['name']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($team['description']); ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Team Leader</label>
                    <select name="leader_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <?php foreach ($all_users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo $user['id'] == $team['team_leader_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update Team</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div id="addMemberModal" class="fixed inset-0 bg-black/50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-4">Add Team Member</h3>
            <?php if (empty($available_users)): ?>
                <p class="text-gray-500 text-center py-4">No available users to add to this team.</p>
                <button onclick="closeAddMemberModal()" class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Close</button>
            <?php else: ?>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="add_member" value="1">
                    <div>
                        <label class="block text-sm font-medium mb-2">Select User</label>
                        <select name="member_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Choose a user...</option>
                            <?php foreach ($available_users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" onclick="closeAddMemberModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Add Member</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Project Modal -->
<div id="projectModal" class="fixed inset-0 bg-black/50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-4">Create New Project</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="create_project" value="1">
                <div>
                    <label class="block text-sm font-medium mb-2">Project Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium mb-2">Start Date</label>
                        <input type="date" name="start_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">End Date</label>
                        <input type="date" name="end_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeProjectModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Create Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

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
    }

    function openProjectModal() {
        document.getElementById('projectModal').classList.remove('hidden');
    }

    function closeProjectModal() {
        document.getElementById('projectModal').classList.add('hidden');
    }
</script>

<?php
$content = ob_get_clean();
echo renderAdminLayout('Team Details', 'teams', $content);