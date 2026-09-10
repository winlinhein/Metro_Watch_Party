<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';   // needed for triggerPusherEvent
require_once __DIR__ . '/mission_progress.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$movieId = intval($input['movie_id'] ?? 0);

if (!$movieId) {
    echo json_encode(['success' => false, 'message' => 'Missing movie_id']);
    exit;
}

$userId = $_SESSION['user_id'] ?? 0; // guests = 0; change if login required

try {
    
    if ($userId > 0) {
        $stmt = $conn->prepare("SELECT 1 FROM watch_history WHERE user_id = ? AND movie_id = ? AND DATE(watched_at) = CURDATE() LIMIT 1");
        $stmt->execute([$userId, $movieId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Already viewed today']);
            exit;
        }
    }

    $conn->beginTransaction();

    // 1. Insert watch history
    $stmt = $conn->prepare("
        INSERT INTO watch_history (user_id, movie_id, watched_at)
        VALUES (:user_id, :movie_id, NOW())
    ");
    $stmt->execute([
        'user_id' => $userId,
        'movie_id' => $movieId
    ]);

    // 2. Increment view count
    $stmt = $conn->prepare("
        UPDATE movies
        SET view_count = view_count + 1
        WHERE movie_id = :movie_id
    ");
    $stmt->execute(['movie_id' => $movieId]);

    $conn->commit();

    // 3. Fetch new count
    $stmt = $conn->prepare("SELECT view_count FROM movies WHERE movie_id = ?");
    $stmt->execute([$movieId]);
    $newCount = $stmt->fetchColumn();

    // 4. Broadcast to movie-specific channel
    $viewData = [
        'movie_id' => $movieId,
        'view_count' => $newCount
    ];
    triggerPusherEvent("movie-{$movieId}", 'view_count_updated', $viewData);

    // 5. Update mission progress for watching a movie
    try {
        updateMissionProgress($userId, 'watch_movie', 1);
    } catch (Exception $e) {
        error_log('Mission update failed: ' . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'view_count' => $newCount
    ]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to record view']);
}