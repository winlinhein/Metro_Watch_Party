<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../point_pack_helper.php';
ensureAppSchema($conn);

echo json_encode([
    'success' => true,
    'packages' => nexusListPointPackages($conn, true),
]);
