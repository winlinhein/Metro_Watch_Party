<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit;
}

require_once __DIR__ . '/../poster_helper.php';

$cacheDir = moviePosterCacheDir();
$binFile = $cacheDir . DIRECTORY_SEPARATOR . $id . '.bin';
$mimeFile = $cacheDir . DIRECTORY_SEPARATOR . $id . '.mime';

function sendPosterBytes($bytes, $mime) {
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=3600');
    header('Content-Length: ' . strlen($bytes));
    echo $bytes;
    exit;
}

function detectPosterMime($bytes) {
    if (strncmp($bytes, "\x89PNG", 4) === 0) return 'image/png';
    if (strncmp($bytes, "\xFF\xD8\xFF", 3) === 0) return 'image/jpeg';
    if (strncmp($bytes, 'GIF8', 4) === 0) return 'image/gif';
    if (strncmp($bytes, 'RIFF', 4) === 0 && strpos($bytes, 'WEBP') !== false) return 'image/webp';
    return 'image/jpeg';
}

if (is_file($binFile) && filesize($binFile) > 0) {
    $mime = is_file($mimeFile) ? trim((string)file_get_contents($mimeFile)) : 'image/jpeg';
    if ($mime === '') $mime = 'image/jpeg';
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=3600');
    header('Content-Length: ' . filesize($binFile));
    readfile($binFile);
    exit;
}

require_once __DIR__ . '/../conn.php';

$stmt = $conn->prepare('SELECT poster FROM movies WHERE movie_id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['poster'])) {
    http_response_code(404);
    exit;
}

$bytes = $row['poster'];
$mime = detectPosterMime($bytes);

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0755, true);
}
@file_put_contents($binFile, $bytes, LOCK_EX);
@file_put_contents($mimeFile, $mime, LOCK_EX);

sendPosterBytes($bytes, $mime);
