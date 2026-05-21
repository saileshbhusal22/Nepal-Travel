<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// 2. Validate Post Variables
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: events.php");
    exit;
}

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$guests = isset($_POST['guests']) ? (int)$_POST['guests'] : 1;

if ($event_id <= 0 || empty($name) || empty($phone) || $guests <= 0) {
    die("Invalid request parameters.");
}

// 3. Fetch Event Details Server-side (Prevents Client-side Tampering)
$stmt = $conn->prepare("SELECT title, is_paid, price_npr, start_date FROM events WHERE id = ?");
if (!$stmt) {
    die("Database error: " . $conn->error);
}
$stmt->bind_param("i", $event_id);
$stmt->execute();
$stmt->bind_result($title, $is_paid, $price_npr, $start_date);

if (!$stmt->fetch()) {
    die("Event not found.");
}
$stmt->close();

// Safeguard travel date
$travel_date = (!empty($start_date) && $start_date !== '0000-00-00') ? $start_date : date('Y-m-d');

// 4. Calculate pricing
$is_paid = (int)$is_paid;
$ticket_price = $is_paid === 1 ? (float)$price_npr : 0.0;
$amount = $ticket_price * $guests;

// 5. Duplicate Booking Check
$chk = $conn->prepare("SELECT id FROM bookings WHERE user_id = ? AND event_id = ? AND status IN ('active', 'pending') LIMIT 1");
$chk->bind_param("ii", $user_id, $event_id);
$chk->execute();
$chk->bind_result($existing_booking_id);
$chk->fetch();
$chk->close();

if ($existing_booking_id) {
    // Already registered / pending, redirect directly to ticket page
    header("Location: event_ticket.php?id=" . $existing_booking_id);
    exit;
}

// 6. Process Ticket Booking
if ($amount <= 0.0) {
    // FREE ADMISSION FLOW: Direct Confirmation
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, name, destination, date, guests, status, payment_method, amount, event_id, created_at) VALUES (?, ?, ?, ?, ?, 'active', 'free', 0.00, ?, NOW())");
    $stmt->bind_param("isssii", $user_id, $name, $title, $travel_date, $guests, $event_id);
    
    if ($stmt->execute()) {
        $booking_id = $stmt->insert_id;
        $stmt->close();
        header("Location: event_ticket.php?id=" . $booking_id);
        exit;
    } else {
        die("Error creating reservation: " . $stmt->error);
    }
} else {
    // PAID FLOW: Save to Session and Redirect to eSewa
    $transaction_uuid = "BOOK-EVT-" . $event_id . "-" . time();

    if (!isset($_SESSION['pending_event_esewa_bookings'])) {
        $_SESSION['pending_event_esewa_bookings'] = [];
    }

    $_SESSION['pending_event_esewa_bookings'][$transaction_uuid] = [
        'user_id' => $user_id,
        'name' => $name,
        'destination' => $title,
        'date' => $travel_date,
        'guests' => $guests,
        'amount' => $amount,
        'event_id' => $event_id
    ];
    
    // Build modern eSewa ePay v2 Signature
    $total_amount = number_format($amount, 2, '.', '');
    $product_code = "EPAYTEST";
    $secret_key = "8gBm/:&EnhH.1/q";
    
    $message = "total_amount={$total_amount},transaction_uuid={$transaction_uuid},product_code={$product_code}";
    $signature = base64_encode(hash_hmac('sha256', $message, $secret_key, true));
    
    $esewa_url = "https://rc.esewa.com.np/api/epay/main/v2/form";
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Connecting to eSewa | Nepal Truly Authentic</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            body {
                background: #0f121d;
                color: white;
                font-family: 'Inter', sans-serif;
                height: 100vh;
                margin: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
            }
            .loader-container {
                text-align: center;
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid rgba(255, 255, 255, 0.08);
                padding: 40px;
                border-radius: 24px;
                backdrop-filter: blur(15px);
                box-shadow: 0 20px 50px rgba(0,0,0,0.4);
                max-width: 400px;
                width: 90%;
            }
            .logo {
                font-size: 28px;
                font-weight: 700;
                color: #2b78e4;
                margin-bottom: 25px;
                letter-spacing: 0.5px;
            }
            .esewa-accent {
                color: #60bb46;
            }
            .spinner {
                width: 50px;
                height: 50px;
                border: 4px solid rgba(255, 255, 255, 0.1);
                border-top-color: #60bb46;
                border-radius: 50%;
                margin: 0 auto 25px;
                animation: spin 1s infinite linear;
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            h3 {
                margin: 0 0 10px;
                font-size: 18px;
                font-weight: 600;
            }
            p {
                margin: 0;
                font-size: 13px;
                color: rgba(255, 255, 255, 0.6);
                line-height: 1.5;
            }
        </style>
    </head>
    <body>
        <div class="loader-container">
            <div class="spinner"></div>
            <div class="logo">NEPAL<span class="esewa-accent">eSewa</span></div>
            <h3>Connecting to Secure Payment</h3>
            <p>Please wait a moment while we redirect you to eSewa to complete your ticket purchase...</p>
        </div>

        <!-- Automated eSewa Form submission -->
        <form id="esewaForm" action="<?= $esewa_url ?>" method="POST" style="display: none;">
            <input type="hidden" name="amount" value="<?= $total_amount ?>">
            <input type="hidden" name="tax_amount" value="0">
            <input type="hidden" name="total_amount" value="<?= $total_amount ?>">
            <input type="hidden" name="transaction_uuid" value="<?= $transaction_uuid ?>">
            <input type="hidden" name="product_code" value="<?= $product_code ?>">
            <input type="hidden" name="product_service_charge" value="0">
            <input type="hidden" name="product_delivery_charge" value="0">
            <input type="hidden" name="success_url" value="http://localhost/Nepal-Travel/Public/event_esewa_booking_callback.php">
            <input type="hidden" name="failure_url" value="http://localhost/Nepal-Travel/Public/event-detail.php?id=<?= $event_id ?>&msg=payment_failed">
            <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
            <input type="hidden" name="signature" value="<?= $signature ?>">
        </form>

        <script>
            setTimeout(() => {
                document.getElementById('esewaForm').submit();
            }, 1500); // 1.5 second beautiful delay for user experience
        </script>
    </body>
    </html>
    <?php
}
?>