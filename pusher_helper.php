<?php
// pusher_helper.php — Native PHP (No Composer required)

define('PUSHER_APP_ID', '2183447');
define('PUSHER_KEY', 'f4b5637ef4b8952b6eb8');
define('PUSHER_SECRET', 'fb4c2d3d373ef2e1afc7');
define('PUSHER_CLUSTER', 'ap1');

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

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}