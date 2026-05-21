<?php
/**
 * khalti_initiate.php
 * Called when user submits the Khalti payment form for a subscription.
 * Creates a pending subscription then redirects to Khalti checkout.
 */
session_start();
require_once __DIR__ . '/../config/db.php';

// ── Auth guard ──────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php?redirect=subscription');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// ── Only accept POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'khalti_pay') {
    header('Location: subscription.php');
    exit;
}

// ── Config ──────────────────────────────────────────────────────
define('KHALTI_SECRET_KEY', '1f7407dfa7ff438cbda63061adfbc7f6');
define('KHALTI_API_URL',    'https://dev.khalti.com/api/v2/epayment/initiate/'); // sandbox
// For production: 'https://khalti.com/api/v2/epayment/initiate/'

define('SITE_URL', 'http://localhost/Nepal-Travel/Public');

$plan_id = (int)$_POST['plan_id'];
if ($plan_id <= 0) {
    header('Location: subscription.php?msg=invalid_plan');
    exit;
}

// ── Fetch plan (prepared) ───────────────────────────────────────
$pStmt = $conn->prepare("SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1");
$pStmt->bind_param("i", $plan_id);
$pStmt->execute();
$plan = $pStmt->get_result()->fetch_assoc();
$pStmt->close();

if (!$plan) {
    header('Location: subscription.php?msg=invalid_plan');
    exit;
}
$amount_npr = (float)$plan['price'];

// ════════════════════════════════════════════════════════════════
// ── DUPLICATE SUBSCRIPTION CHECKS ───────────────────────────────
// Block if user already has an active OR pending subscription
// for this exact plan. Pending = Khalti payment in progress.
// ════════════════════════════════════════════════════════════════

// Layer 1 — same plan, active or pending
/*
$dupStmt = $conn->prepare(
    "SELECT id FROM user_subscriptions
     WHERE user_id = ? AND plan_id = ? AND status IN ('active', 'pending')
     LIMIT 1"
);
$dupStmt->bind_param("ii", $user_id, $plan_id);
$dupStmt->execute();
$dupStmt->bind_result($existing_sub_id);
$dupStmt->fetch();
$dupStmt->close();

if ($existing_sub_id) {
    header('Location: subscription.php?msg=already_subscribed&sub_id=' . $existing_sub_id);
    exit;
}
*/

// Layer 2 — any active subscription at all
// (one active sub at a time policy — remove this block if you allow multiple)
/*
$anyStmt = $conn->prepare(
    "SELECT id FROM user_subscriptions
     WHERE user_id = ? AND status = 'active'
     LIMIT 1"
);
$anyStmt->bind_param("i", $user_id);
$anyStmt->execute();
$anyStmt->bind_result($any_active_sub_id);
$anyStmt->fetch();
$anyStmt->close();

if ($any_active_sub_id) {
    header('Location: subscription.php?msg=already_has_active');
    exit;
}
*/

// ── Fetch user info (prepared) ──────────────────────────────────
$uStmt = $conn->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
$uStmt->bind_param("i", $user_id);
$uStmt->execute();
$user = $uStmt->get_result()->fetch_assoc();
$uStmt->close();

// ── Create PENDING subscription (prepared) ──────────────────────
$iStmt = $conn->prepare(
    "INSERT INTO user_subscriptions
        (user_id, plan_id, status, payment_method, amount_paid)
     VALUES (?, ?, 'pending', 'khalti', ?)"
);
$iStmt->bind_param("iid", $user_id, $plan_id, $amount_npr);
if (!$iStmt->execute()) {
    error_log("Subscription insert failed: " . $iStmt->error);
    $iStmt->close();
    header('Location: subscription.php?khalti_error=' . urlencode('Something went wrong. Please try again.'));
    exit;
}
$sub_id = $iStmt->insert_id;
$iStmt->close();

// ── Build Khalti payload ────────────────────────────────────────
// Khalti requires amount in PAISA (1 NPR = 100 paisa)
$payload = json_encode([
    'return_url'          => SITE_URL . '/khalti_callback.php',
    'website_url'         => SITE_URL,
    'amount'              => (int)($amount_npr * 100),
    'purchase_order_id'   => 'SUB-' . $sub_id,
    'purchase_order_name' => $plan['display_name'] . ' Subscription',
    'customer_info'       => [
        'name'  => $user['full_name'] ?? 'Customer',
        'email' => $user['email']     ?? '',
        'phone' => $user['phone']     ?? '9800000000',
    ],
]);

// ── Call Khalti initiate API ────────────────────────────────────
$ch = curl_init(KHALTI_API_URL);
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

if ($http_code === 200 && !empty($result['payment_url'])) {
    // ── Save pidx for verification later (prepared) ───────────────
    $pidx  = $result['pidx'];
    $uStmt = $conn->prepare("UPDATE user_subscriptions SET payment_ref = ? WHERE id = ?");
    $uStmt->bind_param("si", $pidx, $sub_id);
    $uStmt->execute();
    $uStmt->close();

    // ── Redirect to Khalti checkout ───────────────────────────────
    header('Location: ' . $result['payment_url']);
    exit;

} else {
    // ── API error — delete the pending sub and go back ────────────
    $delStmt = $conn->prepare("DELETE FROM user_subscriptions WHERE id = ?");
    $delStmt->bind_param("i", $sub_id);
    $delStmt->execute();
    $delStmt->close();

    $error = urlencode($result['detail'] ?? 'Khalti payment initiation failed. Please try again.');
    header('Location: subscription.php?khalti_error=' . $error);
    exit;
}