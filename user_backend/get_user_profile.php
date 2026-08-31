<?php
session_start();
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../profile_media_helper.php';

    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }

    $userId = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT user_name, email, avatar_url, points FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $stmt = $conn->prepare("SELECT item_id FROM user_inventory WHERE user_id = ?");
    $stmt->execute([$userId]);
    $inventory = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    $activeBorderId = getActiveBorderId($conn, $userId);

    echo json_encode([
        'success'          => true,
        'user_name'        => $user['user_name'],
        'email'            => $user['email'],
        'avatar_url'       => normalizeAvatarUrl($user['avatar_url'] ?? ''),
        'points'           => (int)$user['points'],
        'inventory'        => $inventory,
        'active_border_id' => $activeBorderId,
        'border_preview'   => borderPreviewForId($conn, $activeBorderId),
    ]);
} catch (Throwable $e) {
    error_log('get_user_profile error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to load profile']);
}
