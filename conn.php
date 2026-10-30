<?php
require_once __DIR__ . '/curl_ssl_helper.php';

$host     = nexusAppEnv('DB_HOST');
$port     = nexusAppEnv('DB_PORT', '3306');
$dbname   = nexusAppEnv('DB_NAME');
$username = nexusAppEnv('DB_USER');
$password = nexusAppEnv('DB_PASSWORD');
$sslCa    = nexusAppEnv('DB_SSL_CA');
$charset  = 'utf8mb4';

if ($host === '' || $dbname === '' || $username === '' || $password === '') {
    error_log('Database configuration is incomplete. Set DB_HOST, DB_NAME, DB_USER, and DB_PASSWORD.');
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database configuration is incomplete.']);
    exit();
}

$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_PERSISTENT         => false,
    PDO::ATTR_TIMEOUT            => 5,
];

if ($sslCa !== '') {
    if (!is_file($sslCa) || !is_readable($sslCa)) {
        error_log('Database CA certificate is missing or unreadable at DB_SSL_CA.');
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database TLS certificate is unavailable.']);
        exit();
    }
    if (!defined('PDO::MYSQL_ATTR_SSL_CA')) {
        error_log('PDO MySQL SSL support is unavailable.');
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database TLS is unavailable.']);
        exit();
    }
    $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
    if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
        $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }
}

try {
    $conn = new PDO($dsn, $username, $password, $pdoOptions);
    // Silence output here so JSON API endpoints remain clean
} catch (PDOException $e) {
    error_log("DB Connection Error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}
?>
