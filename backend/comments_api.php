<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../profile_media_helper.php';

// Admin / moderator authentication
$role = strtolower((string)($_SESSION['user_role'] ?? ''));
if (empty($_SESSION['authenticated']) || !in_array($role, ['admin', 'moderator'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// -------------------------------------------------------------
// GET – Fetch comments (optionally filtered by movie_id)
// -------------------------------------------------------------
if ($method === 'GET') {
    $movieId = intval($_GET['movie_id'] ?? 0);

    $sql = "
        SELECT 
            c.comment_id AS id,
            c.movie_id,
            c.user_id,
            u.user_name,
            m.title AS movie_title,
            c.comment_text,
            c.created_at,
            c.parent_comment_id AS parent_id,
            (
                SELECT COUNT(*) 
                FROM comment_likes l 
                WHERE l.comment_id = c.comment_id
            ) AS likes_count
        FROM movie_comments c
        INNER JOIN users u ON c.user_id = u.user_id
        INNER JOIN movies m ON c.movie_id = m.movie_id
    ";

    if ($movieId) {
        $sql .= " WHERE c.movie_id = :movie_id";
    }

    $sql .= " ORDER BY c.created_at DESC";

    $stmt = $conn->prepare($sql);
    if ($movieId) {
        $stmt->bindParam(':movie_id', $movieId, PDO::PARAM_INT);
    }
    $stmt->execute();
    $comments = attachProfileMedia($conn, $stmt->fetchAll(PDO::FETCH_ASSOC));

    echo json_encode(['success' => true, 'comments' => $comments]);
    exit;
}

// -------------------------------------------------------------
// POST – Delete a comment and its replies
// -------------------------------------------------------------
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $commentId = intval($input['comment_id'] ?? 0);

    if (!$commentId) {
        echo json_encode(['success' => false, 'error' => 'Comment ID required']);
        exit;
    }

    try {
        if ($action === 'delete') {
            $conn->beginTransaction();

            // 1. Delete likes for the comment and its replies
            $likeStmt = $conn->prepare("
                DELETE FROM comment_likes 
                WHERE comment_id IN (
                    SELECT comment_id FROM movie_comments 
                    WHERE comment_id = ? OR parent_comment_id = ?
                )
            ");
            $likeStmt->execute([$commentId, $commentId]);

            // 2. Delete comments and replies
            $deleteStmt = $conn->prepare("DELETE FROM movie_comments WHERE comment_id = ? OR parent_comment_id = ?");
            $deleteStmt->execute([$commentId, $commentId]);

            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Unsupported action']);
        }
    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Action failed: ' . $e->getMessage()]);
    }
    exit;
}

// If method is neither GET nor POST
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);