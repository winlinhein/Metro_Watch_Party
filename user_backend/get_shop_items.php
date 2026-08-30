<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../shop_image_helper.php';
header('Content-Type: application/json');

$stmt = $conn->query("SELECT item_id, item_name, category, point_cost, rarity, image_url FROM shop_items ORDER BY item_id DESC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$result = array_map(function($item) {
    return [
        'id'     => $item['item_id'],
        'name'   => $item['item_name'],
        'price'  => (int)$item['point_cost'],
        'rarity' => $item['rarity'],
        'image'  => shopImageUrl($item['image_url'] ?? '', $item['item_name'] ?? ''),
        'category' => $item['category']
    ];
}, $items);

echo json_encode(['success' => true, 'items' => $result]);