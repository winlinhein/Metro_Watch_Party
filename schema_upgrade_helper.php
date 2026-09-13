<?php

function ensureAppSchema(PDO $conn): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $flag = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nexus_app_schema_ok_v6';
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
        "ALTER TABLE users ADD COLUMN google_id VARCHAR(64) NULL DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN auth_provider VARCHAR(20) NOT NULL DEFAULT 'email'",
    ];

    foreach ($alters as $sql) {
        try {
            $conn->exec($sql);
        } catch (Throwable $ignore) {
        }
    }

    try {
        $conn->exec("
            INSERT INTO reports (reporter_id, reported_user_id, type, description, created_at)
            SELECT ba.user_id, ba.user_id, 'appeal', ba.appeal_text, ba.created_at
            FROM ban_appeals ba
            WHERE ba.status = 'pending'
              AND NOT EXISTS (
                  SELECT 1 FROM reports r
                  WHERE r.type = 'appeal'
                    AND r.reporter_id = ba.user_id
                    AND LOWER(IFNULL(r.status, 'pending')) = 'pending'
              )
        ");
    } catch (Throwable $ignore) {
    }

    @touch($flag);
    $ready = true;
}
