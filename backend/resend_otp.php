<?php
// backend/resend_otp.php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../mail_helper.php';

$email = $_SESSION['verify_email'] ?? '';

// 1. Session Check
if (empty($email)) {
    header("Location: ../frontend/index.php?error=" . urlencode("Session expired. Please log in again."));
    exit();
}

$otp_type = 'register';

try {
    // 2. Fetch existing OTP type before clearing record
    $otp_info = $conn->prepare("SELECT otp_type FROM otp_verification WHERE email = :email");
    $otp_info->execute([':email' => $email]);
    $existing = $otp_info->fetch(PDO::FETCH_ASSOC);

    // Default fallback if type is missing
    $otp_type = $existing['otp_type'] ?? 'register';

    // Generate new OTP & Expiration
    $otp_code   = sprintf("%06d", random_int(100000, 999999));
    $expires_at = time() + 180; // 3 minutes expiration

    // Transaction setup
    $conn->beginTransaction();

    // Clear old OTPs for this email
    $delete_otp = $conn->prepare("DELETE FROM otp_verification WHERE email = :email");
    $delete_otp->execute([':email' => $email]);

    // Insert new OTP record
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

    // Commit DB changes only if mail succeeds
    $conn->commit();

    $_SESSION['verify_email'] = $email;
    header("Location: ../frontend/otp-{$otp_type}.php?success=" . urlencode("A new verification code has been sent to your email."));
    exit();

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Resend OTP Error: " . $e->getMessage());
    
    // Redirect back safely according to type
    $redirect_page = !empty($otp_type) ? "otp-{$otp_type}.php" : "index.php";
    header("Location: ../frontend/{$redirect_page}?error=" . urlencode("Failed to send new code. Please try again later."));
    exit();
}
