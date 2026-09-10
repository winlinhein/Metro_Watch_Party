<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../notifications_helper.php';

$userId = (int)$_SESSION['user_id'];
session_write_close();

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$notificationId = intval($input['notification_id'] ?? 0);
$roomId = intval($input['room_id'] ?? 0);
$senderId = intval($input['sender_id'] ?? 0);
$types = $input['types'] ?? ($input['type'] ?? []);
if (is_string($types) && $types !== '') {
    $types = [$types];
}
if (!is_array($types)) {
    $types = [];
}

try {
    $deletedIds = [];

    if ($notificationId > 0) {
        $deletedIds = deleteMatchingNotifications($conn, $userId, [
            'ids' => [$notificationId],
        ]);
    } elseif ($types || $roomId > 0 || $senderId > 0) {
        $deletedIds = deleteMatchingNotifications($conn, $userId, [
            'types' => $types,
            'sender_id' => $senderId,
            'room_id' => $roomId,
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
        exit();
    }

    echo json_encode(['success' => true, 'deleted_notification_ids' => $deletedIds]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
