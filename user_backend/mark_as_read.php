<?php
session_start();
header('Content-Type: application/json');

// Use global connection
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$currentUserId = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$senderId = (int)($data['sender_id'] ?? 0);

if (!$senderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid sender ID']);
    exit();
}

$stmt = $conn->prepare("
    UPDATE friends_message 
    SET is_read = 1 
    WHERE receiver_id = :receiver_id 
      AND sender_id = :sender_id 
      AND is_read = 0
");
$stmt->execute([
    'receiver_id' => $currentUserId,
    'sender_id'   => $senderId
]);

// Notify the sender that their messages were read
$reader_id = $currentUserId;
$sender_id = $senderId; 
$minId = min($reader_id, $sender_id);
$maxId = max($reader_id, $sender_id);
$channelName = "chat-{$minId}-{$maxId}";

triggerPusherEvent($channelName, 'messages_read', [
    'reader_id' => $reader_id
]);
echo json_encode(['success' => true]);