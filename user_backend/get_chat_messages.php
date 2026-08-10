<?php
session_start();
require_once __DIR__ . '/../conn.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$friend_id = $_GET['friend_id'] ?? null;

if (!$user_id || !$friend_id) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

// Assuming your table is named 'messages' based on the screenshots
$stmt = $conn->prepare("
    SELECT * FROM friends_message 
    WHERE (sender_id = ? AND receiver_id = ?) 
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");
$stmt->execute([$user_id, $friend_id, $friend_id, $user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'messages' => $messages]);