<?php
require_once __DIR__ . '/curl_ssl_helper.php';

// Shared school project database. These defaults let a classmate clone the repo
// and run it with no setup. Anyone with a .env file or real environment
// variables overrides them (nexusAppEnv prefers the environment).
//
// NOTE: these values are committed to the repository, so treat them as public.
// If this project ever holds real user data, move them to the environment only.
$host     = nexusAppEnv('DB_HOST', 'watch-party-2026-15g-watch-party-2026-15g.d.aivencloud.com');
$port     = nexusAppEnv('DB_PORT', '11935');
$dbname   = nexusAppEnv('DB_NAME', 'watch_party');
$username = nexusAppEnv('DB_USER', 'avnadmin');
$password = nexusAppEnv('DB_PASSWORD', 'AVNS_z2RQMzEQu-6JpqDFWmP');
$charset  = 'utf8mb4';

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