<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$hostId = (int)$_SESSION['user_id'];
$action = preg_replace('/[^a-z]/', '', (string)($_POST['action'] ?? ''));
$requestId = (int)($_POST['request_id'] ?? 0);
$roomId = (int)($_POST['room_id'] ?? 0);
$requesterId = (int)($_POST['requester_id'] ?? 0);
session_write_close();

if (!in_array($action, ['accept', 'decline'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../pusher_helper.php';
    require_once __DIR__ . '/../notifications_helper.php';
    require_once __DIR__ . '/../profile_media_helper.php';

    if ($requestId > 0) {
        $stmt = $conn->prepare("SELECT * FROM room_join_requests WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $requestId]);
    } else {
        $stmt = $conn->prepare("
            SELECT * FROM room_join_requests
            WHERE room_id = :room_id AND requester_id = :requester_id
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute(['room_id' => $roomId, 'requester_id' => $requesterId]);
    }
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Join request not found']);
        exit;
    }

    if ((int)$request['host_id'] !== $hostId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only the host can respond']);
        exit;
    }

    if ($action === 'accept') {
        require_once __DIR__ . '/../premium_benefits_helper.php';
        require_once __DIR__ . '/../schema_upgrade_helper.php';
        ensureAppSchema($conn);
        $roomStmt = $conn->prepare("SELECT room_id, host_id, max_members FROM rooms WHERE room_id = ? LIMIT 1");
        $roomStmt->execute([(int)$request['room_id']]);
        $room = $roomStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if ($room && nexusRoomIsFull($conn, $room, (int)$request['requester_id'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'This room is full (' . nexusRoomMaxMembers($conn, $room) . ' people).']);
            exit;
        }
    }

    $status = $action === 'accept' ? 'accepted' : 'declined';
    $conn->prepare("UPDATE room_join_requests SET status = :status WHERE id = :id")
        ->execute(['status' => $status, 'id' => (int)$request['id']]);

    $targetId = (int)$request['requester_id'];
    $roomId = (int)$request['room_id'];

    $deletedIds = deleteMatchingNotifications($conn, $hostId, [
        'types' => ['join_request'],
        'sender_id' => $targetId,
        'room_id' => $roomId,
    ]);
    $type = $action === 'accept' ? 'join_request_accepted' : 'join_request_declined';
    $message = $action === 'accept'
        ? ('accepted your request to join the watch party.|room:' . $roomId)
        : ('declined your request to join the watch party.|room:' . $roomId);

    $notifStmt = $conn->prepare("
        INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at)
        VALUES (:user_id, :sender_id, :type, :message, 0, NOW())
    ");
    $notifStmt->execute([
        'user_id' => $targetId,
        'sender_id' => $hostId,
        'type' => $type,
        'message' => $message,
    ]);
    $notifId = (int)$conn->lastInsertId();

    $hostName = (string)($_SESSION['user_name'] ?? 'The host');
    $media = getUserProfileMedia($conn, $hostId);
    $payload = array_merge([
        'id' => $notifId,
        'type' => $type,
        'sender_id' => $hostId,
        'sender_name' => $hostName,
        'message' => $action === 'accept'
            ? 'accepted your request to join the watch party.'
            : 'declined your request to join the watch party.',
        'room_id' => $roomId,
        'request_id' => (int)$request['id'],
        'created_at' => date('Y-m-d H:i:s'),
        'is_read' => 0,
    ], $media);

    triggerPusherEvent("user-{$targetId}", 'friend_event', $payload);
    $resolved = [
        'request_id' => (int)$request['id'],
        'requester_id' => $targetId,
        'sender_id' => $targetId,
        'status' => $status,
        'request_status' => $status,
        'room_id' => $roomId,
        'type' => 'join_request',
    ];
    triggerPusherEvent("watch-party-{$roomId}", 'join-request-resolved', $resolved);

    echo json_encode([
        'success' => true,
        'status' => $status,
        'room_id' => $roomId,
        'deleted_notification_ids' => $deletedIds,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not respond to join request']);
}
