<?php
/**
 
 * Author: Bijay Sapkota
 * Group: L5CG6
 */
/**
 * event_ticket.php
 * Dedicated event ticket confirmation page for eSewa / free event bookings.
 * Separate from ticket.php (which handles tour package bookings) to avoid team conflicts.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /Nepal-Travel/user/login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$booking_id) {
    die("Invalid ticket ID.");
}

// Fetch booking joined with event details
$stmt = $conn->prepare("
    SELECT 
        b.id, b.name, b.destination, b.date, b.guests, 
        b.status, b.payment_method, b.amount, b.payment_ref, b.created_at,
        e.title AS event_title, e.price_npr, e.location, e.venue_name,
        e.start_time, e.organizer_name, e.category, e.image_path
    FROM bookings b
    LEFT JOIN events e ON e.id = b.event_id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("Ticket not found or you don't have permission to view it.");
}

$event_title  = $booking['event_title'] ?: $booking['destination'];
$event_date   = !empty($booking['date']) && $booking['date'] !== '0000-00-00'
                ? date('l, F j, Y', strtotime($booking['date'])) : 'TBD';
$booked_on    = date('M j, Y \a\t g:i A', strtotime($booking['created_at']));
$ticket_ref   = 'EVT-' . str_pad($booking['id'], 6, '0', STR_PAD_LEFT);
$guests       = (int)$booking['guests'];
$amount       = (float)$booking['amount'];
$is_free      = $amount <= 0;
$is_paid      = in_array($booking['payment_method'], ['esewa', 'khalti']);
$status       = strtolower($booking['status']);
$venue        = $booking['venue_name'] ?: $booking['location'] ?: 'Nepal';
$start_time   = $booking['start_time'] ? date('g:i A', strtotime($booking['start_time'])) : '';
$category     = strtoupper($booking['category'] ?: 'EVENT');

$payment_label = match($booking['payment_method']) {
    'esewa'  => '<span style="color:#60bb46;font-weight:800;">e</span>Sewa',
    'khalti' => 'Khalti',
    'free'   => 'Free Admission',
    default  => ucfirst($booking['payment_method'] ?: 'Free')
};

$status_color = match($status) {
    'active', 'confirmed' => ['bg' => '#d1fae5', 'text' => '#065f46', 'label' => '✓ Confirmed'],
    'pending'             => ['bg' => '#fef3c7', 'text' => '#b45309', 'label' => '⏳ Pending'],
    'cancelled'           => ['bg' => '#fee2e2', 'text' => '#991b1b', 'label' => '✗ Cancelled'],
    default               => ['bg' => '#e0e7ff', 'text' => '#3730a3', 'label' => ucfirst($status)],
};

$msg = $_GET['msg'] ?? '';
$success_flash = $msg === 'esewa_success' ? true : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event_title) ?> — Event Ticket | Nepal Truly Authentic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, #0f1923 0%, #1a2a3a 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        /* Flash Banner */
        .success-banner {
            background: linear-gradient(90deg, #60bb46, #3da32a);
            color: white;
            text-align: center;
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
            width: 100%;
            max-width: 780px;
            box-shadow: 0 4px 20px rgba(96, 187, 70, 0.35);
            animation: slideDown 0.5s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Ticket Wrapper */
        .ticket-wrapper {
            width: 100%;
            max-width: 780px;
        }

        /* Ticket Card */
        .ticket {
            background: #ffffff;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
            position: relative;
        }

        /* Category Banner */
        .ticket-top-banner {
            background: linear-gradient(135deg, #1b3a5a 0%, #0f2640 100%);
            padding: 28px 36px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .ticket-brand {
            display: flex;
            flex-direction: column;
        }
        .ticket-brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            color: white;
            letter-spacing: 1px;
        }
        .ticket-brand-sub {
            font-size: 11px;
            color: rgba(255,255,255,0.45);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 3px;
        }
        .ticket-category-badge {
            background: rgba(245,166,35,0.15);
            border: 1px solid rgba(245,166,35,0.4);
            color: #f5a623;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 40px;
        }

        /* Event Hero Strip */
        .ticket-event-hero {
            background: linear-gradient(135deg, #1b3a5a, #2c5a8a);
            padding: 32px 36px;
            position: relative;
            overflow: hidden;
        }
        .ticket-event-hero::before {
            content: '🎫';
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 90px;
            opacity: 0.06;
        }
        .event-title-ticket {
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            color: white;
            line-height: 1.25;
            margin-bottom: 10px;
            max-width: 80%;
        }
        .event-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            margin-top: 8px;
        }
        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 13px;
            color: rgba(255,255,255,0.7);
        }
        .event-meta-item span { font-size: 16px; }

        /* Tear Line */
        .tear-line {
            display: flex;
            align-items: center;
            position: relative;
            background: #f4f6f8;
        }
        .tear-line::before,
        .tear-line::after {
            content: '';
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, #0f1923, #1a2a3a);
            border-radius: 50%;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            flex-shrink: 0;
        }
        .tear-line::before { left: -14px; }
        .tear-line::after  { right: -14px; }
        .tear-line-inner {
            width: 100%;
            border-top: 2px dashed #d1d8e0;
            margin: 0 24px;
        }
        .tear-ref {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            background: #1b3a5a;
            color: white;
            font-family: monospace;
            font-size: 13px;
            font-weight: 700;
            padding: 5px 18px;
            border-radius: 30px;
            white-space: nowrap;
            letter-spacing: 1.5px;
        }
        .tear-line-spacer { height: 48px; }

        /* Info Body */
        .ticket-body {
            padding: 28px 36px 24px;
            background: white;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 24px 16px;
            margin-bottom: 24px;
        }
        .info-cell { border-bottom: 1px solid #eef0f3; padding-bottom: 14px; }
        .info-cell .lbl {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9aa3b0;
            margin-bottom: 5px;
        }
        .info-cell .val {
            font-size: 15px;
            font-weight: 700;
            color: #1b2a3a;
            line-height: 1.3;
        }

        /* Status */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
        }

        /* Payment row */
        .payment-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8f9fb;
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        .payment-row-left { font-size: 13px; color: #666; }
        .payment-row-left strong { display: block; font-size: 22px; font-weight: 800; color: #1b2a3a; }
        .esewa-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f0fbec;
            border: 1.5px solid #a8e4a0;
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 700;
            color: #2a7a20;
        }
        .esewa-e {
            background: #60bb46;
            color: white;
            width: 22px;
            height: 22px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 900;
        }
        .free-badge {
            background: #eff6ff;
            border: 1.5px solid #bfdbfe;
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 700;
            color: #1d4ed8;
        }
        .cash-badge {
            background: #fefce8;
            border: 1.5px solid #fde68a;
            border-radius: 10px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 700;
            color: #92400e;
        }

        /* Footer */
        .ticket-footer {
            background: #f4f6f8;
            border-top: 1px dashed #d1d8e0;
            padding: 16px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .ticket-footer p { font-size: 11px; color: #9aa3b0; }

        /* Action Buttons */
        .action-bar {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 22px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary   { background: #1b3a5a; color: white; }
        .btn-primary:hover { background: #0f2640; transform: translateY(-1px); }
        .btn-secondary { background: white; color: #1b3a5a; border: 1.5px solid #d0dae7; }
        .btn-secondary:hover { background: #f4f6f8; }
        .btn-print     { background: #f5a623; color: white; }
        .btn-print:hover { background: #d48c00; }

        @media print {
            body { background: white; padding: 0; }
            .action-bar, .success-banner { display: none; }
            .ticket { box-shadow: none; border: 1px solid #ddd; border-radius: 0; }
            .ticket-top-banner,
            .ticket-event-hero { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr 1fr; }
            .event-title-ticket { font-size: 22px; max-width: 100%; }
            .ticket-top-banner { flex-direction: column; align-items: flex-start; gap: 10px; }
            .ticket-body, .ticket-event-hero, .ticket-top-banner { padding-left: 20px; padding-right: 20px; }
            .ticket-footer { padding: 14px 20px; }
        }
    </style>
</head>
<body>

<?php if ($success_flash): ?>
<div class="success-banner">
    🎉 Payment successful! Your event ticket has been confirmed and a receipt has been sent to your email.
</div>
<?php endif; ?>

<div class="ticket-wrapper">
    <div class="ticket">

        <!-- Top Banner -->
        <div class="ticket-top-banner">
            <div class="ticket-brand">
                <div class="ticket-brand-name">Nepal Truly Authentic</div>
                <div class="ticket-brand-sub">Official Event Ticket</div>
            </div>
            <div class="ticket-category-badge"><?= htmlspecialchars($category) ?></div>
        </div>

        <!-- Event Hero -->
        <div class="ticket-event-hero">
            <div class="event-title-ticket"><?= htmlspecialchars($event_title) ?></div>
            <div class="event-meta-row">
                <div class="event-meta-item"><span>📅</span><?= $event_date ?></div>
                <?php if ($start_time): ?>
                <div class="event-meta-item"><span>🕐</span><?= $start_time ?></div>
                <?php endif; ?>
                <div class="event-meta-item"><span>📍</span><?= htmlspecialchars($venue) ?></div>
                <?php if ($booking['organizer_name']): ?>
                <div class="event-meta-item"><span>🎙</span><?= htmlspecialchars($booking['organizer_name']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tear Line -->
        <div class="tear-line">
            <div class="tear-line-spacer"></div>
            <div class="tear-ref"><?= $ticket_ref ?></div>
            <div class="tear-line-inner"></div>
        </div>

        <!-- Body -->
        <div class="ticket-body">

            <!-- Info Grid -->
            <div class="info-grid">
                <div class="info-cell">
                    <div class="lbl">Ticket Holder</div>
                    <div class="val"><?= htmlspecialchars($booking['name']) ?></div>
                </div>
                <div class="info-cell">
                    <div class="lbl">Tickets</div>
                    <div class="val"><?= $guests ?> ticket<?= $guests > 1 ? 's' : '' ?></div>
                </div>
                <div class="info-cell">
                    <div class="lbl">Booking Status</div>
                    <div class="val">
                        <span class="status-pill" style="background:<?= $status_color['bg'] ?>;color:<?= $status_color['text'] ?>">
                            <?= $status_color['label'] ?>
                        </span>
                    </div>
                </div>
                <div class="info-cell">
                    <div class="lbl">Booked On</div>
                    <div class="val" style="font-size:13px;font-weight:500;color:#555;"><?= $booked_on ?></div>
                </div>
                <?php if ($booking['payment_ref'] && $booking['payment_method'] !== 'free'): ?>
                <div class="info-cell" style="grid-column: span 2;">
                    <div class="lbl">Transaction Reference</div>
                    <div class="val" style="font-family:monospace;font-size:13px;color:#555;"><?= htmlspecialchars($booking['payment_ref']) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Payment Row -->
            <div class="payment-row">
                <div class="payment-row-left">
                    <span>Total <?= $is_free ? 'Cost' : 'Paid' ?></span>
                    <strong><?= $is_free ? 'Free' : 'NPR ' . number_format($amount, 0) ?></strong>
                </div>
                <?php if ($booking['payment_method'] === 'esewa'): ?>
                <div class="esewa-badge">
                    <div class="esewa-e">e</div> Paid via eSewa
                </div>
                <?php elseif ($booking['payment_method'] === 'free'): ?>
                <div class="free-badge">🎟️ Free Admission</div>
                <?php else: ?>
                <div class="cash-badge">💵 <?= htmlspecialchars(ucfirst($booking['payment_method'])) ?></div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="action-bar">
                <a href="/Nepal-Travel/Public/events.php" class="btn-action btn-secondary">← Browse Events</a>
                <a href="/Nepal-Travel/user/dashboard.php?tab=bookings" class="btn-action btn-primary">My Bookings</a>
                <button class="btn-action btn-print" onclick="window.print()">🖨️ Print Ticket</button>
            </div>
        </div>

        <!-- Footer -->
        <div class="ticket-footer">
            <p>📧 A confirmation has been sent to your registered email address.</p>
            <p>Ref: <?= $ticket_ref ?> &nbsp;|&nbsp; Nepal Truly Authentic &nbsp;|&nbsp; <?= date('Y') ?></p>
        </div>

    </div>
</div>

</body>
</html>