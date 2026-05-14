<?php
/**
 * khalti_callback.php
 * Khalti redirects here after payment with ?pidx=... &status=Completed etc.
 * We verify the payment via Khalti lookup API, then activate the subscription.
 */
session_start();
require_once __DIR__ . '/../config/db.php';

// ── Auth guard ──────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php'); exit;
}
$user_id = (int)$_SESSION['user_id'];

// ── Config (must match khalti_initiate.php) ─────────────────────
define('KHALTI_SECRET_KEY',  '1f7407dfa7ff438cbda63061adfbc7f6'); // ← same key
define('KHALTI_LOOKUP_URL',  'https://dev.khalti.com/api/v2/epayment/lookup/'); // sandbox
// For production: 'https://khalti.com/api/v2/epayment/lookup/'

$pidx   = trim($_GET['pidx']   ?? '');
$status = trim($_GET['status'] ?? '');

// ── Basic guard ─────────────────────────────────────────────────
if (empty($pidx)) {
    header('Location: subscription.php?msg=payment_failed'); exit;
}

// ── If user cancelled ───────────────────────────────────────────
if ($status === 'User canceled') {
    // Mark the matching pending sub as cancelled
    $safe_pidx = $conn->real_escape_string($pidx);
    $conn->query("
        UPDATE user_subscriptions
        SET status = 'cancelled'
        WHERE payment_ref = '$safe_pidx'
          AND user_id = $user_id
          AND status = 'pending'
    ");
    header('Location: subscription.php?msg=payment_cancelled'); exit;
}

// ── Verify with Khalti Lookup API ───────────────────────────────
$payload = json_encode(['pidx' => $pidx]);

$ch = curl_init(KHALTI_LOOKUP_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Key ' . KHALTI_SECRET_KEY,
        'Content-Type: application/json',
    ],
]);
$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

// ── Check Khalti says "Completed" ───────────────────────────────
if ($http_code !== 200 || ($result['status'] ?? '') !== 'Completed') {
    header('Location: subscription.php?msg=payment_failed'); exit;
}

// ── Find the pending subscription by pidx ───────────────────────
$safe_pidx = $conn->real_escape_string($pidx);
$sub_row   = $conn->query("
    SELECT us.*, sp.duration_days
    FROM user_subscriptions us
    JOIN subscription_plans sp ON sp.id = us.plan_id
    WHERE us.payment_ref = '$safe_pidx'
      AND us.user_id     = $user_id
      AND us.status      = 'pending'
    LIMIT 1
");

if (!$sub_row || $sub_row->num_rows === 0) {
    // Already processed or belongs to another user — silently redirect
    header('Location: subscription.php?msg=already_activated'); exit;
}

$sub    = $sub_row->fetch_assoc();
$sub_id = (int)$sub['id'];

// ── Double-check amount (anti-tampering) ────────────────────────
// Khalti returns amount in paisa
$paid_npr = (float)($result['total_amount'] ?? 0) / 100;
$expected = (float)$sub['amount_paid'];
if (abs($paid_npr - $expected) > 1) { // allow NPR 1 tolerance for rounding
    // Log mismatch and reject
    error_log("Khalti amount mismatch: sub#$sub_id expected NPR $expected, got NPR $paid_npr");
    header('Location: subscription.php?msg=amount_mismatch'); exit;
}

// ── Activate the subscription ────────────────────────────────────
$starts  = date('Y-m-d H:i:s');
$expires = date('Y-m-d H:i:s', strtotime('+' . (int)$sub['duration_days'] . ' days'));
$txn_id  = $conn->real_escape_string($result['transaction_id'] ?? $pidx);

$conn->query("
    UPDATE user_subscriptions
    SET status      = 'active',
        starts_at   = '$starts',
        expires_at  = '$expires',
        payment_ref = '$txn_id',
        approved_at = NOW()
    WHERE id = $sub_id
");

// ── Redirect with success ────────────────────────────────────────
header('Location: subscription.php?msg=khalti_success'); exit;