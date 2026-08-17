\<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    // 1. Fetch movies from watchlist WITHOUT grouping on BLOB
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
            m.poster AS img,
            m.video_url
        FROM watchlists w
        JOIN movies m ON w.movie_id = m.movie_id
        WHERE w.user_id = ?
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $watchlist = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Format data for frontend
    foreach ($watchlist as &$item) {
        $item['year'] = !empty($item['created_at']) ? date('Y', strtotime($item['created_at'])) : "N/A";
        $item['rating'] = "N/A";
        $item['genre'] = !empty($item['genre']) ? $item['genre'] : "Movie";
        $item['status'] = "Saved";

        // Convert BLOB to base64 data URI, or use placeholder
        if (!empty($item['img'])) {
            $item['img'] = 'data:image/jpeg;base64,' . base64_encode($item['img']);
        } else {
            // Use inline SVG placeholder to avoid external service
            $placeholderSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="450" viewBox="0 0 300 450"><rect width="300" height="450" fill="#0d0d12"/><text x="50%" y="50%" fill="#ffffff" font-family="Arial" font-size="20" text-anchor="middle" dominant-baseline="middle">No Poster</text></svg>';
            $item['img'] = 'data:image/svg+xml;base64,' . base64_encode($placeholderSvg);
        }
    }

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'watchlist' => $watchlist], JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>