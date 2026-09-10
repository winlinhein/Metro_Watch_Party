<?php

function ensureUserLastSeenColumn(PDO $conn): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN last_seen DATETIME NULL DEFAULT NULL");
    } catch (Throwable $e) {
        // Column already exists (or cannot be added); queries still try last_seen.
    }
    $ready = true;
}

function broadcastPresence(int $userId, bool $online): void
{
    if ($userId <= 0) {
        return;
    }
    if (!function_exists('triggerPusherEvent')) {
        require_once __DIR__ . '/pusher_helper.php';
    }
    triggerPusherEvent('presence-status', 'presence_update', [
        'user_id' => $userId,
        'is_online' => $online ? 1 : 0,
    ]);
}

function touchUserPresence(PDO $conn, int $userId): void
{
    if ($userId <= 0) {
        return;
    }
    ensureUserLastSeenColumn($conn);
    $prevStmt = $conn->prepare("SELECT last_seen FROM users WHERE user_id = ? LIMIT 1");
    $prevStmt->execute([$userId]);
    $wasOnline = isOnlineLastSeen($prevStmt->fetchColumn());

    $stmt = $conn->prepare("UPDATE users SET last_seen = NOW() WHERE user_id = ?");
    $stmt->execute([$userId]);

    if (!$wasOnline) {
        broadcastPresence($userId, true);
    }
}

function clearUserPresence(PDO $conn, int $userId): void
{
    if ($userId <= 0) {
        return;
    }
    ensureUserLastSeenColumn($conn);
    $prevStmt = $conn->prepare("SELECT last_seen FROM users WHERE user_id = ? LIMIT 1");
    $prevStmt->execute([$userId]);
    $wasOnline = isOnlineLastSeen($prevStmt->fetchColumn());

    $stmt = $conn->prepare("UPDATE users SET last_seen = NULL WHERE user_id = ?");
    $stmt->execute([$userId]);

    if ($wasOnline) {
        broadcastPresence($userId, false);
    }
}

function isOnlineLastSeen($lastSeen): bool
{
    if ($lastSeen === null || $lastSeen === '' || $lastSeen === false) {
        return false;
    }
    $ts = strtotime((string)$lastSeen);
    if ($ts === false) {
        return false;
    }
    return $ts >= (time() - 90);
}

function onlineFlagFromLastSeen($lastSeen): int
{
    return isOnlineLastSeen($lastSeen) ? 1 : 0;
}
