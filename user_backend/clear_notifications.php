<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized session']);
    exit();
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

$currentUserId = (int)$_SESSION['user_id'];
session_write_close();

try {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = :userId");
    $stmt->execute(['userId' => $currentUserId]);

    triggerPusherEvent("user-{$currentUserId}", 'notifications_cleared', []);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}