<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Someone';
$data = json_decode(file_get_contents('php://input'), true);

$senderId = (int)($data['sender_id'] ?? 0); // The user who originally sent the request
$action   = $data['action'] ?? ''; // 'accept' or 'decline'
session_write_close();

if (!$userId || !$senderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

try {
    if ($action === 'accept') {
        $stmt = $conn->prepare("
            UPDATE user_friends 
            SET status = 'accepted' 
            WHERE user_id_1 = :sender AND user_id_2 = :receiver AND status = 'pending'
        ");
        $stmt->execute([':sender' => $senderId, ':receiver' => $userId]);

        // Send acceptance notification to sender
        $notifStmt = $conn->prepare("
            INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at) 
            VALUES (:user_id, :sender_id, 'friend_accepted', 'accepted your friend request.', 0, NOW())
        ");
        $notifStmt->execute([':user_id' => $senderId, ':sender_id' => $userId]);

        $message = "Friend request accepted!";

        // Mark the original friend_request notification as read for the receiver
        $cleanNotifStmt = $conn->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = :receiver AND sender_id = :sender AND type = 'friend_request'
        ");
        $cleanNotifStmt->execute([
            ':receiver' => $userId,
            ':sender'   => $senderId
        ]);
    } else {
        $stmt = $conn->prepare("
            DELETE FROM user_friends 
            WHERE user_id_1 = :sender AND user_id_2 = :receiver AND status = 'pending'
        ");
        $stmt->execute([':sender' => $senderId, ':receiver' => $userId]);

        $message = "Friend request declined.";
    }

    $eventType = ($action === 'accept') ? 'friend_accepted' : 'friend_rejected';
    $message   = ($action === 'accept') ? 'accepted your friend request.' : 'declined your friend request.';

    $payload = [
        'type'        => $eventType,
        'sender_id'   => $userId,
        'sender_name' => $userName,
        'message'     => $message,
        'created_at'  => date('Y-m-d H:i:s')
    ];

    // Notify the original sender
    triggerPusherEvent("user-{$senderId}", "friend_event", $payload);
    echo json_encode(['success' => true, 'message' => "Request {$action}ed."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}