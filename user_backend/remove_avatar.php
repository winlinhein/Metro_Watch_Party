<?php
session_start();
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../pusher_helper.php';
    require_once __DIR__ . '/../profile_media_helper.php';
    require_once __DIR__ . '/../media_store_helper.php';

    if (empty($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }

    $userId = (int)$_SESSION['user_id'];
    session_write_close();

    $stmt = $conn->prepare("SELECT avatar_url FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $oldUrl = (string)($stmt->fetchColumn() ?: '');

    deleteStoredMedia($conn, $oldUrl);
    deletePreviousUserAvatars($conn, $userId, null);

    $stmt = $conn->prepare("UPDATE users SET avatar_url = NULL WHERE user_id = ?");
    $stmt->execute([$userId]);

    $activeBorderId = getActiveBorderId($conn, $userId);
    $borderPreview = borderPreviewForId($conn, $activeBorderId);

    triggerPusherEvent('profile-updates', 'profile_changed', [
        'user_id'        => $userId,
        'avatar_url'     => '',
        'border_id'      => $activeBorderId,
        'border_preview' => $borderPreview,
    ]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    error_log('remove_avatar error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to remove avatar']);
}
