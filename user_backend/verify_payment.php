<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../stripe_helper.php';
require_once __DIR__ . '/../conn.php';   // include database connection

$sessionId = $_GET['session_id'] ?? '';

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'Missing session ID']);
    exit();
}

try {
    $session = stripeRequest('GET', '/v1/checkout/sessions/' . $sessionId);

    if (($session['client_reference_id'] ?? '') != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Session mismatch']);
        exit();
    }

    if ($session['payment_status'] !== 'paid') {
        echo json_encode(['success' => false, 'message' => 'Payment not completed']);
        exit();
    }

    $stmt = $conn->prepare("SELECT is_premium FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $alreadyPremium = (bool)$stmt->fetchColumn();

    if (!$alreadyPremium) {
        $conn->beginTransaction();

        $stmt = $conn->prepare("UPDATE users SET is_premium = 1, premium_expires_at = DATE_ADD(NOW(), INTERVAL :days DAY) WHERE user_id = :user_id");
        $stmt->execute(['days' => PREMIUM_DURATION_DAYS, 'user_id' => $_SESSION['user_id']]);

        $stmt = $conn->prepare("UPDATE users SET stripe_customer_id = :customer_id, stripe_subscription_id = :sub_id WHERE user_id = :user_id");
        $stmt->execute([
            'customer_id' => $session['customer'],
            'sub_id'      => $session['subscription'] ?? null,
            'user_id'     => $_SESSION['user_id']
        ]);

        $gwStmt = $conn->prepare("SELECT gateway_id FROM gateways WHERE name = 'Stripe'");
        $gwStmt->execute();
        $gateway = $gwStmt->fetch(PDO::FETCH_ASSOC);

        if ($gateway) {
            $amountCents = (int)$session['amount_total'];
            $insert = $conn->prepare("INSERT INTO payment_transactions (user_id, plan_id, gateway_id, gateway_transaction_id, amount, status) VALUES (:user_id, :plan_id, :gateway_id, :gateway_txn_id, :amount, 'success')");
            $insert->execute([
                'user_id'         => $_SESSION['user_id'],
                'plan_id'         => PREMIUM_PLAN_ID,
                'gateway_id'      => $gateway['gateway_id'],
                'gateway_txn_id'  => $session['payment_intent'] ?? $session['subscription'] ?? $session['id'],
                'amount'          => $amountCents
            ]);
        }

        $conn->commit();
    }

    echo json_encode(['success' => true, 'message' => 'Premium activated']);
} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}