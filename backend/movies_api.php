<?php
session_start();
if (empty($_SESSION['authenticated']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../conn.php';

// Session authentication check
if (
    empty($_SESSION['authenticated']) || 
    $_SESSION['authenticated'] !== true || 
    empty($_SESSION['user_role']) || 
    $_SESSION['user_role'] !== 'admin'
) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied: Admin permissions required']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// -------------------------------------------------------------
// GET: Fetch all movies along with ratings and combined genres
// -------------------------------------------------------------
if ($method === 'GET') {
    try {
        $sql = "
            SELECT 
                m.movie_id AS id,
                m.title,
                m.description,
                m.poster AS img,
                m.video_url AS trailer,
                m.duration,
                m.view_count,
                m.created_at,
                COALESCE(ROUND(AVG(r.rating), 1), 0) AS rating,
                COALESCE(GROUP_CONCAT(DISTINCT g.genre_name SEPARATOR ', '), '') AS genre,
                COALESCE(GROUP_CONCAT(DISTINCT g.genre_id), '') AS genre_ids
            FROM movies m
            LEFT JOIN movie_and_genres mg ON m.movie_id = mg.movie_id
            LEFT JOIN genres g ON mg.genre_id = g.genre_id
            LEFT JOIN movie_rating r ON m.movie_id = r.movie_id
            GROUP BY m.movie_id
            ORDER BY m.movie_id DESC
        ";

        $stmt = $conn->query($sql);
        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format genre_ids as array of numbers
        foreach ($movies as &$movie) {
            $movie['genre_ids'] = $movie['genre_ids'] ? array_map('intval', explode(',', $movie['genre_ids'])) : [];
            $movie['duration'] = (int) $movie['duration'];
            $movie['view_count'] = (int) $movie['view_count'];
        }

        echo json_encode($movies);
        exit();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
        exit();
    }
}

// -------------------------------------------------------------
// POST: Create or Update a movie + assign genres
// -------------------------------------------------------------
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data payload']);
        exit();
    }

    $movieId     = $input['id'] ?? null;
    $title       = trim($input['title'] ?? '');
    $description = trim($input['description'] ?? '');
    $poster      = trim($input['img'] ?? '');
    $videoUrl    = trim($input['trailer'] ?? '');
    $duration    = !empty($input['duration']) ? (int)$input['duration'] : null;
    $genreIds    = $input['genre_ids'] ?? []; 

    if (empty($title) || empty($description) || empty($poster)) {
        http_response_code(400);
        echo json_encode(['error' => 'Title, description, and poster URL are required fields.']);
        exit();
    }

    try {
        $conn->beginTransaction();

        if ($movieId) {
            // Update existing movie
            $stmt = $conn->prepare("
                UPDATE movies 
                SET title = ?, description = ?, poster = ?, video_url = ?, duration = ? 
                WHERE movie_id = ?
            ");
            $stmt->execute([$title, $description, $poster, $videoUrl, $duration, $movieId]);
        } else {
            // Insert new movie
            $stmt = $conn->prepare("
                INSERT INTO movies (title, description, poster, video_url, duration, view_count) 
                VALUES (?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$title, $description, $poster, $videoUrl, $duration]);
            $movieId = $conn->lastInsertId();
        }

        // Sync genres in movie_and_genres pivot table
        $stmtDelete = $conn->prepare("DELETE FROM movie_and_genres WHERE movie_id = ?");
        $stmtDelete->execute([$movieId]);

        if (!empty($genreIds) && is_array($genreIds)) {
            $stmtGenre = $conn->prepare("INSERT INTO movie_and_genres (movie_id, genre_id) VALUES (?, ?)");
            foreach ($genreIds as $gId) {
                $stmtGenre->execute([$movieId, $gId]);
            }
        }

        $conn->commit();
        echo json_encode(['success' => true, 'movie_id' => $movieId]);
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save movie: ' . $e->getMessage()]);
        exit();
    }
}
?>