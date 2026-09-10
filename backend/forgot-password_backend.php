<?php
// register_backend.php
session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../mail_helper.php';

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

                sendOtpEmail($email, $otp_code, 'forgot');
                $conn->commit();

                $_SESSION['verify_email'] = $email;
                $_SESSION['otp_type'] = 'forgot';
                header("Location: ../frontend/otp-forgot.php");
                exit();

            } else{
                header("Location: ../frontend/forgot-password.php?error=" . urlencode('email is not registered.'));
                exit();

            }
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('Forgot password mail error: ' . $e->getMessage());
            header("Location: ../frontend/forgot-password.php?error=" . urlencode(mailUserError($e)));
            exit();
        }
    } else {
        $firstErr = $emailErr;
        header("Location: ../frontend/forgot-password.php?error=" . urlencode($firstErr));
        exit();
    }
}
?>