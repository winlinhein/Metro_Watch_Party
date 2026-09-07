<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$roomId = (int)($_POST['room_id'] ?? 0);
$movieId = (int)($_POST['movie_id'] ?? 0);
session_write_close();

if ($roomId <= 0 || $movieId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing room or movie']);
    exit;
}

try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../poster_helper.php';

    $roomStmt = $conn->prepare("SELECT room_id, host_id, status FROM rooms WHERE room_id = :id LIMIT 1");
    $roomStmt->execute(['id' => $roomId]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);

    if (!$room) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Room not found']);
        exit;
    }

    if (($room['status'] ?? '') === 'ended') {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'This watch party has ended']);
        exit;
    }

    $movieStmt = $conn->prepare("
        SELECT movie_id, title, description, video_url, actual_video_url, duration, view_count, created_at
        FROM movies
        WHERE movie_id = :movie_id
        LIMIT 1
    ");
    $movieStmt->execute(['movie_id' => $movieId]);
    $movie = $movieStmt->fetch(PDO::FETCH_ASSOC);

    if (!$movie) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Movie not found']);
        exit;
    }

    $update = $conn->prepare("UPDATE rooms SET movie_id = :movie_id WHERE room_id = :room_id");
    $update->execute([
        'movie_id' => $movieId,
        'room_id' => $roomId,
    ]);

    $movie['img'] = moviePosterUrl($movie['movie_id']);
    $movie['cover_image'] = $movie['img'];
    $movie['trailer'] = $movie['video_url'];
    $movie['stream_url'] = $movie['actual_video_url'] ?: $movie['video_url'];
    $movie['id'] = (int)$movie['movie_id'];

    echo json_encode([
        'success' => true,
        'movie' => $movie,
        'updated_by' => $userId,
        'is_host' => ((int)$room['host_id'] === $userId),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to share movie']);
}
