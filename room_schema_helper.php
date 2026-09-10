<?php

function ensureRoomParticipantSchema(PDO $conn): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $flag = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'nexus_room_schema_ok_v2';
    if (is_file($flag)) {
        $ready = true;
        return;
    }

    $conn->exec("CREATE TABLE IF NOT EXISTS room_participants (
        room_id INT NOT NULL,
        user_id INT NOT NULL,
        user_name VARCHAR(191) NOT NULL DEFAULT '',
        peer_id VARCHAR(64) NOT NULL,
        last_seen DATETIME NOT NULL,
        forced_muted TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (peer_id),
        KEY room_seen (room_id, last_seen)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    foreach (['forced_muted', 'forced_video_off', 'chat_banned'] as $col) {
        try {
            $conn->exec("ALTER TABLE room_participants ADD COLUMN {$col} TINYINT(1) NOT NULL DEFAULT 0");
        } catch (Throwable $ignore) {
        }
    }

    $conn->exec("CREATE TABLE IF NOT EXISTS room_kicks (
        room_id INT NOT NULL,
        user_id INT NOT NULL,
        kicked_at DATETIME NOT NULL,
        PRIMARY KEY (room_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->exec("CREATE TABLE IF NOT EXISTS room_join_requests (
        id INT NOT NULL AUTO_INCREMENT,
        room_id INT NOT NULL,
        host_id INT NOT NULL,
        requester_id INT NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY room_requester (room_id, requester_id),
        KEY host_status (host_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    @touch($flag);
    $ready = true;
}
