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

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['avatar'];
    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large (max 2MB)']);
        exit;
    }

    $mime = detectUploadMime($file['tmp_name'], $file['type'] ?? '');
    if (!isAllowedImageMime($mime)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        exit;
    }

    // Remove previous avatar (local + shared DB)
    $oldStmt = $conn->prepare("SELECT avatar_url FROM users WHERE user_id = ?");
    $oldStmt->execute([$userId]);
    $oldUrl = (string)($oldStmt->fetchColumn() ?: '');
    $oldNormalized = normalizeAvatarUrl($oldUrl);
    if ($oldNormalized !== '' && str_starts_with($oldNormalized, '/uploads/avatars/')) {
        deleteMediaByPublicPath($conn, $oldNormalized);
    } elseif (preg_match('/[?&]id=(\d+)/', $oldUrl, $m)) {
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
    }

    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $ext = $extMap[$mime] ?? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png');
    $filename = 'user_' . $userId . '_' . uniqid() . '.' . $ext;

    $stored = storeMediaFromUpload($conn, $file, 'avatars', $filename, $userId);
    $avatarUrl = $stored['serve_url'];

    $stmt = $conn->prepare("UPDATE users SET avatar_url = ? WHERE user_id = ?");
    $stmt->execute([$avatarUrl, $userId]);

    $activeBorderId = getActiveBorderId($conn, $userId);
    $borderPreview = borderPreviewForId($conn, $activeBorderId);

    triggerPusherEvent('profile-updates', 'profile_changed', [
        'user_id'        => $userId,
        'avatar_url'     => $avatarUrl,
        'border_id'      => $activeBorderId,
        'border_preview' => $borderPreview,
    ]);

    echo json_encode(['success' => true, 'avatar_url' => $avatarUrl]);
} catch (Throwable $e) {
    error_log('upload_avatar error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage() ?: 'Failed to upload avatar']);
}
