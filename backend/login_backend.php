<?php
// login_backend.php - Handles user login, lockout checks, and 2FA OTP dispatch
ob_start(); // Prevent "headers already sent" errors
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

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

    if (!empty($emailErr) || !empty($user_passErr)) {
        $firstErr = $emailErr ?: $user_passErr;
        header("Location: ../frontend/login.php?error=" . urlencode($firstErr));
        exit();
    }

    // --- 2. Database Processing & Auth ---
    try {
        $current_time = time();

        // Retrieve user credentials and lockout state (include last_failed_login)
        $stmt = $conn->prepare("
            SELECT user_id, role_id, hashed_password, status, failed_login_attempts, lock_out_until, last_failed_login
            FROM users 
            WHERE email = :email
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $userID = $user['user_id'];

            // --- INTEGER TIMESTAMP LOCKOUT CHECK ---
            if (!empty($user['lock_out_until'])) {
                $lockout_expires = (int)$user['lock_out_until'];
                if ($lockout_expires > $current_time) {
                    $remaining_seconds = $lockout_expires - $current_time;
                    $remaining_minutes = (int)ceil($remaining_seconds / 60);
                    header("Location: ../frontend/login.php?error=" . urlencode("Account is temporarily locked. Please try again in {$remaining_minutes} minute(s)."));
                    exit();
                } else {
                    // Lockout expired: reset lockout timestamp and counter
                    $resetLock = $conn->prepare("UPDATE users SET failed_login_attempts = 0, lock_out_until = NULL WHERE user_id = :userID");
                    $resetLock->execute([':userID' => $userID]);
                    $user['failed_login_attempts'] = 0;
                }
            }

            // --- PASSWORD VERIFICATION ---
            if (password_verify($user_pass, $user['hashed_password'])) {

                // Status Check
                if ($user['status'] === 'pending') {
                    header("Location: ../frontend/login.php?error=" . urlencode("Your account is not active."));
                    exit();
                } else if ($user['status'] === 'banned') {
                    header("Location: ../frontend/login.php?error=" . urlencode("Your account has been banned by an admin."));
                    exit();
                }

                // Retrieve Role
                $roleID = $user['role_id'];
                $user_type = $conn->prepare("SELECT role FROM roles WHERE role_id = :roleID");
                $user_type->execute([':roleID' => $roleID]);
                $type = $user_type->fetch(PDO::FETCH_ASSOC);
                $role = $type['role'] ?? 'user';

                if ($user['status'] === 'active') {
                    $otp_code   = sprintf("%06d", random_int(100000, 999999));
                    $otp_type   = 'login';
                    $expires_at = $current_time + 180; // 3 minutes

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

                    // Dispatch Mail
                    try {
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
                        $_SESSION['user_role']    = $role;
                        header("Location: ../frontend/otp-login.php");
                        exit();
                    } catch (Exception $mailException) {
                        $conn->rollBack();
                        error_log("Mail error: " . $mailException->getMessage());
                        header("Location: ../frontend/login.php?error=" . urlencode("Failed to send OTP email. Please try again."));
                        exit();
                    }
                }
            } else {
                // --- FAILED PASSWORD HANDLING ---
                $window_seconds = 15 * 60; // 15 minutes
                $last_failed = !empty($user['last_failed_login']) ? (int)$user['last_failed_login'] : 0;

                if (($current_time - $last_failed) > $window_seconds) {
                    $new_attempts = 1;
                } else {
                    $new_attempts = (int)$user['failed_login_attempts'] + 1;
                }

                // Log failure entry
                $insert_loginFailed = $conn->prepare("
                    INSERT INTO login_history (user_id, status) 
                    VALUES (:userID, 'wrong_password')
                ");
                $insert_loginFailed->execute([':userID' => $userID]);

                if ($new_attempts >= 3) {
                    // Lock account for 3 minutes
                    $lock_until = $current_time + 180;
                    
                    $update_lock = $conn->prepare("
                        UPDATE users 
                        SET failed_login_attempts = :attempts, 
                            lock_out_until = :lock_until, 
                            last_failed_login = :now 
                        WHERE user_id = :userID
                    ");
                    $update_lock->execute([
                        ':attempts'   => $new_attempts,
                        ':lock_until' => $lock_until,
                        ':now'        => $current_time,
                        ':userID'     => $userID
                    ]);

                    header("Location: ../frontend/login.php?error=" . urlencode("Too many failed attempts. Account locked for 3 minutes."));
                    exit();
                } else {
                    // Under 3 failed attempts
                    $update_attempts = $conn->prepare("
                        UPDATE users 
                        SET failed_login_attempts = :attempts, 
                            last_failed_login = :now 
                        WHERE user_id = :userID
                    ");
                    $update_attempts->execute([
                        ':attempts' => $new_attempts,
                        ':now'      => $current_time,
                        ':userID'   => $userID
                    ]);

                    $remaining = 3 - $new_attempts;
                    header("Location: ../frontend/login.php?error=" . urlencode("Invalid password. {$remaining} attempt(s) remaining before lockout."));
                    exit();
                }
            }
        } else {
            header("Location: ../frontend/login.php?error=" . urlencode("Invalid email."));
            exit();
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Login error: " . $e->getMessage());
        header("Location: ../frontend/login.php?error=" . urlencode("Authentication failed due to a server error."));
        exit();
    }
}
?>