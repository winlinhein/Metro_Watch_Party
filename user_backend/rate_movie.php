<?php
session_start();
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../conn.php';

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$movieId = intval($input['movie_id'] ?? 0);
$rating = floatval($input['rating'] ?? 0);
$userId = $_SESSION['user_id'] ?? 0;

if (!$userId || !$movieId) {
    echo json_encode(['success' => false, 'message' => 'Invalid user or movie ID']);
    exit;
}

try {
    // 1. Insert or Update rating
    $stmt = $conn->prepare("
        INSERT INTO movie_rating (user_id, movie_id, rating, created_at, updated_at) 
        VALUES (:user_id, :movie_id, :rating, NOW(), NOW())
        ON DUPLICATE KEY UPDATE rating = VALUES(rating), updated_at = NOW()
    ");
    $stmt->execute([
        'user_id'  => $userId,
        'movie_id' => $movieId,
        'rating'   => $rating
    ]);

    // 2. Recalculate average rating & total count
    $stmt = $conn->prepare("
        SELECT AVG(rating) as avg_rating, COUNT(user_id) as rating_count 
        FROM movie_rating 
        WHERE movie_id = :movie_id
    ");
    $stmt->execute(['movie_id' => $movieId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $avgRating = round($result['avg_rating'], 1);
    $ratingCount = $result['rating_count'];

    // 3. Broadcast to Pusher (ensure function exists)
    if (function_exists('triggerPusherEvent')) {
        triggerPusherEvent("movie-{$movieId}", 'rating_updated', [
            'movie_id' => $movieId,
            'avg_rating' => $avgRating,
            'rating_count' => $ratingCount
        ]);
        triggerPusherEvent('admin-comments', 'rating_updated', [
            'movie_id' => $movieId,
            'avg_rating' => $avgRating,
            'rating_count' => $ratingCount
        ]);
    } else {
        // Log that Pusher is missing but still consider it a success (or fail?)
        error_log("triggerPusherEvent function not defined");
        // You can still return success, but the UI won't update in real time.
    }

    echo json_encode(['success' => true, 'avg_rating' => $avgRating, 'rating_count' => $ratingCount]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}