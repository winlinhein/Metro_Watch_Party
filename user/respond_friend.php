<?php
// user_backend/respond_friend.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php'; 

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$senderId = filter_var($data['sender_id'] ?? null, FILTER_VALIDATE_INT);
$action = trim($data['action'] ?? ''); // 'accept' or 'decline'
$currentUserId = (int)$_SESSION['user_id'];

if (!$senderId || !in_array($action, ['accept', 'decline'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit();
}

try {
    if ($action === 'accept') {
        $stmt = $conn->prepare("
            UPDATE user_friends 
            SET status = 'accepted' 
            WHERE user_id_1 = :sender AND user_id_2 = :receiver AND status = 'pending'
        ");
        $stmt->execute([':sender' => $senderId, ':receiver' => $currentUserId]);

        // Send acceptance notification to sender
        $notifStmt = $conn->prepare("
            INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at) 
            VALUES (:user_id, :sender_id, 'friend_accepted', 'accepted your friend request.', 0, NOW())
        ");
        $notifStmt->execute([':user_id' => $senderId, ':sender_id' => $currentUserId]);

        $message = "Friend request accepted!";
    } else {
        $stmt = $conn->prepare("
            DELETE FROM user_friends 
            WHERE user_id_1 = :sender AND user_id_2 = :receiver AND status = 'pending'
        ");
        $stmt->execute([':sender' => $senderId, ':receiver' => $currentUserId]);

        $message = "Friend request declined.";
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}