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
require_once __DIR__ . '/../point_pack_helper.php';
ensureAppSchema($conn);

$userId = (int) $_SESSION['user_id'];
$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$type = strtolower((string) ($input['type'] ?? 'premium'));
$base = nexusPublicBaseUrl();
$successUrl = $base . '/user/dashboard.php?payment=success&session_id={CHECKOUT_SESSION_ID}';
$cancelUrl = $base . '/user/dashboard.php?payment=cancelled&session_id={CHECKOUT_SESSION_ID}';

try {
    if ($type === 'points') {
        $packRef = $input['pack'] ?? $input['package_id'] ?? 0;
        $pack = null;
        if (is_numeric($packRef) && (int)$packRef > 0) {
            $pack = nexusGetPointPackage($conn, (int)$packRef, true);
        }
        if (!$pack) {
            $needle = strtolower(trim((string)$packRef));
            foreach (nexusListPointPackages($conn, true) as $row) {
                $label = strtolower((string)$row['label']);
                $name = strtolower((string)$row['name']);
                $slug = preg_replace('/[^a-z0-9]+/', '', $label);
                if ($needle === $label || $needle === $name || $needle === $slug || $needle === (string)$row['id']) {
                    $pack = $row;
                    break;
                }
            }
        }
        if (!$pack) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid point pack']);
            exit();
        }

        $session = stripeCreateCheckoutSession([
            'mode'        => 'payment',
            'user_id'     => $userId,
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
            'price_data'  => [
                'currency'    => 'usd',
                'unit_amount' => (int) round(((float)$pack['price']) * 100),
                'name'        => $pack['name'] . ' Top-up',
            ],
            'metadata'    => [
                'type'       => 'points',
                'pack'       => (string)$pack['id'],
                'package_id' => (string)$pack['id'],
                'points'     => (string)$pack['points'],
                'pack_name'  => (string)$pack['name'],
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
