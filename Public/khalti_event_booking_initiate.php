<?php
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

// Auth guard
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php");
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// Validate Post Variables
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: events.php");
    exit;
}

// Config
define('KHALTI_SECRET_KEY', '1f7407dfa7ff438cbda63061adfbc7f6');
define('KHALTI_INITIATE_URL', 'https://a.khalti.com/api/v2/epayment/initiate/');
define('SITE_URL', 'http://localhost/Nepal-Travel/Public');

$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$guests = isset($_POST['guests']) ? (int)$_POST['guests'] : 1;

if ($event_id <= 0 || empty($name) || empty($phone) || $guests <= 0) {
    die("Invalid request parameters.");
}

// Fetch event details server-side
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

$travel_date = (!empty($start_date) && $start_date !== '0000-00-00') ? $start_date : date('Y-m-d');

// Calculate pricing
$is_paid = (int)$is_paid;
$ticket_price = $is_paid === 1 ? (float)$price_npr : 0.0;
$amount = $ticket_price * $guests;

// Duplicate booking check
$chk = $conn->prepare("SELECT id FROM bookings WHERE user_id = ? AND event_id = ? AND status IN ('active', 'pending') LIMIT 1");
$chk->bind_param("ii", $user_id, $event_id);
$chk->execute();
$chk->bind_result($existing_booking_id);
$chk->fetch();
$chk->close();

if ($existing_booking_id) {
    header("Location: event_ticket.php?id=" . $existing_booking_id);
    exit;
}

if ($amount <= 0.0) {
    // Free flow
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
    // Paid flow: save to session first
    $transaction_uuid = "BOOK-KHALTI-EVT-" . $event_id . "-" . time();

    if (!isset($_SESSION['pending_khalti_event_bookings'])) {
        $_SESSION['pending_khalti_event_bookings'] = [];
    }

    $_SESSION['pending_khalti_event_bookings'][$transaction_uuid] = [
        'user_id' => $user_id,
        'name' => $name,
        'destination' => $title,
        'date' => $travel_date,
        'guests' => $guests,
        'amount' => $amount,
        'event_id' => $event_id
    ];

    // Fetch user info for customer_info
    $uStmt = $conn->prepare("SELECT email, phone FROM users WHERE id = ?");
    $uStmt->bind_param("i", $user_id);
    $uStmt->execute();
    $uResult = $uStmt->get_result()->fetch_assoc();
    $uStmt->close();

    $customer_name = $name;
    $customer_email = !empty($uResult['email']) ? trim($uResult['email']) : 'customer@nepaltravel.com';
    $customer_phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($customer_phone) < 10) {
        $customer_phone = '9800000000';
    }

    // Call Khalti initiate API
    $amount_paisa = (int)round($amount * 100);

    $post_fields = [
        'return_url' => SITE_URL . '/khalti_event_booking_callback.php',
        'website_url' => 'http://localhost/Nepal-Travel',
        'amount' => $amount_paisa,
        'purchase_order_id' => $transaction_uuid,
        'purchase_order_name' => 'Event Ticket - ' . $title,
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
        error_log("[Khalti Event Initiate] cURL error: " . $curl_error);
        header('Location: events.php?msg=payment_failed');
        exit;
    }

    $result = json_decode($response, true);
    error_log("[Khalti Event Initiate] HTTP $http_code Response: " . $response);

    if ($http_code === 200 && isset($result['payment_url'])) {
        header('Location: ' . $result['payment_url']);
        exit;
    } else {
        $error_msg = $result['detail'] ?? ($result['message'] ?? 'Unable to initiate Khalti payment.');
        error_log("[Khalti Event Initiate] Error: " . $error_msg);
        header('Location: events.php?msg=payment_failed');
        exit;
    }
}
