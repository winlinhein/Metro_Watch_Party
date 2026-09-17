<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../schema_upgrade_helper.php';
require_once __DIR__ . '/../pusher_helper.php';
require_once __DIR__ . '/../profile_media_helper.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_role']) || !in_array(strtolower((string)$_SESSION['user_role']), ['admin', 'moderator'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$adminId = (int)($_SESSION['user_id'] ?? 0);
$adminName = (string)($_SESSION['user_name'] ?? 'Moderator');
session_write_close();

ensureAppSchema($conn);

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = [];
}

$report_id = $data['report_id'] ?? null;
if (is_string($report_id) && preg_match('/(\d+)/', $report_id, $m)) {
    $report_id = (int)$m[1];
} else {
    $report_id = (int)$report_id;
}

$statusRaw = strtolower(trim((string)($data['status'] ?? 'read')));
$allowed = ['read', 'resolved', 'cancelled'];
if (!$report_id) {
    echo json_encode(['success' => false, 'message' => 'No report ID provided']);
    exit;
}
if (!in_array($statusRaw, $allowed, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT report_id, reporter_id, IFNULL(status, 'pending') AS status, type
        FROM reports
        WHERE report_id = ?
        LIMIT 1
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        exit;
    }

    $current = strtolower((string)($report['status'] ?? 'pending'));
    if ($statusRaw === 'read' && in_array($current, ['resolved', 'cancelled'], true)) {
        echo json_encode(['success' => true, 'status' => ucfirst($current), 'unchanged' => true]);
        exit;
    }

    if ($current !== $statusRaw) {
        $upd = $conn->prepare("UPDATE reports SET status = ? WHERE report_id = ?");
        $upd->execute([$statusRaw, $report_id]);
    }

    $notified = false;
    if ($statusRaw === 'cancelled' && $current !== 'cancelled') {
        $reporterId = (int)($report['reporter_id'] ?? 0);
        $senderId = $adminId > 0 ? $adminId : $reporterId;
        if ($reporterId > 0 && $senderId > 0) {
            $message = 'cancelled your report (Report #' . $report_id . ').';
            $ins = $conn->prepare("
                INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at)
                VALUES (?, ?, 'report_cancelled', ?, 0, NOW())
            ");
            $ins->execute([$reporterId, $senderId, $message]);
            $notifId = (int)$conn->lastInsertId();

            $senderMedia = getUserProfileMedia($conn, $senderId);
            triggerPusherEvent("user-{$reporterId}", 'new_notification', array_merge([
                'id'          => $notifId,
                'sender_id'   => $senderId,
                'sender_name' => $adminName,
                'type'        => 'report_cancelled',
                'message'     => $message,
                'is_read'     => 0,
                'created_at'  => date('Y-m-d H:i:s'),
                'icon'        => 'flag',
                'report_id'   => $report_id,
            ], $senderMedia));
            $notified = true;
        }
    }

    $publicStatus = ucfirst($statusRaw);
    triggerPusherEvent('admin-moderation-channel', 'report-status-changed', [
        'report_id' => $report_id,
        'status'    => $publicStatus,
    ]);

    echo json_encode([
        'success'  => true,
        'status'   => $publicStatus,
        'notified' => $notified,
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
