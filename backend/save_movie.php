<?php
session_start();
header('Content-Type: application/json');

// 1. Session & Admin Security Check
if (
    empty($_SESSION['authenticated']) || 
    $_SESSION['authenticated'] !== true || 
    empty($_SESSION['user_role']) || 
    $_SESSION['user_role'] !== 'admin'
) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

require_once __DIR__ . '/../conn.php';

// 2. Parse Incoming Request Data
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid payload.']);
    exit();
}

$movieId     = !empty($data['id']) ? (int)$data['id'] : null;
$title       = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$year        = !empty($data['year']) ? (int)$data['year'] : (int)date('Y');
$videoUrl    = trim($data['video_url'] ?? '');
$genres      = is_array($data['genres'] ?? null) ? $data['genres'] : [];
$posterInput = $data['img'] ?? '';

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Title is required.']);
    exit();
}

// 3. Handle Poster Upload (Strict MIME/Extension Whitelist)
$posterPath = $posterInput;
if (strpos($posterInput, 'data:image/') === 0) {
    if (preg_match('/^data:image\/(png|jpe?g|webp);base64,(.*)$/i', $posterInput, $matches)) {
        $rawExt = strtolower($matches[1]);
        $ext = ($rawExt === 'jpeg') ? 'jpg' : $rawExt;
        
        $imageName = 'poster_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $uploadDir = __DIR__ . '/../uploads/posters/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $decodedData = base64_decode($matches[2]);
        if ($decodedData !== false && file_put_contents($uploadDir . $imageName, $decodedData)) {
            $posterPath = 'uploads/posters/' . $imageName;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Unsupported image format provided.']);
        exit();
    }
}

try {
    $conn->beginTransaction();

    if ($movieId) {
        // --- EDIT MOVIE ---
        $stmt = $conn->prepare("
            UPDATE movies 
            SET title = :title, 
                description = :description, 
                poster = :poster, 
                video_url = :video_url
            WHERE movie_id = :id
        ");
        $stmt->execute([
            ':title'       => $title,
            ':description' => $description,
            ':poster'      => $posterPath,
            ':video_url'   => $videoUrl,
            ':id'          => $movieId
        ]);
    } else {
        // --- ADD NEW MOVIE ---
        $stmt = $conn->prepare("
            INSERT INTO movies (title, description, poster, video_url, created_at, view_count) 
            VALUES (:title, :description, :poster, :video_url, NOW(), 0)
        ");
        $stmt->execute([
            ':title'       => $title,
            ':description' => $description,
            ':poster'      => $posterPath,
            ':video_url'   => $videoUrl
        ]);
        $movieId = (int)$conn->lastInsertId();
    }

    // --- SYNC GENRES ---
    $delStmt = $conn->prepare("DELETE FROM movie_and_genres WHERE movie_id = :movie_id");
    $delStmt->execute([':movie_id' => $movieId]);

    if (!empty($genres)) {
        $genreInsertStmt = $conn->prepare("
            INSERT INTO movie_and_genres (movie_id, genre_id)
            SELECT :movie_id, genre_id FROM genres WHERE genre_name = :genre_name
        ");
        foreach ($genres as $genreName) {
            $genreInsertStmt->execute([
                ':movie_id'   => $movieId,
                ':genre_name' => trim($genreName)
            ]);
        }
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Movie saved successfully.',
        'movie'   => [
            'id'     => $movieId,
            'poster' => $posterPath
        ]
    ]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}