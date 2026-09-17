<?php

function ensureAppSchema(PDO $conn): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $flag = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nexus_app_schema_ok_v9';
    if (is_file($flag)) {
        $ready = true;
        return;
    }

    $alters = [
        "ALTER TABLE users ADD COLUMN deletion_requested_at DATETIME NULL DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN ban_reason VARCHAR(500) NULL DEFAULT NULL",
        "ALTER TABLE movies ADD COLUMN is_premium TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE rooms ADD COLUMN max_members INT NOT NULL DEFAULT 5",
        "ALTER TABLE users MODIFY COLUMN status VARCHAR(32) NOT NULL DEFAULT 'active'",
        "ALTER TABLE reports MODIFY COLUMN type VARCHAR(32) NOT NULL",
        "ALTER TABLE reports MODIFY COLUMN status ENUM('pending','read','resolved','cancelled') NULL DEFAULT 'pending'",
        "ALTER TABLE users ADD COLUMN google_id VARCHAR(64) NULL DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN auth_provider VARCHAR(20) NOT NULL DEFAULT 'email'",
        "ALTER TABLE rooms MODIFY COLUMN movie_id INT NULL DEFAULT NULL",
        "ALTER TABLE notifications ADD COLUMN room_id INT NULL DEFAULT NULL",
        "ALTER TABLE notifications ADD COLUMN request_id INT NULL DEFAULT NULL",
        "ALTER TABLE room_messages ADD COLUMN message_type VARCHAR(20) NOT NULL DEFAULT 'text'",
        "ALTER TABLE room_messages ADD COLUMN image_url VARCHAR(500) NULL DEFAULT NULL",
    ];

    foreach ($alters as $sql) {
        nexusTryExec($conn, $sql);
    }

    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS plans (
                plan_id INT NOT NULL AUTO_INCREMENT,
                name VARCHAR(100) NOT NULL,
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                duration_days INT NOT NULL DEFAULT 30,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (plan_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $conn->exec("
            INSERT INTO plans (plan_id, name, price, duration_days, is_active)
            VALUES (1, 'Nexus Premium', 4.99, 30, 1)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                price = VALUES(price),
                duration_days = VALUES(duration_days),
                is_active = VALUES(is_active)
        ");
    } catch (Throwable $ignore) {
    }

    try {
        $conn->exec("UPDATE rooms SET movie_id = NULL WHERE movie_id IS NOT NULL AND movie_id <= 0");
    } catch (Throwable $ignore) {
    }

    try {
        $conn->exec("
            INSERT INTO reports (reporter_id, reported_user_id, type, description, created_at, status)
            SELECT
                ba.user_id,
                ba.user_id,
                'appeal',
                ba.appeal_text,
                ba.created_at,
                CASE LOWER(IFNULL(ba.status, 'pending'))
                    WHEN 'approved' THEN 'resolved'
                    WHEN 'rejected' THEN 'cancelled'
                    ELSE 'pending'
                END
            FROM ban_appeals ba
            WHERE NOT EXISTS (
                SELECT 1 FROM reports r
                WHERE r.type = 'appeal'
                  AND r.reporter_id = ba.user_id
                  AND IFNULL(r.description, '') = IFNULL(ba.appeal_text, '')
            )
        ");
    } catch (Throwable $ignore) {
    }

    nexusMigratePackedNotificationMessages($conn);
    nexusMigratePackedRoomChatMessages($conn);

    foreach ([
        "ALTER TABLE room_join_requests DROP INDEX host_status",
        "ALTER TABLE room_join_requests DROP COLUMN host_id",
        "ALTER TABLE room_participants DROP COLUMN user_name",
        "ALTER TABLE room_messages DROP COLUMN guest_nickname",
        "ALTER TABLE user_customizations DROP COLUMN app_theme",
        "ALTER TABLE user_customizations DROP COLUMN chat_color",
        "DROP TABLE IF EXISTS room_members",
        "DROP TABLE IF EXISTS ban_appeals",
    ] as $sql) {
        nexusTryExec($conn, $sql);
    }

    @touch($flag);
    $ready = true;
}

function nexusTryExec(PDO $conn, string $sql): void
{
    try {
        $conn->exec($sql);
    } catch (Throwable $ignore) {
    }
}

function nexusPremiumPlan(PDO $conn): array
{
    ensureAppSchema($conn);
    $fallback = [
        'plan_id' => defined('PREMIUM_PLAN_ID') ? (int)PREMIUM_PLAN_ID : 1,
        'name' => 'Nexus Premium',
        'price' => 4.99,
        'duration_days' => defined('PREMIUM_DURATION_DAYS') ? (int)PREMIUM_DURATION_DAYS : 30,
    ];
    try {
        $stmt = $conn->prepare("
            SELECT plan_id, name, price, duration_days
            FROM plans
            WHERE is_active = 1
            ORDER BY CASE WHEN plan_id = ? THEN 0 ELSE 1 END, plan_id ASC
            LIMIT 1
        ");
        $stmt->execute([$fallback['plan_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return [
                'plan_id' => (int)$row['plan_id'],
                'name' => (string)$row['name'],
                'price' => (float)$row['price'],
                'duration_days' => (int)$row['duration_days'],
            ];
        }
    } catch (Throwable $ignore) {
    }
    return $fallback;
}

function nexusMigratePackedNotificationMessages(PDO $conn): void
{
    try {
        $stmt = $conn->query("
            SELECT id, message
            FROM notifications
            WHERE message LIKE '%|room:%'
            LIMIT 5000
        ");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $ignore) {
        return;
    }
    if (!$rows) {
        return;
    }

    $upd = $conn->prepare("
        UPDATE notifications
        SET room_id = ?, request_id = ?, message = ?
        WHERE id = ?
    ");
    foreach ($rows as $row) {
        $message = (string)($row['message'] ?? '');
        $roomId = null;
        $requestId = null;
        if (preg_match('/\|room:(\d+)/', $message, $m)) {
            $roomId = (int)$m[1];
        }
        if (preg_match('/\|req:(\d+)/', $message, $m)) {
            $requestId = (int)$m[1];
        }
        $clean = trim(preg_replace('/\|room:\d+(\|req:\d+)?\s*$/', '', $message) ?? $message);
        try {
            $upd->execute([$roomId, $requestId, $clean, (int)$row['id']]);
        } catch (Throwable $ignore) {
        }
    }
}

function nexusMigratePackedRoomChatMessages(PDO $conn): void
{
    try {
        $stmt = $conn->query("
            SELECT message_id, message_text
            FROM room_messages
            WHERE message_text LIKE '__IMG__%'
            LIMIT 5000
        ");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $ignore) {
        return;
    }
    if (!$rows) {
        return;
    }

    $upd = $conn->prepare("
        UPDATE room_messages
        SET message_type = ?, image_url = ?, message_text = ?
        WHERE message_id = ?
    ");
    foreach ($rows as $row) {
        $raw = (string)($row['message_text'] ?? '');
        $prefix = '__IMG__';
        if (!str_starts_with($raw, $prefix)) {
            continue;
        }
        $rest = substr($raw, strlen($prefix));
        $nl = strpos($rest, "\n");
        if ($nl === false) {
            $imageUrl = $rest;
            $text = '';
        } else {
            $imageUrl = substr($rest, 0, $nl);
            $text = substr($rest, $nl + 1);
        }
        try {
            $upd->execute(['image', $imageUrl, $text, (int)$row['message_id']]);
        } catch (Throwable $ignore) {
        }
    }
}
