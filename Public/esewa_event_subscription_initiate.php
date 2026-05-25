<?php
/**
 * esewa_event_subscription_initiate.php
 * Initiates an eSewa payment for buying/renewing an event hosting subscription plan.
 */
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
session_start();
require_once __DIR__ . '/../config/db.php';

// Auth guard
if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php?redirect=event-subscription');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'esewa_pay') {
    header('Location: event-subscription.php');
    exit;
}

// Config
define('ESEWA_SECRET_KEY',   '8gBm/:&EnhH.1/q');   // sandbox secret
define('ESEWA_PRODUCT_CODE', 'EPAYTEST');            // sandbox product code
define('ESEWA_PAYMENT_URL',  'https://rc-epay.esewa.com.np/api/epay/main/v2/form'); // sandbox
define('SITE_URL', 'http://localhost/Nepal-Travel/Public');

// eSewa HMAC-SHA256 signature
function esewa_signature(string $total_amount, string $transaction_uuid, string $product_code, string $secret): string {
    $msg = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$product_code}";
    return base64_encode(hash_hmac('sha256', $msg, $secret, true));
}

// Validate plan_id
$plan_id = isset($_POST['plan_id']) ? (int)$_POST['plan_id'] : 0;
if ($plan_id <= 0) {
    header('Location: event-subscription.php?msg=invalid_plan');
    exit;
}

// Fetch plan
$pStmt = $conn->prepare("SELECT * FROM event_subscription_plans WHERE id = ? AND is_active = 1");
$pStmt->bind_param("i", $plan_id);
$pStmt->execute();
$plan = $pStmt->get_result()->fetch_assoc();
$pStmt->close();

if (!$plan) {
    header('Location: event-subscription.php?msg=invalid_plan');
    exit;
}
$amount_npr = (float)$plan['price'];

// Duplicate checks: block if user already has a pending subscription for this plan.
// If it's a recurring/higher plan (Monthly/Yearly) or experience, block if they already have an active subscription for it with remaining limits.
$event_limit = (int)$plan['event_limit'];
if ($plan['plan_type'] === 'experience' || $plan_id === 1) { 
    $dupStmt = $conn->prepare(
        "SELECT id FROM user_event_subscriptions
         WHERE user_id = ? AND plan_id = ? 
           AND (
             status = 'pending' 
             OR (status = 'active' AND events_posted < ?)
           )
         LIMIT 1"
    );
    $dupStmt->bind_param("iii", $user_id, $plan_id, $event_limit);
} else { 
    $dupStmt = $conn->prepare(
        "SELECT id FROM user_event_subscriptions
         WHERE user_id = ? AND plan_id = ? AND status IN ('active', 'pending')
           AND (expires_at IS NULL OR expires_at > NOW())
           AND events_posted < ?
         LIMIT 1"
    );
    $dupStmt->bind_param("iii", $user_id, $plan_id, $event_limit);
}
$dupStmt->execute();
$dupStmt->bind_result($existing_sub_id);
$dupStmt->fetch();
$dupStmt->close();

if ($existing_sub_id) {
    header('Location: event-subscription.php?msg=already_subscribed');
    exit;
}

$transaction_uuid = 'SUB-EVENT-' . uniqid() . '-' . time();

// Keep pending subscription details in session
if (!isset($_SESSION['pending_esewa_event_subs'])) {
    $_SESSION['pending_esewa_event_subs'] = [];
}
$_SESSION['pending_esewa_event_subs'][$transaction_uuid] = [
    'user_id' => $user_id,
    'plan_id' => $plan_id,
    'amount_npr' => $amount_npr
];

// Amount format for signature: plain integer string for whole numbers, decimal format for fractions.
$amount_str       = (fmod($amount_npr, 1.0) == 0)
                    ? (string)(int)$amount_npr
                    : number_format($amount_npr, 2, '.', '');
$total_amount_str = $amount_str;

$signature = esewa_signature($total_amount_str, $transaction_uuid, ESEWA_PRODUCT_CODE, ESEWA_SECRET_KEY);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirecting to eSewa...</title>
    <style>
        body{font-family:Arial,sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#0b0e14;color:#fff;}
        .box{text-align:center;background:#131722;padding:40px;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.08);}
        .spinner{width:40px;height:40px;border:4px solid #333;border-top-color:#60bb46;border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 16px;}
        @keyframes spin{to{transform:rotate(360deg);}}
        p{color:#9fa6b2;font-size:14px;}
    </style>
</head>
<body>
    <div class="box">
        <div class="spinner"></div>
        <p>Redirecting to eSewa payment gateway...</p>
        <p style="font-size:12px;color:#6b7280;margin-top:8px;">Please do not close this window.</p>
    </div>

    <form id="esewaForm" action="<?= htmlspecialchars(ESEWA_PAYMENT_URL) ?>" method="POST">
        <input type="hidden" name="amount"                   value="<?= htmlspecialchars($amount_str) ?>">
        <input type="hidden" name="tax_amount"               value="0">
        <input type="hidden" name="total_amount"             value="<?= htmlspecialchars($total_amount_str) ?>">
        <input type="hidden" name="transaction_uuid"         value="<?= htmlspecialchars($transaction_uuid) ?>">
        <input type="hidden" name="product_code"             value="<?= htmlspecialchars(ESEWA_PRODUCT_CODE) ?>">
        <input type="hidden" name="product_service_charge"   value="0">
        <input type="hidden" name="product_delivery_charge"  value="0">
        <input type="hidden" name="success_url"              value="<?= SITE_URL ?>/esewa_event_subscription_callback.php?status=success">
        <input type="hidden" name="failure_url"              value="<?= SITE_URL ?>/esewa_event_subscription_callback.php?status=failure">
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
