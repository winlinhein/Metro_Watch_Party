<?php
require_once __DIR__ . '/pusher_helper.php';
require_once __DIR__ . '/schema_upgrade_helper.php';

function nexusInsertNotification(
    PDO $conn,
    int $userId,
    ?int $senderId,
    string $type,
    string $message,
    ?int $roomId = null,
    ?int $requestId = null
): int {
    ensureAppSchema($conn);
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at, room_id, request_id)
        VALUES (?, ?, ?, ?, 0, NOW(), ?, ?)
    ");
    $stmt->execute([
        $userId,
        $senderId,
        $type,
        $message,
        $roomId && $roomId > 0 ? $roomId : null,
        $requestId && $requestId > 0 ? $requestId : null,
    ]);
    return (int)$conn->lastInsertId();
}

/**
 * Delete a user's notifications matching type / sender / room, then notify live clients.
 *
 * @param array{types?: string[], sender_id?: int|null, room_id?: int|null, ids?: int[]} $opts
 * @return int[] Deleted notification IDs
 */
function deleteMatchingNotifications(PDO $conn, int $userId, array $opts = []): array
{
    if ($userId <= 0) {
        return [];
    }
    ensureAppSchema($conn);

    $types = array_values(array_filter(array_map('strval', $opts['types'] ?? [])));
    $senderId = isset($opts['sender_id']) ? (int)$opts['sender_id'] : 0;
    $roomId = isset($opts['room_id']) ? (int)$opts['room_id'] : 0;
    $explicitIds = array_values(array_filter(array_map('intval', $opts['ids'] ?? [])));

    $sql = "SELECT id, type, sender_id, message, room_id FROM notifications WHERE user_id = ?";
    $params = [$userId];

    if ($explicitIds) {
        $in = implode(',', array_fill(0, count($explicitIds), '?'));
        $sql .= " AND id IN ({$in})";
        $params = array_merge($params, $explicitIds);
    }
    if ($types) {
        $in = implode(',', array_fill(0, count($types), '?'));
        $sql .= " AND type IN ({$in})";
        $params = array_merge($params, $types);
    }
    if ($senderId > 0) {
        $sql .= " AND sender_id = ?";
        $params[] = $senderId;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ids = [];
    foreach ($rows as $row) {
        if ($roomId > 0) {
            $rowRoom = (int)($row['room_id'] ?? 0);
            if ($rowRoom > 0) {
                if ($rowRoom !== $roomId) {
                    continue;
                }
            } else {
                $message = (string)($row['message'] ?? '');
                if (!preg_match('/\|room:' . $roomId . '(?:\D|$)/', $message)) {
                    continue;
                }
            }
        }
        $ids[] = (int)$row['id'];
    }

    $ids = array_values(array_unique(array_filter($ids)));
    if (!$ids) {
        return [];
    }

    $in = implode(',', array_fill(0, count($ids), '?'));
    $del = $conn->prepare("DELETE FROM notifications WHERE user_id = ? AND id IN ({$in})");
    $del->execute(array_merge([$userId], $ids));

    triggerPusherEvent("user-{$userId}", 'notifications_deleted', ['ids' => $ids]);
    foreach ($ids as $id) {
        triggerPusherEvent("user-{$userId}", 'notification_deleted', [
            'id' => $id,
            'notification_id' => $id,
        ]);
    }

    return $ids;
}

function deleteNotificationsForRoom(PDO $conn, int $roomId, array $types = []): array
{
    $roomId = (int)$roomId;
    if ($roomId <= 0) {
        return [];
    }
    ensureAppSchema($conn);

    $types = $types ?: ['party_invite', 'join_request', 'join_request_accepted', 'join_request_declined'];
    $types = array_values(array_filter(array_map('strval', $types)));
    if (!$types) {
        return [];
    }

    $in = implode(',', array_fill(0, count($types), '?'));
    $stmt = $conn->prepare("
        SELECT id, user_id, message, room_id
        FROM notifications
        WHERE type IN ({$in})
          AND (room_id = ? OR (room_id IS NULL AND message LIKE ?))
    ");
    $stmt->execute(array_merge($types, [$roomId, '%|room:' . $roomId . '%']));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $byUser = [];
    foreach ($rows as $row) {
        $rowRoom = (int)($row['room_id'] ?? 0);
        if ($rowRoom > 0 && $rowRoom !== $roomId) {
            continue;
        }
        if ($rowRoom <= 0) {
            $message = (string)($row['message'] ?? '');
            if (!preg_match('/\|room:' . $roomId . '(?:\D|$)/', $message)) {
                continue;
            }
        }
        $uid = (int)$row['user_id'];
        $id = (int)$row['id'];
        if ($uid <= 0 || $id <= 0) {
            continue;
        }
        if (!isset($byUser[$uid])) {
            $byUser[$uid] = [];
        }
        $byUser[$uid][] = $id;
    }

    $allIds = [];
    foreach ($byUser as $userId => $ids) {
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) {
            continue;
        }
        $idIn = implode(',', array_fill(0, count($ids), '?'));
        $del = $conn->prepare("DELETE FROM notifications WHERE user_id = ? AND id IN ({$idIn})");
        $del->execute(array_merge([$userId], $ids));
        $allIds = array_merge($allIds, $ids);
        triggerPusherEvent("user-{$userId}", 'notifications_deleted', [
            'ids' => $ids,
            'room_id' => $roomId,
        ]);
        triggerPusherEvent("user-{$userId}", 'room_invites_cleared', [
            'room_id' => $roomId,
            'ids' => $ids,
        ]);
    }

    return $allIds;
}
