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
$type = $input['type'] ?? ''; // 'plan' or 'report'
$content = $input['content'] ?? '';

if (!$barcode || !$type || !$content) {
    http_response_code(400);
    echo json_encode(['error' => 'Barcode, type, and content required']);
    exit;
}

try {
    // Get user by barcode
    $stmt = $db->prepare("SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) as name, b.reporting_enabled FROM users u JOIN business b ON u.business_id = b.id WHERE u.barcode = ? AND u.is_active = 1");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    if (!$user['reporting_enabled']) {
        http_response_code(403);
        echo json_encode(['error' => 'Reporting not enabled']);
        exit;
    }
    
    $today = TODAY;
    
    // Get today's report
    $stmt = $db->prepare("SELECT id, clock_in_time, plan_submitted_at FROM reports WHERE user_id = ? AND report_date = ?");
    $stmt->bind_param("is", $user['id'], $today);
    $stmt->execute();
    $report = $stmt->get_result()->fetch_assoc();
    
    if (!$report) {
        http_response_code(400);
        echo json_encode(['error' => 'Must clock in first']);
        exit;
    }
    
    if ($type === 'plan') {
        if ($report['plan_submitted_at']) {
            http_response_code(400);
            echo json_encode(['error' => 'Plan already submitted']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE reports SET plan = ?, plan_submitted_at = NOW(), status = 'plan_submitted' WHERE id = ?");
        $stmt->bind_param("si", $content, $report['id']);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Plan submitted successfully', 'user' => $user['name']]);
        
    } elseif ($type === 'report') {
        if (!$report['plan_submitted_at']) {
            http_response_code(400);
            echo json_encode(['error' => 'Must submit plan first']);
            exit;
        }
        
        $stmt = $db->prepare("UPDATE reports SET daily_report = ?, report_submitted_at = NOW(), status = 'report_submitted' WHERE id = ?");
        $stmt->bind_param("si", $content, $report['id']);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Report submitted successfully', 'user' => $user['name']]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid type']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>