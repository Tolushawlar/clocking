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
$action = $input['action'] ?? ''; // 'clock_in' or 'clock_out'

if (!$barcode || !$action) {
    http_response_code(400);
    echo json_encode(['error' => 'Barcode and action required']);
    exit;
}

try {
    // Get user by barcode
    $stmt = $db->prepare("SELECT u.id, u.business_id, CONCAT(u.firstname, ' ', u.lastname) as name, u.can_clock, b.clocking_enabled FROM users u JOIN business b ON u.business_id = b.id WHERE u.barcode = ? AND u.is_active = 1");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }
    
    if (!$user['clocking_enabled'] || !$user['can_clock']) {
        http_response_code(403);
        echo json_encode(['error' => 'Clocking not allowed']);
        exit;
    }
    
    $today = TODAY;
    
    if ($action === 'clock_in') {
        // Check if already clocked in
        $stmt = $db->prepare("SELECT id FROM reports WHERE user_id = ? AND report_date = ?");
        $stmt->bind_param("is", $user['id'], $today);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Already clocked in today']);
            exit;
        }
        
        // Clock in
        $stmt = $db->prepare("INSERT INTO reports (user_id, report_date, clock_in_time, status) VALUES (?, ?, NOW(), 'clocked_in')");
        $stmt->bind_param("is", $user['id'], $today);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Clocked in successfully', 'user' => $user['name']]);
        
    } elseif ($action === 'clock_out') {
        // Get today's report
        $stmt = $db->prepare("SELECT id, report_submitted_at FROM reports WHERE user_id = ? AND report_date = ?");
        $stmt->bind_param("is", $user['id'], $today);
        $stmt->execute();
        $report = $stmt->get_result()->fetch_assoc();
        
        if (!$report) {
            http_response_code(400);
            echo json_encode(['error' => 'Must clock in first']);
            exit;
        }
        
        if (!$report['report_submitted_at']) {
            http_response_code(400);
            echo json_encode(['error' => 'Must submit daily report first']);
            exit;
        }
        
        // Clock out
        $stmt = $db->prepare("UPDATE reports SET clock_out_time = NOW(), status = 'clocked_out' WHERE id = ?");
        $stmt->bind_param("i", $report['id']);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Clocked out successfully', 'user' => $user['name']]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>