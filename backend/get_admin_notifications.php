<?php
session_start();
require_once __DIR__ . '/../conn.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$admin_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, type, message, is_read, created_at 
    FROM notifications
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 30
");
$stmt->execute([$admin_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'notifications' => $notifications]);
?>