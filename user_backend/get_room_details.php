<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$roomId = $_GET['room_id'] ?? null;
session_write_close();

if (!$roomId) {
    echo json_encode(['success' => false, 'message' => 'Missing room identifier']);
    exit;
}

try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../poster_helper.php';
    require_once __DIR__ . '/../admin_rooms_helper.php';
    require_once __DIR__ . '/../profile_media_helper.php';
    require_once __DIR__ . '/../room_chat_helper.php';
    require_once __DIR__ . '/../schema_upgrade_helper.php';
    require_once __DIR__ . '/../premium_benefits_helper.php';
    ensureAppSchema($conn);
    $pdo = $conn;
    // 1. Fetch room using room_id or room_code
    $stmt = $pdo->prepare("SELECT room_id, room_code, host_id, movie_id, status, created_at, max_members FROM rooms WHERE (room_id = :id OR room_code = :code) LIMIT 1");
    $stmt->execute(['id' => $roomId, 'code' => $roomId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'This watch party has ended.', 'is_ended' => true]);
        exit;
    }

    // 2. Lifespan check: Block access if the room was closed
    if (isRoomClosed($room['status'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'This watch party has ended by the host.', 'is_ended' => true]);
        exit;
    }

    $isRoomMember = (int)$room['host_id'] === $userId;
    if (!$isRoomMember) {
        $memberStmt = $pdo->prepare("SELECT 1 FROM room_participants WHERE room_id = :room_id AND user_id = :user_id AND last_seen > DATE_SUB(NOW(), INTERVAL 90 SECOND) LIMIT 1");
        $memberStmt->execute(['room_id' => (int)$room['room_id'], 'user_id' => $userId]);
        $isRoomMember = (bool)$memberStmt->fetchColumn();
    }

    // 3. Optional: Fetch movie metadata if movie_id is set
    $movie = null;
    $room['movie_id'] = (int)($room['movie_id'] ?? 0);
    if ($room['movie_id'] > 0) {
        $movieStmt = $pdo->prepare("
            SELECT movie_id, title, description, video_url, actual_video_url, duration, view_count, created_at
            FROM movies
            WHERE movie_id = :movie_id
            LIMIT 1
        ");
        $movieStmt->execute(['movie_id' => $room['movie_id']]);
        $movie = $movieStmt->fetch(PDO::FETCH_ASSOC);
        if ($movie) {
            $movie['id'] = (int)$movie['movie_id'];
            $movie['img'] = moviePosterUrl($movie['movie_id']);
            $movie['cover_image'] = $movie['img'];
            $movie['trailer'] = $movie['video_url'];
            $movie['stream_url'] = $movie['actual_video_url'] ?: $movie['video_url'];
        }
    }

    $messages = [];
    if ($isRoomMember) {
        try {
            $messages = fetchRoomChatMessages($pdo, (int)$room['room_id']);
        } catch (Throwable $ignore) {
            $messages = [];
        }
    }

    $occ = nexusRoomOccupancy($pdo, $room);
    $room['members'] = $occ['members'];
    $room['max_members'] = $occ['max_members'];
    $room['occupancy'] = $occ['occupancy'];

    echo json_encode([
        'success' => true,
        'data' => [
            'room' => $room,
            'movie' => $movie,
            'messages' => $messages,
            'chat_visible' => $isRoomMember,
        ]
    ]);

} catch (Throwable $e) { // Use Throwable to catch all PHP errors, not just Exceptions
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Could not load watch party details.'
    ]);
}
