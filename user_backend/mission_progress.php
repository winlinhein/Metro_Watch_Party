<?php
// /user_backend/mission_progress.php
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../presence_helper.php';

/**
 * Get the current cycle key (e.g., '2025-03-15' for daily, '2025-11' for weekly, '2025-03' for monthly)
 */
function getCurrentCycleKey(string $cycleType): string {
    $today = nexusViewerNow();
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
 * Reset daily/weekly/monthly mission cycles in one round-trip.
 */
function resetAllMissionCyclesIfNeeded(PDO $conn, int $userId): void {
    $daily = getCurrentCycleKey('daily');
    $weekly = getCurrentCycleKey('weekly');
    $monthly = getCurrentCycleKey('monthly');

    $stmt = $conn->prepare("
        SELECT um.mission_id, m.reset_cycle
        FROM user_missions um
        JOIN missions m ON um.mission_id = m.mission_id
        WHERE um.user_id = ?
          AND (
            (m.reset_cycle = 'daily' AND (um.cycle_key IS NULL OR um.cycle_key != ?))
            OR (m.reset_cycle = 'weekly' AND (um.cycle_key IS NULL OR um.cycle_key != ?))
            OR (m.reset_cycle = 'monthly' AND (um.cycle_key IS NULL OR um.cycle_key != ?))
          )
    ");
    $stmt->execute([$userId, $daily, $weekly, $monthly]);
    $outdated = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$outdated) return;

    $updateStmt = $conn->prepare("
        UPDATE user_missions
        SET progress = 0, done_status = 0, claimed_at = NULL, cycle_key = ?
        WHERE user_id = ? AND mission_id = ?
    ");
    foreach ($outdated as $row) {
        $cycle = strtolower((string)($row['reset_cycle'] ?? 'daily'));
        $key = $cycle === 'weekly' ? $weekly : ($cycle === 'monthly' ? $monthly : $daily);
        $updateStmt->execute([$key, $userId, (int)$row['mission_id']]);
    }
}

/**
 * Increment user progress for all active missions of a given type.
 * Also marks missions as completed when progress reaches target.
 */
function nexusCanAccrueMissions(int $userId = 0): bool
{
    $role = strtolower(trim((string)($_SESSION['user_role'] ?? '')));
    if ($role === 'user') {
        return true;
    }
    if (in_array($role, ['admin', 'moderator', 'guest'], true)) {
        return false;
    }
    $userId = $userId > 0 ? $userId : (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return false;
    }
    global $conn;
    if (!($conn instanceof PDO)) {
        return false;
    }
    try {
        $stmt = $conn->prepare("
            SELECT LOWER(TRIM(r.role))
            FROM users u
            INNER JOIN roles r ON r.role_id = u.role_id
            WHERE u.user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return (string)$stmt->fetchColumn() === 'user';
    } catch (Throwable $e) {
        return false;
    }
}

function nexusRecordDailyLoginVisit(PDO $conn, int $userId): void
{
    if ($userId <= 0) {
        return;
    }
    try {
        $offset = nexusViewerOffsetMinutes();
        $stmt = $conn->prepare("
            INSERT INTO login_history (user_id, status)
            SELECT ?, 'success'
            FROM DUAL
            WHERE NOT EXISTS (
                SELECT 1 FROM login_history
                WHERE user_id = ?
                  AND status = 'success'
                  AND DATE(DATE_ADD(attempted_at, INTERVAL {$offset} MINUTE))
                      = DATE(DATE_ADD(UTC_TIMESTAMP(), INTERVAL {$offset} MINUTE))
            )
        ");
        $stmt->execute([$userId, $userId]);
    } catch (Throwable $e) {
        error_log('daily login history: ' . $e->getMessage());
    }
}

function nexusAwardDailyLogin(int $userId): void
{
    if ($userId <= 0 || !nexusCanAccrueMissions($userId)) {
        return;
    }

    $today = getCurrentCycleKey('daily');
    if ($today !== '' && (string)($_SESSION['nexus_daily_login_day'] ?? '') === $today) {
        return;
    }

    global $conn;
    if (!($conn instanceof PDO)) {
        return;
    }

    try {
        $stmt = $conn->prepare("
            SELECT m.mission_id, m.reset_cycle, um.progress, um.cycle_key
            FROM missions m
            LEFT JOIN user_missions um
              ON um.mission_id = m.mission_id AND um.user_id = ?
            WHERE m.mission_type = 'daily_login' AND m.is_active = 1
        ");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $needsAward = false;
        foreach ($rows as $row) {
            $cycle = (string)($row['reset_cycle'] ?? 'daily');
            $key = getCurrentCycleKey($cycle);
            $storedKey = (string)($row['cycle_key'] ?? '');
            $progress = (int)($row['progress'] ?? 0);
            if ($storedKey !== $key || $progress < 1) {
                $needsAward = true;
                break;
            }
        }
        if ($needsAward) {
            updateMissionProgress($userId, 'daily_login', 1);
        }
        nexusRecordDailyLoginVisit($conn, $userId);
        if ($today !== '') {
            $_SESSION['nexus_daily_login_day'] = $today;
        }
    } catch (Throwable $e) {
        error_log('daily_login mission: ' . $e->getMessage());
    }
}

function updateMissionProgress(int $userId, string $missionType, int $increment = 1): void {
    global $conn;

    if ($userId <= 0 || !nexusCanAccrueMissions($userId)) {
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