<?php
/**
 * Serve uploaded media from local disk cache or shared DB blob.
 * Usage:
 *   /user_backend/media.php?id=123
 *   /user_backend/media.php?path=/uploads/avatars/file.png
 */
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../media_store_helper.php';

ensureMediaTable($conn);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$path = isset($_GET['path']) ? trim((string)$_GET['path']) : '';

$row = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT id, public_path, mime_type, file_data FROM media_files WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} elseif ($path !== '') {
    if (!preg_match('#^/uploads/(avatars|chat_images)/[A-Za-z0-9._-]+$#', $path)) {
        http_response_code(400);
        header('Content-Type: text/plain');
        echo 'Invalid path';
        exit;
    }
    $stmt = $conn->prepare("SELECT id, public_path, mime_type, file_data FROM media_files WHERE public_path = ? LIMIT 1");
    $stmt->execute([$path]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // Fallback: local disk only (uploader's machine)
    if (!$row) {
        $local = dirname(__DIR__) . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($local)) {
            $mime = detectUploadMime($local, 'application/octet-stream');
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($local));
            header('Cache-Control: public, max-age=86400');
            readfile($local);
            exit;
        }
        http_response_code(404);
        header('Content-Type: text/plain');
        echo 'Not found';
        exit;
    }
} else {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Missing id or path';
    exit;
}

if (!$row) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Not found';
    exit;
}

$publicPath = $row['public_path'];
$local = dirname(__DIR__) . str_replace('/', DIRECTORY_SEPARATOR, $publicPath);

// Prefer local cache when present
if (is_file($local)) {
    header('Content-Type: ' . ($row['mime_type'] ?: detectUploadMime($local)));
    header('Content-Length: ' . filesize($local));
    header('Cache-Control: public, max-age=86400');
    readfile($local);
    exit;
}

$data = $row['file_data'];
if ($data === null || $data === '') {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Not found';
    exit;
}

// Warm local cache for this machine
$dir = dirname($local);
if (!is_dir($dir)) {
    @mkdir($dir, 0755, true);
}
if (is_dir($dir) && !is_file($local)) {
    @file_put_contents($local, $data);
}

header('Content-Type: ' . ($row['mime_type'] ?: 'application/octet-stream'));
header('Content-Length: ' . strlen($data));
header('Cache-Control: public, max-age=86400');
echo $data;
