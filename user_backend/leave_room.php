<?php
session_start();
header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;
$roomCode = $_REQUEST['room_code'] ?? null;
$roomId = $_REQUEST['room_id'] ?? null;
session_write_close();

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
    require_once __DIR__ . '/../admin_rooms_helper.php';
    $channel = 'watch-party-' . $room['room_id'];

    // If user is the host, tell everyone the party is over, then close the room.
    if ((int)$room['host_id'] === (int)$userId) {
        closeWatchPartyRoom($conn, (int)$room['room_id'], [
            'from_user_id' => (int)$userId,
            'forced_by_admin' => false,
            'message' => 'The host ended this watch party.',
        ]);

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

    broadcastAdminRoomsChanged('leave', [
        'room_id' => (int)$room['room_id'],
        'user_id' => (int)$userId,
    ]);

    echo json_encode(['success' => true, 'message' => 'Participant left']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database update error: ' . $e->getMessage()]);
}