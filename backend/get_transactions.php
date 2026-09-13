<?php
session_start();

$role = strtolower((string)($_SESSION['user_role'] ?? ''));
if (
    empty($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true ||
    !in_array($role, ['admin', 'moderator'], true)
) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access.']);
    exit;
}

header('Content-Type: application/json');
session_write_close();

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../profile_media_helper.php';
require_once __DIR__ . '/../account_lifecycle_helper.php';
nexusPrepareKeptRecords($conn);

function txnAmountToDollars(float $amount): float
{
    if ($amount >= 50 && abs($amount - round($amount)) < 0.001) {
        return $amount / 100;
    }
    return $amount;
}

function txnStatusLabel(string $status): string
{
    $key = strtolower(trim($status));
    $map = [
        'success' => 'Success',
        'paid' => 'Success',
        'complete' => 'Success',
        'completed' => 'Success',
        'pending' => 'Pending',
        'open' => 'Pending',
        'processing' => 'Pending',
        'failed' => 'Failed',
        'failure' => 'Failed',
        'error' => 'Failed',
        'canceled' => 'Failed',
        'cancelled' => 'Failed',
        'refunded' => 'Refunded',
        'refund' => 'Refunded',
    ];
    return $map[$key] ?? ucfirst($key !== '' ? $key : 'Pending');
}

function formatTxnAmount(float $amount, string $status): string
{
    $dollars = txnAmountToDollars($amount);
    $prefix = (strtolower($status) === 'refunded' && $dollars > 0) ? '-' : '';
    return $prefix . '$' . number_format(abs($dollars), 2);
}

try {
    $stmt = $conn->query("
        SELECT
            pt.transaction_id,
            pt.user_id,
            pt.plan_id,
            pt.gateway_transaction_id,
            pt.amount,
            pt.status,
            pt.created_at,
            COALESCE(u.user_name, pt.deleted_user_name, 'Deleted user') AS user_name,
            COALESCE(u.email, pt.deleted_user_email, '') AS email,
            g.name AS gateway_name
        FROM payment_transactions pt
        LEFT JOIN users u ON u.user_id = pt.user_id
        LEFT JOIN gateways g ON g.gateway_id = pt.gateway_id
        ORDER BY pt.created_at DESC, pt.transaction_id DESC
        LIMIT 300
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $rows = attachProfileMedia($conn, $rows, 'user_id');

    $transactions = array_map(static function (array $row): array {
        $status = txnStatusLabel((string)($row['status'] ?? ''));
        $amount = (float)($row['amount'] ?? 0);
        $created = (string)($row['created_at'] ?? '');
        $date = $created !== '' ? date('Y-m-d H:i', strtotime($created)) : '';

        return [
            'id' => 'TXN-' . (int)$row['transaction_id'],
            'raw_id' => (int)$row['transaction_id'],
            'gateway_txn_id' => (string)($row['gateway_transaction_id'] ?? ''),
            'user_name' => (string)($row['user_name'] ?? 'Unknown user'),
            'email' => (string)($row['email'] ?? ''),
            'avatar_url' => (string)($row['avatar_url'] ?? ''),
            'plan' => 'Nexus Premium',
            'gateway' => (string)($row['gateway_name'] ?? 'Stripe'),
            'amount' => formatTxnAmount($amount, $status),
            'status' => $status,
            'date' => $date,
        ];
    }, $rows);

    echo json_encode(['success' => true, 'transactions' => $transactions]);
} catch (Throwable $e) {
    error_log('get_transactions: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to load transactions']);
}
