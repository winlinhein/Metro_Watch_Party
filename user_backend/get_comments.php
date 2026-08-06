<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';

$movieId = intval($_GET['movie_id'] ?? 0);

if (!$movieId) {
    echo json_encode(['success' => false, 'message' => 'Invalid Movie ID']);
    exit();
}

$stmt = $conn->prepare("
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
    WHERE c.movie_id = ?
    ORDER BY c.created_at ASC
");
$stmt->execute([$movieId]);
$flatComments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to build a nested tree from flat DB rows
function buildCommentTree(array &$elements, $parentId = null) {
    $branch = [];
    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $children = buildCommentTree($elements, $element['id']);
            $element['replies'] = $children;
            $branch[] = $element;
        }
    }
    return $branch;
}

$commentTree = buildCommentTree($flatComments);

echo json_encode([
    'success' => true,
    'comments' => array_reverse($commentTree)
]);