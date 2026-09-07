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
    'new_message' => true,
    'playback-sync' => true,
    'toggle-mic' => true,
    'toggle-video' => true,
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

try {
    require_once __DIR__ . '/../pusher_helper.php';
    triggerPusherEvent("watch-party-{$roomId}", $event, $payload);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Signal failed']);
}
