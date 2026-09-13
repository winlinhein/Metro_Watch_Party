<?php
/**
 * Shared helpers for avatar URLs and profile border previews.
 */
require_once __DIR__ . '/shop_image_helper.php';
require_once __DIR__ . '/premium_benefits_helper.php';

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
        if (preg_match('#^/uploads/avatars/#', $avatarUrl)) {
            $local = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $avatarUrl);
            if (is_file($local)) {
                return $avatarUrl;
            }
            return '/user_backend/media.php?path=' . rawurlencode($avatarUrl);
        }
        return $avatarUrl;
    }
    // Legacy bare filenames stored without the uploads path
    $path = '/uploads/avatars/' . ltrim($avatarUrl, '/');
    $local = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if (is_file($local)) {
        return $path;
    }
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

function nexusUserIsStaff(PDO $conn, int $userId): bool
{
    static $cache = [];
    if ($userId <= 0) {
        return false;
    }
    if (array_key_exists($userId, $cache)) {
        return $cache[$userId];
    }
    try {
        $stmt = $conn->prepare("
            SELECT LOWER(TRIM(r.role))
            FROM users u
            INNER JOIN roles r ON r.role_id = u.role_id
            WHERE u.user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $role = (string)$stmt->fetchColumn();
        $cache[$userId] = in_array($role, ['admin', 'moderator'], true);
    } catch (Throwable $e) {
        $cache[$userId] = false;
    }
    return $cache[$userId];
}

function nexusStaffBorderItemIds(PDO $conn): array
{
    static $ids = null;
    if (is_array($ids)) {
        return $ids;
    }
    try {
        $stmt = $conn->query("SELECT item_id FROM shop_items WHERE LOWER(category) = 'border'");
        $ids = array_values(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [])));
    } catch (Throwable $e) {
        $ids = [];
    }
    return $ids;
}

function nexusPremiumBorderItemIds(PDO $conn): array
{
    static $ids = null;
    if (is_array($ids)) {
        return $ids;
    }
    try {
        $stmt = $conn->query("
            SELECT item_id
            FROM shop_items
            WHERE LOWER(category) = 'border'
              AND LOWER(TRIM(rarity)) = 'premium'
        ");
        $ids = array_values(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [])));
    } catch (Throwable $e) {
        $ids = [];
    }
    return $ids;
}

