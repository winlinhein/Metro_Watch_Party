<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../presence_helper.php';
require_once __DIR__ . '/../auth_flow_helper.php';

$userId = (int)$_SESSION['user_id'];

try {
    $blocked = nexusGuardAuthenticatedSession($conn);
    if ($blocked) {
        echo json_encode([
            'success' => false,
            'banned' => true,
            'redirect' => $blocked,
        ]);
        exit;
    }
    if (strtolower((string)($_SESSION['user_role'] ?? '')) === 'user') {
        require_once __DIR__ . '/mission_progress.php';
        nexusAwardDailyLogin($userId);
    }
    session_write_close();
    require_once __DIR__ . '/../admin_rooms_helper.php';
    sweepAbandonedRooms($conn);
    touchUserPresence($conn, $userId);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false]);
}
