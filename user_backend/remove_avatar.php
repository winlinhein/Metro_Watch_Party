<?php
session_start();
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../pusher_helper.php';
    require_once __DIR__ . '/../profile_media_helper.php';

    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }

    $userId = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT avatar_url FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $oldUrl = (string)($stmt->fetchColumn() ?: '');

    $stmt = $conn->prepare("UPDATE users SET avatar_url = NULL WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Delete local file if it lives under uploads/avatars
    $normalized = normalizeAvatarUrl($oldUrl);
    if ($normalized !== '' && str_starts_with($normalized, '/uploads/avatars/')) {
        $path = __DIR__ . '/..' . $normalized;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    $borderId = getActiveBorderId($conn, $userId);
    $borderPreview = borderPreviewForId($conn, $borderId);

    triggerPusherEvent('profile-updates', 'profile_changed', [
        'user_id'        => $userId,
        'avatar_url'     => '',
        'border_id'      => $borderId,
        'border_preview' => $borderPreview,
    ]);

    echo json_encode(['success' => true, 'avatar_url' => '']);
} catch (Throwable $e) {
    error_log('remove_avatar error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to remove avatar']);
}
