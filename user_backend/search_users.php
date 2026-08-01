<?php
session_start();

// Set JSON header immediately
header('Content-Type: application/json');

// Ensure authentication check passes
if (empty($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Adjust relative path according to where your db connection file lives
// e.g., require_once __DIR__ . '/db_connect.php'; 
// or require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../conn.php'; 

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT id, username, email, avatar 
        FROM users 
        WHERE username LIKE :query OR email LIKE :query 
        LIMIT 10
    ");
    
    $stmt->execute(['query' => '%' . $q . '%']);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}