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

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'] ?? 1;

// Handle team creation
if (isset($_POST['create_team'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $team_leader_id = $_POST['team_leader_id'];
    
    $stmt = $db->prepare("INSERT INTO teams (business_id, name, description, team_leader_id, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issii", $business_id, $name, $description, $team_leader_id, $user_id);
    
    if ($stmt->execute()) {
        $team_id = $db->insert_id;
        // Add team leader as member
        $stmt = $db->prepare("INSERT INTO team_members (team_id, user_id, added_by) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $team_id, $team_leader_id, $user_id);
        $stmt->execute();
        
        // Update user role
        $stmt = $db->prepare("UPDATE users SET user_role = 'team_leader' WHERE id = ?");
        $stmt->bind_param("i", $team_leader_id);
        $stmt->execute();
        
        header('Location: teams.php?msg=Team created successfully');
        exit;
    }
}

// Handle member assignment
if (isset($_POST['add_member'])) {
    $team_id = $_POST['team_id'];
    $member_id = $_POST['member_id'];
    
    $stmt = $db->prepare("INSERT IGNORE INTO team_members (team_id, user_id, added_by) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $team_id, $member_id, $user_id);
    $stmt->execute();
    
    // Update user role
    $stmt = $db->prepare("UPDATE users SET user_role = 'team_member' WHERE id = ?");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    
    header('Location: teams.php?msg=Member added successfully');
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

// Get available users for team leaders
$stmt = $db->prepare("SELECT id, firstname, lastname FROM users WHERE business_id = ? AND (user_role IS NULL OR user_role = 'team_member')");
$stmt->bind_param("i", $business_id);
$stmt->execute();
$available_users = $stmt->get_result();

ob_start();
?>
<?php if (isset($_GET['msg'])): ?>
    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
        <?php echo htmlspecialchars($_GET['msg']); ?>
    </div>
<?php endif; ?>

<!-- Header -->
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Team Management</h1>
        <p class="text-gray-500">Create teams and assign team leaders</p>
    </div>
    <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
        <span class="material-icons text-[20px]">add</span>
        Create Team
    </button>
</div>

<!-- Teams Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php while ($team = $teams->fetch_assoc()): ?>
    <div class="bg-white rounded-xl p-6 border shadow-sm">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($team['name']); ?></h3>
                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($team['description']); ?></p>
            </div>
            <button onclick="openMemberModal(<?php echo $team['id']; ?>)" class="text-gray-400 hover:text-blue-600">
                <span class="material-icons">person_add</span>
            </button>
        </div>
        
        <div class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="material-icons text-blue-600 text-sm">star</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">Team Leader</p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($team['leader_firstname'] . ' ' . $team['leader_lastname']); ?></p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                    <span class="material-icons text-gray-600 text-sm">group</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900"><?php echo $team['member_count']; ?> Members</p>
                    <p class="text-xs text-gray-500">Including team leader</p>
                </div>
            </div>
        </div>
        
        <div class="mt-4 pt-4 border-t">
            <a href="team_details.php?id=<?php echo $team['id']; ?>" class="text-blue-600 text-sm font-medium hover:underline">View Details →</a>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<!-- Create Team Modal -->
<div id="createModal" class="fixed inset-0 bg-black/50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-6">
            <h3 class="text-lg font-semibold mb-4">Create New Team</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="create_team" value="1">
                <div>
                    <label class="block text-sm font-medium mb-2">Team Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Team Leader</label>
                    <select name="team_leader_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                    <button type="button" onclick="closeCreateModal()" class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Create Team</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.remove('hidden');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.add('hidden');
}

function openMemberModal(teamId) {
    // Redirect to team details for member management
    window.location.href = 'team_details.php?id=' + teamId;
}
</script>

<?php
$content = ob_get_clean();
echo renderAdminLayout('Teams', 'teams', $content);