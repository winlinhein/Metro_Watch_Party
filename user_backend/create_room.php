<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../admin_rooms_helper.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_room') {
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit;
    }

    header('Content-Type: application/json');
    $host_id = (int)$_SESSION['user_id'];
    $rawMovie = $_POST['movie_id'] ?? 0;
    $movie_id = is_array($rawMovie) ? 0 : (int)$rawMovie;
    if ($movie_id < 0) {
        $movie_id = 0;
    }
    if ($movie_id > 0) {
        $movieCheck = $conn->prepare("SELECT movie_id FROM movies WHERE movie_id = ? LIMIT 1");
        $movieCheck->execute([$movie_id]);
        if (!$movieCheck->fetchColumn()) {
            $movie_id = 0;
        }
    }
    $room_code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
    
    // Insert into database (ensure table exists or just create room)
    try {
        $stmt = $conn->prepare("INSERT INTO rooms (room_code, host_id, movie_id, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
        $stmt->execute([$room_code, $host_id, $movie_id]);
        $room_id = $conn->lastInsertId();
        broadcastAdminRoomsChanged('create', [
            'room_id' => (int)$room_id,
            'host_id' => (int)$host_id,
            'movie_id' => (int)$movie_id,
        ]);
        
        echo json_encode([
            'success' => true,
            'room_code' => $room_code,
            'room_id' => $room_id,
            'movie_id' => (int)$movie_id,
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
