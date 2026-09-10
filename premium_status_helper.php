<?php

function resolveUserPremium(PDO $conn, int $userId): array
{
    if ($userId <= 0) {
        return ['is_premium' => false, 'premium_expires_at' => null];
    }

    $stmt = $conn->prepare("SELECT is_premium, premium_expires_at FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $isPremium = (bool)($row['is_premium'] ?? false);
    $expires = $row['premium_expires_at'] ?? null;

    if ($isPremium && $expires && strtotime((string)$expires) < time()) {
        $conn->prepare("UPDATE users SET is_premium = 0 WHERE user_id = ?")->execute([$userId]);
        $isPremium = false;
        $expires = null;
    }

    if (!$isPremium) {
        $expires = null;
    }

    return [
        'is_premium' => $isPremium,
        'premium_expires_at' => $expires ? (string)$expires : null,
    ];
}

function fallbackAvatarUrl(string $name, int $userId = 0): string
{
    $palette = ['ef4444', '4f46e5', '10b981', 'f59e0b', '8b5cf6', '06b6d4'];
    $bg = $palette[$userId > 0 ? ($userId % count($palette)) : 0];
    $label = $name !== '' ? $name : 'User';
    return 'https://ui-avatars.com/api/?name=' . rawurlencode($label) . '&background=' . $bg . '&color=fff&bold=true';
}
