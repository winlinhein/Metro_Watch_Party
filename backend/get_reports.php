<?php
session_start();
require_once __DIR__ . '/../conn.php';

header('Content-Type: application/json');

// Allow both admin and moderator (match main dashboard)
$allowed_roles = ['admin', 'moderator'];
if (empty($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles, true)) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

try {
    $stmt = $conn->query("
        SELECT 
            r.report_id,
            r.type,
            IFNULL(r.status, 'pending') AS status,
            r.description,
            r.created_at,
            r.reported_room_id,
            r.comment_id,
            reporter.user_name AS reporter_name,
            reported.user_name AS reported_user_name,
            GROUP_CONCAT(re.reason_title SEPARATOR ', ') AS reported_reasons,
            MAX(mc.comment_text) AS comment_text,
            MAX(mc.movie_id) AS movie_id,
            MAX(m.title) AS movie_title
        FROM reports r
        LEFT JOIN users reporter ON r.reporter_id = reporter.user_id
        LEFT JOIN users reported ON r.reported_user_id = reported.user_id
        LEFT JOIN report_and_reasons rr ON r.report_id = rr.report_id
        LEFT JOIN reasons re ON rr.reason_id = re.reason_id
        LEFT JOIN movie_comments mc ON r.comment_id = mc.comment_id
        LEFT JOIN movies m ON mc.movie_id = m.movie_id
        GROUP BY r.report_id
        ORDER BY r.created_at DESC
    ");

    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted_reports = array_map(function($rep) {
        if (!empty($rep['reported_user_name'])) {
            $reported_entity = $rep['reported_user_name'];
        } elseif (!empty($rep['reported_room_id'])) {
            $reported_entity = 'Room #' . $rep['reported_room_id'];
        } else {
            $reported_entity = 'Unknown/None';
        }

        return [
            'raw_id'        => $rep['report_id'],
            'id'            => 'REP-' . str_pad($rep['report_id'], 4, '0', STR_PAD_LEFT),
            'date'          => date('M d, Y', strtotime($rep['created_at'] ?? 'now')),
            'user'          => $rep['reporter_name'] ?? 'Unknown User',
            'reported_user' => $reported_entity,
            'reason'        => $rep['reported_reasons'] ?? 'No Specific Reason',
            'type'          => $rep['type'],           // 'user', 'comment', 'reply'
            'excerpt'       => substr($rep['description'] ?? '', 0, 45) . (strlen($rep['description'] ?? '') > 45 ? '...' : ''),
            'description'   => $rep['description'] ?? '',
            'status'        => ucfirst($rep['status']),
            'priority'      => 'Medium',
            'comment_id'    => $rep['comment_id'] ?? null,
            'comment_text'  => $rep['comment_text'] ?? null,
            'movie_id'      => $rep['movie_id'] ?? null,
            'movie_title'   => $rep['movie_title'] ?? null
        ];
    }, $reports);

    echo json_encode(['success' => true, 'reports' => $formatted_reports]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}