<?php
session_start();
require_once __DIR__ . '/../conn.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Get user info
$stmt = $conn->prepare("SELECT user_name, email, avatar_url, points FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Get inventory (item ids)
$stmt = $conn->prepare("SELECT item_id FROM user_inventory WHERE user_id = ?");
$stmt->execute([$userId]);
$inventory = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get active border from user_customizations
$stmt = $conn->prepare("SELECT active_border_id FROM user_customizations WHERE user_id = ?");
$stmt->execute([$userId]);
$activeBorderId = $stmt->fetchColumn();

if ($activeBorderId === false) {
    $activeBorderId = 0; // default none
    $stmt = $conn->prepare("INSERT INTO user_customizations (user_id, active_border_id, updated_at) VALUES (?, 0, NOW())");
    $stmt->execute([$userId]);
}

echo json_encode([
    'success'          => true,
    'user_name'        => $user['user_name'],
    'email'            => $user['email'],
    'avatar_url'       => $user['avatar_url'] ?: '',
    'points'           => (int)$user['points'],
    'inventory'        => array_map('intval', $inventory),
    'active_border_id' => (int)$activeBorderId
]);