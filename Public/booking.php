<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail_config.php';
require_once __DIR__ . '/../config/db.php';

// ── Email Function ───────────────────────────────────────────
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
        $mail->addReplyTo(SMTP_USER, APP_NAME);
        $mail->XMailer = ' ';
        $mail->isHTML(true);

        $ticketLink  = "http://localhost/Nepal-Travel/Public/ticket.php?id=" . $bookingDetails['id'];
        $destination = htmlspecialchars($bookingDetails['destination']);
        $date        = date('F j, Y', strtotime($bookingDetails['date']));
        $guests      = (int)$bookingDetails['guests'];
        $bookingId   = str_pad($bookingDetails['id'], 6, '0', STR_PAD_LEFT);
        $isPartner   = !empty($bookingDetails['is_partner']);
        $payMethod   = !empty($bookingDetails['payment_method']) ? $bookingDetails['payment_method'] : 'cash';
        $amount      = !empty($bookingDetails['amount']) ? (float)$bookingDetails['amount'] : 0;

        $mail->Subject = 'Booking Confirmed #' . $bookingId . ' – ' . $bookingDetails['destination'];

        $partnerNote = $isPartner
            ? "<p style='font-size:12px;color:#92610a;background:#fdf8ec;border:1px solid #f0d98a;padding:10px 14px;border-radius:8px;margin-bottom:16px;'>
                ✦ <strong>Partner Listing:</strong> This package is operated by a verified Nepal Travel partner. They will contact you shortly to confirm your booking details.
               </p>"
            : '';

        $payBadge = ($payMethod === 'khalti')
            ? "<span style='background:#ede9fe;color:#5b21b6;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;'>💳 Khalti</span>"
            : "<span style='background:#dbeafe;color:#1e40af;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;'>🏔️ Pay at Destination</span>";

        $amountRow = ($amount > 0)
            ? "<tr style='background:#f8f9fa;'>
                <td style='padding:12px 16px;color:#888;font-weight:600;border-bottom:1px solid #eee;'>Amount</td>
                <td style='padding:12px 16px;color:#111;border-bottom:1px solid #eee;font-weight:700;'>NPR " . number_format($amount, 2) . "</td>
               </tr>"
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
                    <p style='font-size:14px;color:#555;margin-bottom:22px;'>Your trip to <strong style='color:#1b3a5a;'>$destination</strong> has been confirmed. Here are your booking details:</p>
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
                        $amountRow
                        <tr style='background:#f8f9fa;'>
                            <td style='padding:12px 16px;color:#888;font-weight:600;border-bottom:1px solid #eee;'>Payment</td>
                            <td style='padding:12px 16px;'>$payBadge</td>
                        </tr>
                        <tr>
                            <td style='padding:12px 16px;color:#888;font-weight:600;'>Status</td>
                            <td style='padding:12px 16px;'>
                                <span style='background:#d1fae5;color:#065f46;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;'>✓ Active</span>
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

// ── Variables ────────────────────────────────────────────────
$error        = '';
$is_logged_in = isset($_SESSION['user_id']);

// ── Pre-fill destination + fetch price ──────────────────────
$IS_POST     = ($_SERVER['REQUEST_METHOD'] === 'POST');
$destination = '';
$deal_id     = 0;
$ud_id       = 0;
$price       = 0;

if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $deal_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT title, price FROM deals WHERE id = ?");
    $stmt->bind_param("i", $deal_id);
    $stmt->execute();
    $stmt->bind_result($title, $price);
    $stmt->fetch();
    $stmt->close();
    $destination = $title ?? '';

} elseif (isset($_GET['ud']) && (int)$_GET['ud'] > 0) {
    $ud_id = (int)$_GET['ud'];
    $stmt  = $conn->prepare(
        "SELECT title, price FROM user_deals
         WHERE id = ? AND status = 'approved'
           AND visible_from <= NOW() AND visible_until > NOW()"
    );
    $stmt->bind_param("i", $ud_id);
    $stmt->execute();
    $stmt->bind_result($title, $price);
    $stmt->fetch();
    $stmt->close();
    $destination = $title ?? '';

    if (empty($destination)) {
        header("Location: ticket.php");
        exit;
    }
}

