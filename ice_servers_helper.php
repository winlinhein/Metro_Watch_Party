<?php

function nexusIceEnv(string $key, string $default = ''): string
{
    foreach ([getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null, $_SERVER['REDIRECT_' . $key] ?? null] as $value) {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }
    return $default;
}

function nexusStunServers(): array
{
    return [
        ['urls' => 'stun:stun.l.google.com:19302'],
        ['urls' => 'stun:stun1.l.google.com:19302'],
        ['urls' => 'stun:stun2.l.google.com:19302'],
        ['urls' => 'stun:stun.relay.metered.ca:80'],
        ['urls' => 'stun:stun.cloudflare.com:3478'],
    ];
}

function nexusTurnUrlsForHost(string $host): array
{
    $host = preg_replace('#^(turns?:)?#i', '', trim($host));
    $host = preg_replace('#:\d+$#', '', $host);
    if ($host === '') {
        return [];
    }
    return [
        'turn:' . $host . ':80',
        'turn:' . $host . ':80?transport=tcp',
        'turn:' . $host . ':443',
        'turn:' . $host . ':443?transport=tcp',
        'turns:' . $host . ':443?transport=tcp',
    ];
}

function nexusTurnEntries(array $urls, string $username, string $credential): array
{
    $servers = [];
    foreach ($urls as $url) {
        $url = trim((string)$url);
        if ($url === '') {
            continue;
        }
        $servers[] = [
            'urls' => $url,
            'username' => $username,
            'credential' => $credential,
        ];
    }
    return $servers;
}

function nexusTurnFromStaticAuth(): array
{
    $secret = nexusIceEnv('TURN_SECRET', 'openrelayprojectsecret');
    $hostsRaw = nexusIceEnv('TURN_HOST', 'openrelay.metered.ca,staticauth.openrelay.metered.ca');
    if ($secret === '' || $hostsRaw === '') {
        return [];
    }
    $ttl = 12 * 3600;
    $username = (string)(time() + $ttl) . ':nexus';
    $credential = base64_encode(hash_hmac('sha1', $username, $secret, true));
    $urls = [];
    foreach (preg_split('/\s*,\s*/', $hostsRaw) as $host) {
        $urls = array_merge($urls, nexusTurnUrlsForHost($host));
    }
    return nexusTurnEntries($urls, $username, $credential);
}

function nexusTurnFromEnvCredentials(): array
{
    $username = nexusIceEnv('TURN_USERNAME');
    $credential = nexusIceEnv('TURN_CREDENTIAL');
    if ($username === '' || $credential === '') {
        return [];
    }
    $urlsRaw = nexusIceEnv('TURN_URLS');
    $urls = $urlsRaw !== ''
        ? preg_split('/\s*,\s*/', $urlsRaw)
        : nexusTurnUrlsForHost(nexusIceEnv('TURN_HOST', 'global.relay.metered.ca'));
    return nexusTurnEntries($urls ?: [], $username, $credential);
}

function nexusTurnFromMeteredApi(): array
{
    $apiKey = nexusIceEnv('METERED_TURN_API_KEY');
    $app = nexusIceEnv('METERED_TURN_APP');
    if ($apiKey === '' || $app === '') {
        return [];
    }
    $app = preg_replace('/[^a-zA-Z0-9-]/', '', $app);
    if ($app === '') {
        return [];
    }
    $url = 'https://' . $app . '.metered.live/api/v1/turn/credentials?apiKey=' . rawurlencode($apiKey);
    require_once __DIR__ . '/curl_ssl_helper.php';
    [$raw] = nexusCurlExec($url, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 4,
    ]);
    $data = json_decode((string)$raw, true);
    if (!is_array($data) || isset($data['error'])) {
        return [];
    }
    $servers = [];
    foreach ($data as $entry) {
        if (!is_array($entry) || empty($entry['urls'])) {
            continue;
        }
        $row = ['urls' => $entry['urls']];
        if (!empty($entry['username'])) {
            $row['username'] = (string)$entry['username'];
        }
        if (!empty($entry['credential'])) {
            $row['credential'] = (string)$entry['credential'];
        }
        $servers[] = $row;
    }
    return $servers;
}

function nexusTurnFromOpenRelayLegacy(): array
{
    return nexusTurnEntries(
        nexusTurnUrlsForHost('openrelay.metered.ca'),
        'openrelayproject',
        'openrelayproject'
    );
}

function nexusIceServers(): array
{
    $turn = nexusTurnFromMeteredApi();
    if (!$turn) {
        $turn = nexusTurnFromEnvCredentials();
    }
    if (!$turn) {
        $turn = array_merge(nexusTurnFromStaticAuth(), nexusTurnFromOpenRelayLegacy());
    }
    return array_values(array_merge(nexusStunServers(), $turn));
}
