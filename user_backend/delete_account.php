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
$password = trim($input['password'] ?? '');

if (empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required.']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT hashed_password FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['hashed_password'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
        exit();
    }

    // Optional: Delete related records (adjust table names as needed)
    $tables = [
        'user_friends' => 'user_id_1 = ? OR user_id_2 = ?',
        'movie_comments' => 'user_id = ?',
        'watchlist' => 'user_id = ?',
    ];
    foreach ($tables as $table => $condition) {
        if ($table === 'user_friends') {
            $stmt = $conn->prepare("DELETE FROM $table WHERE $condition");
            $stmt->execute([$userId, $userId]);
        } else {
            $stmt = $conn->prepare("DELETE FROM $table WHERE user_id = ?");
            $stmt->execute([$userId]);
        }
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Clear session
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();

    echo json_encode(['success' => true, 'message' => 'Account deleted successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}