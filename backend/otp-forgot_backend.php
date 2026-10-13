<?php
// otp-forgot_backend.php - Verifies the password-reset OTP and sets the new password
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../otp_helper.php';

function test_input($data) {
    return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
}

function nexusPasswordPolicyError(string $pwd): string
{
    if (
        strlen($pwd) < 8 ||
        !preg_match('/[A-Z]/', $pwd) ||
        !preg_match('/[a-z]/', $pwd) ||
        !preg_match('/[0-9]/', $pwd) ||
        !preg_match('/[!@#$%^&*(),.?":{}|]/', $pwd)
    ) {
        return "Password does not meet complexity requirements";
    }
    return '';
}

// 1. Retrieve email set during the forgot-password request
$email = $_SESSION['verify_email'] ?? '';
$otpErr = "";

// Force redirect if no active reset session exists
if (empty($email)) {
    header("Location: ../frontend/forgot-password.php?error=" . urlencode("Session expired. Please request a new code."));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_time = time();

    // 2. OTP validation
    if (empty($_POST['otp'])) {
        $otpErr = "Verification code is required.";
    } elseif (!preg_match("/^[0-9]{6}$/", $_POST['otp'])) {
        $otpErr = "Verification code must be a 6-digit number.";
    } else {
        $otp = test_input($_POST['otp']);
    }

    if (!empty($otpErr)) {
        header("Location: ../frontend/otp-forgot.php?error=" . urlencode($otpErr));
        exit();
    }

    // 3. New-password validation
    $newPassword     = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($newPassword === '' || $confirmPassword === '') {
        header("Location: ../frontend/otp-forgot.php?error=" . urlencode("Please enter and confirm your new password."));
        exit();
    }

    $policyErr = nexusPasswordPolicyError($newPassword);
    if ($policyErr !== '') {
        header("Location: ../frontend/otp-forgot.php?error=" . urlencode($policyErr));
        exit();
    }

    if (!hash_equals($newPassword, $confirmPassword)) {
        header("Location: ../frontend/otp-forgot.php?error=" . urlencode("The two passwords do not match."));
        exit();
    }

    try {
        // Brute-force guard: refuse once the attempt allowance is exhausted.
        if (nexusOtpIsLockedOut($conn, $email, 'forgot')) {
            header("Location: ../frontend/otp-forgot.php?error=" . urlencode("Too many incorrect codes. Please request a new reset code."));
            exit();
        }

        // 4. Query `otp_verification` for the reset OTP
        $stmt = $conn->prepare("
            SELECT * FROM otp_verification 
            WHERE email = :email AND otp_type = 'forgot' 
            ORDER BY expires_at DESC LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp_record) {
            header("Location: ../frontend/otp-forgot.php?error=" . urlencode("No pending reset code found. Please request a new one."));
            exit();
        }

        $stored_otp     = $otp_record['otp_code'];
        $stored_expires = (int)$otp_record['expires_at'];

        // 5. Expiration check
        if ($current_time > $stored_expires) {
            $conn->prepare("DELETE FROM otp_verification WHERE email = :email AND otp_type = 'forgot'")
                 ->execute([':email' => $email]);

            header("Location: ../frontend/otp-forgot.php?error=" . urlencode("Your reset code has expired. Please request a new one."));
            exit();
        }

        // 6. Invalid code check
        if (!hash_equals((string)$stored_otp, (string)$otp)) {
            nexusOtpRegisterFailure($conn, $email, 'forgot');
            header("Location: ../frontend/otp-forgot.php?error=" . urlencode("Invalid verification code. Please try again."));
            exit();
        }

        // 7. Set the new password
        $conn->beginTransaction();

        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $conn->prepare("
            UPDATE users
            SET hashed_password = :hash,
                failed_login_attempts = 0,
                lock_out_until = NULL,
                last_failed_login = NULL,
                updated_at = NOW()
            WHERE email = :email
        ");
        $update->execute([':hash' => $hashed, ':email' => $email]);

        if ($update->rowCount() < 1) {
            $conn->rollBack();
            header("Location: ../frontend/forgot-password.php?error=" . urlencode("No account found for this email address."));
            exit();
        }

        // Consume the OTP so it cannot be replayed
        $conn->prepare("DELETE FROM otp_verification WHERE email = :email AND otp_type = 'forgot'")
             ->execute([':email' => $email]);

        $conn->commit();

        unset($_SESSION['verify_email'], $_SESSION['otp_type']);

        header("Location: ../frontend/login.php?success=" . urlencode("Your password has been reset. Please sign in with your new password."));
        exit();

    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('forgot password reset failed: ' . $e->getMessage());
        header("Location: ../frontend/otp-forgot.php?error=" . urlencode("Could not reset your password right now. Please try again."));
        exit();
    }
}
?>