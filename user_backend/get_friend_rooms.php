<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
session_write_close();

try {
    require_once __DIR__ . '/../conn.php';
    require_once __DIR__ . '/../poster_helper.php';
    require_once __DIR__ . '/../profile_media_helper.php';
    require_once __DIR__ . '/../room_schema_helper.php';
    ensureRoomParticipantSchema($conn);

    $stmt = $conn->prepare("
        SELECT
            r.room_id,
            r.room_code,
            r.host_id,
            r.movie_id,
            r.created_at,
            COALESCE(u.user_name, u.email, 'Unknown') AS host_name,
            m.title AS movie_title,
            (
                SELECT COUNT(DISTINCT rp.user_id)
                FROM room_participants rp
                WHERE rp.room_id = r.room_id
                  AND rp.last_seen > DATE_SUB(NOW(), INTERVAL 45 SECOND)
            ) AS members,
            EXISTS (
                SELECT 1 FROM room_participants rp2
                WHERE rp2.room_id = r.room_id
                  AND rp2.user_id = :me_in
                  AND rp2.last_seen > DATE_SUB(NOW(), INTERVAL 45 SECOND)
            ) AS in_room,
            (
                SELECT rj.status
                FROM room_join_requests rj
                WHERE rj.room_id = r.room_id AND rj.requester_id = :me_req
                ORDER BY rj.id DESC
                LIMIT 1
            ) AS request_status,
            (
                SELECT g.genre_name
                FROM movie_and_genres mag
                JOIN genres g ON g.genre_id = mag.genre_id
                WHERE mag.movie_id = r.movie_id
                ORDER BY g.genre_name ASC
                LIMIT 1
            ) AS genre_name
        FROM rooms r
        JOIN users u ON u.user_id = r.host_id
        LEFT JOIN movies m ON m.movie_id = r.movie_id
        WHERE r.status = 'active'
          AND r.host_id <> :me
          AND EXISTS (
              SELECT 1 FROM user_friends uf
              WHERE uf.status = 'accepted'
                AND (
                    (uf.user_id_1 = :me2 AND uf.user_id_2 = r.host_id)
                    OR (uf.user_id_2 = :me3 AND uf.user_id_1 = r.host_id)
                )
          )
          AND EXISTS (
              SELECT 1 FROM room_participants rp
              WHERE rp.room_id = r.room_id
                AND rp.user_id = r.host_id
                AND rp.last_seen > DATE_SUB(NOW(), INTERVAL 45 SECOND)
          )
        ORDER BY r.created_at DESC
        LIMIT 40
    ");
    $stmt->execute([
        'me' => $userId,
        'me2' => $userId,
        'me3' => $userId,
        'me_in' => $userId,
        'me_req' => $userId,
    ]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hostRows = [];
    foreach ($rooms as $room) {
        $hostRows[] = ['user_id' => (int)$room['host_id']];
    }
    $hostMediaById = [];
    if ($hostRows) {
        foreach (attachProfileMedia($conn, $hostRows) as $row) {
            $hostMediaById[(int)$row['user_id']] = $row;
        }
    }

    $defaultImg = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&q=80&w=400&h=200';
    $parties = [];

    foreach ($rooms as $room) {
        $members = (int)($room['members'] ?? 0);
        if ($members < 1) {
            continue;
        }

        $movieId = (int)($room['movie_id'] ?? 0);
        $title = trim((string)($room['movie_title'] ?? ''));
        $hasMovie = $movieId > 0 && $title !== '';
        $genre = $hasMovie
            ? strtoupper((string)($room['genre_name'] ?: 'LIVE'))
            : 'ORIGINAL';
        $hostId = (int)$room['host_id'];
        $hostMedia = $hostMediaById[$hostId] ?? [];

        $parties[] = [
            'room_id' => (int)$room['room_id'],
            'room_code' => $room['room_code'],
            'title' => $hasMovie ? $title : 'Original',
            'genre' => $genre,
            'host' => $room['host_name'],
            'host_id' => $hostId,
            'host_avatar' => $hostMedia['avatar_url'] ?? '',
            'host_border' => $hostMedia['border_preview'] ?? '',
            'members' => $members,
            'img' => $hasMovie ? moviePosterUrl($movieId) : $defaultImg,
            'time' => 'LIVE',
            'has_movie' => $hasMovie,
            'in_room' => !empty($room['in_room']),
            'request_status' => $room['request_status'] ?: null,
        ];
    }

    echo json_encode(['success' => true, 'rooms' => $parties]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load stream rooms']);
}
