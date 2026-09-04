<?php
/**
 * Shared helpers for avatar URLs and profile border previews.
 */
require_once __DIR__ . '/shop_image_helper.php';

function normalizeAvatarUrl(?string $avatarUrl): string
{
    $avatarUrl = trim((string)$avatarUrl);
    if ($avatarUrl === '') {
        return '';
    }
    if (preg_match('#^(https?:)?//#i', $avatarUrl) || str_starts_with($avatarUrl, 'data:') || str_starts_with($avatarUrl, 'blob:')) {
        return $avatarUrl;
    }
    // Already a shared media endpoint
    if (str_starts_with($avatarUrl, '/user_backend/media.php')) {
        return $avatarUrl;
    }
    if (str_starts_with($avatarUrl, '/')) {
        // Legacy disk paths → shared DB/media gateway (works across machines on one remote DB)
        if (preg_match('#^/uploads/avatars/#', $avatarUrl)) {
            return '/user_backend/media.php?path=' . rawurlencode($avatarUrl);
        }
        return $avatarUrl;
    }
    // Legacy bare filenames stored without the uploads path
    $path = '/uploads/avatars/' . ltrim($avatarUrl, '/');
    return '/user_backend/media.php?path=' . rawurlencode($path);
}

function upsertUserCustomization(PDO $conn, int $userId, int $borderId = 0, int $themeId = 0): void
{
    $borderVal = $borderId > 0 ? $borderId : null;
    $themeVal = $themeId > 0 ? $themeId : null;
    $stmt = $conn->prepare("
        INSERT INTO user_customizations (user_id, active_border_id, active_theme_id, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            active_border_id = VALUES(active_border_id),
            updated_at = NOW()
    ");
    $stmt->execute([$userId, $borderVal, $themeVal]);
}

function ensureUserCustomizationRow(PDO $conn, int $userId): int
{
    $stmt = $conn->prepare("SELECT active_border_id, active_theme_id FROM user_customizations WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return (int)($row['active_border_id'] ?? 0);
    }
    upsertUserCustomization($conn, $userId, 0, 0);
    return 0;
}

function userOwnsItem(PDO $conn, int $userId, int $itemId): bool
{
    if ($itemId <= 0) {
        return true;
    }
    $stmt = $conn->prepare("SELECT 1 FROM user_inventory WHERE user_id = ? AND item_id = ? LIMIT 1");
    $stmt->execute([$userId, $itemId]);
    return (bool)$stmt->fetchColumn();
}

function borderPreviewForId(PDO $conn, int $borderId): string
{
    if ($borderId <= 0) {
        return '';
    }
    $stmt = $conn->prepare("SELECT image_url, item_name FROM shop_items WHERE item_id = ? LIMIT 1");
    $stmt->execute([$borderId]);
    $border = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    return shopImageUrl($border['image_url'] ?? '', $border['item_name'] ?? '');
}

function getActiveBorderId(PDO $conn, int $userId): int
{
    $borderId = ensureUserCustomizationRow($conn, $userId);
    if ($borderId !== 0 && !userOwnsItem($conn, $userId, $borderId)) {
        upsertUserCustomization($conn, $userId, 0, 0);
        return 0;
    }
    return $borderId;
}

/**
 * Avatar + border fields for a single user (for Pusher / API payloads).
 */
function getUserProfileMedia(PDO $conn, int $userId): array
{
    if ($userId <= 0) {
        return ['avatar_url' => '', 'border_preview' => '', 'border_id' => 0];
    }
    $rows = attachProfileMedia($conn, [['user_id' => $userId]]);
    $row = $rows[0] ?? [];
    return [
        'avatar_url' => $row['avatar_url'] ?? '',
        'border_preview' => $row['border_preview'] ?? '',
        'border_id' => (int)($row['border_id'] ?? 0),
    ];
}

/**
 * Whether a stored avatar URL can actually be served (local file or media_files row).
 * Prevents admin/user UIs from requesting dead /uploads paths and flooding 404s.
 */
function avatarUrlIsServable(PDO $conn, string $url): bool
{
    $url = trim($url);
    if ($url === '') {
        return false;
    }
    if (preg_match('#^(https?:)?//#i', $url) || str_starts_with($url, 'data:') || str_starts_with($url, 'blob:')) {
        return true;
    }

    $mediaId = 0;
    $publicPath = '';
    if (preg_match('/[?&]id=(\d+)/', $url, $m)) {
        $mediaId = (int)$m[1];
    } elseif (preg_match('/[?&]path=([^&]+)/', $url, $m)) {
        $publicPath = rawurldecode($m[1]);
    } elseif (preg_match('#^/uploads/(avatars|chat_images)/#', $url)) {
        $publicPath = $url;
    } else {
        // Non-upload relative paths (e.g. already a working gateway) — keep
        return str_starts_with($url, '/');
    }

    if ($mediaId > 0) {
        try {
            $stmt = $conn->prepare('SELECT public_path FROM media_files WHERE id = ? LIMIT 1');
            $stmt->execute([$mediaId]);
            $publicPath = (string)($stmt->fetchColumn() ?: '');
            if ($publicPath === '') {
                return false;
            }
        } catch (Throwable $e) {
            return false;
        }
    }

    if ($publicPath !== '') {
        $local = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $publicPath);
        if (is_file($local)) {
            return true;
        }
        try {
            $stmt = $conn->prepare('SELECT 1 FROM media_files WHERE public_path = ? LIMIT 1');
            $stmt->execute([$publicPath]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    return false;
}

/**
 * Attach avatar_url + border_preview onto rows that contain user_id.
 * Mutates and returns the same array.
 */
function attachProfileMedia(PDO $conn, array $rows, string $userIdKey = 'user_id'): array
{
    if (!$rows) {
        return $rows;
    }

    $ids = [];
    foreach ($rows as $row) {
        $id = (int)($row[$userIdKey] ?? 0);
        if ($id > 0) {
            $ids[$id] = true;
        }
    }
    $ids = array_keys($ids);
    if (!$ids) {
        return $rows;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("SELECT user_id, avatar_url FROM users WHERE user_id IN ($placeholders)");
    $stmt->execute($ids);
    $avatars = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
        $uid = (int)$u['user_id'];
        $normalized = normalizeAvatarUrl($u['avatar_url'] ?? '');
        $avatars[$uid] = ($normalized !== '' && avatarUrlIsServable($conn, $normalized))
            ? $normalized
            : '';
    }

    $stmt = $conn->prepare("
        SELECT uc.user_id, uc.active_border_id, si.image_url, si.item_name
        FROM user_customizations uc
        LEFT JOIN shop_items si ON si.item_id = uc.active_border_id
        WHERE uc.user_id IN ($placeholders)
    ");
    $stmt->execute($ids);
    $borders = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
        $uid = (int)$b['user_id'];
        $bid = (int)$b['active_border_id'];
        $borders[$uid] = $bid > 0
            ? shopImageUrl($b['image_url'] ?? '', $b['item_name'] ?? '')
            : '';
        $borders[$uid . '_id'] = $bid;
    }

    // Drop borders the user no longer owns
    $stmt = $conn->prepare("SELECT user_id, item_id FROM user_inventory WHERE user_id IN ($placeholders)");
    $stmt->execute($ids);
    $owned = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $inv) {
        $owned[(int)$inv['user_id']][(int)$inv['item_id']] = true;
    }

    foreach ($rows as &$row) {
        $uid = (int)($row[$userIdKey] ?? 0);
        $row['avatar_url'] = $avatars[$uid] ?? '';
        $bid = (int)($borders[$uid . '_id'] ?? 0);
        if ($bid > 0 && empty($owned[$uid][$bid])) {
            $bid = 0;
        }
        $preview = $bid > 0 ? ($borders[$uid] ?? '') : '';
        // Drop borders whose shop image file is missing
        if ($preview !== '' && str_starts_with($preview, '/')) {
            $borderFile = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $preview);
            if (!is_file($borderFile)) {
                $preview = '';
            }
        }
        $row['border_id'] = $preview !== '' ? $bid : 0;
        $row['border_preview'] = $preview;
    }
    unset($row);

    return $rows;
}

function attachProfileMediaTree(PDO $conn, array $comments): array
{
    $flat = [];
    $walk = function ($nodes) use (&$walk, &$flat) {
        foreach ($nodes as $node) {
            $flat[] = $node;
            if (!empty($node['replies']) && is_array($node['replies'])) {
                $walk($node['replies']);
            }
        }
    };
    $walk($comments);
    $enriched = attachProfileMedia($conn, $flat);
    $byId = [];
    foreach ($enriched as $row) {
        $id = $row['id'] ?? $row['comment_id'] ?? null;
        if ($id !== null) {
            $byId[$id] = $row;
        }
    }

    $rebuild = function ($nodes) use (&$rebuild, $byId) {
        $out = [];
        foreach ($nodes as $node) {
            $id = $node['id'] ?? $node['comment_id'] ?? null;
            $merged = $id !== null && isset($byId[$id]) ? array_merge($node, [
                'avatar_url' => $byId[$id]['avatar_url'] ?? '',
                'border_preview' => $byId[$id]['border_preview'] ?? '',
                'border_id' => $byId[$id]['border_id'] ?? 0,
            ]) : $node;
            if (!empty($merged['replies']) && is_array($merged['replies'])) {
                $merged['replies'] = $rebuild($merged['replies']);
            }
            $out[] = $merged;
        }
        return $out;
    };

    return $rebuild($comments);
}
