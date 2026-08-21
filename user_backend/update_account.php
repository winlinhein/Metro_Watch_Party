<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

require_once __DIR__ . '/../conn.php';

$userId = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');

if (empty($username) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Username and email are required.']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit();
}

try {
    // Check uniqueness of username/email (excluding current user)
    $stmt = $conn->prepare("
        SELECT user_id FROM users 
        WHERE (user_name = :username OR email = :email) 
        AND user_id != :user_id
    ");
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'user_id' => $userId
    ]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already in use.']);
        exit();
    }

    // Update ONLY username and email
    $stmt = $conn->prepare("
        UPDATE users 
        SET user_name = :username, email = :email, updated_at = NOW() 
        WHERE user_id = :user_id
    ");
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'user_id' => $userId
    ]);

    // Update session
    $_SESSION['user_name'] = $username;
    $_SESSION['user_email'] = $email;

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'That email address is already in use.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}