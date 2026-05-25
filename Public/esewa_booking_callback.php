<?php
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail_config.php';
require_once __DIR__ . '/../config/db.php';

// ── Auth guard ──────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// ── Config ──────────────────────────────────────────────────────
define('ESEWA_SECRET_KEY',   '8gBm/:&EnhH.1/q');
define('ESEWA_PRODUCT_CODE', 'EPAYTEST');
define('ESEWA_STATUS_URL',   'https://rc-epay.esewa.com.np/api/epay/transaction/status/');
// Production:
// define('ESEWA_SECRET_KEY',   'YOUR_LIVE_SECRET_KEY');
// define('ESEWA_PRODUCT_CODE', 'YOUR_LIVE_PRODUCT_CODE');
// define('ESEWA_STATUS_URL',   'https://epay.esewa.com.np/api/epay/transaction/status/');

// ── eSewa response signature verifier ───────────────────────────
function verify_esewa_signature(array $data, string $secret): bool {
    if (empty($data['signature']) || empty($data['signed_field_names'])) {
        error_log("[eSewa Callback] Missing signature or signed_field_names.");
        return false;
    }
    $fields  = explode(',', $data['signed_field_names']);
    $parts   = [];
    foreach ($fields as $field) {
        $parts[] = trim($field) . '=' . ($data[trim($field)] ?? '');
    }
    $message  = implode(',', $parts);
    $expected = base64_encode(hash_hmac('sha256', $message, $secret, true));
    error_log("[eSewa Callback] Signature check — message: $message");
    error_log("[eSewa Callback] Signature check — expected: $expected | received: " . $data['signature']);
    return hash_equals($expected, $data['signature']);
}

// ── FIX 1: Amount formatter — must match what was sent in initiate ──
// Plain integer string for whole numbers, 2 decimal places otherwise.
function esewa_format_amount(float $amount): string {
    return (fmod($amount, 1.0) == 0)
        ? (string)(int)$amount
        : number_format($amount, 2, '.', '');
}

// ── Handle failure redirect ─────────────────────────────────────
$raw_status = $_GET['status'] ?? '';

// FIX 6: eSewa V2 appends "?data=..." instead of "&data=..." if the URL already has a query string.
// So our URL becomes: esewa_booking_callback.php?status=success?data=eyJ...
// PHP parses $_GET['status'] as "success?data=eyJ..." and $_GET['data'] is empty.
$status = $raw_status;
$encoded = $_GET['data'] ?? '';

if (strpos($raw_status, '?data=') !== false) {
    $parts = explode('?data=', $raw_status);
    $status = trim($parts[0]);
    $encoded = trim($parts[1]);
} else {
    $status = trim($status);
}

error_log("[eSewa Callback] Parsed status: '$status' | Data present: " . (!empty($encoded) ? 'Yes' : 'No'));

if ($status === 'failure') {
    if ($encoded) {
        $decoded          = json_decode(base64_decode($encoded), true);
        $transaction_uuid = $decoded['transaction_uuid'] ?? '';
        error_log("[eSewa Callback] Explicit failure for UUID: $transaction_uuid");
        if ($transaction_uuid && isset($_SESSION['pending_esewa_bookings'][$transaction_uuid])) {
            unset($_SESSION['pending_esewa_bookings'][$transaction_uuid]);
        }
    } else {
        error_log("[eSewa Callback] Failure callback with no data payload.");
    }
    header('Location: booking.php?msg=payment_failed');
    exit;
}

// ── Decode eSewa response data (base64 JSON) ────────────────────
if (empty($encoded)) {
    error_log("[eSewa Callback] Error: No data payload in success URL.");
    header('Location: booking.php?msg=payment_failed');
    exit;
}

$decoded = json_decode(base64_decode($encoded), true);
if (!$decoded) {
    error_log("[eSewa Callback] Error: JSON decode failed. Raw: " . base64_decode($encoded));
    header('Location: booking.php?msg=payment_failed');
    exit;
}
error_log("[eSewa Callback] Decoded data: " . json_encode($decoded));

// ── Verify eSewa signature ──────────────────────────────────────
if (!verify_esewa_signature($decoded, ESEWA_SECRET_KEY)) {
    error_log("[eSewa Callback] Signature mismatch. Aborting.");
    header('Location: booking.php?msg=payment_failed');
    exit;
}

$transaction_uuid = $decoded['transaction_uuid'] ?? '';
$esewa_txn_id     = $decoded['transaction_id']   ?? '';

// FIX 2: Strip commas from total_amount before float cast (eSewa sometimes returns "1,500")
$paid_amount = (float)str_replace(',', '', $decoded['total_amount'] ?? '0');

error_log("[eSewa Callback] UUID: $transaction_uuid | eSewa TXN ID: $esewa_txn_id | Paid: $paid_amount");

if (empty($transaction_uuid)) {
    error_log("[eSewa Callback] Empty transaction_uuid. Aborting.");
    header('Location: booking.php?msg=payment_failed');
    exit;
}

// ── FIX 3: Status check — use same amount format as initiate ────
$paid_amount_str = esewa_format_amount($paid_amount);

