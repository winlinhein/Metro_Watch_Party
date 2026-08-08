<?php
session_start();
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../conn.php'; 

$input = json_decode(file_get_contents('php://input'), true);
$movieId = intval($input['movie_id'] ?? 0);
$commentText = trim($input['comment'] ?? '');
$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Anonymous'; 

if (!$userId || !$movieId || empty($commentText)) {
    echo json_encode(['success' => false]);
    exit;
}

// 1. Insert comment into database
$stmt = $conn->prepare("
    INSERT INTO movie_comments (user_id, movie_id, comment_text, created_at) 
    VALUES (:user_id, :movie_id, :comment_text, NOW())
");
$stmt->execute([
    'user_id' => $userId,
    'movie_id' => $movieId,
    'comment_text' => $commentText
]);

$commentId = $conn->lastInsertId();

// 2. Prepare payload for Pusher
$commentData = [
    'id' => $commentId,
    'movie_id' => $movieId,
    'user_name' => $userName,
    'comment' => $commentText,
    'created_at' => date('Y-m-d H:i:s'),
    'likes_count' => 0,
    'replies' => []
];

// 3. Broadcast
triggerPusherEvent("movie-{$movieId}", 'new_comment', $commentData);

echo json_encode(['success' => true, 'comment' => $commentData]);