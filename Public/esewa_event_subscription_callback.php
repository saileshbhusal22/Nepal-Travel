<?php
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../user/mail.php';

// Auth guard
if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// Config
define('ESEWA_SECRET_KEY',   '8gBm/:&EnhH.1/q');   // sandbox secret
define('ESEWA_PRODUCT_CODE', 'EPAYTEST');            // sandbox product code
define('ESEWA_STATUS_URL',   'https://rc-epay.esewa.com.np/api/epay/transaction/status/');

// Signature verifier
function verify_esewa_signature(array $data, string $secret): bool {
    if (empty($data['signature']) || empty($data['signed_field_names'])) {
        return false;
    }
    $fields = explode(',', $data['signed_field_names']);
    $parts  = [];
    foreach ($fields as $field) {
        $parts[] = trim($field) . '=' . ($data[trim($field)] ?? '');
    }
    $message  = implode(',', $parts);
    $expected = base64_encode(hash_hmac('sha256', $message, $secret, true));
    return hash_equals($expected, $data['signature']);
}

// Amount formatter
function esewa_format_amount(float $amount): string {
    return (fmod($amount, 1.0) == 0)
        ? (string)(int)$amount
        : number_format($amount, 2, '.', '');
}

$raw_status = $_GET['status'] ?? '';
$encoded    = $_GET['data']   ?? '';

if (strpos($raw_status, '?data=') !== false) {
    $parts   = explode('?data=', $raw_status);
    $status  = trim($parts[0]);
    $encoded = trim($parts[1]);
} else {
    $status = trim($raw_status);
}

error_log("[eSewa Event Sub Callback] Parsed status: '$status'");

// Failure redirect
if ($status === 'failure') {
    if ($encoded) {
        $decoded          = json_decode(base64_decode($encoded), true);
        $transaction_uuid = $decoded['transaction_uuid'] ?? '';
        if ($transaction_uuid && isset($_SESSION['pending_esewa_event_subs'][$transaction_uuid])) {
            unset($_SESSION['pending_esewa_event_subs'][$transaction_uuid]);
        }
    }
    header('Location: event-subscription.php?msg=payment_failed');
    exit;
}

if (empty($encoded)) {
    header('Location: event-subscription.php?msg=payment_failed');
    exit;
}

$decoded = json_decode(base64_decode($encoded), true);
if (!$decoded) {
    header('Location: event-subscription.php?msg=payment_failed');
    exit;
}

// Verify signature
if (!verify_esewa_signature($decoded, ESEWA_SECRET_KEY)) {
    error_log("[eSewa Event Sub Callback] Signature verification failed.");
    header('Location: event-subscription.php?msg=payment_failed');
    exit;
}

$transaction_uuid = $decoded['transaction_uuid'] ?? '';
$esewa_txn_id     = $decoded['transaction_id']   ?? '';
$paid_amount      = (float)str_replace(',', '', $decoded['total_amount'] ?? '0');

if (empty($transaction_uuid)) {
    header('Location: event-subscription.php?msg=payment_failed');
    exit;
}

// Perform Lookup Status Check via eSewa API
$paid_amount_str = esewa_format_amount($paid_amount);
$status_url = ESEWA_STATUS_URL . '?' . http_build_query([
    'product_code'     => ESEWA_PRODUCT_CODE,
    'transaction_uuid' => $transaction_uuid,
    'total_amount'     => $paid_amount_str,
]);

$ch = curl_init($status_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET        => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
]);
$api_response = curl_exec($ch);
$http_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$api_result = json_decode($api_response, true);

if ($http_code !== 200 || strtoupper($api_result['status'] ?? '') !== 'COMPLETE') {
    error_log("[eSewa Event Sub Callback] Verification failed or incomplete status.");
    header('Location: event-subscription.php?msg=payment_failed');
    exit;
}

// Retrieve the pending subscription from session
if (!isset($_SESSION['pending_esewa_event_subs'][$transaction_uuid])) {
    header('Location: event-subscription.php?msg=already_activated');
    exit;
}

$pending_sub = $_SESSION['pending_esewa_event_subs'][$transaction_uuid];
$plan_id = (int)$pending_sub['plan_id'];

// Get plan duration
$pStmt = $conn->query("SELECT duration_days, display_name FROM event_subscription_plans WHERE id = $plan_id");
$plan = $pStmt->fetch_assoc();

if (!$plan) {
    header('Location: event-subscription.php?msg=payment_failed');
    exit;
}

// Double-check amount
$expected_amount = (float)$pending_sub['amount_npr'];
if (abs($paid_amount - $expected_amount) > 1) {
    header('Location: event-subscription.php?msg=amount_mismatch');
    exit;
}

// Activate subscription
$starts   = date('Y-m-d H:i:s');
$expires  = date('Y-m-d H:i:s', strtotime('+' . (int)$plan['duration_days'] . ' days'));
$safe_txn = $conn->real_escape_string($esewa_txn_id ?: $transaction_uuid);

$stmt = $conn->prepare(
    "INSERT INTO user_event_subscriptions
        (user_id, plan_id, status, payment_method, payment_ref, amount_paid, starts_at, expires_at, approved_at)
     VALUES (?, ?, 'active', 'esewa', ?, ?, ?, ?, NOW())"
);
$stmt->bind_param("iisdss", $pending_sub['user_id'], $pending_sub['plan_id'], $safe_txn, $expected_amount, $starts, $expires);

if (!$stmt->execute()) {
    error_log("[eSewa Event Sub Callback] Insert failed: " . $stmt->error);
    $stmt->close();
    header('Location: event-subscription.php?esewa_error=' . urlencode('Something went wrong saving your subscription. Please contact support.'));
    exit;
}
$stmt->close();

// Send Email
$uStmt = $conn->query("SELECT email, full_name FROM users WHERE id = $user_id");
$user = $uStmt->fetch_assoc();
if ($user) {
    // sendSubscriptionSuccessEmail($user['email'], $user['full_name'], $plan['display_name'] ?? ('Plan ' . $plan_id), "Event & Experience Subscription");
}

// Clear session state
unset($_SESSION['pending_esewa_event_subs'][$transaction_uuid]);

header('Location: event-subscription.php?msg=esewa_success');
exit;
