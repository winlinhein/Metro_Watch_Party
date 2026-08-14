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
    $conn->begin_transaction();

    // 1. Insert the main report using MySQLi prepared statements
    $stmt = $conn->prepare("INSERT INTO reports (reporter_id, reported_user_id, type, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $reporter_id, $reported_id, $type, $description);
    $stmt->execute();
    
    $report_id = $conn->insert_id;

    // 2. Insert predefined reasons into the junction table
    if (!empty($reason_ids)) {
        $stmt_reasons = $conn->prepare("INSERT INTO report_and_reasons (report_id, reason_id) VALUES (?, ?)");
        foreach ($reason_ids as $r_id) {
            $stmt_reasons->bind_param("ii", $report_id, $r_id);
            $stmt_reasons->execute();
        }
    }

    $conn->commit();

    // 3. Trigger Real-Time notification via Pusher to admins
    if (function_exists('get_pusher_instance')) {
        $pusher = get_pusher_instance();
        $pusher->trigger('private-admin-moderation', 'new-report', [
            'report_id' => $report_id,
            'reporter_id' => $reporter_id,
            'reported_id' => $reported_id,
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to submit report. Please try again.']);
}
?>