<?php
require_once __DIR__ . '/../config/db.php';
include '../includes/header.php';

// ── Auto-expire user deals past visible_until ──────────────────
$conn->query("
    UPDATE user_deals
    SET status = 'expired'
    WHERE status = 'approved'
      AND visible_until IS NOT NULL
      AND visible_until < NOW()
");

// ── FETCH ALL ADMIN DEALS ──────────────────────────────────────
$result = $conn->query("SELECT *, 'admin' AS deal_source FROM deals ORDER BY id DESC");
if (!$result) {
    die('<p style="color:red;padding:2rem;">Deals query failed: ' . $conn->error . '</p>');
}
$admin_deals = $result->fetch_all(MYSQLI_ASSOC);

// ── FETCH APPROVED USER-SUBMITTED DEALS ───────────────────────
$ud_result = $conn->query("
    SELECT
        ud.*,
        'user_submitted'  AS deal_source,
        u.full_name       AS submitted_by,
        sp.display_name   AS plan_name,
        sp.duration_days
    FROM user_deals ud
    LEFT JOIN users u               ON u.id  = ud.user_id
    LEFT JOIN user_subscriptions us ON us.id = ud.subscription_id
    LEFT JOIN subscription_plans sp ON sp.id = us.plan_id
    WHERE ud.status        = 'approved'
      AND ud.visible_from <= NOW()
      AND ud.visible_until > NOW()
    ORDER BY ud.visible_until ASC
");
$user_deals = $ud_result ? $ud_result->fetch_all(MYSQLI_ASSOC) : [];

// ── MERGE: admin first, then active user deals ─────────────────
$all_deals = array_merge($admin_deals, $user_deals);

// ── FETCH REAL RATINGS FROM deal_reviews TABLE (admin deals) ───
$reviewData    = [];
$reviewDetails = [];

$tableCheck = $conn->query("SHOW TABLES LIKE 'deal_reviews'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $rr = $conn->query("
        SELECT deal_id,
               ROUND(AVG(rating), 1) AS avg_rating,
               COUNT(*)              AS reviews_count
        FROM deal_reviews
        GROUP BY deal_id
    ");
    if ($rr) {
        while ($row = $rr->fetch_assoc()) {
            $reviewData[$row['deal_id']] = $row;
        }
    }

    $rd = $conn->query("
        SELECT r.deal_id,
               r.id          AS review_id,
               r.rating,
               r.review_text,
               r.created_at,
               u.full_name   AS reviewer_name
        FROM deal_reviews r
        LEFT JOIN users u ON u.id = r.user_id
        ORDER BY r.created_at DESC
    ");
    if (!$rd) {
        $rd = $conn->query("
            SELECT deal_id,
                   id          AS review_id,
                   rating,
                   review_text,
                   created_at,
                   NULL        AS reviewer_name
            FROM deal_reviews
            ORDER BY created_at DESC
        ");
    }
    if ($rd) {
        while ($row = $rd->fetch_assoc()) {
            $reviewDetails[$row['deal_id']][] = $row;
        }
    }
}

// ── FETCH RATINGS FROM user_deal_reviews TABLE (user deals) ────
$udReviewData    = [];
$udReviewDetails = [];

$udTableCheck = $conn->query("SHOW TABLES LIKE 'user_deal_reviews'");
if ($udTableCheck && $udTableCheck->num_rows > 0) {
    $urr = $conn->query("
        SELECT ud_id,
               ROUND(AVG(rating), 1) AS avg_rating,
               COUNT(*)              AS reviews_count
        FROM user_deal_reviews
        GROUP BY ud_id
    ");
    if ($urr) {
        while ($row = $urr->fetch_assoc()) {
            $udReviewData[$row['ud_id']] = $row;
        }
    }

    $urd = $conn->query("
        SELECT r.ud_id,
               r.id          AS review_id,
               r.rating,
               r.review_text,
               r.created_at,
               u.full_name   AS reviewer_name
        FROM user_deal_reviews r
        LEFT JOIN users u ON u.id = r.user_id
        ORDER BY r.created_at DESC
    ");
    if ($urd) {
        while ($row = $urd->fetch_assoc()) {
            $udReviewDetails[$row['ud_id']][] = $row;
        }
    }
}

// ── SLIDER: top 5 deals that have an image (admin only) ────────
$slider_deals = array_filter($admin_deals, fn($d) =>
    !empty($d['image_url']) && strtoupper(trim($d['image_url'])) !== 'NULL'
);
$slider_deals = array_slice(array_values($slider_deals), 0, 5);

// ── UNIQUE CATEGORIES from all deals ──────────────────────────
$all_categories = array_unique(array_filter(array_column($all_deals, 'category')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Deals &amp; Packages | Nepal Tours</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    /* ═══════════════════════════════════════════
       RESET & ROOT VARIABLES
    ═══════════════════════════════════════════ */
    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --gold:      #C9A227;
      --gold-lt:   #E8C44A;
      --gold-dk:   #A8841A;
      --blue:      #2563eb;
      --blue-lt:   #4A90D9;
      --bg-card:   #12151f;
      --bg-modal:  #13161f;
      --border:    rgba(255,255,255,0.08);
      --border2:   rgba(255,255,255,0.14);
      --text:      #F0EEE8;
      --muted:     rgba(240,238,232,0.45);
      --muted2:    rgba(240,238,232,0.25);
      --ff-h:      'Playfair Display', serif;
      --ff-b:      'DM Sans', sans-serif;
      --ff-m:      'DM Mono', monospace;
      --radius-card: 18px;
      --shadow-card: 0 4px 24px rgba(0,0,0,0.4);
      --transition: 0.22s ease;
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: var(--ff-b);
      min-height: 100vh;
      background-image: url('https://www.discovertreks.com/wp-content/uploads/2017/09/Nepal-discover-himalayan-treks.jpg');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      -webkit-font-smoothing: antialiased;
    }

    /* For iOS, fixed background doesn't work well */
    @supports (-webkit-touch-callout: none) {
      body { background-attachment: scroll; }
    }

    .page-overlay {
      min-height: 100vh;
      background: rgba(8, 10, 20, 0.22);
      padding-bottom: 5rem;
    }

    /* ═══════════════════════════════════════════
       HERO SLIDER
    ═══════════════════════════════════════════ */
    .hero-slider {
      position: relative;
      width: 100%;
      height: clamp(380px, 60vw, 900px);
      overflow: hidden;
      margin-bottom: clamp(2rem, 4vw, 3.5rem);
    }

    .hero-slide {
      position: absolute;
      inset: 0;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      opacity: 0;
      transition: opacity 0.9s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .hero-slide.active { opacity: 1; z-index: 1; }

    .hero-slide-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(
        to bottom,
        rgba(0,0,0,0.08) 0%,
        rgba(0,0,0,0.50) 55%,
        rgba(0,0,0,0.72) 100%
      );
    }

    .hero-slide-content {
      position: relative;
      z-index: 2;
      text-align: center;
      padding: 0 clamp(1.2rem, 5vw, 3rem);
      max-width: 860px;
      width: 100%;
    }

    .hero-label {
      font-size: clamp(9px, 1.5vw, 11px);
      font-weight: 700;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: rgba(255,255,255,0.58);
      margin-bottom: 0.7rem;
      font-family: var(--ff-m);
    }

    .hero-title {
      font-family: var(--ff-h);
      font-size: clamp(1.5rem, 5vw, 3.4rem);
      font-weight: 700;
      color: #fff;
      line-height: 1.13;
      margin-bottom: clamp(1rem, 2.5vw, 1.8rem);
      text-shadow: 0 2px 28px rgba(0,0,0,0.5);
    }

    .hero-cta {
      display: inline-block;
      background: var(--gold);
      color: #fff;
      font-family: var(--ff-b);
      font-size: clamp(10px, 1.8vw, 12px);
      font-weight: 700;
      letter-spacing: 0.17em;
      text-transform: uppercase;
      padding: clamp(10px, 2vw, 14px) clamp(24px, 4vw, 40px);
      border-radius: 4px;
      text-decoration: none;
      transition: background var(--transition), transform 0.15s;
    }
    .hero-cta:hover { background: var(--gold-dk); transform: scale(1.03); }

    .hero-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      z-index: 3;
      background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.20);
      color: #fff;
      font-size: clamp(20px, 3vw, 28px);
      width: clamp(36px, 5vw, 46px);
      height: clamp(36px, 5vw, 46px);
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background var(--transition);
      line-height: 1;
    }
    .hero-arrow:hover { background: rgba(255,255,255,0.25); }
    .hero-prev { left: clamp(10px, 2vw, 20px); }
    .hero-next { right: clamp(10px, 2vw, 20px); }

    .hero-dots {
      position: absolute;
      bottom: clamp(12px, 2vw, 18px);
      left: 50%;
      transform: translateX(-50%);
      z-index: 3;
      display: flex;
      gap: 7px;
    }
    .hero-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: rgba(255,255,255,0.35);
      cursor: pointer;
      transition: background 0.2s, transform 0.2s;
      border: none;
    }
    .hero-dot.active { background: var(--gold); transform: scale(1.3); }

    /* ═══════════════════════════════════════════
       PAGE HEADING
    ═══════════════════════════════════════════ */
    .page-heading {
      text-align: center;
      margin-bottom: clamp(1.2rem, 3vw, 2rem);
      padding: 0 clamp(1rem, 4vw, 2rem);
    }
    .page-heading h1 {
      font-family: var(--ff-h);
      font-size: clamp(1.9rem, 5vw, 2.8rem);
      font-weight: 700;
      color: #fff;
      letter-spacing: -0.01em;
      line-height: 1.1;
    }
    .page-heading p {
      color: rgba(255,255,255,0.48);
      font-size: clamp(0.85rem, 2vw, 0.95rem);
      margin-top: 0.5rem;
    }

    /* ═══════════════════════════════════════════
       SEARCH BAR
    ═══════════════════════════════════════════ */
    .search-wrap {
      display: flex;
      justify-content: center;
      margin-bottom: clamp(1rem, 2.5vw, 1.5rem);
      padding: 0 clamp(1rem, 4vw, 1.5rem);
    }
    .search-inner {
      position: relative;
      width: 100%;
      max-width: 520px;
    }
    .search-icon {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 15px;
      pointer-events: none;
      line-height: 1;
    }
    .search-input {
      width: 100%;
      padding: 13px 44px 13px 44px;
      background: rgba(255,255,255,0.09);
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: 30px;
      color: #fff;
      font-family: var(--ff-b);
      font-size: clamp(13px, 2.2vw, 14px);
      outline: none;
      transition: background var(--transition), border-color var(--transition);
    }
    .search-input::placeholder { color: rgba(255,255,255,0.36); }
    .search-input:focus {
      background: rgba(255,255,255,0.13);
      border-color: rgba(100,150,255,0.50);
    }
    .search-clear {
      display: none;
      position: absolute;
      right: 13px;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(255,255,255,0.12);
      border: none;
      color: rgba(255,255,255,0.6);
      width: 22px;
      height: 22px;
      border-radius: 50%;
      cursor: pointer;
      font-size: 11px;
      align-items: center;
      justify-content: center;
      transition: background 0.15s;
    }
    .search-clear:hover { background: rgba(255,255,255,0.22); color: #fff; }
    .search-clear.visible { display: flex; }

    /* ═══════════════════════════════════════════
       FILTER BAR  — scrollable on mobile
    ═══════════════════════════════════════════ */
    .filter-wrap {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
      margin-bottom: clamp(1.8rem, 4vw, 3rem);
      padding: 0 clamp(1rem, 4vw, 1.5rem);
    }
    .filter-wrap::-webkit-scrollbar { display: none; }

    .filter-bar {
      display: flex;
      gap: 0.45rem;
      width: max-content;
      margin: 0 auto;
    }
    .filter-btn {
      background: rgba(255,255,255,0.07);
      color: rgba(255,255,255,0.65);
      border: 1px solid rgba(255,255,255,0.12);
      padding: clamp(7px, 1.5vw, 9px) clamp(16px, 3vw, 24px);
      border-radius: 30px;
      font-family: var(--ff-b);
      font-size: clamp(12px, 1.8vw, 13px);
      font-weight: 500;
      cursor: pointer;
      white-space: nowrap;
      transition: background var(--transition), color var(--transition), border-color var(--transition);
    }
    .filter-btn:hover,
    .filter-btn.active {
      background: var(--blue);
      color: #fff;
      border-color: var(--blue);
    }

    /* ═══════════════════════════════════════════
       LIST YOUR DEAL BANNER
    ═══════════════════════════════════════════ */
    .list-deal-banner {
      max-width: 1320px;
      margin: 0 auto clamp(1.8rem, 4vw, 3rem);
      padding: 0 clamp(1rem, 3vw, 2.5rem);
    }
    .list-deal-inner {
      background: linear-gradient(135deg, rgba(201,162,39,0.12) 0%, rgba(37,99,235,0.10) 100%);
      border: 1px solid rgba(201,162,39,0.25);
      border-radius: var(--radius-card);
      padding: clamp(1.2rem, 3vw, 1.7rem) clamp(1.2rem, 4vw, 2.2rem);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: clamp(1rem, 3vw, 1.5rem);
      flex-wrap: wrap;
    }
    .list-deal-text h3 {
      font-family: var(--ff-h);
      font-size: clamp(1rem, 2.5vw, 1.25rem);
      font-weight: 700;
      color: #fff;
      margin-bottom: 4px;
    }
    .list-deal-text p {
      font-size: clamp(11px, 1.8vw, 13px);
      color: rgba(255,255,255,0.48);
    }
    .list-deal-btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      background: var(--gold);
      color: #000;
      font-family: var(--ff-b);
      font-size: clamp(12px, 1.8vw, 13px);
      font-weight: 800;
      padding: clamp(10px, 2vw, 13px) clamp(20px, 3.5vw, 28px);
      border-radius: 10px;
      text-decoration: none;
      white-space: nowrap;
      letter-spacing: 0.04em;
      transition: background var(--transition), transform 0.15s;
      flex-shrink: 0;
    }
    .list-deal-btn:hover { background: var(--gold-lt); transform: scale(1.02); }

    /* ═══════════════════════════════════════════
       CARDS GRID  — fluid, adaptive columns
    ═══════════════════════════════════════════ */
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
      gap: clamp(1.2rem, 3vw, 2.5rem);
      max-width: 1360px;
      margin: 0 auto;
      padding: 0 clamp(1rem, 3vw, 2.5rem);
    }

    /* ═══════════════════════════════════════════
       TOUR CARD
    ═══════════════════════════════════════════ */
    .tour-card {
      background: var(--bg-card);
      border-radius: var(--radius-card);
      border: 1px solid var(--border);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: transform var(--transition), border-color var(--transition), box-shadow var(--transition);
      cursor: pointer;
      text-decoration: none;
      color: inherit;
      box-shadow: var(--shadow-card);
    }
    .tour-card:hover {
      transform: translateY(-5px);
      border-color: rgba(100,150,255,0.3);
      box-shadow: 0 22px 54px rgba(0,0,0,0.55);
    }
    .tour-card.partner-card { border-color: rgba(201,162,39,0.18); }
    .tour-card.partner-card:hover {
      border-color: rgba(201,162,39,0.4);
      box-shadow: 0 22px 54px rgba(201,162,39,0.09);
    }

    /* ── PHOTO AREA ── */
    .card-photo {
      position: relative;
      overflow: hidden;
      height: clamp(180px, 28vw, 250px);
    }
    .card-photo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.42s ease;
    }
    .tour-card:hover .card-photo img { transform: scale(1.05); }

    .card-icon-fallback {
      height: clamp(180px, 28vw, 250px);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: clamp(56px, 10vw, 80px);
      background: linear-gradient(135deg, #1a1f35 0%, #0d1020 100%);
      position: relative;
    }

    /* ── BADGES ── */
    .cat-badge {
      position: absolute;
      top: 12px;
      left: 12px;
      background: rgba(92,63,204,0.9);
      backdrop-filter: blur(6px);
      color: #fff;
      font-size: clamp(9px, 1.5vw, 10px);
      font-weight: 700;
      padding: 4px 11px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      z-index: 2;
    }
    .discount-badge {
      position: absolute;
      top: 12px;
      right: 12px;
      background: #e84393;
      color: #fff;
      font-size: clamp(10px, 1.8vw, 11px);
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 7px;
      z-index: 2;
    }
    .partner-badge {
      position: absolute;
      bottom: 10px;
      left: 12px;
      z-index: 2;
      background: rgba(201,162,39,0.88);
      backdrop-filter: blur(6px);
      color: #000;
      font-size: clamp(8px, 1.4vw, 9px);
      font-weight: 800;
      letter-spacing: 1.5px;
      padding: 4px 10px;
      border-radius: 20px;
      text-transform: uppercase;
    }
    .expiry-badge {
      position: absolute;
      bottom: 10px;
      right: 12px;
      z-index: 2;
      font-size: clamp(9px, 1.5vw, 10px);
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 7px;
      font-family: var(--ff-m);
    }
    .expiry-soon { background: rgba(232,67,147,0.85); color: #fff; }
    .expiry-ok   { background: rgba(76,175,125,0.75); color: #fff; }
    .expiry-warn { background: rgba(240,160,48,0.85); color: #000; }

    /* ── CARD BODY ── */
    .card-body {
      padding: clamp(1rem, 2.5vw, 1.35rem) clamp(1rem, 2.5vw, 1.4rem) clamp(1.1rem, 2.5vw, 1.45rem);
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: clamp(6px, 1.2vw, 9px);
    }

    .location-label {
      font-size: clamp(10px, 1.6vw, 11px);
      font-weight: 700;
      color: #7c9ef8;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
    .card-title {
      font-family: var(--ff-h);
      font-size: clamp(15px, 2.5vw, 18px);
      font-weight: 600;
      color: #fff;
      line-height: 1.3;
    }
    .meta-row {
      display: flex;
      align-items: center;
      gap: clamp(8px, 2vw, 14px);
      font-size: clamp(11px, 1.8vw, 13px);
      color: rgba(255,255,255,0.45);
      flex-wrap: wrap;
    }
    .meta-item { display: flex; align-items: center; gap: 4px; }

    /* ── RATING ROW ── */
    .rating-row {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: clamp(12px, 2vw, 13px);
      width: fit-content;
      padding: 4px 8px 4px 0;
      border-radius: 7px;
      transition: background 0.15s;
    }
    .rating-row.has-reviews { cursor: pointer; }
    .rating-row.has-reviews:hover { background: rgba(255,255,255,0.06); }
    .rating-row.has-reviews:hover .rating-count {
      color: #7c9ef8;
      text-decoration: underline;
    }
    .star-filled { color: #f4b942; font-size: clamp(13px, 2vw, 14px); }
    .star-empty  { color: rgba(255,255,255,0.18); font-size: clamp(13px, 2vw, 14px); }
    .rating-value { color: #fff; font-weight: 600; font-size: clamp(12px, 2vw, 13px); margin-left: 2px; }
    .rating-count { color: rgba(255,255,255,0.35); font-size: clamp(11px, 1.8vw, 12px); transition: color 0.15s; }
    .no-reviews   { color: rgba(255,255,255,0.25); font-size: clamp(11px, 1.8vw, 12px); font-style: italic; }

    .tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 2px; }
    .tag {
      background: rgba(37,99,235,0.22);
      color: #a8c4f8;
      font-size: clamp(10px, 1.6vw, 11px);
      padding: 3px 10px;
      border-radius: 5px;
      border: 1px solid rgba(100,150,255,0.18);
    }

    /* ── CARD FOOTER ── */
    .card-footer {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      margin-top: auto;
      padding-top: clamp(10px, 2vw, 14px);
      border-top: 1px solid rgba(255,255,255,0.06);
      gap: 0.5rem;
    }
    .price-from  { font-size: clamp(9px, 1.5vw, 10px); font-weight: 600; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.07em; }
    .price-original { font-size: clamp(11px, 1.8vw, 12px); color: rgba(255,255,255,0.3); text-decoration: line-through; line-height: 1.2; }
    .price-main  { font-size: clamp(18px, 3vw, 22px); font-weight: 700; color: #fff; line-height: 1.1; }
    .price-sub   { font-size: clamp(10px, 1.6vw, 11px); color: rgba(255,255,255,0.35); }
    .view-btn {
      background: var(--blue);
      color: #fff;
      font-family: var(--ff-b);
      font-size: clamp(11px, 1.8vw, 13px);
      font-weight: 600;
      border: none;
      border-radius: 9px;
      padding: clamp(9px, 1.8vw, 12px) clamp(14px, 2.5vw, 20px);
      white-space: nowrap;
      pointer-events: none;
      flex-shrink: 0;
    }

    /* ── HIDDEN / NO-RESULTS ── */
    .tour-card.hidden { display: none; }
    .no-results {
      text-align: center;
      color: rgba(255,255,255,0.4);
      font-size: 1rem;
      padding: 3rem 0;
      grid-column: 1 / -1;
    }

    /* ═══════════════════════════════════════════
       REVIEWS MODAL
    ═══════════════════════════════════════════ */
    .modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      z-index: 9999;
      background: rgba(0,0,0,0.75);
      backdrop-filter: blur(5px);
      align-items: center;
      justify-content: center;
      padding: clamp(0.8rem, 3vw, 1.5rem);
      overflow-y: auto;
    }
    .modal-backdrop.open { display: flex; }

    .modal-box {
      background: var(--bg-modal);
      border: 1px solid rgba(255,255,255,0.10);
      border-radius: clamp(14px, 3vw, 22px);
      width: 100%;
      max-width: 560px;
      max-height: min(88vh, 700px);
      display: flex;
      flex-direction: column;
      box-shadow: 0 30px 80px rgba(0,0,0,0.7);
      animation: modalIn 0.22s ease;
      margin: auto;
    }
    @keyframes modalIn {
      from { opacity: 0; transform: translateY(18px) scale(0.97); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .modal-header {
      padding: clamp(1rem, 2.5vw, 1.4rem) clamp(1rem, 3vw, 1.6rem) clamp(0.8rem, 2vw, 1rem);
      border-bottom: 1px solid rgba(255,255,255,0.07);
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
      flex-shrink: 0;
    }
    .modal-header-left { flex: 1; min-width: 0; }
    .modal-deal-title {
      font-family: var(--ff-h);
      font-size: clamp(14px, 2.5vw, 17px);
      font-weight: 600;
      color: #fff;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 8px;
    }
    .modal-summary { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .modal-avg-stars { display: flex; gap: 2px; }
    .modal-avg-num { font-size: clamp(20px, 4vw, 24px); font-weight: 700; color: #fff; line-height: 1; }
    .modal-avg-label { font-size: clamp(11px, 1.8vw, 12px); color: rgba(255,255,255,0.4); align-self: flex-end; margin-bottom: 2px; }
    .modal-close {
      background: rgba(255,255,255,0.07);
      border: 1px solid rgba(255,255,255,0.12);
      color: rgba(255,255,255,0.6);
      font-size: 17px;
      line-height: 1;
      min-width: 34px;
      width: 34px;
      height: 34px;
      border-radius: 50%;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      transition: background 0.15s, color 0.15s;
      touch-action: manipulation;
    }
    .modal-close:hover { background: rgba(255,80,80,0.18); color: #ff6b6b; border-color: rgba(255,80,80,0.3); }

    .modal-breakdown {
      padding: clamp(0.7rem, 2vw, 0.9rem) clamp(1rem, 3vw, 1.6rem);
      border-bottom: 1px solid rgba(255,255,255,0.06);
      display: flex;
      flex-direction: column;
      gap: 6px;
      flex-shrink: 0;
    }
    .breakdown-row { display: flex; align-items: center; gap: 9px; font-size: 12px; color: rgba(255,255,255,0.5); }
    .breakdown-label { width: 28px; text-align: right; flex-shrink: 0; font-size: 11px; }
    .breakdown-bar-bg { flex: 1; height: 5px; background: rgba(255,255,255,0.08); border-radius: 10px; overflow: hidden; }
    .breakdown-bar-fill { height: 100%; background: linear-gradient(90deg, #f4b942, #f97316); border-radius: 10px; transition: width 0.5s ease; }
    .breakdown-count { width: 18px; text-align: left; flex-shrink: 0; font-size: 11px; }

    .modal-reviews {
      flex: 1;
      overflow-y: auto;
      padding: clamp(0.8rem, 2vw, 1rem) clamp(1rem, 3vw, 1.6rem) clamp(1rem, 2.5vw, 1.4rem);
      display: flex;
      flex-direction: column;
      gap: 1rem;
      -webkit-overflow-scrolling: touch;
    }
    .modal-reviews::-webkit-scrollbar { width: 4px; }
    .modal-reviews::-webkit-scrollbar-track { background: transparent; }
    .modal-reviews::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 10px; }

    .review-item {
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 14px;
      padding: clamp(0.8rem, 2vw, 1rem) clamp(0.9rem, 2vw, 1.1rem);
      transition: border-color 0.15s;
    }
    .review-item:hover { border-color: rgba(255,255,255,0.12); }
    .review-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.8rem;
      margin-bottom: 0.6rem;
      flex-wrap: wrap;
    }
    .reviewer-info { display: flex; align-items: center; gap: 0.65rem; }
    .reviewer-avatar {
      width: clamp(32px, 5vw, 38px);
      height: clamp(32px, 5vw, 38px);
      border-radius: 50%;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: clamp(11px, 2vw, 13px);
      font-weight: 700;
      color: #fff;
      letter-spacing: 0.03em;
    }
    .reviewer-name { font-size: clamp(13px, 2vw, 14px); font-weight: 600; color: #fff; }
    .reviewer-date { font-size: clamp(10px, 1.6vw, 11px); color: rgba(255,255,255,0.3); margin-top: 2px; }
    .review-stars-inline { display: flex; gap: 2px; }
    .review-stars-inline .rs { font-size: 13px; }
    .review-stars-inline .rs.filled { color: #f4b942; }
    .review-stars-inline .rs.empty  { color: rgba(255,255,255,0.15); }
    .review-text { font-size: clamp(12px, 2vw, 13.5px); color: rgba(255,255,255,0.62); line-height: 1.65; }

    .modal-empty { text-align: center; padding: 3rem 1rem; color: rgba(255,255,255,0.3); font-size: 14px; }
    .modal-empty-icon { font-size: 40px; margin-bottom: 0.7rem; }

    .av-0{background:#4f46e5} .av-1{background:#0ea5e9} .av-2{background:#10b981}
    .av-3{background:#f59e0b} .av-4{background:#ec4899} .av-5{background:#8b5cf6}
    .av-6{background:#ef4444} .av-7{background:#14b8a6}

    /* ═══════════════════════════════════════════
       RESPONSIVE BREAKPOINTS
    ═══════════════════════════════════════════ */

    /* Large desktops */
    @media (min-width: 1400px) {
      .cards-grid { grid-template-columns: repeat(4, 1fr); }
    }

    /* Tablet landscape */
    @media (max-width: 1100px) {
      .cards-grid { grid-template-columns: repeat(2, 1fr); }
    }

    /* Tablet portrait */
    @media (max-width: 768px) {
      .cards-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        padding: 0 0.9rem;
      }
      .list-deal-banner { padding: 0 0.9rem; }
      .list-deal-inner {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }
      .list-deal-btn { width: 100%; justify-content: center; }
      .card-footer { flex-direction: column; align-items: flex-start; gap: 0.7rem; }
      .view-btn { width: 100%; text-align: center; display: block; }
      .modal-box { max-height: 90vh; }
    }

    /* Mobile */
    @media (max-width: 520px) {
      .cards-grid {
        grid-template-columns: 1fr;
        padding: 0 0.75rem;
      }
      .card-photo { height: 200px; }
      .card-icon-fallback { height: 200px; }
      .hero-slider { height: 320px; }
      .filter-bar { gap: 0.35rem; }
      .filter-btn { padding: 7px 14px; }
      .modal-box {
        max-height: 92vh;
        border-radius: 16px;
      }
    }

    /* Very small phones */
    @media (max-width: 360px) {
      .hero-title { font-size: 1.35rem; }
      .page-heading h1 { font-size: 1.65rem; }
      .card-body { padding: 0.85rem; }
    }

    /* Touch devices — disable hover zoom to avoid sticky states */
    @media (hover: none) {
      .tour-card:hover { transform: none; }
      .tour-card:hover .card-photo img { transform: none; }
      .hero-cta:hover { transform: none; }
      .list-deal-btn:hover { transform: none; }
    }

    /* Reduce motion preference */
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { transition-duration: 0.01ms !important; animation-duration: 0.01ms !important; }
    }
  </style>
</head>
<body>
<div class="page-overlay">

  <!-- ── HERO SLIDER ── -->
  <?php if (!empty($slider_deals)): ?>
  <div class="hero-slider" id="heroSlider">
    <?php foreach ($slider_deals as $i => $slide): ?>
    <div class="hero-slide <?= $i === 0 ? 'active' : '' ?>"
         style="background-image: url('<?= htmlspecialchars($slide['image_url']) ?>')">
      <div class="hero-slide-overlay"></div>
      <div class="hero-slide-content">
        <p class="hero-label">Deals &amp; Packages</p>
        <h2 class="hero-title"><?= htmlspecialchars($slide['title']) ?></h2>
        <a href="deal-details.php?id=<?= (int)$slide['id'] ?>" class="hero-cta">View Deal</a>
      </div>
    </div>
    <?php endforeach; ?>
    <button class="hero-arrow hero-prev" onclick="slideHero(-1)" aria-label="Previous">&#8249;</button>
    <button class="hero-arrow hero-next" onclick="slideHero(1)"  aria-label="Next">&#8250;</button>
    <div class="hero-dots">
      <?php foreach ($slider_deals as $i => $_): ?>
        <button class="hero-dot <?= $i === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $i ?>)"></button>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── PAGE HEADING ── -->
  <div class="page-heading" id="deals-section">
    <h1>Deals &amp; Packages</h1>
    <p>Handpicked treks and tours across Nepal — at the best prices</p>
  </div>

  <!-- ── SEARCH BAR ── -->
  <div class="search-wrap">
    <div class="search-inner">
      <span class="search-icon">🔍</span>
      <input
        type="text"
        id="dealSearch"
        class="search-input"
        placeholder="Search deals, locations, treks..."
        oninput="applyFilters()"
        autocomplete="off"
      >
      <button class="search-clear" id="searchClearBtn" onclick="clearSearch()" aria-label="Clear search">✕</button>
    </div>
  </div>

  <!-- ── FILTER BAR ── -->
  <div class="filter-wrap">
    <div class="filter-bar">
      <button class="filter-btn active" data-filter="all">All</button>
      <?php foreach ($all_categories as $cat): ?>
        <button class="filter-btn" data-filter="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></button>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ── LIST YOUR DEAL BANNER ── -->
  <div class="list-deal-banner">
    <div class="list-deal-inner">
      <div class="list-deal-text">
        <h3>🏔️ Have a Nepal travel deal to offer?</h3>
        <p>List your trek, tour or package and reach thousands of travelers planning their Nepal adventure.</p>
      </div>
      <a href="subscription.php" class="list-deal-btn">✦ List Your Deal</a>
    </div>
  </div>

  <!-- ── CARDS GRID ── -->
  <div class="cards-grid" id="cardsGrid">

    <?php if (empty($all_deals)): ?>
      <p class="no-results">No deals found.</p>
    <?php endif; ?>

    <?php foreach ($all_deals as $deal):
      $isUserDeal = ($deal['deal_source'] ?? 'admin') === 'user_submitted';
      $dealId     = (int)$deal['id'];

      // ── Discount ──
      $discount = 0;
      if (!empty($deal['original_price']) && (float)$deal['original_price'] > 0) {
          $discount = round((((float)$deal['original_price'] - (float)$deal['price']) / (float)$deal['original_price']) * 100);
      }

      // ── Features ──
      $features = [];
      if (!empty($deal['features'])) {
          $features = array_slice(array_map('trim', explode(',', $deal['features'])), 0, 2);
      }

      // ── Image / Location ──
      $hasImage      = !empty($deal['image_url']) && strtoupper(trim($deal['image_url'])) !== 'NULL';
      $locationLabel = !empty($deal['location']) ? $deal['location'] : ($deal['season'] ?? '');

      // ── Ratings ──
      if (!$isUserDeal) {
          $avgRating   = isset($reviewData[$dealId])   ? (float)$reviewData[$dealId]['avg_rating']   : 0;
          $reviewCount = isset($reviewData[$dealId])   ? (int)$reviewData[$dealId]['reviews_count']  : 0;
          $dealReviews = $reviewDetails[$dealId] ?? [];
      } else {
          $avgRating   = isset($udReviewData[$dealId])  ? (float)$udReviewData[$dealId]['avg_rating']  : 0;
          $reviewCount = isset($udReviewData[$dealId])  ? (int)$udReviewData[$dealId]['reviews_count'] : 0;
          $dealReviews = $udReviewDetails[$dealId] ?? [];
      }

      // ── Stars HTML ──
      $starsHtml = '';
      for ($s = 1; $s <= 5; $s++) {
          $starsHtml .= $s <= round($avgRating)
              ? '<span class="star-filled">★</span>'
              : '<span class="star-empty">★</span>';
      }

      // ── JSON for reviews modal ──
      $reviewsJson   = json_encode($dealReviews,  JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
      $dealTitleJson = json_encode($deal['title'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

      // ── Expiry countdown (user deals) ──
      $daysLeft    = null;
      $expiryClass = '';
      $expiryLabel = '';
      if ($isUserDeal && !empty($deal['visible_until'])) {
          $diff     = strtotime($deal['visible_until']) - time();
          $daysLeft = max(0, (int)ceil($diff / 86400));
          if ($daysLeft <= 3) {
              $expiryClass = 'expiry-soon';
              $expiryLabel = "⏱ {$daysLeft}d left";
          } elseif ($daysLeft <= 7) {
              $expiryClass = 'expiry-warn';
              $expiryLabel = "⏱ {$daysLeft}d left";
          } else {
              $expiryClass = 'expiry-ok';
              $expiryLabel = "⏱ {$daysLeft}d left";
          }
      }

      // ── Detail link ──
      $detailLink = $isUserDeal
          ? "Ud_deal_details.php?ud={$dealId}"
          : "deal-details.php?id={$dealId}";

      // ── Search data attribute ──
      $searchData = strtolower(implode(' ', [
          $deal['title']       ?? '',
          $deal['location']    ?? '',
          $deal['category']    ?? '',
          $deal['season']      ?? '',
          $deal['features']    ?? '',
          $deal['description'] ?? '',
      ]));
    ?>

    <a href="<?= $detailLink ?>"
       class="tour-card<?= $isUserDeal ? ' partner-card' : '' ?>"
       data-category="<?= htmlspecialchars($deal['category'] ?? '') ?>"
       data-source="<?= $isUserDeal ? 'user' : 'admin' ?>"
       data-search="<?= htmlspecialchars($searchData) ?>"
       style="text-decoration:none;">

      <!-- ── PHOTO / FALLBACK ── -->
      <?php if ($hasImage): ?>
        <div class="card-photo">
          <img src="<?= htmlspecialchars($deal['image_url']) ?>" alt="<?= htmlspecialchars($deal['title']) ?>" loading="lazy">
          <span class="cat-badge"><?= htmlspecialchars($deal['category'] ?? '') ?></span>
          <?php if ($discount > 0): ?>
            <span class="discount-badge">-<?= $discount ?>%</span>
          <?php endif; ?>
          <?php if ($isUserDeal): ?>
            <span class="partner-badge">Partner Listing</span>
            <?php if ($expiryLabel): ?>
              <span class="expiry-badge <?= $expiryClass ?>"><?= $expiryLabel ?></span>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="card-icon-fallback">
          <?= !empty($deal['emoji']) ? $deal['emoji'] : '🏔️' ?>
          <span class="cat-badge"><?= htmlspecialchars($deal['category'] ?? '') ?></span>
          <?php if ($discount > 0): ?>
            <span class="discount-badge">-<?= $discount ?>%</span>
          <?php endif; ?>
          <?php if ($isUserDeal): ?>
            <span class="partner-badge">Partner Listing</span>
            <?php if ($expiryLabel): ?>
              <span class="expiry-badge <?= $expiryClass ?>"><?= $expiryLabel ?></span>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- ── CARD BODY ── -->
      <div class="card-body">

        <?php if (!empty($locationLabel)): ?>
          <div class="location-label"><?= htmlspecialchars($locationLabel) ?></div>
        <?php endif; ?>

        <div class="card-title"><?= htmlspecialchars($deal['title']) ?></div>

        <div class="meta-row">
          <?php if (!empty($deal['days'])): ?>
            <span class="meta-item">📅 <?= (int)$deal['days'] ?> day<?= (int)$deal['days'] > 1 ? 's' : '' ?></span>
          <?php endif; ?>
          <?php if (!empty($deal['season'])): ?>
            <span class="meta-item">🗓 <?= htmlspecialchars($deal['season']) ?></span>
          <?php endif; ?>
        </div>

        <!-- ★ RATING ROW ★ -->
        <?php if ($avgRating > 0): ?>
          <div class="rating-row has-reviews"
               onclick="event.preventDefault(); openReviews(<?= $dealId ?>, <?= $reviewsJson ?>, <?= $dealTitleJson ?>)"
               title="See all reviews">
            <?= $starsHtml ?>
            <span class="rating-value"><?= number_format($avgRating, 1) ?></span>
            <span class="rating-count">(<?= $reviewCount ?> review<?= $reviewCount !== 1 ? 's' : '' ?> ↗)</span>
          </div>
        <?php else: ?>
          <div class="rating-row">
            <?= str_repeat('<span class="star-empty">★</span>', 5) ?>
            <span class="no-reviews">No reviews yet</span>
          </div>
        <?php endif; ?>

        <!-- ── PARTNER BYLINE ── -->
        <?php if ($isUserDeal && !empty($deal['submitted_by'])): ?>
          <div style="font-size:12px;color:rgba(255,255,255,0.3)">
            🏢 Listed by <?= htmlspecialchars($deal['submitted_by']) ?>
          </div>
        <?php endif; ?>

        <!-- ── FEATURE TAGS ── -->
        <?php if (!empty($features)): ?>
          <div class="tags">
            <?php foreach ($features as $f): ?>
              <span class="tag"><?= htmlspecialchars($f) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- ── CARD FOOTER ── -->
        <div class="card-footer">
          <div class="price-block">
            <div class="price-from">From</div>
            <?php if (!empty($deal['original_price']) && (float)$deal['original_price'] > (float)$deal['price']): ?>
              <div class="price-original">NPR <?= number_format((float)$deal['original_price']) ?></div>
            <?php endif; ?>
            <div class="price-main">NPR <?= number_format((float)$deal['price']) ?></div>
            <div class="price-sub">per person</div>
          </div>
          <span class="view-btn">View Details</span>
        </div>

      </div><!-- /card-body -->
    </a>

    <?php endforeach; ?>
  </div><!-- /cards-grid -->

</div><!-- /page-overlay -->

<!-- ── REVIEWS MODAL ── -->
<div class="modal-backdrop" id="reviewsModal" onclick="closeIfBackdrop(event)">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-header-left">
        <div class="modal-deal-title" id="modalDealTitle">Reviews</div>
        <div class="modal-summary">
          <div class="modal-avg-stars" id="modalAvgStars"></div>
          <div class="modal-avg-num"   id="modalAvgNum"></div>
          <div class="modal-avg-label" id="modalAvgLabel"></div>
        </div>
      </div>
      <button class="modal-close" onclick="closeModal()" aria-label="Close">✕</button>
    </div>
    <div class="modal-breakdown" id="modalBreakdown"></div>
    <div class="modal-reviews"   id="modalReviewsList"></div>
  </div>
</div>

<script>
  /* ════════════════════════════════════
     SEARCH + FILTER
  ════════════════════════════════════ */
  let activeFilter = 'all';

  function applyFilters() {
    const query    = (document.getElementById('dealSearch').value || '').toLowerCase().trim();
    const clearBtn = document.getElementById('searchClearBtn');
    clearBtn.classList.toggle('visible', query.length > 0);

    let anyVisible = false;
    document.querySelectorAll('.tour-card').forEach(card => {
      const matchesFilter = activeFilter === 'all' || card.dataset.category === activeFilter;
      const searchText    = card.dataset.search || '';
      const matchesSearch = !query || searchText.includes(query);
      const show          = matchesFilter && matchesSearch;
      card.classList.toggle('hidden', !show);
      if (show) anyVisible = true;
    });

    let noRes = document.getElementById('noResultsMsg');
    if (!anyVisible) {
      if (!noRes) {
        noRes = document.createElement('p');
        noRes.id        = 'noResultsMsg';
        noRes.className = 'no-results';
        document.getElementById('cardsGrid').appendChild(noRes);
      }
      noRes.textContent = query
        ? `No deals found for "${query}".`
        : 'No deals in this category.';
    } else if (noRes) {
      noRes.remove();
    }
  }

  function clearSearch() {
    document.getElementById('dealSearch').value = '';
    applyFilters();
    document.getElementById('dealSearch').focus();
  }

  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      activeFilter = btn.dataset.filter;
      applyFilters();
    });
  });

  /* ════════════════════════════════════
     HERO SLIDER
  ════════════════════════════════════ */
  const slides  = document.querySelectorAll('.hero-slide');
  const dots    = document.querySelectorAll('.hero-dot');
  let current   = 0, autoPlay = null;

  function goToSlide(i) {
    if (!slides.length) return;
    slides[current].classList.remove('active'); dots[current].classList.remove('active');
    current = (i + slides.length) % slides.length;
    slides[current].classList.add('active');    dots[current].classList.add('active');
  }
  function slideHero(d) { resetAuto(); goToSlide(current + d); }
  function startAuto()  { autoPlay = setInterval(() => goToSlide(current + 1), 5000); }
  function resetAuto()  { clearInterval(autoPlay); startAuto(); }
  if (slides.length > 1) startAuto();

  const sliderEl = document.getElementById('heroSlider');
  if (sliderEl) {
    sliderEl.addEventListener('mouseenter', () => clearInterval(autoPlay));
    sliderEl.addEventListener('mouseleave', startAuto);

    // Touch/swipe support for slider
    let touchStartX = 0;
    sliderEl.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
    sliderEl.addEventListener('touchend', e => {
      const diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 40) { resetAuto(); goToSlide(current + (diff > 0 ? 1 : -1)); }
    });
  }

  /* ════════════════════════════════════
     REVIEWS MODAL
  ════════════════════════════════════ */
  const AV_COLORS = ['av-0','av-1','av-2','av-3','av-4','av-5','av-6','av-7'];

  function esc(s) {
    return String(s || '')
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  function buildStars(rating, sz, fc, ec) {
    let h = '';
    for (let s = 1; s <= 5; s++)
      h += `<span class="${s <= Math.round(rating) ? fc : ec}" style="font-size:${sz}px">★</span>`;
    return h;
  }

  function openReviews(dealId, reviews, dealTitle) {
    document.getElementById('modalDealTitle').textContent = dealTitle;

    if (!reviews || reviews.length === 0) {
      document.getElementById('modalAvgStars').innerHTML   = buildStars(0, 17, 'star-filled', 'star-empty');
      document.getElementById('modalAvgNum').textContent   = '';
      document.getElementById('modalAvgLabel').textContent = 'No reviews yet';
      document.getElementById('modalBreakdown').innerHTML  = '';
      document.getElementById('modalReviewsList').innerHTML = `
        <div class="modal-empty">
          <div class="modal-empty-icon">🏔️</div>
          <div>Be the first to review this deal!</div>
        </div>`;
      openModalUI(); return;
    }

    const avg = reviews.reduce((s, r) => s + parseFloat(r.rating), 0) / reviews.length;
    document.getElementById('modalAvgStars').innerHTML   = buildStars(avg, 17, 'star-filled', 'star-empty');
    document.getElementById('modalAvgNum').textContent   = avg.toFixed(1);
    document.getElementById('modalAvgLabel').textContent =
      `out of 5 · ${reviews.length} review${reviews.length !== 1 ? 's' : ''}`;

    const counts = {5:0,4:0,3:0,2:0,1:0};
    reviews.forEach(r => {
      const v = Math.min(5, Math.max(1, Math.round(parseFloat(r.rating))));
      counts[v]++;
    });

    let bHtml = '';
    for (let star = 5; star >= 1; star--) {
      const pct = Math.round((counts[star] / reviews.length) * 100);
      bHtml += `<div class="breakdown-row">
        <span class="breakdown-label">${star}★</span>
        <div class="breakdown-bar-bg">
          <div class="breakdown-bar-fill" style="width:${pct}%"></div>
        </div>
        <span class="breakdown-count">${counts[star]}</span>
      </div>`;
    }
    document.getElementById('modalBreakdown').innerHTML = bHtml;

    let rHtml = '';
    reviews.forEach((r, idx) => {
      const name     = r.reviewer_name || 'Anonymous';
      const initials = name.split(' ').map(w => w[0] || '').join('').toUpperCase().slice(0, 2) || '?';
      const avClass  = AV_COLORS[idx % AV_COLORS.length];
      const date     = r.created_at
        ? new Date(r.created_at).toLocaleDateString('en-US', {year:'numeric', month:'short', day:'numeric'})
        : '';
      const text = (r.review_text || '').trim();

      rHtml += `<div class="review-item">
        <div class="review-top">
          <div class="reviewer-info">
            <div class="reviewer-avatar ${avClass}">${esc(initials)}</div>
            <div>
              <div class="reviewer-name">${esc(name)}</div>
              ${date ? `<div class="reviewer-date">${esc(date)}</div>` : ''}
            </div>
          </div>
          <div class="review-stars-inline">${buildStars(r.rating, 13, 'rs filled', 'rs empty')}</div>
        </div>
        ${text
          ? `<div class="review-text">${esc(text)}</div>`
          : `<div class="review-text" style="font-style:italic;opacity:.35">No written review.</div>`}
      </div>`;
    });

    document.getElementById('modalReviewsList').innerHTML = rHtml;
    openModalUI();
  }

  function openModalUI() {
    document.getElementById('reviewsModal').classList.add('open');
    document.body.style.overflow = 'hidden';
  }
  function closeModal() {
    document.getElementById('reviewsModal').classList.remove('open');
    document.body.style.overflow = '';
  }
  function closeIfBackdrop(e) {
    if (e.target === document.getElementById('reviewsModal')) closeModal();
  }
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>