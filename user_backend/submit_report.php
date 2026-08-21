<?php
session_start();
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../pusher_helper.php';

header('Content-Type: application/json');

// ---------------------------------------------------------------------
// 1. Authentication Check
// ---------------------------------------------------------------------
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$reporter_id = (int)$_SESSION['user_id'];

// ---------------------------------------------------------------------
// 2. Read & Decode JSON Input
// ---------------------------------------------------------------------
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

// ---------------------------------------------------------------------
// 3. Determine Report Type
// ---------------------------------------------------------------------
$is_item_report = isset($data['reported_item_id']) && isset($data['item_type']);
$is_user_report = isset($data['reported_id']) && ($data['type'] ?? '') === 'user';

if (!$is_item_report && !$is_user_report) {
    echo json_encode(['success' => false, 'message' => 'Invalid report data']);
    exit;
}

$reason_ids  = $data['reason_ids'] ?? [];
$description = $data['description'] ?? null;

// Normalize reason_ids to an array of positive integers
if (!is_array($reason_ids)) {
    $reason_ids = [];
} else {
    $reason_ids = array_filter(array_map('intval', $reason_ids), fn($id) => $id > 0);
}

// At least one reason or a description is required
if (empty($reason_ids) && empty(trim($description))) {
    echo json_encode(['success' => false, 'message' => 'Please provide a reason or description']);
    exit;
}

// ---------------------------------------------------------------------
// 4. Prepare Data Depending on Report Type (outside try for early exits)
// ---------------------------------------------------------------------
if ($is_item_report) {
    $type = $data['item_type'];

    // Allowed item types
    $allowed_item_types = ['comment', 'reply'];
    if (!in_array($type, $allowed_item_types, true)) {
        echo json_encode(['success' => false, 'message' => 'Invalid item type']);
        exit;
    }

    $comment_id = (int)$data['reported_item_id'];

    // Fetch the author's user_id from movie_comments
    $stmt_user = $conn->prepare("SELECT user_id FROM movie_comments WHERE comment_id = ?");
    $stmt_user->execute([$comment_id]);
    $comment_author = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$comment_author) {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit;
    }

    $reported_user_id = (int)$comment_author['user_id'];

    // Prevent reporting yourself (optional)
    if ($reported_user_id === $reporter_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot report your own comment']);
        exit;
    }
} else {
    // Direct user report
    $comment_id = null;
    $reported_user_id = (int)$data['reported_id'];
    $type = 'user';

    // Prevent reporting yourself
    if ($reported_user_id === $reporter_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot report yourself']);
        exit;
    }
}

// ---------------------------------------------------------------------
// 5. Database Transaction (only for critical DB operations)
// ---------------------------------------------------------------------
try {
    $conn->beginTransaction();

    // 5a. Insert the main report
    $stmt = $conn->prepare("
        INSERT INTO reports (reporter_id, reported_user_id, comment_id, type, description)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $reporter_id,
        $reported_user_id,
        $comment_id,
        $type,
        $description
    ]);
    $report_id = $conn->lastInsertId();

    // 5b. Insert reason mappings (if any)
    if (!empty($reason_ids)) {
        $stmt_reasons = $conn->prepare("
            INSERT INTO report_and_reasons (report_id, reason_id)
            VALUES (?, ?)
        ");
        foreach ($reason_ids as $r_id) {
            $stmt_reasons->execute([$report_id, $r_id]);
        }
    }

    // 5c. Create a notification for admins (role_id 1 = superadmin, 3 = moderator)
    $noti_message = "New " . ucfirst($type) . " report submitted (Report #" . $report_id . ")";
    $stmt_noti = $conn->prepare("
        INSERT INTO notifications (user_id, sender_id, type, message, is_read, created_at)
        SELECT user_id, ?, 'report_alert', ?, 0, NOW()
        FROM users
        WHERE role_id IN (1, 3)
    ");
    $stmt_noti->execute([$reporter_id, $noti_message]);

    $conn->commit();

    // -----------------------------------------------------------------
    // 6. Send Real-time Notification via Pusher (non-critical, isolated)
    // -----------------------------------------------------------------
    if (function_exists('get_pusher_instance')) {
        try {
            $pusher = get_pusher_instance();
            $payload = [
                'notification' => [
                    'id'         => time(),
                    'type'       => 'report_alert',
                    'message'    => $noti_message,
                    'is_read'    => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ],
                'report' => [
                    'id'               => $report_id,
                    'reporter_id'      => $reporter_id,
                    'reported_user_id' => $reported_user_id,
                    'comment_id'       => $comment_id,
                    'type'             => $type,
                    'description'      => $description,
                    'status'           => 'Pending',
                    'created_at'       => date('Y-m-d H:i:s')
                ]
            ];

            $pusher->trigger('admin-moderation-channel', 'new-report-event', $payload);
        } catch (Exception $e) {
            // Log the Pusher error but do not affect the success response
            error_log('Pusher trigger error: ' . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------
    // 7. Success Response
    // -----------------------------------------------------------------
    echo json_encode([
        'success' => true,
        'message' => 'Report submitted successfully'
    ]);

} catch (Exception $e) {
    // Rollback if transaction is still active
    if ($conn->inTransaction()) {
        $conn->rollback();
    }

    error_log('Report submission error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to submit report. Please try again.'
    ]);
}