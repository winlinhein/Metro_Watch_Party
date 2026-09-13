<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../notifications_helper.php';
require_once __DIR__ . '/../profile_media_helper.php';
require_once __DIR__ . '/mission_progress.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'Someone';
$data = json_decode(file_get_contents('php://input'), true);

$senderId = (int)($data['sender_id'] ?? 0); // The user who originally sent the request
$action   = $data['action'] ?? ''; // 'accept' or 'decline'
session_write_close();

$actorMedia = getUserProfileMedia($conn, (int)$userId);

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
  
        try {
            updateMissionProgress($userId, 'add_friend', 1);
            updateMissionProgress($senderId, 'add_friend', 1);
        } catch (Throwable $e) {
            error_log('add_friend mission update: ' . $e->getMessage());
        }

        // Send acceptance notification to sender
        $notifStmt = $conn->prepare("
            INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at) 
            VALUES (:user_id, :sender_id, 'friend_accepted', 'accepted your friend request.', 0, NOW())
        ");
        $notifStmt->execute([':user_id' => $senderId, ':sender_id' => $userId]);
        $acceptedNotifId = (int)$conn->lastInsertId();

        $message = "Friend request accepted!";
    } else {
        $stmt = $conn->prepare("
            DELETE FROM user_friends 
            WHERE user_id_1 = :sender AND user_id_2 = :receiver AND status = 'pending'
        ");
        $stmt->execute([':sender' => $senderId, ':receiver' => $userId]);

        $message = "Friend request declined.";
    }

    $deletedIds = deleteMatchingNotifications($conn, (int)$userId, [
        'types' => ['friend_request'],
        'sender_id' => $senderId,
    ]);

    $eventType = ($action === 'accept') ? 'friend_accepted' : 'friend_rejected';
    $message   = ($action === 'accept') ? 'accepted your friend request.' : 'declined your friend request.';

    $payload = array_merge([
        'id'          => $acceptedNotifId ?? 0,
        'type'        => $eventType,
        'sender_id'   => $userId,
        'sender_name' => $userName,
        'message'     => $message,
        'created_at'  => date('Y-m-d H:i:s'),
        'is_read'     => 0,
    ], $actorMedia);

    // Notify the original sender
    triggerPusherEvent("user-{$senderId}", "friend_event", $payload);
    echo json_encode([
        'success' => true,
        'message' => "Request {$action}ed.",
        'deleted_notification_ids' => $deletedIds,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}