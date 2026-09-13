<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../account_lifecycle_helper.php';

$userId = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$password = trim($input['password'] ?? '');

if ($password === '') {
    echo json_encode(['success' => false, 'message' => 'Password is required.']);
    exit();
}

try {
    ensureAppSchema($conn);
    $stmt = $conn->prepare("SELECT hashed_password FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['hashed_password'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
        exit();
    }

    nexusScheduleAccountDeletion($conn, $userId);
    nexusClearAccountSession();

    echo json_encode([
        'success' => true,
        'pending' => true,
        'message' => 'Account deletion scheduled. You have 24 hours to cancel from the login page.'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
