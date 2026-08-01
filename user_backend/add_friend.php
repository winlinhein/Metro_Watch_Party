<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized session.']);
    exit();
}

require_once __DIR__ . '/../conn.php';

$data = json_decode(file_get_contents('php://input'), true);
$friendId = intval($data['friend_id'] ?? 0);
$userId = $_SESSION['user_id'];

if (!$friendId || $friendId === $userId) {
    echo json_encode(['success' => false, 'message' => 'Invalid friend target.']);
    exit();
}

try {
    // Validate target user exists and is not an administrator
    $checkAdmin = $conn->prepare("
        SELECT r.role 
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        WHERE u.user_id = :friendId
    ");
    $checkAdmin->execute(['friendId' => $friendId]);
    $targetUser = $checkAdmin->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        echo json_encode(['success' => false, 'message' => 'Target user does not exist.']);
        exit();
    }

    if (strtolower($targetUser['role']) === 'admin') {
        echo json_encode(['success' => false, 'message' => 'Adding administrators to friends list is prohibited.']);
        exit();
    }

    // Record request in user_friends table
    $stmt = $conn->prepare("
        INSERT INTO user_friends (user_id_1, user_id_2, status) 
        VALUES (:userId, :friendId, 'pending')
        ON DUPLICATE KEY UPDATE status = 'pending'
    ");

    if ($stmt->execute(['userId' => $userId, 'friendId' => $friendId])) {
        echo json_encode(['success' => true, 'message' => 'Friend request successfully sent!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to send friend request.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database operation failed.']);
}