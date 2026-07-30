<?php
session_start();
header('Content-Type: application/json');

// Authorization check
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

require_once __DIR__ . '/../conn.php';

$userId   = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? '';

// Role Guard: Prevent primary admin deletion if required
/* if ($userRole === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Action restricted for primary administrator accounts.']);
    exit();
} */

$rawInput = file_get_contents('php://input');
$data     = json_decode($rawInput, true);
$password = $data['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required to confirm deletion.']);
    exit();
}

try {
    // 1. Verify password
    $stmt = $conn->prepare("SELECT hashed_password FROM users WHERE user_id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['hashed_password'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password. Deletion aborted.']);
        exit();
    }

    // 2. Delete user row
    $deleteStmt = $conn->prepare("DELETE FROM users WHERE user_id = :id");
    $deleteStmt->execute([':id' => $userId]);

    // 3. Clear session and destroy
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    echo json_encode(['success' => true, 'redirect' => 'login.php']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}