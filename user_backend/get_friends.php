<?php
// user_backend/get_friends.php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized session']);
    exit();
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../profile_media_helper.php';
require_once __DIR__ . '/../presence_helper.php';

$currentUserId = (int)$_SESSION['user_id'];
session_write_close();

try {
    ensureUserLastSeenColumn($conn);
    $friendsStmt = $conn->prepare("
        SELECT 
            u.user_id, 
            u.user_name, 
            u.email, 
            u.is_premium,
            uf.status,
            MAX(u.last_seen) AS last_seen,
            COUNT(m.message_id) AS unread_count
        FROM user_friends uf
        JOIN users u 
          ON u.user_id = IF(uf.user_id_1 = :id1, uf.user_id_2, uf.user_id_1)
        LEFT JOIN friends_message m 
          ON m.sender_id = u.user_id 
         AND m.receiver_id = :id4 
         AND m.is_read = 0
        WHERE (uf.user_id_1 = :id2 OR uf.user_id_2 = :id3)
          AND uf.status = 'accepted'
        GROUP BY 
            u.user_id, 
            u.user_name, 
            u.email, 
            u.is_premium, 
            uf.status
    ");
    $friendsStmt->execute([
        'id1' => $currentUserId,
        'id2' => $currentUserId,
        'id3' => $currentUserId,
        'id4' => $currentUserId
    ]);
    $friends = attachProfileMedia($conn, $friendsStmt->fetchAll(PDO::FETCH_ASSOC));
    foreach ($friends as &$friend) {
        $friend['is_online'] = onlineFlagFromLastSeen($friend['last_seen'] ?? null);
    }
    unset($friend);

    $pendingStmt = $conn->prepare("
        SELECT 
            u.user_id, 
            u.user_name, 
            u.email, 
            u.is_premium,
            u.last_seen
        FROM user_friends uf
        JOIN users u ON u.user_id = uf.user_id_1
        WHERE uf.user_id_2 = :current_id AND uf.status = 'pending'
    ");
    $pendingStmt->execute(['current_id' => $currentUserId]);
    $pendingRequests = attachProfileMedia($conn, $pendingStmt->fetchAll(PDO::FETCH_ASSOC));
    foreach ($pendingRequests as &$req) {
        $req['is_online'] = onlineFlagFromLastSeen($req['last_seen'] ?? null);
    }
    unset($req);

    echo json_encode([
        'friends' => $friends,
        'pending_requests' => $pendingRequests
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
