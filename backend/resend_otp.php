<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../mail_helper.php';
require_once __DIR__ . '/../otp_helper.php';

$email = $_SESSION['verify_email'] ?? '';
$wantsJson = (
    (isset($_GET['format']) && $_GET['format'] === 'json')
    || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
    || strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
);

function nexusOtpResendRespond(bool $wantsJson, bool $ok, string $otpType, string $message, array $status = []): void
{
    $page = in_array($otpType, ['login', 'register', 'forgot'], true) ? "otp-{$otpType}.php" : 'login.php';
    if ($wantsJson) {
        header('Content-Type: application/json');
        echo json_encode(array_merge([
            'success' => $ok,
            'message' => $message,
            'otp_type' => $otpType,
        ], $status));
        exit();
    }

    $key = $ok ? 'success' : 'error';
    header('Location: ../frontend/' . $page . '?' . $key . '=' . urlencode($message));
    exit();
}

if (empty($email)) {
    nexusOtpResendRespond($wantsJson, false, (string)($_SESSION['otp_type'] ?? 'login'), 'Session expired. Please log in again.');
}

$otp_type = (string)($_SESSION['otp_type'] ?? '');

try {
    if ($otp_type === '') {
        $otp_info = $conn->prepare("SELECT otp_type FROM otp_verification WHERE email = :email ORDER BY expires_at DESC LIMIT 1");
        $otp_info->execute([':email' => $email]);
        $otp_type = (string)($otp_info->fetchColumn() ?: 'register');
    }

    $otp_code   = sprintf("%06d", random_int(100000, 999999));
    $expires_at = time() + NEXUS_OTP_DURATION;

    $conn->beginTransaction();

    $delete_otp = $conn->prepare("DELETE FROM otp_verification WHERE email = :email");
    $delete_otp->execute([':email' => $email]);

    $insert_otp = $conn->prepare("
        INSERT INTO otp_verification (email, otp_code, otp_type, expires_at)
        VALUES (:email, :otp_code, :otp_type, :expires_at)
    ");
    $insert_otp->execute([
        ':email'      => $email,
        ':otp_code'   => $otp_code,
        ':otp_type'   => $otp_type,
        ':expires_at' => $expires_at
    ]);

    sendOtpEmail($email, $otp_code, $otp_type);
    $conn->commit();

    $_SESSION['verify_email'] = $email;
    $_SESSION['otp_type'] = $otp_type;

    $status = nexusCurrentOtpStatus($conn, $email, $otp_type);
    nexusOtpResendRespond($wantsJson, true, $otp_type, 'A new verification code has been sent to your email.', $status);
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Resend OTP Error: ' . $e->getMessage());
    nexusOtpResendRespond($wantsJson, false, $otp_type ?: 'login', 'Failed to send new code. Please try again later.');
}
