<?php
header('Content-Type: application/json');
header('Cache-Control: public, max-age=15, stale-while-revalidate=45');

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../home_feed_helper.php';

try {
    echo json_encode(getHomeFeed($conn));
} catch (Throwable $e) {
    error_log('home_feed_api: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'active_users' => ['total' => 0, 'online' => 0, 'preview' => [], 'extra' => 0],
        'trending' => [],
    ]);
}
