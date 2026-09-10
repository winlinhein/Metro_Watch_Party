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

    $stmt = $conn->prepare("
        SELECT
            r.room_id,
            r.room_code,
            r.host_id,
            r.movie_id,
            r.created_at,
            COALESCE(u.user_name, u.email, 'Unknown') AS host_name
        FROM rooms r
        JOIN users u ON u.user_id = r.host_id
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
    $stmt->execute(['me' => $userId, 'me2' => $userId, 'me3' => $userId]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $defaultImg = 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?auto=format&fit=crop&q=80&w=400&h=200';
    $parties = [];

    foreach ($rooms as $room) {
        $roomId = (int)$room['room_id'];
        $hostId = (int)$room['host_id'];
        $movieId = (int)($room['movie_id'] ?? 0);

        $members = 0;
        try {
            $countStmt = $conn->prepare("
                SELECT COUNT(DISTINCT user_id)
                FROM room_participants
                WHERE room_id = :room_id
                  AND last_seen > DATE_SUB(NOW(), INTERVAL 45 SECOND)
            ");
            $countStmt->execute(['room_id' => $roomId]);
            $members = (int)$countStmt->fetchColumn();
        } catch (Throwable $ignore) {
            continue;
        }
        if ($members < 1) {
            continue;
        }

        $inRoom = false;
        try {
            $inStmt = $conn->prepare("
                SELECT 1 FROM room_participants
                WHERE room_id = :room_id AND user_id = :user_id
                  AND last_seen > DATE_SUB(NOW(), INTERVAL 45 SECOND)
                LIMIT 1
            ");
            $inStmt->execute(['room_id' => $roomId, 'user_id' => $userId]);
            $inRoom = (bool)$inStmt->fetchColumn();
        } catch (Throwable $ignore) {}

        $requestStatus = null;
        try {
            $reqStmt = $conn->prepare("
                SELECT status FROM room_join_requests
                WHERE room_id = :room_id AND requester_id = :user_id
                ORDER BY id DESC LIMIT 1
            ");
            $reqStmt->execute(['room_id' => $roomId, 'user_id' => $userId]);
            $requestStatus = $reqStmt->fetchColumn() ?: null;
        } catch (Throwable $ignore) {}

        $title = 'Original';
        $genre = 'ORIGINAL';
        $img = $defaultImg;
        if ($movieId > 0) {
            $movieStmt = $conn->prepare("SELECT title FROM movies WHERE movie_id = :id LIMIT 1");
            $movieStmt->execute(['id' => $movieId]);
            $movieTitle = $movieStmt->fetchColumn();
            if ($movieTitle) {
                $title = (string)$movieTitle;
                $genre = 'LIVE';
                $img = moviePosterUrl($movieId);
                try {
                    $genreStmt = $conn->prepare("
                        SELECT g.genre_name
                        FROM movie_and_genres mag
                        JOIN genres g ON g.genre_id = mag.genre_id
                        WHERE mag.movie_id = :id
                        ORDER BY g.genre_name ASC
                        LIMIT 1
                    ");
                    $genreStmt->execute(['id' => $movieId]);
                    $genreName = $genreStmt->fetchColumn();
                    if ($genreName) {
                        $genre = strtoupper((string)$genreName);
                    }
                } catch (Throwable $ignore) {}
            }
        }

        $hostMedia = getUserProfileMedia($conn, $hostId);

        $parties[] = [
            'room_id' => $roomId,
            'room_code' => $room['room_code'],
            'title' => $title,
            'genre' => $genre,
            'host' => $room['host_name'],
            'host_id' => $hostId,
            'host_avatar' => $hostMedia['avatar_url'] ?? '',
            'host_border' => $hostMedia['border_preview'] ?? '',
            'members' => $members,
            'img' => $img,
            'time' => 'LIVE',
            'has_movie' => $movieId > 0 && $title !== 'Original',
            'in_room' => $inRoom,
            'request_status' => $requestStatus,
        ];
    }

    echo json_encode(['success' => true, 'rooms' => $parties]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load stream rooms']);
}