// On POST, override deal_id / ud_id from hidden fields
if ($IS_POST) {
    if ((int)($_POST['deal_id'] ?? 0) > 0) $deal_id = (int)$_POST['deal_id'];
    if ((int)($_POST['ud_id']   ?? 0) > 0) $ud_id   = (int)$_POST['ud_id'];
}

// ── Handle direct (cash/free) booking POST ───────────────────
if ($is_logged_in && $IS_POST) {

    $name             = trim($_POST['name']        ?? '');
    $post_destination = trim($_POST['destination'] ?? '');
    $post_date        = trim($_POST['date']        ?? '');
    $guests           = (int)($_POST['guests']     ?? 1);
    $user_id          = (int)$_SESSION['user_id'];

    // ── Validate date format strictly (must be YYYY-MM-DD) ───
    if ($post_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $post_date)) {
        $post_date = '';
    }

    // ── Re-fetch price server-side (tamper-proof) ────────────
    $amount = 0.0;
    if ($deal_id > 0) {
        $pStmt = $conn->prepare("SELECT price FROM deals WHERE id = ?");
        $pStmt->bind_param("i", $deal_id);
        $pStmt->execute();
        $pStmt->bind_result($fetched_price);
        $pStmt->fetch();
        $pStmt->close();
        $amount = (float)$fetched_price * $guests;
    } elseif ($ud_id > 0) {
        $pStmt = $conn->prepare(
            "SELECT price FROM user_deals
             WHERE id = ? AND status = 'approved'
               AND visible_from <= NOW() AND visible_until > NOW()"
        );
        $pStmt->bind_param("i", $ud_id);
        $pStmt->execute();
        $pStmt->bind_result($fetched_price);
        $pStmt->fetch();
        $pStmt->close();
        $amount = (float)$fetched_price * $guests;
    }

    // ── Basic validation ─────────────────────────────────────
    if (!$name || !$post_destination || !$post_date || $guests < 1) {
        $error = 'Please fill in all fields.';
    } elseif (strtotime($post_date) < strtotime('today')) {
        $error = 'Please choose a travel date in the future.';
    } else {

        $final_deal_id = ($deal_id > 0) ? $deal_id : null;
        $final_ud_id   = ($ud_id > 0)   ? $ud_id   : null;

        // ── Duplicate booking check — by deal_id ─────────────
        $duplicate_booking_id = null;

        if ($final_deal_id !== null) {
            // Check active OR pending (pending = Khalti payment in progress)
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

        } elseif ($final_ud_id !== null) {
            // ── Duplicate booking check — by ud_id ───────────
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

        // ── Duplicate booking check — by destination (cross-type fallback) ──
        // Catches cases where the same destination exists as both a deal
        // and a user_deal, or the user arrives without proper GET params.
        if (!$duplicate_booking_id && $post_destination) {
            $dup3 = $conn->prepare(
                "SELECT id FROM bookings
                 WHERE user_id = ? AND destination = ? AND status IN ('active','pending')
                 LIMIT 1"
            );
            $dup3->bind_param("is", $user_id, $post_destination);
            $dup3->execute();
            $dup3->bind_result($duplicate_booking_id);
            $dup3->fetch();
            $dup3->close();
        }

        if ($duplicate_booking_id) {
            $error = 'duplicate:' . $duplicate_booking_id;

        } elseif ($final_deal_id !== null) {
            $stmt = $conn->prepare(
                "INSERT INTO bookings
                    (user_id, name, destination, date, guests, amount, deal_id, ud_id, status, payment_method, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NULL, 'active', 'cash', NOW())"
            );
            $stmt->bind_param("isssidi",
                $user_id, $name, $post_destination, $post_date,
                $guests, $amount, $final_deal_id
            );

        } elseif ($final_ud_id !== null) {
            $stmt = $conn->prepare(
                "INSERT INTO bookings
                    (user_id, name, destination, date, guests, amount, deal_id, ud_id, status, payment_method, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NULL, ?, 'active', 'cash', NOW())"
            );
            $stmt->bind_param("isssidi",
                $user_id, $name, $post_destination, $post_date,
                $guests, $amount, $final_ud_id
            );

        } else {
            $error = 'Invalid booking. Please select a deal first.';
        }

        if (empty($error) && isset($stmt)) {
            if ($stmt->execute()) {
                $booking_id = $stmt->insert_id;
                $stmt->close();

                $emailStmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
                $emailStmt->bind_param("i", $user_id);
                $emailStmt->execute();
                $userData = $emailStmt->get_result()->fetch_assoc();
                $emailStmt->close();

                if ($userData) {
                    sendBookingEmail($userData['email'], $userData['full_name'], [
                        'id'             => $booking_id,
                        'destination'    => $post_destination,
                        'date'           => $post_date,
                        'guests'         => $guests,
                        'amount'         => $amount,
                        'is_partner'     => ($final_ud_id !== null),
                        'payment_method' => 'cash',
                    ]);
                }

                header("Location: review.php?booking_id=" . $booking_id);
                exit;

            } else {
                $error = 'Something went wrong. Please try again.';
                error_log("Booking insert failed: " . $conn->error);
                $stmt->close();
            }
        }
    }
}

