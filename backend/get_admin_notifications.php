<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../profile_media_helper.php';
header('Content-Type: application/json');

$role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? '');
if (empty($_SESSION['user_id']) || !in_array(strtolower((string)$role), ['admin', 'moderator'], true)) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$admin_id = (int)$_SESSION['user_id'];
session_write_close();

try {
    $stmt = $conn->prepare("
        SELECT
            n.id,
            n.sender_id,
            n.type,
            n.message,
            n.is_read,
            n.created_at,
            u.user_name AS sender_name
        FROM notifications n
        LEFT JOIN users u ON u.user_id = n.sender_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 30
    ");
    $stmt->execute([$admin_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $mediaRows = [];
    foreach ($notifications as $n) {
        if (!empty($n['sender_id'])) {
            $mediaRows[] = ['user_id' => (int)$n['sender_id']];
        }
    }
    $mediaByUser = [];
    foreach (attachProfileMedia($conn, $mediaRows) as $m) {
        $mediaByUser[(int)$m['user_id']] = $m;
    }

    foreach ($notifications as &$n) {
        $sid = (int)($n['sender_id'] ?? 0);
        $n['avatar_url'] = $mediaByUser[$sid]['avatar_url'] ?? '';
        $n['border_preview'] = $mediaByUser[$sid]['border_preview'] ?? '';
        $n['border_id'] = (int)($mediaByUser[$sid]['border_id'] ?? 0);
        $n['sender_name'] = $n['sender_name'] ?? 'System';
        $n['is_read'] = (int)($n['is_read'] ?? 0);
        $type = (string)($n['type'] ?? '');
        if ($type === 'report_alert' || $type === 'report') {
            $n['icon'] = 'flag';
            $n['iconColorClass'] = 'text-red-400';
            $n['bgClass'] = 'bg-red-500/20';
            $n['borderClass'] = 'border-red-500/30';
        } else {
            $n['icon'] = $n['icon'] ?? 'notifications';
            $n['iconColorClass'] = $n['iconColorClass'] ?? 'text-white/70';
            $n['bgClass'] = $n['bgClass'] ?? 'bg-white/5';
            $n['borderClass'] = $n['borderClass'] ?? 'border-white/10';
        }
    }
    unset($n);

    echo json_encode(['success' => true, 'notifications' => $notifications]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
