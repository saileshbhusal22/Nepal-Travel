<?php
/**
 * khalti_booking_initiate.php
 * Called when user submits a booking with Khalti payment.
 * Creates a pending booking then redirects to Khalti checkout.
 */
session_start();
require_once __DIR__ . '/../config/db.php';

// ── Auth guard ──────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php?redirect=booking');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// ── Only accept POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: booking.php');
    exit;
}

// ── Config ──────────────────────────────────────────────────────
define('KHALTI_SECRET_KEY', '1f7407dfa7ff438cbda63061adfbc7f6');
define('KHALTI_API_URL',    'https://dev.khalti.com/api/v2/epayment/initiate/'); // sandbox
// For production: 'https://khalti.com/api/v2/epayment/initiate/'

define('SITE_URL', 'http://localhost/Nepal-Travel/Public');

// ── Get & sanitise form data ────────────────────────────────────
$name        = trim($_POST['name']        ?? '');
$destination = trim($_POST['destination'] ?? '');
$date        = trim($_POST['date']        ?? '');
$guests      = (int)($_POST['guests']     ?? 1);
$deal_id     = (int)($_POST['deal_id']    ?? 0);
$ud_id       = (int)($_POST['ud_id']      ?? 0);

// ── Validate date format (must be YYYY-MM-DD) ───────────────────
if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = '';
}

// ── Basic validation ────────────────────────────────────────────
if (!$name || !$destination || !$date || $guests < 1) {
    header('Location: booking.php?error=' . urlencode('Please fill in all required fields.'));
    exit;
}

if (strtotime($date) < strtotime('today')) {
    header('Location: booking.php?error=' . urlencode('Please choose a travel date in the future.'));
    exit;
}

// ── Determine deal type ─────────────────────────────────────────
$final_deal_id = ($deal_id > 0) ? $deal_id : null;
$final_ud_id   = ($ud_id > 0)   ? $ud_id   : null;

if ($final_deal_id === null && $final_ud_id === null) {
    header('Location: booking.php?error=' . urlencode('Invalid booking. Please select a deal first.'));
    exit;
}

// ── Re-fetch price server-side (never trust POST amount) ────────
$amount = 0.0;
if ($final_deal_id !== null) {
    $pStmt = $conn->prepare("SELECT price FROM deals WHERE id = ?");
    $pStmt->bind_param("i", $final_deal_id);
    $pStmt->execute();
    $pStmt->bind_result($fetched_price);
    $pStmt->fetch();
    $pStmt->close();
    $amount = (float)$fetched_price * $guests;
} elseif ($final_ud_id !== null) {
    $pStmt = $conn->prepare(
        "SELECT price FROM user_deals
         WHERE id = ? AND status = 'approved'
           AND visible_from <= NOW() AND visible_until > NOW()"
    );
    $pStmt->bind_param("i", $final_ud_id);
    $pStmt->execute();
    $pStmt->bind_result($fetched_price);
    $pStmt->fetch();
    $pStmt->close();
    $amount = (float)$fetched_price * $guests;
}

if ($amount <= 0) {
    header('Location: booking.php?error=' . urlencode('Could not determine booking amount. Please try again.'));
    exit;
}

// ════════════════════════════════════════════════════════════════
// ── DUPLICATE BOOKING CHECKS (3 layers) ─────────────────────────
// ════════════════════════════════════════════════════════════════

$duplicate_booking_id = null;

// Layer 1 — check by deal_id (active OR pending)
if ($final_deal_id !== null) {
    $dup = $conn->prepare(
        "SELECT id FROM bookings
         WHERE user_id = ? AND deal_id = ? AND status IN ('active','pending')
         LIMIT 1"
    );
    $dup->bind_param("ii", $user_id, $final_deal_id);
    $dup->execute();
    $dup->bind_result($duplicate_booking_id);
    $dup->fetch();
    $dup->close();
}

// Layer 2 — check by ud_id (active OR pending)
if (!$duplicate_booking_id && $final_ud_id !== null) {
    $dup = $conn->prepare(
        "SELECT id FROM bookings
         WHERE user_id = ? AND ud_id = ? AND status IN ('active','pending')
         LIMIT 1"
    );
    $dup->bind_param("ii", $user_id, $final_ud_id);
    $dup->execute();
    $dup->bind_result($duplicate_booking_id);
    $dup->fetch();
    $dup->close();
}

