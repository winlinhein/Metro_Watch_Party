<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$hostId = (int)$_SESSION['user_id'];
$roomId = (int)($_POST['room_id'] ?? 0);
$action = preg_replace('/[^a-z_]/', '', (string)($_POST['action'] ?? ''));
$targetUserId = (int)($_POST['target_user_id'] ?? 0);
$targetPeerId = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)($_POST['target_peer_id'] ?? '')), 0, 64);
$muted = isset($_POST['muted']) ? (($_POST['muted'] === '1' || $_POST['muted'] === 'true') ? true : false) : true;
$videoOn = isset($_POST['video_on']) ? (($_POST['video_on'] === '1' || $_POST['video_on'] === 'true') ? true : false) : false;
$chatBanned = isset($_POST['banned']) ? (($_POST['banned'] === '1' || $_POST['banned'] === 'true') ? true : false) : true;
session_write_close();

if ($roomId <= 0 || $targetUserId <= 0 || !in_array($action, ['kick', 'mute', 'video', 'chatban'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid host action']);
    exit;
}

if ($targetUserId === $hostId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You cannot do that to yourself']);
    exit;
}

function ensureRoomParticipantFlags(PDO $conn): void
{
    foreach (['forced_muted', 'forced_video_off', 'chat_banned'] as $col) {
        try {
            $conn->exec("ALTER TABLE room_participants ADD COLUMN {$col} TINYINT(1) NOT NULL DEFAULT 0");
        } catch (Throwable $ignore) {
        }
    }
}

try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../pusher_helper.php';

    $stmt = $conn->prepare("SELECT room_id, host_id, status FROM rooms WHERE room_id = :id LIMIT 1");
    $stmt->execute(['id' => $roomId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room || ($room['status'] ?? '') === 'ended') {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }

    if ((int)$room['host_id'] !== $hostId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only the host can do that']);
        exit;
    }

    ensureRoomParticipantFlags($conn);

    $baseEvent = [
        'targetUserId' => $targetUserId,
        'targetPeerId' => $targetPeerId,
        'fromUserId' => $hostId,
        'userId' => $targetUserId,
        'peerId' => $targetPeerId,
    ];

    if ($action === 'kick') {
        try {
            $conn->exec("CREATE TABLE IF NOT EXISTS room_kicks (
                room_id INT NOT NULL,
                user_id INT NOT NULL,
                kicked_at DATETIME NOT NULL,
                PRIMARY KEY (room_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $conn->prepare("
                INSERT INTO room_kicks (room_id, user_id, kicked_at)
                VALUES (:room_id, :user_id, NOW())
                ON DUPLICATE KEY UPDATE kicked_at = NOW()
            ")->execute(['room_id' => $roomId, 'user_id' => $targetUserId]);
        } catch (Throwable $ignore) {}

        try {
            $conn->prepare("DELETE FROM room_participants WHERE room_id = :room_id AND user_id = :user_id")
                ->execute(['room_id' => $roomId, 'user_id' => $targetUserId]);
        } catch (Throwable $ignore) {}

        triggerPusherEvent("watch-party-{$roomId}", 'force-leave', $baseEvent + [
            'message' => 'The host removed you from the room.',
        ]);

        echo json_encode(['success' => true, 'action' => 'kick']);
        exit;
    }

    if ($action === 'mute') {
        try {
            $conn->prepare("
                UPDATE room_participants
                SET forced_muted = :muted
                WHERE room_id = :room_id AND user_id = :user_id
            ")->execute([
                'muted' => $muted ? 1 : 0,
                'room_id' => $roomId,
                'user_id' => $targetUserId,
            ]);
        } catch (Throwable $ignore) {}

        triggerPusherEvent("watch-party-{$roomId}", 'force-mute', $baseEvent + [
            'muted' => $muted,
            'isMuted' => $muted,
        ]);

        echo json_encode(['success' => true, 'action' => 'mute', 'muted' => $muted]);
        exit;
    }

    if ($action === 'video') {
        try {
            $conn->prepare("
                UPDATE room_participants
                SET forced_video_off = :off
                WHERE room_id = :room_id AND user_id = :user_id
            ")->execute([
                'off' => $videoOn ? 0 : 1,
                'room_id' => $roomId,
                'user_id' => $targetUserId,
            ]);
        } catch (Throwable $ignore) {}

        triggerPusherEvent("watch-party-{$roomId}", 'force-video', $baseEvent + [
            'videoOn' => $videoOn,
            'isVideoOn' => $videoOn,
        ]);

        echo json_encode(['success' => true, 'action' => 'video', 'videoOn' => $videoOn]);
        exit;
    }

    try {
        $conn->prepare("
            UPDATE room_participants
            SET chat_banned = :banned
            WHERE room_id = :room_id AND user_id = :user_id
        ")->execute([
            'banned' => $chatBanned ? 1 : 0,
            'room_id' => $roomId,
            'user_id' => $targetUserId,
        ]);
    } catch (Throwable $ignore) {}

    triggerPusherEvent("watch-party-{$roomId}", 'force-chat-ban', $baseEvent + [
        'chatBanned' => $chatBanned,
        'banned' => $chatBanned,
        'message' => $chatBanned ? 'The host banned you from room chat.' : 'The host allowed you to chat again.',
    ]);

    echo json_encode(['success' => true, 'action' => 'chatban', 'chatBanned' => $chatBanned]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Host action failed']);
}
