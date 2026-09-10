<?php
require_once __DIR__ . '/pusher_helper.php';

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

    $types = array_values(array_filter(array_map('strval', $opts['types'] ?? [])));
    $senderId = isset($opts['sender_id']) ? (int)$opts['sender_id'] : 0;
    $roomId = isset($opts['room_id']) ? (int)$opts['room_id'] : 0;
    $explicitIds = array_values(array_filter(array_map('intval', $opts['ids'] ?? [])));

    $sql = "SELECT id, type, sender_id, message FROM notifications WHERE user_id = ?";
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
            $message = (string)($row['message'] ?? '');
            if (!preg_match('/\|room:' . $roomId . '(?:\D|$)/', $message)) {
                continue;
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
