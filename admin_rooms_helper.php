<?php
require_once __DIR__ . '/pusher_helper.php';
require_once __DIR__ . '/media_store_helper.php';
require_once __DIR__ . '/room_chat_helper.php';

function isRoomClosed(?string $status): bool
{
    return in_array((string)$status, ['ended', 'deleted'], true);
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
