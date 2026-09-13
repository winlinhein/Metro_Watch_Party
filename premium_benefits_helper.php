<?php

require_once __DIR__ . '/premium_status_helper.php';
require_once __DIR__ . '/schema_upgrade_helper.php';

const NEXUS_FREE_ROOM_CAP = 5;
const NEXUS_PREMIUM_ROOM_CAP = 30;
const NEXUS_PREMIUM_MISSION_MULT = 2;

function nexusIsPremium(PDO $conn, int $userId): bool
{
    return !empty(resolveUserPremium($conn, $userId)['is_premium']);
}

function nexusRoomCapacityForUser(PDO $conn, int $userId): int
{
    return nexusIsPremium($conn, $userId) ? NEXUS_PREMIUM_ROOM_CAP : NEXUS_FREE_ROOM_CAP;
}

function nexusMissionPoints(int $basePoints, bool $isPremium): int
{
    $base = max(0, $basePoints);
    return $isPremium ? (int)round($base * NEXUS_PREMIUM_MISSION_MULT) : $base;
}

function nexusIsPremiumRarity(?string $rarity): bool
{
    return strtolower(trim((string)$rarity)) === 'premium';
}

function nexusMovieIsPremium($value): bool
{
    return (int)$value === 1 || $value === true || $value === '1';
}

function nexusCountRoomOccupants(PDO $conn, int $roomId): int
{
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT user_id)
            FROM room_participants
            WHERE room_id = ?
              AND last_seen > DATE_SUB(NOW(), INTERVAL 45 SECOND)
        ");
        $stmt->execute([$roomId]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function nexusRoomMaxMembers(PDO $conn, array $room): int
{
    $stored = (int)($room['max_members'] ?? 0);
    if ($stored > 0) {
        return $stored;
    }
    $hostId = (int)($room['host_id'] ?? 0);
    return $hostId > 0 ? nexusRoomCapacityForUser($conn, $hostId) : NEXUS_FREE_ROOM_CAP;
}

function nexusRoomIsFull(PDO $conn, array $room, int $joiningUserId = 0): bool
{
    $roomId = (int)($room['room_id'] ?? 0);
    if ($roomId <= 0) {
        return true;
    }
    $max = nexusRoomMaxMembers($conn, $room);
    $count = nexusCountRoomOccupants($conn, $roomId);
    if ($joiningUserId > 0) {
        $self = $conn->prepare("
            SELECT 1 FROM room_participants
            WHERE room_id = ? AND user_id = ?
              AND last_seen > DATE_SUB(NOW(), INTERVAL 45 SECOND)
            LIMIT 1
        ");
        $self->execute([$roomId, $joiningUserId]);
        if ($self->fetchColumn()) {
            return false;
        }
    }
    return $count >= $max;
}
