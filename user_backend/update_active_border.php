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

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $borderId = (int)($input['border_id'] ?? 0);
    if ($borderId < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid border ID']);
        exit;
    }

    $userId = (int)$_SESSION['user_id'];

    if ($borderId !== 0 && !userOwnsItem($conn, $userId, $borderId)) {
        echo json_encode(['success' => false, 'message' => 'You do not own this border']);
        exit;
    }

    // Preserve existing theme id if present
    $stmt = $conn->prepare("SELECT active_theme_id FROM user_customizations WHERE user_id = ?");
    $stmt->execute([$userId]);
    $themeId = (int)($stmt->fetchColumn() ?: 0);

    upsertUserCustomization($conn, $userId, $borderId, $themeId);

    $avatarStmt = $conn->prepare("SELECT avatar_url FROM users WHERE user_id = ?");
    $avatarStmt->execute([$userId]);
    $avatarUrl = normalizeAvatarUrl($avatarStmt->fetchColumn() ?: '');

    $borderPreview = borderPreviewForId($conn, $borderId);

    triggerPusherEvent('profile-updates', 'profile_changed', [
        'user_id'        => $userId,
        'avatar_url'     => $avatarUrl,
        'border_id'      => $borderId,
        'border_preview' => $borderPreview,
    ]);

    echo json_encode([
        'success'        => true,
        'border_id'      => $borderId,
        'border_preview' => $borderPreview,
    ]);
} catch (Throwable $e) {
    error_log('update_active_border error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save border']);
}
