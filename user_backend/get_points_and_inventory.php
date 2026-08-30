<?php
session_start();
require_once __DIR__ . '/../conn.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT points FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$points = (int)$stmt->fetchColumn();

$stmt = $conn->prepare("SELECT item_id FROM user_inventory WHERE user_id = ?");
$stmt->execute([$userId]);
$inventory = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'success'   => true,
    'points'    => $points,
    'inventory' => $inventory
]);