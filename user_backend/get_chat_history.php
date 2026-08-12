<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = (int)$_SESSION['user_id'];
$friendId = isset($_GET['friend_id']) ? (int)$_GET['friend_id'] : 0;

if (!$friendId) {
    echo json_encode(['success' => false, 'message' => 'Invalid friend ID']);
    exit();
}

require_once __DIR__ . '/../conn.php';

try {
    $stmt = $conn->prepare("
        SELECT message_id, sender_id, receiver_id, message_text, is_read,
               DATE_FORMAT(created_at, '%h:%i %p') AS time 
        FROM friends_message 
        WHERE (sender_id = :user_id_1 AND receiver_id = :friend_id_1)
           OR (sender_id = :friend_id_2 AND receiver_id = :user_id_2)
        ORDER BY created_at ASC
    ");
    
    // Pass each parameter uniquely, even if the values are the same
    $stmt->execute([
        'user_id_1'   => $userId, 
        'friend_id_1' => $friendId,
        'friend_id_2' => $friendId, 
        'user_id_2'   => $userId
    ]);
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'messages' => $messages ?: []]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
exit();