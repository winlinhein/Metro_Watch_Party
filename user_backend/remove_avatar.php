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

    $stmt = $conn->prepare("SELECT avatar_url FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $oldUrl = (string)($stmt->fetchColumn() ?: '');

    if ($oldUrl !== '') {
        if (preg_match('/[?&]id=(\d+)/', $oldUrl, $m)) {
            ensureMediaTable($conn);
            $find = $conn->prepare("SELECT public_path FROM media_files WHERE id = ?");
            $find->execute([(int)$m[1]]);
            $oldPath = (string)($find->fetchColumn() ?: '');
            if ($oldPath !== '') {
                deleteMediaByPublicPath($conn, $oldPath);
            } else {
                $del = $conn->prepare("DELETE FROM media_files WHERE id = ?");
                $del->execute([(int)$m[1]]);
            }
        } else {
            $normalized = normalizeAvatarUrl($oldUrl);
            // normalizeAvatarUrl may rewrite to media.php?path=...
            if (preg_match('/[?&]path=([^&]+)/', $normalized, $m)) {
                deleteMediaByPublicPath($conn, rawurldecode($m[1]));
            } elseif (str_starts_with($oldUrl, '/uploads/avatars/')) {
                deleteMediaByPublicPath($conn, $oldUrl);
            }
        }
    }

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
