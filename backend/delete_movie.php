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

// 2. Extract Movie ID from Query String or Request Body
$movieId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$movieId) {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!empty($data['id'])) {
        $movieId = (int)$data['id'];
    }
}

if (!$movieId) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing Movie ID.']);
    exit();
}

try {
    $conn->beginTransaction();

    // 3. Fetch Poster Path prior to deletion
    $stmt = $conn->prepare("SELECT poster FROM movies WHERE movie_id = :id");
    $stmt->execute([':id' => $movieId]);
    $movie = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$movie) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Movie asset not found.']);
        exit();
    }

    // 4. Cascade Delete Relational Data
    $delGenres = $conn->prepare("DELETE FROM movie_and_genres WHERE movie_id = :id");
    $delGenres->execute([':id' => $movieId]);

    $delRatings = $conn->prepare("DELETE FROM movie_rating WHERE movie_id = :id");
    $delRatings->execute([':id' => $movieId]);

    $delComments = $conn->prepare("DELETE FROM movie_comments WHERE movie_id = :id");
    $delComments->execute([':id' => $movieId]);

    // 5. Delete Main Movie Record
    $delMovie = $conn->prepare("DELETE FROM movies WHERE movie_id = :id");
    $delMovie->execute([':id' => $movieId]);

    $conn->commit();

    // 6. File Cleanup (Only remove local uploads to avoid deleting external URLs)
    $posterPath = $movie['poster'] ?? '';
    if (!empty($posterPath) && strpos($posterPath, 'uploads/posters/') === 0) {
        $fullPath = __DIR__ . '/../' . $posterPath;
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Movie asset and associated data deleted successfully.'
    ]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}