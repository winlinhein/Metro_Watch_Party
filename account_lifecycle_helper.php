<?php

require_once __DIR__ . '/schema_upgrade_helper.php';
require_once __DIR__ . '/media_store_helper.php';

const NEXUS_DELETION_WAIT_SECONDS = 86400;

function nexusScheduleAccountDeletion(PDO $conn, int $userId): void
{
    ensureAppSchema($conn);
    $conn->prepare("UPDATE users SET deletion_requested_at = NOW() WHERE user_id = ?")
        ->execute([$userId]);
    try {
        $conn->prepare("DELETE FROM persistent_session WHERE user_id = ?")->execute([$userId]);
    } catch (Throwable $ignore) {
    }
}

function nexusCancelAccountDeletion(PDO $conn, int $userId): void
{
    ensureAppSchema($conn);
    $conn->prepare("UPDATE users SET deletion_requested_at = NULL WHERE user_id = ?")
        ->execute([$userId]);
}

function nexusDeletionDeadline(?string $requestedAt): ?int
{
    if (!$requestedAt) {
        return null;
    }
    $start = strtotime($requestedAt);
    if ($start === false) {
        return null;
    }
    return $start + NEXUS_DELETION_WAIT_SECONDS;
}

function nexusDeletionExpired(?string $requestedAt): bool
{
    $deadline = nexusDeletionDeadline($requestedAt);
    return $deadline !== null && $deadline <= time();
}

