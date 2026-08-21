<?php
session_start();
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../conn.php';

$input = json_decode(file_get_contents('php://input'), true);
$movieId = intval($input['movie_id'] ?? 0);
$parentId = intval($input['parent_id'] ?? 0);
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
    'comment_text' => $replyText,
    'created_at' => date('Y-m-d H:i:s'),
    'likes_count' => 0,
    'movie_title' => '' // Admin can refresh to get full details
];

// 3. Broadcast to movie-specific channel
triggerPusherEvent("movie-{$movieId}", 'new_reply', $replyData);

// 4. Broadcast to global admin channel
triggerPusherEvent('admin-comments', 'new_reply', $replyData);

// Get parent comment owner
$stmt = $conn->prepare("SELECT user_id FROM movie_comments WHERE comment_id = ?");
$stmt->execute([$parentId]);
$ownerId = $stmt->fetchColumn();

// If owner exists and is not the current user, send a notification
if ($ownerId && (int)$ownerId !== $userId) {
    triggerPusherEvent("user-{$ownerId}", 'comment_replied', [
        'sender_name' => $userName,
        'comment_id' => $parentId,
        'reply_text' => $replyText,
        'movie_id' => $movieId,
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

echo json_encode(['success' => true, 'reply' => $replyData]);