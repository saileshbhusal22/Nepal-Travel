<?php
/**
 * khalti_booking_callback.php
 * Khalti redirects here after payment with ?pidx=... &status=Completed etc.
 * We verify the payment via Khalti lookup API, then activate the booking and send confirmation email.
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

// ── Config (must match khalti_booking_initiate.php) ────────────
define('KHALTI_SECRET_KEY',  '1f7407dfa7ff438cbda63061adfbc7f6'); // ← same key
define('KHALTI_LOOKUP_URL',  'https://dev.khalti.com/api/v2/epayment/lookup/'); // sandbox
// For production: 'https://khalti.com/api/v2/epayment/lookup/'

$pidx             = trim($_GET['pidx'] ?? '');
$status           = trim($_GET['status'] ?? '');
$transaction_uuid = trim($_GET['purchase_order_id'] ?? '');

// ── Basic guard ─────────────────────────────────────────────────
if (empty($pidx)) {
    header('Location: booking.php?msg=payment_failed'); 
    exit;
}

// ── If user cancelled ───────────────────────────────────────────
if ($status === 'User canceled' || $status !== 'Completed') {
    if ($transaction_uuid && isset($_SESSION['pending_khalti_bookings'][$transaction_uuid])) {
        unset($_SESSION['pending_khalti_bookings'][$transaction_uuid]);
    }
    header('Location: booking.php?msg=payment_cancelled'); 
    exit;
}

// ── Verify with Khalti Lookup API ───────────────────────────────
$payload = json_encode(['pidx' => $pidx]);

$ch = curl_init(KHALTI_LOOKUP_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Key ' . KHALTI_SECRET_KEY,
        'Content-Type: application/json',
    ],
]);
$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$result = json_decode($response, true);

// ── Check Khalti says "Completed" ───────────────────────────────
if ($http_code !== 200 || strtoupper($result['status'] ?? '') !== 'COMPLETED') {
    header('Location: booking.php?msg=payment_failed'); 
    exit;
}

// ── Find the pending booking from session ────────────────────────────
if (!$transaction_uuid || !isset($_SESSION['pending_khalti_bookings'][$transaction_uuid])) {
    // Already processed or invalid
    header('Location: ticket.php?msg=already_activated'); 
    exit;
}

$pending_booking = $_SESSION['pending_khalti_bookings'][$transaction_uuid];
$expected        = (float)$pending_booking['amount'];

// ── Double-check amount (anti-tampering) ────────────────────────
// Khalti returns amount in paisa
$paid_npr = (float)($result['total_amount'] ?? 0) / 100;

if (abs($paid_npr - $expected) > 1) { // allow NPR 1 tolerance for rounding
    error_log("Khalti amount mismatch: expected NPR $expected, got NPR $paid_npr");
    header('Location: booking.php?msg=amount_mismatch'); 
    exit;
}

// ── Insert the booking — status = confirmed, payment_method = khalti ──
$txn_id = $conn->real_escape_string($result['transaction_id'] ?? $pidx);
$final_deal_id = $pending_booking['deal_id'];
$final_ud_id   = $pending_booking['ud_id'];

if ($final_deal_id !== null) {
    $stmt = $conn->prepare(
        "INSERT INTO bookings
            (user_id, name, destination, date, guests, deal_id, ud_id, status, payment_method, payment_ref, amount, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NULL, 'confirmed', 'khalti', ?, ?, NOW())"
    );
    $stmt->bind_param("isssidsd",
        $pending_booking['user_id'], $pending_booking['name'], $pending_booking['destination'], 
        $pending_booking['date'], $pending_booking['guests'], $final_deal_id, 
        $txn_id, $expected
    );
} else {
    $stmt = $conn->prepare(
        "INSERT INTO bookings
            (user_id, name, destination, date, guests, deal_id, ud_id, status, payment_method, payment_ref, amount, created_at)
         VALUES (?, ?, ?, ?, ?, NULL, ?, 'confirmed', 'khalti', ?, ?, NOW())"
    );
    $stmt->bind_param("isssidsd",
        $pending_booking['user_id'], $pending_booking['name'], $pending_booking['destination'], 
        $pending_booking['date'], $pending_booking['guests'], $final_ud_id, 
        $txn_id, $expected
    );
}

if (!$stmt->execute()) {
    error_log("Khalti callback DB insert failed: " . $stmt->error);
    header('Location: booking.php?msg=payment_failed');
    exit;
}

$booking_id = $stmt->insert_id;
$stmt->close();

// Clear the session
unset($_SESSION['pending_khalti_bookings'][$transaction_uuid]);

// Add $booking array to pass to sendBookingEmail
$booking = [
    'id'          => $booking_id,
    'destination' => $pending_booking['destination'],
    'date'        => $pending_booking['date'],
    'guests'      => $pending_booking['guests'],
    'amount'      => $expected,
    'ud_id'       => $final_ud_id
];

$uStmt = $conn->query("SELECT email, full_name FROM users WHERE id = $user_id");
if ($uRow = $uStmt->fetch_assoc()) {
    $booking['email']     = $uRow['email'];
    $booking['full_name'] = $uRow['full_name'];
}

// ── Send confirmation email ──────────────────────────────────────
function sendBookingEmail($email, $fullname, $bookingDetails) {
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
                            <td style='padding:12px 16px;color:#5C2D91;font-weight:700;border-bottom:1px solid #eee;'>Khalti</td>
                        </tr>
                        <tr style='background:#f8f9fa;'>
                            <td style='padding:12px 16px;color:#888;font-weight:600;'>Status</td>
                            <td style='padding:12px 16px;'>
                                <span style='background:#ede0ff;color:#4a1a8a;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;'>✓ Paid &amp; Confirmed</span>
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

// Send the email
sendBookingEmail(
    $booking['email'], 
    $booking['full_name'], 
    [
        'id'          => $booking_id,
        'destination' => $booking['destination'],
        'date'        => $booking['date'],
        'guests'      => $booking['guests'],
        'amount'      => $booking['amount'],
        'is_partner'  => ($booking['ud_id'] !== null)
    ]
);

// ── Redirect with success ────────────────────────────────────────
header('Location: review.php?booking_id=' . $booking_id . '&msg=khalti_success'); 
exit;