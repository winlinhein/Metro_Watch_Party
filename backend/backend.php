<?php
// backend.php - Handles all backend POST requests

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = urlencode('Please fill in all fields.');
            header("Location: ../frontend/index.php?error=$error");
            exit;
        } else {
            // Redirect to OTP login page
            header("Location: ../frontend/otp-login.php");
            exit;
        }
    }
} elseif ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $terms = $_POST['terms'] ?? '';
        
        if (empty($name) || empty($email) || empty($password) || empty($terms)) {
            $error = urlencode('Please fill in all required fields.');
            header("Location: ../frontend/register.php?error=$error");
            exit;
        } else {
            // Mock successful registration
            $error = urlencode('Account creation simulated successfully.');
            header("Location: ../frontend/register.php?error=$error");
            exit;
        }
    }
} elseif ($action === 'forgot_password') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = $_POST['email'] ?? '';
        
        if (empty($email)) {
            $error = urlencode('Please enter your email address.');
            header("Location: ../frontend/forgot-password.php?error=$error");
            exit;
        } else {
            // Mock successful reset link sent, redirecting to OTP
            header("Location: ../frontend/otp-forgot.php");
            exit;
        }
    }
} elseif ($action === 'verify_otp_login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $otp = $_POST['otp'] ?? '';
        if (empty($otp) || strlen($otp) !== 6) {
            $error = urlencode('Please enter a valid 6-digit code.');
            header("Location: ../frontend/otp-login.php?error=$error");
            exit;
        } else {
            // Mock successful 2FA
            $error = urlencode('2FA successful! (Mock PHP response)');
            header("Location: ../frontend/otp-login.php?error=$error");
            exit;
        }
    }
} elseif ($action === 'verify_otp_forgot') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $otp = $_POST['otp'] ?? '';
        if (empty($otp) || strlen($otp) !== 6) {
            $error = urlencode('Please enter a valid 6-digit code.');
            header("Location: ../frontend/otp-forgot.php?error=$error");
            exit;
        } else {
            // Mock successful reset code
            $error = urlencode('Code verified! (Mock PHP response)');
            header("Location: ../frontend/otp-forgot.php?error=$error");
            exit;
        }
    }
}

// Redirect back to frontend if accessed directly without valid action
header("Location: ../frontend/index.php");
exit;
?>
