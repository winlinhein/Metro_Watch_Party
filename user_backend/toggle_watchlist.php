<?php
// user_backend/toggle_watchlist.php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized session']);
    exit();
}

$currentUserId = (int)$_SESSION['user_id'];
session_write_close();

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../premium_benefits_helper.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?? [];
$movieId = intval($data['movie_id'] ?? 0);

if (!$movieId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Movie ID']);
    exit();
}

try {
    $stmt = $conn->prepare("SELECT 1 FROM watchlists WHERE user_id = :uid AND movie_id = :mid");
    $stmt->execute(['uid' => $currentUserId, 'mid' => $movieId]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        $deleteStmt = $conn->prepare("DELETE FROM watchlists WHERE user_id = :uid AND movie_id = :mid");
        $deleteStmt->execute(['uid' => $currentUserId, 'mid' => $movieId]);
        $action = 'removed';
    } else {
        $cap = nexusWatchlistCap($conn, $currentUserId);
        $count = nexusWatchlistCount($conn, $currentUserId);
        if ($cap !== null && $count >= $cap) {
            echo json_encode([
                'success' => false,
                'message' => 'Free accounts can save up to ' . $cap . ' titles. Upgrade to Premium for an unlimited watchlist.',
                'needs_premium' => true,
                'count' => $count,
                'cap' => $cap,
                'occupancy' => $count . '/' . $cap,
            ]);
            exit();
        }
        $insertStmt = $conn->prepare("INSERT INTO watchlists (user_id, movie_id) VALUES (:uid, :mid)");
        $insertStmt->execute(['uid' => $currentUserId, 'mid' => $movieId]);
        $action = 'added';
    }

    $count = nexusWatchlistCount($conn, $currentUserId);
    $cap = nexusWatchlistCap($conn, $currentUserId);

    triggerPusherEvent('user-' . $currentUserId, 'watchlist-updated', [
        'movie_id' => $movieId,
        'action' => $action,
        'count' => $count,
        'cap' => $cap,
        'occupancy' => $cap === null ? ($count . '/Unlimited') : ($count . '/' . $cap),
    ]);

    echo json_encode([
        'success' => true,
        'action' => $action,
        'count' => $count,
        'cap' => $cap,
        'occupancy' => $cap === null ? ($count . '/Unlimited') : ($count . '/' . $cap),
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
