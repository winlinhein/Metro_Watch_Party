<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../otp_helper.php';

$email = (string)($_SESSION['verify_email'] ?? '');
$type = (string)($_SESSION['otp_type'] ?? ($_GET['type'] ?? ''));

if ($email === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Session expired.',
        'expires_at' => 0,
        'server_now' => time(),
        'remaining' => 0,
        'duration' => NEXUS_OTP_DURATION,
        'otp_type' => $type,
    ]);
    exit();
}

echo json_encode(nexusCurrentOtpStatus($conn, $email, $type !== '' ? $type : null));