// ── Check if user already booked this deal (pre-render check) ─
$existing_booking_id = null;
if ($is_logged_in) {
    $user_id = (int)$_SESSION['user_id'];

    if ($deal_id > 0) {
        // Check by deal_id — active OR pending (pending = Khalti in progress)
        $chk = $conn->prepare(
            "SELECT id FROM bookings
             WHERE user_id = ? AND deal_id = ? AND status IN ('active','pending')
             LIMIT 1"
        );
        $chk->bind_param("ii", $user_id, $deal_id);
        $chk->execute();
        $chk->bind_result($existing_booking_id);
        $chk->fetch();
        $chk->close();

    } elseif ($ud_id > 0) {
        // Check by ud_id — active OR pending
        $chk = $conn->prepare(
            "SELECT id FROM bookings
             WHERE user_id = ? AND ud_id = ? AND status IN ('active','pending')
             LIMIT 1"
        );
        $chk->bind_param("ii", $user_id, $ud_id);
        $chk->execute();
        $chk->bind_result($existing_booking_id);
        $chk->fetch();
        $chk->close();
    }

    // Cross-type fallback: same destination booked via either deal type
    if (!$existing_booking_id && $destination) {
        $chk2 = $conn->prepare(
            "SELECT id FROM bookings
             WHERE user_id = ? AND destination = ? AND status IN ('active','pending')
             LIMIT 1"
        );
        $chk2->bind_param("is", $user_id, $destination);
        $chk2->execute();
        $chk2->bind_result($existing_booking_id);
        $chk2->fetch();
        $chk2->close();
    }
}

// ── Parse duplicate error ────────────────────────────────────
$duplicate_redirect_id = null;
if (strpos($error, 'duplicate:') === 0) {
    $duplicate_redirect_id = (int)substr($error, 10);
    $error = '';
}

$guests_default = max(1, (int)($_POST['guests'] ?? 1));

