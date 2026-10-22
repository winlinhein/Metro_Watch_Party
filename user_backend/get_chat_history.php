<?php
session_start();
require_once __DIR__ . '/../error_response_helper.php';
header('Content-Type: application/json');
header('Cache-Control: private, no-cache, must-revalidate');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = (int)$_SESSION['user_id'];
$friendId = isset($_GET['friend_id']) ? (int)$_GET['friend_id'] : 0;
session_write_close();

if (!$friendId) {
    echo json_encode(['success' => false, 'message' => 'Invalid friend ID']);
    exit();
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../media_store_helper.php';

try {
    $stmt = $conn->prepare("
        SELECT message_id, sender_id, receiver_id, message_text, is_read,
               message_type, image_url,
               UNIX_TIMESTAMP(created_at) AS created_unix 
        FROM friends_message 
        WHERE (sender_id = :user_id_1 AND receiver_id = :friend_id_1)
           OR (sender_id = :friend_id_2 AND receiver_id = :user_id_2)
        ORDER BY created_at DESC
        LIMIT 100
    ");
    
    $stmt->execute([
        'user_id_1'   => $userId, 
        'friend_id_1' => $friendId,
        'friend_id_2' => $friendId, 
        'user_id_2'   => $userId
    ]);
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $messages = array_reverse($messages);

    foreach ($messages as &$msg) {
        $msg['message_type'] = $msg['message_type'] ?? 'text';
        $img = $msg['image_url'] ?? null;
        $msg['image_url'] = $img ? mediaServeUrlFromStored($img) : null;
    }
    unset($msg);

    $payload = json_encode([
        'success' => true,
        'friend_id' => $friendId,
        'viewer_id' => $userId,
        'messages' => $messages ?: []
    ]);
    $etag = '"' . md5($payload) . '"';
    header('ETag: ' . $etag);
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
        http_response_code(304);
        exit;
    }
    echo $payload;
} catch (PDOException $e) {
    nexusSafeError($e);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
exit();
