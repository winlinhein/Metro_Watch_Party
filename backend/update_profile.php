<?php
session_start();
header('Content-Type: application/json');

// 1. Session Authorization Check
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

require_once __DIR__ . '/../conn.php';

$userId = (int)$_SESSION['user_id'];
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request payload.']);
    exit();
}

$action = $data['action'] ?? 'update_info';

try {
    if ($action === 'update_info') {
        $name  = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');

        if (empty($name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
            exit();
        }

        // Check if email is already used by another user
        $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :id");
        $checkStmt->execute([':email' => $email, ':id' => $userId]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email address is already in use.']);
            exit();
        }

        // Update user_name, email, and updated_at timestamp
        $stmt = $conn->prepare("
            UPDATE users 
            SET user_name = :name, 
                email = :email,
                updated_at = NOW()
            WHERE user_id = :id
        ");
        $stmt->execute([
            ':name'  => $name,
            ':email' => $email,
            ':id'    => $userId
        ]);

        // Sync local PHP session
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user'    => ['name' => $name, 'email' => $email]
        ]);

    } elseif ($action === 'change_password') {
        $currentPassword = $data['current_password'] ?? '';
        $newPassword     = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            echo json_encode(['success' => false, 'message' => 'All password fields are required.']);
            exit();
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'New password and confirmation do not match.']);
            exit();
        }

        if (strlen($newPassword) < 8) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
            exit();
        }

        // Check current password using 'hashed_password' column
        $stmt = $conn->prepare("SELECT hashed_password FROM users WHERE user_id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($currentPassword, $user['hashed_password'])) {
            echo json_encode(['success' => false, 'message' => 'Incorrect current password.']);
            exit();
        }

        // Store new password hash and update timestamp
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateStmt = $conn->prepare("
            UPDATE users 
            SET hashed_password = :hash,
                updated_at = NOW()
            WHERE user_id = :id
        ");
        $updateStmt->execute([':hash' => $newHash, ':id' => $userId]);

        echo json_encode(['success' => true, 'message' => 'Security password updated successfully.']);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action type.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}