function nexusClearAccountSession(): void
{
    try {
        require_once __DIR__ . '/auth_flow_helper.php';
        nexusClearPersistentSession();
    } catch (Throwable $ignore) {
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

function nexusDeleteFriendshipChats(PDO $conn, int $userId, int $friendId): void
{
    $stmt = $conn->prepare("
        SELECT image_url FROM friends_message
        WHERE (sender_id = ? AND receiver_id = ?)
           OR (sender_id = ? AND receiver_id = ?)
    ");
    $stmt->execute([$userId, $friendId, $friendId, $userId]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $imageUrl) {
        if ($imageUrl) {
            deleteStoredMedia($conn, (string)$imageUrl);
        }
    }
    $conn->prepare("
        DELETE FROM friends_message
        WHERE (sender_id = ? AND receiver_id = ?)
           OR (sender_id = ? AND receiver_id = ?)
    ")->execute([$userId, $friendId, $friendId, $userId]);
}

function nexusSafeExec(PDO $conn, string $sql, array $params = []): void
{
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
    } catch (Throwable $ignore) {
    }
}

function nexusDropColumnForeignKeys(PDO $conn, string $table, string $column): void
{
    try {
        $stmt = $conn->prepare("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $stmt->execute([$table, $column]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $name) {
            $safe = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$name);
            if ($safe !== '') {
                try {
                    $conn->exec("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$safe}`");
                } catch (Throwable $ignore) {
                }
            }
        }
    } catch (Throwable $ignore) {
    }
}

function nexusMakeUserRefNullable(PDO $conn, string $table, string $column): void
{
    nexusDropColumnForeignKeys($conn, $table, $column);
    foreach ([
        "ALTER TABLE `{$table}` MODIFY `{$column}` INT NULL",
        "ALTER TABLE `{$table}` MODIFY `{$column}` INT DEFAULT NULL",
    ] as $sql) {
        try {
            $conn->exec($sql);
            return;
        } catch (Throwable $ignore) {
        }
    }
}

function nexusPrepareKeptRecords(PDO $conn): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    nexusMakeUserRefNullable($conn, 'reports', 'reporter_id');
    nexusMakeUserRefNullable($conn, 'reports', 'reported_user_id');
    nexusMakeUserRefNullable($conn, 'reports', 'comment_id');
    nexusMakeUserRefNullable($conn, 'reports', 'reported_room_id');
    nexusMakeUserRefNullable($conn, 'payment_transactions', 'user_id');

    foreach ([
        "ALTER TABLE reports ADD COLUMN deleted_reporter_name VARCHAR(191) NULL",
        "ALTER TABLE reports ADD COLUMN deleted_reported_name VARCHAR(191) NULL",
        "ALTER TABLE payment_transactions ADD COLUMN deleted_user_name VARCHAR(191) NULL",
        "ALTER TABLE payment_transactions ADD COLUMN deleted_user_email VARCHAR(191) NULL",
    ] as $sql) {
        try {
            $conn->exec($sql);
        } catch (Throwable $ignore) {
        }
    }

    $ready = true;
}

function nexusDetachKeptRecords(PDO $conn, int $userId, string $userName, string $email): void
{
    nexusPrepareKeptRecords($conn);
    $label = $userName !== '' ? $userName : ('User #' . $userId);

    nexusSafeExec($conn, "
        UPDATE reports
        SET deleted_reporter_name = COALESCE(deleted_reporter_name, ?)
        WHERE reporter_id = ?
    ", [$label, $userId]);
    nexusSafeExec($conn, "
        UPDATE reports
        SET deleted_reported_name = COALESCE(deleted_reported_name, ?)
        WHERE reported_user_id = ?
    ", [$label, $userId]);
    nexusSafeExec($conn, "UPDATE reports SET reporter_id = NULL WHERE reporter_id = ?", [$userId]);
    nexusSafeExec($conn, "UPDATE reports SET reported_user_id = NULL WHERE reported_user_id = ?", [$userId]);

    $commentIds = [];
    try {
        $stmt = $conn->prepare("SELECT comment_id FROM movie_comments WHERE user_id = ?");
        $stmt->execute([$userId]);
        $commentIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $ignore) {
    }
    if ($commentIds) {
        $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
        nexusSafeExec($conn, "UPDATE reports SET comment_id = NULL WHERE comment_id IN ({$placeholders})", $commentIds);
    }

    $hostedRooms = [];
    try {
        $stmt = $conn->prepare("SELECT room_id FROM rooms WHERE host_id = ?");
        $stmt->execute([$userId]);
        $hostedRooms = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $ignore) {
    }
    if ($hostedRooms) {
        $placeholders = implode(',', array_fill(0, count($hostedRooms), '?'));
        nexusSafeExec($conn, "UPDATE reports SET reported_room_id = NULL WHERE reported_room_id IN ({$placeholders})", $hostedRooms);
    }

    nexusSafeExec($conn, "
        UPDATE payment_transactions
        SET deleted_user_name = COALESCE(deleted_user_name, ?),
            deleted_user_email = COALESCE(deleted_user_email, ?)
        WHERE user_id = ?
    ", [$label, $email, $userId]);
    nexusSafeExec($conn, "UPDATE payment_transactions SET user_id = NULL WHERE user_id = ?", [$userId]);
}

function nexusPurgeUserAccount(PDO $conn, int $userId): void
{
    if ($userId <= 0) {
        return;
    }

    ensureAppSchema($conn);
    ensureMediaTable($conn);

    $user = [];
    try {
        $stmt = $conn->prepare("SELECT user_name, email, avatar_url FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $ignore) {
    }
    $userName = trim((string)($user['user_name'] ?? ''));
    $email = trim((string)($user['email'] ?? ''));

    nexusDetachKeptRecords($conn, $userId, $userName, $email);

    $chatImages = $conn->prepare("
        SELECT image_url FROM friends_message
        WHERE (sender_id = ? OR receiver_id = ?) AND image_url IS NOT NULL AND image_url <> ''
    ");
    $chatImages->execute([$userId, $userId]);
    foreach ($chatImages->fetchAll(PDO::FETCH_COLUMN) as $imageUrl) {
        deleteStoredMedia($conn, (string)$imageUrl);
    }

    deleteStoredMedia($conn, (string)($user['avatar_url'] ?? ''));
    deletePreviousUserAvatars($conn, $userId);

    $commentIds = [];
    try {
        $stmt = $conn->prepare("SELECT comment_id FROM movie_comments WHERE user_id = ? OR parent_comment_id IN (SELECT comment_id FROM movie_comments WHERE user_id = ?)");
        $stmt->execute([$userId, $userId]);
        $commentIds = array_values(array_unique(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))));
    } catch (Throwable $ignore) {
        try {
            $stmt = $conn->prepare("SELECT comment_id FROM movie_comments WHERE user_id = ?");
            $stmt->execute([$userId]);
            $commentIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Throwable $ignore2) {
        }
    }
    if ($commentIds) {
        $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
        nexusSafeExec($conn, "DELETE FROM comment_likes WHERE comment_id IN ({$placeholders})", $commentIds);
        nexusSafeExec($conn, "DELETE FROM movie_comments WHERE parent_comment_id IN ({$placeholders})", $commentIds);
        nexusSafeExec($conn, "DELETE FROM movie_comments WHERE comment_id IN ({$placeholders})", $commentIds);
    }

    $hostedRooms = [];
    try {
        $stmt = $conn->prepare("SELECT room_id FROM rooms WHERE host_id = ?");
        $stmt->execute([$userId]);
        $hostedRooms = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $ignore) {
    }

    $deletes = [
        ['DELETE FROM comment_likes WHERE user_id = ?', [$userId]],
        ['DELETE FROM movie_comments WHERE user_id = ?', [$userId]],
        ['DELETE FROM movie_rating WHERE user_id = ?', [$userId]],
        ['DELETE FROM friends_message WHERE sender_id = ? OR receiver_id = ?', [$userId, $userId]],
        ['DELETE FROM user_friends WHERE user_id_1 = ? OR user_id_2 = ?', [$userId, $userId]],
        ['DELETE FROM notifications WHERE user_id = ? OR sender_id = ?', [$userId, $userId]],
        ['DELETE FROM login_history WHERE user_id = ?', [$userId]],
        ['DELETE FROM persistent_session WHERE user_id = ?', [$userId]],
        ['DELETE FROM ban_appeals WHERE user_id = ?', [$userId]],
        ['DELETE FROM user_customizations WHERE user_id = ?', [$userId]],
        ['DELETE FROM user_inventory WHERE user_id = ?', [$userId]],
        ['DELETE FROM user_missions WHERE user_id = ?', [$userId]],
        ['DELETE FROM watch_history WHERE user_id = ?', [$userId]],
        ['DELETE FROM watchlists WHERE user_id = ?', [$userId]],
        ['DELETE FROM room_join_requests WHERE host_id = ? OR requester_id = ?', [$userId, $userId]],
        ['DELETE FROM room_kicks WHERE user_id = ?', [$userId]],
        ['DELETE FROM room_members WHERE user_id = ?', [$userId]],
        ['DELETE FROM room_messages WHERE user_id = ?', [$userId]],
        ['DELETE FROM room_participants WHERE user_id = ?', [$userId]],
        ['DELETE FROM media_files WHERE user_id = ?', [$userId]],
        ['DELETE FROM otp_verification WHERE email = ?', [$email]],
    ];

    foreach ($deletes as [$sql, $params]) {
        nexusSafeExec($conn, $sql, $params);
    }

    foreach ($hostedRooms as $rid) {
        if ($rid <= 0) {
            continue;
        }
        foreach ([
            'DELETE FROM room_messages WHERE room_id = ?',
            'DELETE FROM room_members WHERE room_id = ?',
            'DELETE FROM room_participants WHERE room_id = ?',
            'DELETE FROM room_join_requests WHERE room_id = ?',
            'DELETE FROM room_kicks WHERE room_id = ?',
            'DELETE FROM room_watch_history WHERE room_id = ?',
        ] as $sql) {
            nexusSafeExec($conn, $sql, [$rid]);
        }
        nexusSafeExec($conn, 'DELETE FROM rooms WHERE room_id = ?', [$rid]);
    }

    $inTransaction = false;
    try {
        if (!$conn->inTransaction()) {
            $conn->beginTransaction();
            $inTransaction = true;
        }
        $conn->prepare("DELETE FROM users WHERE user_id = ?")->execute([$userId]);
        if ($inTransaction) {
            $conn->commit();
        }
    } catch (Throwable $e) {
        if ($inTransaction && $conn->inTransaction()) {
            $conn->rollBack();
        }
        // Last pass: drop any leftover FKs pointing at users, then delete again.
        foreach ([
            ['reports', 'reporter_id'],
            ['reports', 'reported_user_id'],
            ['payment_transactions', 'user_id'],
            ['rooms', 'host_id'],
            ['movie_comments', 'user_id'],
            ['notifications', 'user_id'],
            ['notifications', 'sender_id'],
            ['friends_message', 'sender_id'],
            ['friends_message', 'receiver_id'],
            ['user_friends', 'user_id_1'],
            ['user_friends', 'user_id_2'],
            ['login_history', 'user_id'],
            ['watchlists', 'user_id'],
            ['watch_history', 'user_id'],
            ['user_inventory', 'user_id'],
            ['user_missions', 'user_id'],
            ['user_customizations', 'user_id'],
            ['room_members', 'user_id'],
            ['room_messages', 'user_id'],
            ['room_participants', 'user_id'],
            ['room_kicks', 'user_id'],
            ['room_join_requests', 'host_id'],
            ['room_join_requests', 'requester_id'],
            ['comment_likes', 'user_id'],
            ['movie_rating', 'user_id'],
            ['ban_appeals', 'user_id'],
            ['persistent_session', 'user_id'],
            ['media_files', 'user_id'],
        ] as [$table, $column]) {
            nexusDropColumnForeignKeys($conn, $table, $column);
        }
        $conn->prepare("DELETE FROM users WHERE user_id = ?")->execute([$userId]);
    }
}

function nexusPurgeExpiredDeletions(PDO $conn): void
{
    ensureAppSchema($conn);
    try {
        $stmt = $conn->query("
            SELECT user_id FROM users
            WHERE deletion_requested_at IS NOT NULL
              AND deletion_requested_at <= DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $userId) {
            try {
                nexusPurgeUserAccount($conn, (int)$userId);
            } catch (Throwable $e) {
                error_log('purge expired deletion ' . $userId . ': ' . $e->getMessage());
            }
        }
    } catch (Throwable $e) {
        error_log('purge expired deletions: ' . $e->getMessage());
    }
}

function nexusUnbanUser(PDO $conn, int $userId): void
{
    if ($userId <= 0) {
        return;
    }
    ensureAppSchema($conn);
    $conn->prepare("UPDATE users SET status = 'active', ban_reason = NULL WHERE user_id = ?")
        ->execute([$userId]);
    try {
        $conn->prepare("
            UPDATE ban_appeals
            SET status = 'approved', reviewed_at = NOW()
            WHERE user_id = ? AND status = 'pending'
        ")->execute([$userId]);
    } catch (Throwable $ignore) {
    }
    try {
        $conn->prepare("
            UPDATE reports
            SET status = 'read'
            WHERE type = 'appeal'
              AND (reporter_id = ? OR reported_user_id = ?)
              AND LOWER(IFNULL(status, 'pending')) = 'pending'
        ")->execute([$userId, $userId]);
    } catch (Throwable $ignore) {
    }
}

function nexusReleasePremiumBorderIfNeeded(PDO $conn, int $userId, bool $isPremium): void
{
    if ($isPremium || $userId <= 0) {
        return;
    }
    try {
        $stmt = $conn->prepare("
            SELECT si.rarity
            FROM user_customizations uc
            JOIN shop_items si ON si.item_id = uc.active_border_id
            WHERE uc.user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $rarity = (string)($stmt->fetchColumn() ?: '');
        if (strtolower($rarity) !== 'premium') {
            return;
        }
        $owned = $conn->prepare("
            SELECT 1
            FROM user_customizations uc
            INNER JOIN user_inventory ui ON ui.user_id = uc.user_id AND ui.item_id = uc.active_border_id
            WHERE uc.user_id = ?
            LIMIT 1
        ");
        $owned->execute([$userId]);
        if ($owned->fetchColumn()) {
            return;
        }
        $conn->prepare("UPDATE user_customizations SET active_border_id = 0 WHERE user_id = ?")
            ->execute([$userId]);
    } catch (Throwable $ignore) {
    }
}
