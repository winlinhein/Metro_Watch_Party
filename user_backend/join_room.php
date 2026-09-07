<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userName = (string)($_SESSION['user_name'] ?? 'Guest');
$roomId = (int)($_POST['room_id'] ?? $_GET['room_id'] ?? 0);
$peerId = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($_POST['peer_id'] ?? '')), 0, 64);
$heartbeat = !empty($_POST['heartbeat']);
session_write_close();

if ($roomId <= 0 || $peerId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing room or peer']);
    exit;
}

try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../pusher_helper.php';
    require_once __DIR__ . '/../profile_media_helper.php';

    $roomStmt = $conn->prepare("SELECT room_id, host_id, status FROM rooms WHERE room_id = :id LIMIT 1");
    $roomStmt->execute(['id' => $roomId]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
    if (!$room || ($room['status'] ?? '') === 'ended') {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }

    $conn->exec("CREATE TABLE IF NOT EXISTS room_participants (
        room_id INT NOT NULL,
        user_id INT NOT NULL,
        user_name VARCHAR(191) NOT NULL DEFAULT '',
        peer_id VARCHAR(64) NOT NULL,
        last_seen DATETIME NOT NULL,
        PRIMARY KEY (peer_id),
        KEY room_seen (room_id, last_seen)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->prepare("
        DELETE FROM room_participants
        WHERE room_id = :room_id
          AND (last_seen < DATE_SUB(NOW(), INTERVAL 45 SECOND)
               OR (user_id = :user_id AND peer_id <> :peer_id))
    ")->execute([
        'room_id' => $roomId,
        'user_id' => $userId,
        'peer_id' => $peerId,
    ]);

    $existsStmt = $conn->prepare("SELECT 1 FROM room_participants WHERE peer_id = :peer_id LIMIT 1");
    $existsStmt->execute(['peer_id' => $peerId]);
    $alreadyThere = (bool)$existsStmt->fetchColumn();

    $conn->prepare("
        INSERT INTO room_participants (room_id, user_id, user_name, peer_id, last_seen)
        VALUES (:room_id, :user_id, :user_name, :peer_id, NOW())
        ON DUPLICATE KEY UPDATE
            room_id = VALUES(room_id),
            user_id = VALUES(user_id),
            user_name = VALUES(user_name),
            last_seen = NOW()
    ")->execute([
        'room_id' => $roomId,
        'user_id' => $userId,
        'user_name' => $userName,
        'peer_id' => $peerId,
    ]);

    $peerStmt = $conn->prepare("
        SELECT user_id, user_name, peer_id
        FROM room_participants
        WHERE room_id = :room_id
          AND peer_id <> :peer_id
          AND last_seen > DATE_SUB(NOW(), INTERVAL 45 SECOND)
    ");
    $peerStmt->execute(['room_id' => $roomId, 'peer_id' => $peerId]);
    $peers = attachProfileMedia($conn, $peerStmt->fetchAll(PDO::FETCH_ASSOC));
    $selfMedia = getUserProfileMedia($conn, $userId);

    $payload = [
        'peerId' => $peerId,
        'userId' => $userId,
        'userName' => $userName,
        'socketId' => $peerId,
        'avatar_url' => $selfMedia['avatar_url'] ?? '',
        'border_preview' => $selfMedia['border_preview'] ?? '',
    ];

    if (!$heartbeat || !$alreadyThere) {
        triggerPusherEvent("watch-party-{$roomId}", 'peer-join', $payload);
    }

    echo json_encode([
        'success' => true,
        'peers' => $peers,
        'you' => $payload,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to join room']);
}
