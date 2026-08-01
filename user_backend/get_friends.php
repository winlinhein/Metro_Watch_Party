<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized session.']);
    exit();
}

require_once __DIR__ . '/../conn.php';

$userId = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("
        SELECT 
            u.user_id, 
            u.user_name, 
            u.email, 
            u.is_premium, 
            uf.status
        FROM users u
        JOIN user_friends uf 
          ON (uf.user_id_2 = u.user_id AND uf.user_id_1 = :userId)
          OR (uf.user_id_1 = u.user_id AND uf.user_id_2 = :userId)
        WHERE uf.status = 'accepted' 
          AND u.user_id != :userId
    ");
    $stmt->execute(['userId' => $userId]);
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'friends' => $friends]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred.']);
}