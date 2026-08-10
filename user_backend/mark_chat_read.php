<?php
session_start();
require_once __DIR__ . '/../conn.php';

header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

$user_id = $_SESSION['user_id'] ?? null;
$friend_id = $data['friend_id'] ?? null;

if ($user_id && $friend_id) {
    // Change unread from 1 to 0 for messages sent BY the friend TO the user
    $stmt = $conn->prepare("UPDATE friends_message SET unread = 1 WHERE sender_id = ? AND receiver_id = ? AND unread = 0");
    $stmt->execute([$friend_id, $user_id]);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}