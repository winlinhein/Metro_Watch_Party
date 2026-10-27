<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$roomId = (int)($_POST['room_id'] ?? 0);
$event = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($_POST['event'] ?? ''));
$rawPayload = $_POST['payload'] ?? '';
session_write_close();

$allowed = [
    'offer' => true,
    'answer' => true,
    'ice-candidate' => true,
    'peer-leave' => true,
    'peer-park' => true,
    'peer-resume' => true,
    'new_message' => true,
    'room-ended' => true,
    'playback-sync' => true,
    'toggle-mic' => true,
    'toggle-video' => true,
    'movie-changed' => true,
    'force-leave' => true,
    'force-mute' => true,
    'force-video' => true,
    'force-chat-ban' => true,
];

if ($roomId <= 0 || $event === '' || empty($allowed[$event])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid signal']);
    exit;
}

$payload = json_decode($rawPayload, true);
if (!is_array($payload)) {
    $payload = [];
}

$payload['fromUserId'] = $userId;
$payload['roomId'] = $roomId;

$hostOnly = [
    'room-ended' => true,
    'force-leave' => true,
    'force-mute' => true,
    'force-video' => true,
    'force-chat-ban' => true,
];

try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../admin_rooms_helper.php';
    require_once __DIR__ . '/../room_schema_helper.php';
    ensureRoomParticipantSchema($conn);

    $roomStmt = $conn->prepare('SELECT room_id, host_id, status FROM rooms WHERE room_id = :id LIMIT 1');
    $roomStmt->execute(['id' => $roomId]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
    if (!$room) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }
    if (isRoomClosed($room['status'] ?? '')) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This watch party has ended']);
        exit;
    }

    $memberStmt = $conn->prepare("SELECT chat_banned FROM room_participants WHERE room_id = :room_id AND user_id = :user_id AND last_seen > DATE_SUB(NOW(), INTERVAL 90 SECOND) ORDER BY last_seen DESC LIMIT 1");
    $memberStmt->execute(['room_id' => $roomId, 'user_id' => $userId]);
    $membership = $memberStmt->fetch(PDO::FETCH_ASSOC);
    if (!$membership) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Join this watch party before sending signals.']);
        exit;
    }

    if (!empty($hostOnly[$event])) {
        if ((int)$room['host_id'] !== $userId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only the host can do that']);
            exit;
        }
    }
    if ($event === 'new_message' && (int)$membership['chat_banned'] === 1) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'The host banned you from room chat.', 'chat_banned' => true]);
        exit;
    }

    require_once __DIR__ . '/../pusher_helper.php';
    triggerPusherEvent("watch-party-{$roomId}", $event, $payload, true);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Signal failed']);
}
