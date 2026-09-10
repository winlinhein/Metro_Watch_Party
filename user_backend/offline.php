<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../presence_helper.php';

$userId = (int)$_SESSION['user_id'];
session_write_close();

try {
    clearUserPresence($conn, $userId);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false]);
}
