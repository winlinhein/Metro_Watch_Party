<?php
require '../conn.php';
$stmt = $conn->query("SHOW COLUMNS FROM reports");
echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
