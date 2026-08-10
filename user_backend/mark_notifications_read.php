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
session_write_close();

try {
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = :userId AND is_read = 0
    ");
    $stmt->execute(['userId' => $currentUserId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}