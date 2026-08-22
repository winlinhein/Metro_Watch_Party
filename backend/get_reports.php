<?php
// /admin_backend/get_reports.php
session_start();
require_once __DIR__ . '/../conn.php';

header('Content-Type: application/json');

// Ensure user is an admin
if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

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
            ANY_VALUE(reporter.user_name) AS reporter_name,
            ANY_VALUE(reported.user_name) AS reported_user_name,
            GROUP_CONCAT(re.reason_title SEPARATOR ', ') AS reported_reasons
        FROM 
            reports r
        LEFT JOIN 
            users reporter ON r.reporter_id = reporter.user_id
        LEFT JOIN 
            movie_comments mc ON (r.comment_id = mc.comment_id OR (r.comment_id IS NULL AND r.reported_user_id = mc.comment_id)) AND r.type IN ('comment', 'reply')
        LEFT JOIN 
            users reported ON (r.reported_user_id = reported.user_id AND r.type = 'user') OR (mc.user_id = reported.user_id AND r.type IN ('comment', 'reply'))
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

    // Format the data exactly as your Alpine.js frontend expects it
    $formatted_reports = array_map(function($rep) {
        
        // Determine if they reported a user or a room
        $reported_entity = 'Unknown/None';
        if (!empty($rep['reported_user_name'])) {
            $reported_entity = $rep['reported_user_name'];
        } elseif (!empty($rep['reported_room_id'])) {
            $reported_entity = 'Room #' . $rep['reported_room_id'];
        }

        return [
            'raw_id'        => $rep['report_id'], 
            'id'            => 'REP-' . str_pad($rep['report_id'], 4, '0', STR_PAD_LEFT), 
            'date'          => date('M d, Y', strtotime($rep['created_at'] ?? 'now')),
            'user'          => $rep['reporter_name'] ?? 'Unknown User',
            'reported_user' => $reported_entity, 
            'reported_movie_id' => $rep['reported_movie_id'] ?? null,
            'reported_comment_id' => in_array($rep['type'], ['comment', 'reply']) ? ($rep['comment_id'] ?? $rep['reported_user_id']) : null,
            'reason'        => $rep['reported_reasons'] ?? 'No Specific Reason', // Using your GROUP_CONCAT alias
            'type'          => ucfirst($rep['type']),
            'excerpt'       => substr($rep['description'], 0, 45) . (strlen($rep['description']) > 45 ? '...' : ''),
            'description'   => $rep['description'],
            'status'        => ucfirst($rep['status']),
            'priority'      => 'Medium' // Reverted back to hardcoded since there is no column
        ];
    }, $reports);

    echo json_encode(['success' => true, 'reports' => $formatted_reports]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>