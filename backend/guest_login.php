<?php
session_start();

// Optional: clear any existing session to avoid mixing roles
$_SESSION = [];

// Set guest session
$_SESSION['authenticated'] = true;
$_SESSION['user_role']      = 'guest';
$_SESSION['user_name']      = 'Guest';
$_SESSION['user_email']     = null;
$_SESSION['user_id']        = null;   

// Regenerate session ID for security
session_regenerate_id(true);

// Redirect to the user dashboard
header('Location: ../user/dashboard.php');
exit();