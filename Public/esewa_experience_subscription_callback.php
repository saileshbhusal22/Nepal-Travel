<?php

/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');
define('ESEWA_PRODUCT_CODE', 'EPAYTEST');
define('ESEWA_STATUS_URL', 'https://rc-epay.esewa.com.np/api/epay/transaction/status/');

function verify_esewa_signature(array $data, string $secret): bool {
    if (empty($data['signature']) || empty($data['signed_field_names'])) {
        return false;
    }
    $parts = [];
    foreach (explode(',', $data['signed_field_names']) as $field) {
        $parts[] = trim($field) . '=' . ($data[trim($field)] ?? '');
    }
    $expected = base64_encode(hash_hmac('sha256', implode(',', $parts), $secret, true));
    return hash_equals($expected, $data['signature']);
}

function esewa_format_amount(float $amount): string {
    return (fmod($amount, 1.0) == 0) ? (string)(int)$amount : number_format($amount, 2, '.', '');
}

$raw_status = $_GET['status'] ?? '';
$encoded = $_GET['data'] ?? '';

if (strpos($raw_status, '?data=') !== false) {
    $parts = explode('?data=', $raw_status);
    $status = trim($parts[0]);
    $encoded = trim($parts[1]);
} else {
    $status = trim($raw_status);
}

if ($status === 'failure') {
    if ($encoded) {
        $decoded = json_decode(base64_decode($encoded), true);
        $uuid = $decoded['transaction_uuid'] ?? '';
        if ($uuid && isset($_SESSION['pending_esewa_experience_subs'][$uuid])) {
            unset($_SESSION['pending_esewa_experience_subs'][$uuid]);
        }
    }
    header('Location: experience-subscription.php?msg=payment_failed');
    exit;
}

if (empty($encoded)) {
    header('Location: experience-subscription.php?msg=payment_failed');
    exit;
}

$decoded = json_decode(base64_decode($encoded), true);
if (!$decoded || !verify_esewa_signature($decoded, ESEWA_SECRET_KEY)) {
    header('Location: experience-subscription.php?msg=payment_failed');
    exit;
}

$transaction_uuid = $decoded['transaction_uuid'] ?? '';
$esewa_txn_id = $decoded['transaction_id'] ?? '';
$paid_amount = (float)str_replace(',', '', $decoded['total_amount'] ?? '0');

if ($transaction_uuid === '') {
    header('Location: experience-subscription.php?msg=payment_failed');
    exit;
}

$status_url = ESEWA_STATUS_URL . '?' . http_build_query([
    'product_code' => ESEWA_PRODUCT_CODE,
    'transaction_uuid' => $transaction_uuid,
    'total_amount' => esewa_format_amount($paid_amount),
]);

$ch = curl_init($status_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET => true,
    CURLOPT_TIMEOUT => 30,
]);
$api_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$api_result = json_decode($api_response, true);

if ($http_code !== 200 || strtoupper($api_result['status'] ?? '') !== 'COMPLETE') {
    header('Location: experience-subscription.php?msg=payment_failed');
    exit;
}

if (!isset($_SESSION['pending_esewa_experience_subs'][$transaction_uuid])) {
    header('Location: experience-subscription.php?msg=already_activated');
    exit;
}

$pending = $_SESSION['pending_esewa_experience_subs'][$transaction_uuid];
$plan_id = (int)$pending['plan_id'];
$expected_amount = (float)$pending['amount_npr'];

if (abs($paid_amount - $expected_amount) > 1) {
    header('Location: experience-subscription.php?msg=amount_mismatch');
    exit;
}

$pStmt = $conn->prepare('SELECT duration_days FROM experience_subscription_plans WHERE id = ?');
$pStmt->bind_param('i', $plan_id);
$pStmt->execute();
$plan = $pStmt->get_result()->fetch_assoc();
$pStmt->close();

if (!$plan) {
    header('Location: experience-subscription.php?msg=payment_failed');
    exit;
}

$duration_days = (int)$plan['duration_days'];
$payment_ref = $esewa_txn_id ?: $transaction_uuid;
$starts = date('Y-m-d H:i:s');
$expires = date('Y-m-d H:i:s', strtotime("+{$duration_days} days"));

$stmt = $conn->prepare(
    "INSERT INTO user_experience_subscriptions
        (user_id, plan_id, status, payment_method, payment_ref, amount_paid, starts_at, expires_at, approved_at)
     VALUES (?, ?, 'active', 'esewa', ?, ?, ?, ?, NOW())"
);
$stmt->bind_param('iisdss', $user_id, $plan_id, $payment_ref, $expected_amount, $starts, $expires);

if (!$stmt->execute()) {
    $stmt->close();
    header('Location: experience-subscription.php?esewa_error=' . urlencode('Failed to save subscription.'));
    exit;
}
$stmt->close();

unset($_SESSION['pending_esewa_experience_subs'][$transaction_uuid]);
header('Location: experience-subscription.php?msg=esewa_success');
exit;
