<?php
// /user_backend/unfriend.php

session_start();

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

header('Content-Type: application/json; charset=utf-8');

try {

    if (empty($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized'
        ]);
        exit;
    }

    $user_id = (int) $_SESSION['user_id'];

    $data = json_decode(file_get_contents('php://input'), true);
    $friend_id = isset($data['friend_id'])
        ? (int) $data['friend_id']
        : 0;

    if ($friend_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid friend ID provided'
        ]);
        exit;
    }

    // PDO
    $stmt = $conn->prepare("
        DELETE FROM user_friends
        WHERE
            (user_id_1 = ? AND user_id_2 = ?)
            OR
            (user_id_1 = ? AND user_id_2 = ?)
    ");

    $stmt->execute([
        $user_id,
        $friend_id,
        $friend_id,
        $user_id
    ]);

    if ($stmt->rowCount() > 0) {

        // Real-time notification using USER channel
        if (function_exists('get_pusher_instance')) {

            $pusher = get_pusher_instance();

            // Notify current user
            $pusher->trigger(
                "user-{$user_id}",
                'friend-removed',
                [
                    'friend_id' => $friend_id
                ]
            );

            // Notify the other user
            $pusher->trigger(
                "user-{$friend_id}",
                'friend-removed',
                [
                    'friend_id' => $user_id
                ]
            );
        }

        echo json_encode([
            'success' => true,
            'message' => 'Friend removed successfully'
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' => 'Relationship not found'
        ]);
    }

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}