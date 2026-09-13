<?php
session_start();

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../auth_flow_helper.php';
require_once __DIR__ . '/../account_lifecycle_helper.php';

$config = require __DIR__ . '/../google_oauth_config.php';
$clientId = trim((string)($config['client_id'] ?? ''));
$clientSecret = trim((string)($config['client_secret'] ?? ''));
$from = (string)($_SESSION['google_oauth_from'] ?? 'login');
$failPage = $from === 'register' ? '../frontend/register.php' : '../frontend/login.php';
$redirectUri = nexusGoogleRedirectUri();

$state = (string)($_GET['state'] ?? '');
$code = (string)($_GET['code'] ?? '');
$expected = (string)($_SESSION['google_oauth_state'] ?? '');
unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_from']);

if ($clientId === '' || $clientSecret === '') {
    header('Location: ' . $failPage . '?error=' . urlencode('Google login is not configured.'));
    exit();
}

if (!empty($_GET['error'])) {
    header('Location: ' . $failPage . '?error=' . urlencode('Google sign-in was cancelled.'));
    exit();
}

if ($code === '' || $state === '' || $expected === '' || !hash_equals($expected, $state)) {
    header('Location: ' . $failPage . '?error=' . urlencode('Google sign-in was cancelled or expired. Try Google again.'));
    exit();
}

try {
    ensureAppSchema($conn);
    nexusEnsureGoogleUserColumns($conn);

    $tokenRes = nexusGooglePost('https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code',
    ]);

    if (!empty($tokenRes['error'])) {
        $detail = (string)($tokenRes['error_description'] ?? $tokenRes['error']);
        if (($tokenRes['error'] ?? '') === 'redirect_uri_mismatch') {
            throw new RuntimeException('Redirect URI mismatch. Add this exact URI in Google Cloud: ' . $redirectUri);
        }
        if (($tokenRes['error'] ?? '') === 'invalid_client') {
            throw new RuntimeException('Google client ID or secret is wrong.');
        }
        throw new RuntimeException($detail !== '' ? $detail : 'Google token exchange failed.');
    }

    $accessToken = (string)($tokenRes['access_token'] ?? '');
    $idToken = (string)($tokenRes['id_token'] ?? '');
    $info = [];
    if ($accessToken !== '') {
        $info = nexusGoogleGet('https://www.googleapis.com/oauth2/v3/userinfo', $accessToken);
    }
    if (empty($info['sub']) && $idToken !== '') {
        $info = nexusGoogleDecodeIdToken($idToken);
    }

    $googleId = (string)($info['sub'] ?? '');
    $email = strtolower(trim((string)($info['email'] ?? '')));
    $name = trim((string)($info['name'] ?? ''));
    $verified = !empty($info['email_verified']) && $info['email_verified'] !== 'false';
    if ($googleId === '' || $email === '') {
        throw new RuntimeException('Google did not return an email for this account.');
    }
    if ($name === '') {
        $name = strstr($email, '@', true) ?: 'Nexus User';
    }

    $user = nexusFindGoogleUser($conn, $googleId, $email);
    if ($user && empty($user['google_id'])) {
        try {
            $conn->prepare("UPDATE users SET google_id = ? WHERE user_id = ?")
                ->execute([$googleId, (int)$user['user_id']]);
            $user['google_id'] = $googleId;
        } catch (Throwable $ignore) {
        }
    }

    if (!$user) {
        $user = nexusCreateGoogleUser($conn, $name, $email, $googleId);
    }

    if (!$user) {
        throw new RuntimeException('Could not create or load the Google account.');
    }

    try {
        nexusPurgeExpiredDeletions($conn);
    } catch (Throwable $ignore) {
    }

    $redirect = nexusFinishLogin($conn, $user, true);
    header('Location: ' . $redirect);
    exit();
} catch (Throwable $e) {
    $message = $e->getMessage();
    @file_put_contents(
        __DIR__ . '/../cache/google_oauth.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $message . ' uri=' . $redirectUri . PHP_EOL,
        FILE_APPEND
    );
    header('Location: ' . $failPage . '?error=' . urlencode($message));
    exit();
}

function nexusEnsureGoogleUserColumns(PDO $conn): void
{
    foreach ([
        "ALTER TABLE users ADD COLUMN google_id VARCHAR(64) NULL DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN auth_provider VARCHAR(20) NOT NULL DEFAULT 'email'",
    ] as $sql) {
        try {
            $conn->exec($sql);
        } catch (Throwable $ignore) {
        }
    }
}

function nexusFindGoogleUser(PDO $conn, string $googleId, string $email): ?array
{
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE google_id = ? LIMIT 1");
        $stmt->execute([$googleId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            return $user;
        }
    } catch (Throwable $ignore) {
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function nexusCreateGoogleUser(PDO $conn, string $name, string $email, string $googleId): ?array
{
    $roleId = 2;
    try {
        $roleStmt = $conn->prepare("SELECT role_id FROM roles WHERE LOWER(role) = 'user' LIMIT 1");
        $roleStmt->execute();
        $found = (int)$roleStmt->fetchColumn();
        if ($found > 0) {
            $roleId = $found;
        }
    } catch (Throwable $ignore) {
    }

    $passwordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    try {
        $insert = $conn->prepare("
            INSERT INTO users (user_name, email, hashed_password, role_id, status, google_id, auth_provider)
            VALUES (?, ?, ?, ?, 'active', ?, 'google')
        ");
        $insert->execute([$name, $email, $passwordHash, $roleId, $googleId]);
    } catch (Throwable $insertError) {
        $insert = $conn->prepare("
            INSERT INTO users (user_name, email, hashed_password, role_id, status)
            VALUES (?, ?, ?, ?, 'active')
        ");
        $insert->execute([$name, $email, $passwordHash, $roleId]);
    }

    $id = (int)$conn->lastInsertId();
    if ($id <= 0) {
        return null;
    }
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function nexusGooglePost(string $url, array $fields): array
{
    $raw = nexusGoogleCurl($url, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
    ]);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function nexusGoogleGet(string $url, string $accessToken): array
{
    $raw = nexusGoogleCurl($url, [
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
    ]);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function nexusGoogleCurl(string $url, array $opts): string
{
    $base = $opts + [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
    ];

    $attempts = [
        [CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2],
        [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0],
    ];
    $lastError = 'Google request failed.';
    foreach ($attempts as $ssl) {
        $ch = curl_init($url);
        curl_setopt_array($ch, $base + $ssl);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw !== false) {
            return (string)$raw;
        }
        $lastError = $err !== '' ? $err : $lastError;
    }
    throw new RuntimeException($lastError);
}

function nexusGoogleDecodeIdToken(string $idToken): array
{
    $parts = explode('.', $idToken);
    if (count($parts) < 2) {
        return [];
    }
    $payload = $parts[1];
    $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
    $json = base64_decode(strtr($payload, '-_', '+/'));
    $data = json_decode((string)$json, true);
    return is_array($data) ? $data : [];
}
