<?php
session_start();
header('Content-Type: application/json');

// ---------- ROLE CHECK ----------
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Only regular users can claim missions.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$missionId = (int)($_POST['mission_id'] ?? 0);

require_once __DIR__ . '/../conn.php';

try {
    // Fetch mission and user progress
    $stmt = $conn->prepare("
        SELECT m.points_reward, um.done_status, um.claimed_at
        FROM missions m
        LEFT JOIN user_missions um ON m.mission_id = um.mission_id AND um.user_id = ?
        WHERE m.mission_id = ?
    ");
    $stmt->execute([$userId, $missionId]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mission) {
        echo json_encode(['success' => false, 'message' => 'Mission not found']);
        exit;
    }

    if ($mission['done_status'] != 1 || $mission['claimed_at'] !== null) {
        echo json_encode(['success' => false, 'message' => 'Mission not claimable']);
        exit;
    }

    // Begin transaction to atomically claim and add points
    $conn->beginTransaction();

    // Mark as claimed
    $stmt = $conn->prepare("UPDATE user_missions SET claimed_at = NOW() WHERE user_id = ? AND mission_id = ?");
    $stmt->execute([$userId, $missionId]);

    // Add points to user
    $stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE user_id = ?");
    $stmt->execute([$mission['points_reward'], $userId]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'points_added' => $mission['points_reward']
    ]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    error_log('Claim error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Claim failed']);
}