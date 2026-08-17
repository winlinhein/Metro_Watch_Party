<?php
require_once __DIR__ . '/../conn.php';
$stmt = $conn->query("SELECT * FROM roles");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
