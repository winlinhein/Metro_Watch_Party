<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$roomId = trim((string)($_GET['room_id'] ?? ''));
session_write_close();

try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../admin_rooms_helper.php';
    require_once __DIR__ . '/../profile_media_helper.php';
    require_once __DIR__ . '/../room_schema_helper.php';
    require_once __DIR__ . '/../schema_upgrade_helper.php';
    ensureAppSchema($conn);
    ensureRoomParticipantSchema($conn);

    $room = null;
    if ($roomId !== '') {
        $stmt = $conn->prepare("SELECT room_id, room_code, host_id, status FROM rooms WHERE room_id = :id OR room_code = :code LIMIT 1");
        $stmt->execute(['id' => $roomId, 'code' => $roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } else {
        $stmt = $conn->prepare("
            SELECT r.room_id, r.room_code, r.host_id, r.status
            FROM room_participants rp
            JOIN rooms r ON r.room_id = rp.room_id
            WHERE rp.user_id = :user_id
              AND rp.last_seen > DATE_SUB(NOW(), INTERVAL 90 SECOND)
            ORDER BY rp.last_seen DESC
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $userId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'No active room', 'is_ended' => $roomId !== '']);
        exit;
    }

    if (isRoomClosed($room['status'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'This watch party has ended.', 'is_ended' => true]);
        exit;
    }

    $kickStmt = $conn->prepare("SELECT 1 FROM room_kicks WHERE room_id = :room_id AND user_id = :user_id LIMIT 1");
    $kickStmt->execute(['room_id' => $room['room_id'], 'user_id' => $userId]);
    if ($kickStmt->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'The host removed you from the room.', 'is_kicked' => true]);
        exit;
    }

    $peerStmt = $conn->prepare("
        SELECT rp.user_id, COALESCE(u.user_name, 'User') AS user_name, rp.peer_id
        FROM room_participants rp
        LEFT JOIN users u ON u.user_id = rp.user_id
        WHERE rp.room_id = :room_id
          AND rp.last_seen > DATE_SUB(NOW(), INTERVAL 90 SECOND)
        ORDER BY rp.last_seen DESC
    ");
    $peerStmt->execute(['room_id' => $room['room_id']]);
    $participants = attachProfileMedia($conn, $peerStmt->fetchAll(PDO::FETCH_ASSOC) ?: []);

    echo json_encode([
        'success' => true,
        'data' => [
            'room' => [
                'room_id' => (int)$room['room_id'],
                'room_code' => $room['room_code'],
                'host_id' => (int)$room['host_id'],
                'status' => $room['status'],
            ],
            'participants' => array_map(static function ($row) {
                return [
                    'user_id' => (int)($row['user_id'] ?? 0),
                    'user_name' => $row['user_name'] ?? 'User',
                    'peer_id' => $row['peer_id'] ?? '',
                    'avatar_url' => $row['avatar_url'] ?? '',
                    'border_preview' => $row['border_preview'] ?? '',
                ];
            }, $participants),
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load active room']);
}
