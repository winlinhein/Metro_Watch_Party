<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../../account_lifecycle_helper.php';
require_once __DIR__ . '/../../auth_flow_helper.php';

if (empty($_SESSION['authenticated']) || empty($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/../../conn.php';
        $blocked = nexusResumePersistentLogin($conn);
        if ($blocked) {
            header('Location: ' . $blocked);
            exit();
        }
    } catch (Throwable $ignore) {
    }
} else {
    nexusKeepSessionCookie();
}
