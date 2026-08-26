<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../stripe_helper.php';

$userId = (int)$_SESSION['user_id'];

try {
    $session = stripeCreateCheckoutSession([
        'price_id'    => PREMIUM_PRICE_ID,
        'mode'        => 'subscription',
        'user_id'     => $userId,
        'success_url' => 'http://localhost:8000/user/dashboard.php?payment=success&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => 'http://localhost:8000/user/dashboard.php?payment=cancelled',
        'metadata'    => ['type' => 'premium']
    ]);
    echo json_encode(['id' => $session['id']]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}