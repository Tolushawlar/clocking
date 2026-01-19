<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$user_id = $_SESSION['user_id'];
$project_id = $_GET['project_id'] ?? 0;

if ($_POST) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $assigned_to = $_POST['assigned_to'] ?: null;
    $due_date = $_POST['due_date'] ?: null;
    
    $stmt = $db->prepare("INSERT INTO tasks (project_id, title, description, assigned_to, due_date, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issisi", $project_id, $title, $description, $assigned_to, $due_date, $user_id);
    $stmt->execute();
    
    header("Location: project_details.php?id=$project_id");
    exit;
}

// Get project
$stmt = $db->prepare("SELECT p.name, p.team_id FROM projects p WHERE p.id = ? AND p.business_id = ?");
$stmt->bind_param("ii", $project_id, $business_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

// Get users for assignment (team members if project has team, otherwise all users)
if ($project['team_id']) {
    $users_stmt = $db->prepare("
        SELECT u.id, u.firstname, u.lastname 
        FROM users u 
        JOIN team_members tm ON u.id = tm.user_id 
        WHERE tm.team_id = ? AND u.is_active = 1
        ORDER BY u.firstname, u.lastname
    ");
    $users_stmt->bind_param("i", $project['team_id']);
} else {
    $users_stmt = $db->prepare("SELECT id, firstname, lastname FROM users WHERE business_id = ? AND is_active = 1 ORDER BY firstname, lastname");
    $users_stmt->bind_param("i", $business_id);
}
$users_stmt->execute();
$users = $users_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>Add Task - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-2xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Add Task to <?php echo htmlspecialchars($project['name']); ?></h1>
        <form method="POST" class="bg-white p-6 rounded-lg shadow space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Task Title</label>
                <input type="text" name="title" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Assign To</label>
                <select name="assigned_to" class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Unassigned</option>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Due Date</label>
                <input type="date" name="due_date" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Add Task</button>
                <a href="project_details.php?id=<?php echo $project_id; ?>" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>