include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Book Your Trip | Nepal Tours</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', sans-serif;
      min-height: 100vh;
      background-image: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1600&q=80');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
    }

    .page-overlay {
      min-height: 100vh;
      background: rgba(10, 12, 22, 0.68);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 3rem 1.5rem;
    }

    .back-link {
      align-self: flex-start;
      max-width: 520px;
      width: 100%;
      margin: 0 auto 1.2rem;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: rgba(255,255,255,0.55);
      font-size: 13px;
      text-decoration: none;
      transition: color 0.15s;
    }
    .back-link:hover { color: #fff; }

    .booking-card {
      background: rgba(24, 28, 44, 0.92);
      border-radius: 20px;
      border: 1px solid rgba(255,255,255,0.09);
      backdrop-filter: blur(12px);
      padding: 2.2rem 2rem;
      width: 100%;
      max-width: 520px;
    }

    .card-top { text-align: center; margin-bottom: 1.8rem; }
    .card-top .icon { font-size: 42px; margin-bottom: 0.5rem; }
    .card-top h1 { font-size: 1.5rem; font-weight: 700; color: #fff; }
    .card-top p  { font-size: 13px; color: rgba(255,255,255,0.45); margin-top: 4px; }

    .destination-pill {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(37,99,235,0.35); color: #a8c4f8;
      border: 1px solid rgba(100,150,255,0.25);
      border-radius: 30px; font-size: 13px;
      padding: 5px 16px; margin-top: 10px;
    }
    .destination-pill.partner {
      background: rgba(201,162,39,0.20);
      color: #e8c44a;
      border-color: rgba(201,162,39,0.30);
    }

    .partner-notice {
      display: flex; align-items: flex-start; gap: 8px;
      background: rgba(201,162,39,0.08);
      border: 1px solid rgba(201,162,39,0.20);
      border-radius: 10px; padding: 10px 14px;
      font-size: 12px; color: rgba(201,162,39,0.85);
      line-height: 1.6; margin-bottom: 1.2rem;
    }

    .form-group { margin-bottom: 1.1rem; }

    label {
      display: block; font-size: 12px; font-weight: 600;
      color: rgba(255,255,255,0.55); text-transform: uppercase;
      letter-spacing: 0.06em; margin-bottom: 6px;
    }

    input, select {
      width: 100%; padding: 11px 14px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 10px; color: #fff; font-size: 14px;
      font-family: inherit; outline: none;
      transition: border-color 0.15s, background 0.15s;
    }
    input::placeholder { color: rgba(255,255,255,0.25); }
    input:focus, select:focus {
      border-color: #2563eb;
      background: rgba(37,99,235,0.08);
    }
    select option { background: #1a1f35; color: #fff; }

    .guests-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }

    .alert {
      padding: 12px 16px; border-radius: 10px; font-size: 13px;
      margin-bottom: 1.2rem; display: flex; align-items: center; gap: 8px;
      line-height: 1.5;
    }
    .alert-error   { background: rgba(239,68,68,0.15);  border: 1px solid rgba(239,68,68,0.3);  color: #fca5a5; }
    .alert-info    { background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3); color: #93c5fd; }
    .alert-warning { background: rgba(234,179,8,0.12);  border: 1px solid rgba(234,179,8,0.30); color: #fde68a; }

    .login-links { margin-top: 1rem; display: flex; gap: 1rem; justify-content: center; }
    .login-links a {
      color: #93c5fd; text-decoration: none; font-weight: 500;
      border-bottom: 1px dashed rgba(147,197,253,0.5);
    }
    .login-links a:hover { color: white; border-bottom-color: white; }

    input[type="date"]::-webkit-calendar-picker-indicator {
      filter: invert(1) opacity(0.4); cursor: pointer;
    }

    .divider { border: none; border-top: 1px solid rgba(255,255,255,0.07); margin: 1.2rem 0; }

    .email-note {
      display: flex; justify-content: space-between; align-items: center;
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 10px; padding: 11px 14px;
      font-size: 12px; color: rgba(255,255,255,0.35);
    }
    .email-note span:last-child { color: #4caf7d; font-weight: 600; }

    /* ── Already booked banner ──────────────────────────────── */
    .already-booked-banner {
      background: rgba(234,179,8,0.10);
      border: 1px solid rgba(234,179,8,0.30);
      border-radius: 14px;
      padding: 1.4rem 1.6rem;
      text-align: center;
      margin-top: 0.5rem;
    }
    .already-booked-banner .ab-icon { font-size: 38px; margin-bottom: 0.6rem; }
    .already-booked-banner h3 {
      font-size: 1rem; font-weight: 700; color: #fde68a; margin-bottom: 6px;
    }
    .already-booked-banner p {
      font-size: 13px; color: rgba(253,230,138,0.65); line-height: 1.6; margin-bottom: 1rem;
    }
    .ab-btn-row { display: flex; flex-direction: column; gap: 8px; }
    .ab-btn-ticket {
      display: block; width: 100%;
      background: #ca8a04; color: #fff;
      font-size: 13px; font-weight: 700;
      padding: 11px; border-radius: 10px;
      text-decoration: none; text-align: center;
      transition: background 0.15s;
    }
    .ab-btn-ticket:hover { background: #a16207; }
    .ab-btn-deals {
      display: block; width: 100%;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      color: rgba(255,255,255,0.55);
      font-size: 13px; font-weight: 500;
      padding: 10px; border-radius: 10px;
      text-decoration: none; text-align: center;
      transition: background 0.15s, color 0.15s;
    }
    .ab-btn-deals:hover { background: rgba(255,255,255,0.10); color: #fff; }

    /* ── Price summary box ──────────────────────────────────── */
    .price-summary {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.10);
      border-radius: 12px;
      padding: 14px 16px;
      margin-bottom: 1.2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .price-summary .label {
      font-size: 12px;
      color: rgba(255,255,255,0.45);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      font-weight: 600;
    }
    .price-summary .total {
      font-size: 18px;
      font-weight: 700;
      color: #fff;
    }
    .price-summary .total span {
      font-size: 12px;
      color: rgba(255,255,255,0.4);
      font-weight: 400;
      margin-left: 4px;
    }

    /* ── Payment buttons ────────────────────────────────────── */
    .payment-section {
      margin-top: 1.4rem;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .payment-label {
      font-size: 11px;
      font-weight: 600;
      color: rgba(255,255,255,0.35);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 2px;
      text-align: center;
    }

    .btn-khalti {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      width: 100%;
      padding: 13px;
      background: #5C2D91;
      color: #fff;
      font-size: 15px;
      font-weight: 700;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.15s, transform 0.1s;
      letter-spacing: 0.03em;
    }
    .btn-khalti:hover  { background: #4a2275; }
    .btn-khalti:active { transform: scale(0.98); }

    .khalti-logo {
      background: #fff;
      border-radius: 4px;
      padding: 2px 6px;
      font-size: 11px;
      font-weight: 900;
      color: #5C2D91;
      letter-spacing: 0.05em;
    }

    .btn-cash {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      padding: 12px;
      background: rgba(255,255,255,0.06);
      color: rgba(255,255,255,0.65);
      font-size: 14px;
      font-weight: 600;
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.15s, color 0.15s, transform 0.1s;
    }
    .btn-cash:hover  { background: rgba(255,255,255,0.10); color: #fff; }
    .btn-cash:active { transform: scale(0.98); }

    .or-divider {
      display: flex;
      align-items: center;
      gap: 10px;
      color: rgba(255,255,255,0.2);
      font-size: 11px;
    }
    .or-divider::before,
    .or-divider::after {
      content: '';
      flex: 1;
      border-top: 1px solid rgba(255,255,255,0.08);
    }
  </style>
</head>
<body>
<div class="page-overlay">

  <!-- Smart back link -->
  <?php if ($ud_id > 0): ?>
    <a href="Ud_deal_details.php?ud=<?= $ud_id ?>" class="back-link">← Back to Deal</a>
  <?php elseif ($deal_id > 0): ?>
    <a href="deal_details.php?id=<?= $deal_id ?>" class="back-link">← Back to Deal</a>
  <?php else: ?>
    <a href="deals.php" class="back-link">← Back to Deals &amp; Packages</a>
  <?php endif; ?>

  <div class="booking-card">

    <div class="card-top">
      <div class="icon"><?= $ud_id > 0 ? '✦' : '🏔️' ?></div>
      <h1>Book Your Trip</h1>
      <p>Fill in your details and we'll confirm your booking</p>
      <?php if ($destination): ?>
        <div class="destination-pill <?= $ud_id > 0 ? 'partner' : '' ?>">
          <?= $ud_id > 0 ? '✦' : '📍' ?> <?= htmlspecialchars($destination) ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($ud_id > 0): ?>
    <div class="partner-notice">
      ✦ <span>This is a <strong>partner listing</strong>. Nepal Travel facilitates the booking but does not operate this package directly. The partner will contact you to confirm details.</span>
    </div>
    <?php endif; ?>

    <?php if (!$is_logged_in): ?>
      <div class="alert alert-info">
        🔐 You need to login or register to make a booking.
      </div>
      <div class="login-links">
        <a href="/Nepal-Travel/user/login.php">→ Login</a>
        <a href="/Nepal-Travel/user/register.php">→ Register</a>
      </div>

    <?php elseif ($existing_booking_id): ?>
      <!-- ── Already booked: show banner, hide form entirely ── -->
      <div class="already-booked-banner">
        <div class="ab-icon">🎫</div>
        <h3>You've Already Booked This Deal!</h3>
        <p>
          You have an active booking for <strong><?= htmlspecialchars($destination) ?></strong>.<br>
          You cannot book the same deal twice while your booking is still active.
        </p>
        <div class="ab-btn-row">
          <a href="ticket.php?id=<?= $existing_booking_id ?>" class="ab-btn-ticket">
            🎟️ &nbsp; View My Existing Ticket
          </a>
          <a href="deals.php" class="ab-btn-deals">
            ← Browse Other Deals
          </a>
        </div>
      </div>

    <?php else: ?>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($duplicate_redirect_id): ?>
        <div class="alert alert-warning" style="flex-direction:column; align-items:flex-start; gap:10px;">
          <span>⚠️ You already have an active booking for this deal.</span>
          <a href="ticket.php?id=<?= $duplicate_redirect_id ?>"
             style="color:#fde68a; font-weight:600; text-decoration:underline; text-underline-offset:3px;">
            🎟️ View my existing ticket →
          </a>
        </div>
      <?php endif; ?>

      <?php
        $msg = $_GET['msg'] ?? '';

        // Duplicate detected by khalti_booking_initiate.php
        if (strpos($msg, 'duplicate:') === 0):
            $khalti_dup_id = (int)substr($msg, 10);
      ?>
        <div class="alert alert-warning" style="flex-direction:column; align-items:flex-start; gap:10px;">
          <span>⚠️ You already have an active or pending booking for this deal.</span>
          <a href="ticket.php?id=<?= $khalti_dup_id ?>"
             style="color:#fde68a; font-weight:600; text-decoration:underline; text-underline-offset:3px;">
            🎟️ View my existing ticket →
          </a>
        </div>
      <?php elseif ($msg === 'payment_cancelled'): ?>
        <div class="alert alert-error">⚠️ Payment was cancelled. You can try again below.</div>
      <?php elseif ($msg === 'payment_failed'): ?>
        <div class="alert alert-error">⚠️ Payment failed or could not be verified. Please try again.</div>
      <?php elseif ($msg === 'amount_mismatch'): ?>
        <div class="alert alert-error">⚠️ Payment amount mismatch detected. Please contact support.</div>
      <?php endif; ?>

      <form method="POST" action="booking.php" id="bookingForm">
        <input type="hidden" name="deal_id" value="<?= (int)$deal_id ?>">
        <input type="hidden" name="ud_id"   value="<?= (int)$ud_id ?>">
        <input type="hidden" name="amount"  id="amountField" value="<?= number_format((float)$price, 2, '.', '') ?>">

        <div class="form-group">
          <label for="name">Full Name</label>
          <input
            type="text" id="name" name="name"
            placeholder="e.g. Ram Bahadur Thapa"
            value="<?= htmlspecialchars($_POST['name'] ?? $_SESSION['user_name'] ?? '') ?>"
            required
          />
        </div>

        <div class="form-group">
          <label for="destination">Destination / Tour</label>
          <input
            type="text" id="destination" name="destination"
            placeholder="e.g. Everest Base Camp Trek"
            value="<?= htmlspecialchars($_POST['destination'] ?? $destination) ?>"
            required
          />
        </div>

        <div class="guests-row">
          <div class="form-group">
            <label for="date">Travel Date</label>
            <input
              type="date" id="date" name="date"
              min="<?= date('Y-m-d') ?>"
              value="<?= htmlspecialchars($_POST['date'] ?? '') ?>"
              pattern="\d{4}-\d{2}-\d{2}"
              required
            />
          </div>
          <div class="form-group">
            <label for="guests">No. of Guests</label>
            <input
              type="number" id="guests" name="guests"
              min="1" max="50" placeholder="1"
              value="<?= htmlspecialchars((string)$guests_default) ?>"
              required
            />
          </div>
        </div>

        <hr class="divider">

        <div class="email-note">
          <span>📧 Confirmation email will be sent to you</span>
          <span>✓ Auto</span>
        </div>

        <?php if ($price > 0): ?>
        <div class="price-summary" style="margin-top: 1rem;">
          <div>
            <div class="label">Total Amount</div>
            <div style="font-size:11px; color:rgba(255,255,255,0.3); margin-top:2px;" id="priceBreakdown">
              NPR <?= number_format((float)$price, 0) ?> × 1 guest
            </div>
          </div>
          <div class="total" id="totalDisplay">
            NPR <?= number_format((float)$price, 0) ?><span>total</span>
          </div>
        </div>
        <?php endif; ?>

        <!-- Payment buttons -->
        <div class="payment-section">
          <div class="payment-label">Choose Payment Method</div>

          <?php if ($price > 0): ?>
          <button type="button" class="btn-khalti" onclick="payWithKhalti()">
            <span class="khalti-logo">K</span>
            Pay with Khalti &nbsp;·&nbsp;
            <span id="khaltiAmount">NPR <?= number_format((float)$price, 0) ?></span>
          </button>

          <div class="or-divider">or</div>
          <?php endif; ?>

          <button type="submit" class="btn-cash">
            🏔️ &nbsp; Confirm Booking (Pay at Destination)
          </button>
        </div>

      </form>

    <?php endif; ?>
  </div>

</div>

<script>
  const pricePerGuest = <?= (float)$price ?>;

  const guestsInput    = document.getElementById('guests');
  const amountField    = document.getElementById('amountField');
  const totalDisplay   = document.getElementById('totalDisplay');
  const priceBreakdown = document.getElementById('priceBreakdown');
  const khaltiAmount   = document.getElementById('khaltiAmount');

  function updateTotal() {
    if (!pricePerGuest) return;
    const guests    = Math.max(1, parseInt(guestsInput.value) || 1);
    const total     = pricePerGuest * guests;
    const formatted = 'NPR ' + total.toLocaleString('en-IN', { maximumFractionDigits: 0 });

    if (amountField)    amountField.value      = total.toFixed(2);
    if (totalDisplay)   totalDisplay.innerHTML = formatted + '<span>total</span>';
    if (priceBreakdown) priceBreakdown.textContent =
      'NPR ' + pricePerGuest.toLocaleString('en-IN', { maximumFractionDigits: 0 }) +
      ' × ' + guests + ' guest' + (guests > 1 ? 's' : '');
    if (khaltiAmount)   khaltiAmount.textContent = formatted;
  }

  if (guestsInput) {
    guestsInput.addEventListener('input', updateTotal);
    updateTotal();
  }

  function payWithKhalti() {
    const form   = document.getElementById('bookingForm');
    const name   = document.getElementById('name').value.trim();
    const dest   = document.getElementById('destination').value.trim();
    const date   = document.getElementById('date').value;
    const guests = parseInt(guestsInput.value) || 0;

    if (!name || !dest || !date || guests < 1) {
      alert('Please fill in all fields before paying.');
      return;
    }
    if (new Date(date) < new Date(new Date().toDateString())) {
      alert('Please choose a travel date in the future.');
      return;
    }

    form.action = 'khalti_booking_initiate.php';
    form.submit();
  }
</script>

</body>
</html>