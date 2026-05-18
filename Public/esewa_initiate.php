<?php
/**
 * esewa_initiate.php
 * Called when user submits the eSewa payment form for a subscription.
 * Creates a pending subscription then redirects to eSewa checkout.
 *
 * Converted from khalti_initiate.php — uses eSewa v2 epayment API.
 * Fix: amount string in HMAC message must EXACTLY match the form's total_amount field.
 * eSewa sandbox prefers plain integers for whole amounts ("100" not "100.00").
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'esewa_pay') {
    header('Location: subscription.php');
    exit;
}

// ── Config ──────────────────────────────────────────────────────
define('ESEWA_SECRET_KEY',   '8gBm/:&EnhH.1/q');   // sandbox secret
define('ESEWA_PRODUCT_CODE', 'EPAYTEST');            // sandbox product code
define('ESEWA_PAYMENT_URL',  'https://rc-epay.esewa.com.np/api/epay/main/v2/form'); // sandbox
// For production:
// define('ESEWA_SECRET_KEY',   '<your_live_secret>');
// define('ESEWA_PRODUCT_CODE', '<your_live_product_code>');
// define('ESEWA_PAYMENT_URL',  'https://epay.esewa.com.np/api/epay/main/v2/form');

define('SITE_URL', 'http://localhost/Nepal-Travel/Public');

// ── eSewa HMAC-SHA256 signature ─────────────────────────────────
// signed_field_names order: total_amount, transaction_uuid, product_code
function esewa_signature(string $total_amount, string $transaction_uuid, string $product_code, string $secret): string {
    $msg = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$product_code}";
    error_log("[eSewa] HMAC message: " . $msg); // remove in production
    return base64_encode(hash_hmac('sha256', $msg, $secret, true));
}

// ── Validate plan_id ────────────────────────────────────────────
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
// for this exact plan.
// ════════════════════════════════════════════════════════════════

// Layer 1 — same plan, active or pending
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

// Layer 2 — any active subscription at all
// (one active sub at a time policy — remove this block if you allow multiple)
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

$transaction_uuid = 'SUB-' . uniqid() . '-' . time();

// Save to session instead of database
if (!isset($_SESSION['pending_esewa_subs'])) {
    $_SESSION['pending_esewa_subs'] = [];
}
$_SESSION['pending_esewa_subs'][$transaction_uuid] = [
    'user_id' => $user_id,
    'plan_id' => $plan_id,
    'amount_npr' => $amount_npr
];

// ── Build amount & signature ────────────────────────────────────
// Use plain integer string for whole amounts — no ".00" suffix.
// eSewa does a string comparison of your signature vs their recomputed one.
$amount_str       = (fmod($amount_npr, 1.0) == 0)
                    ? (string)(int)$amount_npr               // e.g. "1500"
                    : number_format($amount_npr, 2, '.', ''); // e.g. "1500.50"
$total_amount_str = $amount_str; // tax=0, service=0, delivery=0

$signature        = esewa_signature($total_amount_str, $transaction_uuid, ESEWA_PRODUCT_CODE, ESEWA_SECRET_KEY);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirecting to eSewa...</title>
    <style>
        body{font-family:Arial,sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f0f2f5;}
        .box{text-align:center;background:#fff;padding:40px;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.1);}
        .spinner{width:40px;height:40px;border:4px solid #eee;border-top-color:#60bb46;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 16px;}
        @keyframes spin{to{transform:rotate(360deg);}}
        p{color:#555;font-size:14px;}
    </style>
</head>
<body>
    <div class="box">
        <div class="spinner"></div>
        <p>Redirecting to eSewa payment...</p>
        <p style="font-size:12px;color:#aaa;margin-top:8px;">Do not close this window.</p>
    </div>

    <form id="esewaForm" action="<?= htmlspecialchars(ESEWA_PAYMENT_URL) ?>" method="POST">
        <input type="hidden" name="amount"                   value="<?= htmlspecialchars($amount_str) ?>">
        <input type="hidden" name="tax_amount"               value="0">
        <input type="hidden" name="total_amount"             value="<?= htmlspecialchars($total_amount_str) ?>">
        <input type="hidden" name="transaction_uuid"         value="<?= htmlspecialchars($transaction_uuid) ?>">
        <input type="hidden" name="product_code"             value="<?= htmlspecialchars(ESEWA_PRODUCT_CODE) ?>">
        <input type="hidden" name="product_service_charge"   value="0">
        <input type="hidden" name="product_delivery_charge"  value="0">
        <input type="hidden" name="success_url"              value="<?= SITE_URL ?>/esewa_callback.php?status=success">
        <input type="hidden" name="failure_url"              value="<?= SITE_URL ?>/esewa_callback.php?status=failure">
        <input type="hidden" name="signed_field_names"       value="total_amount,transaction_uuid,product_code">
        <input type="hidden" name="signature"                value="<?= htmlspecialchars($signature) ?>">
    </form>

    <script>
        setTimeout(function() {
            document.getElementById('esewaForm').submit();
        }, 300);
    </script>
</body>
</html>