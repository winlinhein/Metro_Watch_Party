<?php
header('Content-Type: application/json');
header('Cache-Control: private, max-age=20, stale-while-revalidate=60');

session_start();
$current_user_id = (int)($_SESSION['user_id'] ?? 0);
session_write_close();

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../poster_helper.php';
require_once __DIR__ . '/../schema_upgrade_helper.php';
require_once __DIR__ . '/../premium_benefits_helper.php';

ensureAppSchema($conn);

try {
    $stmt = $conn->prepare("
        SELECT
            m.movie_id AS id,
            m.title,
            m.description,
            m.video_url,
            m.video_url AS trailer,
            m.actual_video_url,
            m.duration,
            m.view_count,
            m.created_at,
            COALESCE(m.is_premium, 0) AS is_premium,
            COALESCE(ROUND(AVG(r.rating), 1), 0) AS rating,
            COALESCE(MAX(CASE WHEN ur.user_id = ? THEN ur.rating ELSE 0 END), 0) AS user_rating,
            COALESCE(GROUP_CONCAT(DISTINCT g.genre_name SEPARATOR ', '), '') AS genres
        FROM movies m
        LEFT JOIN movie_rating r ON r.movie_id = m.movie_id
        LEFT JOIN movie_rating ur ON ur.movie_id = m.movie_id AND ur.user_id = ?
        LEFT JOIN movie_and_genres mg ON mg.movie_id = m.movie_id
        LEFT JOIN genres g ON g.genre_id = mg.genre_id
        GROUP BY m.movie_id
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$current_user_id, $current_user_id]);
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($movies as &$movie) {
        $movie['rating'] = number_format((float)$movie['rating'], 1);
        $movie['user_rating'] = (int)$movie['user_rating'];
        $movie['genres'] = $movie['genres'] ? explode(', ', $movie['genres']) : [];
        $movie['comments'] = [];
        $movie['is_premium'] = (int)($movie['is_premium'] ?? 0);
        $movie['duration'] = (int)($movie['duration'] ?? 0);
        $posterUrl = moviePosterUrl($movie['id']);
        $movie['img'] = $posterUrl;
        $movie['cover_image'] = $posterUrl;
    }
    unset($movie);

    echo json_encode($movies);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
