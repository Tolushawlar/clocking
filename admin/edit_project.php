<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$project_id = $_GET['id'] ?? 0;

if ($_POST) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $client_name = trim($_POST['client_name']);
    $status = $_POST['status'];
    $end_date = $_POST['end_date'] ?: null;
    $budget_hours = (int)$_POST['budget_hours'];
    
    $stmt = $db->prepare("UPDATE projects SET name = ?, description = ?, client_name = ?, status = ?, end_date = ?, budget_hours = ? WHERE id = ? AND business_id = ?");
    $stmt->bind_param("sssssiis", $name, $description, $client_name, $status, $end_date, $budget_hours, $project_id, $business_id);
    $stmt->execute();
    
    header("Location: project_details.php?id=$project_id");
    exit;
}

$stmt = $db->prepare("SELECT * FROM projects WHERE id = ? AND business_id = ?");
$stmt->bind_param("ii", $project_id, $business_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    header('Location: projects.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>Edit Project - TimeTrack Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="max-w-2xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-6">Edit Project</h1>
        <form method="POST" class="bg-white p-6 rounded-lg shadow space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Project Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($project['name']); ?>" required class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded-lg"><?php echo htmlspecialchars($project['description']); ?></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Client Name</label>
                <input type="text" name="client_name" value="<?php echo htmlspecialchars($project['client_name']); ?>" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Status</label>
                <select name="status" class="w-full px-3 py-2 border rounded-lg">
                    <option value="planning" <?php echo $project['status'] == 'planning' ? 'selected' : ''; ?>>Planning</option>
                    <option value="active" <?php echo $project['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="on_hold" <?php echo $project['status'] == 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                    <option value="completed" <?php echo $project['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">End Date</label>
                <input type="date" name="end_date" value="<?php echo $project['end_date']; ?>" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Budget Hours</label>
                <input type="number" name="budget_hours" value="<?php echo $project['budget_hours']; ?>" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Update Project</button>
                <a href="project_details.php?id=<?php echo $project_id; ?>" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>