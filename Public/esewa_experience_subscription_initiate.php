<?php
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/experience_subscription.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php?redirect=experience-subscription');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'esewa_pay') {
    header('Location: experience-subscription.php');
    exit;
}

define('ESEWA_SECRET_KEY', '8gBm/:&EnhH.1/q');
define('ESEWA_PRODUCT_CODE', 'EPAYTEST');
define('ESEWA_PAYMENT_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form');
define('SITE_URL', 'http://localhost/Nepal-Travel/Public');

function esewa_signature(string $total_amount, string $transaction_uuid, string $product_code, string $secret): string {
    $msg = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$product_code}";
    return base64_encode(hash_hmac('sha256', $msg, $secret, true));
}

$plan_id = isset($_POST['plan_id']) ? (int)$_POST['plan_id'] : 0;
if ($plan_id <= 0) {
    header('Location: experience-subscription.php?msg=invalid_plan');
    exit;
}

$pStmt = $conn->prepare('SELECT * FROM experience_subscription_plans WHERE id = ? AND is_active = 1');
$pStmt->bind_param('i', $plan_id);
$pStmt->execute();
$plan = $pStmt->get_result()->fetch_assoc();
$pStmt->close();

if (!$plan) {
    header('Location: experience-subscription.php?msg=invalid_plan');
    exit;
}

if (experience_has_active_subscription($conn, $user_id)) {
    header('Location: experience-subscription.php?msg=already_has_active');
    exit;
}

$dupStmt = $conn->prepare(
    "SELECT id FROM user_experience_subscriptions
     WHERE user_id = ? AND plan_id = ? AND status = 'pending' LIMIT 1"
);
$dupStmt->bind_param('ii', $user_id, $plan_id);
$dupStmt->execute();
$dupStmt->bind_result($pending_id);
$dupStmt->fetch();
$dupStmt->close();

if ($pending_id) {
    header('Location: experience-subscription.php?msg=already_subscribed');
    exit;
}

$amount_npr = (float)$plan['price'];
$transaction_uuid = 'SUB-EXP-' . uniqid() . '-' . time();

if (!isset($_SESSION['pending_esewa_experience_subs'])) {
    $_SESSION['pending_esewa_experience_subs'] = [];
}
$_SESSION['pending_esewa_experience_subs'][$transaction_uuid] = [
    'user_id' => $user_id,
    'plan_id' => $plan_id,
    'amount_npr' => $amount_npr,
];

$amount_str = (fmod($amount_npr, 1.0) == 0) ? (string)(int)$amount_npr : number_format($amount_npr, 2, '.', '');
$signature = esewa_signature($amount_str, $transaction_uuid, ESEWA_PRODUCT_CODE, ESEWA_SECRET_KEY);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirecting to eSewa...</title>
    <style>
        body{font-family:Arial,sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#0b0e14;color:#fff;}
        .box{text-align:center;background:#131722;padding:40px;border-radius:16px;}
        .spinner{width:40px;height:40px;border:4px solid #333;border-top-color:#60bb46;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 16px;}
        @keyframes spin{to{transform:rotate(360deg)}}
    </style>
</head>
<body>
    <div class="box">
        <div class="spinner"></div>
        <p>Redirecting to eSewa...</p>
    </div>
    <form id="esewaForm" action="<?= htmlspecialchars(ESEWA_PAYMENT_URL) ?>" method="POST">
        <input type="hidden" name="amount" value="<?= htmlspecialchars($amount_str) ?>">
        <input type="hidden" name="tax_amount" value="0">
        <input type="hidden" name="total_amount" value="<?= htmlspecialchars($amount_str) ?>">
        <input type="hidden" name="transaction_uuid" value="<?= htmlspecialchars($transaction_uuid) ?>">
        <input type="hidden" name="product_code" value="<?= htmlspecialchars(ESEWA_PRODUCT_CODE) ?>">
        <input type="hidden" name="product_service_charge" value="0">
        <input type="hidden" name="product_delivery_charge" value="0">
        <input type="hidden" name="success_url" value="<?= SITE_URL ?>/esewa_experience_subscription_callback.php?status=success">
        <input type="hidden" name="failure_url" value="<?= SITE_URL ?>/esewa_experience_subscription_callback.php?status=failure">
        <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
        <input type="hidden" name="signature" value="<?= htmlspecialchars($signature) ?>">
    </form>
    <script>setTimeout(function(){document.getElementById('esewaForm').submit();},300);</script>
</body>
</html>
