<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php'; // Include Pusher Helper

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
$senderId   = $userId; // Defined to fix parameter check and payload
$senderName = $_SESSION['user_name'] ?? 'Someone';

$data = json_decode(file_get_contents('php://input'), true);
$friendId = (int)($data['friend_id'] ?? 0);

if (!$senderId || !$friendId) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit();
}

try {
    // 1. Check existing relationship in user_friends
    $checkSql = "SELECT user_id_1, user_id_2, status FROM user_friends 
                 WHERE (user_id_1 = :u1 AND user_id_2 = :u2) 
                    OR (user_id_1 = :u3 AND user_id_2 = :u4)
                 LIMIT 1";
                 
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([
        ':u1' => $userId,
        ':u2' => $friendId,
        ':u3' => $friendId,
        ':u4' => $userId
    ]);

    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if ($existing['status'] === 'accepted') {
            echo json_encode(['success' => false, 'message' => 'You are already friends.']);
            exit();
        }

        // If the other user already sent a pending request -> Automatically accept ("Add Back")
        if ($existing['status'] === 'pending' && (int)$existing['user_id_1'] === $friendId) {
            $updateStmt = $conn->prepare("UPDATE user_friends SET status = 'accepted' WHERE id = :id");
            $updateStmt->execute([':id' => $existing['id']]);

            // Notify original sender
            $notifStmt = $conn->prepare("
                INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at) 
                VALUES (:user_id, :sender_id, 'friend_accepted', 'accepted your friend request.', 0, NOW())
            ");
            $notifStmt->execute([':user_id' => $friendId, ':sender_id' => $userId]);

            echo json_encode(['success' => true, 'message' => 'Friend request accepted!', 'action' => 'accepted']);
            exit();
        }

        echo json_encode(['success' => false, 'message' => 'Friend request is already pending.']);
        exit();
    }

    // 2. Insert new friend request
    $insertStmt = $conn->prepare("
        INSERT INTO user_friends (user_id_1, user_id_2, status) 
        VALUES (:sender, :receiver, 'pending')
    ");
    $insertStmt->execute([
        ':sender'   => $userId,
        ':receiver' => $friendId
    ]);

    // 3. Send notification to receiver
    $notifStmt = $conn->prepare("
        INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at) 
        VALUES (:receiver, :sender, 'friend_request', 'sent you a friend request.', 0, NOW())
    ");
    $notifStmt->execute([
        ':receiver' => $friendId,
        ':sender'   => $userId
    ]);


    // Payload for live event
    $payload = [
        'type'        => 'friend_request',
        'sender_id'   => $senderId,
        'sender_name' => $senderName,
        'message'     => 'sent you a friend request.',
        'created_at'  => date('Y-m-d H:i:s')
    ];

    // Trigger event on target user's private channel
    // Adjust function name according to your pusher_helper.php implementation
    triggerPusherEvent("user-{$friendId}", "friend_event", $payload);
    echo json_encode(['success' => true, 'message' => 'Friend request sent!']);   

} catch (PDOException $e) {
    error_log("Add Friend Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}