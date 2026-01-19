<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../lib/constant.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$barcode = $input['barcode'] ?? '';

if (!$barcode) {
    http_response_code(400);
    echo json_encode(['error' => 'Barcode required']);
    exit;
}

try {
    // Get user by barcode
    $stmt = $db->prepare("SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) as name FROM users u WHERE u.barcode = ? AND u.is_active = 1");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['can_clock_out' => false, 'message' => 'User not found']);
        exit;
    }
    
    $today = TODAY;
    
    // Get today's report
    $stmt = $db->prepare("SELECT clock_in_time, report_submitted_at, clock_out_time FROM reports WHERE user_id = ? AND report_date = ?");
    $stmt->bind_param("is", $user['id'], $today);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();
    
    if (!$report || !$report['clock_in_time']) {
        echo json_encode(['can_clock_out' => false, 'message' => 'User needs to clock in first']);
    } elseif ($report['clock_out_time']) {
        echo json_encode(['can_clock_out' => false, 'message' => 'User already clocked out']);
    } elseif (!$report['report_submitted_at']) {
        echo json_encode(['can_clock_out' => false, 'message' => 'User must submit plan and report first']);
    } else {
        echo json_encode(['can_clock_out' => true, 'message' => 'Ready to clock out', 'user' => $user['name']]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>