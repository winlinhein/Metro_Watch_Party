<?php
// stripe_helper.php — Native PHP (No Composer required)
require_once __DIR__ . '/curl_ssl_helper.php';

// Test-mode keys for the shared school project. Committed on purpose so a
// classmate can run the checkout flow with no setup; a real environment
// variable overrides them. Rotate these before any real use.
if (!defined('STRIPE_SECRET_KEY')) {
    define('STRIPE_SECRET_KEY', nexusAppEnv(
        'STRIPE_SECRET_KEY',
        'sk_test_51UQsoq2OBZxVKmwB1dnki2wu8prZXmbiwa3svVFldg6Aeuz0L1HUmoIhZ3W77EloPxi7BI5EEYsbiqPWhtmAm5dX00iijRxsLG'
    ));
}
if (!defined('STRIPE_PUBLISHABLE_KEY')) {
    define('STRIPE_PUBLISHABLE_KEY', nexusAppEnv(
        'STRIPE_PUBLISHABLE_KEY',
        'pk_test_51UQsoq2OBZxVKmwBMxYEXUs4FK4fxMZQ0RLDavRYFXesTEAAHWeS9ymQbHsHEWKKKAVLN8OsUXgo8kLXBkuAlJSx00yjP5HSoZ'
    ));
}

if (!defined('PREMIUM_PRICE_ID')) {
    define('PREMIUM_PRICE_ID', nexusAppEnv('PREMIUM_PRICE_ID', 'price_1UTcQX2OBZxVKmwB3vlzJEVQ'));
}
if (!defined('PREMIUM_PLAN_ID')) {
    define('PREMIUM_PLAN_ID', 1);
}
if (!defined('PREMIUM_DURATION_DAYS')) {
    define('PREMIUM_DURATION_DAYS', 30);
}

function stripeRequest($method, $path, $params = []) {
    $url = 'https://api.stripe.com' . $path;
    $headers = [
        'Authorization: Bearer ' . STRIPE_SECRET_KEY,
        'Content-Type: application/x-www-form-urlencoded'
    ];

    if ($method === 'GET' && !empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 10,
    ];
    if ($method !== 'GET') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = http_build_query($params);
    }

    [$response, $httpCode, $error] = nexusCurlExec($url, $options);

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
    return [];
}