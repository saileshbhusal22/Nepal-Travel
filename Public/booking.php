<?php
session_start();

// ─────────────────────────────────────────────────────────────
// Include PHPMailer and mail config (must be before any output)
// ─────────────────────────────────────────────────────────────
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail_config.php';

// ─────────────────────────────────────────────────────────────
// Function to send booking confirmation email
// ─────────────────────────────────────────────────────────────
function sendBookingEmail($email, $fullname, $bookingDetails)
{
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

        $ticketLink = "http://localhost/Nepal-Travel/Public/ticket.php?id=" . $bookingDetails['id'];
        $destination = htmlspecialchars($bookingDetails['destination']);
        $date = date('F j, Y', strtotime($bookingDetails['date']));
        $guests = $bookingDetails['guests'];
        $bookingId = str_pad($bookingDetails['id'], 6, '0', STR_PAD_LEFT);

        $mail->Body = "
        <html>
        <head><style>body{font-family:Arial,sans-serif;}</style></head>
        <body>
            <div style='max-width:600px;margin:auto;border:1px solid #ddd;border-radius:12px;padding:20px;background:#faf6ef;'>
                <h2 style='color:#1b3a5a;'>🎫 Your Booking Ticket</h2>
                <p>Hello <strong>$fullname</strong>,</p>
                <p>Your trip to <strong>$destination</strong> is confirmed!</p>
                <table style='width:100%;border-collapse:collapse;margin:20px 0;'>
                    <tr><td style='padding:8px 0;'><strong>Booking ID:</strong></td><td>#$bookingId</td></tr>
                    <tr><td style='padding:8px 0;'><strong>Destination:</strong></td><td>$destination</td></tr>
                    <tr><td style='padding:8px 0;'><strong>Travel Date:</strong></td><td>$date</td></tr>
                    <tr><td style='padding:8px 0;'><strong>Guests:</strong></td><td>$guests</td></tr>
                    <tr><td style='padding:8px 0;'><strong>Status:</strong></td><td>Pending</td></tr>
                </table>
                <p>You can view and print your full ticket here:</p>
                <p><a href='$ticketLink' style='display:inline-block;background:#1b3a5a;color:white;padding:10px 20px;border-radius:30px;text-decoration:none;'>View Your Ticket →</a></p>
                <p>Or copy this link:<br>$ticketLink</p>
                <hr>
                <p style='font-size:12px;color:#777;'>Thank you for choosing " . APP_NAME . ". Need help? Contact us at " . SMTP_USER . "</p>
            </div>
        </body>
        </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Booking email failed: " . $mail->ErrorInfo);
        return false;
    }
}

// ─────────────────────────────────────────────────────────────
// Database connection (needed for POST handling)
// ─────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/db.php';

