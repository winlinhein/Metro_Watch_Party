<?php
session_start();
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../json_respond.php';
require_once __DIR__ . '/../profile_media_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$movieId = intval($input['movie_id'] ?? 0);
$commentText = trim($input['comment'] ?? '');
$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Anonymous';
session_write_close();

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

// 2. Fetch movie title for admin event
$movieTitle = '';
$titleStmt = $conn->prepare("SELECT title FROM movies WHERE movie_id = ?");
$titleStmt->execute([$movieId]);
if ($row = $titleStmt->fetch()) {
    $movieTitle = $row['title'];
}

// 3. Prepare payload
$commentData = [
    'id' => $commentId,
    'movie_id' => $movieId,
    'user_id' => $userId,
    'user_name' => $userName,
    'comment' => $commentText,
    'comment_text' => $commentText,
    'created_at' => date('Y-m-d H:i:s'),
    'likes_count' => 0,
    'parent_id' => null,
    'movie_title' => $movieTitle
];
$commentData = array_merge($commentData, getUserProfileMedia($conn, $userId));

jsonRespondAndContinue(['success' => true, 'comment' => $commentData]);

triggerPusherEvent("movie-{$movieId}", 'new_comment', $commentData);
triggerPusherEvent('admin-comments', 'new_comment', $commentData);