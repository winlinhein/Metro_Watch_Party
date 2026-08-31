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
require_once __DIR__ . '/../profile_media_helper.php';

$currentUserId = (int)$_SESSION['user_id'];
session_write_close();

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
        LIMIT 30
    ");
    $stmt->execute(['userId' => $currentUserId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Attach sender avatar/border using sender_id as the user key
    $mediaRows = array_map(static function ($n) {
        return ['user_id' => (int)$n['sender_id']];
    }, $notifications);
    $mediaByUser = [];
    foreach (attachProfileMedia($conn, $mediaRows) as $m) {
        $mediaByUser[(int)$m['user_id']] = $m;
    }

    foreach ($notifications as &$n) {
        $sid = (int)$n['sender_id'];
        $n['avatar_url'] = $mediaByUser[$sid]['avatar_url'] ?? '';
        $n['border_preview'] = $mediaByUser[$sid]['border_preview'] ?? '';
        $n['border_id'] = (int)($mediaByUser[$sid]['border_id'] ?? 0);
    }
    unset($n);

    echo json_encode(['success' => true, 'notifications' => $notifications]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
