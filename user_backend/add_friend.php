<?php
// user_backend/add_friend.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php'; 

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$friendId = filter_var($data['friend_id'] ?? null, FILTER_VALIDATE_INT);
$userId = (int)$_SESSION['user_id'];

if (!$friendId || $friendId === $userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid friend ID request.']);
    exit();
}

try {
    // 1. Check existing relationship in user_friends
    $checkSql = "SELECT id, user_id_1, user_id_2, status FROM user_friends 
                 WHERE (user_id_1 = :u1 AND user_id_2 = :u2) 
                    OR (user_id_1 = :u3 AND user_id_2 = :u4)
                 LIMIT 1";
                 
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->execute([
        ':u1' => $userId,
        ':u2' => $friendId,
        ':u3' => $friendId,
        ':u4' => $userId
    ]);

    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if ($existing['status'] === 'accepted') {
            echo json_encode(['success' => false, 'message' => 'You are already friends.']);
            exit();
        }

        // If the other user already sent a pending request -> Automatically accept ("Add Back")
        if ($existing['status'] === 'pending' && (int)$existing['user_id_1'] === $friendId) {
            $updateStmt = $conn->prepare("UPDATE user_friends SET status = 'accepted' WHERE id = :id");
            $updateStmt->execute([':id' => $existing['id']]);

            // Notify original sender
            $notifStmt = $conn->prepare("
                INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at) 
                VALUES (:user_id, :sender_id, 'friend_accepted', 'accepted your friend request.', 0, NOW())
            ");
            $notifStmt->execute([':user_id' => $friendId, ':sender_id' => $userId]);

            echo json_encode(['success' => true, 'message' => 'Friend request accepted!', 'action' => 'accepted']);
            exit();
        }

        echo json_encode(['success' => false, 'message' => 'Friend request is already pending.']);
        exit();
    }

    // 2. Insert new friend request
    $insertStmt = $conn->prepare("
        INSERT INTO user_friends (user_id_1, user_id_2, status) 
        VALUES (:sender, :receiver, 'pending')
    ");
    $insertStmt->execute([
        ':sender'   => $userId,
        ':receiver' => $friendId
    ]);

    // 3. Send notification to receiver
    $notifStmt = $conn->prepare("
        INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at) 
        VALUES (:receiver, :sender, 'friend_request', 'sent you a friend request.', 0, NOW())
    ");
    $notifStmt->execute([
        ':receiver' => $friendId,
        ':sender'   => $userId
    ]);

    echo json_encode(['success' => true, 'message' => 'Friend request sent successfully!', 'action' => 'sent']);

} catch (PDOException $e) {
    error_log("Add Friend Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}