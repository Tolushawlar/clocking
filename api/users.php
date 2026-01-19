<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../lib/constant.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $date = $_GET['date'] ?? TODAY;
    
    $stmt = $db->prepare("
        SELECT u.id, u.firstname, u.lastname, u.email, u.barcode, u.password, u.business_id, u.can_clock_others,
               r.clock_in_time, r.plan_submitted_at, r.report_submitted_at, r.clock_out_time,
               r.plan, r.daily_report
        FROM users u
        LEFT JOIN reports r ON u.id = r.user_id AND r.report_date = ?
        WHERE u.is_active = 1 
        ORDER BY u.id
    ");
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => (int)$row['id'],
            'name' => trim($row['firstname'] . ' ' . $row['lastname']),
            'email' => $row['email'],
            'barcode' => $row['barcode'],
            'password' => $row['password'],
            'business_id' => (int)$row['business_id'],
            'can_clock_others' => (bool)($row['can_clock_others'] ?? 0),
            'daily_status' => [
                'date' => $date,
                'clocked_in' => !empty($row['clock_in_time']),
                'plan_submitted' => !empty($row['plan_submitted_at']),
                'report_submitted' => !empty($row['report_submitted_at']),
                'clocked_out' => !empty($row['clock_out_time']),
                'clock_in_time' => $row['clock_in_time'],
                'plan_submitted_at' => $row['plan_submitted_at'],
                'report_submitted_at' => $row['report_submitted_at'],
                'clock_out_time' => $row['clock_out_time']
            ]
        ];
    }
    
    echo json_encode($users);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>