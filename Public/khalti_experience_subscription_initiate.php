<?php
/**
 * Khalti payment for experience section subscription.
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/experience_subscription.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php?redirect=experience-subscription');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'khalti_pay') {
    header('Location: experience-subscription.php');
    exit;
}

define('KHALTI_SECRET_KEY', '1f7407dfa7ff438cbda63061adfbc7f6');
define('KHALTI_INITIATE_URL', 'https://dev.khalti.com/api/v2/epayment/initiate/');
define('SITE_URL', 'http://localhost/Nepal-Travel/Public');

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
$transaction_uuid = 'SUB-EXP-KHALTI-' . uniqid() . '-' . time();

if (!isset($_SESSION['pending_khalti_experience_subs'])) {
    $_SESSION['pending_khalti_experience_subs'] = [];
}
$_SESSION['pending_khalti_experience_subs'][$transaction_uuid] = [
    'user_id' => $user_id,
    'plan_id' => $plan_id,
    'amount_npr' => $amount_npr,
];

$uStmt = $conn->prepare('SELECT full_name, email, phone FROM users WHERE id = ?');
$uStmt->bind_param('i', $user_id);
$uStmt->execute();
$user = $uStmt->get_result()->fetch_assoc();
$uStmt->close();

$customer_name = trim($user['full_name'] ?? '') ?: 'Nepal Travel Customer';
$customer_email = trim($user['email'] ?? '') ?: 'customer@nepaltravel.com';
$customer_phone = preg_replace('/[^0-9]/', '', $user['phone'] ?? '');
if (strlen($customer_phone) < 10) {
    $customer_phone = '9800000000';
}

$post_fields = [
    'return_url' => SITE_URL . '/khalti_experience_subscription_callback.php',
    'website_url' => 'http://localhost/Nepal-Travel',
    'amount' => (int)round($amount_npr * 100),
    'purchase_order_id' => $transaction_uuid,
    'purchase_order_name' => 'Experience Plan - ' . $plan['display_name'],
    'customer_info' => [
        'name' => $customer_name,
        'email' => $customer_email,
        'phone' => $customer_phone,
    ],
];

$ch = curl_init(KHALTI_INITIATE_URL);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($post_fields),
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

if ($http_code === 200 && !empty($result['payment_url'])) {
    header('Location: ' . $result['payment_url']);
    exit;
}

$error_msg = $result['detail'] ?? ($result['message'] ?? 'Unable to initiate Khalti payment.');
header('Location: experience-subscription.php?khalti_error=' . urlencode($error_msg));
exit;
