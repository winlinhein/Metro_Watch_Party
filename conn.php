<?php
$host     = "watch-party-2026-15g-watch-party-2026-15g.d.aivencloud.com";
$port     = "11935";
$dbname   = "watch_party"; // Change to "defaultdb" if that is your primary DB
$username = "avnadmin";
$password = "AVNS_z2RQMzEQu-6JpqDFWmP";
$charset  = "utf8mb4";

// Correct DSN string
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";

try {
    $conn = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    // Log details securely instead of displaying raw errors to users
    error_log($e->getMessage());
    die("Database Connection Failed.");
}
?>