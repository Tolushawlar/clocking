<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$task_id = $_GET['id'] ?? 0;

if ($_POST) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $assigned_to = $_POST['assigned_to'] ?: null;
    $due_date = $_POST['due_date'] ?: null;
    
    $stmt = $db->prepare("UPDATE tasks SET title = ?, description = ?, status = ?, assigned_to = ?, due_date = ? WHERE id = ?");
    $stmt->bind_param("sssisi", $title, $description, $status, $assigned_to, $due_date, $task_id);
    $stmt->execute();
    
    // Get project_id to redirect back
    $stmt = $db->prepare("SELECT project_id FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $project_id = $stmt->get_result()->fetch_assoc()['project_id'];
    
    header("Location: project_details.php?id=$project_id");
    exit;
}

// Get task
$stmt = $db->prepare("SELECT t.*, p.name as project_name, p.team_id FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$task = $stmt->get_result()->fetch_assoc();

// Get users (team members if project has team, otherwise all users)
if ($task['team_id']) {
    $users_stmt = $db->prepare("
        SELECT u.id, u.firstname, u.lastname 
        FROM users u 
        JOIN team_members tm ON u.id = tm.user_id 
        WHERE tm.team_id = ? AND u.is_active = 1
        ORDER BY u.firstname, u.lastname
    ");
    $users_stmt->bind_param("i", $task['team_id']);
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
    <title>Edit Task - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-2xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Edit Task</h1>
        <form method="POST" class="bg-white p-6 rounded-lg shadow space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Task Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($task['name'] ?? $task['title'] ?? ''); ?>" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg"><?php echo htmlspecialchars($task['description'] ?? ''); ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Status</label>
                <select name="status" class="w-full px-3 py-2 border rounded-lg">
                    <option value="pending" <?php echo $task['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $task['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $task['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="blocked" <?php echo $task['status'] == 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Assign To</label>
                <select name="assigned_to" class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Unassigned</option>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo $task['assigned_to'] == $user['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Due Date</label>
                <input type="date" name="due_date" value="<?php echo $task['due_date']; ?>" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Update Task</button>
                <a href="project_details.php?id=<?php echo $task['project_id']; ?>" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>