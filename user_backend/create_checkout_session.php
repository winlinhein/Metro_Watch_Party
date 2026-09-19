<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../stripe_helper.php';

$userId = (int) $_SESSION['user_id'];
$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$type = strtolower((string) ($input['type'] ?? 'premium'));
$base = nexusPublicBaseUrl();
$successUrl = $base . '/user/dashboard.php?payment=success&session_id={CHECKOUT_SESSION_ID}';
$cancelUrl = $base . '/user/dashboard.php?payment=cancelled';

try {
    if ($type === 'points') {
        $packs = nexusPointPacks();
        $packId = (string) ($input['pack'] ?? '');
        if (!isset($packs[$packId])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid point pack']);
            exit();
        }

        $pack = $packs[$packId];
        $session = stripeCreateCheckoutSession([
            'mode'        => 'payment',
            'user_id'     => $userId,
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
            'price_data'  => [
                'currency'    => 'usd',
                'unit_amount' => (int) round($pack['price'] * 100),
                'name'        => $pack['name'] . ' Top-up',
            ],
            'metadata'    => [
                'type'    => 'points',
                'pack'    => $pack['id'],
                'points'  => $pack['points'],
                'plan_id' => $pack['plan_id'],
            ],
        ]);
        echo json_encode(['id' => $session['id']]);
        exit();
    }

    $session = stripeCreateCheckoutSession([
        'price_id'    => PREMIUM_PRICE_ID,
        'mode'        => 'subscription',
        'user_id'     => $userId,
        'success_url' => $successUrl,
        'cancel_url'  => $cancelUrl,
        'metadata'    => ['type' => 'premium'],
    ]);
    echo json_encode(['id' => $session['id']]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
