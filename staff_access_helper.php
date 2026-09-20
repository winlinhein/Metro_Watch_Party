<?php

function nexusNormalizeStaffRole(?string $role): string
{
    $role = strtolower(trim((string)$role));
    if ($role === 'premium' || $role === 'standard' || $role === '') {
        return 'user';
    }
    return $role;
}

function nexusLookupUserStaff(PDO $conn, int $userId): ?array
{
    if ($userId <= 0) {
        return null;
    }
    $stmt = $conn->prepare("
        SELECT
            u.user_id,
            LOWER(TRIM(IFNULL(u.status, 'active'))) AS status,
            LOWER(TRIM(IFNULL(r.role, 'user'))) AS role
        FROM users u
        LEFT JOIN roles r ON r.role_id = u.role_id
        WHERE u.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    return [
        'user_id' => (int)$row['user_id'],
        'status' => (string)$row['status'],
        'role' => nexusNormalizeStaffRole($row['role'] ?? 'user'),
    ];
}

function nexusCanManageStaffTarget(string $actorRole, int $actorId, array $target): bool
{
    $actorRole = nexusNormalizeStaffRole($actorRole);
    $targetId = (int)($target['user_id'] ?? 0);
    $targetRole = nexusNormalizeStaffRole($target['role'] ?? 'user');
    if ($targetId <= 0 || $actorId <= 0 || $targetId === $actorId) {
        return false;
    }
    if ($targetRole === 'admin') {
        return false;
    }
    if ($actorRole === 'admin') {
        return in_array($targetRole, ['user', 'moderator'], true);
    }
    if ($actorRole === 'moderator') {
        return $targetRole === 'user';
    }
    return false;
}
