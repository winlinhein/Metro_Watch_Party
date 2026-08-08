<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized session.']);
    exit();
}

require_once __DIR__ . '/../conn.php';

$userId = $_SESSION['user_id'];

// Updated table variables to match your schema naming
$missionsTable = 'missions'; 
$userMissionsTable = 'user_missions';

// 1. Fetch Total Available Points for the logged-in user
$totalPoints = 0;

try {
    // JOIN is required here because 'points_reward' is in the missions table, 
    // but we only want to sum them if 'done_status' = 1 in user_missions
    $stmtPts = $conn->prepare("
        SELECT SUM(m.points_reward) 
        FROM {$userMissionsTable} um
        JOIN {$missionsTable} m ON um.mission_id = m.mission_id
        WHERE um.user_id = :userId AND um.done_status = 1
    ");
    $stmtPts->execute([':userId' => $userId]);
    $totalPoints = (int) $stmtPts->fetchColumn() ?: 0;
} catch (PDOException $e) {
    // Fallback default value if table isn't ready yet
    $totalPoints = 1250; 
}

// 2. Fetch Quests from Database grouped by mission_type
$quests = [
    'daily'   => [],
    'weekly'  => [],
    'monthly' => []
];

try {
    // Replaced incorrect column names (id, quest_id, is_completed) with your actual schema
    // Both 'completed' and 'claimed' JSON keys now pull from 'done_status'
    $sql = "SELECT m.*, 
                   IF(um.done_status IS NOT NULL, um.done_status, 0) AS completed,
                   IF(um.done_status IS NOT NULL, um.done_status, 0) AS claimed
            FROM {$missionsTable} m
            LEFT JOIN {$userMissionsTable} um 
                   ON m.mission_id = um.mission_id AND um.user_id = :userId";
                   
    $stmt = $conn->prepare($sql);
    $stmt->execute([':userId' => $userId]);
    $allQuests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allQuests as $q) {
        // Changed 'frequency' to your actual column name 'mission_type'
        $type = strtolower($q['mission_type'] ?? 'daily'); 
        if (isset($quests[$type])) {
            $quests[$type][] = $q;
        }
    }
    
    echo json_encode([
        'success' => true,
        'totalPoints' => $totalPoints,
        'quests' => $quests
    ]);
    exit();

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    exit();
}