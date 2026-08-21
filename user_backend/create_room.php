<?php
session_start();

// You MUST include your database connection here so $conn is defined
require_once __DIR__ . '/../conn.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_room') {
    
    // Safety check: ensure the user is actually logged in before creating a room
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit;
    }

    // Get the host's user_id from the session
    $host_id = $_SESSION['user_id']; 
    
    // Check if movie_id was passed, otherwise default to 0
    $movie_id = isset($_POST['movie_id']) && is_numeric($_POST['movie_id']) ? intval($_POST['movie_id']) : 0;
    
    // Generate a unique 6-character room code
    $room_code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
    
    // Insert into database, defaulting movie_id to 0 since it might not have a default value
    $stmt = $conn->prepare("INSERT INTO rooms (room_code, host_id, movie_id, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
    $stmt->execute([$room_code, $host_id, $movie_id]);
    
    $room_id = $conn->lastInsertId();
    
    // Return the room code to the frontend so they can join it
    echo json_encode(['success' => true, 'room_code' => $room_code, 'room_id' => $room_id]);
    exit;
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid request or missing action parameter']);
    exit;
}
?>