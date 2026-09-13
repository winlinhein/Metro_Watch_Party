<?php

require_once __DIR__ . '/poster_helper.php';
require_once __DIR__ . '/profile_media_helper.php';
require_once __DIR__ . '/presence_helper.php';
require_once __DIR__ . '/premium_status_helper.php';

function homeMovieSelectSql(): string
{
    return "
        SELECT
            m.movie_id AS id,
            m.title,
            m.description,
            m.view_count,
            m.created_at,
            COALESCE((
                SELECT ROUND(AVG(r.rating), 1)
                FROM movie_rating r
                WHERE r.movie_id = m.movie_id
            ), 0.0) AS rating,
            COALESCE((
                SELECT GROUP_CONCAT(g.genre_name SEPARATOR ', ')
                FROM movie_and_genres mg
                JOIN genres g ON g.genre_id = mg.genre_id
                WHERE mg.movie_id = m.movie_id
            ), '') AS genres
        FROM movies m
    ";
}

function mapHomeMovieRow(array $movie): array
{
    $id = (int)($movie['id'] ?? 0);
    $genres = [];
    if (!empty($movie['genres'])) {
        $genres = array_values(array_filter(array_map('trim', explode(',', (string)$movie['genres']))));
    }
    $poster = moviePosterUrl($id);
    return [
        'id' => $id,
        'movie_id' => $id,
        'title' => (string)($movie['title'] ?? 'Untitled'),
        'description' => (string)($movie['description'] ?? ''),
        'view_count' => (int)($movie['view_count'] ?? 0),
        'created_at' => $movie['created_at'] ?? null,
        'rating' => number_format((float)($movie['rating'] ?? 0), 1),
        'genres' => $genres,
        'genre' => $genres[0] ?? 'Film',
        'img' => $poster,
        'cover_image' => $poster,
    ];
}

function getTrendingMovies(PDO $conn, int $limit = 10): array
{
    $limit = max(1, min(20, $limit));
    $sql = homeMovieSelectSql() . " ORDER BY m.view_count DESC, m.movie_id DESC";
    $stmt = $conn->query($sql);
    $all = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    if (!$all) {
        return [];
    }

    $viewed = [];
    $rest = [];
    foreach ($all as $row) {
        if ((int)($row['view_count'] ?? 0) > 0) {
            $viewed[] = $row;
        } else {
            $rest[] = $row;
        }
    }

    $picked = [];
    $seen = [];
    foreach ($viewed as $row) {
        if (count($picked) >= $limit) {
            break;
        }
        $id = (int)$row['id'];
        $seen[$id] = true;
        $picked[] = mapHomeMovieRow($row);
    }

    if (count($picked) < $limit && $rest) {
        shuffle($rest);
        foreach ($rest as $row) {
            if (count($picked) >= $limit) {
                break;
            }
            $id = (int)$row['id'];
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $picked[] = mapHomeMovieRow($row);
        }
    }

    if (count($picked) < $limit) {
        foreach ($all as $row) {
            if (count($picked) >= $limit) {
                break;
            }
            $id = (int)$row['id'];
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $picked[] = mapHomeMovieRow($row);
        }
    }

    return $picked;
}

function getHomeActiveUsers(PDO $conn, int $previewLimit = 3): array
{
    $previewLimit = max(1, min(8, $previewLimit));
    ensureUserLastSeenColumn($conn);

    $countStmt = $conn->query("
        SELECT
            COUNT(*) AS total,
            SUM(CASE
                WHEN last_seen IS NOT NULL AND last_seen > DATE_SUB(NOW(), INTERVAL 90 SECOND) THEN 1
                ELSE 0
            END) AS online
        FROM users
        WHERE LOWER(COALESCE(status, 'active')) = 'active'
    ");
    $counts = $countStmt ? ($countStmt->fetch(PDO::FETCH_ASSOC) ?: []) : [];
    $total = (int)($counts['total'] ?? 0);
    $online = (int)($counts['online'] ?? 0);

    $stmt = $conn->query("
        SELECT user_id, user_name, avatar_url, last_seen
        FROM users
        WHERE LOWER(COALESCE(status, 'active')) = 'active'
        ORDER BY
            CASE
                WHEN last_seen IS NOT NULL AND last_seen > DATE_SUB(NOW(), INTERVAL 90 SECOND) THEN 0
                ELSE 1
            END,
            last_seen DESC,
            user_id DESC
        LIMIT {$previewLimit}
    ");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $rows = attachProfileMedia($conn, $rows, 'user_id');

    $preview = [];
    foreach ($rows as $row) {
        $uid = (int)($row['user_id'] ?? 0);
        $name = (string)($row['user_name'] ?? 'User');
        $avatar = trim((string)($row['avatar_url'] ?? ''));
        if ($avatar === '') {
            $avatar = fallbackAvatarUrl($name, $uid);
        }
        $preview[] = [
            'user_id' => $uid,
            'name' => $name,
            'avatar_url' => $avatar,
            'border_preview' => (string)($row['border_preview'] ?? ''),
        ];
    }

    return [
        'total' => $total,
        'online' => $online,
        'preview' => $preview,
        'extra' => max(0, $total - count($preview)),
    ];
}

function getHomeFeed(PDO $conn): array
{
    return [
        'success' => true,
        'active_users' => getHomeActiveUsers($conn),
        'trending' => getTrendingMovies($conn, 10),
    ];
}
