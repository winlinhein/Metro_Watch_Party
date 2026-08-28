<?php
function moviePosterUrl($movieId) {
    return '/user_backend/get_poster.php?id=' . (int)$movieId;
}

function moviePosterCacheDir() {
    return __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'posters';
}

function invalidateMoviePosterCache($movieId) {
    $dir = moviePosterCacheDir();
    $id = (int)$movieId;
    foreach ([$dir . DIRECTORY_SEPARATOR . $id . '.bin', $dir . DIRECTORY_SEPARATOR . $id . '.mime'] as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
