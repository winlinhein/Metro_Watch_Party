<?php
// Set headers to return JSON
header('Content-Type: application/json');

// Include your database connection path
require_once __DIR__ . '/../conn.php';

try {
    // Query matching schema: movies, movie_rating, genres, and movie_and_genres
    $query = "
        SELECT 
            m.movie_id AS id,
            m.title,
            m.description,
            m.poster AS cover_image,
            m.poster AS img,             
            m.video_url,
            m.video_url AS trailer,       
            m.duration,
            m.view_count,
            m.created_at,
            COALESCE(ROUND(AVG(r.rating), 1), 0.0) AS rating,
            COALESCE(GROUP_CONCAT(DISTINCT g.genre_name SEPARATOR ', '), '') AS genres
        FROM movies m
        LEFT JOIN movie_rating r ON m.movie_id = r.movie_id
        LEFT JOIN movie_and_genres mg ON m.movie_id = mg.movie_id
        LEFT JOIN genres g ON mg.genre_id = g.genre_id
        GROUP BY m.movie_id
        ORDER BY m.created_at DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format types and fetch comments per movie
    foreach ($movies as &$movie) {
        $movie['rating'] = number_format((float)$movie['rating'], 1);
        $movie['genres'] = $movie['genres'] ? explode(', ', $movie['genres']) : [];

        // Fetch comments for this specific movie
        $commentQuery = "
            SELECT 
                comment_id,
                user_id,
                parent_comment_id,
                comment_text,
                created_at
            FROM movie_comments
            WHERE movie_id = :movie_id
            ORDER BY created_at ASC
        ";
        $commentStmt = $conn->prepare($commentQuery);
        $commentStmt->execute([':movie_id' => $movie['id']]);
        $movie['comments'] = $commentStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($movies);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>