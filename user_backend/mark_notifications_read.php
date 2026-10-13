<?php
session_start();
require_once __DIR__ . '/../error_response_helper.php';
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
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = :userId AND is_read = 0
    ");
    $stmt->execute(['userId' => $currentUserId]);

    triggerPusherEvent("user-{$currentUserId}", 'notifications_read', []);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    nexusSafeError($e);
    echo json_encode(['error' => 'Database error.']);
}