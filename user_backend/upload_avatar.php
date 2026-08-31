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

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        exit;
    }

    $file = $_FILES['avatar'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes, true)) {
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

    // Remove previous local avatar if present
    $oldStmt = $conn->prepare("SELECT avatar_url FROM users WHERE user_id = ?");
    $oldStmt->execute([$userId]);
    $oldUrl = normalizeAvatarUrl($oldStmt->fetchColumn() ?: '');
    if ($oldUrl !== '' && str_starts_with($oldUrl, '/uploads/avatars/')) {
        $oldPath = __DIR__ . '/..' . $oldUrl;
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png');
    $filename = 'user_' . $userId . '_' . uniqid() . '.' . $ext;
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
        exit;
    }

    $avatarUrl = '/uploads/avatars/' . $filename;

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
    echo json_encode(['success' => false, 'message' => 'Failed to upload avatar']);
}