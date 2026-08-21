<?php
require '../conn.php';
$stmt = $conn->query("SHOW COLUMNS FROM rooms");
echo json_encode($stmt->fetchAll());
