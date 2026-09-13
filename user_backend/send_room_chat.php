<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../media_store_helper.php';
require_once __DIR__ . '/../admin_rooms_helper.php';
require_once __DIR__ . '/../room_chat_helper.php';
require_once __DIR__ . '/../profile_media_helper.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$senderId = (int)$_SESSION['user_id'];
$senderName = trim((string)($_SESSION['user_name'] ?? 'Guest'));
$roomId = (int)($_POST['room_id'] ?? 0);
$messageText = trim((string)($_POST['message'] ?? ''));
session_write_close();

if ($roomId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing room']);
    exit;
}

try {
    $roomStmt = $conn->prepare('SELECT room_id, status FROM rooms WHERE room_id = :id LIMIT 1');
    $roomStmt->execute(['id' => $roomId]);
    $room = $roomStmt->fetch(PDO::FETCH_ASSOC);
    if (!$room || isRoomClosed($room['status'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'This watch party has ended.', 'is_ended' => true]);
        exit;
    }

    $banStmt = $conn->prepare("
        SELECT chat_banned
        FROM room_participants
        WHERE room_id = :room_id AND user_id = :user_id
        ORDER BY last_seen DESC
        LIMIT 1
    ");
    $banStmt->execute(['room_id' => $roomId, 'user_id' => $senderId]);
    $banned = $banStmt->fetchColumn();
    if ($banned !== false && (int)$banned === 1) {
        echo json_encode(['success' => false, 'message' => 'The host banned you from room chat.']);
        exit;
    }
} catch (Throwable $e) {
    error_log('send_room_chat room check: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to send message']);
    exit;
}

$messageType = 'text';
$imageUrl = null;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    $fileSize = (int)($file['size'] ?? 0);
    if ($fileSize > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image too large (max 5MB)']);
        exit;
    }

    $mime = detectUploadMime($file['tmp_name'], $file['type'] ?? '');
    if (!isAllowedImageMime($mime)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type']);
        exit;
    }

    try {
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $extension = $extMap[$mime] ?? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png');
        $filename = uniqid('room_chat_', true) . '.' . $extension;
        $stored = storeMediaFromUpload($conn, $file, 'chat_images', $filename, $senderId);
        $imageUrl = $stored['serve_url'];
        $messageType = 'image';
    } catch (Throwable $e) {
        error_log('send_room_chat image upload: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to save image']);
        exit;
    }
}

if ($messageType === 'text' && $messageText === '') {
    echo json_encode(['success' => false, 'message' => 'Empty message']);
    exit;
}

$storedText = encodeRoomChatText($messageText, $imageUrl);
if (strlen($storedText) > 250) {
    $decoded = decodeRoomChatText($storedText);
    $caption = substr($decoded['text'], 0, 180);
    $storedText = encodeRoomChatText($caption, $decoded['image_url']);
}

try {
    $stmt = $conn->prepare("
        INSERT INTO room_messages (room_id, user_id, guest_nickname, message_text)
        VALUES (:room_id, :user_id, :guest_nickname, :message_text)
    ");
    $stmt->execute([
        'room_id' => $roomId,
        'user_id' => $senderId,
        'guest_nickname' => $senderName,
        'message_text' => $storedText,
    ]);
    $messageId = (int)$conn->lastInsertId();

    $media = ['avatar_url' => '', 'border_preview' => ''];
    try {
        $media = getUserProfileMedia($conn, $senderId);
    } catch (Throwable $ignore) {
    }

    $payload = [
        'id' => $messageId,
        'senderId' => $senderId,
        'name' => $senderName,
        'text' => $messageText,
        'type' => $messageType,
        'image_url' => $imageUrl,
        'time' => date('g:i A'),
        'avatar' => $media['avatar_url'] ?? '',
        'border' => $media['border_preview'] ?? '',
    ];

    echo json_encode(['success' => true, 'data' => $payload]);

    try {
        require_once __DIR__ . '/mission_progress.php';
        updateMissionProgress($senderId, 'send_chat_message', 1);
    } catch (Throwable $e) {
        error_log('send_room_chat mission update: ' . $e->getMessage());
    }
} catch (Throwable $e) {
    error_log('send_room_chat insert: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}
