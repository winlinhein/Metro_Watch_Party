<?php
/**
 * Serve uploaded media from local disk cache or shared DB blob.
 * Usage:
 *   /user_backend/media.php?id=123
 *   /user_backend/media.php?path=/uploads/avatars/file.png
 */
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../media_store_helper.php';

ensureMediaTable($conn);

function isAllowedMediaPublicPath(string $path): bool
{
    if ($path === '' || str_contains($path, '..') || str_contains($path, "\\")) {
        return false;
    }
    if (!preg_match('#^/uploads/(avatars|chat_images|shop)/#', $path)) {
        return false;
    }
    $filename = preg_replace('#^/uploads/(avatars|chat_images|shop)/#', '', $path);
    return $filename !== '' && !str_contains($filename, '/');
}

function sendCachedMediaFile(string $local, string $mime): void
{
    $mtime = (int)@filemtime($local);
    $size = (int)@filesize($local);
    $etag = '"' . dechex($mtime) . '-' . dechex($size) . '"';
    header('Content-Type: ' . $mime);
    header('ETag: ' . $etag);
    header('Cache-Control: public, max-age=604800, immutable');
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim((string)$_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
        http_response_code(304);
        exit;
    }
    header('Content-Length: ' . $size);
    readfile($local);
}

function mediaNotFoundImage(): void
{
    http_response_code(404);
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5W2XQAAAAASUVORK5CYII=');
    header('Content-Type: image/png');
    header('Cache-Control: no-store');
    echo $png;
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$path = isset($_GET['path']) ? trim((string)$_GET['path']) : '';

$row = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT id, public_path, mime_type, file_data FROM media_files WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} elseif ($path !== '') {
    if (!isAllowedMediaPublicPath($path)) {
        mediaNotFoundImage();
    }
    $stmt = $conn->prepare("SELECT id, public_path, mime_type, file_data FROM media_files WHERE public_path = ? LIMIT 1");
    $stmt->execute([$path]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    // Fallback: local disk only (uploader's machine)
    if (!$row) {
        $local = dirname(__DIR__) . str_replace('/', DIRECTORY_SEPARATOR, $path);
        if (is_file($local)) {
            $mime = detectUploadMime($local, 'application/octet-stream');
            sendCachedMediaFile($local, $mime);
            exit;
        }
        mediaNotFoundImage();
    }
} else {
    mediaNotFoundImage();
}

if (!$row) {
    mediaNotFoundImage();
}

$publicPath = $row['public_path'];
$local = dirname(__DIR__) . str_replace('/', DIRECTORY_SEPARATOR, $publicPath);

// Prefer local cache when present
if (is_file($local)) {
    sendCachedMediaFile($local, $row['mime_type'] ?: detectUploadMime($local));
    exit;
}

$data = $row['file_data'];
if ($data === null || $data === '') {
    mediaNotFoundImage();
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
header('Cache-Control: public, max-age=604800, immutable');
echo $data;
