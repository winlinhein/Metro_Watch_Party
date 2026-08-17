<?php
session_start();
require_once __DIR__ . '/../conn.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$admin_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$stmt->execute([$admin_id]);

echo json_encode(['success' => true]);
?>