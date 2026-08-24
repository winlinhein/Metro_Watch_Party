<?php
session_start();
require_once __DIR__ . '/../conn.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_room') {
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit;
    }

    $host_id = $_SESSION['user_id']; 
    $movie_id = isset($_POST['movie_id']) && !empty($_POST['movie_id']) ? $_POST['movie_id'] : 0;
    $room_code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
    
    // Insert into database (ensure table exists or just create room)
    try {
        $stmt = $conn->prepare("INSERT INTO rooms (room_code, host_id, movie_id, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
        $stmt->execute([$room_code, $host_id, $movie_id]);
        $room_id = $conn->lastInsertId();
        
        echo json_encode(['success' => true, 'room_code' => $room_code, 'room_id' => $room_id]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