function nexusShopItemIsPremiumBorder(PDO $conn, int $itemId): bool
{
    if ($itemId <= 0) {
        return false;
    }
    try {
        $stmt = $conn->prepare("
            SELECT rarity, category
            FROM shop_items
            WHERE item_id = ?
            LIMIT 1
        ");
        $stmt->execute([$itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return strtolower(trim((string)($row['category'] ?? ''))) === 'border'
            && nexusIsPremiumRarity($row['rarity'] ?? '');
    } catch (Throwable $e) {
        return false;
    }
}

function nexusPurchasedItemIds(PDO $conn, int $userId): array
{
    if ($userId <= 0) {
        return [];
    }
    $stmt = $conn->prepare("SELECT item_id FROM user_inventory WHERE user_id = ?");
    $stmt->execute([$userId]);
    return array_values(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [])));
}

function nexusEffectiveInventory(PDO $conn, int $userId): array
{
    $bought = nexusPurchasedItemIds($conn, $userId);
    $extra = [];
    if (nexusUserIsStaff($conn, $userId)) {
        $extra = nexusStaffBorderItemIds($conn);
    } elseif (nexusIsPremium($conn, $userId)) {
        $extra = nexusPremiumBorderItemIds($conn);
    }
    return array_values(array_unique(array_merge($bought, $extra)));
}

function userOwnsItem(PDO $conn, int $userId, int $itemId): bool
{
    if ($itemId <= 0) {
        return true;
    }
    $stmt = $conn->prepare("SELECT 1 FROM user_inventory WHERE user_id = ? AND item_id = ? LIMIT 1");
    $stmt->execute([$userId, $itemId]);
    if ($stmt->fetchColumn()) {
        return true;
    }
    if (nexusUserIsStaff($conn, $userId)) {
        $check = $conn->prepare("SELECT 1 FROM shop_items WHERE item_id = ? AND LOWER(category) = 'border' LIMIT 1");
        $check->execute([$itemId]);
        return (bool)$check->fetchColumn();
    }
    return nexusShopItemIsPremiumBorder($conn, $itemId) && nexusIsPremium($conn, $userId);
}

function nexusRevertUnearnedStaffBorder(PDO $conn, int $userId): void
{
    if ($userId <= 0) {
        return;
    }
    $borderId = ensureUserCustomizationRow($conn, $userId);
    if ($borderId <= 0) {
        return;
    }
    if (userOwnsItem($conn, $userId, $borderId)) {
        return;
    }

    $themeStmt = $conn->prepare("SELECT active_theme_id FROM user_customizations WHERE user_id = ?");
    $themeStmt->execute([$userId]);
    $themeId = (int)($themeStmt->fetchColumn() ?: 0);
    upsertUserCustomization($conn, $userId, 0, $themeId);

    if (function_exists('triggerPusherEvent')) {
        $avatarStmt = $conn->prepare("SELECT avatar_url FROM users WHERE user_id = ?");
        $avatarStmt->execute([$userId]);
        triggerPusherEvent('profile-updates', 'profile_changed', [
            'user_id' => $userId,
            'avatar_url' => normalizeAvatarUrl((string)($avatarStmt->fetchColumn() ?: '')),
            'border_id' => 0,
            'border_preview' => '',
        ]);
    }
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
        $avatars[$uid] = normalizeAvatarUrl($u['avatar_url'] ?? '');
    }

    $stmt = $conn->prepare("
        SELECT uc.user_id, uc.active_border_id, si.image_url, si.item_name, si.rarity, si.category
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
        $borders[$uid . '_rarity'] = strtolower(trim((string)($b['rarity'] ?? '')));
        $borders[$uid . '_category'] = strtolower(trim((string)($b['category'] ?? '')));
    }

    // Drop borders the user no longer owns, except staff or active premium borders
    $stmt = $conn->prepare("SELECT user_id, item_id FROM user_inventory WHERE user_id IN ($placeholders)");
    $stmt->execute($ids);
    $owned = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $inv) {
        $owned[(int)$inv['user_id']][(int)$inv['item_id']] = true;
    }
    $staffIds = [];
    try {
        $roleStmt = $conn->prepare("
            SELECT u.user_id
            FROM users u
            INNER JOIN roles r ON r.role_id = u.role_id
            WHERE u.user_id IN ($placeholders)
              AND LOWER(TRIM(r.role)) IN ('admin', 'moderator')
        ");
        $roleStmt->execute($ids);
        foreach ($roleStmt->fetchAll(PDO::FETCH_COLUMN) as $staffId) {
            $staffIds[(int)$staffId] = true;
        }
    } catch (Throwable $ignore) {
    }
    $premiumIds = [];
    try {
        $premStmt = $conn->prepare("
            SELECT user_id, is_premium, premium_expires_at
            FROM users
            WHERE user_id IN ($placeholders)
        ");
        $premStmt->execute($ids);
        foreach ($premStmt->fetchAll(PDO::FETCH_ASSOC) as $prem) {
            $uid = (int)$prem['user_id'];
            $active = !empty($prem['is_premium']);
            $expires = $prem['premium_expires_at'] ?? null;
            if ($active && $expires && strtotime((string)$expires) < time()) {
                $active = false;
            }
            if ($active) {
                $premiumIds[$uid] = true;
            }
        }
    } catch (Throwable $ignore) {
    }

    foreach ($rows as &$row) {
        $uid = (int)($row[$userIdKey] ?? 0);
        $row['avatar_url'] = $avatars[$uid] ?? '';
        $bid = (int)($borders[$uid . '_id'] ?? 0);
        if ($bid > 0 && empty($owned[$uid][$bid]) && empty($staffIds[$uid])) {
            $isPremiumBorder = ($borders[$uid . '_category'] ?? '') === 'border'
                && ($borders[$uid . '_rarity'] ?? '') === 'premium';
            if (!($isPremiumBorder && !empty($premiumIds[$uid]))) {
                $bid = 0;
            }
        }
        $preview = $bid > 0 ? ($borders[$uid] ?? '') : '';
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
