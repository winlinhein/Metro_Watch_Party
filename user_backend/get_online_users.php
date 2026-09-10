<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'online_ids' => []]);
    exit;
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../presence_helper.php';

session_write_close();

try {
    ensureUserLastSeenColumn($conn);
    $stmt = $conn->query("
        SELECT user_id
        FROM users
        WHERE last_seen IS NOT NULL
          AND last_seen > DATE_SUB(NOW(), INTERVAL 90 SECOND)
    ");
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    echo json_encode(['success' => true, 'online_ids' => $ids]);
} catch (Throwable $e) {
    echo json_encode(['success' => true, 'online_ids' => []]);
}
