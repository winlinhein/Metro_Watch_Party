<?php
// otp-register_backend.php - Dedicated Account Activation Endpoint
session_start();
require_once __DIR__ . '/../conn.php';

function test_input($data) {
    return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
}

// 1. Retrieve email set during registration
$email = $_SESSION['verify_email'] ?? '';
$otpErr = "";

// Force redirect if no active registration session exists
if (empty($email)) {
    header("Location: ../frontend/forgot-password.php?error=" . urlencode("Session expired. Please register again."));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_time = time();

    // 2. Input Validation
    if (empty($_POST['otp'])) {
        $otpErr = "Activation code is required.";
    } elseif (!preg_match("/^[0-9]{6}$/", $_POST['otp'])) {
        $otpErr = "Activation code must be a 6-digit number.";
    } else {
        $otp = test_input($_POST['otp']);
    }

    if (!empty($otpErr)) {
        header("Location: ../frontend/otp-forgot.php?error=" . urlencode($otpErr));
        exit();
    }

    try {
        // 3. Query `otp_verification` for registration OTP
        $stmt = $conn->prepare("
            SELECT * FROM otp_verification 
            WHERE email = :email AND otp_type = 'forgot' 
            ORDER BY expires_at DESC LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp_record) {
            header("Location: ../frontend/otp-forgot.php?error=" . urlencode("No pending activation code found. Please request a new one."));
            exit();
        }

        $stored_otp     = $otp_record['otp_code'];
        $stored_expires = (int)$otp_record['expires_at'];

        // 4. Expiration Check
        if ($current_time > $stored_expires) {
            $conn->prepare("DELETE FROM otp_verification WHERE email = :email AND otp_type = 'login'")
                 ->execute([':email' => $email]);

            header("Location: ../frontend/otp-forgot.php?error=" . urlencode("Your activation code has expired. Please register or request a new code."));
            exit();
        }

        // 5. Invalid Code Check
        if ($otp !== $stored_otp) {
            header("Location: ../frontend/otp-forgot.php?error=" . urlencode("Invalid verification code. Please try again."));
            exit();
        }

        // 6. Execute Account Activation Transaction
        $conn->beginTransaction();
        // Step C: Clear verification session variable
        unset($_SESSION['verify_email']);

        // Step D: Redirect to login view with success notification
        header("Location: ../frontend/login.php?success=" . urlencode("You are now changed"));
        exit();

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        header("Location: ../frontend/otp-login.php?error=" . urlencode("Database error during account activation."));
        exit();
    }
}
?>