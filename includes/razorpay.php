<?php
// ============================================================
// includes/razorpay.php — Razorpay Integration (pure cURL)
// No Composer required — works on bare LAMP stack
// ============================================================

require_once __DIR__ . '/../config.php';

/**
 * Create a Razorpay Order
 * Returns ['id' => 'order_xxx', 'amount' => ..., ...] or throws Exception
 */
function createRazorpayOrder(float $amount, string $receipt, array $notes = []): array {
    $url  = 'https://api.razorpay.com/v1/orders';
    $data = [
        'amount'   => (int)($amount * 100),   // paise
        'currency' => 'INR',
        'receipt'  => $receipt,
        'notes'    => $notes,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_USERPWD        => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        throw new Exception('cURL error: ' . $curlErr);
    }

    $result = json_decode($response, true);

    if ($httpCode !== 200) {
        $msg = $result['error']['description'] ?? 'Razorpay API error (HTTP ' . $httpCode . ')';
        throw new Exception($msg);
    }

    return $result;
}

/**
 * Verify Razorpay Payment Signature
 */
function verifyRazorpaySignature(string $orderId, string $paymentId, string $signature): bool {
    $payload  = $orderId . '|' . $paymentId;
    $expected = hash_hmac('sha256', $payload, RAZORPAY_KEY_SECRET);
    return hash_equals($expected, $signature);
}

/**
 * Fetch payment details from Razorpay
 */
function fetchRazorpayPayment(string $paymentId): array {
    $url = 'https://api.razorpay.com/v1/payments/' . $paymentId;
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
