<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$commentId = (int)($input['comment_id'] ?? $_POST['comment_id'] ?? 0);
session_write_close();

if ($commentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Comment ID required']);
    exit;
}

require_once __DIR__ . '/../conn.php';

try {
    $ownerStmt = $conn->prepare("SELECT user_id FROM movie_comments WHERE comment_id = ? LIMIT 1");
    $ownerStmt->execute([$commentId]);
    $ownerId = (int)$ownerStmt->fetchColumn();
    if ($ownerId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit;
    }
    if ($ownerId !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only delete your own comments']);
        exit;
    }

    $conn->beginTransaction();
    $idsStmt = $conn->prepare("SELECT comment_id FROM movie_comments WHERE comment_id = ? OR parent_comment_id = ?");
    $idsStmt->execute([$commentId, $commentId]);
    $ids = array_values(array_unique(array_map('intval', $idsStmt->fetchAll(PDO::FETCH_COLUMN))));
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $conn->prepare("DELETE FROM comment_likes WHERE comment_id IN ($placeholders)")->execute($ids);
        try {
            $conn->prepare("DELETE FROM reports WHERE comment_id IN ($placeholders)")->execute($ids);
        } catch (Throwable $ignore) {
        }
    }
    $conn->prepare("DELETE FROM movie_comments WHERE parent_comment_id = ?")->execute([$commentId]);
    $conn->prepare("DELETE FROM movie_comments WHERE comment_id = ?")->execute([$commentId]);
    $conn->commit();

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Delete failed']);
}
