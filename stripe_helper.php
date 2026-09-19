<?php
// stripe_helper.php — Native PHP (No Composer required)

// ========== STRIPE TEST KEYS ==========
define('STRIPE_SECRET_KEY', 'sk_test_51U7dOOQ4txrxX3UywInn3MozdXDvyhMNeytcf5Bmbssb5U6izwGhfshD7tBgu0GVGZXTlhjd0n8yK09FvK0mtBeo00GGMHNME6');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51U7dOOQ4txrxX3UyKFl8Esnat3ahKw22hUWtA1HpDKSozJXz9UBofzTjLNreSIOlt8sN6WM4gkS8PCw2k7fuqhUO00CcN5mWd8');

// ========== HARDCODED PLAN DETAILS ==========
define('PREMIUM_PRICE_ID', 'price_1U7s7JQ4txrxX3UyKuyL2ZQo'); // ← Replace with your actual Stripe Price ID
define('PREMIUM_PLAN_ID', 1);
define('PREMIUM_DURATION_DAYS', 30);

// ========== GENERIC REQUEST FUNCTION ==========
function stripeRequest($method, $path, $params = []) {
    $url = 'https://api.stripe.com' . $path;
    $headers = [
        'Authorization: Bearer ' . STRIPE_SECRET_KEY,
        'Content-Type: application/x-www-form-urlencoded'
    ];

    $ch = curl_init();
    if ($method === 'GET') {
        if (!empty($params)) $url .= '?' . http_build_query($params);
    } else {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false   // Same as your Pusher helper for local dev
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) throw new Exception('Stripe API connection failed: ' . $error);
    $decoded = json_decode($response, true);
    if ($httpCode >= 400) {
        $message = $decoded['error']['message'] ?? 'Unknown Stripe error';
        throw new Exception("Stripe API error ($httpCode): $message");
    }
    return $decoded;
}

// ========== CREATE CHECKOUT SESSION ==========
function stripeCreateCheckoutSession($params) {
    $required = ['mode', 'user_id', 'success_url', 'cancel_url'];
    foreach ($required as $field) {
        if (empty($params[$field])) throw new Exception("Missing required parameter: $field");
    }
    if (empty($params['price_id']) && empty($params['price_data'])) {
        throw new Exception("Missing required parameter: price_id");
    }

    $requestParams = [
        'payment_method_types[]' => 'card',
        'mode' => $params['mode'],
        'line_items[0][quantity]' => 1,
        'success_url' => $params['success_url'],
        'cancel_url' => $params['cancel_url'],
        'client_reference_id' => (string)$params['user_id'],
        'metadata[user_id]' => $params['user_id'],
        'metadata[type]' => $params['metadata']['type'] ?? 'premium'
    ];

    if (!empty($params['price_id'])) {
        $requestParams['line_items[0][price]'] = $params['price_id'];
    } else {
        $priceData = $params['price_data'];
        $requestParams['line_items[0][price_data][currency]'] = $priceData['currency'] ?? 'usd';
        $requestParams['line_items[0][price_data][unit_amount]'] = (int) $priceData['unit_amount'];
        $requestParams['line_items[0][price_data][product_data][name]'] = $priceData['name'] ?? 'Nexus Top-up';
    }

    if (!empty($params['metadata']) && is_array($params['metadata'])) {
        foreach ($params['metadata'] as $key => $value) {
            $requestParams['metadata[' . $key . ']'] = $value;
        }
    }

    return stripeRequest('POST', '/v1/checkout/sessions', $requestParams);
}

function nexusPublicBaseUrl(): string
{
    $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $https = $forwarded === 'https'
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';

    return ($https ? 'https' : 'http') . '://' . $host;
}

function nexusPointPacks(): array
{
    return [
        'starter' => [
            'id' => 'starter',
            'plan_id' => 2,
            'points' => 500,
            'price' => 0.99,
            'name' => '500 Points',
            'label' => 'Starter',
        ],
        'boost' => [
            'id' => 'boost',
            'plan_id' => 3,
            'points' => 1500,
            'price' => 2.49,
            'name' => '1,500 Points',
            'label' => 'Boost',
        ],
        'bundle' => [
            'id' => 'bundle',
            'plan_id' => 4,
            'points' => 4000,
            'price' => 4.99,
            'name' => '4,000 Points',
            'label' => 'Bundle',
        ],
        'mega' => [
            'id' => 'mega',
            'plan_id' => 5,
            'points' => 10000,
            'price' => 9.99,
            'name' => '10,000 Points',
            'label' => 'Mega',
        ],
    ];
}