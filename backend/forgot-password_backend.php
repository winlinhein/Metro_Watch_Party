<?php
// register_backend.php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conn.php';

function test_input($data) {
    return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
}

$email = "";
$emailErr = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (empty($_POST['email'])) {
    $emailErr = "Email is required";
    } elseif (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Invalid email format";
    } else {
        $cleanEmail = test_input($_POST['email']);
        $domain = substr(strrchr($cleanEmail, "@"), 1);

        // Verify if the domain actually exists and has active mail servers (MX records)
        if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
            $emailErr = "The email domain '$domain' does not exist or cannot receive mail.";
        } else {
            $email = $cleanEmail;
        }
    }

    // --- 2. Database Processing ---
    if (empty($emailErr)) {

        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_user) {

                $otp_code   = sprintf("%06d", random_int(100000, 999999));
                $otp_type   = 'forgot';
                $expires_at = time() + 180; // 3 minutes expiration

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
                $mail->Subject = "Nexus Registration - OTP Code";
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; background: #050505; color: #ffffff; padding: 24px; border-radius: 12px;'>
                        <h2 style='color: #4f46e5;'>Nexus Verification</h2>
                        <p style='color: #cccccc;'>Your verification code is:</p>
                        <h1 style='color: #dc2626; letter-spacing: 6px; font-size: 32px;'>{$otp_code}</h1>
                        <p style='color: #888888; font-size: 12px;'>Valid for 3 minutes.</p>
                    </div>
                ";

                $mail->send();
                $conn->commit();

                $_SESSION['verify_email'] = $email;
                header("Location: ../frontend/otp-forgot.php");
                exit();

            } else{
                header("Location: ../frontend/forgot-password.php?error=" . urlencode('email is not registered.'));
                exit();

            }
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            echo "<div style='background:#111; color:#ff5555; padding:20px; font-family:monospace;'>";
            echo "<h2>PHPMailer / DB Error Debug:</h2>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
            exit();
        }
    } else {
        $firstErr = $emailErr;
        header("Location: ../frontend/forgot-password.php?error=" . urlencode($firstErr));
        exit();
    }
}
?>