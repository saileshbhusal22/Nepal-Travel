<?php
/**
 * esewa_callback.php
 * eSewa redirects here after payment (success or failure) for a subscription.
 *
 * Converted from khalti_callback.php — uses eSewa v2 epayment API.
 * FIXES applied (mirrored from esewa_booking_callback.php):
 * 1. Amount format in status-check API matches initiate (plain integer, no ".00").
 * 2. Status comparison is case-insensitive (strtoupper).
 * 3. total_amount from eSewa response is stripped of commas before float cast.
 * 4. eSewa V2 URL quirk: "?data=" may be appended after "?status=success"
 *    making PHP see status as "success?data=eyJ..." and $_GET['data'] empty.
 * 5. Detailed error logging throughout.
 */
session_start();
require_once __DIR__ . '/../config/db.php';

// ── Auth guard ──────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// ── Config (must match esewa_initiate.php) ──────────────────────
define('ESEWA_SECRET_KEY',   '8gBm/:&EnhH.1/q');   // sandbox secret
define('ESEWA_PRODUCT_CODE', 'EPAYTEST');            // sandbox product code
define('ESEWA_STATUS_URL',   'https://rc-epay.esewa.com.np/api/epay/transaction/status/');
// For production:
// define('ESEWA_SECRET_KEY',   '<your_live_secret>');
// define('ESEWA_PRODUCT_CODE', '<your_live_product_code>');
// define('ESEWA_STATUS_URL',   'https://epay.esewa.com.np/api/epay/transaction/status/');

// ── eSewa response signature verifier ───────────────────────────
function verify_esewa_signature(array $data, string $secret): bool {
    if (empty($data['signature']) || empty($data['signed_field_names'])) {
        error_log("[eSewa Sub Callback] Missing signature or signed_field_names.");
        return false;
    }
    $fields = explode(',', $data['signed_field_names']);
    $parts  = [];
    foreach ($fields as $field) {
        $parts[] = trim($field) . '=' . ($data[trim($field)] ?? '');
    }
    $message  = implode(',', $parts);
    $expected = base64_encode(hash_hmac('sha256', $message, $secret, true));
    error_log("[eSewa Sub Callback] Signature check — message: $message");
    error_log("[eSewa Sub Callback] Signature check — expected: $expected | received: " . $data['signature']);
    return hash_equals($expected, $data['signature']);
}

// ── Amount formatter — must match what was sent in initiate ─────
// Plain integer string for whole numbers, 2 decimal places otherwise.
function esewa_format_amount(float $amount): string {
    return (fmod($amount, 1.0) == 0)
        ? (string)(int)$amount
        : number_format($amount, 2, '.', '');
}

// ── FIX: Parse status & data — handle eSewa V2 URL quirk ────────
// eSewa V2 may append "?data=..." instead of "&data=..." if the
// success_url already contains a query string, producing:
//   esewa_callback.php?status=success?data=eyJ...
// PHP then puts "success?data=eyJ..." into $_GET['status'] and
// leaves $_GET['data'] empty. We split it manually here.
$raw_status = $_GET['status'] ?? '';
$encoded    = $_GET['data']   ?? '';

if (strpos($raw_status, '?data=') !== false) {
    $parts   = explode('?data=', $raw_status);
    $status  = trim($parts[0]);
    $encoded = trim($parts[1]);
} else {
    $status = trim($raw_status);
}

error_log("[eSewa Sub Callback] Parsed status: '$status' | Data present: " . (!empty($encoded) ? 'Yes' : 'No'));

// ── Handle failure redirect ─────────────────────────────────────
if ($status === 'failure') {
    if ($encoded) {
        $decoded          = json_decode(base64_decode($encoded), true);
        $transaction_uuid = $decoded['transaction_uuid'] ?? '';
        error_log("[eSewa Sub Callback] Explicit failure for UUID: $transaction_uuid");
        if ($transaction_uuid && isset($_SESSION['pending_esewa_subs'][$transaction_uuid])) {
            unset($_SESSION['pending_esewa_subs'][$transaction_uuid]);
        }
    } else {
        error_log("[eSewa Sub Callback] Failure callback with no data payload.");
    }
    header('Location: subscription.php?msg=payment_failed');
    exit;
}

// ── Decode eSewa response data (base64 JSON) ────────────────────
if (empty($encoded)) {
    error_log("[eSewa Sub Callback] Error: No data payload in success URL.");
    header('Location: subscription.php?msg=payment_failed');
    exit;
}

$decoded = json_decode(base64_decode($encoded), true);
if (!$decoded) {
    error_log("[eSewa Sub Callback] Error: JSON decode failed. Raw: " . base64_decode($encoded));
    header('Location: subscription.php?msg=payment_failed');
    exit;
}
error_log("[eSewa Sub Callback] Decoded data: " . json_encode($decoded));

