<?php

require_once __DIR__ . '/schema_upgrade_helper.php';

function nexusFormatPointPackage(array $row): array
{
    return [
        'id' => (int)($row['package_id'] ?? $row['id'] ?? 0),
        'name' => (string)($row['name'] ?? ''),
        'label' => (string)($row['label'] ?? ''),
        'points' => (int)($row['points'] ?? 0),
        'price' => (float)($row['price'] ?? 0),
        'best' => !empty($row['is_best']) || !empty($row['best']),
        'sort_order' => (int)($row['sort_order'] ?? 0),
        'is_active' => isset($row['is_active']) ? (int)$row['is_active'] === 1 : true,
    ];
}

function nexusListPointPackages(PDO $conn, bool $activeOnly = true): array
{
    ensureAppSchema($conn);
    $sql = "
        SELECT package_id, name, label, points, price, is_best, sort_order, is_active
        FROM point_packages
    ";
    if ($activeOnly) {
        $sql .= " WHERE is_active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, package_id ASC";
    try {
        $rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
    return array_map('nexusFormatPointPackage', $rows);
}

function nexusGetPointPackage(PDO $conn, int $packageId, bool $activeOnly = true): ?array
{
    if ($packageId <= 0) {
        return null;
    }
    ensureAppSchema($conn);
    $sql = "
        SELECT package_id, name, label, points, price, is_best, sort_order, is_active
        FROM point_packages
        WHERE package_id = ?
    ";
    if ($activeOnly) {
        $sql .= " AND is_active = 1";
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute([$packageId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? nexusFormatPointPackage($row) : null;
}
