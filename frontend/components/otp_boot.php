<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../conn.php';
require_once __DIR__ . '/../../otp_helper.php';

$otpPageType = $otpPageType ?? 'login';
$email = (string)($_SESSION['verify_email'] ?? '');
if ($email === '') {
    $back = [
        'login' => 'login.php',
        'register' => 'register.php',
        'forgot' => 'forgot-password.php',
    ][$otpPageType] ?? 'login.php';
    header('Location: ' . $back . '?error=' . urlencode('Session expired. Please try again.'));
    exit();
}

if (empty($_SESSION['otp_type'])) {
    $_SESSION['otp_type'] = $otpPageType;
}

$otpStatus = nexusCurrentOtpStatus($conn, $email, $otpPageType);
?>
<script>
window.NEXUS_OTP = <?php echo json_encode($otpStatus, JSON_UNESCAPED_SLASHES); ?>;
</script>
