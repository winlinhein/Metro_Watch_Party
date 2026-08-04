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
// GET: Retrieve all genres from the database
// -------------------------------------------------------------
if ($method === 'GET') {
    try {
        $stmt = $conn->query("SELECT genre_id AS id, genre_name AS name FROM genres ORDER BY genre_name ASC");
        $genres = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ensure IDs are integers
        foreach ($genres as &$genre) {
            $genre['id'] = (int) $genre['id'];
        }

        echo json_encode($genres);
        exit();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
        exit();
    }
}

// -------------------------------------------------------------
// POST: Add a new genre (optional feature for admin)
// -------------------------------------------------------------
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $genreName = trim($input['name'] ?? '');

    if (empty($genreName)) {
        http_response_code(400);
        echo json_encode(['error' => 'Genre name is required']);
        exit();
    }

    try {
        $stmt = $conn->prepare("INSERT INTO genres (genre_name) VALUES (?)");
        $stmt->execute([$genreName]);
        $newId = $conn->lastInsertId();

        echo json_encode(['success' => true, 'genre' => ['id' => (int)$newId, 'name' => $genreName]]);
        exit();

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add genre: ' . $e->getMessage()]);
        exit();
    }
}
?>