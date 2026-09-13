<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../profile_media_helper.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
session_write_close();

$stmt = $conn->prepare("SELECT points FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$points = (int)$stmt->fetchColumn();

$inventory = nexusEffectiveInventory($conn, (int)$userId);

echo json_encode([
    'success'   => true,
    'points'    => $points,
    'inventory' => $inventory
]);