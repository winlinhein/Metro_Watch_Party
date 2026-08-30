<?php
session_start();
require_once __DIR__ . '/../conn.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$itemId = intval($input['item_id'] ?? 0);

if (!$itemId) {
    echo json_encode(['success' => false, 'message' => 'Invalid item']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $conn->beginTransaction();

    // Get item cost
    $stmt = $conn->prepare("SELECT point_cost FROM shop_items WHERE item_id = ?");
    $stmt->execute([$itemId]);
    $cost = $stmt->fetchColumn();

    if (!$cost) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }

    // Check if already owned
    $stmt = $conn->prepare("SELECT 1 FROM user_inventory WHERE user_id = ? AND item_id = ?");
    $stmt->execute([$userId, $itemId]);
    if ($stmt->fetch()) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Already owned']);
        exit;
    }

    // Check points and deduct
    $stmt = $conn->prepare("SELECT points FROM users WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$userId]);
    $points = (int)$stmt->fetchColumn();

    if ($points < $cost) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Not enough points']);
        exit;
    }

    $newPoints = $points - $cost;

    $stmt = $conn->prepare("UPDATE users SET points = ? WHERE user_id = ?");
    $stmt->execute([$newPoints, $userId]);

    // Add to inventory
    $stmt = $conn->prepare("INSERT INTO user_inventory (user_id, item_id, purchased_at) VALUES (?, ?, NOW())");
    $stmt->execute([$userId, $itemId]);

    $conn->commit();

    echo json_encode([
        'success'    => true,
        'new_points' => $newPoints,
        'item_id'    => $itemId
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Purchase failed']);
}