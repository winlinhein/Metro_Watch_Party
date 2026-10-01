<?php
require_once __DIR__ . '/pusher_helper.php';
require_once __DIR__ . '/media_store_helper.php';
require_once __DIR__ . '/room_chat_helper.php';

function isRoomClosed(?string $status): bool
{
    return in_array((string)$status, ['ended', 'deleted'], true);
}

function nexusRoomStaleSeconds(): int
{
    return 40;
}

function sweepAbandonedRooms(PDO $conn): void
{
    static $ran = false;
    if ($ran) {
        return;
    }
    $ran = true;

    $stale = nexusRoomStaleSeconds();
    try {
        $conn->exec("DELETE FROM room_participants WHERE last_seen < DATE_SUB(NOW(), INTERVAL {$stale} SECOND)");
    } catch (Throwable $ignore) {
    }

    try {
        $rooms = $conn->query("SELECT room_id, host_id, created_at FROM rooms WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $ignore) {
        return;
    }

    foreach ($rooms as $room) {
        $roomId = (int)($room['room_id'] ?? 0);
        $hostId = (int)($room['host_id'] ?? 0);
        if ($roomId <= 0) {
            continue;
        }

        try {
            $countStmt = $conn->prepare("SELECT COUNT(*) FROM room_participants WHERE room_id = ?");
            $countStmt->execute([$roomId]);
            $count = (int)$countStmt->fetchColumn();
            $hostHere = false;
            if ($count > 0 && $hostId > 0) {
                $hostStmt = $conn->prepare("SELECT 1 FROM room_participants WHERE room_id = ? AND user_id = ? LIMIT 1");
                $hostStmt->execute([$roomId, $hostId]);
                $hostHere = (bool)$hostStmt->fetchColumn();
            }
        } catch (Throwable $ignore) {
            continue;
        }

        if ($count === 0) {
            $createdTs = strtotime((string)($room['created_at'] ?? ''));
            if ($createdTs && $createdTs > time() - 20) {
                continue;
            }
            closeWatchPartyRoom($conn, $roomId, [
                'from_user_id' => $hostId,
                'message' => 'This watch party ended because the room was empty.',
            ]);
            continue;
        }

        if ($hostHere) {
            continue;
        }

        closeWatchPartyRoom($conn, $roomId, [
            'from_user_id' => $hostId,
            'message' => 'The host left this watch party.',
        ]);
    }
}

function markRoomParticipantUnloaded(PDO $conn, int $roomId, int $userId, string $peerId): void
{
    if ($roomId <= 0 || $userId <= 0) {
        return;
    }
    $backdate = max(1, nexusRoomStaleSeconds() - 12);
    try {
        if ($peerId !== '') {
            $conn->prepare("
                UPDATE room_participants
                SET last_seen = DATE_SUB(NOW(), INTERVAL {$backdate} SECOND)
                WHERE room_id = :room_id AND user_id = :user_id AND peer_id = :peer_id
            ")->execute([
                'room_id' => $roomId,
                'user_id' => $userId,
                'peer_id' => $peerId,
            ]);
        } else {
            $conn->prepare("
                UPDATE room_participants
                SET last_seen = DATE_SUB(NOW(), INTERVAL {$backdate} SECOND)
                WHERE room_id = :room_id AND user_id = :user_id
            ")->execute([
                'room_id' => $roomId,
                'user_id' => $userId,
            ]);
        }
    } catch (Throwable $ignore) {
    }
    sweepAbandonedRooms($conn);
}

function broadcastAdminRoomsChanged(string $action, array $extra = []): void
{
    if (!function_exists('triggerPusherEvent')) {
        return;
    }
    triggerPusherEvent('admin-moderation-channel', 'rooms-changed', array_merge([
        'action' => $action,
        'at' => date('c'),
    ], $extra));
}

function closeWatchPartyRoom(PDO $conn, int $roomId, array $opts = []): bool
{
    $roomId = (int)$roomId;
    if ($roomId <= 0) {
        return false;
    }

    $fromUserId = (int)($opts['from_user_id'] ?? 0);
    $forcedByAdmin = !empty($opts['forced_by_admin']);
    $message = (string)($opts['message'] ?? (
        $forcedByAdmin
            ? 'An admin closed this watch party.'
            : 'The host ended this watch party.'
    ));

    try {
        $conn->prepare("DELETE FROM room_participants WHERE room_id = :room_id")
             ->execute(['room_id' => $roomId]);
    } catch (Throwable $ignore) {}

    try {
        $conn->prepare("DELETE FROM room_kicks WHERE room_id = :room_id")
             ->execute(['room_id' => $roomId]);
    } catch (Throwable $ignore) {}

    try {
        $conn->prepare("DELETE FROM room_join_requests WHERE room_id = :room_id")
             ->execute(['room_id' => $roomId]);
    } catch (Throwable $ignore) {}

    try {
        deleteRoomChat($conn, $roomId);
    } catch (Throwable $ignore) {}

    try {
        require_once __DIR__ . '/notifications_helper.php';
        deleteNotificationsForRoom($conn, $roomId);
    } catch (Throwable $ignore) {}

    $updated = $conn->prepare("UPDATE rooms SET status = 'deleted' WHERE room_id = :room_id AND status = 'active'");
    $updated->execute(['room_id' => $roomId]);

    $channel = 'watch-party-' . $roomId;
    try {
        triggerPusherEvent($channel, 'room-ended', [
            'fromUserId' => $fromUserId,
            'is_ended' => true,
            'forced_by_admin' => $forcedByAdmin,
            'message' => $message,
        ]);
    } catch (Throwable $ignore) {}

    broadcastAdminRoomsChanged($forcedByAdmin ? 'force-close' : 'ended', [
        'room_id' => $roomId,
        'forced_by_admin' => $forcedByAdmin,
        'message' => $message,
    ]);

    return $updated->rowCount() > 0;
}
