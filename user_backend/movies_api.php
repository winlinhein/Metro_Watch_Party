<?php
header('Content-Type: application/json');
header('Cache-Control: private, max-age=20, stale-while-revalidate=60');

session_start();
$current_user_id = (int)($_SESSION['user_id'] ?? 0);
session_write_close();

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../poster_helper.php';

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
            COALESCE((
                SELECT ROUND(AVG(r.rating), 1)
                FROM movie_rating r
                WHERE r.movie_id = m.movie_id
            ), 0.0) AS rating,
            COALESCE((
                SELECT ur.rating
                FROM movie_rating ur
                WHERE ur.movie_id = m.movie_id AND ur.user_id = :user_id
                LIMIT 1
            ), 0) AS user_rating,
            COALESCE((
                SELECT GROUP_CONCAT(g.genre_name SEPARATOR ', ')
                FROM movie_and_genres mg
                JOIN genres g ON g.genre_id = mg.genre_id
                WHERE mg.movie_id = m.movie_id
            ), '') AS genres
        FROM movies m
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([':user_id' => $current_user_id]);
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($movies as &$movie) {
        $movie['rating'] = number_format((float)$movie['rating'], 1);
        $movie['user_rating'] = (int)$movie['user_rating'];
        $movie['genres'] = $movie['genres'] ? explode(', ', $movie['genres']) : [];
        $movie['comments'] = [];
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
