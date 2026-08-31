<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../profile_media_helper.php';

$movieId = intval($_GET['movie_id'] ?? 0);
session_write_close();

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
        u.user_name,
        COUNT(l.comment_id) AS likes_count,
        MAX(CASE WHEN l.user_id = ? THEN 1 ELSE 0 END) AS is_liked
    FROM movie_comments c
    INNER JOIN users u ON c.user_id = u.user_id
    LEFT JOIN comment_likes l ON c.comment_id = l.comment_id
    WHERE c.movie_id = ?
    GROUP BY c.comment_id
    ORDER BY c.created_at ASC
");
$stmt->execute([$_SESSION['user_id'] ?? 0, $movieId]);
$flatComments = attachProfileMedia($conn, $stmt->fetchAll(PDO::FETCH_ASSOC));

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