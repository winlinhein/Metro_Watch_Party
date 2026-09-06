<?php
session_start();
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../json_respond.php';
require_once __DIR__ . '/../profile_media_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$movieId = intval($input['movie_id'] ?? 0);
$parentId = intval($input['parent_id'] ?? 0);
$replyText = trim($input['comment'] ?? '');
$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Anonymous';
session_write_close();

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
    'user_id' => $userId,
    'user_name' => $userName,
    'comment' => $replyText,
    'comment_text' => $replyText,
    'created_at' => date('Y-m-d H:i:s'),
    'likes_count' => 0,
    'movie_title' => ''
];
$replyData = array_merge($replyData, getUserProfileMedia($conn, $userId));

jsonRespondAndContinue(['success' => true, 'reply' => $replyData]);

triggerPusherEvent("movie-{$movieId}", 'new_reply', $replyData);
triggerPusherEvent('admin-comments', 'new_reply', $replyData);