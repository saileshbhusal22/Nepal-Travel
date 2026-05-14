<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$session_user_id = (int)$_SESSION['user_id'];

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if ($booking_id <= 0) {
    header("Location: deals-and-packages.php");
    exit;
}

// ── Load booking ─────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT
        b.id            AS booking_id,
        b.destination   AS deal_title,
        b.date          AS travel_date,
        b.guests,
        b.status        AS booking_status,
        b.deal_id,
        b.ud_id,
        COALESCE(d.image_url, ud.image_url) AS image_url,
        COALESCE(d.category, ud.category)   AS category,
        COALESCE(d.days, ud.days)           AS days,
        CASE WHEN b.deal_id IS NULL THEN 1 ELSE 0 END AS is_partner_deal,
        u.id        AS user_id,
        u.full_name,
        u.email
    FROM bookings b
    LEFT JOIN deals d ON d.id = b.deal_id
    LEFT JOIN user_deals ud ON ud.id = b.ud_id
    JOIN users u ON u.id = b.user_id
    WHERE b.id = ? AND b.user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $booking_id, $session_user_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: deals-and-packages.php");
    exit;
}

$is_partner_deal = (bool)$row['is_partner_deal'];
$deal_id = !empty($row['deal_id']) ? (int)$row['deal_id'] : null;
$ud_id   = !empty($row['ud_id'])   ? (int)$row['ud_id']   : null;

// ── If still no deal reference, redirect gracefully ──────────
if ($deal_id === null && $ud_id === null) {
    header("Location: deals-and-packages.php");
    exit;
}

