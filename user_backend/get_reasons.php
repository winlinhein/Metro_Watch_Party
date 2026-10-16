<?php
// /user_backend/get_reasons.php
require_once __DIR__ . '/../conn.php'; 
header('Content-Type: application/json');
header('Cache-Control: private, max-age=60, stale-while-revalidate=300');

try {
    $stmt = $conn->query("SELECT reason_id, reason_title, reason_description FROM reasons");
    $reasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'reasons' => $reasons]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>