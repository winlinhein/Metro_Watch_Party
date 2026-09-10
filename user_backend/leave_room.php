<?php
session_start();
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$roomCode = $_REQUEST['room_code'] ?? null;
$roomId = $_REQUEST['room_id'] ?? null;

$identifier = $roomCode ?: $roomId;

if (!$userId || !$identifier) {
    echo json_encode(['success' => false, 'message' => 'Missing user session or room identifier']);
    exit;
}

try {
    require_once __DIR__ . '/../conn.php'; // Use $conn (PDO instance from conn.php)

    // Query exact column depending on parameter type
   // Remove the $identifier variable and replace the query block with this:
if ($roomCode) {
    $stmt = $conn->prepare("SELECT room_id, host_id FROM rooms WHERE room_code = :code LIMIT 1");
    $stmt->execute(['code' => $roomCode]);
} else {
    $stmt = $conn->prepare("SELECT room_id, host_id FROM rooms WHERE room_id = :id LIMIT 1");
    $stmt->execute(['id' => (int)$roomId]);
}

    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'This watch party has ended.', 'is_ended' => true]);
        exit;
    }

    require_once __DIR__ . '/../pusher_helper.php';
    $channel = 'watch-party-' . $room['room_id'];

    // If user is the host, tell everyone the party is over, then delete the room.
    if ((int)$room['host_id'] === (int)$userId) {
        try {
            triggerPusherEvent($channel, 'room-ended', [
                'fromUserId' => (int)$userId,
                'is_ended' => true,
                'message' => 'The host ended this watch party.',
            ]);
        } catch (Throwable $ignore) {}

        try {
            $conn->prepare("DELETE FROM room_participants WHERE room_id = :room_id")
                 ->execute(['room_id' => $room['room_id']]);
        } catch (Exception $ignore) {}

        try {
            $conn->prepare("DELETE FROM room_kicks WHERE room_id = :room_id")
                 ->execute(['room_id' => $room['room_id']]);
        } catch (Exception $ignore) {}

        $deleteStmt = $conn->prepare("DELETE FROM rooms WHERE room_id = :room_id");
        $deleteStmt->execute(['room_id' => $room['room_id']]);

        echo json_encode(['success' => true, 'message' => 'Room deleted', 'is_ended' => true]);
        exit;
    }

    try {
        $delStmt = $conn->prepare("DELETE FROM room_participants WHERE room_id = :room_id AND user_id = :user_id");
        $delStmt->execute(['room_id' => $room['room_id'], 'user_id' => $userId]);
    } catch (Exception $ignore) {}

    try {
        triggerPusherEvent($channel, 'peer-leave', [
            'userId' => (int)$userId,
            'fromUserId' => (int)$userId,
            'peerId' => (string)($_REQUEST['peer_id'] ?? ''),
            'socketId' => (string)($_REQUEST['peer_id'] ?? ''),
        ]);
    } catch (Throwable $ignore) {}

    echo json_encode(['success' => true, 'message' => 'Participant left']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database update error: ' . $e->getMessage()]);
}