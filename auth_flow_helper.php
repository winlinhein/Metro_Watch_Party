<?php

require_once __DIR__ . '/schema_upgrade_helper.php';

const NEXUS_REMEMBER_COOKIE = 'nexus_remember';
const NEXUS_REMEMBER_DAYS = 30;

function nexusStaffUserIds(PDO $conn): array
{
    try {
        $stmt = $conn->query("
            SELECT u.user_id
            FROM users u
            INNER JOIN roles r ON r.role_id = u.role_id
            WHERE LOWER(TRIM(r.role)) IN ('admin', 'moderator')
        ");
        $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
        if ($ids) {
            return array_values(array_unique(array_filter($ids)));
        }
    } catch (Throwable $ignore) {
    }

    try {
        $stmt = $conn->query("SELECT user_id FROM users WHERE role_id IN (1, 3)");
        return array_values(array_unique(array_filter(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []))));
    } catch (Throwable $ignore) {
    }

    return [];
}

function nexusNotifyStaff(PDO $conn, int $senderId, string $type, string $message): array
{
    $ids = nexusStaffUserIds($conn);
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at)
        VALUES (?, ?, ?, ?, 0, NOW())
    ");
    $notifs = [];
    foreach ($ids as $staffId) {
        if ($staffId <= 0) {
            continue;
        }
        $stmt->execute([$staffId, $senderId > 0 ? $senderId : null, $type, $message]);
        $notifs[$staffId] = (int)$conn->lastInsertId();
    }
    return $notifs;
}

function nexusKeepSessionCookie(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }
    $params = session_get_cookie_params();
    $lifetime = NEXUS_REMEMBER_DAYS * 86400;
    setcookie(session_name(), session_id(), [
        'expires' => time() + $lifetime,
        'path' => $params['path'] ?: '/',
        'domain' => $params['domain'] ?? '',
        'secure' => !empty($params['secure']) || nexusRequestIsHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function nexusRequestIsHttps(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    $forwarded = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    return $forwarded === 'https';
}

function nexusAppBaseUrl(): string
{
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    return (nexusRequestIsHttps() ? 'https' : 'http') . '://' . $host;
}

function nexusGoogleRedirectUri(): string
{
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/backend/google_callback.php'));
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($dir === '' || $dir === '.') {
        $dir = '/backend';
    }
    return nexusAppBaseUrl() . $dir . '/google_callback.php';
}

function nexusRoleName(PDO $conn, int $roleId): string
{
    try {
        $stmt = $conn->prepare("SELECT role FROM roles WHERE role_id = ? LIMIT 1");
        $stmt->execute([$roleId]);
        $role = strtolower(trim((string)$stmt->fetchColumn()));
        if ($role !== '') {
            return $role;
        }
    } catch (Throwable $ignore) {
    }
    return 'user';
}

function nexusDashboardPath(string $role): string
{
    $role = strtolower($role);
    if (in_array($role, ['admin', 'moderator'], true)) {
        return '/frontend/admin_dashboard.php';
    }
    return '/user/dashboard.php';
}

function nexusHoldIfBlocked(PDO $conn, array $user, string $role): ?string
{
    ensureAppSchema($conn);
    $userId = (int)($user['user_id'] ?? 0);
    $email = (string)($user['email'] ?? '');
    $status = strtolower((string)($user['status'] ?? ''));
    $deletionAt = $user['deletion_requested_at'] ?? null;

    if (!empty($deletionAt)) {
        if (function_exists('nexusDeletionExpired') && nexusDeletionExpired($deletionAt)) {
            if (function_exists('nexusPurgeUserAccount')) {
                nexusPurgeUserAccount($conn, $userId);
            }
            return '/frontend/login.php?error=' . urlencode('This account was permanently deleted after the 24-hour waiting period.');
        }
        $_SESSION['account_hold'] = [
            'user_id' => $userId,
            'email' => $email,
            'role' => $role,
            'mode' => 'deletion',
            'deletion_requested_at' => $deletionAt,
        ];
        return '/frontend/account_hold.php';
    }

    if ($status === 'banned') {
        $_SESSION['account_hold'] = [
            'user_id' => $userId,
            'email' => $email,
            'role' => $role,
            'mode' => 'banned',
            'ban_reason' => (string)($user['ban_reason'] ?? 'Violation of community guidelines'),
        ];
        return '/frontend/account_hold.php';
    }

    if ($status === 'pending') {
        return '/frontend/login.php?error=' . urlencode('Your account is not active.');
    }

    return null;
}

function nexusEstablishSession(array $user, string $role): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['authenticated'] = true;
    $_SESSION['user_id'] = (int)($user['user_id'] ?? 0);
    $_SESSION['user_name'] = (string)($user['user_name'] ?? 'User');
    $_SESSION['user_email'] = (string)($user['email'] ?? '');
    $_SESSION['user_role'] = $role;
    unset($_SESSION['account_hold'], $_SESSION['verify_email'], $_SESSION['otp_type']);
    nexusKeepSessionCookie();
}

function nexusCreatePersistentSession(PDO $conn, int $userId): void
{
    if ($userId <= 0) {
        return;
    }
    ensureAppSchema($conn);
    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $expires = time() + (NEXUS_REMEMBER_DAYS * 86400);
    $stmt = $conn->prepare("
        INSERT INTO persistent_session (user_id, selector, hashed_validator, device_info, ip_address, expired_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId,
        $selector,
        hash('sha256', $validator),
        substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 180),
        substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64),
        $expires,
    ]);
    nexusSetRememberCookie($selector . ':' . $validator, $expires);
}

