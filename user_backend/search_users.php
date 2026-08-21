<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized session.']);
    exit();
}

require_once __DIR__ . '/../conn.php'; 

$currentUserId = (int)$_SESSION['user_id'];
$query = trim($_GET['q'] ?? '');
session_write_close();

try {
    if ($query === '') {
        $stmt = $conn->prepare("
            SELECT 
                u.user_id, 
                u.user_name, 
                u.email, 
                u.is_premium, 
                f.status AS friend_status,
                f.user_id_1 AS requester_id
            FROM users u
            LEFT JOIN user_friends f 
                   ON (f.user_id_1 = ? AND f.user_id_2 = u.user_id)
                   OR (f.user_id_2 = ? AND f.user_id_1 = u.user_id)
            WHERE u.user_id != ?
              AND u.role_id NOT IN (1, 3)
              AND u.status NOT IN ('pending', 'banned')
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
                f.status AS friend_status,
                f.user_id_1 AS requester_id
            FROM users u
            LEFT JOIN user_friends f 
                   ON (f.user_id_1 = ? AND f.user_id_2 = u.user_id)
                   OR (f.user_id_2 = ? AND f.user_id_1 = u.user_id)
            WHERE u.user_id != ? 
              AND (u.user_name LIKE ? OR u.email LIKE ?) 
              AND u.role_id NOT IN (1, 3)
              AND u.status NOT IN ('pending', 'banned')
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

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
}