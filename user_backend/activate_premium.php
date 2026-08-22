<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../conn.php';

try {
    $userId = (int)$_SESSION['user_id'];
    
    // In a real app, you'd process a Stripe or PayPal payment here.
    // For this mockup, we just activate premium directly.
    $stmt = $conn->prepare("UPDATE users SET is_premium = 1 WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);

    echo json_encode(['success' => true, 'message' => 'Premium activated successfully!']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