// ── Verify eSewa signature ──────────────────────────────────────
if (!verify_esewa_signature($decoded, ESEWA_SECRET_KEY)) {
    error_log("[eSewa Sub Callback] Signature mismatch. Aborting.");
    header('Location: subscription.php?msg=payment_failed');
    exit;
}

$transaction_uuid = $decoded['transaction_uuid'] ?? '';
$esewa_txn_id     = $decoded['transaction_id']   ?? '';

// Strip commas from total_amount before float cast (eSewa sometimes returns "1,500")
$paid_amount = (float)str_replace(',', '', $decoded['total_amount'] ?? '0');

error_log("[eSewa Sub Callback] UUID: $transaction_uuid | eSewa TXN ID: $esewa_txn_id | Paid: $paid_amount");

if (empty($transaction_uuid)) {
    error_log("[eSewa Sub Callback] Empty transaction_uuid. Aborting.");
    header('Location: subscription.php?msg=payment_failed');
    exit;
}

// ── Status check via eSewa API — amount format must match initiate
$paid_amount_str = esewa_format_amount($paid_amount);

$status_url = ESEWA_STATUS_URL . '?' . http_build_query([
    'product_code'     => ESEWA_PRODUCT_CODE,
    'transaction_uuid' => $transaction_uuid,
    'total_amount'     => $paid_amount_str,
]);

error_log("[eSewa Sub Callback] Status check URL: $status_url");

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
$curl_error   = curl_error($ch);
curl_close($ch);

error_log("[eSewa Sub Callback] Status API HTTP $http_code | cURL error: '$curl_error' | Response: $api_response");

$api_result = json_decode($api_response, true);

// Case-insensitive status comparison
if ($http_code !== 200 || strtoupper($api_result['status'] ?? '') !== 'COMPLETE') {
    error_log("[eSewa Sub Callback] Status check did not return COMPLETE. status='" . ($api_result['status'] ?? 'null') . "'");
    header('Location: subscription.php?msg=payment_failed');
    exit;
}

// ── Retrieve the pending subscription from session ───────────────
if (!isset($_SESSION['pending_esewa_subs'][$transaction_uuid])) {
    error_log("[eSewa Sub Callback] No pending subscription found in session for UUID: $transaction_uuid (may already be processed or session expired).");
    header('Location: subscription.php?msg=already_activated');
    exit;
}

$pending_sub = $_SESSION['pending_esewa_subs'][$transaction_uuid];

// Fetch plan details to get duration_days
$plan_id = (int)$pending_sub['plan_id'];
$pStmt = $conn->query("SELECT duration_days FROM subscription_plans WHERE id = $plan_id");
$plan = $pStmt->fetch_assoc();

if (!$plan) {
    error_log("[eSewa Sub Callback] Plan $plan_id no longer exists.");
    header('Location: subscription.php?msg=payment_failed');
    exit;
}

// ── Double-check amount (anti-tampering) ────────────────────────
$expected_amount = (float)$pending_sub['amount_npr'];
if (abs($paid_amount - $expected_amount) > 1) { // allow NPR 1 tolerance for rounding
    error_log("[eSewa Sub Callback] Amount mismatch! expected NPR $expected_amount, got NPR $paid_amount");
    header('Location: subscription.php?msg=amount_mismatch');
    exit;
}

// ── Activate the subscription ────────────────────────────────────
$starts   = date('Y-m-d H:i:s');
$expires  = date('Y-m-d H:i:s', strtotime('+' . (int)$plan['duration_days'] . ' days'));
$safe_txn = $conn->real_escape_string($esewa_txn_id ?: $transaction_uuid);

$stmt = $conn->prepare(
    "INSERT INTO user_subscriptions
        (user_id, plan_id, status, payment_method, payment_ref, amount_paid, starts_at, expires_at, approved_at)
     VALUES (?, ?, 'active', 'esewa', ?, ?, ?, ?, NOW())"
);
$stmt->bind_param("iisdss", $pending_sub['user_id'], $pending_sub['plan_id'], $safe_txn, $expected_amount, $starts, $expires);

if (!$stmt->execute()) {
    error_log("eSewa callback subscription insert failed: " . $stmt->error);
    $stmt->close();
    header('Location: subscription.php?esewa_error=' . urlencode('Something went wrong saving your subscription. Please contact support.'));
    exit;
}
$sub_id = $stmt->insert_id;
$stmt->close();

error_log("[eSewa Sub Callback] Subscription #$sub_id created and activated. eSewa TXN: $safe_txn");

// Remove from session
unset($_SESSION['pending_esewa_subs'][$transaction_uuid]);

// ── Redirect with success ────────────────────────────────────────
header('Location: subscription.php?msg=esewa_success');
exit;