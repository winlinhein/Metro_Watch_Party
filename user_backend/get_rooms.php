<?php
session_start();
header("Content-Type: application/json");

if (empty($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

try {
    require_once __DIR__ . "/../conn.php";

    $stmt = $conn->prepare("
        SELECT r.room_id AS id,
               r.room_code,
               r.host_id,
               r.status,
               r.created_at,
               COALESCE(u.name, u.username, u.email, \"Unknown\") AS host_name
        FROM rooms r
        LEFT JOIN users u ON u.user_id = r.host_id
        WHERE r.status = \"active\"
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = array_map(function($room) {
        return [
            "id"         => (int)$room["id"],
            "name"       => "Room #" . $room["room_code"],
            "room_code"  => $room["room_code"],
            "host"       => $room["host_name"],
            "host_id"    => (int)$room["host_id"],
            "users"      => 0,
            "created_at" => $room["created_at"],
        ];
    }, $rooms);

    echo json_encode(["success" => true, "rooms" => $formatted]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
