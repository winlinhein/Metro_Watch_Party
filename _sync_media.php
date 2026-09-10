<?php
require __DIR__ . '/conn.php';
require __DIR__ . '/media_store_helper.php';
ensureMediaTable($conn);

$dirs = [
    'avatars' => __DIR__ . '/uploads/avatars',
    'chat_images' => __DIR__ . '/uploads/chat_images',
];
$n = 0;
foreach ($dirs as $subdir => $dir) {
    if (!is_dir($dir)) continue;
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $file;
        if (!is_file($full)) continue;
        $public = '/uploads/' . $subdir . '/' . $file;
        $check = $conn->prepare('SELECT id FROM media_files WHERE public_path = ?');
        $check->execute([$public]);
        if ($check->fetchColumn()) {
            echo "skip $public\n";
            continue;
        }
        $bytes = file_get_contents($full);
        if ($bytes === false || $bytes === '') continue;
        $mime = detectUploadMime($full);
        $stmt = $conn->prepare('INSERT INTO media_files (public_path, mime_type, file_data, user_id) VALUES (?,?,?,NULL)');
        $stmt->execute([$public, $mime, $bytes]);
        $n++;
        echo "stored $public id=" . $conn->lastInsertId() . "\n";
    }
}
echo "done synced=$n\n";
