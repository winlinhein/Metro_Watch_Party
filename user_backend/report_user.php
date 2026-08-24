<?php
session_start();
require_once __DIR__ . '/../conn.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
$data = json_decode(file_get_contents('php://input'), true);
$reportedId = (int)($data['reported_id'] ?? 0);
$reason = $data['reason'] ?? 'Inappropriate behavior';

if (!$userId || !$reportedId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

try {
    // Try to insert into reports table if it exists. 
    // Alternatively just insert a notification for admin.
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at) 
        VALUES (1, :sender_id, 'report', :msg, 0, NOW())
    ");
    $msg = "User reported ID: {$reportedId}. Reason: {$reason}";
    $stmt->execute([
        ':sender_id' => $userId,
        ':msg' => $msg
    ]);

    echo json_encode(['success' => true, 'message' => 'Report submitted successfully.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}