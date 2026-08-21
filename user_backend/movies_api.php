<?php
// Set headers to return JSON
header('Content-Type: application/json');

// 1. Get the current logged-in user's ID. 
// (Ensure session_start() is called if your auth relies on standard PHP sessions)
session_start(); 
$current_user_id = $_SESSION['user_id'] ?? 0; // Default to 0 if the user is a guest

// Include your database connection path
require_once __DIR__ . '/../conn.php';

try {
    // 2. Query matching schema: added the 'ur' join and user_rating field
    $query = "
        SELECT 
            m.movie_id AS id,
            m.title,
            m.description,
            m.poster AS cover_image,
            m.poster AS img,             
            m.video_url,
            m.video_url AS trailer,
            m.actual_video_url,       
            m.duration,
            m.view_count,
            m.created_at,
            COALESCE(ROUND(AVG(r.rating), 1), 0.0) AS rating,
            COALESCE(MAX(ur.rating), 0) AS user_rating, -- NEW: Retrieves the current user's personal rating
            COALESCE(GROUP_CONCAT(DISTINCT g.genre_name SEPARATOR ', '), '') AS genres
        FROM movies m
        LEFT JOIN movie_rating r ON m.movie_id = r.movie_id
        LEFT JOIN movie_rating ur ON m.movie_id = ur.movie_id AND ur.user_id = :user_id -- NEW: Join isolated to this user
        LEFT JOIN movie_and_genres mg ON m.movie_id = mg.movie_id
        LEFT JOIN genres g ON mg.genre_id = g.genre_id
        GROUP BY m.movie_id
        ORDER BY m.created_at DESC
    ";

    $stmt = $conn->prepare($query);
    
    // 3. Bind the user_id parameter to the query
    $stmt->execute([':user_id' => $current_user_id]);
    
    $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format types and fetch comments per movie
    foreach ($movies as &$movie) {
        $movie['rating'] = number_format((float)$movie['rating'], 1);
        
        // 4. Force user_rating to be a clean integer for Alpine JS
        $movie['user_rating'] = (int)$movie['user_rating'];
        
        $movie['genres'] = $movie['genres'] ? explode(', ', $movie['genres']) : [];
        
        // Comments are loaded dynamically when the modal opens via get_comments.php
        $movie['comments'] = [];

        // Convert raw BLOB poster to base64 data URI
        if (!empty($movie['img'])) {
            $movie['img'] = 'data:image/jpeg;base64,' . base64_encode($movie['img']);
        }
        if (!empty($movie['cover_image'])) {
            $movie['cover_image'] = 'data:image/jpeg;base64,' . base64_encode($movie['cover_image']);
        }
    }

    echo json_encode($movies);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>