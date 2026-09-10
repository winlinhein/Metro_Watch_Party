<?php
header('Content-Type: application/json');

session_start();
$current_user_id = $_SESSION['user_id'] ?? 0;
session_write_close();

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../poster_helper.php';

try {
    $query = "
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
            COALESCE(ROUND(AVG(r.rating), 1), 0.0) AS rating,
            COALESCE(MAX(ur.rating), 0) AS user_rating,
            COALESCE(GROUP_CONCAT(DISTINCT g.genre_name SEPARATOR ', '), '') AS genres
        FROM movies m
        LEFT JOIN movie_rating r ON m.movie_id = r.movie_id
        LEFT JOIN movie_rating ur ON m.movie_id = ur.movie_id AND ur.user_id = :user_id
        LEFT JOIN movie_and_genres mg ON m.movie_id = mg.movie_id
        LEFT JOIN genres g ON mg.genre_id = g.genre_id
        GROUP BY m.movie_id
        ORDER BY m.created_at DESC
    ";

    $stmt = $conn->prepare($query);
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
