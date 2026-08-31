<?php
/**
 * Store uploaded media in the shared DB so every client can load it
 * (local disk alone breaks when multiple machines share one remote database).
 */

function ensureMediaTable(PDO $conn): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $conn->exec("
        CREATE TABLE IF NOT EXISTS media_files (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            public_path VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL DEFAULT 'application/octet-stream',
            file_data LONGBLOB NOT NULL,
            user_id INT UNSIGNED NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_media_public_path (public_path),
            KEY idx_media_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $ready = true;
}

function detectUploadMime(string $tmpPath, string $fallback = 'application/octet-stream'): string
{
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $tmpPath) ?: '';
            finfo_close($finfo);
            if ($mime !== '') {
                return $mime;
            }
        }
    }
    if (function_exists('mime_content_type')) {
        $mime = @mime_content_type($tmpPath);
        if (is_string($mime) && $mime !== '') {
            return $mime;
        }
    }
    return $fallback;
}

function isAllowedImageMime(string $mime): bool
{
    return in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
}

/**
 * Downscale large images so shared-DB blobs stay reasonable.
 * Returns [path, mime] — may rewrite file in place as JPEG/PNG.
 */
function optimizeImageFile(string $path, string $mime, string $subdir): array
{
    if (!function_exists('imagecreatefromstring')) {
        return [$path, $mime];
    }

    $maxSide = $subdir === 'avatars' ? 512 : 1280;
    $bytes = @file_get_contents($path);
    if ($bytes === false || $bytes === '') {
        return [$path, $mime];
    }

    $img = @imagecreatefromstring($bytes);
    if (!$img) {
        return [$path, $mime];
    }

    $w = imagesx($img);
    $h = imagesy($img);
    if ($w < 1 || $h < 1) {
        imagedestroy($img);
        return [$path, $mime];
    }

    $scale = min(1.0, $maxSide / max($w, $h));
    $nw = max(1, (int)round($w * $scale));
    $nh = max(1, (int)round($h * $scale));

    if ($scale < 1.0) {
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($img);
        $img = $dst;
    }

    // Prefer JPEG for photos (smaller); keep PNG/GIF/WebP when alpha/animation likely
    $outMime = $mime;
    if ($subdir === 'avatars' || in_array($mime, ['image/jpeg', 'image/webp'], true)) {
        $outMime = 'image/jpeg';
        imagejpeg($img, $path, 82);
    } elseif ($mime === 'image/png') {
        imagepng($img, $path, 6);
    } else {
        // gif etc — leave original bytes if we didn't resize
        if ($scale < 1.0) {
            imagepng($img, $path, 6);
            $outMime = 'image/png';
        }
    }
    imagedestroy($img);

    return [$path, $outMime];
}

/**
 * Persist file bytes in media_files and return a shareable serve URL.
 * Also keeps a local disk copy when possible (cache for the uploading machine).
 */
function storeMediaFromUpload(
    PDO $conn,
    array $file,
    string $subdir,
    string $filename,
    ?int $userId = null
): array {
    ensureMediaTable($conn);

    $subdir = trim($subdir, '/');
    if (!in_array($subdir, ['avatars', 'chat_images', 'shop'], true)) {
        throw new InvalidArgumentException('Invalid media subdirectory');
    }

    $mime = detectUploadMime($file['tmp_name'] ?? '', $file['type'] ?? 'application/octet-stream');
    if (!isAllowedImageMime($mime)) {
        throw new RuntimeException('Invalid image type');
    }

    $uploadDir = __DIR__ . '/uploads/' . $subdir . '/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Failed to create upload directory');
    }

    $destination = $uploadDir . $filename;
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid upload');
    }
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Failed to save file');
    }

    [$destination, $mime] = optimizeImageFile($destination, $mime, $subdir);

    // Keep filename extension aligned with optimized mime when needed
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $wantedExt = $extMap[$mime] ?? null;
    if ($wantedExt) {
        $currentExt = strtolower(pathinfo($destination, PATHINFO_EXTENSION));
        if ($currentExt !== $wantedExt) {
            $newName = preg_replace('/\.[^.]+$/', '.' . $wantedExt, $filename) ?: ($filename . '.' . $wantedExt);
            $newPath = $uploadDir . $newName;
            if (@rename($destination, $newPath)) {
                $destination = $newPath;
                $filename = $newName;
            }
        }
    }

    $bytes = file_get_contents($destination);
    if ($bytes === false || $bytes === '') {
        @unlink($destination);
        throw new RuntimeException('Failed to read saved file');
    }

    $publicPath = '/uploads/' . $subdir . '/' . $filename;

    $stmt = $conn->prepare("
        INSERT INTO media_files (public_path, mime_type, file_data, user_id)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            mime_type = VALUES(mime_type),
            file_data = VALUES(file_data),
            user_id = VALUES(user_id)
    ");
    $stmt->execute([$publicPath, $mime, $bytes, $userId]);

    $id = (int)$conn->lastInsertId();
    if ($id <= 0) {
        $find = $conn->prepare("SELECT id FROM media_files WHERE public_path = ? LIMIT 1");
        $find->execute([$publicPath]);
        $id = (int)$find->fetchColumn();
    }

    if ($id <= 0) {
        throw new RuntimeException('Failed to register media in database');
    }

    return [
        'id' => $id,
        'public_path' => $publicPath,
        'serve_url' => '/user_backend/media.php?id=' . $id,
        'mime_type' => $mime,
        'local_path' => $destination,
    ];
}

function deleteMediaByPublicPath(PDO $conn, string $publicPath): void
{
    if ($publicPath === '') {
        return;
    }
    ensureMediaTable($conn);

    $local = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $publicPath);
    if (is_file($local)) {
        @unlink($local);
    }

    $stmt = $conn->prepare("DELETE FROM media_files WHERE public_path = ?");
    $stmt->execute([$publicPath]);
}

function mediaServeUrlFromStored(?string $stored): string
{
    $stored = trim((string)$stored);
    if ($stored === '') {
        return '';
    }
    if (str_starts_with($stored, '/user_backend/media.php')) {
        return $stored;
    }
    if (preg_match('#^/uploads/(avatars|chat_images)/#', $stored)) {
        return '/user_backend/media.php?path=' . rawurlencode($stored);
    }
    return $stored;
}
