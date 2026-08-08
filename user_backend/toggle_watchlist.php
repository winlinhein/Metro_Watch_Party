<?php
// user_backend/toggle_watchlist.php
session_start();
header('Content-Type: application/json');

// 1. Return 401 Unauthorized if session is missing
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized session']);
    exit();
}

$currentUserId = (int)$_SESSION['user_id'];
session_write_close(); // Release session lock early[cite: 19]

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

// Safe payload decoding
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?? [];
$movieId = intval($data['movie_id'] ?? 0);

if (!$movieId) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid Movie ID']);
    exit();
}

try {
    // Check if record exists using named parameters
    $stmt = $conn->prepare("SELECT 1 FROM watchlists WHERE user_id = :uid AND movie_id = :mid");
    $stmt->execute(['uid' => $currentUserId, 'mid' => $movieId]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        $deleteStmt = $conn->prepare("DELETE FROM watchlists WHERE user_id = :uid AND movie_id = :mid");
        $deleteStmt->execute(['uid' => $currentUserId, 'mid' => $movieId]);
        $action = 'removed';
    } else {
        $insertStmt = $conn->prepare("INSERT INTO watchlists (user_id, movie_id) VALUES (:uid, :mid)");
        $insertStmt->execute(['uid' => $currentUserId, 'mid' => $movieId]);
        $action = 'added';
    }

    $pusher->trigger('user-' . $currentUserId, 'watchlist-updated', [
        'movie_id' => $movieId,
        'action' => $action
    ]);

    echo json_encode(['success' => true, 'action' => $action]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>