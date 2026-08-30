<?php
session_start();
require_once __DIR__ . '/../conn.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Remove avatar by setting avatar_url to NULL
$stmt = $conn->prepare("UPDATE users SET avatar_url = NULL WHERE user_id = ?");
$stmt->execute([$userId]);

echo json_encode(['success' => true]);