// ─────────────────────────────────────────────────────────────
// Handle POST submission (BEFORE any output)
// ─────────────────────────────────────────────────────────────
$error = '';
$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']        ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $date        = trim($_POST['date']        ?? '');
    $guests      = (int)($_POST['guests']     ?? 1);
    $user_id     = $_SESSION['user_id'];

    if (!$name || !$destination || !$date || $guests < 1) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO bookings (user_id, name, destination, date, guests, status, created_at)
             VALUES (?, ?, ?, ?, ?, 'active', NOW())"
        );
        $stmt->bind_param("isssi", $user_id, $name, $destination, $date, $guests);
        if ($stmt->execute()) {
            $booking_id = $stmt->insert_id;
            $stmt->close();

            // Fetch user's email
            $emailStmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
            $emailStmt->bind_param("i", $user_id);
            $emailStmt->execute();
            $userData = $emailStmt->get_result()->fetch_assoc();
            $emailStmt->close();

            if ($userData) {
                $bookingDetails = [
                    'id'          => $booking_id,
                    'destination' => $destination,
                    'date'        => $date,
                    'guests'      => $guests
                ];
                sendBookingEmail($userData['email'], $userData['full_name'], $bookingDetails);
            }

            // ✅ Redirect – no output has been sent yet, so safe
            header("Location:ticket.php?id=" . $booking_id);
            exit;
        } else {
            $error = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

// ─────────────────────────────────────────────────────────────
// Now it's safe to include header.php and output HTML
// ─────────────────────────────────────────────────────────────
include '../includes/header.php';

// Pre-fill destination from deals table if ?id= is passed
$destination = '';
$deal_id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($deal_id > 0) {
    $stmt = $conn->prepare("SELECT title FROM deals WHERE id = ?");
    $stmt->bind_param("i", $deal_id);
    $stmt->execute();
    $stmt->bind_result($title);
    $stmt->fetch();
    $stmt->close();
    $destination = $title ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Book Your Trip | Nepal Tours</title>
  <style>
    /* ========== YOUR EXISTING STYLES (keep as they were) ========== */
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
    .card-top {
      text-align: center;
      margin-bottom: 1.8rem;
    }
    .card-top .icon { font-size: 42px; margin-bottom: 0.5rem; }
    .card-top h1 {
      font-size: 1.5rem;
      font-weight: 700;
      color: #fff;
    }
    .card-top p {
      font-size: 13px;
      color: rgba(255,255,255,0.45);
      margin-top: 4px;
    }
    .destination-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(37,99,235,0.35);
      color: #a8c4f8;
      border: 1px solid rgba(100,150,255,0.25);
      border-radius: 30px;
      font-size: 13px;
      padding: 5px 14px;
      margin-top: 10px;
    }
    .form-group {
      margin-bottom: 1.1rem;
    }
    label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: rgba(255,255,255,0.55);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 6px;
    }
    input, select {
      width: 100%;
      padding: 11px 14px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 10px;
      color: #fff;
      font-size: 14px;
      font-family: inherit;
      outline: none;
      transition: border-color 0.15s, background 0.15s;
    }
    input::placeholder { color: rgba(255,255,255,0.25); }
    input:focus, select:focus {
      border-color: #2563eb;
      background: rgba(37,99,235,0.08);
    }
    select option { background: #1a1f35; color: #fff; }
    .guests-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }
    .submit-btn {
      display: block;
      width: 100%;
      padding: 13px;
      background: #2563eb;
      color: #fff;
      font-size: 15px;
      font-weight: 600;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      margin-top: 1.4rem;
      transition: background 0.15s, transform 0.1s;
    }
    .submit-btn:hover  { background: #1d4ed8; }
    .submit-btn:active { transform: scale(0.98); }
    .alert {
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 13px;
      margin-bottom: 1.2rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .alert-error {
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #fca5a5;
    }
    .alert-info {
      background: rgba(59, 130, 246, 0.15);
      border: 1px solid rgba(59, 130, 246, 0.3);
      color: #93c5fd;
    }
    .login-links {
      margin-top: 1rem;
      display: flex;
      gap: 1rem;
      justify-content: center;
    }
    .login-links a {
      color: #93c5fd;
      text-decoration: none;
      font-weight: 500;
      border-bottom: 1px dashed rgba(147,197,253,0.5);
    }
    .login-links a:hover {
      color: white;
      border-bottom-color: white;
    }
    input[type="date"]::-webkit-calendar-picker-indicator {
      filter: invert(1) opacity(0.4);
      cursor: pointer;
    }
  </style>
</head>
<body>
<div class="page-overlay">
  <a href="deals_and_packages.php" class="back-link">← Back to Deals &amp; Packages</a>
  <div class="booking-card">
    <div class="card-top">
      <div class="icon">🏔️</div>
      <h1>Book Your Trip</h1>
      <p>Fill in your details and we'll confirm your booking</p>
      <?php if ($destination): ?>
        <div class="destination-pill">📍 <?= htmlspecialchars($destination) ?></div>
      <?php endif; ?>
    </div>

    <?php if (!$is_logged_in): ?>
      <div class="alert alert-info">
        🔐 You need to login or register to make a booking.
      </div>
      <div class="login-links">
        <a href="/Nepal-Travel/user/login.php">→ Login</a>
        <a href="/Nepal-Travel/user/register.php">→ Register</a>
      </div>
    <?php else: ?>
      <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" placeholder="e.g. Ram Bahadur"
                 value="<?= htmlspecialchars($_POST['name'] ?? $_SESSION['user_name'] ?? '') ?>" required />
        </div>
        <div class="form-group">
          <label for="destination">Destination / Tour</label>
          <input type="text" id="destination" name="destination" placeholder="e.g. Everest Base Camp Trek"
                 value="<?= htmlspecialchars($_POST['destination'] ?? $destination) ?>" required />
        </div>
        <div class="guests-row">
          <div class="form-group">
            <label for="date">Travel Date</label>
            <input type="date" id="date" name="date" min="<?= date('Y-m-d') ?>"
                   value="<?= htmlspecialchars($_POST['date'] ?? '') ?>" required />
          </div>
          <div class="form-group">
            <label for="guests">No. of Guests</label>
            <input type="number" id="guests" name="guests" min="1" max="50" placeholder="1"
                   value="<?= htmlspecialchars($_POST['guests'] ?? '1') ?>" required />
          </div>
        </div>
        <button type="submit" class="submit-btn">Confirm Booking</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>