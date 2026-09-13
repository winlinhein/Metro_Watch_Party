<?php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../mail_helper.php';
require_once __DIR__ . '/../account_lifecycle_helper.php';

$hold = $_SESSION['account_hold'] ?? null;
if (!$hold || ($hold['mode'] ?? '') !== 'deletion' || empty($hold['user_id'])) {
    header('Location: ../frontend/login.php?error=' . urlencode('No pending deletion to cancel.'));
    exit();
}

try {
    ensureAppSchema($conn);
    $userId = (int)$hold['user_id'];
    $email = (string)($hold['email'] ?? '');
    $role = (string)($hold['role'] ?? 'user');

    $stmt = $conn->prepare("SELECT user_id, email, status, deletion_requested_at FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        unset($_SESSION['account_hold']);
        header('Location: ../frontend/login.php?error=' . urlencode('This account no longer exists.'));
        exit();
    }

    nexusCancelAccountDeletion($conn, $userId);
    unset($_SESSION['account_hold']);

    $otp_code = sprintf('%06d', random_int(100000, 999999));
    $expires_at = time() + 180;
    $conn->beginTransaction();
    $conn->prepare("DELETE FROM otp_verification WHERE email = :email")->execute([':email' => $email]);
    $conn->prepare("
        INSERT INTO otp_verification (email, otp_code, otp_type, expires_at)
        VALUES (:email, :otp_code, 'login', :expires_at)
    ")->execute([
        ':email' => $email,
        ':otp_code' => $otp_code,
        ':expires_at' => $expires_at,
    ]);
    sendOtpEmail($email, $otp_code, 'login');
    $conn->commit();

    $_SESSION['verify_email'] = $email;
    $_SESSION['otp_type'] = 'login';
    $_SESSION['user_role'] = $role;
    header('Location: ../frontend/otp-login.php?restored=1');
    exit();
} catch (Throwable $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    header('Location: ../frontend/account_hold.php?error=' . urlencode('Could not reverse deletion. Try again.'));
    exit();
}
