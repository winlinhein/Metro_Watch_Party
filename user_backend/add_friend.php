<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../profile_media_helper.php';

header('Content-Type: application/json');

$userId     = $_SESSION['user_id'] ?? 0;
$senderId   = $userId;
$senderName = $_SESSION['user_name'] ?? 'Someone';

$data     = json_decode(file_get_contents('php://input'), true);
$friendId = (int)($data['friend_id'] ?? 0);
session_write_close();

$senderMedia = getUserProfileMedia($conn, (int)$senderId);

// 1. Validation & Self-Request Check
if (!$senderId || !$friendId) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit();
}

if ($senderId === $friendId) {
    echo json_encode(['success' => false, 'message' => 'You cannot send a friend request to yourself.']);
    exit();
}

try {
    // 2. Check existing relationship
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

        // Auto-accept if the target user already sent a pending request
        if ($existing['status'] === 'pending' && (int)$existing['user_id_1'] === $friendId) {
            $updateStmt = $conn->prepare("
                UPDATE user_friends 
                SET status = 'accepted' 
                WHERE user_id_1 = :u1 AND user_id_2 = :u2
            ");
            $updateStmt->execute([
                ':u1' => $existing['user_id_1'],
                ':u2' => $existing['user_id_2']
            ]);
            
            // Insert notification
            $notifStmt = $conn->prepare("
                INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at) 
                VALUES (:user_id, :sender_id, 'friend_accepted', 'accepted your friend request.', 0, NOW())
            ");
            $notifStmt->execute([':user_id' => $friendId, ':sender_id' => $userId]);

            // Real-time Pusher notification for the auto-accept event
            $payload = array_merge([
                'type'        => 'friend_accepted',
                'sender_id'   => $senderId,
                'sender_name' => $senderName,
                'message'     => 'accepted your friend request.',
                'created_at'  => date('Y-m-d H:i:s')
            ], $senderMedia);
            triggerPusherEvent("user-{$friendId}", "friend_event", $payload);

            echo json_encode([
                'success' => true, 
                'message' => 'Friend request accepted!', 
                'status'  => 'accepted'
            ]);
            exit();
        }

        echo json_encode(['success' => false, 'message' => 'Friend request is already pending.']);
        exit();
    }

    // 3. Insert new friend request
    $insertStmt = $conn->prepare("
        INSERT INTO user_friends (user_id_1, user_id_2, status) 
        VALUES (:sender, :receiver, 'pending')
    ");
    $insertStmt->execute([
        ':sender'   => $userId,
        ':receiver' => $friendId
    ]);

    // Insert notification
    $notifStmt = $conn->prepare("
        INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at) 
        VALUES (:receiver, :sender, 'friend_request', 'sent you a friend request.', 0, NOW())
    ");
    $notifStmt->execute([
        ':receiver' => $friendId,
        ':sender'   => $userId
    ]);

    // 4. Trigger event on target user's channel (include avatar/border for live UI)
    $payload = array_merge([
        'type'        => 'friend_request',
        'sender_id'   => $senderId,
        'sender_name' => $senderName,
        'message'     => 'sent you a friend request.',
        'created_at'  => date('Y-m-d H:i:s')
    ], $senderMedia);

    triggerPusherEvent("user-{$friendId}", "friend_event", $payload);

    echo json_encode([
        'success' => true, 
        'message' => 'Friend request sent!', 
        'status'  => 'pending'
    ]);   

} catch (PDOException $e) {
    error_log("Add Friend Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A server error occurred. Please try again later.']);
}