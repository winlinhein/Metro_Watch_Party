<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

header('Content-Type: application/json');

// Admin check
if (
    empty($_SESSION['authenticated']) || 
    $_SESSION['authenticated'] !== true || 
    empty($_SESSION['user_role']) || 
    !in_array($_SESSION['user_role'], ['admin', 'moderator'])
) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $stmt = $conn->query("SELECT item_id, item_name, point_cost, rarity, image_url, category FROM shop_items ORDER BY item_id DESC");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = array_map(function($item) {
            return [
                'id'     => $item['item_id'],
                'name'   => $item['item_name'],
                'price'  => (int)$item['point_cost'],
                'rarity' => $item['rarity'],
                'image'  => $item['image_url'] ?: '',
                'category' => $item['category']
            ];
        }, $items);
        echo json_encode(['success' => true, 'items' => $result]);
        exit;
        break;

    case 'create':
        $name   = $_POST['name'] ?? '';
        $price  = intval($_POST['price'] ?? 0);
        $rarity = $_POST['rarity'] ?? 'Common';
        $category = $_POST['category'] ?? 'border';

        if (empty($name) || $price <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Name and price are required']);
            exit;
        }

        $imageUrl = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/shop/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('shop_') . '.' . $ext;
            $destination = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $imageUrl = '/uploads/shop/' . $filename;
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to upload image']);
                exit;
            }
        }

        $stmt = $conn->prepare("INSERT INTO shop_items (item_name, category, point_cost, rarity, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category, $price, $rarity, $imageUrl]);

        $newId = $conn->lastInsertId();

        $newItem = [
            'id'     => $newId,
            'name'   => $name,
            'price'  => $price,
            'rarity' => $rarity,
            'image'  => $imageUrl,
            'category' => $category
        ];

        // Broadcast before responding
        triggerPusherEvent('shop-updates', 'shop_changed', [
            'action' => 'create',
            'item'   => $newItem
        ]);

        echo json_encode(['success' => true, 'item' => $newItem]);
        exit;
        break;

    case 'update':
        $id     = intval($_POST['id'] ?? 0);
        $name   = $_POST['name'] ?? '';
        $price  = intval($_POST['price'] ?? 0);
        $rarity = $_POST['rarity'] ?? 'Common';
        $category = $_POST['category'] ?? 'border';

        if ($id <= 0 || empty($name) || $price <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit;
        }

        // Get existing image
        $stmt = $conn->prepare("SELECT image_url FROM shop_items WHERE item_id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetchColumn();
        $imageUrl = $existing ?: '';

        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/shop/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if ($imageUrl && file_exists(__DIR__ . '/..' . $imageUrl)) {
                unlink(__DIR__ . '/..' . $imageUrl);
            }
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('shop_') . '.' . $ext;
            $destination = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                $imageUrl = '/uploads/shop/' . $filename;
            }
        }

        $stmt = $conn->prepare("UPDATE shop_items SET item_name = ?, category = ?, point_cost = ?, rarity = ?, image_url = ? WHERE item_id = ?");
        $stmt->execute([$name, $category, $price, $rarity, $imageUrl, $id]);

        $updatedItem = [
            'id'     => $id,
            'name'   => $name,
            'price'  => $price,
            'rarity' => $rarity,
            'image'  => $imageUrl,
            'category' => $category
        ];

        // Broadcast before responding
        triggerPusherEvent('shop-updates', 'shop_changed', [
            'action' => 'update',
            'item'   => $updatedItem
        ]);

        echo json_encode(['success' => true, 'item' => $updatedItem]);
        exit;
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }

        $stmt = $conn->prepare("SELECT image_url FROM shop_items WHERE item_id = ?");
        $stmt->execute([$id]);
        $imageUrl = $stmt->fetchColumn();
        if ($imageUrl && file_exists(__DIR__ . '/..' . $imageUrl)) {
            unlink(__DIR__ . '/..' . $imageUrl);
        }

        $stmt = $conn->prepare("DELETE FROM shop_items WHERE item_id = ?");
        $stmt->execute([$id]);

        // Broadcast before responding
        triggerPusherEvent('shop-updates', 'shop_changed', [
            'action'  => 'delete',
            'item_id' => $id
        ]);

        echo json_encode(['success' => true]);
        exit;
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
}