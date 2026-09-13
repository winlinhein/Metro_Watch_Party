<?php
session_start();
require_once __DIR__ . '/../auth_flow_helper.php';

$config = require __DIR__ . '/../google_oauth_config.php';
$clientId = trim((string)($config['client_id'] ?? ''));
if ($clientId === '') {
    header('Location: ../frontend/login.php?error=' . urlencode('Google login is not configured yet. Add GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET.'));
    exit();
}

$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;
$_SESSION['google_oauth_from'] = (string)($_GET['from'] ?? 'login');
$_SESSION['remember_login'] = true;

$params = http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => nexusGoogleRedirectUri(),
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'access_type' => 'online',
    'prompt' => 'select_account',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit();
