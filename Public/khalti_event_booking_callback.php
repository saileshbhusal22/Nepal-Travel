<?php
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail_config.php';
require_once __DIR__ . '/../config/db.php';

// Auth guard
if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// Config
define('KHALTI_SECRET_KEY', '1f7407dfa7ff438cbda63061adfbc7f6');
define('KHALTI_LOOKUP_URL', 'https://a.khalti.com/api/v2/epayment/lookup/');

// Fetch parameters
$pidx = isset($_GET['pidx']) ? trim($_GET['pidx']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$transaction_uuid = isset($_GET['purchase_order_id']) ? trim($_GET['purchase_order_id']) : '';

error_log("[Khalti Event Callback] Callback received. status='$status' | pidx='$pidx' | uuid='$transaction_uuid'");

if ($status !== 'Completed' || empty($pidx)) {
    error_log("[Khalti Event Callback] Payment not completed or pidx missing.");
    if ($transaction_uuid && isset($_SESSION['pending_khalti_event_bookings'][$transaction_uuid])) {
        unset($_SESSION['pending_khalti_event_bookings'][$transaction_uuid]);
    }
    header('Location: events.php?msg=payment_failed');
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
    error_log("[Khalti Event Callback] Lookup cURL error: " . $curl_error);
    header('Location: events.php?msg=payment_failed');
    exit;
}

$result = json_decode($response, true);
error_log("[Khalti Event Callback] Lookup Response (HTTP $http_code): " . $response);

if ($http_code !== 200 || strtoupper($result['status'] ?? '') !== 'COMPLETED') {
    error_log("[Khalti Event Callback] Lookup verification failed or status not COMPLETED.");
    header('Location: events.php?msg=payment_failed');
    exit;
}

// Find pending booking in session
if (!isset($_SESSION['pending_khalti_event_bookings'][$transaction_uuid])) {
    error_log("[Khalti Event Callback] No pending booking found matching: $transaction_uuid");
    header('Location: /Nepal-Travel/user/dashboard.php?tab=bookings&msg=already_processed');
    exit;
}

$pending_booking = $_SESSION['pending_khalti_event_bookings'][$transaction_uuid];

// Verify amount
$paid_amount_npr = (float)($result['total_amount'] ?? 0) / 100.0;
$expected_amount = (float)$pending_booking['amount'];
if (abs($paid_amount_npr - $expected_amount) > 1.0) {
    error_log("[Khalti Event Callback] Amount mismatch! Expected NPR $expected_amount, got NPR $paid_amount_npr");
    header('Location: events.php?msg=price_mismatch');
    exit;
}

// Activate booking status
$txn_code = $conn->real_escape_string($result['transaction_id'] ?? $pidx);
$stmt = $conn->prepare("INSERT INTO bookings (user_id, name, destination, date, guests, status, payment_method, payment_ref, amount, event_id, created_at) VALUES (?, ?, ?, ?, ?, 'active', 'khalti', ?, ?, ?, NOW())");
$stmt->bind_param("isssisdi", $pending_booking['user_id'], $pending_booking['name'], $pending_booking['destination'], $pending_booking['date'], $pending_booking['guests'], $txn_code, $expected_amount, $pending_booking['event_id']);
$stmt->execute();
$booking_id = $stmt->insert_id;
$stmt->close();

unset($_SESSION['pending_khalti_event_bookings'][$transaction_uuid]);

// Fetch user info for email
$uStmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
$uStmt->bind_param("i", $pending_booking['user_id']);
$uStmt->execute();
$uResult = $uStmt->get_result()->fetch_assoc();
$uStmt->close();

$booking = [
    'email' => $uResult['email'] ?? '',
    'full_name' => $uResult['full_name'] ?? $pending_booking['name'],
    'destination' => $pending_booking['destination'],
    'date' => $pending_booking['date'],
    'guests' => $pending_booking['guests'],
    'amount' => $pending_booking['amount']
];

// Send printable ticket receipt email via PHPMailer
function sendTicketEmail($email, $fullname, $bookingDetails) {
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
        $mail->Subject = 'Your Event Ticket Confirmation – ' . APP_NAME;

        $ticketLink  = "http://localhost/Nepal-Travel/Public/event_ticket.php?id=" . $bookingDetails['id'];
        $eventTitle  = htmlspecialchars($bookingDetails['destination']);
        $date        = date('F j, Y', strtotime($bookingDetails['date']));
        $guests      = (int)$bookingDetails['guests'];
        $amount      = number_format((float)$bookingDetails['amount'], 2);
        $bookingId   = str_pad($bookingDetails['id'], 6, '0', STR_PAD_LEFT);

        $mail->Body = "
        <html>
        <head><style>body{font-family:Arial,sans-serif;margin:0;padding:0;}</style></head>
        <body style='background:#f0f2f5;padding:30px 0;'>
            <div style='max-width:600px;margin:auto;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.10);'>
                <div style='background:#1b3a5a;padding:30px;text-align:center;'>
                    <div style='font-size:36px;margin-bottom:8px;'>🎫</div>
                    <h2 style='color:#fff;margin:0;font-size:22px;font-weight:700;'>Event Ticket Confirmed!</h2>
                    <p style='color:rgba(255,255,255,0.65);margin:6px 0 0;font-size:13px;'>" . APP_NAME . "</p>
                </div>
                <div style='background:#fff;padding:30px;'>
                    <p style='font-size:15px;color:#333;margin-bottom:6px;'>Hello <strong>$fullname</strong>,</p>
                    <p style='font-size:14px;color:#555;margin-bottom:22px;'>Your ticket registration for <strong style='color:#1b3a5a;'>$eventTitle</strong> has been successfully processed. Here is your printable event ticket itinerary:</p>
                    
                    <table style='width:100%;border-collapse:collapse;font-size:14px;border-radius:10px;overflow:hidden;border:1px solid #eee;'>
                        <tr style='background:#f8f9fa;'>
                            <td style='padding:12px 16px;color:#888;font-weight:600;border-bottom:1px solid #eee;width:42%'>Ticket Reference</td>
                            <td style='padding:12px 16px;color:#111;border-bottom:1px solid #eee;font-weight:700;'>#$bookingId</td>
                        </tr>
                        <tr>
                            <td style='padding:12px 16px;color:#888;font-weight:600;border-bottom:1px solid #eee;'>Event Title</td>
                            <td style='padding:12px 16px;color:#111;border-bottom:1px solid #eee;'>$eventTitle</td>
                        </tr>
                        <tr style='background:#f8f9fa;'>
                            <td style='padding:12px 16px;color:#888;font-weight:600;border-bottom:1px solid #eee;'>Event Date</td>
                            <td style='padding:12px 16px;color:#111;border-bottom:1px solid #eee;'>$date</td>
                        </tr>
                        <tr>
                            <td style='padding:12px 16px;color:#888;font-weight:600;border-bottom:1px solid #eee;'>Number of Tickets</td>
                            <td style='padding:12px 16px;color:#111;border-bottom:1px solid #eee;'>$guests ticket(s)</td>
                        </tr>
                        <tr style='background:#f8f9fa;'>
                            <td style='padding:12px 16px;color:#888;font-weight:600;border-bottom:1px solid #eee;'>Total Ticket Price</td>
                            <td style='padding:12px 16px;color:#111;border-bottom:1px solid #eee;font-weight:700;'>NPR $amount</td>
                        </tr>
                        <tr>
                            <td style='padding:12px 16px;color:#888;font-weight:600;border-bottom:1px solid #eee;'>Payment Method</td>
                            <td style='padding:12px 16px;color:#5c2d91;font-weight:800;border-bottom:1px solid #eee;'>Khalti</td>
                        </tr>
                        <tr style='background:#f8f9fa;'>
                            <td style='padding:12px 16px;color:#888;font-weight:600;'>Reservation Status</td>
                            <td style='padding:12px 16px;'>
                                <span style='background:#e2fcdb;color:#206a10;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;'>✓ Paid &amp; Active</span>
                            </td>
                        </tr>
                    </table>
                    
                    <div style='text-align:center;margin-top:28px;'>
                        <a href='$ticketLink' style='display:inline-block;background:#1b3a5a;color:white;padding:13px 32px;border-radius:30px;text-decoration:none;font-weight:700;font-size:14px;letter-spacing:0.03em;'>View & Print Ticket →</a>
                    </div>
                </div>
                <div style='background:#f8f9fa;padding:16px 30px;border-top:1px solid #eee;text-align:center;'>
                    <p style='font-size:12px;color:#aaa;margin:0;'>Thank you for booking with <strong style='color:#555;'>" . APP_NAME . "</strong>. Enjoy your event! 🇳🇵</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Event ticket confirmation email error: " . $mail->ErrorInfo);
        return false;
    }
}

sendTicketEmail($booking['email'], $booking['full_name'], [
    'id'          => $booking_id,
    'destination' => $booking['destination'],
    'date'        => $booking['date'],
    'guests'      => $booking['guests'],
    'amount'      => $booking['amount']
]);

// Redirect
header("Location: event_ticket.php?id=" . $booking_id . "&msg=khalti_success");
exit;
