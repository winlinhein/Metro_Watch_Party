<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../admin_rooms_helper.php';

header('Content-Type: application/json');

$role = strtolower((string)($_SESSION['user_role'] ?? ''));
if (
    empty($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true ||
    !in_array($role, ['admin', 'moderator'], true)
) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$adminId = (int)($_SESSION['user_id'] ?? 0);
session_write_close();

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$roomId = (int)($data['room_id'] ?? $_GET['room_id'] ?? 0);
if ($roomId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing room id']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT room_id, status FROM rooms WHERE room_id = :id LIMIT 1");
    $stmt->execute(['id' => $roomId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }

    if (isRoomClosed($room['status'] ?? '')) {
        broadcastAdminRoomsChanged('force-close', ['room_id' => $roomId]);
        echo json_encode(['success' => true, 'message' => 'Room already closed']);
        exit;
    }

    closeWatchPartyRoom($conn, $roomId, [
        'from_user_id' => $adminId,
        'forced_by_admin' => true,
        'message' => 'An admin closed this watch party.',
    ]);

    echo json_encode(['success' => true, 'message' => 'Room closed']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to close room']);
}
