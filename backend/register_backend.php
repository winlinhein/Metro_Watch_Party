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

$name = $email = $user_pass = "";
$nameErr = $emailErr = $user_passErr = $termsErr = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // --- 1. Form Validation ---
    if (empty($_POST['name'])) {
        $nameErr = "Name is required";
    } elseif (!preg_match("/^[a-zA-Z ]*$/", $_POST["name"])) {
        $nameErr = "Only letters and white space allowed";
    } else {
        $name = test_input($_POST['name']);
    }

    if (empty($_POST['email'])) {
        $emailErr = "Email is required";
    } elseif (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Invalid email format";
    } else {
        $email = test_input($_POST['email']);
    }

    if (empty($_POST['password'])) {
        $user_passErr = "Password is required";
    } else {
        $pwd = $_POST['password'];
        if (
            strlen($pwd) < 8 ||
            !preg_match('/[A-Z]/', $pwd) ||
            !preg_match('/[a-z]/', $pwd) ||
            !preg_match('/[0-9]/', $pwd) ||
            !preg_match('/[!@#$%^&*(),.?":{}|]/', $pwd)
        ) {
            $user_passErr = "Password does not meet complexity requirements";
        } else {
            $user_pass = $pwd;
        }
    }

    if (!isset($_POST['terms'])) {
        $termsErr = "You must accept the terms of service";
    }

    // --- 2. Database Processing ---
    if (empty($nameErr) && empty($emailErr) && empty($user_passErr) && empty($termsErr)) {

        try {
            $stmt = $conn->prepare("SELECT user_id, status FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_user) {
                if ($existing_user['status'] === 'active') {
                    header("Location: ../frontend/register.php?error=" . urlencode("This email is already registered."));
                    exit();
                } elseif ($existing_user['status'] === 'pending') {
                    $otp_stmt = $conn->prepare("SELECT expires_at FROM otp_verification WHERE email = :email AND otp_type = 'register'");
                    $otp_stmt->execute([':email' => $email]);
                    $otp_data = $otp_stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$otp_data || time() > $otp_data['expires_at']) {
                        $conn->prepare("DELETE FROM otp_verification WHERE email = :email")->execute([':email' => $email]);
                        $conn->prepare("DELETE FROM users WHERE user_id = :id")->execute([':id' => $existing_user['user_id']]);
                    } else {
                        $_SESSION['verify_email'] = $email;
                        header("Location: ../frontend/otp-register.php");
                        exit();
                    }
                }
            }

            $otp_code   = sprintf("%06d", random_int(100000, 999999));
            $otp_type   = 'register';
            $expires_at = time() + 180; // 3 minutes expiration
            $hashed_password = password_hash($user_pass, PASSWORD_DEFAULT);

            $conn->beginTransaction();

            $insert_user = $conn->prepare("
                INSERT INTO users (user_name, email, hashed_password) 
                VALUES (:user_name, :email, :hashed_password)
            ");
            $insert_user->execute([
                ':user_name'       => $name,
                ':email'           => $email,
                ':hashed_password' => $hashed_password
            ]);

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
            header("Location: ../frontend/otp-register.php");
            exit();

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
        $firstErr = $nameErr ?: ($emailErr ?: ($user_passErr ?: $termsErr));
        header("Location: ../frontend/register.php?error=" . urlencode($firstErr));
        exit();
    }
}
?>