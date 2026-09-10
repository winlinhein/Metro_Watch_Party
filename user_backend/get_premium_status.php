<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'is_premium' => false, 'premium_expires_at' => null]);
    exit();
}

$userId = (int)$_SESSION['user_id'];
session_write_close();

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../premium_status_helper.php';

$premium = resolveUserPremium($conn, $userId);

echo json_encode([
    'success' => true,
    'is_premium' => $premium['is_premium'],
    'premium_expires_at' => $premium['premium_expires_at'],
]);
