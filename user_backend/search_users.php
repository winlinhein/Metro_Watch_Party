<?php
session_start();
header('Content-Type: application/json');

// Ensure user session exists
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized session.']);
    exit();
}

require_once __DIR__ . '/../conn.php'; 

$currentUserId = $_SESSION['user_id'];
$query = trim($_GET['q'] ?? '');

try {
    if ($query === '') {
        // Return default active users when search input is empty
        $stmt = $conn->prepare("
            SELECT user_id, user_name, email, is_premium 
            FROM users 
            WHERE user_id != :current_id 
            ORDER BY user_id DESC 
            LIMIT 10
        ");
        $stmt->execute(['current_id' => $currentUserId]);
    } else {
        // Use separate parameters (:search1 and :search2) for native PDO compatibility
        $searchTerm = '%' . $query . '%';
        $stmt = $conn->prepare("
            SELECT user_id, user_name, email, is_premium 
            FROM users 
            WHERE user_id != :current_id 
              AND (user_name LIKE :search1 OR email LIKE :search2) 
            LIMIT 20
        ");
        
        $stmt->execute([
            'current_id' => $currentUserId,
            'search1'    => $searchTerm,
            'search2'    => $searchTerm
        ]);
    }

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($users);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed']);
}
?>