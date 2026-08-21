<?php
session_start();

// 1. Authorization Guard (Matching update_profile.php pattern)
if (empty($_SESSION['authenticated']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

header('Content-Type: application/json');
require_once __DIR__ . '/../conn.php';

// Decode JSON request payload from Alpine.js
$input = json_decode(file_get_contents('php://input'), true);

$userId   = $_SESSION['user_id'] ?? null;
$password = trim($input['password'] ?? '');

// 2. Validate Session User ID
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Session user ID is missing.']);
    exit();
}

// 3. Validate Required Input
if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required to confirm account deletion.']);
    exit();
}

try {
    // 4. Fetch Hashed Password using $conn and your schema's column names
    $stmt = $conn->prepare("SELECT hashed_password FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User record not found.']);
        exit();
    }

    // 5. Verify Password
    if (!password_verify($password, $user['hashed_password'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password. Account deletion aborted.']);
        exit();
    }

    // 6. Delete User Record
    $deleteStmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $deleteStmt->execute([$userId]);

    // 7. Clear Session & Cookies
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    // 8. Success Response
    echo json_encode(['success' => true, 'message' => 'Account deleted successfully!']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}