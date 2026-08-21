<?php
// otp-register_backend.php - Forgot Password OTP Verification Endpoint
ob_start(); // Prevent "headers already sent" errors
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../conn.php';

function test_input($data) {
    return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
}

// 1. Retrieve email set during forgot password request
$email = $_SESSION['verify_email'] ?? '';
$otpErr = "";

// Force redirect if no active forgot password session exists
if (empty($email)) {
    header("Location: ../frontend/forgot-password.php?error=" . urlencode("Session expired. Please request a new code."));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_time = time();

    // 2. Input Validation
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

    try {
        // 3. Query `otp_verification` for forgot password OTP
        $stmt = $conn->prepare("
            SELECT * FROM otp_verification 
            WHERE email = :email AND otp_type = 'forgot' 
            ORDER BY expires_at DESC LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp_record) {
            header("Location: ../frontend/otp-forgot.php?error=" . urlencode("No pending verification code found. Please request a new one."));
            exit();
        }

        $stored_otp     = $otp_record['otp_code'];
        $stored_expires = (int)$otp_record['expires_at'];

        // 4. Expiration Check
        if ($current_time > $stored_expires) {
            // Delete expired OTP
            $conn->prepare("DELETE FROM otp_verification WHERE email = :email AND otp_type = 'forgot'")
                 ->execute([':email' => $email]);

            header("Location: ../frontend/otp-forgot.php?error=" . urlencode("Your verification code has expired. Please request a new one."));
            exit();
        }

        // 5. Invalid Code Check
        if ($otp !== $stored_otp) {
            header("Location: ../frontend/otp-forgot.php?error=" . urlencode("Invalid verification code. Please try again."));
            exit();
        }

        // 6. Successful Verification – Clear OTP and session
        $conn->prepare("DELETE FROM otp_verification WHERE email = :email AND otp_type = 'forgot'")
             ->execute([':email' => $email]);

        // Clear temporary verification state
        unset($_SESSION['verify_email']);

        // Redirect to password reset page (or login with success)
        // You may want to set a session variable to allow password reset
        $_SESSION['reset_email'] = $email; // optional, for the reset form
        header("Location: ../frontend/reset-password.php?success=" . urlencode("Verification successful. You can now reset your password."));
        exit();

    } catch (PDOException $e) {
        error_log("OTP Forgot Error: " . $e->getMessage());
        header("Location: ../frontend/otp-forgot.php?error=" . urlencode("Database error during verification. Please try again."));
        exit();
    }
}
?>