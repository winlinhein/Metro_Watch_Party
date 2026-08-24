<?php
// otp-login_backend.php - Dedicated 2FA Verification Endpoint with Integer Lockout Support
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
        $stmt_user = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt_user->execute([':email' => $email]);
        $user_record = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if (!$user_record) {
            header("Location: ../frontend/login.php?error=" . urlencode("User record not found."));
            exit();
        }

        $userID = $user_record['user_id'];

        // --- INTEGER TIMESTAMP LOCKOUT CHECK ---
        if (!empty($user_record['lock_out_until'])) {
            $lockout_expires = (int)$user_record['lock_out_until'];

            if ($lockout_expires > $current_time) {
                $remaining_seconds = $lockout_expires - $current_time;
                $remaining_minutes = (int)ceil($remaining_seconds / 60);
                header("Location: ../frontend/login.php?error=" . urlencode("Account is locked. Please try again in {$remaining_minutes} minute(s)."));
                exit();
            }
        }

        $stmt_otp = $conn->prepare("
            SELECT * FROM otp_verification 
            WHERE email = :email AND otp_type = 'login' 
            ORDER BY expires_at DESC LIMIT 1
        ");
        $stmt_otp->execute([':email' => $email]);
        $otp_record = $stmt_otp->fetch(PDO::FETCH_ASSOC);

        if (!$otp_record) {
            header("Location: ../frontend/otp-login.php?error=" . urlencode("No pending verification code found. Please request a new one."));
            exit();
        }

        $stored_otp     = $otp_record['otp_code'];
        $stored_expires = (int)$otp_record['expires_at'];

        // Helper to process OTP failure & trigger lockout if limit reached
        $handleFailedOtp = function($status, $errorMsg) use ($conn, $userID, $user_record, $email, $current_time) {
            $new_attempts = (int)$user_record['failed_login_attempts'] + 1;

            $insert_loginFailed = $conn->prepare("
                INSERT INTO login_history (user_id, status) 
                VALUES (:userID, :status)
            ");
            $insert_loginFailed->execute([
                ':userID' => $userID,
                ':status' => $status
            ]);

            if ($new_attempts >= 3) {
                $lock_until = $current_time + 180; // 3 minutes lockout
                $update_lock = $conn->prepare("
                    UPDATE users 
                    SET failed_login_attempts = :attempts, lock_out_until = :lock_until 
                    WHERE user_id = :userID
                ");
                $update_lock->execute([
                    ':attempts'   => $new_attempts,
                    ':lock_until' => $lock_until,
                    ':userID'     => $userID
                ]);

                $conn->prepare("DELETE FROM otp_verification WHERE email = :email AND otp_type = 'login'")
                     ->execute([':email' => $email]);

                header("Location: ../frontend/login.php?error=" . urlencode("Too many failed attempts. Account locked for 3 minutes."));
                exit();
            } else {
                $update_attempts = $conn->prepare("
                    UPDATE users SET failed_login_attempts = :attempts WHERE user_id = :userID
                ");
                $update_attempts->execute([
                    ':attempts' => $new_attempts,
                    ':userID'   => $userID
                ]);

                $remaining = 3 - $new_attempts;
                header("Location: ../frontend/otp-login.php?error=" . urlencode("{$errorMsg} {$remaining} attempt(s) remaining."));
                exit();
            }
        };

        // Check expired OTP
        if ($current_time > $stored_expires) {
            $handleFailedOtp('otp_expired', 'Your verification code has expired.');
        }

        // Check invalid OTP
        if ($otp !== $stored_otp) {
            $handleFailedOtp('wrong_otp', 'Invalid verification code.');
        }

        // --- SUCCESSFUL VERIFICATION ---
        $conn->beginTransaction();
        
        // Remove used OTP
        $delete_otp = $conn->prepare("DELETE FROM otp_verification WHERE email = :email AND otp_type = 'login'");
        $delete_otp->execute([':email' => $email]);

        // Log success
        $insert_loginSuccess = $conn->prepare("
            INSERT INTO login_history (user_id, status) 
            VALUES (:userID, 'success')
        ");
        $insert_loginSuccess->execute([':userID' => $userID]);

        // Upon successful login or OTP verification:
        $reset_lockout = $conn->prepare("
            UPDATE users 
            SET failed_login_attempts = 0, 
                lock_out_until = NULL, 
                last_failed_login = NULL 
            WHERE user_id = :userID
        ");
        $reset_lockout->execute([':userID' => $userID]);

        $conn->commit();

        /// Clear temporary verification state
        unset($_SESSION['verify_email']);

        // Store permanent session details
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id']       = $user_record['user_id'];
        $_SESSION['user_name']     = $user_record['user_name']; 
        $_SESSION['user_email']    = $user_record['email'];
        $_SESSION['user_role']     = $role;

        if ($role === 'admin') {
            header("Location: ../frontend/admin_dashboard.php?success=" . urlencode("Welcome to Admin Dashboard"));
            exit();
        } else {
            header("Location: ../user/dashboard.php?success=" . urlencode("Successfully logged in"));
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