<?php
session_start();
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../conn.php'; 

$input = json_decode(file_get_contents('php://input'), true);
$movieId = intval($input['movie_id'] ?? 0);
$parentId = intval($input['parent_id'] ?? 0); // The comment being replied to
$replyText = trim($input['comment'] ?? '');
$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Anonymous';

if (!$userId || !$movieId || !$parentId || empty($replyText)) {
    echo json_encode(['success' => false]);
    exit;
}

// 1. Insert reply into DB
$stmt = $conn->prepare("
    INSERT INTO movie_comments (user_id, movie_id, parent_comment_id, comment_text, created_at) 
    VALUES (:user_id, :movie_id, :parent_id, :comment_text, NOW())
");
$stmt->execute([
    'user_id' => $userId,
    'movie_id' => $movieId,
    'parent_id' => $parentId,
    'comment_text' => $replyText
]);

$replyId = $conn->lastInsertId();

// 2. Prepare payload
$replyData = [
    'id' => $replyId,
    'parent_id' => $parentId,
    'movie_id' => $movieId,
    'user_name' => $userName,
    'comment' => $replyText,
    'created_at' => date('Y-m-d H:i:s'),
    'likes_count' => 0
];

// 3. Broadcast
triggerPusherEvent("movie-{$movieId}", 'new_reply', $replyData);

echo json_encode(['success' => true, 'reply' => $replyData]);