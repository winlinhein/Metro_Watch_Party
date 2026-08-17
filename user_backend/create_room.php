<?php
session_start();

// You MUST include your database connection here so $pdo is defined
require_once 'db_connect.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_room') {
    
    // Safety check: ensure the user is actually logged in before creating a room
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'User not logged in']);
        exit;
    }

    // Get the host's user_id from the session
    $host_id = $_SESSION['user_id']; 
    
    // Generate a unique 6-character room code
    $room_code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
    
    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO rooms (room_code, host_id, status, created_at) VALUES (?, ?, 'active', NOW())");
    $stmt->execute([$room_code, $host_id]);
    
    $room_id = $pdo->lastInsertId();
    
    // Return the room code to the frontend so they can join it
    echo json_encode(['success' => true, 'room_code' => $room_code, 'room_id' => $room_id]);
    exit;
}
?>