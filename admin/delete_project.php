<?php
require_once '../lib/constant.php';
session_start();

if (!isset($_SESSION['business_id'])) {
    header('Location: ../index.php');
    exit;
}

$business_id = $_SESSION['business_id'];
$project_id = $_GET['id'] ?? 0;

$stmt = $db->prepare("DELETE FROM projects WHERE id = ? AND business_id = ?");
$stmt->bind_param("ii", $project_id, $business_id);
$stmt->execute();

header('Location: projects.php?msg=Project deleted successfully');
exit;
?>