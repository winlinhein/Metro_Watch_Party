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
$parentId = !empty($input['parent_id']) ? intval($input['parent_id']) : null;
$commentText = trim($input['comment'] ?? '');

if (!$movieId || empty($commentText)) {
    echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
    exit();
}

// 1. Insert comment into movie_comments table
$stmt = $conn->prepare("
    INSERT INTO movie_comments (user_id, movie_id, parent_comment_id, comment_text)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$_SESSION['user_id'], $movieId, $parentId, $commentText]);
$newCommentId = $conn->lastInsertId();

// 2. Retrieve newly inserted comment details with user name
$stmtFetch = $conn->prepare("
    SELECT 
        c.comment_id AS id, 
        c.movie_id, 
        c.user_id, 
        c.parent_comment_id AS parent_id,
        c.comment_text AS comment, 
        c.created_at, 
        u.name AS user_name
    FROM movie_comments c
    INNER JOIN users u ON c.user_id = u.user_id
    WHERE c.comment_id = ?
");
$stmtFetch->execute([$newCommentId]);
$commentData = $stmtFetch->fetch(PDO::FETCH_ASSOC);
$commentData['replies'] = [];

// 3. Broadcast real-time comment using pusher_helper
if (isset($pusher)) {
    $pusher->trigger("movie-{$movieId}", 'new_comment', $commentData);
} elseif (function_exists('triggerPusherEvent')) {
    triggerPusherEvent("movie-{$movieId}", 'new_comment', $commentData);
}

echo json_encode(['success' => true, 'comment' => $commentData]);