$status_url = ESEWA_STATUS_URL . '?' . http_build_query([
    'product_code'     => ESEWA_PRODUCT_CODE,
    'transaction_uuid' => $transaction_uuid,
    'total_amount'     => $paid_amount_str,   // FIXED: was number_format(..., 2)
]);

error_log("[eSewa Callback] Status check URL: $status_url");

$ch = curl_init($status_url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPGET        => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
]);
$api_response = curl_exec($ch);
$http_code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error   = curl_error($ch);
curl_close($ch);

error_log("[eSewa Callback] Status API HTTP $http_code | cURL error: '$curl_error' | Response: $api_response");

$api_result = json_decode($api_response, true);

// FIX 4: Case-insensitive status comparison
if ($http_code !== 200 || strtoupper($api_result['status'] ?? '') !== 'COMPLETE') {
    error_log("[eSewa Callback] Status check did not return COMPLETE. status='" . ($api_result['status'] ?? 'null') . "'");
    header('Location: booking.php?msg=payment_failed');
    exit;
}

// ── Retrieve the pending booking from session ───────────────────
if (!isset($_SESSION['pending_esewa_bookings'][$transaction_uuid])) {
    error_log("[eSewa Callback] No pending booking found in session for UUID: $transaction_uuid (may already be processed or session expired).");
    header('Location: ticket.php?msg=already_activated');
    exit;
}

$pending_booking = $_SESSION['pending_esewa_bookings'][$transaction_uuid];

// ── Double-check amount (anti-tampering) ────────────────────────
$expected_amount = (float)$pending_booking['amount'];
if (abs($paid_amount - $expected_amount) > 1) {
    error_log("[eSewa Callback] Amount mismatch! Expected NPR $expected_amount, got NPR $paid_amount");
    header('Location: booking.php?msg=amount_mismatch');
    exit;
}

// ── Insert booking into database upon successful eSewa payment ──
$safe_txn = $conn->real_escape_string($esewa_txn_id ?: $transaction_uuid);

if ($pending_booking['deal_id'] !== null) {
    $stmt = $conn->prepare(
        "INSERT INTO bookings (user_id,name,destination,date,guests,deal_id,ud_id,status,payment_method,payment_ref,amount,created_at)
         VALUES (?,?,?,?,?,?,NULL,'confirmed','esewa',?,?,NOW())"
    );
    $stmt->bind_param("isssiisd", $pending_booking['user_id'], $pending_booking['name'], $pending_booking['destination'], $pending_booking['date'], $pending_booking['guests'], $pending_booking['deal_id'], $safe_txn, $expected_amount);
} else {
    $stmt = $conn->prepare(
        "INSERT INTO bookings (user_id,name,destination,date,guests,deal_id,ud_id,status,payment_method,payment_ref,amount,created_at)
         VALUES (?,?,?,?,?,NULL,?,'confirmed','esewa',?,?,NOW())"
    );
    $stmt->bind_param("isssiisd", $pending_booking['user_id'], $pending_booking['name'], $pending_booking['destination'], $pending_booking['date'], $pending_booking['guests'], $pending_booking['ud_id'], $safe_txn, $expected_amount);
}

if (!$stmt->execute()) {
    error_log("eSewa callback booking insert failed: " . $stmt->error);
    $stmt->close();
    header('Location: booking.php?error=' . urlencode('Something went wrong saving your booking. Please contact support.'));
    exit;
}
$booking_id = $stmt->insert_id;
$stmt->close();

error_log("[eSewa Callback] Booking #$booking_id created and confirmed. eSewa TXN: $safe_txn");

// Remove from session
unset($_SESSION['pending_esewa_bookings'][$transaction_uuid]);

// Fetch user info for email
$user_id_for_email = (int)$pending_booking['user_id'];
$uStmt = $conn->query("SELECT email, full_name FROM users WHERE id = $user_id_for_email");
$uData = $uStmt->fetch_assoc();

$booking = [
    'email'       => $uData['email'] ?? '',
    'full_name'   => $uData['full_name'] ?? $pending_booking['name'],
    'destination' => $pending_booking['destination'],
    'date'        => $pending_booking['date'],
    'guests'      => $pending_booking['guests'],
    'amount'      => $pending_booking['amount'],
    'ud_id'       => $pending_booking['ud_id'],
];

