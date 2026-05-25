<?php
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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'khalti_pay') {
    header('Location: event-subscription.php');
    exit;
}

// Config
define('KHALTI_SECRET_KEY', '1f7407dfa7ff438cbda63061adfbc7f6');
define('KHALTI_INITIATE_URL', 'https://a.khalti.com/api/v2/epayment/initiate/');
define('SITE_URL', 'http://localhost/Nepal-Travel/Public');

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

// Fetch user info for Khalti customer_info
$uStmt = $conn->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
$uStmt->bind_param("i", $user_id);
$uStmt->execute();
$uResult = $uStmt->get_result()->fetch_assoc();
$uStmt->close();

$customer_name = !empty($uResult['full_name']) ? trim($uResult['full_name']) : 'Nepal Travel Customer';
$customer_email = !empty($uResult['email']) ? trim($uResult['email']) : 'customer@nepaltravel.com';
$customer_phone = !empty($uResult['phone']) ? preg_replace('/[^0-9]/', '', $uResult['phone']) : '9800000000';
if (strlen($customer_phone) < 10) {
    $customer_phone = '9800000000';
}

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

// Create unique purchase_order_id
$transaction_uuid = 'SUB-EVENT-KHALTI-' . uniqid() . '-' . time();

// Keep pending subscription details in session
if (!isset($_SESSION['pending_khalti_event_subs'])) {
    $_SESSION['pending_khalti_event_subs'] = [];
}
$_SESSION['pending_khalti_event_subs'][$transaction_uuid] = [
    'user_id' => $user_id,
    'plan_id' => $plan_id,
    'amount_npr' => $amount_npr
];

// Amount must be in paisa (1 NPR = 100 paisa)
$amount_paisa = (int)round($amount_npr * 100);

$post_fields = [
    'return_url' => SITE_URL . '/khalti_event_subscription_callback.php',
    'website_url' => 'http://localhost/Nepal-Travel',
    'amount' => $amount_paisa,
    'purchase_order_id' => $transaction_uuid,
    'purchase_order_name' => 'Event Hosting Plan - ' . $plan['display_name'],
    'customer_info' => [
        'name' => $customer_name,
        'email' => $customer_email,
        'phone' => $customer_phone
    ]
];

$ch = curl_init(KHALTI_INITIATE_URL);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($post_fields),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Key ' . KHALTI_SECRET_KEY,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    error_log("[Khalti Event Sub Initiate] cURL error: " . $curl_error);
    header('Location: event-subscription.php?esewa_error=' . urlencode('Failed to connect to Khalti payment gateway.'));
    exit;
}

$result = json_decode($response, true);
error_log("[Khalti Event Sub Initiate] Response: " . $response);

if ($http_code === 200 && isset($result['payment_url'])) {
    header('Location: ' . $result['payment_url']);
    exit;
} else {
    $error_msg = $result['detail'] ?? ($result['message'] ?? 'Unable to initiate Khalti payment.');
    header('Location: event-subscription.php?esewa_error=' . urlencode($error_msg));
    exit;
}
