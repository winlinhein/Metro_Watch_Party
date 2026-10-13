<?php
session_start();
require_once __DIR__ . '/../error_response_helper.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

require_once __DIR__ . '/../conn.php';

$userId = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';

if (empty($currentPassword) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}

// Same policy as registration (register_backend.php) so a user cannot weaken
// their password after signing up.
if (
    strlen($newPassword) < 8 ||
    !preg_match('/[A-Z]/', $newPassword) ||
    !preg_match('/[a-z]/', $newPassword) ||
    !preg_match('/[0-9]/', $newPassword) ||
    !preg_match('/[!@#$%^&*(),.?":{}|]/', $newPassword)
) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters and include upper case, lower case, a number and a symbol.']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT hashed_password FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($currentPassword, $user['hashed_password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit();
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET hashed_password = ?, updated_at = NOW() WHERE user_id = ?");
    $stmt->execute([$newHash, $userId]);

    echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
} catch (PDOException $e) {
    nexusSafeError($e);
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}