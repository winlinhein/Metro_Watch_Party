<?php
session_start();
header('Content-Type: application/json');

// ---------- ROLE CHECK ----------
// Only regular users can view missions. Admins, mods, and guests get an empty set.
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'user') {
    echo json_encode([
        'success' => true,
        'totalPoints' => 0,
        'quests' => [
            'daily'   => [],
            'weekly'  => [],
            'monthly' => []
        ]
    ]);
    exit;
}

$userId = (int)$_SESSION['user_id'];

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/mission_progress.php'; // helper functions

// ---------- RESET MISSIONS FOR CURRENT CYCLE ----------
resetMissionProgressIfNeeded($conn, $userId, 'daily');
resetMissionProgressIfNeeded($conn, $userId, 'weekly');
resetMissionProgressIfNeeded($conn, $userId, 'monthly');

// ---------- FETCH TOTAL CLAIMABLE POINTS ----------
$totalPoints = 0;
try {
    $stmt = $conn->prepare("
        SELECT SUM(m.points_reward)
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.mission_id
        WHERE um.user_id = ? 
          AND um.done_status = 1 
          AND um.claimed_at IS NULL
    ");
    $stmt->execute([$userId]);
    $totalPoints = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Error fetching total points: ' . $e->getMessage());
    $totalPoints = 0;
}

// ---------- FETCH MISSIONS GROUPED BY CYCLE ----------
$quests = ['daily' => [], 'weekly' => [], 'monthly' => []];

try {
    $stmt = $conn->prepare("
        SELECT 
            m.mission_id,
            m.title,
            m.mission_type,
            m.points_reward,
            m.target_count,
            m.reset_cycle,
            COALESCE(um.progress, 0) AS progress,
            COALESCE(um.done_status, 0) AS completed,
            CASE WHEN um.claimed_at IS NULL THEN 0 ELSE 1 END AS claimed
        FROM missions m
        LEFT JOIN user_missions um 
               ON m.mission_id = um.mission_id AND um.user_id = ?
        WHERE m.is_active = 1
        ORDER BY m.mission_id ASC
    ");
    $stmt->execute([$userId]);
    $allMissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allMissions as $m) {
        $cycle = strtolower($m['reset_cycle'] ?? 'daily');
        if (!array_key_exists($cycle, $quests)) {
            $cycle = 'daily';
        }

        $quests[$cycle][] = [
            'id'            => (int)$m['mission_id'],
            'title'         => $m['title'],
            'desc'          => "Progress: {$m['progress']}/{$m['target_count']}",
            'points'        => (int)$m['points_reward'],
            'completed'     => (int)$m['completed'],
            'claimed'       => (int)$m['claimed'],
            'progress'      => (int)$m['progress'],
            'target'        => (int)$m['target_count'],
            'mission_type'  => $m['mission_type']
        ];
    }

    echo json_encode([
        'success'     => true,
        'totalPoints' => $totalPoints,
        'quests'      => $quests
    ]);
} catch (PDOException $e) {
    error_log('Database error in mission.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please try again.'
    ]);
}