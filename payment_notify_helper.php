<?php

require_once __DIR__ . '/notifications_helper.php';
require_once __DIR__ . '/pusher_helper.php';
require_once __DIR__ . '/schema_upgrade_helper.php';

function nexusCurrentUserPoints(PDO $conn, int $userId): int
{
    $stmt = $conn->prepare("SELECT points FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

function nexusStripeGatewayId(PDO $conn): ?int
{
    try {
        $gwStmt = $conn->prepare("SELECT gateway_id FROM gateways WHERE name = 'Stripe'");
        $gwStmt->execute();
        $id = $gwStmt->fetchColumn();
        return $id ? (int)$id : null;
    } catch (Throwable $e) {
        return null;
    }
}

function nexusPaymentAlreadyRecorded(PDO $conn, string $gwTxn): bool
{
    if ($gwTxn === '') {
        return false;
    }
    $dup = $conn->prepare("SELECT transaction_id FROM payment_transactions WHERE gateway_transaction_id = ? LIMIT 1");
    $dup->execute([$gwTxn]);
    return (bool)$dup->fetchColumn();
}

function nexusRecordPaymentTransaction(
    PDO $conn,
    array $session,
    int $userId,
    string $type,
    string $status,
    ?int $planId,
    ?int $packageId,
    float $fallbackAmount
): ?array {
    ensureAppSchema($conn);
    $gatewayId = nexusStripeGatewayId($conn);
    if (!$gatewayId) {
        return null;
    }

    $gwTxn = (string)($session['payment_intent'] ?? $session['subscription'] ?? $session['id'] ?? '');
    if ($gwTxn === '') {
        $gwTxn = 'sess_' . time() . '_' . $userId;
    }
    if (nexusPaymentAlreadyRecorded($conn, $gwTxn)) {
        $stmt = $conn->prepare("
            SELECT transaction_id, amount, status, created_at
            FROM payment_transactions
            WHERE gateway_transaction_id = ?
            LIMIT 1
        ");
        $stmt->execute([$gwTxn]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'id' => 'TXN-' . (int)($existing['transaction_id'] ?? 0),
            'raw_id' => (int)($existing['transaction_id'] ?? 0),
            'gateway_txn_id' => $gwTxn,
            'duplicate' => true,
        ];
    }

    $amountCents = (int)($session['amount_total'] ?? 0);
    $amount = $amountCents > 0 ? round($amountCents / 100, 2) : $fallbackAmount;
    $insert = $conn->prepare("
        INSERT INTO payment_transactions
            (user_id, plan_id, package_id, type, gateway_id, gateway_transaction_id, amount, status)
        VALUES
            (:user_id, :plan_id, :package_id, :type, :gateway_id, :gateway_txn_id, :amount, :status)
    ");
    $insert->execute([
        'user_id' => $userId > 0 ? $userId : null,
        'plan_id' => $planId && $planId > 0 ? $planId : null,
        'package_id' => $packageId && $packageId > 0 ? $packageId : null,
        'type' => $type === 'points' ? 'points' : 'premium',
        'gateway_id' => $gatewayId,
        'gateway_txn_id' => $gwTxn,
        'amount' => $amount,
        'status' => $status,
    ]);
    $txnId = (int)$conn->lastInsertId();

    return [
        'id' => 'TXN-' . $txnId,
        'raw_id' => $txnId,
        'gateway_txn_id' => $gwTxn,
        'amount' => $amount,
        'status' => $status,
        'type' => $type === 'points' ? 'points' : 'premium',
        'duplicate' => false,
    ];
}

function nexusNotifyPayment(PDO $conn, int $userId, string $type, bool $success, string $message, array $extra = []): void
{
    if ($userId <= 0) {
        return;
    }
    $notifType = $success ? 'payment_success' : 'payment_failed';
    try {
        $notifId = nexusInsertNotification($conn, $userId, null, $notifType, $message);
    } catch (Throwable $e) {
        $notifId = 0;
    }

    $payload = array_merge([
        'id' => $notifId,
        'type' => $notifType,
        'sender_id' => null,
        'sender_name' => 'Nexus Billing',
        'message' => $message,
        'created_at' => date('Y-m-d H:i:s'),
        'is_read' => 0,
        'payment_type' => $type,
    ], $extra);

    triggerPusherEvent("user-{$userId}", 'new_notification', $payload);
    if (isset($extra['points'])) {
        triggerPusherEvent("user-{$userId}", 'points_changed', [
            'points' => (int)$extra['points'],
            'points_added' => (int)($extra['points_added'] ?? 0),
        ]);
    }
}

function nexusBroadcastTransaction(PDO $conn, array $txn, int $userId = 0): void
{
    $userName = (string)($txn['user_name'] ?? '');
    $email = (string)($txn['email'] ?? '');
    if ($userId > 0 && ($userName === '' || $email === '')) {
        try {
            $stmt = $conn->prepare("SELECT user_name, email FROM users WHERE user_id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $userName = $userName !== '' ? $userName : (string)($row['user_name'] ?? 'User');
            $email = $email !== '' ? $email : (string)($row['email'] ?? '');
        } catch (Throwable $e) {
        }
    }

    $status = strtolower((string)($txn['status'] ?? 'success'));
    $statusLabel = $status === 'success' || $status === 'paid' ? 'Success' : ($status === 'failed' ? 'Failed' : ucfirst($status));
    $type = strtolower((string)($txn['type'] ?? 'premium')) === 'points' ? 'Points' : 'Premium';
    $amount = isset($txn['amount']) ? (float)$txn['amount'] : 0;

    triggerPusherEvent('admin-moderation-channel', 'new-transaction-event', [
        'transaction' => [
            'id' => $txn['id'] ?? ('TXN-' . ($txn['raw_id'] ?? 0)),
            'raw_id' => (int)($txn['raw_id'] ?? 0),
            'gateway_txn_id' => (string)($txn['gateway_txn_id'] ?? ''),
            'user_name' => $userName !== '' ? $userName : 'User',
            'email' => $email,
            'avatar_url' => (string)($txn['avatar_url'] ?? ''),
            'plan' => (string)($txn['plan'] ?? ($type === 'Points' ? 'Point Pack' : 'Nexus Premium')),
            'type' => $type,
            'gateway' => 'Stripe',
            'amount' => '$' . number_format(abs($amount), 2),
            'status' => $statusLabel,
            'date' => date('Y-m-d H:i'),
        ],
    ]);
}
