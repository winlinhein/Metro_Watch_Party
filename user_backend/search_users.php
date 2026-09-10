<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized session.']);
    exit();
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../profile_media_helper.php';
require_once __DIR__ . '/../presence_helper.php';

$currentUserId = (int)$_SESSION['user_id'];
$query = trim($_GET['q'] ?? '');
session_write_close();

try {
    ensureUserLastSeenColumn($conn);
    if ($query === '') {
        $stmt = $conn->prepare("
            SELECT 
                u.user_id, 
                u.user_name, 
                u.email, 
                u.is_premium,
                u.last_seen,
                f.status AS friend_status,
                f.user_id_1 AS requester_id
            FROM users u
            LEFT JOIN user_friends f 
                   ON (f.user_id_1 = ? AND f.user_id_2 = u.user_id)
                   OR (f.user_id_2 = ? AND f.user_id_1 = u.user_id)
            WHERE u.user_id != ?
              AND u.role_id = 2
              AND LOWER(u.status) = 'active'
            ORDER BY u.user_id DESC 
            LIMIT 10
        ");
        $stmt->execute([$currentUserId, $currentUserId, $currentUserId]);
    } else {
        $searchTerm = '%' . $query . '%';
        $stmt = $conn->prepare("
            SELECT 
                u.user_id, 
                u.user_name, 
                u.email, 
                u.is_premium,
                u.last_seen,
                f.status AS friend_status,
                f.user_id_1 AS requester_id
            FROM users u
            LEFT JOIN user_friends f 
                   ON (f.user_id_1 = ? AND f.user_id_2 = u.user_id)
                   OR (f.user_id_2 = ? AND f.user_id_1 = u.user_id)
            WHERE u.user_id != ? 
              AND u.role_id = 2
              AND LOWER(u.status) = 'active'
              AND (u.user_name LIKE ? OR u.email LIKE ?) 
            LIMIT 20
        ");
        $stmt->execute([
            $currentUserId, 
            $currentUserId, 
            $currentUserId, 
            $searchTerm, 
            $searchTerm
        ]);
    }

    $users = attachProfileMedia($conn, $stmt->fetchAll(PDO::FETCH_ASSOC));
    foreach ($users as &$user) {
        $user['is_online'] = onlineFlagFromLastSeen($user['last_seen'] ?? null);
    }
    unset($user);
    echo json_encode($users);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}