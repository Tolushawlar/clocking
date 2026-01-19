<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$task_id = $_GET['id'] ?? 0;

// Get project_id before deleting
$stmt = $db->prepare("SELECT project_id FROM tasks WHERE id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();
$project_id = $stmt->get_result()->fetch_assoc()['project_id'];

// Delete task
$stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
$stmt->bind_param("i", $task_id);
$stmt->execute();

header("Location: project_details.php?id=$project_id&msg=Task deleted successfully");
exit;
?>