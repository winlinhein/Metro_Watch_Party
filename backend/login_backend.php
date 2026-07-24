<?php
// login_backend.php - Handles user login and 2FA OTP dispatch
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conn.php';

function test_input($data) {
    return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
}

$email = $user_pass = "";
$emailErr = $user_passErr = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // --- 1. Validation ---
    if (empty($_POST['email'])) {
        $emailErr = "Email is required.";
    } elseif (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Invalid email format.";
    } else {
        $cleanEmail = test_input($_POST['email']);
        $domain = substr(strrchr($cleanEmail, "@"), 1);

        // Verify domain mail capability
        if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
            $emailErr = "The email domain '$domain' does not exist or cannot receive mail.";
        } else {
            $email = $cleanEmail;
        }
    }

    if (empty($_POST['password'])) {
        $user_passErr = "Password is required.";
    } else {
        $user_pass = $_POST['password'];
    }

    // Stop execution early if validation fails
    if (!empty($emailErr) || !empty($user_passErr)) {
        $firstErr = $emailErr ?: $user_passErr;
        header("Location: ../frontend/login.php?error=" . urlencode($firstErr));
        exit();
    }

    // --- 2. Database Processing & Auth ---
    try {
        $stmt = $conn->prepare("SELECT user_id, hashed_password, status FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify account exists & password matches
        if ($user && password_verify($user_pass, $user['hashed_password'])) {
            
            if ($user['status'] === 'active') {
                $otp_code   = sprintf("%06d", random_int(100000, 999999));
                $otp_type   = 'login';
                $expires_at = time() + 180; // 3 minutes expiration

                $conn->beginTransaction();

                // Clear prior OTPs
                $delete_otp = $conn->prepare("DELETE FROM otp_verification WHERE email = :email");
                $delete_otp->execute([':email' => $email]);

                // Insert new OTP
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

                $mail->setFrom('koz51751@gmail.com', 'Nexus Auth');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = "Nexus Login - 2FA Verification Code";
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; background: #050505; color: #ffffff; padding: 24px; border-radius: 12px;'>
                        <h2 style='color: #4f46e5;'>Nexus Verification</h2>
                        <p style='color: #cccccc;'>Your login verification code is:</p>
                        <h1 style='color: #dc2626; letter-spacing: 6px; font-size: 32px;'>{$otp_code}</h1>
                        <p style='color: #888888; font-size: 12px;'>Valid for 3 minutes.</p>
                    </div>
                ";

                $mail->send();
                $conn->commit();

                $_SESSION['verify_email'] = $email;
                header("Location: ../frontend/otp-login.php");
                exit();
                
            } else if($user['status'] == 'pending'){
                header("Location: ../frontend/login.php?error=" . urlencode("Your account is not active."));
                exit();
                               
            } else if($user['status'] == 'banned'){
                header("Location: ../frontend/login.php?error=" . urlencode("Your account is being banned by admin."));
                exit();               
            }               
        } else {
            // Bad credentials redirect
            header("Location: ../frontend/login.php?error=" . urlencode("Invalid email or password."));
            exit();
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        header("Location: ../frontend/login.php?error=" . urlencode("Authentication failed due to a server error."));
        exit();
    }
}
?>