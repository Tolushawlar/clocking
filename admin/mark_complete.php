<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$type = $_GET['type'] ?? '';
$id = $_GET['id'] ?? 0;
$status = $_GET['status'] ?? 'completed';
$redirect = $_GET['redirect'] ?? 'projects.php';

if ($type === 'task') {
    $valid_statuses = ['pending', 'in_progress', 'completed', 'blocked'];
    if (!in_array($status, $valid_statuses)) {
        $status = 'completed';
    }
    $stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
} elseif ($type === 'project') {
    $valid_statuses = ['active', 'completed', 'on_hold', 'planning'];
    if (!in_array($status, $valid_statuses)) {
        $status = 'completed';
    }
    $stmt = $db->prepare("UPDATE projects SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
} elseif ($type === 'phase') {
    // Check current status and toggle
    $check_stmt = $db->prepare("SELECT status FROM project_phases WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $current = $check_stmt->get_result()->fetch_assoc();
    
    // Toggle: if completed, set to pending, otherwise set to completed
    $new_status = ($current['status'] === 'completed') ? 'pending' : 'completed';
    
    $stmt = $db->prepare("UPDATE project_phases SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $id);
    $stmt->execute();
}

header("Location: $redirect");
exit;
?>
