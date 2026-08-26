<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'is_premium' => false]);
    exit();
}

require_once __DIR__ . '/../conn.php';

$stmt = $conn->prepare("SELECT is_premium, premium_expires_at FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$isPremium = (bool)$row['is_premium'];
if ($isPremium && $row['premium_expires_at'] && strtotime($row['premium_expires_at']) < time()) {
    $conn->prepare("UPDATE users SET is_premium = 0 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
    $isPremium = false;
}

echo json_encode(['success' => true, 'is_premium' => $isPremium]);