<?php

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../staff_access_helper.php';

$actorRole = nexusNormalizeStaffRole($_SESSION['user_role'] ?? '');
$actorId = (int)($_SESSION['user_id'] ?? 0);

if (
    empty($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true ||
    !in_array($actorRole, ['admin', 'moderator'], true) ||
    $actorId <= 0
) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Staff privileges required.',
    ]);
    exit;
}

session_write_close();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'POST requests only.',
    ]);
    exit;
}

$data = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = [];
}

$action = (string)($data['action'] ?? '');
$userId = (int)($data['id'] ?? 0);

function nexusRejectStaffAction(string $error, int $code = 403): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

if ($action === 'demote_admin') {
    nexusRejectStaffAction('Admins cannot act on other admins.');
}

if ($userId <= 0) {
    nexusRejectStaffAction('Invalid user ID.', 400);
}

$target = nexusLookupUserStaff($conn, $userId);
if (!$target) {
    nexusRejectStaffAction('User not found.', 404);
}

if (!nexusCanManageStaffTarget($actorRole, $actorId, $target)) {
    if (($target['role'] ?? '') === 'admin') {
        nexusRejectStaffAction('Admins cannot act on other admins.');
    }
    nexusRejectStaffAction('You can only manage users' . ($actorRole === 'admin' ? ' and moderators' : '') . '.');
}

$targetRole = $target['role'];
$targetStatus = $target['status'];
$isPending = $targetStatus === 'pending';

if ($isPending && in_array($action, ['promote_moderator', 'demote_moderator', 'ban'], true)) {
    nexusRejectStaffAction('Pending accounts can only be deleted.', 400);
}

if ($action === 'promote_moderator') {
    if ($targetRole !== 'user') {
        nexusRejectStaffAction('Only standard users can be promoted.', 400);
    }

    $stmt = $conn->prepare("UPDATE users SET role_id = 3 WHERE user_id = ?");
    if ($stmt->execute([$userId])) {
        echo json_encode(['success' => true, 'message' => 'User promoted to Moderator.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error occurred while promoting.']);
    }
    exit;
}

if ($action === 'demote_moderator') {
    if ($targetRole !== 'moderator') {
        nexusRejectStaffAction('Only moderators can be demoted.', 400);
    }

    $stmt = $conn->prepare("UPDATE users SET role_id = 2 WHERE user_id = ?");
    if ($stmt->execute([$userId])) {
        require_once __DIR__ . '/../profile_media_helper.php';
        nexusRevertUnearnedStaffBorder($conn, $userId);
        echo json_encode(['success' => true, 'message' => 'Moderator demoted to User.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error occurred while demoting.']);
    }
    exit;
}

if ($action === 'ban') {
    $reason = $data['reason'] ?? 'Violation of terms';
    $notes = $data['notes'] ?? '';

    require_once __DIR__ . '/../schema_upgrade_helper.php';
    ensureAppSchema($conn);
    $banReason = trim((string)$reason);
    if ($notes !== '') {
        $banReason = $banReason !== '' && $banReason !== 'Violation of terms'
            ? $banReason . ' — ' . trim((string)$notes)
            : trim((string)$notes);
    }
    if ($banReason === '') {
        $banReason = 'Violation of community guidelines';
    }

    $stmt = $conn->prepare("UPDATE users SET status = 'banned', ban_reason = ? WHERE user_id = ?");
    if ($stmt->execute([$banReason, $userId])) {
        triggerPusherEvent("user-{$userId}", 'force_logout', [
            'message' => 'Your account has been banned. Reason: ' . htmlspecialchars((string)$reason),
        ]);
        echo json_encode(['success' => true, 'message' => 'User banned and disconnected.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to ban user in the database.']);
    }
    exit;
}

if ($action === 'unban') {
    require_once __DIR__ . '/../account_lifecycle_helper.php';
    try {
        nexusUnbanUser($conn, $userId);
        echo json_encode(['success' => true, 'message' => 'Account restored to active.']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Failed to restore account.']);
    }
    exit;
}

if ($action === 'delete') {
    if (!$isPending) {
        nexusRejectStaffAction('Only pending accounts can be deleted.', 400);
    }
    require_once __DIR__ . '/../account_lifecycle_helper.php';
    try {
        nexusPurgeUserAccount($conn, $userId);
        echo json_encode(['success' => true, 'message' => 'Pending account deleted.']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Failed to delete account.']);
    }
    exit;
}

echo json_encode([
    'success' => false,
    'error' => 'Invalid request method or action.',
]);