// Layer 3 — cross-type fallback: same destination via either deal type
if (!$duplicate_booking_id && $destination) {
    $dup = $conn->prepare(
        "SELECT id FROM bookings
         WHERE user_id = ? AND destination = ? AND status IN ('active','pending')
         LIMIT 1"
    );
    $dup->bind_param("is", $user_id, $destination);
    $dup->execute();
    $dup->bind_result($duplicate_booking_id);
    $dup->fetch();
    $dup->close();
}

if ($duplicate_booking_id) {
    // Redirect back to booking page showing the existing ticket
    header('Location: booking.php?' . http_build_query([
        'id'  => $final_deal_id ?? '',
        'ud'  => $final_ud_id   ?? '',
        'msg' => 'duplicate:' . $duplicate_booking_id,
    ]));
    exit;
}

// ════════════════════════════════════════════════════════════════
// ── Fetch user info (prepared) ───────────────────────────────────
// ════════════════════════════════════════════════════════════════
$uStmt = $conn->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
$uStmt->bind_param("i", $user_id);
$uStmt->execute();
$uResult = $uStmt->get_result();
$user    = $uResult ? $uResult->fetch_assoc() : [];
$uStmt->close();

// ════════════════════════════════════════════════════════════════
// ── Create PENDING booking (prepared statement) ──────────────────
// ════════════════════════════════════════════════════════════════
if ($final_deal_id !== null) {
    $stmt = $conn->prepare(
        "INSERT INTO bookings
            (user_id, name, destination, date, guests, deal_id, ud_id, status, payment_method, amount, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NULL, 'pending', 'khalti', ?, NOW())"
    );
    $stmt->bind_param("isssidi",
        $user_id, $name, $destination, $date,
        $guests, $final_deal_id, $amount
    );
} else {
    $stmt = $conn->prepare(
        "INSERT INTO bookings
            (user_id, name, destination, date, guests, deal_id, ud_id, status, payment_method, amount, created_at)
         VALUES (?, ?, ?, ?, ?, NULL, ?, 'pending', 'khalti', ?, NOW())"
    );
    $stmt->bind_param("isssidi",
        $user_id, $name, $destination, $date,
        $guests, $final_ud_id, $amount
    );
}

if (!$stmt->execute()) {
    error_log("Khalti booking insert failed: " . $stmt->error);
    $stmt->close();
    header('Location: booking.php?error=' . urlencode('Something went wrong. Please try again.'));
    exit;
}

$booking_id = $stmt->insert_id;
$stmt->close();

// ════════════════════════════════════════════════════════════════
// ── Build Khalti payload ─────────────────────────────────────────
// ════════════════════════════════════════════════════════════════
// Khalti requires amount in PAISA (1 NPR = 100 paisa)
$payload = json_encode([
    'return_url'          => SITE_URL . '/khalti_booking_callback.php',
    'website_url'         => SITE_URL,
    'amount'              => (int)($amount * 100),
    'purchase_order_id'   => 'BOOK-' . $booking_id . '-' . time(),
    'purchase_order_name' => 'Booking: ' . $destination,
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
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
]);
$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

if ($http_code === 200 && !empty($result['payment_url'])) {
    // ── Save pidx for verification later (prepared) ───────────────
    $pidx  = $result['pidx'];
    $pStmt = $conn->prepare("UPDATE bookings SET payment_ref = ? WHERE id = ?");
    $pStmt->bind_param("si", $pidx, $booking_id);
    $pStmt->execute();
    $pStmt->close();

    // ── Redirect to Khalti checkout ───────────────────────────────
    header('Location: ' . $result['payment_url']);
    exit;

} else {
    // ── API error — delete the pending booking and go back ────────
    $delStmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
    $delStmt->bind_param("i", $booking_id);
    $delStmt->execute();
    $delStmt->close();

    $error = urlencode($result['detail'] ?? 'Khalti payment initiation failed. Please try again.');
    header('Location: booking.php?khalti_error=' . $error);
    exit;
}