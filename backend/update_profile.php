<?php
session_start();
if (empty($_SESSION['authenticated']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../conn.php';

// Decode JSON request payload from Alpine.js
$input = json_decode(file_get_contents('php://input'), true);

$userId          = $_SESSION['user_id'] ?? null;
$userName        = trim($input['user_name'] ?? '');
$email           = trim($input['email'] ?? '');
$currentPassword = $input['current_password'] ?? '';
$newPassword     = $input['new_password'] ?? '';

// Validate user session presence
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Session user ID is missing.']);
    exit();
}

// Validate required fields
if (empty($userName) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'User name and email are required.']);
    exit();
}

try {
    // 1. Fetch current hashed password from database using $conn from conn.php
    $stmt = $conn->prepare("SELECT hashed_password FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User record not found.']);
        exit();
    }

    // 2. Handle update logic
    if (!empty($newPassword)) {
        // Verify current password before updating to new one
        if (empty($currentPassword) || !password_verify($currentPassword, $user['hashed_password'])) {
            echo json_encode(['success' => false, 'message' => 'Incorrect current password.']);
            exit();
        }

        $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $updateStmt = $conn->prepare("
            UPDATE users 
            SET user_name = ?, email = ?, hashed_password = ?, updated_at = NOW() 
            WHERE user_id = ?
        ");
        $updateStmt->execute([$userName, $email, $newHashedPassword, $userId]);
    } else {
        // Update user_name and email only
        $updateStmt = $conn->prepare("
            UPDATE users 
            SET user_name = ?, email = ?, updated_at = NOW() 
            WHERE user_id = ?
        ");
        $updateStmt->execute([$userName, $email, $userId]);
    }

    // 3. Keep current session values updated in sync
    $_SESSION['user_name'] = $userName;
    $_SESSION['email']     = $email;

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);

} catch (PDOException $e) {
    // Handle unique constraint failure (e.g., duplicate email)
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'That email address is already in use.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}