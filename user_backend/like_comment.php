<?php
session_start();
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../conn.php'; 

$input = json_decode(file_get_contents('php://input'), true);
$commentId = intval($input['comment_id'] ?? 0);
$userId = $_SESSION['user_id'] ?? 0;

if (!$userId || !$commentId) {
    echo json_encode(['success' => false]);
    exit;
}

// 1. Check if user already liked this comment
$stmt = $conn->prepare("SELECT 1 FROM comment_likes WHERE comment_id = :comment_id AND user_id = :user_id");
$stmt->execute(['comment_id' => $commentId, 'user_id' => $userId]);
$hasLiked = $stmt->fetchColumn();

if ($hasLiked) {
    // Unlike (Remove row)
    $stmt = $conn->prepare("DELETE FROM comment_likes WHERE comment_id = :comment_id AND user_id = :user_id");
    $stmt->execute(['comment_id' => $commentId, 'user_id' => $userId]);
} else {
    // Like (Insert row)
    $stmt = $conn->prepare("INSERT INTO comment_likes (comment_id, user_id, like_at) VALUES (:comment_id, :user_id, NOW())");
    $stmt->execute(['comment_id' => $commentId, 'user_id' => $userId]);
}

// 2. Get the new total likes count for this comment
$stmt = $conn->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = :comment_id");
$stmt->execute(['comment_id' => $commentId]);
$newLikesCount = $stmt->fetchColumn();

// 3. Get the movie_id associated with this comment for the Pusher channel
$stmt = $conn->prepare("SELECT movie_id FROM movie_comments WHERE comment_id = :comment_id");
$stmt->execute(['comment_id' => $commentId]);
$movieId = $stmt->fetchColumn();

if ($movieId) {
    // 4. Broadcast updated like count to everyone looking at this movie
    triggerPusherEvent("movie-{$movieId}", 'comment_liked', [
        'comment_id' => $commentId,
        'likes_count' => $newLikesCount
    ]);
}

echo json_encode(['success' => true]);