// ── Already reviewed? ────────────────────────────────────────
$existing = null;
if ($deal_id !== null) {
    $stmt = $conn->prepare("SELECT id, rating, review_text FROM deal_reviews WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT id, rating, review_text FROM user_deal_reviews WHERE booking_id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$success       = false;
$error         = '';
$posted_rating = 0;

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing) {
    $rating      = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review_text = trim($_POST['review_text'] ?? '');
    $posted_rating = $rating;

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a star rating.';
    } elseif (mb_strlen($review_text) < 10) {
        $error = 'Review must be at least 10 characters.';
    } else {
        $user_id = (int)$row['user_id'];

        if ($deal_id !== null) {
            // ── Admin deal review ────────────────────────────
            $stmt = $conn->prepare("
                INSERT INTO deal_reviews (deal_id, booking_id, user_id, rating, review_text, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iiiss", $deal_id, $booking_id, $user_id, $rating, $review_text);
            if ($stmt->execute()) {
                $success = true;
                // Trigger automatically updates deals.rating & reviews_count
            } else {
                $error = 'Could not save your review. Please try again.';
                error_log("deal_reviews insert error: " . $stmt->error);
            }
            $stmt->close();

        } else {
            // ── Partner deal review ──────────────────────────
            $stmt = $conn->prepare("
                INSERT INTO user_deal_reviews (ud_id, booking_id, user_id, rating, review_text, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("iiiss", $ud_id, $booking_id, $user_id, $rating, $review_text);
            if ($stmt->execute()) {
                $success = true;

                // Update average rating in user_deals
                $avg_stmt = $conn->prepare("SELECT AVG(rating) AS avg FROM user_deal_reviews WHERE ud_id = ?");
                $avg_stmt->bind_param("i", $ud_id);
                $avg_stmt->execute();
                $avg_result = $avg_stmt->get_result()->fetch_assoc();
                $avg_stmt->close();

                if (!empty($avg_result['avg'])) {
                    $new_avg    = round((float)$avg_result['avg'], 1);
                    $upd_stmt   = $conn->prepare("UPDATE user_deals SET average_rating = ? WHERE id = ?");
                    $upd_stmt->bind_param("di", $new_avg, $ud_id);
                    $upd_stmt->execute();
                    $upd_stmt->close();
                }
            } else {
                $error = 'Could not save your review. Please try again.';
                error_log("user_deal_reviews insert error: " . $stmt->error);
            }
            $stmt->close();
        }
    }
}

$star_labels = [1 => 'Terrible', 2 => 'Poor', 3 => 'Okay', 4 => 'Good', 5 => 'Excellent'];

include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Rate Your Experience | Nepal Tours</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh; color: #fff;
            background-image: url('https://www.discovertreks.com/wp-content/uploads/2017/09/Nepal-discover-himalayan-treks.jpg');
            background-size: cover; background-position: center; background-attachment: fixed;
        }
        .overlay {
            min-height: 100vh; background: rgba(8,10,20,0.62);
            display: flex; align-items: center; justify-content: center; padding: 3rem 1rem;
        }
        .card {
            background: rgba(18,21,31,0.94); backdrop-filter: blur(18px);
            border: 1px solid rgba(255,255,255,0.08); border-radius: 24px;
            width: 100%; max-width: 540px; overflow: hidden;
        }
        .partner-ribbon {
            background: rgba(201,162,39,0.12);
            border-bottom: 1px solid rgba(201,162,39,0.20);
            padding: 8px 16px; font-size: 11px; font-weight: 700;
            color: #c9a227; letter-spacing: 0.08em;
            text-align: center; text-transform: uppercase;
        }
        .deal-banner { position: relative; height: 170px; overflow: hidden; }
        .deal-banner img { width: 100%; height: 100%; object-fit: cover; filter: brightness(0.50); }
        .deal-banner-fallback {
            width: 100%; height: 100%;
            background: linear-gradient(135deg, #1a1f35, #0d1020);
            display: flex; align-items: center; justify-content: center; font-size: 5rem;
        }
        .deal-banner-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.75) 0%, transparent 60%);
        }
        .deal-banner-text { position: absolute; bottom: 1rem; left: 1.5rem; right: 1.5rem; }
        .deal-banner-text h2 { font-family: 'Playfair Display', serif; font-size: 1.25rem; color: #fff; }
        .deal-banner-meta { display: flex; gap: 10px; margin-top: 4px; flex-wrap: wrap; }
        .deal-banner-meta span { font-size: 11px; color: rgba(255,255,255,0.45); }
        .user-pill {
            display: flex; align-items: center; gap: 10px;
            margin: 1.3rem 1.6rem 0;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 50px; padding: 8px 14px;
        }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 700; color: #fff; flex-shrink: 0;
        }
        .user-info-name { font-size: 13px; font-weight: 600; color: #fff; }
        .user-info-email { font-size: 11px; color: rgba(255,255,255,0.28); }
        .card-body { padding: 1.6rem 1.6rem 2rem; }
        .card-title { font-family: 'Playfair Display', serif; font-size: 1.5rem; color: #fff; margin-bottom: 0.3rem; }
        .card-sub { font-size: 13px; color: rgba(255,255,255,0.32); margin-bottom: 1.8rem; }
        .star-section { margin-bottom: 1.6rem; }
        .field-label {
            font-size: 10px; font-weight: 700; letter-spacing: 0.14em;
            text-transform: uppercase; color: rgba(255,255,255,0.28);
            margin-bottom: 10px; display: block;
        }
        .star-picker { display: flex; gap: 8px; direction: rtl; justify-content: flex-end; }
        .star-picker input { display: none; }
        .star-picker label {
            font-size: 2.6rem; cursor: pointer; color: rgba(255,255,255,0.12);
            transition: color 0.12s, transform 0.1s;
        }
        .star-picker input:checked ~ label,
        .star-picker label:hover,
        .star-picker label:hover ~ label { color: #f4b942; }
        .star-picker label:active { transform: scale(1.25); }
        .star-hint {
            text-align: center; font-size: 12px; color: rgba(255,255,255,0.30);
            margin-top: 6px; min-height: 18px;
        }
        .field { margin-bottom: 1.2rem; }
        .field textarea {
            width: 100%; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 11px; color: #fff;
            font-family: 'DM Sans', sans-serif; font-size: 14px;
            padding: 12px 14px; outline: none; resize: vertical;
            min-height: 120px;
        }
        .field textarea:focus { border-color: #2563eb; }
        .char-count { text-align: right; font-size: 11px; color: rgba(255,255,255,0.20); margin-top: 4px; }
        .error-box {
            background: rgba(232,67,147,0.10); border: 1px solid rgba(232,67,147,0.28);
            border-radius: 10px; color: #f48fb1; font-size: 13px;
            padding: 11px 14px; margin-bottom: 1.2rem;
        }
        .submit-btn {
            width: 100%; background: #2563eb; color: #fff;
            font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600;
            border: none; border-radius: 12px; padding: 15px;
            cursor: pointer;
        }
        .submit-btn:hover { background: #1d4ed8; }
        .skip-link {
            display: block; text-align: center; margin-top: 1rem;
            font-size: 12px; color: rgba(255,255,255,0.20); text-decoration: none;
        }
        .skip-link:hover { color: rgba(255,255,255,0.45); }
        .success-wrap { padding: 3rem 2rem; text-align: center; }
        .success-icon { font-size: 4.5rem; margin-bottom: 1rem; }
        .success-stars { font-size: 2.2rem; color: #f4b942; letter-spacing: 5px; margin: 0.8rem 0 1rem; }
        .success-title { font-family: 'Playfair Display', serif; font-size: 1.9rem; color: #fff; margin-bottom: 0.6rem; }
        .success-msg { font-size: 14px; color: rgba(255,255,255,0.40); line-height: 1.7; margin-bottom: 2rem; }
        .go-btn {
            display: inline-block; background: #2563eb; color: #fff;
            font-size: 14px; font-weight: 600; padding: 13px 32px;
            border-radius: 12px; text-decoration: none; margin: 4px;
        }
        .go-btn:hover { background: #1d4ed8; }
        .go-btn.secondary {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.15);
            color: rgba(255,255,255,0.55);
        }
        .go-btn.secondary:hover { background: rgba(255,255,255,0.07); color: #fff; }
        .already-wrap { padding: 3rem 2rem; text-align: center; }
        .already-wrap .big-icon { font-size: 3.5rem; margin-bottom: 1rem; }
        .already-wrap p { color: rgba(255,255,255,0.40); font-size: 14px; line-height: 1.7; }
        .already-wrap .your-review {
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);
            border-radius: 12px; padding: 1rem 1.2rem; margin: 1.2rem 0; text-align: left;
        }
        .already-wrap .your-stars { color: #f4b942; font-size: 1.3rem; letter-spacing: 3px; margin-bottom: 6px; }
        .already-wrap .your-text { font-size: 13px; color: rgba(255,255,255,0.55); line-height: 1.7; }
        @media (max-width: 580px) {
            .card { border-radius: 16px; }
            .deal-banner { height: 140px; }
            .star-picker label { font-size: 2rem; }
        }
    </style>
</head>
<body>
<div class="overlay">
<div class="card">

<?php if ($is_partner_deal): ?>
    <div class="partner-ribbon">✦ Partner Deal Review</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="success-wrap">
    <div class="success-icon">🎉</div>
    <h1 class="success-title">Thank You, <?= htmlspecialchars(explode(' ', $row['full_name'])[0]) ?>!</h1>
    <div class="success-stars">
        <?= str_repeat('★', $posted_rating) ?><?= str_repeat('☆', 5 - $posted_rating) ?>
    </div>
    <p class="success-msg">
        Your review for
        <strong style="color:rgba(255,255,255,0.70)"><?= htmlspecialchars($row['deal_title']) ?></strong>
        has been saved and will help other travellers plan their adventure.
    </p>
    <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
        <a href="deals-and-packages.php" class="go-btn secondary">Browse More Deals</a>
    </div>
</div>

<?php elseif ($existing): ?>
<div class="already-wrap">
    <div class="big-icon">✅</div>
    <p>You've already reviewed
        <strong style="color:rgba(255,255,255,0.65)"><?= htmlspecialchars($row['deal_title']) ?></strong>.
    </p>
    <div class="your-review">
        <div class="your-stars">
            <?= str_repeat('★', (int)$existing['rating']) ?><?= str_repeat('☆', 5 - (int)$existing['rating']) ?>
        </div>
        <p class="your-text"><?= nl2br(htmlspecialchars($existing['review_text'])) ?></p>
    </div>
    <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:1rem;">
        <a href="deals-and-packages.php" class="go-btn secondary">Browse Deals</a>
    </div>
</div>

<?php else: ?>
<div class="deal-banner">
    <?php if (!empty($row['image_url'])): ?>
        <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="<?= htmlspecialchars($row['deal_title']) ?>">
    <?php else: ?>
        <div class="deal-banner-fallback">🏔️</div>
    <?php endif; ?>
    <div class="deal-banner-overlay"></div>
    <div class="deal-banner-text">
        <h2><?= htmlspecialchars($row['deal_title']) ?></h2>
        <div class="deal-banner-meta">
            <?php if ($is_partner_deal): ?>
                <span>✦ Partner Listing</span>
            <?php endif; ?>
            <?php if (!empty($row['category'])): ?>
                <span>📂 <?= htmlspecialchars($row['category']) ?></span>
            <?php endif; ?>
            <?php if (!empty($row['days'])): ?>
                <span>📅 <?= (int)$row['days'] ?> days</span>
            <?php endif; ?>
            <?php if (!empty($row['travel_date'])): ?>
                <span>🗓 <?= date('M j, Y', strtotime($row['travel_date'])) ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="user-pill">
    <div class="user-avatar"><?= mb_strtoupper(mb_substr($row['full_name'], 0, 1)) ?></div>
    <div>
        <div class="user-info-name"><?= htmlspecialchars($row['full_name']) ?></div>
        <div class="user-info-email"><?= htmlspecialchars($row['email']) ?></div>
    </div>
</div>

<div class="card-body">
    <h1 class="card-title">How was your trip?</h1>
    <p class="card-sub">Your feedback helps other travellers choose the right adventure.</p>

    <?php if ($error): ?>
        <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="star-section">
            <span class="field-label">Your Rating</span>
            <div class="star-picker" id="starPicker">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="rating" id="s<?= $i ?>" value="<?= $i ?>"
                        <?= (isset($_POST['rating']) && (int)$_POST['rating'] === $i) ? 'checked' : '' ?>>
                    <label for="s<?= $i ?>" title="<?= $star_labels[$i] ?>">★</label>
                <?php endfor; ?>
            </div>
            <p class="star-hint" id="starHint">Tap a star to rate</p>
        </div>

        <div class="field">
            <label class="field-label" for="reviewText">Your Review</label>
            <textarea name="review_text" id="reviewText" maxlength="1000"
                placeholder="What did you love? Any tips for future travellers?"
                required><?= htmlspecialchars($_POST['review_text'] ?? '') ?></textarea>
            <p class="char-count"><span id="charNum">0</span> / 1000</p>
        </div>

        <button type="submit" class="submit-btn">Submit Review ✨</button>
    </form>

    <a href="deals-and-packages.php" class="skip-link">Maybe later → Browse more deals</a>
</div>

<?php endif; ?>

</div>
</div>

<script>
    const hints = { 1:'Terrible 😞', 2:'Poor 😐', 3:'Okay 🙂', 4:'Good 😊', 5:'Excellent 🤩' };
    const hint  = document.getElementById('starHint');
    document.querySelectorAll('.star-picker input').forEach(input => {
        input.addEventListener('change', () => {
            if (hint) { hint.textContent = hints[input.value] ?? ''; hint.style.color = '#f4b942'; }
        });
    });
    document.querySelectorAll('.star-picker label').forEach(label => {
        label.addEventListener('mouseenter', () => {
            const v = label.getAttribute('for').replace('s','');
            if (hint) hint.textContent = hints[v] ?? '';
        });
        label.addEventListener('mouseleave', () => {
            const checked = document.querySelector('.star-picker input:checked');
            if (hint) hint.textContent = checked ? (hints[checked.value] ?? '') : 'Tap a star to rate';
        });
    });
    const ta = document.getElementById('reviewText');
    const cn = document.getElementById('charNum');
    if (ta && cn) {
        cn.textContent = ta.value.length;
        ta.addEventListener('input', () => cn.textContent = ta.value.length);
    }
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>