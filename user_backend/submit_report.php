<?php
// /user_backend/submit_report.php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$reporter_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$reported_id = $data['reported_id'] ?? null;
$type = $data['type'] ?? 'user';
$description = $data['description'] ?? null;
$reason_ids = $data['reason_ids'] ?? [];

if (!$reported_id || (empty($description) && empty($reason_ids))) {
    echo json_encode(['success' => false, 'message' => 'Invalid report data']);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Insert main report
    $stmt = $conn->prepare("INSERT INTO reports (reporter_id, reported_user_id, type, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$reporter_id, $reported_id, $type, $description]);
    $report_id = $conn->lastInsertId();

    // 2. Insert predefined reasons
    if (!empty($reason_ids)) {
        $stmt_reasons = $conn->prepare("INSERT INTO report_and_reasons (report_id, reason_id) VALUES (?, ?)");
        foreach ($reason_ids as $r_id) {
            $stmt_reasons->execute([$report_id, $r_id]);
        }
    }

    // 3. Insert notification for all Admins into the shared 'noti' table
    $noti_message = "New " . ucfirst($type) . " report submitted (Report #" . $report_id . ")";
    $stmt_noti = $conn->prepare("
        INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at)
        SELECT user_id, ?, 'report_alert', ?, 0, NOW()
        FROM users 
        WHERE role_id = 1 OR role_id = 3 
    ");
    $stmt_noti->execute([$reporter_id, $noti_message]);

    $conn->commit();

    // 4. Trigger Pusher Event to update Admin UI instantly (Notifications & Reports Table)
    if (function_exists('get_pusher_instance')) {
        $pusher = get_pusher_instance();
        
        $payload = [
            'notification' => [
                'id' => time(), // Temp ID for real-time push
                'type' => 'report_alert',
                'message' => $noti_message,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ],
            'report' => [
                'id' => $report_id,
                'reporter_id' => $reporter_id,
                'reported_user_id' => $reported_id,
                'type' => $type,
                'description' => $description,
                'status' => 'Pending',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];

        // Broadcast to admin channel
        $pusher->trigger('admin-moderation-channel', 'new-report-event', $payload);
    }

    echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Failed to submit report.']);
}
?>