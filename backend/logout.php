<?php
session_start();

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId > 0) {
    try {
        require_once __DIR__ . '/../conn.php';
        require_once __DIR__ . '/../presence_helper.php';
        clearUserPresence($conn, $userId);
    } catch (Throwable $e) {
        // Still sign out even if presence update fails.
    }
}

// Clear session data
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

$loginUrl = '/frontend/login.php?success=' . urlencode('You have been signed out.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Signed out successfully.',
        'redirect' => $loginUrl
    ]);
    exit();
}

header('Location: ' . $loginUrl);
exit();
?>