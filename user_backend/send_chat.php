<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../media_store_helper.php';

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$senderId = (int)$_SESSION['user_id'];
$receiverId = (int)($_POST['receiver_id'] ?? 0);
$messageText = trim($_POST['message'] ?? '');

if (!$receiverId) {
    echo json_encode(['success' => false, 'message' => 'Missing receiver ID']);
    exit();
}

$messageType = 'text';
$imageUrl = null;

// Handle image upload if file is present
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    $fileSize = (int)($file['size'] ?? 0);

    if ($fileSize > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image too large (max 5MB)']);
        exit();
    }

    $mime = detectUploadMime($file['tmp_name'], $file['type'] ?? '');
    if (!isAllowedImageMime($mime)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type']);
        exit();
    }

    try {
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $extension = $extMap[$mime] ?? strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png');
        $filename = uniqid('chat_', true) . '.' . $extension;
        $stored = storeMediaFromUpload($conn, $file, 'chat_images', $filename, $senderId);
        $imageUrl = $stored['serve_url'];
        $messageType = 'image';
    } catch (Throwable $e) {
        error_log('send_chat image upload: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to save image']);
        exit();
    }
}

// Reject if neither text nor image
if ($messageType === 'text' && empty($messageText)) {
    echo json_encode(['success' => false, 'message' => 'Empty message']);
    exit();
}

try {
    $stmt = $conn->prepare("INSERT INTO friends_message (sender_id, receiver_id, message_text, message_type, image_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$senderId, $receiverId, $messageText, $messageType, $imageUrl]);
    $messageId = $conn->lastInsertId();

    $time = date('h:i A');

    $minId = min($senderId, $receiverId);
    $maxId = max($senderId, $receiverId);
    $channelName = "chat-{$minId}-{$maxId}";

    $payload = [
        'message_id'   => $messageId,
        'sender_id'    => $senderId,
        'receiver_id'  => $receiverId,
        'message_text' => $messageText,
        'message_type' => $messageType,
        'image_url'    => $imageUrl,
        'time'         => $time
    ];

    echo json_encode(['success' => true, 'data' => $payload]);

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    triggerPusherEvent($channelName, "new_message", $payload);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
