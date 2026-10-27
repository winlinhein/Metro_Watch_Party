<?php
require_once __DIR__ . '/curl_ssl_helper.php';

$host     = nexusAppEnv('DB_HOST');
$port     = nexusAppEnv('DB_PORT', '3306');
$dbname   = nexusAppEnv('DB_NAME');
$username = nexusAppEnv('DB_USER');
$password = nexusAppEnv('DB_PASSWORD');
$charset  = 'utf8mb4';

if ($host === '' || $dbname === '' || $username === '' || $password === '') {
    error_log('Database configuration is incomplete. Set DB_HOST, DB_NAME, DB_USER, and DB_PASSWORD.');
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database configuration is incomplete.']);
    exit();
}

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

try {
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
        PDO::ATTR_TIMEOUT            => 5,
    ]);
    // Silence output here so JSON API endpoints remain clean
} catch (PDOException $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}
?>
