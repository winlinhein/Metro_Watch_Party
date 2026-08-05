<?php
// user_backend/get_notifications.php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized session']);
    exit();
}

require_once __DIR__ . '/../conn.php';

$currentUserId = (int)$_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            n.id,
            n.sender_id,
            n.type,
            n.message,
            n.is_read,
            n.created_at,
            u.user_name AS sender_name
        FROM notifications n
        JOIN users u ON u.user_id = n.sender_id
        WHERE n.user_id = :userId
        ORDER BY n.created_at DESC
        LIMIT 20
    ");
    $stmt->execute(['userId' => $currentUserId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'notifications' => $notifications]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}