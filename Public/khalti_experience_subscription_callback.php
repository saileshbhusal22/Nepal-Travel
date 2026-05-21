<?php
/**
 * Khalti callback — activate experience subscription.
 */
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

define('KHALTI_SECRET_KEY', '1f7407dfa7ff438cbda63061adfbc7f6');
define('KHALTI_LOOKUP_URL', 'https://dev.khalti.com/api/v2/epayment/lookup/');

$pidx = trim($_GET['pidx'] ?? '');
$status = trim($_GET['status'] ?? '');
$transaction_uuid = trim($_GET['purchase_order_id'] ?? '');

if ($status !== 'Completed' || $pidx === '') {
    if ($transaction_uuid && isset($_SESSION['pending_khalti_experience_subs'][$transaction_uuid])) {
        unset($_SESSION['pending_khalti_experience_subs'][$transaction_uuid]);
    }
    header('Location: experience-subscription.php?msg=payment_failed');
    exit;
}

$ch = curl_init(KHALTI_LOOKUP_URL);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['pidx' => $pidx]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Key ' . KHALTI_SECRET_KEY,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($http_code !== 200 || strtoupper($result['status'] ?? '') !== 'COMPLETED') {
    header('Location: experience-subscription.php?msg=payment_failed');
    exit;
}

if (!isset($_SESSION['pending_khalti_experience_subs'][$transaction_uuid])) {
    header('Location: experience-subscription.php?msg=already_activated');
    exit;
}

$pending = $_SESSION['pending_khalti_experience_subs'][$transaction_uuid];
$plan_id = (int)$pending['plan_id'];
$expected_amount = (float)$pending['amount_npr'];
$paid_amount_npr = (float)($result['total_amount'] ?? 0) / 100.0;

if (abs($paid_amount_npr - $expected_amount) > 1.0) {
    header('Location: experience-subscription.php?msg=amount_mismatch');
    exit;
}

$pStmt = $conn->prepare('SELECT duration_days, display_name FROM experience_subscription_plans WHERE id = ?');
$pStmt->bind_param('i', $plan_id);
$pStmt->execute();
$plan = $pStmt->get_result()->fetch_assoc();
$pStmt->close();

if (!$plan) {
    header('Location: experience-subscription.php?msg=payment_failed');
    exit;
}

$duration_days = (int)$plan['duration_days'];
$payment_ref = $result['transaction_id'] ?? $pidx;

$stmt = $conn->prepare(
    "INSERT INTO user_experience_subscriptions
        (user_id, plan_id, status, payment_method, payment_ref, amount_paid, starts_at, expires_at, approved_at)
     VALUES (?, ?, 'active', 'khalti', ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), NOW())"
);
$stmt->bind_param('iisdi', $user_id, $plan_id, $payment_ref, $expected_amount, $duration_days);

if (!$stmt->execute()) {
    $stmt->close();
    header('Location: experience-subscription.php?khalti_error=' . urlencode('Failed to save subscription.'));
    exit;
}
$stmt->close();

unset($_SESSION['pending_khalti_experience_subs'][$transaction_uuid]);
header('Location: experience-subscription.php?msg=khalti_success');
exit;
