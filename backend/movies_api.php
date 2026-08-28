<?php
session_start();
if (empty($_SESSION['authenticated']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../poster_helper.php';

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
    session_write_close();
    try {
        $sql = "
            SELECT 
                m.movie_id AS id,
                m.title,
                m.description,
                m.video_url AS trailer,
                m.actual_video_url,
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

        foreach ($movies as &$movie) {
            $movie['genre_ids'] = $movie['genre_ids'] ? array_map('intval', explode(',', $movie['genre_ids'])) : [];
            $movie['duration'] = (int) $movie['duration'];
            $movie['view_count'] = (int) $movie['view_count'];
            $movie['img'] = moviePosterUrl($movie['id']);
            $movie['cover_image'] = $movie['img'];
        }
        unset($movie);

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
    // Handle delete action first
    if (($_POST['action'] ?? '') === 'delete') {
        $movieId = intval($_POST['id'] ?? 0);
        if (!$movieId) {
            http_response_code(400);
            echo json_encode(['error' => 'Movie ID required']);
            exit();
        }
        try {
            $conn->beginTransaction();
            
            $stmt = $conn->prepare("DELETE FROM movies WHERE movie_id = ?");
            $stmt->execute([$movieId]);
            $stmtDel = $conn->prepare("DELETE FROM movie_and_genres WHERE movie_id = ?");
            $stmtDel->execute([$movieId]);
            
            $conn->commit();
            invalidateMoviePosterCache($movieId);
            
            require_once __DIR__ . '/../pusher_helper.php';
            triggerPusherEvent('movie-updates', 'movie_changed', [
                'action' => 'delete',
                'movie_id' => $movieId
            ]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Delete failed: ' . $e->getMessage()]);
        }
        exit();
    }

    // Create / Update handling
    $movieId        = $_POST['id'] ?? null;
    $isUpdate       = !empty($movieId);          // remember original state
    $title          = trim($_POST['title'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $videoUrl       = trim($_POST['trailer'] ?? '');
    $actualVideoUrl = trim($_POST['actual_video_url'] ?? '');
    $duration       = isset($_POST['duration']) ? (int)$_POST['duration'] : null;

    // Parse genre_ids if sent as JSON string or array
    $genreIds = [];
    if (!empty($_POST['genre_ids'])) {
        $genreIds = is_array($_POST['genre_ids']) ? $_POST['genre_ids'] : json_decode($_POST['genre_ids'], true);
    }

    // Read poster file bytes if uploaded – accept both possible field names
    $posterBytes = null;
    if (isset($_FILES['poster_image']) && $_FILES['poster_image']['error'] === UPLOAD_ERR_OK) {
        $posterBytes = file_get_contents($_FILES['poster_image']['tmp_name']);
    } elseif (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $posterBytes = file_get_contents($_FILES['poster']['tmp_name']);
    }

    // Validation: Poster is required for new entries
    if (empty($title) || empty($description) || (!$isUpdate && !$posterBytes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Title, description, and poster file are required.']);
        exit();
    }

    try {
        $conn->beginTransaction();

        if ($isUpdate) {
            // Update existing movie (only update poster BLOB if a new file was uploaded)
            if ($posterBytes !== null) {
                $stmt = $conn->prepare("
                    UPDATE movies SET title = ?, description = ?, poster = ?, video_url = ?, actual_video_url = ?, duration = ? WHERE movie_id = ?
                ");
                $stmt->execute([$title, $description, $posterBytes, $videoUrl, $actualVideoUrl, $duration, $movieId]);
            } else {
                $stmt = $conn->prepare("
                    UPDATE movies SET title = ?, description = ?, video_url = ?, actual_video_url = ?, duration = ? WHERE movie_id = ?
                ");
                $stmt->execute([$title, $description, $videoUrl, $actualVideoUrl, $duration, $movieId]);
            }
        } else {
            // Insert new movie with uploaded poster bytes
            $stmt = $conn->prepare("
                INSERT INTO movies (title, description, poster, video_url, actual_video_url, duration, view_count) VALUES (?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([$title, $description, $posterBytes, $videoUrl, $actualVideoUrl, $duration]);
            $movieId = $conn->lastInsertId();
        }
        
        // Sync genres in movie_and_genres pivot table
        $stmtDelete = $conn->prepare("DELETE FROM movie_and_genres WHERE movie_id = ?");
        $stmtDelete->execute([$movieId]);

        if (!empty($genreIds) && is_array($genreIds)) {
            $stmtGenre = $conn->prepare("INSERT INTO movie_and_genres (movie_id, genre_id) VALUES (?, ?)");
            foreach ($genreIds as $gId) {
                $stmtGenre->execute([$movieId, (int)$gId]);
            }
        }

        $conn->commit();

        // Fetch the updated/inserted movie with all computed fields
        $stmt = $conn->prepare("
            SELECT 
                m.movie_id AS id,
                m.title,
                m.description,
                m.video_url AS trailer,
                m.actual_video_url,
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
            WHERE m.movie_id = ?
            GROUP BY m.movie_id
        ");
        $stmt->execute([$movieId]);
        $movie = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($movie) {
            $movie['genre_ids'] = $movie['genre_ids'] ? array_map('intval', explode(',', $movie['genre_ids'])) : [];
            $movie['duration'] = (int)$movie['duration'];
            $movie['view_count'] = (int)$movie['view_count'];
            $movie['img'] = moviePosterUrl($movie['id']);
            $movie['cover_image'] = $movie['img'];
        }

        if ($posterBytes !== null) {
            invalidateMoviePosterCache($movieId);
        }

        echo json_encode(['success' => true, 'movie' => $movie]);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        require_once __DIR__ . '/../pusher_helper.php';
        triggerPusherEvent('movie-updates', 'movie_changed', [
            'action' => $isUpdate ? 'update' : 'create',
            'movie_id' => $movieId
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save movie: ' . $e->getMessage()]);
    }
    exit();
}