// ── Send confirmation email ──────────────────────────────────────
function sendBookingEmail(string $email, string $fullname, array $bookingDetails): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_USER, APP_NAME);
        $mail->addAddress($email, $fullname);
        $mail->isHTML(true);
        $mail->Subject = 'Your Booking Confirmation – ' . APP_NAME;

        $ticketLink  = "http://localhost/Nepal-Travel/Public/ticket.php?id=" . $bookingDetails['id'];
        $destination = htmlspecialchars($bookingDetails['destination']);
        $date        = date('F j, Y', strtotime($bookingDetails['date']));
        $guests      = (int)$bookingDetails['guests'];
        $amount      = number_format((float)$bookingDetails['amount'], 2);
        $bookingId   = str_pad($bookingDetails['id'], 6, '0', STR_PAD_LEFT);
        $isPartner   = !empty($bookingDetails['is_partner']);

        $partnerNote = $isPartner
            ? "<p style='font-size:12px;color:#92610a;background:#fdf8ec;border:1px solid #f0d98a;padding:10px 14px;border-radius:8px;margin-bottom:16px;'>
                ✦ <strong>Partner Listing:</strong> This package is operated by a verified Nepal Travel partner. They will contact you shortly to confirm your booking details.
               </p>"
            : '';

        $mail->Body = "
        <html>
        <head><style>body{font-family:Arial,sans-serif;margin:0;padding:0;}</style></head>
        <body style='background:#f0f2f5;padding:30px 0;'>
            <div style='max-width:600px;margin:auto;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.10);'>
                <div style='background:#1b3a5a;padding:30px;text-align:center;'>
                    <div style='font-size:36px;margin-bottom:8px;'>🎫</div>
                    <h2 style='color:#fff;margin:0;font-size:22px;font-weight:700;'>Booking Confirmed!</h2>
                    <p style='color:rgba(255,255,255,0.65);margin:6px 0 0;font-size:13px;'>" . APP_NAME . "</p>
                </div>
                <div style='background:#fff;padding:30px;'>
                    <p style='font-size:15px;color:#333;margin-bottom:6px;'>Hello <strong>$fullname</strong>,</p>
                    <p style='font-size:14px;color:#555;margin-bottom:22px;'>Your trip to <strong style='color:#1b3a5a;'>$destination</strong> has been confirmed and payment received. Here are your booking details:</p>
                    $partnerNote
                    <table style='width:100%;border-collapse:collapse;font-size:14px;border-radius:10px;overflow:hidden;border:1px solid #eee;'>
                        <tr style='background:#f8f9fa;'>
                            <td style='padding:12px 16px;color:#888;font-weight:600;border-bottom:1px solid #eee;width:42%'>Booking ID</td>
                            <td style='padding:12px 16px;color:#111;border-bottom:1px solid #eee;font-weight:700;'>#$bookingId</td>
                        </tr>
                        <tr>
                            <td style='padding:12px 16px;color:#888;font-weight:600;border-bottom:1px solid #eee;'>Destination</td>
                            <td style='padding:12px 16px;color:#111;border-bottom:1px solid #eee;'>$destination</td>
                        </tr>
                        <tr style='background:#f8f9fa;'>
                            <td style='padding:12px 16px;color:#888;font-weight:600;border-bottom:1px solid #eee;'>Travel Date</td>
                            <td style='padding:12px 16px;color:#111;border-bottom:1px solid #eee;'>$date</td>
                        </tr>
                        <tr>
                            <td style='padding:12px 16px;color:#888;font-weight:600;border-bottom:1px solid #eee;'>Guests</td>
                            <td style='padding:12px 16px;color:#111;border-bottom:1px solid #eee;'>$guests person(s)</td>
                        </tr>
                        <tr style='background:#f8f9fa;'>
                            <td style='padding:12px 16px;color:#888;font-weight:600;border-bottom:1px solid #eee;'>Amount Paid</td>
                            <td style='padding:12px 16px;color:#111;border-bottom:1px solid #eee;font-weight:700;'>NPR $amount</td>
                        </tr>
                        <tr>
                            <td style='padding:12px 16px;color:#888;font-weight:600;border-bottom:1px solid #eee;'>Payment Method</td>
                            <td style='padding:12px 16px;color:#60bb46;font-weight:700;border-bottom:1px solid #eee;'>eSewa</td>
                        </tr>
                        <tr style='background:#f8f9fa;'>
                            <td style='padding:12px 16px;color:#888;font-weight:600;'>Status</td>
                            <td style='padding:12px 16px;'>
                                <span style='background:#e6f9e0;color:#2d7a1f;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;'>✓ Paid &amp; Confirmed</span>
                            </td>
                        </tr>
                    </table>
                    <div style='text-align:center;margin-top:28px;'>
                        <a href='$ticketLink' style='display:inline-block;background:#1b3a5a;color:white;padding:13px 32px;border-radius:30px;text-decoration:none;font-weight:700;font-size:14px;letter-spacing:0.03em;'>View Your Ticket →</a>
                    </div>
                </div>
                <div style='background:#f8f9fa;padding:16px 30px;border-top:1px solid #eee;text-align:center;'>
                    <p style='font-size:12px;color:#aaa;margin:0;'>Thank you for choosing <strong style='color:#555;'>" . APP_NAME . "</strong>. We wish you a wonderful journey! 🏔️</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Booking email failed: " . $mail->ErrorInfo);
        return false;
    }
}

sendBookingEmail(
    $booking['email'],
    $booking['full_name'],
    [
        'id'          => $booking_id,
        'destination' => $booking['destination'],
        'date'        => $booking['date'],
        'guests'      => $booking['guests'],
        'amount'      => $booking['amount'],
        'is_partner'  => ($booking['ud_id'] !== null),
    ]
);

// ── Redirect with success ────────────────────────────────────────
header('Location: review.php?booking_id=' . $booking_id . '&msg=esewa_success');
exit;