<?php
// /user_backend/mission_progress.php
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

/**
 * Get the current cycle key (e.g., '2025-03-15' for daily, '2025-11' for weekly, '2025-03' for monthly)
 */
function getCurrentCycleKey(string $cycleType): string {
    $today = new DateTime();
    switch ($cycleType) {
        case 'daily':
            return $today->format('Y-m-d');
        case 'weekly':
            return $today->format('o-W'); // ISO week
        case 'monthly':
            return $today->format('Y-m');
        default:
            return '';
    }
}

/**
 * Reset progress for missions whose cycle has changed.
 */
function resetMissionProgressIfNeeded(PDO $conn, int $userId, string $cycleType): void {
    $currentCycleKey = getCurrentCycleKey($cycleType);
    if ($currentCycleKey === '') return;

    $stmt = $conn->prepare("
        SELECT um.user_id, um.mission_id
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.mission_id
        WHERE um.user_id = ? 
          AND m.reset_cycle = ?
          AND (um.cycle_key IS NULL OR um.cycle_key != ?)
    ");
    $stmt->execute([$userId, $cycleType, $currentCycleKey]);
    $outdated = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($outdated) {
        $updateStmt = $conn->prepare("
            UPDATE user_missions
            SET progress = 0, done_status = 0, claimed_at = NULL, cycle_key = ?
            WHERE user_id = ? AND mission_id = ?
        ");
        foreach ($outdated as $row) {
            $updateStmt->execute([$currentCycleKey, $row['user_id'], $row['mission_id']]);
        }
    }
}

/**
 * Increment user progress for all active missions of a given type.
 * Also marks missions as completed when progress reaches target.
 */
function updateMissionProgress(int $userId, string $missionType, int $increment = 1): void {
    global $conn;

    // Only regular users accrue mission progress
    if (($_SESSION['user_role'] ?? '') !== 'user') {
        return;
    }

    // Find all active missions of this type
    $stmt = $conn->prepare("SELECT mission_id, target_count, reset_cycle FROM missions WHERE mission_type = ? AND is_active = 1");
    $stmt->execute([$missionType]);
    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($missions as $mission) {
        // Reset if cycle changed
        resetMissionProgressIfNeeded($conn, $userId, $mission['reset_cycle']);

        // Insert or update progress, including cycle_key
        $currentCycleKey = getCurrentCycleKey($mission['reset_cycle']);
        $stmt = $conn->prepare("
            INSERT INTO user_missions (user_id, mission_id, progress, done_status, claimed_at, cycle_key)
            VALUES (?, ?, ?, 0, NULL, ?)
            ON DUPLICATE KEY UPDATE progress = progress + ?, cycle_key = VALUES(cycle_key)
        ");
        $stmt->execute([$userId, $mission['mission_id'], $increment, $currentCycleKey, $increment]);

        // Mark completed if progress >= target
        $stmt = $conn->prepare("
            UPDATE user_missions 
            SET done_status = 1 
            WHERE user_id = ? AND mission_id = ? AND progress >= ? AND done_status = 0
        ");
        $stmt->execute([$userId, $mission['mission_id'], $mission['target_count']]);
    }
    triggerPusherEvent("user-{$userId}", 'missions_updated', ['user_id' => $userId]);
}