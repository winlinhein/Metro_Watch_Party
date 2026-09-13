<?php
// /admin_backend/get_reports.php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../profile_media_helper.php';
require_once __DIR__ . '/../poster_helper.php';
require_once __DIR__ . '/../account_lifecycle_helper.php';
ensureAppSchema($conn);
nexusPrepareKeptRecords($conn);

header('Content-Type: application/json');

// Ensure user is an admin or moderator
$role = strtolower((string)($_SESSION['user_role'] ?? ''));
if (empty($_SESSION['user_role']) || !in_array($role, ['admin', 'moderator'], true)) {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

session_write_close();

try {
    // Incorporating your corrected query with the many-to-many relationship
    $stmt = $conn->query("
        SELECT 
            r.report_id,
            r.type,
            IFNULL(r.status, 'pending') AS status,
            r.description,
            r.created_at,
            r.reported_room_id,
            r.reported_user_id,
            r.comment_id,
            ANY_VALUE(mc.movie_id) AS reported_movie_id,
            ANY_VALUE(reporter.user_id) AS reporter_user_id,
            ANY_VALUE(COALESCE(reporter.user_name, r.deleted_reporter_name, 'Deleted user')) AS reporter_name,
            ANY_VALUE(reported.user_id) AS reported_target_user_id,
            ANY_VALUE(COALESCE(reported.user_name, r.deleted_reported_name, 'Deleted user')) AS reported_user_name,
            ANY_VALUE(rm.room_code) AS reported_room_code,
            ANY_VALUE(rm.status) AS reported_room_status,
            ANY_VALUE(rm.movie_id) AS reported_room_movie_id,
            ANY_VALUE(mv.title) AS reported_room_movie_title,
            GROUP_CONCAT(re.reason_title SEPARATOR ', ') AS reported_reasons
        FROM 
            reports r
        LEFT JOIN 
            users reporter ON r.reporter_id = reporter.user_id
        LEFT JOIN 
            movie_comments mc ON (r.comment_id = mc.comment_id OR (r.comment_id IS NULL AND r.reported_user_id = mc.comment_id)) AND r.type IN ('comment', 'reply')
        LEFT JOIN 
            users reported ON (r.reported_user_id = reported.user_id AND r.type IN ('user', 'room', 'appeal')) OR (mc.user_id = reported.user_id AND r.type IN ('comment', 'reply'))
        LEFT JOIN
            rooms rm ON r.reported_room_id = rm.room_id
        LEFT JOIN
            movies mv ON rm.movie_id = mv.movie_id AND rm.movie_id > 0
        LEFT JOIN 
            report_and_reasons rr ON r.report_id = rr.report_id
        LEFT JOIN 
            reasons re ON rr.reason_id = re.reason_id
        GROUP BY 
            r.report_id
        ORDER BY 
            r.created_at DESC
    ");
    
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $mediaRows = [];
    foreach ($reports as $rep) {
        if (!empty($rep['reporter_user_id'])) {
            $mediaRows[] = ['user_id' => (int)$rep['reporter_user_id']];
        }
        if (!empty($rep['reported_target_user_id'])) {
            $mediaRows[] = ['user_id' => (int)$rep['reported_target_user_id']];
        }
    }
    $mediaByUser = [];
    foreach (attachProfileMedia($conn, $mediaRows) as $m) {
        $mediaByUser[(int)$m['user_id']] = $m;
    }

    // Format the data exactly as your Alpine.js frontend expects it
    $formatted_reports = array_map(function($rep) use ($mediaByUser) {
        
        $reported_entity = 'Unknown/None';
        $roomCode = $rep['reported_room_code'] ?? '';
        $roomMovieTitle = trim((string)($rep['reported_room_movie_title'] ?? ''));
        $roomMovieId = (int)($rep['reported_room_movie_id'] ?? 0);
        if (($rep['type'] ?? '') === 'appeal') {
            $reported_entity = 'Ban appeal';
            if (!empty($rep['reported_user_name'])) {
                $reported_entity .= ' · ' . $rep['reported_user_name'];
            }
        } elseif (($rep['type'] ?? '') === 'room') {
            if ($roomCode) {
                $reported_entity = 'Room #' . $roomCode;
            } elseif (!empty($rep['reported_room_id'])) {
                $reported_entity = 'Room #' . $rep['reported_room_id'];
            }
            if ($roomMovieTitle !== '') {
                $reported_entity .= ' · ' . $roomMovieTitle;
            }
        } elseif (!empty($rep['reported_user_name'])) {
            $reported_entity = $rep['reported_user_name'];
        } elseif (!empty($rep['reported_room_id'])) {
            $reported_entity = 'Room #' . ($roomCode ?: $rep['reported_room_id']);
        }

        $reporterId = (int)($rep['reporter_user_id'] ?? 0);
        $reportedId = (int)($rep['reported_target_user_id'] ?? 0);
        $reporterMedia = $mediaByUser[$reporterId] ?? [];
        $reportedMedia = $mediaByUser[$reportedId] ?? [];
        $desc = (string)($rep['description'] ?? '');

        return [
            'raw_id'        => $rep['report_id'], 
            'id'            => 'REP-' . str_pad($rep['report_id'], 4, '0', STR_PAD_LEFT), 
            'date'          => date('M d, Y', strtotime($rep['created_at'] ?? 'now')),
            'user'          => $rep['reporter_name'] ?? 'Unknown User',
            'reported_user' => $reported_entity, 
            'reported_movie_id' => $rep['reported_movie_id'] ?? null,
            'reported_comment_id' => in_array($rep['type'], ['comment', 'reply']) ? ($rep['comment_id'] ?? $rep['reported_user_id']) : null,
            'reported_room_id' => $rep['reported_room_id'] ? (int)$rep['reported_room_id'] : null,
            'reported_room_code' => $roomCode ?: null,
            'reported_room_status' => $rep['reported_room_status'] ?? null,
            'reported_room_movie_title' => $roomMovieTitle !== '' ? $roomMovieTitle : null,
            'reported_room_movie_poster' => ($roomMovieId > 0 && $roomMovieTitle !== '') ? moviePosterUrl($roomMovieId) : '',
            'reason'        => $rep['reported_reasons'] ?: (($rep['type'] ?? '') === 'appeal' ? 'Ban Appeal' : 'No Specific Reason'),
            'type'          => ucfirst($rep['type']),
            'reporter_id'   => $reporterId ?: null,
            'excerpt'       => $desc !== '' ? (substr($desc, 0, 45) . (strlen($desc) > 45 ? '...' : '')) : '',
            'description'   => $desc,
            'status'        => ucfirst($rep['status']),
            'priority'      => 'Medium',
            'reporter_avatar_url' => $reporterMedia['avatar_url'] ?? '',
            'reporter_border_preview' => $reporterMedia['border_preview'] ?? '',
            'reported_user_id' => $reportedId ?: null,
            'reported_user_name' => $rep['reported_user_name'] ?? '',
            'reported_avatar_url' => $reportedMedia['avatar_url'] ?? '',
            'reported_border_preview' => $reportedMedia['border_preview'] ?? '',
        ];
    }, $reports);

    echo json_encode(['success' => true, 'reports' => $formatted_reports]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>