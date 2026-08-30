<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['avatar'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large (max 2MB)']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'user_' . $userId . '_' . uniqid() . '.' . $ext;
$destination = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

$avatarUrl = '/uploads/avatars/' . $filename;

$stmt = $conn->prepare("UPDATE users SET avatar_url = ? WHERE user_id = ?");
$stmt->execute([$avatarUrl, $userId]);

// Get active border (for payload)
$stmt = $conn->prepare("SELECT active_border_id FROM user_customizations WHERE user_id = ?");
$stmt->execute([$userId]);
$activeBorderId = $stmt->fetchColumn() ?: 0;

$borderPreview = '';
if ($activeBorderId != 0) {
    $stmt = $conn->prepare("SELECT image_url FROM shop_items WHERE item_id = ?");
    $stmt->execute([$activeBorderId]);
    $borderPreview = $stmt->fetchColumn() ?: '';
}

$payload = [
    'user_id'        => $userId,
    'avatar_url'     => $avatarUrl,
    'border_id'      => (int)$activeBorderId,
    'border_preview' => $borderPreview
];
triggerPusherEvent('profile-updates', 'profile_changed', $payload);

echo json_encode(['success' => true, 'avatar_url' => $avatarUrl]);