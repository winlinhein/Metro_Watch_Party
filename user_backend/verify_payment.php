<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../stripe_helper.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../schema_upgrade_helper.php';

ensureAppSchema($conn);

$sessionId = $_GET['session_id'] ?? '';
$userId = (int) $_SESSION['user_id'];

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'Missing session ID']);
    exit();
}

function nexusCurrentUserPoints(PDO $conn, int $userId): int
{
    $stmt = $conn->prepare("SELECT points FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function nexusRecordPayment(PDO $conn, array $session, int $userId, int $planId, float $fallbackAmount): void
{
    $gwStmt = $conn->prepare("SELECT gateway_id FROM gateways WHERE name = 'Stripe'");
    $gwStmt->execute();
    $gateway = $gwStmt->fetch(PDO::FETCH_ASSOC);
    if (!$gateway) {
        return;
    }

    $gwTxn = $session['payment_intent'] ?? $session['subscription'] ?? $session['id'];
    $dup = $conn->prepare("SELECT transaction_id FROM payment_transactions WHERE gateway_transaction_id = ? LIMIT 1");
    $dup->execute([$gwTxn]);
    if ($dup->fetchColumn()) {
        return;
    }

    $amountCents = (int) ($session['amount_total'] ?? 0);
    $amount = $amountCents > 0 ? round($amountCents / 100, 2) : $fallbackAmount;
    $insert = $conn->prepare("INSERT INTO payment_transactions (user_id, plan_id, gateway_id, gateway_transaction_id, amount, status) VALUES (:user_id, :plan_id, :gateway_id, :gateway_txn_id, :amount, 'success')");
    $insert->execute([
        'user_id'        => $userId,
        'plan_id'        => $planId,
        'gateway_id'     => $gateway['gateway_id'],
        'gateway_txn_id' => $gwTxn,
        'amount'         => $amount,
    ]);
}

try {
    $session = stripeRequest('GET', '/v1/checkout/sessions/' . $sessionId);

    if (($session['client_reference_id'] ?? '') != $userId) {
        echo json_encode(['success' => false, 'message' => 'Session mismatch']);
        exit();
    }

    if ($session['payment_status'] !== 'paid') {
        echo json_encode(['success' => false, 'message' => 'Payment not completed']);
        exit();
    }

    $type = strtolower((string) ($session['metadata']['type'] ?? 'premium'));

    if ($type === 'points') {
        $points = (int) ($session['metadata']['points'] ?? 0);
        $planId = (int) ($session['metadata']['plan_id'] ?? 0);
        if ($points <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid point pack']);
            exit();
        }

        $gwTxn = $session['payment_intent'] ?? $session['id'];
        $dup = $conn->prepare("SELECT transaction_id FROM payment_transactions WHERE gateway_transaction_id = ? LIMIT 1");
        $dup->execute([$gwTxn]);
        if ($dup->fetchColumn()) {
            echo json_encode([
                'success' => true,
                'type' => 'points',
                'points_added' => 0,
                'points' => nexusCurrentUserPoints($conn, $userId),
                'message' => 'Already credited',
            ]);
            exit();
        }

        $conn->beginTransaction();
        $stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE user_id = ?");
        $stmt->execute([$points, $userId]);
        try {
            nexusRecordPayment($conn, $session, $userId, $planId > 0 ? $planId : 2, $points / 100);
        } catch (Throwable $ignore) {
        }
        $conn->commit();

        echo json_encode([
            'success' => true,
            'type' => 'points',
            'points_added' => $points,
            'points' => nexusCurrentUserPoints($conn, $userId),
            'message' => 'Points added',
        ]);
        exit();
    }

    $stmt = $conn->prepare("SELECT is_premium FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $alreadyPremium = (bool) $stmt->fetchColumn();
    $plan = nexusPremiumPlan($conn);

    if (!$alreadyPremium) {
        $conn->beginTransaction();

        $stmt = $conn->prepare("UPDATE users SET is_premium = 1, premium_expires_at = DATE_ADD(NOW(), INTERVAL :days DAY) WHERE user_id = :user_id");
        $stmt->execute(['days' => $plan['duration_days'], 'user_id' => $userId]);

        $stmt = $conn->prepare("UPDATE users SET stripe_customer_id = :customer_id, stripe_subscription_id = :sub_id WHERE user_id = :user_id");
        $stmt->execute([
            'customer_id' => $session['customer'],
            'sub_id'      => $session['subscription'] ?? null,
            'user_id'     => $userId,
        ]);

        nexusRecordPayment($conn, $session, $userId, (int) $plan['plan_id'], (float) $plan['price']);
        $conn->commit();
    }

    echo json_encode(['success' => true, 'type' => 'premium', 'message' => 'Premium activated']);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
