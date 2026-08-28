<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$roomId = $_GET['room_id'] ?? null;
session_write_close();

if (!$roomId) {
    echo json_encode(['success' => false, 'message' => 'Missing room identifier']);
    exit;
}

try {
    require_once '../conn.php';
    require_once '../poster_helper.php';
    $pdo = $conn;
    // 1. Fetch room using room_id or room_code
    $stmt = $pdo->prepare("SELECT room_id, room_code, host_id, movie_id, status, created_at FROM rooms WHERE (room_id = :id OR room_code = :code) LIMIT 1");
    $stmt->execute(['id' => $roomId, 'code' => $roomId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }

    // 2. Lifespan check: Block access if the room status is 'ended'
    if ($room['status'] === 'ended') {
        echo json_encode(['success' => false, 'message' => 'This watch party has ended by the host.', 'is_ended' => true]);
        exit;
    }

    // 3. Optional: Fetch movie metadata if movie_id is set
    $movie = null;
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
            $movie['img'] = moviePosterUrl($movie['movie_id']);
            $movie['cover_image'] = $movie['img'];
            $movie['trailer'] = $movie['video_url'];
            $movie['stream_url'] = $movie['actual_video_url'] ?: $movie['video_url'];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'room' => $room,
            'movie' => $movie
        ]
    ]);

} catch (Throwable $e) { // Use Throwable to catch all PHP errors, not just Exceptions
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Backend error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}