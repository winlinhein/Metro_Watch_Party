<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$borderId = intval($input['border_id'] ?? 0);

if ($borderId < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid border ID']);
    exit;
}

$userId = $_SESSION['user_id'];

// If border is not "None", check ownership
if ($borderId != 0) {
    $stmt = $conn->prepare("SELECT 1 FROM user_inventory WHERE user_id = ? AND item_id = ?");
    $stmt->execute([$userId, $borderId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You do not own this border']);
        exit;
    }
}

// Upsert active border
$stmt = $conn->prepare("INSERT INTO user_customizations (user_id, active_border_id, updated_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE active_border_id = ?, updated_at = NOW()");
$stmt->execute([$userId, $borderId, $borderId]);

// Fetch border preview if set
$borderPreview = '';
if ($borderId != 0) {
    $stmt = $conn->prepare("SELECT image_url FROM shop_items WHERE item_id = ?");
    $stmt->execute([$borderId]);
    $borderPreview = $stmt->fetchColumn() ?: '';
}

// Broadcast
$payload = [
    'user_id'        => $userId,
    'avatar_url'     => null,
    'border_id'      => $borderId,
    'border_preview' => $borderPreview
];
triggerPusherEvent('profile-updates', 'profile_changed', $payload);

echo json_encode(['success' => true]);