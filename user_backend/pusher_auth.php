<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$channelName = (string)($_POST['channel_name'] ?? '');
$socketId = (string)($_POST['socket_id'] ?? '');
if (!preg_match('/^private-watch-party-(\d+)$/', $channelName, $matches)
    || !preg_match('/^\d+\.\d+$/', $socketId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid channel request']);
    exit;
}

try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../pusher_helper.php';

    if (PUSHER_KEY === '' || PUSHER_SECRET === '') {
        http_response_code(503);
        echo json_encode(['error' => 'Realtime service is unavailable']);
        exit;
    }

    $roomId = (int)$matches[1];
    $userId = (int)$_SESSION['user_id'];
    $roomStmt = $conn->prepare("SELECT host_id, status FROM rooms WHERE room_id = ? LIMIT 1");
    $roomStmt->execute([$roomId]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
    if (!$room || in_array(strtolower((string)$room['status']), ['ended', 'deleted', 'closed'], true)) {
        http_response_code(403);
        echo json_encode(['error' => 'Room is unavailable']);
        exit;
    }

    $isHost = (int)$room['host_id'] === $userId;
    if (!$isHost) {
        $kickStmt = $conn->prepare("SELECT 1 FROM room_kicks WHERE room_id = ? AND user_id = ? LIMIT 1");
        $kickStmt->execute([$roomId, $userId]);
        $memberStmt = $conn->prepare("SELECT 1 FROM room_participants WHERE room_id = ? AND user_id = ? AND last_seen > DATE_SUB(NOW(), INTERVAL 90 SECOND) LIMIT 1");
        $memberStmt->execute([$roomId, $userId]);
        if ($kickStmt->fetchColumn() || !$memberStmt->fetchColumn()) {
            http_response_code(403);
            echo json_encode(['error' => 'Room access denied']);
            exit;
        }
    }

    $signature = hash_hmac('sha256', $socketId . ':' . $channelName, PUSHER_SECRET);
    echo json_encode(['auth' => PUSHER_KEY . ':' . $signature]);
} catch (Throwable $e) {
    error_log('Pusher room authorization failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Could not authorize room access']);
}
