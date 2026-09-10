<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
session_write_close();

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../poster_helper.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            m.movie_id AS id,
            m.title,
            m.created_at,
            COALESCE((
                SELECT GROUP_CONCAT(g.genre_name SEPARATOR ', ')
                FROM movie_and_genres mag
                JOIN genres g ON mag.genre_id = g.genre_id
                WHERE mag.movie_id = m.movie_id
            ), '') AS genre,
            m.video_url
        FROM watchlists w
        JOIN movies m ON w.movie_id = m.movie_id
        WHERE w.user_id = ?
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $watchlist = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($watchlist as &$item) {
        $item['year'] = !empty($item['created_at']) ? date('Y', strtotime($item['created_at'])) : 'N/A';
        $item['rating'] = 'N/A';
        $item['genre'] = !empty($item['genre']) ? $item['genre'] : 'Movie';
        $item['status'] = 'Saved';
        $item['img'] = moviePosterUrl($item['id']);
        $item['cover_image'] = $item['img'];
    }
    unset($item);

    echo json_encode(['success' => true, 'watchlist' => $watchlist], JSON_UNESCAPED_SLASHES);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
