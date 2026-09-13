<?php

function roomChatImagePrefix(): string
{
    return '__IMG__';
}

function encodeRoomChatText(string $text, ?string $imageUrl): string
{
    $text = trim($text);
    $imageUrl = trim((string)$imageUrl);
    if ($imageUrl === '') {
        return $text;
    }
    $encoded = roomChatImagePrefix() . $imageUrl;
    return $text === '' ? $encoded : $encoded . "\n" . $text;
}

function decodeRoomChatText(?string $raw): array
{
    $raw = (string)$raw;
    $prefix = roomChatImagePrefix();
    if (str_starts_with($raw, $prefix)) {
        $rest = substr($raw, strlen($prefix));
        $nl = strpos($rest, "\n");
        if ($nl === false) {
            return ['type' => 'image', 'image_url' => $rest, 'text' => ''];
        }
        return [
            'type' => 'image',
            'image_url' => substr($rest, 0, $nl),
            'text' => substr($rest, $nl + 1),
        ];
    }
    return ['type' => 'text', 'image_url' => null, 'text' => $raw];
}

function formatRoomChatRow(array $row): array
{
    $decoded = decodeRoomChatText($row['message_text'] ?? '');
    $sentAt = $row['sent_at'] ?? '';
    $time = $sentAt !== '' ? date('g:i A', strtotime((string)$sentAt)) : date('g:i A');

    return [
        'id' => isset($row['message_id']) ? (int)$row['message_id'] : null,
        'senderId' => isset($row['user_id']) && $row['user_id'] !== null ? (int)$row['user_id'] : null,
        'name' => (string)($row['user_name'] ?? $row['guest_nickname'] ?? 'Guest'),
        'text' => $decoded['text'],
        'type' => $decoded['type'],
        'image_url' => $decoded['image_url'],
        'time' => $time,
        'avatar' => (string)($row['avatar_url'] ?? ''),
        'border' => (string)($row['border_preview'] ?? ''),
    ];
}

function fetchRoomChatMessages(PDO $conn, int $roomId, int $limit = 80): array
{
    $limit = max(1, min(200, $limit));
    $stmt = $conn->prepare("
        SELECT
            rm.message_id,
            rm.room_id,
            rm.user_id,
            rm.guest_nickname,
            rm.message_text,
            rm.sent_at,
            COALESCE(u.user_name, rm.guest_nickname, 'Guest') AS user_name
        FROM room_messages rm
        LEFT JOIN users u ON u.user_id = rm.user_id
        WHERE rm.room_id = :room_id
        ORDER BY rm.sent_at ASC, rm.message_id ASC
        LIMIT {$limit}
    ");
    $stmt->execute(['room_id' => $roomId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if (function_exists('attachProfileMedia')) {
        $rows = attachProfileMedia($conn, $rows, 'user_id');
    }

    return array_map('formatRoomChatRow', $rows);
}

function deleteRoomChat(PDO $conn, int $roomId): void
{
    $roomId = (int)$roomId;
    if ($roomId <= 0) {
        return;
    }

    $texts = [];
    try {
        $stmt = $conn->prepare('SELECT message_text FROM room_messages WHERE room_id = :room_id');
        $stmt->execute(['room_id' => $roomId]);
        $texts = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Throwable $ignore) {
        $texts = [];
    }

    try {
        $conn->prepare('DELETE FROM room_messages WHERE room_id = :room_id')
             ->execute(['room_id' => $roomId]);
    } catch (Throwable $ignore) {
    }

    if (!$texts || !function_exists('deleteStoredMedia')) {
        return;
    }

    foreach ($texts as $raw) {
        $decoded = decodeRoomChatText((string)$raw);
        if (!empty($decoded['image_url'])) {
            try {
                deleteStoredMedia($conn, $decoded['image_url']);
            } catch (Throwable $ignore) {
            }
        }
    }
}
