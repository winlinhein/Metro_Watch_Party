<?php
// otp-login_backend.php - Dedicated 2FA Verification Endpoint
session_start();
require_once __DIR__ . '/../conn.php';

function test_input($data) {
    return htmlspecialchars(trim(stripslashes($data)), ENT_QUOTES, 'UTF-8');
}

$email = $_SESSION['verify_email'] ?? '';
$role  = $_SESSION['user_role'] ?? '';
$otpErr = "";

if (empty($email)) {
    header("Location: ../frontend/login.php?error=" . urlencode("Session expired. Please log in again."));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_time = time();

    if (empty($_POST['otp'])) {
        $otpErr = "Verification code is required.";
    } elseif (!preg_match("/^[0-9]{6}$/", $_POST['otp'])) {
        $otpErr = "Verification code must be 6 digits.";
    } else {
        $otp = test_input($_POST['otp']);
    }

    if (!empty($otpErr)) {
        header("Location: ../frontend/otp-login.php?error=" . urlencode($otpErr));
        exit();
    }

    try {
        $stmt = $conn->prepare("
            SELECT * FROM otp_verification 
            WHERE email = :email AND otp_type = 'login' 
            ORDER BY expires_at DESC LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $otp_record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp_record) {
            header("Location: ../frontend/otp-login.php?error=" . urlencode("No pending verification code found. Please request a new one."));
            exit();
        }

        $stored_otp     = $otp_record['otp_code'];
        $stored_expires = (int)$otp_record['expires_at'];

        if ($current_time > $stored_expires) {
            $conn->prepare("DELETE FROM otp_verification WHERE email = :email AND otp_type = 'login'")
                 ->execute([':email' => $email]);

            header("Location: ../frontend/otp-login.php?error=" . urlencode("Your verification code has expired. Please log in again."));
            exit();
        }

        if ($otp !== $stored_otp) {
            header("Location: ../frontend/otp-login.php?error=" . urlencode("Invalid verification code. Please try again."));
            exit();
        }

        // Execute transaction
        $conn->beginTransaction();
        
        // Remove used OTP
        $delete_otp = $conn->prepare("DELETE FROM otp_verification WHERE email = :email AND otp_type = 'login'");
        $delete_otp->execute([':email' => $email]);
        
        // FIX: Commit transaction before session clearing and redirection
        $conn->commit();

        // Clear temporary verification state and set active session
        unset($_SESSION['verify_email']);
        $_SESSION['authenticated'] = true;

        if ($role === 'admin') {
            header("Location: ../frontend/admin_dashboard.php?success=" . urlencode("Welcome to Admin Dashboard"));
            exit();
        } else {
            header("Location: ../frontend/login.php?success=" . urlencode("Successfully logged in"));
            exit();
        }

    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        header("Location: ../frontend/otp-login.php?error=" . urlencode("Database error during verification."));
        exit();
    }
}
?>