function nexusSetRememberCookie(string $value, int $expires): void
{
    setcookie(NEXUS_REMEMBER_COOKIE, $value, [
        'expires' => $expires,
        'path' => '/',
        'secure' => nexusRequestIsHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function nexusClearPersistentSession(?PDO $conn = null, ?int $userId = null): void
{
    $raw = (string)($_COOKIE[NEXUS_REMEMBER_COOKIE] ?? '');
    if ($conn instanceof PDO) {
        try {
            if ($raw !== '' && str_contains($raw, ':')) {
                [$selector] = explode(':', $raw, 2);
                $conn->prepare("DELETE FROM persistent_session WHERE selector = ?")->execute([$selector]);
            } elseif ($userId) {
                $conn->prepare("DELETE FROM persistent_session WHERE user_id = ?")->execute([$userId]);
            }
        } catch (Throwable $ignore) {
        }
    }
    if (isset($_COOKIE[NEXUS_REMEMBER_COOKIE])) {
        unset($_COOKIE[NEXUS_REMEMBER_COOKIE]);
    }
    setcookie(NEXUS_REMEMBER_COOKIE, '', [
        'expires' => time() - 42000,
        'path' => '/',
        'secure' => nexusRequestIsHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function nexusResumePersistentLogin(PDO $conn): ?string
{
    if (!empty($_SESSION['authenticated']) && !empty($_SESSION['user_id'])) {
        nexusKeepSessionCookie();
        return null;
    }

    $raw = (string)($_COOKIE[NEXUS_REMEMBER_COOKIE] ?? '');
    if ($raw === '' || !str_contains($raw, ':')) {
        return null;
    }

    [$selector, $validator] = explode(':', $raw, 2);
    if ($selector === '' || $validator === '') {
        nexusClearPersistentSession($conn);
        return null;
    }

    try {
        ensureAppSchema($conn);
        $stmt = $conn->prepare("
            SELECT ps.hashed_validator, ps.expired_at, u.*
            FROM persistent_session ps
            INNER JOIN users u ON u.user_id = ps.user_id
            WHERE ps.selector = ?
            LIMIT 1
        ");
        $stmt->execute([$selector]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int)$row['expired_at'] < time() || !hash_equals((string)$row['hashed_validator'], hash('sha256', $validator))) {
            nexusClearPersistentSession($conn);
            return null;
        }

        $role = nexusRoleName($conn, (int)($row['role_id'] ?? 0));
        $blocked = nexusHoldIfBlocked($conn, $row, $role);
        if ($blocked) {
            nexusClearPersistentSession($conn, (int)$row['user_id']);
            return $blocked;
        }

        nexusEstablishSession($row, $role);
        if ($role === 'user') {
            require_once __DIR__ . '/user_backend/mission_progress.php';
            nexusAwardDailyLogin((int)$row['user_id']);
        }
        $conn->prepare("UPDATE persistent_session SET expired_at = ? WHERE selector = ?")
            ->execute([time() + (NEXUS_REMEMBER_DAYS * 86400), $selector]);
        nexusSetRememberCookie($raw, time() + (NEXUS_REMEMBER_DAYS * 86400));
    } catch (Throwable $e) {
        error_log('nexusResumePersistentLogin: ' . $e->getMessage());
    }

    return null;
}

function nexusFinishLogin(PDO $conn, array $user, bool $remember = true): string
{
    $role = nexusRoleName($conn, (int)($user['role_id'] ?? 0));
    $blocked = nexusHoldIfBlocked($conn, $user, $role);
    if ($blocked) {
        return $blocked;
    }

    nexusEstablishSession($user, $role);
    if ($role === 'user') {
        require_once __DIR__ . '/user_backend/mission_progress.php';
        nexusAwardDailyLogin((int)($user['user_id'] ?? 0));
    }
    if ($remember) {
        try {
            nexusClearPersistentSession($conn, (int)$user['user_id']);
            nexusCreatePersistentSession($conn, (int)$user['user_id']);
        } catch (Throwable $e) {
            error_log('nexusFinishLogin persist: ' . $e->getMessage());
        }
    }

    return nexusDashboardPath($role) . '?success=' . urlencode('Successfully logged in');
}
