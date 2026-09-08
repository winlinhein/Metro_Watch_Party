<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$hostId = (int)$_SESSION['user_id'];
$roomId = (int)($_GET['room_id'] ?? $_POST['room_id'] ?? 0);
session_write_close();

if ($roomId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing room']);
    exit;
}

try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../profile_media_helper.php';

    $roomStmt = $conn->prepare("SELECT room_id, host_id FROM rooms WHERE room_id = :id LIMIT 1");
    $roomStmt->execute(['id' => $roomId]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
    if (!$room || (int)$room['host_id'] !== $hostId) {
        echo json_encode(['success' => true, 'requests' => []]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT r.id, r.room_id, r.requester_id, r.status, r.created_at, u.user_name AS sender_name
        FROM room_join_requests r
        JOIN users u ON u.user_id = r.requester_id
        WHERE r.room_id = :room_id AND r.host_id = :host_id AND r.status = 'pending'
        ORDER BY r.created_at ASC
    ");
    $stmt->execute(['room_id' => $roomId, 'host_id' => $hostId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rows = attachProfileMedia($conn, array_map(static function ($row) {
        $row['user_id'] = (int)$row['requester_id'];
        return $row;
    }, $rows));

    $requests = array_map(static function ($row) {
        return [
            'id' => (int)$row['id'],
            'request_id' => (int)$row['id'],
            'room_id' => (int)$row['room_id'],
            'sender_id' => (int)$row['requester_id'],
            'sender_name' => $row['sender_name'] ?? 'Friend',
            'message' => 'wants to join the watch party.',
            'text' => 'wants to join the watch party.',
            'request_status' => 'pending',
            'avatar_url' => $row['avatar_url'] ?? '',
            'border_preview' => $row['border_preview'] ?? '',
            'created_at' => $row['created_at'] ?? '',
            'type' => 'join_request',
        ];
    }, $rows);

    echo json_encode(['success' => true, 'requests' => $requests]);
} catch (Throwable $e) {
    echo json_encode(['success' => true, 'requests' => []]);
}
