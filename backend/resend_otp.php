<?php
// backend/resend_otp.php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conn.php';

$email = $_SESSION['verify_email'] ?? '';

// 1. Session Check
if (empty($email)) {
    header("Location: ../frontend/index.php?error=" . urlencode("Session expired. Please log in again."));
    exit();
}

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

    // --- 3. Send Mail ---
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = getenv('SMTP_USER') ?: 'koz51751@gmail.com'; 
    $mail->Password   = getenv('SMTP_PASS') ?: 'kfnc dyla izdh zmpd';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true
        )
    );

    $mail->setFrom('koz51751@gmail.com', 'Watch Party');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $subject_title = ucfirst($otp_type);
    $mail->Subject = "Nexus {$subject_title} - Verification OTP Code";
    $mail->Body    = "
        <div style='font-family: Arial, sans-serif; background: #050505; color: #ffffff; padding: 24px; border-radius: 12px;'>
            <h2 style='color: #4f46e5;'>Nexus Verification</h2>
            <p style='color: #cccccc;'>Your verification code is:</p>
            <h1 style='color: #dc2626; letter-spacing: 6px; font-size: 32px;'>{$otp_code}</h1>
            <p style='color: #888888; font-size: 12px;'>Valid for 3 minutes.</p>
        </div>
    ";

    $mail->send();

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
?>