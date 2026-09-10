<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../poster_helper.php';
require_once __DIR__ . '/../profile_media_helper.php';

header('Content-Type: application/json');

$role = strtolower((string)($_SESSION['user_role'] ?? ''));
if (
    empty($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true ||
    !in_array($role, ['admin', 'moderator'], true)
) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

session_write_close();

try {
    $stmt = $conn->query("
        SELECT
            r.room_id,
            r.room_code,
            r.host_id,
            r.movie_id,
            r.created_at,
            COALESCE(u.user_name, u.email, 'Unknown') AS host_name,
            m.title AS movie_title
        FROM rooms r
        LEFT JOIN users u ON u.user_id = r.host_id
        LEFT JOIN movies m ON m.movie_id = r.movie_id AND r.movie_id > 0
        WHERE r.status = 'active'
        ORDER BY r.created_at DESC
    ");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $roomIds = array_map(static fn($row) => (int)$row['room_id'], $rooms);
    $participantsByRoom = [];
    $mediaRows = [];

    foreach ($rooms as $room) {
        if (!empty($room['host_id'])) {
            $mediaRows[] = ['user_id' => (int)$room['host_id']];
        }
    }

    if ($roomIds) {
        $placeholders = implode(',', array_fill(0, count($roomIds), '?'));
        $participantStmt = $conn->prepare("
            SELECT room_id, user_id, user_name, peer_id, last_seen
            FROM room_participants
            WHERE room_id IN ({$placeholders})
              AND last_seen > DATE_SUB(NOW(), INTERVAL 45 SECOND)
            ORDER BY last_seen DESC
        ");
        $participantStmt->execute($roomIds);
        foreach ($participantStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rid = (int)$row['room_id'];
            $uid = (int)$row['user_id'];
            if (!isset($participantsByRoom[$rid])) {
                $participantsByRoom[$rid] = [];
            }
            if (isset($participantsByRoom[$rid][$uid])) {
                continue;
            }
            $participantsByRoom[$rid][$uid] = $row;
            $mediaRows[] = ['user_id' => $uid];
        }
    }

    $mediaByUser = [];
    foreach (attachProfileMedia($conn, $mediaRows) as $m) {
        $mediaByUser[(int)$m['user_id']] = $m;
    }

    $formatted = array_map(static function ($room) use ($participantsByRoom, $mediaByUser) {
        $roomId = (int)$room['room_id'];
        $hostId = (int)$room['host_id'];
        $movieId = (int)($room['movie_id'] ?? 0);
        $hostMedia = $mediaByUser[$hostId] ?? [];
        $movieTitle = trim((string)($room['movie_title'] ?? ''));
        $hasMovie = $movieId > 0 && $movieTitle !== '';

        $participants = [];
        foreach (array_values($participantsByRoom[$roomId] ?? []) as $p) {
            $uid = (int)$p['user_id'];
            $media = $mediaByUser[$uid] ?? [];
            $name = $p['user_name'] ?: ('User #' . $uid);
            $participants[] = [
                'id' => $uid,
                'user_id' => $uid,
                'name' => $name,
                'avatar_url' => $media['avatar_url'] ?? '',
                'border_preview' => $media['border_preview'] ?? '',
                'isHost' => $uid === $hostId,
            ];
        }

        usort($participants, static function ($a, $b) {
            if ($a['isHost'] === $b['isHost']) {
                return strcasecmp($a['name'], $b['name']);
            }
            return $a['isHost'] ? -1 : 1;
        });

        return [
            'id' => $roomId,
            'name' => 'Room #' . $room['room_code'],
            'room_code' => $room['room_code'],
            'host' => $room['host_name'],
            'host_id' => $hostId,
            'host_avatar_url' => $hostMedia['avatar_url'] ?? '',
            'host_border_preview' => $hostMedia['border_preview'] ?? '',
            'movie_id' => $hasMovie ? $movieId : null,
            'movie_title' => $hasMovie ? $movieTitle : 'No movie selected',
            'movie_poster' => $hasMovie ? moviePosterUrl($movieId) : '',
            'users' => count($participants),
            'participants' => $participants,
            'created_at' => $room['created_at'],
        ];
    }, $rooms);

    echo json_encode(['success' => true, 'rooms' => $formatted]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load active rooms.']);
}
