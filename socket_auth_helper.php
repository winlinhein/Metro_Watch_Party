<?php
require_once __DIR__ . '/pusher_helper.php';

function nexusCreateSocketToken(?int $userId): string
{
    if (!$userId || $userId <= 0 || PUSHER_SECRET === '') {
        return '';
    }

    $payload = rtrim(strtr(base64_encode(json_encode([
        'user_id' => $userId,
        'expires_at' => time() + (4 * 60 * 60),
    ], JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');

    return $payload . '.' . hash_hmac('sha256', $payload, PUSHER_SECRET);
}
