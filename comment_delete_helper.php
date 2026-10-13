<?php

function nexusDeleteCommentThread(PDO $conn, int $commentId): void
{
    if ($commentId <= 0) {
        return;
    }

    $ids = [$commentId];
    $seen = [$commentId => true];
    $index = 0;
    $childStmt = $conn->prepare('SELECT comment_id FROM movie_comments WHERE parent_comment_id = ?');
    while ($index < count($ids)) {
        $childStmt->execute([$ids[$index]]);
        $index++;
        foreach ($childStmt->fetchAll(PDO::FETCH_COLUMN) as $childId) {
            $childId = (int)$childId;
            if ($childId > 0 && !isset($seen[$childId])) {
                $seen[$childId] = true;
                $ids[] = $childId;
            }
        }
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $conn->prepare("
        DELETE rr FROM report_and_reasons rr
        INNER JOIN reports r ON r.report_id = rr.report_id
        WHERE r.comment_id IN ($placeholders)
    ")->execute($ids);
    $conn->prepare("DELETE FROM reports WHERE comment_id IN ($placeholders)")->execute($ids);
    $conn->prepare("DELETE FROM comment_likes WHERE comment_id IN ($placeholders)")->execute($ids);
    $conn->prepare("UPDATE movie_comments SET parent_comment_id = NULL WHERE comment_id IN ($placeholders) AND parent_comment_id IS NOT NULL")->execute($ids);
    $conn->prepare("DELETE FROM movie_comments WHERE comment_id IN ($placeholders)")->execute($ids);
}
