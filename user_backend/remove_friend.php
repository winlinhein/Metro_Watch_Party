<?php
session_start();
require_once __DIR__ . '/../conn.php';
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
$data = json_decode(file_get_contents('php://input'), true);
$friendId = (int)($data['friend_id'] ?? 0);

if (!$userId || !$friendId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

try {
    $stmt = $conn->prepare("
        DELETE FROM user_friends 
        WHERE ( (user_id_1 = :u1 AND user_id_2 = :u2) OR (user_id_1 = :u2 AND user_id_2 = :u1) )
        AND status = 'accepted'
    ");
    $stmt->execute([':u1' => $userId, ':u2' => $friendId]);

    echo json_encode(['success' => true, 'message' => 'Friend removed.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
