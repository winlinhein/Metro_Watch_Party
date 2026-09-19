<?php

require_once __DIR__ . '/premium_status_helper.php';
require_once __DIR__ . '/schema_upgrade_helper.php';

const NEXUS_FREE_ROOM_CAP = 5;
const NEXUS_PREMIUM_ROOM_CAP = 30;
const NEXUS_PREMIUM_MISSION_MULT = 2;
const NEXUS_FREE_WATCHLIST_CAP = 10;

function nexusIsPremium(PDO $conn, int $userId): bool
{
    return !empty(resolveUserPremium($conn, $userId)['is_premium']);
}

function nexusRoomCapacityForUser(PDO $conn, int $userId): int
{
    return nexusIsPremium($conn, $userId) ? NEXUS_PREMIUM_ROOM_CAP : NEXUS_FREE_ROOM_CAP;
}

function nexusWatchlistCap(PDO $conn, int $userId): ?int
{
    return nexusIsPremium($conn, $userId) ? null : NEXUS_FREE_WATCHLIST_CAP;
}

function nexusWatchlistCount(PDO $conn, int $userId): int
{
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM watchlists WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function nexusOccupancyFields(int $count, int $max): array
{
    $max = $max > 0 ? $max : NEXUS_FREE_ROOM_CAP;
    $count = max(0, $count);
    return [
        'members' => $count,
        'max_members' => $max,
        'occupancy' => $count . '/' . $max,
    ];
}

function nexusRoomOccupancy(PDO $conn, array $room, ?int $knownCount = null): array
{
    $max = nexusRoomMaxMembers($conn, $room);
    $count = $knownCount !== null
        ? max(0, $knownCount)
        : nexusCountRoomOccupants($conn, (int)($room['room_id'] ?? 0));
    return nexusOccupancyFields($count, $max);
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
