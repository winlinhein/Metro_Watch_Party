<?php
// pusher_helper.php — Native PHP (No Composer required)
require_once __DIR__ . '/curl_ssl_helper.php';

if (!defined('PUSHER_APP_ID')) {
    define('PUSHER_APP_ID', nexusAppEnv('PUSHER_APP_ID', '2204653'));
}
if (!defined('PUSHER_KEY')) {
    define('PUSHER_KEY', nexusAppEnv('PUSHER_KEY', '30110959c92045514bbb'));
}
if (!defined('PUSHER_SECRET')) {
    define('PUSHER_SECRET', nexusAppEnv('PUSHER_SECRET', 'de149a0336db284366cd'));
}
if (!defined('PUSHER_CLUSTER')) {
    define('PUSHER_CLUSTER', nexusAppEnv('PUSHER_CLUSTER', 'ap1'));
}

function triggerPusherEvent($channel, $event, $data) {
    $payload = json_encode([
        'name' => $event,
        'channels' => [$channel],
        'data' => is_string($data) ? $data : json_encode($data)
    ]);

    $path = "/apps/" . PUSHER_APP_ID . "/events";
    $timestamp = time();
    $bodyMd5 = md5($payload);

    $stringToSign = "POST\n{$path}\nauth_key=" . PUSHER_KEY . "&auth_timestamp={$timestamp}&auth_version=1.0&body_md5={$bodyMd5}";
    $authSignature = hash_hmac('sha256', $stringToSign, PUSHER_SECRET);

    $url = "https://api-" . PUSHER_CLUSTER . ".pusher.com{$path}?" . http_build_query([
        'auth_key' => PUSHER_KEY,
        'auth_timestamp' => $timestamp,
        'auth_version' => '1.0',
        'body_md5' => $bodyMd5,
        'auth_signature' => $authSignature
    ]);

    [$response, $httpCode] = nexusCurlExec($url, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => 2,
    ]);

    // Log Pusher errors to /tmp (always writable on Render and locally).
    if ($httpCode !== 200) {
        error_log(
            date('[Y-m-d H:i:s] ') . "Pusher error: HTTP $httpCode, response: $response\n",
            3,
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pusher_errors.log'
        );
    }
    
    return $response;
}