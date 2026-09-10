<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';
header('Content-Type: application/json');

$role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? '');
if (empty($_SESSION['user_id']) || !in_array(strtolower((string)$role), ['admin', 'moderator'], true)) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$admin_id = (int)$_SESSION['user_id'];
session_write_close();

try {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$admin_id]);
    triggerPusherEvent("user-{$admin_id}", 'notifications_read', []);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
