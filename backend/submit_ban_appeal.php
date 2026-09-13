<?php
session_start();

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../schema_upgrade_helper.php';
require_once __DIR__ . '/../profile_media_helper.php';
require_once __DIR__ . '/../pusher_helper.php';

$hold = $_SESSION['account_hold'] ?? null;
if (!$hold || ($hold['mode'] ?? '') !== 'banned' || empty($hold['user_id'])) {
    header('Location: ../frontend/login.php?error=' . urlencode('No banned account session found.'));
    exit();
}

$appealText = trim((string)($_POST['appeal_text'] ?? ''));
if ($appealText === '') {
    header('Location: ../frontend/account_hold.php?error=' . urlencode('Please write your appeal before submitting.'));
    exit();
}

try {
    ensureAppSchema($conn);
    $userId = (int)$hold['user_id'];

    $existing = $conn->prepare("
        SELECT appeal_id FROM ban_appeals
        WHERE user_id = ? AND status = 'pending'
        LIMIT 1
    ");
    $existing->execute([$userId]);
    if ($existing->fetchColumn()) {
        header('Location: ../frontend/account_hold.php?appeal=pending');
        exit();
    }

    $existingReport = $conn->prepare("
        SELECT report_id FROM reports
        WHERE type = 'appeal'
          AND (reporter_id = ? OR reported_user_id = ?)
          AND LOWER(IFNULL(status, 'pending')) = 'pending'
        LIMIT 1
    ");
    $existingReport->execute([$userId, $userId]);
    if ($existingReport->fetchColumn()) {
        header('Location: ../frontend/account_hold.php?appeal=pending');
        exit();
    }

    $userStmt = $conn->prepare("SELECT user_name FROM users WHERE user_id = ? LIMIT 1");
    $userStmt->execute([$userId]);
    $reporterName = (string)($userStmt->fetchColumn() ?: 'User');

    $conn->beginTransaction();

    $stmt = $conn->prepare("
        INSERT INTO ban_appeals (user_id, appeal_text, status, created_at)
        VALUES (?, ?, 'pending', NOW())
    ");
    $stmt->execute([$userId, $appealText]);

    $reportStmt = $conn->prepare("
        INSERT INTO reports (reporter_id, reported_user_id, reported_room_id, comment_id, type, description)
        VALUES (?, ?, NULL, NULL, 'appeal', ?)
    ");
    $reportStmt->execute([$userId, $userId, $appealText]);
    $reportId = (int)$conn->lastInsertId();

    require_once __DIR__ . '/../auth_flow_helper.php';
    $notiMessage = $reporterName . ' submitted a ban appeal (Report #' . $reportId . ')';
    $adminNotifs = nexusNotifyStaff($conn, $userId, 'report_alert', $notiMessage);

    $conn->commit();

    if (function_exists('triggerPusherEvent')) {
        $createdAt = date('Y-m-d H:i:s');
        triggerPusherEvent('admin-moderation-channel', 'new-report-event', [
            'report' => [
                'id'               => $reportId,
                'reporter_id'      => $userId,
                'reported_user_id' => $userId,
                'reported_room_id' => null,
                'comment_id'       => null,
                'type'             => 'appeal',
                'description'      => $appealText,
                'status'           => 'Pending',
                'created_at'       => $createdAt
            ]
        ]);

        $reporterMedia = getUserProfileMedia($conn, $userId);
        foreach ($adminNotifs as $adminId => $notifId) {
            $notifPayload = array_merge([
                'id'          => $notifId,
                'sender_id'   => $userId,
                'sender_name' => $reporterName,
                'type'        => 'report_alert',
                'message'     => $notiMessage,
                'is_read'     => 0,
                'created_at'  => $createdAt,
                'icon'        => 'gavel',
            ], $reporterMedia);
            triggerPusherEvent("user-{$adminId}", 'new_notification', $notifPayload);
        }
    }

    header('Location: ../frontend/account_hold.php?appeal=sent');
    exit();
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('submit_ban_appeal: ' . $e->getMessage());
    header('Location: ../frontend/account_hold.php?error=' . urlencode('Could not submit appeal. Try again.'));
    exit();
}
