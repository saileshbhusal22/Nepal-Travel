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

// ── MERGE ──────────────────────────────────────────────────────
$all_deals = array_merge($admin_deals, $user_deals);

// ── FETCH RATINGS: admin deals ─────────────────────────────────
$reviewData    = [];
$reviewDetails = [];

$tableCheck = $conn->query("SHOW TABLES LIKE 'deal_reviews'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $rr = $conn->query("
        SELECT deal_id,
               ROUND(AVG(rating), 1) AS avg_rating,
               COUNT(*)              AS reviews_count
        FROM deal_reviews GROUP BY deal_id
    ");
    if ($rr) while ($row = $rr->fetch_assoc()) $reviewData[$row['deal_id']] = $row;

    $rd = $conn->query("
        SELECT r.deal_id, r.id AS review_id, r.rating, r.review_text, r.created_at,
               u.full_name AS reviewer_name
        FROM deal_reviews r LEFT JOIN users u ON u.id = r.user_id
        ORDER BY r.created_at DESC
    ");
    if (!$rd) $rd = $conn->query("
        SELECT deal_id, id AS review_id, rating, review_text, created_at, NULL AS reviewer_name
        FROM deal_reviews ORDER BY created_at DESC
    ");
    if ($rd) while ($row = $rd->fetch_assoc()) $reviewDetails[$row['deal_id']][] = $row;
}

// ── FETCH RATINGS: user deals ──────────────────────────────────
$udReviewData    = [];
$udReviewDetails = [];

$udTableCheck = $conn->query("SHOW TABLES LIKE 'user_deal_reviews'");
if ($udTableCheck && $udTableCheck->num_rows > 0) {
    $urr = $conn->query("
        SELECT ud_id, ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS reviews_count
        FROM user_deal_reviews GROUP BY ud_id
    ");
    if ($urr) while ($row = $urr->fetch_assoc()) $udReviewData[$row['ud_id']] = $row;

    $urd = $conn->query("
        SELECT r.ud_id, r.id AS review_id, r.rating, r.review_text, r.created_at,
               u.full_name AS reviewer_name
        FROM user_deal_reviews r LEFT JOIN users u ON u.id = r.user_id
        ORDER BY r.created_at DESC
    ");
    if ($urd) while ($row = $urd->fetch_assoc()) $udReviewDetails[$row['ud_id']][] = $row;
}

// ── SLIDER: top 5 admin deals with image ──────────────────────
$slider_deals = array_filter($admin_deals, fn($d) =>
    !empty($d['image_url']) && strtoupper(trim($d['image_url'])) !== 'NULL'
);
$slider_deals = array_slice(array_values($slider_deals), 0, 5);

// ── UNIQUE CATEGORIES ─────────────────────────────────────────
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
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --gold:    #C9A227;
      --gold-lt: #E8C44A;
      --gold-dk: #A8841A;
      --blue:    #2563eb;
      --bg-card: #12151f;
      --bg-modal:#13161f;
      --border:  rgba(255,255,255,0.08);
      --text:    #F0EEE8;
      --muted:   rgba(240,238,232,0.45);
      --ff-h:    'Playfair Display', serif;
      --ff-b:    'DM Sans', sans-serif;
      --ff-m:    'DM Mono', monospace;
      --radius:  14px;
      --transition: 0.2s ease;
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
    @supports (-webkit-touch-callout: none) { body { background-attachment: scroll; } }

    .page-overlay {
      min-height: 100vh;
      background: rgba(8,10,20,0.22);
      padding-bottom: 5rem;
    }

    /* ── HERO SLIDER ── */
    .hero-slider {
      position: relative;
      width: 100%;
      height: clamp(320px, 52vw, 780px);
      overflow: hidden;
      margin-bottom: clamp(1.8rem, 3.5vw, 3rem);
    }
    .hero-slide {
      position: absolute; inset: 0;
      background-size: cover; background-position: center;
      opacity: 0; transition: opacity 0.9s ease;
      display: flex; align-items: center; justify-content: center;
    }
    .hero-slide.active { opacity: 1; z-index: 1; }
    .hero-slide-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(to bottom, rgba(0,0,0,0.06) 0%, rgba(0,0,0,0.48) 55%, rgba(0,0,0,0.70) 100%);
    }
    .hero-slide-content {
      position: relative; z-index: 2;
      text-align: center;
      padding: 0 clamp(1.2rem,5vw,3rem);
      max-width: 800px; width: 100%;
    }
    .hero-label {
      font-size: clamp(9px,1.4vw,11px); font-weight: 700;
      letter-spacing: 0.22em; text-transform: uppercase;
      color: rgba(255,255,255,0.55); margin-bottom: 0.6rem;
      font-family: var(--ff-m);
    }
    .hero-title {
      font-family: var(--ff-h);
      font-size: clamp(1.45rem,4.5vw,3.2rem);
      font-weight: 700; color: #fff; line-height: 1.13;
      margin-bottom: clamp(0.9rem,2vw,1.6rem);
      text-shadow: 0 2px 24px rgba(0,0,0,0.5);
    }
    .hero-cta {
      display: inline-block; background: var(--gold); color: #fff;
      font-size: clamp(10px,1.6vw,12px); font-weight: 700;
      letter-spacing: 0.17em; text-transform: uppercase;
      padding: clamp(10px,1.8vw,13px) clamp(22px,3.5vw,36px);
      border-radius: 4px; text-decoration: none;
      transition: background var(--transition), transform 0.15s;
    }
    .hero-cta:hover { background: var(--gold-dk); transform: scale(1.03); }
    .hero-arrow {
      position: absolute; top: 50%; transform: translateY(-50%); z-index: 3;
      background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.20);
      color: #fff; font-size: clamp(20px,3vw,26px);
      width: clamp(34px,4.5vw,44px); height: clamp(34px,4.5vw,44px);
      border-radius: 50%; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: background var(--transition); line-height: 1;
    }
    .hero-arrow:hover { background: rgba(255,255,255,0.24); }
    .hero-prev { left: clamp(10px,2vw,20px); }
    .hero-next { right: clamp(10px,2vw,20px); }
    .hero-dots {
      position: absolute; bottom: clamp(12px,2vw,16px);
      left: 50%; transform: translateX(-50%);
      z-index: 3; display: flex; gap: 7px;
    }
    .hero-dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: rgba(255,255,255,0.32); cursor: pointer;
      transition: background 0.2s, transform 0.2s; border: none;
    }
    .hero-dot.active { background: var(--gold); transform: scale(1.3); }

    /* ── PAGE HEADING ── */
    .page-heading {
      text-align: center;
      margin-bottom: clamp(1rem,2.5vw,1.6rem);
      padding: 0 clamp(1rem,4vw,2rem);
    }
    .page-heading h1 {
      font-family: var(--ff-h);
      font-size: clamp(1.75rem,4.5vw,2.6rem);
      font-weight: 700; color: #fff; letter-spacing: -0.01em; line-height: 1.1;
    }
    .page-heading p {
      color: rgba(255,255,255,0.44);
      font-size: clamp(0.82rem,1.8vw,0.92rem); margin-top: 0.45rem;
    }

    /* ── SEARCH ── */
    .search-wrap {
      display: flex; justify-content: center;
      margin-bottom: clamp(0.9rem,2vw,1.3rem);
      padding: 0 clamp(1rem,4vw,1.5rem);
    }
    .search-inner { position: relative; width: 100%; max-width: 500px; }
    .search-icon {
      position: absolute; left: 15px; top: 50%;
      transform: translateY(-50%); font-size: 14px; pointer-events: none; line-height: 1;
    }
    .search-input {
      width: 100%; padding: 12px 40px 12px 42px;
      background: rgba(255,255,255,0.09); border: 1px solid rgba(255,255,255,0.14);
      border-radius: 28px; color: #fff;
      font-family: var(--ff-b); font-size: clamp(12px,2vw,14px); outline: none;
      transition: background var(--transition), border-color var(--transition);
    }
    .search-input::placeholder { color: rgba(255,255,255,0.33); }
    .search-input:focus { background: rgba(255,255,255,0.12); border-color: rgba(100,150,255,0.46); }
    .search-clear {
      display: none; position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: rgba(255,255,255,0.11); border: none; color: rgba(255,255,255,0.55);
      width: 21px; height: 21px; border-radius: 50%; cursor: pointer; font-size: 10px;
      align-items: center; justify-content: center; transition: background 0.15s;
    }
    .search-clear:hover { background: rgba(255,255,255,0.2); color: #fff; }
    .search-clear.visible { display: flex; }

    /* ── FILTER BAR ── */
    .filter-wrap {
      overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none;
      margin-bottom: clamp(1.5rem,3.5vw,2.5rem);
      padding: 0 clamp(1rem,4vw,1.5rem);
    }
    .filter-wrap::-webkit-scrollbar { display: none; }
    .filter-bar { display: flex; gap: 0.4rem; width: max-content; margin: 0 auto; }
    .filter-btn {
      background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.62);
      border: 1px solid rgba(255,255,255,0.11);
      padding: clamp(6px,1.3vw,8px) clamp(14px,2.5vw,22px);
      border-radius: 28px; font-family: var(--ff-b);
      font-size: clamp(11px,1.7vw,13px); font-weight: 500;
      cursor: pointer; white-space: nowrap;
      transition: background var(--transition), color var(--transition), border-color var(--transition);
    }
    .filter-btn:hover, .filter-btn.active {
      background: var(--blue); color: #fff; border-color: var(--blue);
    }

    /* ── LIST YOUR DEAL BANNER ── */
    .list-deal-banner {
      max-width: 1320px; margin: 0 auto clamp(1.5rem,3.5vw,2.5rem);
      padding: 0 clamp(1rem,3vw,2.5rem);
    }
    .list-deal-inner {
      background: linear-gradient(135deg, rgba(201,162,39,0.11) 0%, rgba(37,99,235,0.09) 100%);
      border: 1px solid rgba(201,162,39,0.23);
      border-radius: var(--radius);
      padding: clamp(1rem,2.5vw,1.5rem) clamp(1.2rem,3.5vw,2rem);
      display: flex; align-items: center; justify-content: space-between;
      gap: clamp(0.8rem,2.5vw,1.2rem); flex-wrap: wrap;
    }
    .list-deal-text h3 {
      font-family: var(--ff-h); font-size: clamp(0.95rem,2.2vw,1.18rem);
      font-weight: 700; color: #fff; margin-bottom: 3px;
    }
    .list-deal-text p { font-size: clamp(11px,1.6vw,12px); color: rgba(255,255,255,0.44); }
    .list-deal-btn {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--gold); color: #000;
      font-size: clamp(11px,1.6vw,13px); font-weight: 800;
      padding: clamp(9px,1.8vw,12px) clamp(18px,3vw,26px);
      border-radius: 9px; text-decoration: none; white-space: nowrap;
      letter-spacing: 0.04em; flex-shrink: 0;
      transition: background var(--transition), transform 0.15s;
    }
    .list-deal-btn:hover { background: var(--gold-lt); transform: scale(1.02); }

    /* ════════════════════════════════════
       COMPACT DEAL CARDS
    ════════════════════════════════════ */
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
      gap: clamp(1rem,2.5vw,1.8rem);
      max-width: 1360px; margin: 0 auto;
      padding: 0 clamp(1rem,3vw,2.5rem);
    }

    /* The card itself — matches user's request */
    .tour-card {
      background: #ffffff;
      border-radius: 20px;
      border: 1px solid rgba(0, 0, 0, 0.05);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: transform var(--transition), border-color var(--transition), box-shadow var(--transition);
      cursor: pointer;
      text-decoration: none;
      color: inherit;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }
    .tour-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
      border-color: rgba(37, 99, 235, 0.15);
    }
    .tour-card.partner-card {
      border: 1px solid rgba(201, 162, 39, 0.18);
    }
    .tour-card.partner-card:hover {
      border-color: rgba(201, 162, 39, 0.4);
      box-shadow: 0 20px 40px rgba(201, 162, 39, 0.1);
    }

    /* ── Image ── */
    .card-photo {
      position: relative;
      overflow: hidden;
      height: 280px;
      flex-shrink: 0;
    }
    .card-photo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.38s ease;
    }
    .tour-card:hover .card-photo img {
      transform: scale(1.04);
    }

    .card-icon-fallback {
      height: 280px;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 64px;
      background: #f3f4f6;
      position: relative;
    }

    /* ── Badges ── */
    .cat-badge {
      position: absolute;
      top: 16px;
      left: 16px;
      background: #2563eb !important;
      color: #ffffff !important;
      font-size: 11px;
      font-weight: 700;
      padding: 6px 14px;
      border-radius: 8px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      z-index: 2;
    }
    .duration-badge {
      position: absolute;
      top: 16px;
      right: 16px;
      background: #ffffff;
      color: #1f2937;
      font-size: 11px;
      font-weight: 700;
      padding: 6px 14px;
      border-radius: 8px;
      z-index: 2;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .discount-badge {
      position: absolute;
      bottom: 16px;
      right: 16px;
      background: #db2777 !important;
      color: #ffffff !important;
      font-size: 11px;
      font-weight: 800;
      padding: 6px 12px;
      border-radius: 6px;
      text-transform: uppercase;
      z-index: 2;
    }
    .partner-badge {
      position: absolute;
      bottom: 16px;
      left: 16px;
      z-index: 2;
      background: rgba(201, 162, 39, 0.95);
      color: #000000;
      font-size: 9px;
      font-weight: 800;
      letter-spacing: 1px;
      padding: 4px 10px;
      border-radius: 18px;
      text-transform: uppercase;
    }
    .expiry-badge {
      position: absolute;
      bottom: 45px;
      right: 16px;
      z-index: 2;
      font-size: 10px;
      font-weight: 700;
      padding: 4px 10px;
      border-radius: 6px;
      font-family: var(--ff-m);
    }
    .expiry-soon { background: rgba(232, 67, 147, 0.95); color: #fff; }
    .expiry-ok   { background: rgba(76, 175, 125, 0.95); color: #fff; }
    .expiry-warn { background: rgba(240, 160, 48, 0.95); color: #000; }

    /* ── Card body ── */
    .card-body {
      padding: 24px;
      flex: 1;
      display: flex;
      flex-direction: column;
      background: #ffffff;
    }

    /* Yellow location label */
    .card-location-lbl {
      font-size: 12px;
      font-weight: 800;
      color: #ca8a04;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    /* Title */
    .card-title-new {
      font-size: 20px;
      font-weight: 800;
      color: #111827;
      line-height: 1.3;
      margin-top: 8px;
      margin-bottom: 8px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      height: 52px; /* fixed height for alignment */
    }

    /* Rating row */
    .rating-row-new {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 4px;
    }
    .star-filled {
      color: #f59e0b;
      font-size: 14px;
    }
    .star-empty {
      color: #e5e7eb;
      font-size: 14px;
    }
    .rating-value-new {
      color: #374151;
      font-weight: 700;
      font-size: 13px;
    }
    .rating-count-new {
      color: #9ca3af;
      font-size: 12px;
    }
    .no-reviews-new {
      color: #9ca3af;
      font-size: 13px;
      font-style: italic;
    }

    /* Divider */
    .card-divider {
      border: 0;
      border-top: 1px solid #f3f4f6;
      margin: 16px 0;
    }

    /* Bottom row */
    .card-bottom-new {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: auto;
    }
    .card-price-col-new {
      display: flex;
      flex-direction: column;
    }
    .price-original-new {
      font-size: 13px;
      color: #9ca3af;
      text-decoration: line-through;
      line-height: 1.2;
      margin-bottom: 2px;
    }
    .price-main-new {
      font-size: 20px;
      font-weight: 800;
      color: #1d4ed8;
      line-height: 1.1;
    }

    .view-btn-new {
      background: #2563eb;
      color: #ffffff;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border: none;
      border-radius: 9999px;
      padding: 10px 20px;
      white-space: nowrap;
      transition: background 0.2s;
    }
    .tour-card:hover .view-btn-new {
      background: #1d4ed8;
    }

    .tour-card.hidden { display: none; }
    .no-results {
      text-align: center; color: rgba(255,255,255,0.38);
      font-size: 1rem; padding: 3rem 0; grid-column: 1 / -1;
    }

    /* ── REVIEWS MODAL ── */
    .modal-backdrop {
      display: none; position: fixed; inset: 0; z-index: 9999;
      background: rgba(0,0,0,0.74); backdrop-filter: blur(5px);
      align-items: center; justify-content: center;
      padding: clamp(0.8rem,3vw,1.5rem); overflow-y: auto;
    }
    .modal-backdrop.open { display: flex; }
    .modal-box {
      background: var(--bg-modal); border: 1px solid rgba(255,255,255,0.09);
      border-radius: clamp(14px,3vw,20px); width: 100%; max-width: 540px;
      max-height: min(88vh,680px); display: flex; flex-direction: column;
      box-shadow: 0 28px 72px rgba(0,0,0,0.68); animation: modalIn 0.22s ease; margin: auto;
    }
    @keyframes modalIn {
      from { opacity: 0; transform: translateY(16px) scale(0.97); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .modal-header {
      padding: clamp(1rem,2.5vw,1.3rem) clamp(1rem,3vw,1.5rem) clamp(0.8rem,2vw,1rem);
      border-bottom: 1px solid rgba(255,255,255,0.07);
      display: flex; align-items: flex-start; justify-content: space-between;
      gap: 1rem; flex-shrink: 0;
    }
    .modal-header-left { flex: 1; min-width: 0; }
    .modal-deal-title {
      font-family: var(--ff-h); font-size: clamp(14px,2.5vw,16px); font-weight: 600;
      color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 7px;
    }
    .modal-summary { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .modal-avg-stars { display: flex; gap: 2px; }
    .modal-avg-num { font-size: clamp(19px,3.5vw,23px); font-weight: 700; color: #fff; line-height: 1; }
    .modal-avg-label { font-size: clamp(11px,1.7vw,12px); color: rgba(255,255,255,0.38); align-self: flex-end; margin-bottom: 2px; }
    .modal-close {
      background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
      color: rgba(255,255,255,0.58); font-size: 16px; line-height: 1;
      min-width: 32px; width: 32px; height: 32px; border-radius: 50%; cursor: pointer;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
      transition: background 0.15s, color 0.15s; touch-action: manipulation;
    }
    .modal-close:hover { background: rgba(255,80,80,0.16); color: #ff6b6b; border-color: rgba(255,80,80,0.28); }
    .modal-breakdown {
      padding: clamp(0.6rem,1.8vw,0.85rem) clamp(1rem,3vw,1.5rem);
      border-bottom: 1px solid rgba(255,255,255,0.06);
      display: flex; flex-direction: column; gap: 5px; flex-shrink: 0;
    }
    .breakdown-row { display: flex; align-items: center; gap: 8px; font-size: 12px; color: rgba(255,255,255,0.46); }
    .breakdown-label { width: 26px; text-align: right; flex-shrink: 0; font-size: 11px; }
    .breakdown-bar-bg { flex: 1; height: 4px; background: rgba(255,255,255,0.07); border-radius: 10px; overflow: hidden; }
    .breakdown-bar-fill { height: 100%; background: linear-gradient(90deg,#f4b942,#f97316); border-radius: 10px; transition: width 0.5s ease; }
    .breakdown-count { width: 16px; text-align: left; flex-shrink: 0; font-size: 11px; }
    .modal-reviews {
      flex: 1; overflow-y: auto;
      padding: clamp(0.8rem,2vw,1rem) clamp(1rem,3vw,1.5rem) clamp(1rem,2.5vw,1.3rem);
      display: flex; flex-direction: column; gap: 0.9rem;
      -webkit-overflow-scrolling: touch;
    }
    .modal-reviews::-webkit-scrollbar { width: 4px; }
    .modal-reviews::-webkit-scrollbar-track { background: transparent; }
    .modal-reviews::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.11); border-radius: 10px; }
    .review-item {
      background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);
      border-radius: 13px; padding: clamp(0.75rem,1.8vw,0.95rem) clamp(0.85rem,2vw,1rem);
      transition: border-color 0.15s;
    }
    .review-item:hover { border-color: rgba(255,255,255,0.11); }
    .review-top {
      display: flex; align-items: center; justify-content: space-between;
      gap: 0.8rem; margin-bottom: 0.55rem; flex-wrap: wrap;
    }
    .reviewer-info { display: flex; align-items: center; gap: 0.6rem; }
    .reviewer-avatar {
      width: clamp(30px,4.5vw,36px); height: clamp(30px,4.5vw,36px);
      border-radius: 50%; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: clamp(11px,1.8vw,12px); font-weight: 700; color: #fff;
    }
    .reviewer-name { font-size: clamp(12px,2vw,13px); font-weight: 600; color: #fff; }
    .reviewer-date { font-size: clamp(10px,1.5vw,11px); color: rgba(255,255,255,0.28); margin-top: 1px; }
    .review-stars-inline { display: flex; gap: 2px; }
    .review-stars-inline .rs { font-size: 12px; }
    .review-stars-inline .rs.filled { color: #f4b942; }
    .review-stars-inline .rs.empty  { color: rgba(255,255,255,0.14); }
    .review-text { font-size: clamp(12px,1.8vw,13px); color: rgba(255,255,255,0.58); line-height: 1.6; }
    .modal-empty { text-align: center; padding: 2.5rem 1rem; color: rgba(255,255,255,0.28); font-size: 13px; }
    .modal-empty-icon { font-size: 36px; margin-bottom: 0.65rem; }
    .av-0{background:#4f46e5} .av-1{background:#0ea5e9} .av-2{background:#10b981}
    .av-3{background:#f59e0b} .av-4{background:#ec4899} .av-5{background:#8b5cf6}
    .av-6{background:#ef4444} .av-7{background:#14b8a6}

    /* ── RESPONSIVE ── */
    @media (min-width:1400px) { .cards-grid { grid-template-columns: repeat(4,1fr); } }
    @media (max-width:1100px) { .cards-grid { grid-template-columns: repeat(3,1fr); } }
    @media (max-width:860px)  { .cards-grid { grid-template-columns: repeat(2,1fr); gap: 0.9rem; padding: 0 0.9rem; } }
    @media (max-width:520px) {
      .cards-grid { grid-template-columns: 1fr; padding: 0 0.75rem; }
      .card-photo, .card-icon-fallback { height: 148px; }
      .hero-slider { height: 300px; }
      .list-deal-inner { flex-direction: column; align-items: flex-start; gap: 0.9rem; }
      .list-deal-btn { width: 100%; justify-content: center; }
      .modal-box { max-height: 92vh; border-radius: 16px; }
    }
    @media (max-width:360px) {
      .hero-title { font-size: 1.3rem; }
      .page-heading h1 { font-size: 1.6rem; }
      .card-body { padding: 10px 12px 11px; }
    }
    @media (hover:none) {
      .tour-card:hover { transform: none; }
      .tour-card:hover .card-photo img { transform: none; }
    }
    @media (prefers-reduced-motion:reduce) {
      *, *::before, *::after { transition-duration:0.01ms !important; animation-duration:0.01ms !important; }
    }
  </style>
</head>
<body>
<div class="page-overlay">

  <!-- ── HERO SLIDER ── -->
  <?php if (!empty($slider_deals)): ?>
  <div class="hero-slider" id="heroSlider">
    <?php foreach ($slider_deals as $i => $slide): ?>
    <div class="hero-slide <?= $i===0?'active':'' ?>"
         style="background-image:url('<?= htmlspecialchars($slide['image_url']) ?>')">
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
        <button class="hero-dot <?= $i===0?'active':'' ?>" onclick="goToSlide(<?= $i ?>)"></button>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── PAGE HEADING ── -->
  <div class="page-heading" id="deals-section">
    <h1>Deals &amp; Packages</h1>
    <p>Handpicked treks and tours across Nepal — at the best prices</p>
  </div>

  <!-- ── SEARCH ── -->
  <div class="search-wrap">
    <div class="search-inner">
      <span class="search-icon">🔍</span>
      <input type="text" id="dealSearch" class="search-input"
             placeholder="Search deals, locations, treks..."
             oninput="applyFilters()" autocomplete="off">
      <button class="search-clear" id="searchClearBtn" onclick="clearSearch()" aria-label="Clear">✕</button>
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

      $discount = 0;
      if (!empty($deal['original_price']) && (float)$deal['original_price'] > 0)
          $discount = round((((float)$deal['original_price'] - (float)$deal['price']) / (float)$deal['original_price']) * 100);

      $features = [];
      if (!empty($deal['features']))
          $features = array_slice(array_map('trim', explode(',', $deal['features'])), 0, 2);

      $hasImage      = !empty($deal['image_url']) && strtoupper(trim($deal['image_url'])) !== 'NULL';
      $locationLabel = !empty($deal['location']) ? $deal['location'] : ($deal['season'] ?? '');

      if (!$isUserDeal) {
          $avgRating   = isset($reviewData[$dealId])   ? (float)$reviewData[$dealId]['avg_rating']   : 0;
          $reviewCount = isset($reviewData[$dealId])   ? (int)$reviewData[$dealId]['reviews_count']  : 0;
          $dealReviews = $reviewDetails[$dealId] ?? [];
      } else {
          $avgRating   = isset($udReviewData[$dealId])  ? (float)$udReviewData[$dealId]['avg_rating']  : 0;
          $reviewCount = isset($udReviewData[$dealId])  ? (int)$udReviewData[$dealId]['reviews_count'] : 0;
          $dealReviews = $udReviewDetails[$dealId] ?? [];
      }

      $starsHtml = '';
      for ($s=1;$s<=5;$s++)
          $starsHtml .= $s<=round($avgRating)
              ? '<span class="star-filled">★</span>'
              : '<span class="star-empty">★</span>';

      $reviewsJson   = json_encode($dealReviews, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
      $dealTitleJson = json_encode($deal['title'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);

      $daysLeft=null; $expiryClass=''; $expiryLabel='';
      if ($isUserDeal && !empty($deal['visible_until'])) {
          $diff = strtotime($deal['visible_until']) - time();
          $daysLeft = max(0,(int)ceil($diff/86400));
          if ($daysLeft<=3)      { $expiryClass='expiry-soon'; $expiryLabel="⏱ {$daysLeft}d left"; }
          elseif ($daysLeft<=7)  { $expiryClass='expiry-warn'; $expiryLabel="⏱ {$daysLeft}d left"; }
          else                   { $expiryClass='expiry-ok';   $expiryLabel="⏱ {$daysLeft}d left"; }
      }

      $detailLink = $isUserDeal ? "Ud_deal_details.php?ud={$dealId}" : "deal-details.php?id={$dealId}";
      $searchData = strtolower(implode(' ', [
          $deal['title']??'', $deal['location']??'', $deal['category']??'',
          $deal['season']??'', $deal['features']??'', $deal['description']??'',
      ]));
    ?>

    <a href="<?= $detailLink ?>"
       class="tour-card<?= $isUserDeal?' partner-card':'' ?>"
       data-category="<?= htmlspecialchars($deal['category']??'') ?>"
       data-source="<?= $isUserDeal?'user':'admin' ?>"
       data-search="<?= htmlspecialchars($searchData) ?>"
       style="text-decoration:none;">

      <!-- IMAGE / FALLBACK -->
      <?php if ($hasImage): ?>
        <div class="card-photo">
          <img src="<?= htmlspecialchars($deal['image_url']) ?>" alt="<?= htmlspecialchars($deal['title']) ?>" loading="lazy">
          <span class="cat-badge"><?= htmlspecialchars($deal['category']??'') ?></span>
          <?php if (!empty($deal['days'])): ?>
            <span class="duration-badge"><?= (int)$deal['days'] ?> Days</span>
          <?php endif; ?>
          <?php if ($discount>0): ?><span class="discount-badge">-<?= $discount ?>% OFF</span><?php endif; ?>
          <?php if ($isUserDeal): ?>
            <span class="partner-badge">Partner Listing</span>
            <?php if ($expiryLabel): ?><span class="expiry-badge <?= $expiryClass ?>"><?= $expiryLabel ?></span><?php endif; ?>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="card-icon-fallback">
          <?= !empty($deal['emoji'])?$deal['emoji']:'🏔️' ?>
          <span class="cat-badge"><?= htmlspecialchars($deal['category']??'') ?></span>
          <?php if (!empty($deal['days'])): ?>
            <span class="duration-badge"><?= (int)$deal['days'] ?> Days</span>
          <?php endif; ?>
          <?php if ($discount>0): ?><span class="discount-badge">-<?= $discount ?>% OFF</span><?php endif; ?>
          <?php if ($isUserDeal): ?>
            <span class="partner-badge">Partner Listing</span>
            <?php if ($expiryLabel): ?><span class="expiry-badge <?= $expiryClass ?>"><?= $expiryLabel ?></span><?php endif; ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- CARD BODY -->
      <div class="card-body">
        
        <!-- Location Label -->
        <div class="card-location-lbl"><?= htmlspecialchars($locationLabel ?: 'Nepal') ?></div>

        <!-- Title -->
        <div class="card-title-new"><?= htmlspecialchars($deal['title']) ?></div>

        <!-- Rating -->
        <?php if ($avgRating>0): ?>
          <div class="rating-row-new has-reviews"
               onclick="event.preventDefault();openReviews(<?= $dealId ?>,<?= $reviewsJson ?>,<?= $dealTitleJson ?>)"
               title="See all reviews">
            <?= $starsHtml ?>
            <span class="rating-value-new"><?= number_format($avgRating,1) ?></span>
            <span class="rating-count-new">(<?= $reviewCount ?> review<?= $reviewCount!==1?'s':'' ?> ↗)</span>
          </div>
        <?php else: ?>
          <div class="rating-row-new">
            <?= str_repeat('<span class="star-empty">★</span>',5) ?>
            <span class="no-reviews-new">No reviews yet</span>
          </div>
        <?php endif; ?>

        <!-- Divider line -->
        <hr class="card-divider">

        <!-- Bottom row: Price and CTA button -->
        <div class="card-bottom-new">
          <div class="card-price-col-new">
            <?php if (!empty($deal['original_price']) && (float)$deal['original_price']>(float)$deal['price']): ?>
              <div class="price-original-new">NPR <?= number_format((float)$deal['original_price']) ?></div>
            <?php endif; ?>
            <div class="price-main-new">NPR <?= number_format((float)$deal['price']) ?></div>
          </div>
          
          <span class="view-btn-new">View Details</span>
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
  let activeFilter = 'all';

  function applyFilters() {
    const query    = (document.getElementById('dealSearch').value || '').toLowerCase().trim();
    const clearBtn = document.getElementById('searchClearBtn');
    clearBtn.classList.toggle('visible', query.length > 0);
    let anyVisible = false;
    document.querySelectorAll('.tour-card').forEach(card => {
      const matchesFilter = activeFilter==='all' || card.dataset.category===activeFilter;
      const matchesSearch = !query || (card.dataset.search||'').includes(query);
      const show = matchesFilter && matchesSearch;
      card.classList.toggle('hidden', !show);
      if (show) anyVisible = true;
    });
    let noRes = document.getElementById('noResultsMsg');
    if (!anyVisible) {
      if (!noRes) {
        noRes = document.createElement('p');
        noRes.id='noResultsMsg'; noRes.className='no-results';
        document.getElementById('cardsGrid').appendChild(noRes);
      }
      noRes.textContent = query ? `No deals found for "${query}".` : 'No deals in this category.';
    } else if (noRes) noRes.remove();
  }

  function clearSearch() {
    document.getElementById('dealSearch').value='';
    applyFilters();
    document.getElementById('dealSearch').focus();
  }

  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      activeFilter = btn.dataset.filter;
      applyFilters();
    });
  });

  /* SLIDER */
  const slides=document.querySelectorAll('.hero-slide');
  const dots  =document.querySelectorAll('.hero-dot');
  let current=0, autoPlay=null;
  function goToSlide(i){
    if(!slides.length)return;
    slides[current].classList.remove('active'); dots[current]&&dots[current].classList.remove('active');
    current=(i+slides.length)%slides.length;
    slides[current].classList.add('active'); dots[current]&&dots[current].classList.add('active');
  }
  function slideHero(d){resetAuto();goToSlide(current+d);}
  function startAuto(){autoPlay=setInterval(()=>goToSlide(current+1),5000);}
  function resetAuto(){clearInterval(autoPlay);startAuto();}
  if(slides.length>1)startAuto();
  const sliderEl=document.getElementById('heroSlider');
  if(sliderEl){
    sliderEl.addEventListener('mouseenter',()=>clearInterval(autoPlay));
    sliderEl.addEventListener('mouseleave',startAuto);
    let tx=0;
    sliderEl.addEventListener('touchstart',e=>{tx=e.touches[0].clientX;},{passive:true});
    sliderEl.addEventListener('touchend',e=>{
      const diff=tx-e.changedTouches[0].clientX;
      if(Math.abs(diff)>40){resetAuto();goToSlide(current+(diff>0?1:-1));}
    });
  }

  /* REVIEWS MODAL */
  const AV=['av-0','av-1','av-2','av-3','av-4','av-5','av-6','av-7'];
  function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');}
  function buildStars(r,sz,fc,ec){let h='';for(let s=1;s<=5;s++)h+=`<span class="${s<=Math.round(r)?fc:ec}" style="font-size:${sz}px">★</span>`;return h;}

  function openReviews(dealId,reviews,dealTitle){
    document.getElementById('modalDealTitle').textContent=dealTitle;
    if(!reviews||reviews.length===0){
      document.getElementById('modalAvgStars').innerHTML=buildStars(0,16,'star-filled','star-empty');
      document.getElementById('modalAvgNum').textContent='';
      document.getElementById('modalAvgLabel').textContent='No reviews yet';
      document.getElementById('modalBreakdown').innerHTML='';
      document.getElementById('modalReviewsList').innerHTML=`<div class="modal-empty"><div class="modal-empty-icon">🏔️</div><div>Be the first to review this deal!</div></div>`;
      openModalUI();return;
    }
    const avg=reviews.reduce((s,r)=>s+parseFloat(r.rating),0)/reviews.length;
    document.getElementById('modalAvgStars').innerHTML=buildStars(avg,16,'star-filled','star-empty');
    document.getElementById('modalAvgNum').textContent=avg.toFixed(1);
    document.getElementById('modalAvgLabel').textContent=`out of 5 · ${reviews.length} review${reviews.length!==1?'s':''}`;
    const counts={5:0,4:0,3:0,2:0,1:0};
    reviews.forEach(r=>{const v=Math.min(5,Math.max(1,Math.round(parseFloat(r.rating))));counts[v]++;});
    let bHtml='';
    for(let star=5;star>=1;star--){
      const pct=Math.round((counts[star]/reviews.length)*100);
      bHtml+=`<div class="breakdown-row"><span class="breakdown-label">${star}★</span><div class="breakdown-bar-bg"><div class="breakdown-bar-fill" style="width:${pct}%"></div></div><span class="breakdown-count">${counts[star]}</span></div>`;
    }
    document.getElementById('modalBreakdown').innerHTML=bHtml;
    let rHtml='';
    reviews.forEach((r,idx)=>{
      const name=r.reviewer_name||'Anonymous';
      const initials=name.split(' ').map(w=>w[0]||'').join('').toUpperCase().slice(0,2)||'?';
      const avC=AV[idx%AV.length];
      const date=r.created_at?new Date(r.created_at).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric'}):'';
      const text=(r.review_text||'').trim();
      rHtml+=`<div class="review-item"><div class="review-top"><div class="reviewer-info"><div class="reviewer-avatar ${avC}">${esc(initials)}</div><div><div class="reviewer-name">${esc(name)}</div>${date?`<div class="reviewer-date">${esc(date)}</div>`:''}</div></div><div class="review-stars-inline">${buildStars(r.rating,12,'rs filled','rs empty')}</div></div>${text?`<div class="review-text">${esc(text)}</div>`:`<div class="review-text" style="font-style:italic;opacity:.3">No written review.</div>`}</div>`;
    });
    document.getElementById('modalReviewsList').innerHTML=rHtml;
    openModalUI();
  }
  function openModalUI(){document.getElementById('reviewsModal').classList.add('open');document.body.style.overflow='hidden';}
  function closeModal(){document.getElementById('reviewsModal').classList.remove('open');document.body.style.overflow='';}
  function closeIfBackdrop(e){if(e.target===document.getElementById('reviewsModal'))closeModal();}
  document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal();});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>