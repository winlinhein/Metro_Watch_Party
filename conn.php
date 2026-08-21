<?php
$host     = "watch-party-2026-15g-watch-party-2026-15g.d.aivencloud.com";
$port     = "11935";
$dbname   = "watch_party";
$username = "avnadmin";
$password = "AVNS_z2RQMzEQu-6JpqDFWmP";
$charset  = "utf8mb4";

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

try {
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
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