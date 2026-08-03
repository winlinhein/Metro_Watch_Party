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
    // 1. Check if relationship already exists in user_friends
    $checkSql = "SELECT user_id_1, status FROM user_friends 
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

    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Friend request or connection already exists.']);
        exit();
    }

    // 2. Insert new friend request into user_friends table
    $insertSql = "INSERT INTO user_friends (user_id_1, user_id_2, status) 
                  VALUES (:sender, :receiver, 'pending')";
                  
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->execute([
        ':sender'   => $userId,
        ':receiver' => $friendId
    ]);

    echo json_encode(['success' => true, 'message' => 'Friend request sent successfully!']);

} catch (PDOException $e) {
    error_log("Add Friend Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}