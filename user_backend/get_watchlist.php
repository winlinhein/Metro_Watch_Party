<?php
session_start();
header('Content-Type: application/json');

// Include your DB connection securely
require_once __DIR__ . '/../conn.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch the watchlist joined with the movies, movie_and_genres, and genres tables
    $stmt = $conn->prepare("
        SELECT 
            m.movie_id AS id, 
            m.title, 
            m.created_at, 
            GROUP_CONCAT(g.genre_name SEPARATOR ', ') AS genre, 
            m.poster AS img, 
            m.video_url
        FROM watchlists w
        JOIN movies m ON w.movie_id = m.movie_id
        LEFT JOIN movie_and_genres mag ON m.movie_id = mag.movie_id
        LEFT JOIN genres g ON mag.genre_id = g.genre_id
        WHERE w.user_id = ?
        GROUP BY 
            m.movie_id, 
            m.title, 
            m.created_at, 
            m.poster, 
            m.video_url, 
            w.added_at
        ORDER BY w.added_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $watchlist = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the data so it seamlessly plugs into your Alpine.js UI expectations
   foreach ($watchlist as &$item) {
        // Extract just the year from the timestamp
        $item['year'] = !empty($item['created_at']) ? date('Y', strtotime($item['created_at'])) : "N/A";
        
        // Default rating if not in query
        $item['rating'] = "N/A";
        
        // Ensure genre is not null
        $item['genre'] = !empty($item['genre']) ? $item['genre'] : "Movie";
        
        // Add static status
        $item['status'] = "Saved";
        
        // Handle image: if raw BLOB, convert to base64; otherwise use placeholder
        if (!empty($item['img'])) {
            // It's a BLOB from DB, convert to data URI
            $item['img'] = 'data:image/jpeg;base64,' . base64_encode($item['img']);
        } else {
            // No image, use placeholder URL (do NOT encode)
            $item['img'] = "https://via.placeholder.com/300x450/0d0d12/ffffff?text=No+Poster";
        }
    }

    // Return the formatted array to Alpine.js
    echo json_encode(['success' => true, 'watchlist' => $watchlist]);

} catch (PDOException $e) {
    // Catch and return any database errors gracefully
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>