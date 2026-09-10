<?php
// /admin_backend/update_report_status.php
session_start();
require_once __DIR__ . '/../conn.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_role']) || !in_array(strtolower((string)$_SESSION['user_role']), ['admin', 'moderator'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

session_write_close();

$data = json_decode(file_get_contents('php://input'), true);
$report_id = $data['report_id'] ?? null;
if (is_string($report_id) && preg_match('/(\d+)/', $report_id, $m)) {
    $report_id = (int)$m[1];
} else {
    $report_id = (int)$report_id;
}

if ($report_id) {
    try {
        // CHANGED: 'id' to 'report_id' and 'Read' to 'read'
        $stmt = $conn->prepare("UPDATE reports SET status = 'read' WHERE report_id = ?"); 
        $stmt->execute([$report_id]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No report ID provided']);
}
?>