<?php
/**
 * esewa_booking_initiate.php
 * Fixed ES104 "Invalid payload signature" error.
 *
 * Root cause: the amount string in the HMAC message must be
 * EXACTLY the same string that goes into the form's total_amount field.
 * eSewa sandbox prefers plain integers for whole amounts ("100" not "100.00").
 */
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php?redirect=booking');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: booking.php');
    exit;
}

define('ESEWA_SECRET_KEY',   '8gBm/:&EnhH.1/q');
define('ESEWA_PRODUCT_CODE', 'EPAYTEST');
define('ESEWA_PAYMENT_URL',  'https://rc-epay.esewa.com.np/api/epay/main/v2/form');
define('SITE_URL',           'http://localhost/Nepal-Travel/Public');

// signed_field_names order: total_amount, transaction_uuid, product_code
function esewa_signature(string $total_amount, string $transaction_uuid, string $product_code, string $secret): string {
    $msg = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$product_code}";
    error_log("[eSewa] HMAC message: " . $msg); // remove in production
    return base64_encode(hash_hmac('sha256', $msg, $secret, true));
}

$name        = trim($_POST['name']        ?? '');
$destination = trim($_POST['destination'] ?? '');
$date        = trim($_POST['date']        ?? '');
$guests      = (int)($_POST['guests']     ?? 1);
$deal_id     = (int)($_POST['deal_id']    ?? 0);
$ud_id       = (int)($_POST['ud_id']      ?? 0);

if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = '';

if (!$name || !$destination || !$date || $guests < 1) {
    header('Location: booking.php?error=' . urlencode('Please fill in all required fields.'));
    exit;
}
if (strtotime($date) < strtotime('today')) {
    header('Location: booking.php?error=' . urlencode('Please choose a travel date in the future.'));
    exit;
}

$final_deal_id = ($deal_id > 0) ? $deal_id : null;
$final_ud_id   = ($ud_id  > 0) ? $ud_id   : null;

if ($final_deal_id === null && $final_ud_id === null) {
    header('Location: booking.php?error=' . urlencode('Invalid booking. Please select a deal first.'));
    exit;
}

$amount = 0.0;
if ($final_deal_id !== null) {
    $pStmt = $conn->prepare("SELECT price FROM deals WHERE id = ?");
    $pStmt->bind_param("i", $final_deal_id);
    $pStmt->execute(); $pStmt->bind_result($fetched_price); $pStmt->fetch(); $pStmt->close();
    $amount = (float)$fetched_price * $guests;
} elseif ($final_ud_id !== null) {
    $pStmt = $conn->prepare(
        "SELECT price FROM user_deals WHERE id=? AND status='approved' AND visible_from<=NOW() AND visible_until>NOW()"
    );
    $pStmt->bind_param("i", $final_ud_id);
    $pStmt->execute(); $pStmt->bind_result($fetched_price); $pStmt->fetch(); $pStmt->close();
    $amount = (float)$fetched_price * $guests;
}

if ($amount <= 0) {
    header('Location: booking.php?error=' . urlencode('Could not determine booking amount.'));
    exit;
}

// Duplicate checks
$duplicate_booking_id = null;
if ($final_deal_id !== null) {
    $dup = $conn->prepare("SELECT id FROM bookings WHERE user_id=? AND deal_id=? AND status IN ('active','pending') LIMIT 1");
    $dup->bind_param("ii", $user_id, $final_deal_id);
    $dup->execute(); $dup->bind_result($duplicate_booking_id); $dup->fetch(); $dup->close();
}
if (!$duplicate_booking_id && $final_ud_id !== null) {
    $dup = $conn->prepare("SELECT id FROM bookings WHERE user_id=? AND ud_id=? AND status IN ('active','pending') LIMIT 1");
    $dup->bind_param("ii", $user_id, $final_ud_id);
    $dup->execute(); $dup->bind_result($duplicate_booking_id); $dup->fetch(); $dup->close();
}
if (!$duplicate_booking_id && $destination) {
    $dup = $conn->prepare("SELECT id FROM bookings WHERE user_id=? AND destination=? AND status IN ('active','pending') LIMIT 1");
    $dup->bind_param("is", $user_id, $destination);
    $dup->execute(); $dup->bind_result($duplicate_booking_id); $dup->fetch(); $dup->close();
}
if ($duplicate_booking_id) {
    header('Location: booking.php?' . http_build_query([
        'id' => $final_deal_id ?? '', 'ud' => $final_ud_id ?? '',
        'msg' => 'duplicate:' . $duplicate_booking_id,
    ]));
    exit;
}

$transaction_uuid = 'ESEWA-' . uniqid() . '-' . time();

// Save to session instead of database
if (!isset($_SESSION['pending_esewa_bookings'])) {
    $_SESSION['pending_esewa_bookings'] = [];
}
$_SESSION['pending_esewa_bookings'][$transaction_uuid] = [
    'user_id' => $user_id,
    'name' => $name,
    'destination' => $destination,
    'date' => $date,
    'guests' => $guests,
    'deal_id' => $final_deal_id,
    'ud_id' => $final_ud_id,
    'amount' => $amount
];

// ── THE FIX: amount string must exactly match what goes in the form ─
// Use plain integer string for whole numbers — no ".00" suffix.
// eSewa does a string comparison of your signature vs their recomputed one.
$amount_str       = (fmod($amount, 1.0) == 0)
                    ? (string)(int)$amount                // e.g. "1500"
                    : number_format($amount, 2, '.', ''); // e.g. "1500.50"
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
        <input type="hidden" name="success_url"              value="<?= SITE_URL ?>/esewa_booking_callback.php?status=success">
        <input type="hidden" name="failure_url"              value="<?= SITE_URL ?>/esewa_booking_callback.php?status=failure">
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