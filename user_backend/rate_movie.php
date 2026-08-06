<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$movieId = intval($input['movie_id'] ?? 0);
$rating = floatval($input['rating'] ?? 0);

if (!$movieId || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid rating value']);
    exit();
}

// 1. Insert or Update rating in movie_rating table
$stmt = $conn->prepare("
    INSERT INTO movie_rating (user_id, movie_id, rating)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        rating = VALUES(rating),
        updated_at = CURRENT_TIMESTAMP
");
$stmt->execute([$_SESSION['user_id'], $movieId, $rating]);

// 2. Fetch updated average rating and count
$statsStmt = $conn->prepare("
    SELECT 
        COALESCE(ROUND(AVG(rating), 1), 0) AS avg_rating,
        COUNT(*) AS rating_count
    FROM movie_rating
    WHERE movie_id = ?
");
$statsStmt->execute([$movieId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$payload = [
    'movie_id' => $movieId,
    'avg_rating' => $stats['avg_rating'],
    'rating_count' => $stats['rating_count']
];

// 3. Broadcast updated rating via pusher_helper
if (isset($pusher)) {
    $pusher->trigger("movie-{$movieId}", 'rating_updated', $payload);
} elseif (function_exists('triggerPusherEvent')) {
    triggerPusherEvent("movie-{$movieId}", 'rating_updated', $payload);
}

echo json_encode(['success' => true, 'stats' => $stats]);