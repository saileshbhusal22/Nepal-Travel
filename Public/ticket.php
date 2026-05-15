<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$booking_id) {
    die("Invalid booking ID.");
}

$stmt = $conn->prepare("
    SELECT id, name, destination, date, guests, status, created_at
    FROM bookings
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("Booking not found or you don't have permission.");
}

$travel_date = date('F j, Y', strtotime($booking['date']));
$booked_on   = date('F j, Y \a\t g:i A', strtotime($booking['created_at']));
$status_class = $booking['status'] === 'pending' ? 'pending' : 'confirmed';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Ticket | Nepal Tours</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #1e3c2c 0%, #2a4a3a 100%);
            font-family: 'Segoe UI', 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        .ticket {
            max-width: 800px;
            width: 100%;
            background: #fff;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: 0 25px 45px rgba(0,0,0,0.25);
        }
        .ticket-header {
            background: #1e3a2f;
            padding: 1.8rem 2rem;
            color: white;
            text-align: center;
        }
        .ticket-header h1 {
            font-size: 1.8rem;
            letter-spacing: 2px;
        }
        .ticket-header .logo { font-size: 48px; margin-bottom: 8px; }
        .ticket-body { padding: 2rem 2rem 1.5rem; }
        .booking-id {
            background: #f5f3ef;
            padding: 0.8rem 1.2rem;
            border-radius: 40px;
            text-align: center;
            font-family: monospace;
            font-size: 1.1rem;
            font-weight: bold;
            color: #1e3a2f;
            margin-bottom: 2rem;
            border: 1px dashed #c0b9a8;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .info-item {
            border-bottom: 1px solid #eae6df;
            padding-bottom: 0.7rem;
        }
        .info-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #8b7a66;
            font-weight: 600;
        }
        .info-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2c4b3a;
            margin-top: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 18px;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-badge.pending { background: #fef3c7; color: #b45309; }
        .status-badge.confirmed { background: #d1fae5; color: #065f46; }
        .ticket-footer {
            background: #f9f7f3;
            padding: 1.2rem 2rem;
            text-align: center;
            font-size: 0.75rem;
            color: #5e6e5e;
            border-top: 1px solid #e2dbcf;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .print-btn {
            background: #1e3a2f;
            color: white;
            border: none;
            padding: 8px 24px;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
        }
        .print-btn:hover { background: #0f2a20; }
        .back-dash {
            color: #2c5a44;
            text-decoration: none;
            font-weight: 500;
        }
        @media print {
            body { background: white; padding: 0; }
            .print-btn, .back-dash { display: none; }
            .ticket { box-shadow: none; border: 1px solid #ccc; }
            .ticket-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @media (max-width: 550px) {
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="ticket">
    <div class="ticket-header">
        <div class="logo">🏔️✨</div>
        <h1>NEPAL TRULY AUTHENTIC</h1>
        <p>Booking Confirmation</p>
    </div>
    <div class="ticket-body">
        <div class="booking-id">
            📌 Booking ID: #<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?>
        </div>
        <div class="info-grid">
            <div class="info-item"><div class="info-label">Traveler Name</div><div class="info-value"><?php echo htmlspecialchars($booking['name']); ?></div></div>
            <div class="info-item"><div class="info-label">Destination</div><div class="info-value">🗺️ <?php echo htmlspecialchars($booking['destination']); ?></div></div>
            <div class="info-item"><div class="info-label">Travel Date</div><div class="info-value">📅 <?php echo $travel_date; ?></div></div>
            <div class="info-item"><div class="info-label">Number of Guests</div><div class="info-value">👥 <?php echo (int)$booking['guests']; ?> guest(s)</div></div>
            <div class="info-item"><div class="info-label">Booking Status</div><div class="info-value"><span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($booking['status']); ?></span></div></div>
            <div class="info-item"><div class="info-label">Booked On</div><div class="info-value">📆 <?php echo $booked_on; ?></div></div>
        </div>
        <div style="margin-top: 1rem; text-align: center; font-size: 0.8rem; color: #7e8c7a;">
            <p>✈️ A confirmation email has been sent to your registered email address.</p>
            <p>For changes, contact support@nepaltours.com</p>
        </div>
    </div>
    <div class="ticket-footer">
        <a href="/Nepal-Travel/user/dashboard.php?tab=bookings" class="back-dash">← My Bookings</a>
        <button class="print-btn" onclick="window.print();">🖨️ Print Ticket</button>
    </div>
</div>
</body>
</html>