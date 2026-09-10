<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$requesterId = (int)$_SESSION['user_id'];
$requesterName = (string)($_SESSION['user_name'] ?? 'Someone');
$roomId = (int)($_POST['room_id'] ?? 0);
session_write_close();

if ($roomId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing room']);
    exit;
}

try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../pusher_helper.php';
    require_once __DIR__ . '/../profile_media_helper.php';

    $conn->exec("CREATE TABLE IF NOT EXISTS room_join_requests (
        id INT NOT NULL AUTO_INCREMENT,
        room_id INT NOT NULL,
        host_id INT NOT NULL,
        requester_id INT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY room_requester (room_id, requester_id),
        KEY host_status (host_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $roomStmt = $conn->prepare("SELECT room_id, host_id, room_code, status FROM rooms WHERE room_id = :id LIMIT 1");
    $roomStmt->execute(['id' => $roomId]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);

    if (!$room || ($room['status'] ?? '') === 'ended') {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'This watch party has ended.']);
        exit;
    }

    $hostId = (int)$room['host_id'];
    if ($hostId === $requesterId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You already host this room.']);
        exit;
    }

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
        'a' => $requesterId,
        'b' => $hostId,
        'a2' => $requesterId,
        'b2' => $hostId,
    ]);
    if (!$friendStmt->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only join rooms hosted by friends.']);
        exit;
    }

    $conn->prepare("
        INSERT INTO room_join_requests (room_id, host_id, requester_id, status, created_at)
        VALUES (:room_id, :host_id, :requester_id, 'pending', NOW())
        ON DUPLICATE KEY UPDATE
            status = 'pending',
            created_at = NOW()
    ")->execute([
        'room_id' => $roomId,
        'host_id' => $hostId,
        'requester_id' => $requesterId,
    ]);

    $idStmt = $conn->prepare("SELECT id FROM room_join_requests WHERE room_id = :room_id AND requester_id = :requester_id LIMIT 1");
    $idStmt->execute(['room_id' => $roomId, 'requester_id' => $requesterId]);
    $requestId = (int)$idStmt->fetchColumn();

    $message = 'wants to join your watch party.|room:' . $roomId . '|req:' . $requestId;
    $notifStmt = $conn->prepare("
        INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at)
        VALUES (:user_id, :sender_id, 'join_request', :message, 0, NOW())
    ");
    $notifStmt->execute([
        'user_id' => $hostId,
        'sender_id' => $requesterId,
        'message' => $message,
    ]);
    $notifId = (int)$conn->lastInsertId();

    $media = getUserProfileMedia($conn, $requesterId);
    $payload = array_merge([
        'id' => $notifId,
        'type' => 'join_request',
        'sender_id' => $requesterId,
        'senderId' => $requesterId,
        'sender_name' => $requesterName,
        'name' => $requesterName,
        'message' => 'wants to join the watch party.',
        'text' => 'wants to join the watch party.',
        'room_id' => $roomId,
        'request_id' => $requestId,
        'request_status' => 'pending',
        'time' => date('h:i A'),
        'avatar' => $media['avatar_url'] ?? '',
        'border' => $media['border_preview'] ?? '',
        'created_at' => date('Y-m-d H:i:s'),
        'is_read' => 0,
        'isSelf' => false,
    ], $media);

    triggerPusherEvent("user-{$hostId}", 'friend_event', $payload);
    triggerPusherEvent("watch-party-{$roomId}", 'join-request', $payload);
    triggerPusherEvent("watch-party-{$roomId}", 'new_message', $payload);

    echo json_encode(['success' => true, 'request_id' => $requestId, 'status' => 'pending']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not send join request']);
}
