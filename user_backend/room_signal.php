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
    'force-leave' => true,
    'force-mute' => true,
    'force-video' => true,
    'force-chat-ban' => true,
];

try {
    if (!empty($hostOnly[$event]) || $event === 'new_message') {
        require_once __DIR__ . '/../conn.php';
    }
    if (!empty($hostOnly[$event])) {
        $hostStmt = $conn->prepare('SELECT host_id, status FROM rooms WHERE room_id = :id LIMIT 1');
        $hostStmt->execute(['id' => $roomId]);
        $room = $hostStmt->fetch(PDO::FETCH_ASSOC);
        if (!$room || (int)$room['host_id'] !== $userId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only the host can do that']);
            exit;
        }
    }
    if ($event === 'new_message') {
        try {
            $banStmt = $conn->prepare("SELECT chat_banned FROM room_participants WHERE room_id = :room_id AND user_id = :user_id LIMIT 1");
            $banStmt->execute(['room_id' => $roomId, 'user_id' => $userId]);
            if ((int)$banStmt->fetchColumn() === 1) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'The host banned you from room chat.', 'chat_banned' => true]);
                exit;
            }
        } catch (Throwable $ignore) {}
    }

    require_once __DIR__ . '/../pusher_helper.php';
    triggerPusherEvent("watch-party-{$roomId}", $event, $payload);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Signal failed']);
}
