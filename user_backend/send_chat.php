<?php
session_start();
header('Content-Type: application/json');

// Include DB connection and Pusher helper
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$senderId = (int)$_SESSION['user_id'];
$receiverId = (int)($data['receiver_id'] ?? 0);
$messageText = trim($data['message'] ?? '');

if (!$receiverId || empty($messageText)) {
    echo json_encode(['success' => false, 'message' => 'Missing message data']);
    exit();
}

// Insert using $conn
$stmt = $conn->prepare("INSERT INTO friends_message (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
$stmt->execute([$senderId, $receiverId, $messageText]);
$messageId = $conn->lastInsertId();

$time = date('h:i A');

$minId = min($senderId, $receiverId);
$maxId = max($senderId, $receiverId);
$channelName = "chat-{$minId}-{$maxId}";

$payload = [
    'message_id' => $messageId,
    'sender_id' => $senderId,
    'receiver_id' => $receiverId,
    'message_text' => $messageText,
    'time' => $time
];

// Trigger Pusher event using the $pusher object initialized in pusher_helper.php
if (isset($pusher)) {
    $pusher->trigger($channelName, 'new_message', $payload);
}

echo json_encode(['success' => true, 'data' => $payload]);