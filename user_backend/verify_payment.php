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
require_once __DIR__ . '/../point_pack_helper.php';
require_once __DIR__ . '/../payment_notify_helper.php';

ensureAppSchema($conn);

$sessionId = $_GET['session_id'] ?? '';
$userId = (int) $_SESSION['user_id'];

if (!$sessionId) {
    echo json_encode(['success' => false, 'message' => 'Missing session ID']);
    exit();
}

function nexusPaymentPlanLabel(string $type, ?array $pack, ?array $plan): string
{
    if ($type === 'points') {
        return (string)($pack['name'] ?? 'Point Pack');
    }
    return (string)($plan['name'] ?? 'Nexus Premium');
}

function nexusFinishPayment(
    PDO $conn,
    array $session,
    int $userId,
    string $type,
    string $status,
    ?array $pack,
    ?array $plan,
    bool $notify,
    string $message
): array {
    $packageId = $pack ? (int)$pack['id'] : 0;
    $planId = $plan ? (int)$plan['plan_id'] : 0;
    $fallback = $type === 'points'
        ? (float)($pack['price'] ?? 0)
        : (float)($plan['price'] ?? 0);
    $txn = [];
    try {
        $txn = nexusRecordPaymentTransaction(
            $conn,
            $session,
            $userId,
            $type,
            $status,
            $planId > 0 ? $planId : null,
            $packageId > 0 ? $packageId : null,
            $fallback
        ) ?: [];
    } catch (Throwable $e) {
        $txn = [];
    }

    $txn['plan'] = nexusPaymentPlanLabel($type, $pack, $plan);
    $txn['type'] = $type === 'points' ? 'points' : 'premium';
    $txn['status'] = $status;

    if (!empty($txn['raw_id']) && empty($txn['duplicate'])) {
        nexusBroadcastTransaction($conn, $txn, $userId);
    }

    $extra = [];
    if ($type === 'points') {
        $extra['points'] = nexusCurrentUserPoints($conn, $userId);
        $extra['points_added'] = $status === 'success' ? (int)($pack['points'] ?? 0) : 0;
    }
    if ($notify && empty($txn['duplicate'])) {
        nexusNotifyPayment($conn, $userId, $type, $status === 'success', $message, $extra);
    }

    return $txn;
}

try {
    $session = stripeRequest('GET', '/v1/checkout/sessions/' . $sessionId);

    if (($session['client_reference_id'] ?? '') != $userId) {
        echo json_encode(['success' => false, 'message' => 'Session mismatch']);
        exit();
    }

    $type = strtolower((string)($session['metadata']['type'] ?? 'premium'));
    $type = $type === 'points' ? 'points' : 'premium';
    $paid = (($session['payment_status'] ?? '') === 'paid');
    $plan = $type === 'premium' ? nexusPremiumPlan($conn) : null;
    $pack = null;
    if ($type === 'points') {
        $packageId = (int)($session['metadata']['package_id'] ?? $session['metadata']['pack'] ?? 0);
        $pack = $packageId > 0 ? nexusGetPointPackage($conn, $packageId, false) : null;
        if (!$pack) {
            $points = (int)($session['metadata']['points'] ?? 0);
            $pack = [
                'id' => $packageId,
                'name' => (string)($session['metadata']['pack_name'] ?? 'Point Pack'),
                'points' => $points,
                'price' => 0,
            ];
        }
    }

    if (!$paid) {
        $failMessage = $type === 'points'
            ? 'Your point top-up did not complete. No points were added.'
            : 'Your Premium payment did not complete.';
        nexusFinishPayment($conn, $session, $userId, $type, 'failed', $pack, $plan, true, $failMessage);
        echo json_encode([
            'success' => false,
            'type' => $type,
            'message' => $failMessage,
        ]);
        exit();
    }

    if ($type === 'points') {
        $points = (int)($pack['points'] ?? $session['metadata']['points'] ?? 0);
        if ($points <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid point pack']);
            exit();
        }

        $gwTxn = (string)($session['payment_intent'] ?? $session['id'] ?? '');
        if ($gwTxn !== '' && nexusPaymentAlreadyRecorded($conn, $gwTxn)) {
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
        $conn->commit();

        $message = 'Your purchase of ' . number_format($points) . ' points was successful.';
        nexusFinishPayment($conn, $session, $userId, 'points', 'success', $pack, null, true, $message);

        echo json_encode([
            'success' => true,
            'type' => 'points',
            'points_added' => $points,
            'points' => nexusCurrentUserPoints($conn, $userId),
            'message' => $message,
        ]);
        exit();
    }

    $stmt = $conn->prepare("SELECT is_premium FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $alreadyPremium = (bool)$stmt->fetchColumn();

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
        $conn->commit();
    }

    $message = 'Premium activated. Enjoy larger rooms and an unlimited watchlist.';
    nexusFinishPayment($conn, $session, $userId, 'premium', 'success', null, $plan, !$alreadyPremium, $message);

    echo json_encode(['success' => true, 'type' => 'premium', 'message' => $message]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
