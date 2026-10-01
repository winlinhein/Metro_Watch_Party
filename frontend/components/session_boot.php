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
    try {
        require_once __DIR__ . '/../../conn.php';
        $blocked = nexusGuardAuthenticatedSession($conn);
        if ($blocked) {
            header('Location: ' . $blocked);
            exit();
        }
        require_once __DIR__ . '/../../admin_rooms_helper.php';
        sweepAbandonedRooms($conn);
    } catch (Throwable $ignore) {
    }
}
