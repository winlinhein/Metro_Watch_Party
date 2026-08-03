<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized session']);
    exit();
}

require_once __DIR__ . '/../conn.php'; 

$currentUserId = (int)$_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            u.user_id, 
            u.user_name, 
            u.email, 
            u.is_premium,
            uf.status
        FROM user_friends uf
        JOIN users u ON u.user_id = IF(uf.user_id_1 = :id1, uf.user_id_2, uf.user_id_1)
        WHERE (uf.user_id_1 = :id2 OR uf.user_id_2 = :id3)
          AND uf.status = 'accepted'
    ");

    $stmt->execute([
        'id1' => $currentUserId,
        'id2' => $currentUserId,
        'id3' => $currentUserId
    ]);

    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($friends);

} catch (PDOException $e) {
    http_response_code(500);
    // Explicitly returning 'error' so JS data.error is not undefined
    echo json_encode(['error' => $e->getMessage()]);
}