<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

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
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($_FILES['image']['tmp_name']);
    $fileSize = $_FILES['image']['size'];

    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image type']);
        exit();
    }

    if ($fileSize > 5 * 1024 * 1024) { // 5MB max
        echo json_encode(['success' => false, 'message' => 'Image too large (max 5MB)']);
        exit();
    }

    $uploadDir = __DIR__ . '/../uploads/chat_images/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('chat_', true) . '.' . $extension;
    $destination = $uploadDir . $filename;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save image']);
        exit();
    }

    $imageUrl = '/uploads/chat_images/' . $filename;
    $messageType = 'image';
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

    // Trigger Pusher event using helper
    triggerPusherEvent($channelName, "new_message", $payload);

    echo json_encode(['success' => true, 'data' => $payload]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}