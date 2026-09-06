<?php
session_start();

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