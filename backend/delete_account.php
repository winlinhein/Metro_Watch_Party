<?php
session_start();

if (empty($_SESSION['authenticated']) || !in_array((string)($_SESSION['user_role'] ?? ''), ['admin', 'moderator', 'user'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

header('Content-Type: application/json');
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../account_lifecycle_helper.php';

$input = json_decode(file_get_contents('php://input'), true);

$userId   = (int)($_SESSION['user_id'] ?? 0);
$password = trim($input['password'] ?? '');

if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Session user ID is missing.']);
    exit();
}

if ($password === '') {
    echo json_encode(['success' => false, 'message' => 'Password is required to confirm account deletion.']);
    exit();
}

try {
    ensureAppSchema($conn);
    $stmt = $conn->prepare("SELECT hashed_password FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User record not found.']);
        exit();
    }

    if (!password_verify($password, $user['hashed_password'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password. Account deletion aborted.']);
        exit();
    }

    nexusScheduleAccountDeletion($conn, $userId);
    nexusClearAccountSession();

    echo json_encode([
        'success' => true,
        'pending' => true,
        'message' => 'Account deletion scheduled. Sign in within 24 hours to cancel.'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
