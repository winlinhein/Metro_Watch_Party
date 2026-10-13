<?php

// Load local .env values (DB_*, STRIPE_*, TURN_* ...) as early as possible.
// This file is included by conn.php, so secrets resolve before the DB connects.
// On Render these values come from the real environment instead, and .env is
// excluded from the Docker image.
if (!function_exists('loadMailDotEnv')) {
    $nexusMailHelper = __DIR__ . DIRECTORY_SEPARATOR . 'mail_helper.php';
    if (is_file($nexusMailHelper)) {
        require_once $nexusMailHelper;
    }
}
if (function_exists('loadMailDotEnv')) {
    loadMailDotEnv();
}

function nexusAppEnv(string $key, string $default = ''): string
{
    foreach ([getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null, $_SERVER['REDIRECT_' . $key] ?? null] as $value) {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }
    return $default;
}

function nexusIsRenderHost(): bool
{
    return nexusAppEnv('RENDER') === 'true' || nexusAppEnv('RENDER_SERVICE_ID') !== '';
}

function nexusCurlExec(string $url, array $options): array
{
    $sslOn = [
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];
    $attempts = [$sslOn];
    if (!nexusIsRenderHost()) {
        $attempts[] = [
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ];
    }

    $response = false;
    $httpCode = 0;
    $error = '';
    foreach ($attempts as $ssl) {
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        curl_setopt_array($ch, $ssl);
        $response = curl_exec($ch);
        $error = (string)curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response !== false) {
            return [$response, $httpCode, $error];
        }
    }

    return [$response, $httpCode, $error];
}
