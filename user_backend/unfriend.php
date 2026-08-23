<?php
// /user_backend/unfriend.php

session_start();

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $user_id = (int) $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $friend_id = isset($data['friend_id']) ? (int) $data['friend_id'] : 0;

    if ($friend_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid friend ID provided']);
        exit;
    }

    $stmt = $conn->prepare("
        DELETE FROM user_friends
        WHERE (user_id_1 = ? AND user_id_2 = ?)
           OR (user_id_1 = ? AND user_id_2 = ?)
    ");
    $stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        // Send real-time events to both users using the helper
        $payloadForCurrent = ['friend_id' => $friend_id];
        $payloadForFriend  = ['friend_id' => $user_id];

        triggerPusherEvent("user-{$user_id}", 'friend-removed', $payloadForCurrent);
        triggerPusherEvent("user-{$friend_id}", 'friend-removed', $payloadForFriend);

        echo json_encode(['success' => true, 'message' => 'Friend removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Relationship not found']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}