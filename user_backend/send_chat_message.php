<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

$sender_id = $_SESSION['user_id'] ?? null;
$receiver_id = $data['receiver_id'] ?? null;
$message_text = trim($data['message'] ?? '');

if ($sender_id && $receiver_id && $message_text !== '') {
    // Insert into DB with unread = 1
    $stmt = $conn->prepare("INSERT INTO friends_message (sender_id, receiver_id, message_text, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
    $stmt->execute([$sender_id, $receiver_id, $message_text]);
    
    $message_id = $conn->lastInsertId();
    $time = date('h:i A');

    // Prepare payload for Pusher
    $payload = [
        'id' => $message_id,
        'sender_id' => $sender_id,
        'receiver_id' => $receiver_id,
        'text' => $message_text,
        'time' => $time
    ];

    // Trigger Pusher event to the receiver's private channel
    triggerPusherEvent("user-{$receiver_id}", 'new_chat_message', $payload);

    echo json_encode(['success' => true, 'message' => $payload]);
} else {
    echo json_encode(['success' => false]);
}