<?php
/**
 * khalti_event_subscription_callback.php
 * Khalti redirects here after payment verification.
 * We verify the payment status via Khalti lookup API and activate the event hosting subscription.
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../user/mail.php';

// Auth guard
if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// Config
define('KHALTI_SECRET_KEY', '1f7407dfa7ff438cbda63061adfbc7f6');
define('KHALTI_LOOKUP_URL', 'https://a.khalti.com/api/v2/epayment/lookup/');

// Fetch GET parameters
$pidx = isset($_GET['pidx']) ? trim($_GET['pidx']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$transaction_uuid = isset($_GET['purchase_order_id']) ? trim($_GET['purchase_order_id']) : '';

error_log("[Khalti Event Sub Callback] Received callback. status='$status' | uuid='$transaction_uuid'");

if ($status !== 'Completed' || empty($pidx)) {
    error_log("[Khalti Event Sub Callback] Payment not completed.");
    if ($transaction_uuid && isset($_SESSION['pending_khalti_event_subs'][$transaction_uuid])) {
        unset($_SESSION['pending_khalti_event_subs'][$transaction_uuid]);
    }
    header('Location: event-subscription.php?msg=payment_failed');
    exit;
}

// Perform Server-Side Verification using Lookup API
$post_fields = ['pidx' => $pidx];
$ch = curl_init(KHALTI_LOOKUP_URL);
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
    error_log("[Khalti Event Sub Callback] Lookup cURL error: " . $curl_error);
    header('Location: event-subscription.php?esewa_error=' . urlencode('Failed to verify payment with Khalti.'));
    exit;
}

$result = json_decode($response, true);
error_log("[Khalti Event Sub Callback] Lookup Response: " . $response);

if ($http_code !== 200 || strtoupper($result['status'] ?? '') !== 'COMPLETED') {
    error_log("[Khalti Event Sub Callback] Lookup verification failed or not COMPLETED.");
    header('Location: event-subscription.php?msg=payment_failed');
    exit;
}

// Retrieve the pending subscription from session
if (!isset($_SESSION['pending_khalti_event_subs'][$transaction_uuid])) {
    header('Location: event-subscription.php?msg=already_activated');
    exit;
}

$pending_sub = $_SESSION['pending_khalti_event_subs'][$transaction_uuid];
$plan_id = (int)$pending_sub['plan_id'];
$expected_amount = (float)$pending_sub['amount_npr'];

// Verify amount (convert Khalti's paisa to NPR)
$paid_amount_npr = (float)($result['total_amount'] ?? 0) / 100.0;
if (abs($paid_amount_npr - $expected_amount) > 1.0) {
    header('Location: event-subscription.php?msg=amount_mismatch');
    exit;
}

// Fetch plan duration
$pStmt = $conn->query("SELECT duration_days, display_name FROM event_subscription_plans WHERE id = $plan_id");
$plan_data = $pStmt->fetch_assoc();
$duration_days = $plan_data ? (int)$plan_data['duration_days'] : 30;

// Activate the subscription
$safe_txn = $conn->real_escape_string($result['transaction_id'] ?? $pidx);
$sql = "INSERT INTO user_event_subscriptions 
            (user_id, plan_id, status, payment_method, payment_ref, amount_paid, starts_at, expires_at, created_at, approved_at)
        VALUES ($user_id, $plan_id, 'active', 'khalti', '$safe_txn', $expected_amount, NOW(), DATE_ADD(NOW(), INTERVAL $duration_days DAY), NOW(), NOW())";

if (!$conn->query($sql)) {
    error_log("[Khalti Event Sub Callback] DB insert failed: " . $conn->error);
    header('Location: event-subscription.php?esewa_error=' . urlencode('Something went wrong saving your subscription.'));
    exit;
}

// Send Email
$uStmt = $conn->query("SELECT email, full_name FROM users WHERE id = $user_id");
$user = $uStmt->fetch_assoc();
if ($user) {
    // sendSubscriptionSuccessEmail($user['email'], $user['full_name'], $plan_data['display_name'] ?? ('Plan ' . $plan_id), "Event & Experience Subscription");
}

// Clear session
unset($_SESSION['pending_khalti_event_subs'][$transaction_uuid]);

header('Location: event-subscription.php?msg=khalti_success');
exit;
