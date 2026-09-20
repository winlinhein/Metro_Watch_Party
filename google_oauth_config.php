<?php

require_once __DIR__ . '/curl_ssl_helper.php';

$clientId = nexusAppEnv('GOOGLE_CLIENT_ID');
$clientSecret = nexusAppEnv('GOOGLE_CLIENT_SECRET');

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
