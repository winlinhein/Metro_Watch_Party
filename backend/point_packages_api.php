<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../point_pack_helper.php';

header('Content-Type: application/json');

$role = strtolower((string)($_SESSION['user_role'] ?? ''));
if (
    empty($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true ||
    !in_array($role, ['admin', 'moderator'], true)
) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

session_write_close();
ensureAppSchema($conn);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function nexusBroadcastPointPacks(string $action, $payload = null): void
{
    triggerPusherEvent('shop-updates', 'point_packs_changed', [
        'action' => $action,
        'item' => $payload,
        'item_id' => is_array($payload) ? ($payload['id'] ?? null) : $payload,
    ]);
}

switch ($action) {
    case 'list':
        echo json_encode(['success' => true, 'items' => nexusListPointPackages($conn, false)]);
        exit;

    case 'create':
        $name = trim((string)($_POST['name'] ?? ''));
        $label = trim((string)($_POST['label'] ?? ''));
        $points = (int)($_POST['points'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $best = !empty($_POST['best']) ? 1 : 0;
        $active = isset($_POST['is_active']) ? ((int)$_POST['is_active'] ? 1 : 0) : 1;
        if ($name === '' || $points <= 0 || $price <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Name, points, and price are required']);
            exit;
        }
        if ($label === '') {
            $label = $name;
        }
        $sort = (int)($conn->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM point_packages")->fetchColumn() ?: 1);
        $stmt = $conn->prepare("
            INSERT INTO point_packages (name, label, points, price, is_best, sort_order, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $label, $points, $price, $best, $sort, $active]);
        $item = nexusGetPointPackage($conn, (int)$conn->lastInsertId(), false);
        nexusBroadcastPointPacks('create', $item);
        echo json_encode(['success' => true, 'item' => $item]);
        exit;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $label = trim((string)($_POST['label'] ?? ''));
        $points = (int)($_POST['points'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $best = !empty($_POST['best']) ? 1 : 0;
        $active = isset($_POST['is_active']) ? ((int)$_POST['is_active'] ? 1 : 0) : 1;
        if ($id <= 0 || $name === '' || $points <= 0 || $price <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }
        if ($label === '') {
            $label = $name;
        }
        $stmt = $conn->prepare("
            UPDATE point_packages
            SET name = ?, label = ?, points = ?, price = ?, is_best = ?, is_active = ?
            WHERE package_id = ?
        ");
        $stmt->execute([$name, $label, $points, $price, $best, $active, $id]);
        $item = nexusGetPointPackage($conn, $id, false);
        nexusBroadcastPointPacks('update', $item);
        echo json_encode(['success' => true, 'item' => $item]);
        exit;

    case 'delete':
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM point_packages WHERE package_id = ?");
        $stmt->execute([$id]);
        nexusBroadcastPointPacks('delete', $id);
        echo json_encode(['success' => true]);
        exit;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
}
