<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../conn.php';

$userId = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$notificationId = intval($input['notification_id'] ?? 0);

if ($notificationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

try {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notificationId, $userId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}