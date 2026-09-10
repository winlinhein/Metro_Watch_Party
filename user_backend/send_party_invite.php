<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../profile_media_helper.php';

$senderId = (int)$_SESSION['user_id'];
$senderName = (string)($_SESSION['user_name'] ?? 'Someone');
$targetUserId = (int)($_POST['target_user_id'] ?? 0);
$roomId = (int)($_POST['room_id'] ?? 0);

if ($targetUserId <= 0 || $roomId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing friend or room.']);
    exit();
}

if ($targetUserId === $senderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You cannot invite yourself.']);
    exit();
}

try {
    // Must be accepted friends
    $friendStmt = $conn->prepare("
        SELECT 1 FROM user_friends
        WHERE status = 'accepted'
          AND (
            (user_id_1 = :a AND user_id_2 = :b)
            OR (user_id_1 = :b2 AND user_id_2 = :a2)
          )
        LIMIT 1
    ");
    $friendStmt->execute([
        'a' => $senderId,
        'b' => $targetUserId,
        'a2' => $senderId,
        'b2' => $targetUserId,
    ]);
    if (!$friendStmt->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only invite friends.']);
        exit();
    }

    // Room must exist and be hosted by sender (or at least exist)
    $roomStmt = $conn->prepare("SELECT room_id, host_id, room_code FROM rooms WHERE room_id = :rid LIMIT 1");
    $roomStmt->execute(['rid' => $roomId]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
    if (!$room) {
        // Some schemas use watch_rooms — try soft fallback by accepting room_id as-is
        $room = ['room_id' => $roomId, 'host_id' => $senderId, 'room_code' => (string)$roomId];
    }

    $message = 'invited you to a watch party.|room:' . $roomId;

    $notifStmt = $conn->prepare("
        INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at)
        VALUES (:user_id, :sender_id, 'party_invite', :message, 0, NOW())
    ");
    $notifStmt->execute([
        'user_id' => $targetUserId,
        'sender_id' => $senderId,
        'message' => $message,
    ]);
    $notifId = (int)$conn->lastInsertId();

    $senderMedia = getUserProfileMedia($conn, $senderId);

    $payload = array_merge([
        'id' => $notifId,
        'type' => 'party_invite',
        'sender_id' => $senderId,
        'sender_name' => $senderName,
        'message' => 'invited you to a watch party.',
        'room_id' => $roomId,
        'room_code' => $room['room_code'] ?? null,
        'created_at' => date('Y-m-d H:i:s'),
        'is_read' => 0,
    ], $senderMedia);

    triggerPusherEvent("user-{$targetUserId}", 'friend_event', $payload);

    echo json_encode([
        'success' => true,
        'message' => 'Invite sent!',
        'notification' => $payload,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send invite.']);
}
