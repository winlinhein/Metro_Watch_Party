<?php

$clientId = trim((string)(getenv('GOOGLE_CLIENT_ID') ?: ''));
$clientSecret = trim((string)(getenv('GOOGLE_CLIENT_SECRET') ?: ''));

$local = __DIR__ . '/google_oauth.local.php';
if (is_file($local)) {
    $extra = include $local;
    if (is_array($extra)) {
        if (!empty($extra['client_id'])) {
            $clientId = trim((string)$extra['client_id']);
        }
        if (!empty($extra['client_secret'])) {
            $clientSecret = trim((string)$extra['client_secret']);
        }
    }
}

return [
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
];
