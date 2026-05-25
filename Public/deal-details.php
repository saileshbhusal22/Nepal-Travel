<?php
/**
 * Author: Ishwor Dhital
 * Student ID: 2462304
 * Group: L5CG6
 */
require_once __DIR__ . '/../config/db.php';
include '../includes/header.php';

// ── Admin deals only — user deals go to Ud_deal_details.php ──
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: deals-and-packages.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM deals WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$deal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$deal) { header("Location: deals-and-packages.php"); exit; }

// ── Check if the logged-in user already booked this deal ────
$already_booked    = false;
$existing_book_id  = null;

if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $bchk = $conn->prepare(
        "SELECT id FROM bookings
         WHERE user_id = ? AND deal_id = ? AND status = 'active'
         LIMIT 1"
    );
    $bchk->bind_param("ii", $uid, $id);
    $bchk->execute();
    $bchk->bind_result($existing_book_id);
    $bchk->fetch();
    $bchk->close();
    $already_booked = !empty($existing_book_id);
}

// Fetch reviews with user info
$reviews = [];
$rev_stmt = $conn->prepare("
    SELECT r.id, r.rating, r.review_text, r.created_at,
           u.id AS user_id, u.full_name, u.username, u.email
    FROM deal_reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.deal_id = ?
    ORDER BY r.created_at DESC
");
$rev_stmt->bind_param("i", $id);
$rev_stmt->execute();
$rev_result = $rev_stmt->get_result();
while ($row = $rev_result->fetch_assoc()) {
    $reviews[] = $row;
}
$rev_stmt->close();

$total_reviews = count($reviews);

// ── FIX: Only calculate avg_rating from actual reviews.
//         Never fall back to $deal['rating'] so the widget
//         stays hidden when there are no reviews.
$avg_rating = $total_reviews > 0
    ? array_sum(array_column($reviews, 'rating')) / $total_reviews
    : 0;  // ← was: ($deal['rating'] ?? 0)  — that caused the ghost rating

// Rating breakdown
$breakdown = [5=>0,4=>0,3=>0,2=>0,1=>0];
foreach ($reviews as $rv) {
    $star = max(1, min(5, (int)$rv['rating']));
    $breakdown[$star]++;
}

// Features
$features = !empty($deal['features'])
    ? array_map('trim', explode(',', $deal['features']))
    : [];

// Discount %
$discount = 0;
if (!empty($deal['original_price']) && (float)$deal['original_price'] > (float)$deal['price']) {
    $discount = round((((float)$deal['original_price'] - (float)$deal['price']) / (float)$deal['original_price']) * 100);
}

// Images
$allImages = [];
foreach (['image_url','image_url_2','image_url_3','image_url_4'] as $col) {
    if (!empty($deal[$col]) && strtoupper(trim($deal[$col])) !== 'NULL') {
        $allImages[] = $deal[$col];
    }
}
$heroImage   = $allImages[0] ?? null;
$galleryImgs = array_slice($allImages, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($deal['title']) ?> | Nepal Tours</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      background-image: url('https://www.discovertreks.com/wp-content/uploads/2017/09/Nepal-discover-himalayan-treks.jpg');
      background-size: cover; background-position: center; background-attachment: fixed;
      color: #fff;
    }

    .page-overlay { min-height: 100vh; background: rgba(8,10,20,0.30); padding-bottom: 6rem; }

    /* ══ HERO ══ */
    .detail-hero { position: relative; width: 100%; height: 600px; overflow: hidden; }
    .hero-img {
      width: 100%; height: 100%; object-fit: cover; display: block;
      transform: scale(1.05); transition: transform 8s ease;
    }
    .hero-img.loaded { transform: scale(1); }
    .hero-fallback {
      width: 100%; height: 100%;
      background: linear-gradient(135deg, #1a1f35 0%, #0d1020 100%);
      display: flex; align-items: center; justify-content: center; font-size: 130px;
    }
    .hero-grad {
      position: absolute; inset: 0;
      background: linear-gradient(to bottom, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.45) 55%, rgba(0,0,0,0.88) 100%);
    }
    .hero-top {
      position: absolute; top: 0; left: 0; right: 0; z-index: 4;
      display: flex; align-items: center; justify-content: space-between;
      padding: 1.5rem 2rem;
    }
    .back-btn {
      display: inline-flex; align-items: center; gap: 8px;
      background: rgba(0,0,0,0.40); border: 1px solid rgba(255,255,255,0.20);
      color: #fff; font-size: 13px; font-weight: 500;
      padding: 9px 20px; border-radius: 30px; text-decoration: none;
      backdrop-filter: blur(8px); transition: background 0.2s;
    }
    .back-btn:hover { background: rgba(0,0,0,0.65); }
    .hero-badges { display: flex; gap: 8px; }
    .badge-cat {
      background: rgba(92,63,204,0.92); backdrop-filter: blur(6px);
      color: #fff; font-size: 10px; font-weight: 600;
      padding: 6px 15px; border-radius: 20px;
      text-transform: uppercase; letter-spacing: 0.08em;
    }
    .badge-disc { background: #e84393; color: #fff; font-size: 11px; font-weight: 700; padding: 6px 13px; border-radius: 8px; }
    .hero-bottom {
      position: absolute; bottom: 0; left: 0; right: 0; z-index: 3;
      padding: 0 2.5rem 2.8rem; max-width: 900px;
    }
    .hero-eyebrow {
      font-size: 10px; font-weight: 700; letter-spacing: 0.22em;
      text-transform: uppercase; color: rgba(255,255,255,0.45); margin-bottom: 0.5rem;
    }
    .hero-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2rem, 4.5vw, 3.2rem); font-weight: 700; color: #fff;
      line-height: 1.10; text-shadow: 0 2px 28px rgba(0,0,0,0.55); margin-bottom: 1rem;
    }
    .hero-meta { display: flex; flex-wrap: wrap; gap: 1.2rem; font-size: 13px; color: rgba(255,255,255,0.60); }
    .hero-meta-item { display: flex; align-items: center; gap: 5px; }
    .star-gold { color: #f4b942; }

    .rating-clickable {
      cursor: pointer;
      background: rgba(244,185,66,0.15);
      border: 1px solid rgba(244,185,66,0.30);
      border-radius: 20px; padding: 4px 12px;
      transition: background 0.2s, border-color 0.2s;
      display: flex; align-items: center; gap: 5px;
    }
    .rating-clickable:hover { background: rgba(244,185,66,0.28); border-color: rgba(244,185,66,0.60); }
    .rating-clickable .pulse-dot {
      width: 6px; height: 6px; border-radius: 50%;
      background: #f4b942; animation: pulse 1.8s infinite;
    }
    @keyframes pulse {
      0%,100% { opacity: 1; transform: scale(1); }
      50%      { opacity: 0.4; transform: scale(1.5); }
    }

    /* ══ GALLERY STRIP ══ */
    .gallery-strip {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 4px; max-height: 200px; overflow: hidden;
    }
    .gallery-strip img {
      width: 100%; height: 200px; object-fit: cover; display: block;
      cursor: pointer; opacity: 0.78; transition: opacity 0.2s, transform 0.3s;
    }
    .gallery-strip img:hover { opacity: 1; transform: scale(1.02); }

    /* ══ LIGHTBOX ══ */
    .lightbox {
      display: none; position: fixed; inset: 0; z-index: 999;
      background: rgba(0,0,0,0.92); align-items: center; justify-content: center;
    }
    .lightbox.open { display: flex; }
    .lightbox img { max-width: 92vw; max-height: 88vh; object-fit: contain; border-radius: 8px; }
    .lb-close {
      position: absolute; top: 1.5rem; right: 1.5rem;
      background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2);
      color: #fff; font-size: 22px; width: 44px; height: 44px;
      border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;
    }
    .lb-close:hover { background: rgba(255,255,255,0.25); }
    .lb-prev, .lb-next {
      position: absolute; top: 50%; transform: translateY(-50%);
      background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18);
      color: #fff; font-size: 30px; width: 50px; height: 50px;
      border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;
      transition: background 0.2s;
    }
    .lb-prev { left: 1.5rem; } .lb-next { right: 1.5rem; }
    .lb-prev:hover, .lb-next:hover { background: rgba(255,255,255,0.25); }

    /* ══ LAYOUT ══ */
    .detail-layout {
      max-width: 1240px; margin: 0 auto; padding: 3rem 2rem;
      display: grid; grid-template-columns: 1fr 360px;
      gap: 3rem; align-items: start;
    }
    @media (max-width: 880px) {
      .detail-layout { grid-template-columns: 1fr; padding: 1.5rem 1rem; }
      .detail-hero   { height: 380px; }
      .hero-bottom   { padding: 0 1.5rem 2rem; }
    }

    /* ══ GLASS CARDS ══ */
    .glass-card {
      background: rgba(18,21,31,0.85); backdrop-filter: blur(14px);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 18px; padding: 1.8rem; margin-bottom: 1.4rem;
    }
    .sec-label {
      font-size: 10px; font-weight: 700; letter-spacing: 0.18em;
      text-transform: uppercase; color: rgba(255,255,255,0.30); margin-bottom: 1rem;
    }

    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(105px, 1fr)); gap: 10px; }
    .stat-card {
      background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);
      border-radius: 12px; padding: 1rem 0.7rem; text-align: center;
    }
    .stat-icon { font-size: 20px; margin-bottom: 5px; }
    .stat-val  { font-size: 1.15rem; font-weight: 600; color: #fff; line-height: 1.2; }
    .stat-lbl  { font-size: 10px; color: rgba(255,255,255,0.32); margin-top: 3px; text-transform: uppercase; letter-spacing: 0.06em; }

    .rating-row { display: flex; align-items: center; gap: 10px; margin-top: 1.3rem; }
    .stars-disp { color: #f4b942; font-size: 17px; letter-spacing: 2px; }
    .rating-num { font-size: 1.4rem; font-weight: 600; color: #fff; }
    .review-cnt { font-size: 13px; color: rgba(255,255,255,0.35); }
    .see-reviews-link {
      font-size: 12px; color: #60a5fa; cursor: pointer;
      text-decoration: underline; text-underline-offset: 2px;
      background: none; border: none; font-family: inherit;
      padding: 0; transition: color 0.2s;
    }
    .see-reviews-link:hover { color: #93c5fd; }

    .desc-text { font-size: 15px; line-height: 1.80; color: rgba(255,255,255,0.68); }
    .feat-wrap { display: flex; flex-wrap: wrap; gap: 8px; }
    .feat-tag {
      background: rgba(37,99,235,0.18); color: #a8c4f8;
      border: 1px solid rgba(100,150,255,0.18);
      font-size: 12px; padding: 6px 14px; border-radius: 6px;
    }
    .info-tbl { width: 100%; border-collapse: collapse; }
    .info-tbl tr { border-bottom: 1px solid rgba(255,255,255,0.05); }
    .info-tbl tr:last-child { border-bottom: none; }
    .info-tbl td { padding: 12px 0; font-size: 14px; }
    .info-tbl .lbl { color: rgba(255,255,255,0.35); width: 38%; }
    .info-tbl .val { color: #fff; font-weight: 500; }

    /* ══ SIDEBAR ══ */
    .sidebar-wrap { position: sticky; top: 2rem; }
    .sidebar-card {
      background: #12151f; border: 1px solid rgba(255,255,255,0.08);
      border-radius: 20px; overflow: hidden; margin-bottom: 1rem;
    }
    .price-head {
      background: linear-gradient(135deg, #1a1f35 0%, #0d1020 100%);
      border-bottom: 1px solid rgba(255,255,255,0.07); padding: 1.6rem 1.5rem;
    }
    .price-from-lbl {
      font-size: 10px; font-weight: 700; color: rgba(255,255,255,0.32);
      text-transform: uppercase; letter-spacing: 0.10em; margin-bottom: 7px;
    }
    .price-main-row { display: flex; align-items: baseline; flex-wrap: wrap; gap: 10px; }
    .price-now  { font-size: 2.1rem; font-weight: 700; color: #fff; line-height: 1; }
    .price-orig { font-size: 1rem; color: rgba(255,255,255,0.27); text-decoration: line-through; }
    .disc-pill  { background: #e84393; color: #fff; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px; }
    .price-pp   { font-size: 11px; color: rgba(255,255,255,0.28); margin-top: 5px; }
    .s-body { padding: 1.4rem 1.5rem; }
    .s-row {
      display: flex; justify-content: space-between; align-items: center;
      padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px;
    }
    .s-row:last-of-type { border-bottom: none; }
    .s-row .lbl { color: rgba(255,255,255,0.35); }
    .s-row .val { color: #fff; font-weight: 500; }

    /* Book Now button */
    .book-btn {
      display: block; width: 100%; margin-top: 1.2rem;
      background: #2563eb; color: #fff;
      font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600;
      border: none; border-radius: 12px; padding: 15px;
      cursor: pointer; text-align: center; text-decoration: none;
      letter-spacing: 0.04em; transition: background 0.15s, transform 0.1s;
    }
    .book-btn:hover  { background: #1d4ed8; }
    .book-btn:active { transform: scale(0.98); }

    /* Already booked state */
    .booked-badge {
      display: flex; align-items: center; justify-content: center;
      gap: 8px; width: 100%; margin-top: 1.2rem;
      background: rgba(234,179,8,0.12);
      border: 1px solid rgba(234,179,8,0.35);
      border-radius: 12px; padding: 13px 15px;
      font-family: 'DM Sans', sans-serif; font-size: 14px; font-weight: 600;
      color: #fde68a; text-decoration: none; text-align: center;
      transition: background 0.15s;
    }
    .booked-badge:hover { background: rgba(234,179,8,0.20); }
    .booked-badge .bb-dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: #f4b942; flex-shrink: 0;
      box-shadow: 0 0 0 3px rgba(244,185,66,0.25);
      animation: pulse 1.8s infinite;
    }
    .booked-sub {
      display: block; text-align: center;
      font-size: 11px; color: rgba(255,255,255,0.25);
      margin-top: 6px; font-weight: 400;
    }

    .wish-btn {
      display: flex; align-items: center; justify-content: center;
      gap: 7px; width: 100%; margin-top: 10px;
      background: transparent; border: 1px solid rgba(255,255,255,0.10);
      color: rgba(255,255,255,0.40); font-family: 'DM Sans', sans-serif;
      font-size: 13px; border-radius: 12px; padding: 13px;
      cursor: pointer; transition: border-color 0.2s, color 0.2s;
    }
    .wish-btn:hover { border-color: #e84393; color: #e84393; }
    .share-row { display: flex; gap: 8px; margin-top: 10px; }
    .share-btn {
      flex: 1; display: flex; align-items: center; justify-content: center; gap: 5px;
      background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
      color: rgba(255,255,255,0.45); font-family: 'DM Sans', sans-serif;
      font-size: 12px; font-weight: 500; border-radius: 10px; padding: 10px 6px;
      cursor: pointer; transition: background 0.15s, color 0.15s;
    }
    .share-btn:hover { background: rgba(255,255,255,0.10); color: #fff; }

    /* ══ REVIEWS MODAL ══ */
    .reviews-modal-overlay {
      display: none; position: fixed; inset: 0; z-index: 1000;
      background: rgba(0,0,0,0.75); backdrop-filter: blur(6px);
      align-items: center; justify-content: center; padding: 1rem;
    }
    .reviews-modal-overlay.open { display: flex; }
    .reviews-modal {
      background: #0e1120; border: 1px solid rgba(255,255,255,0.08);
      border-radius: 24px; width: 100%; max-width: 680px;
      max-height: 88vh; display: flex; flex-direction: column;
      overflow: hidden; box-shadow: 0 40px 100px rgba(0,0,0,0.6);
      animation: modalIn 0.3s cubic-bezier(0.34,1.56,0.64,1);
    }
    @keyframes modalIn {
      from { opacity: 0; transform: translateY(30px) scale(0.96); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }
    .rm-header {
      padding: 1.6rem 1.8rem 1.2rem;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      display: flex; align-items: flex-start; justify-content: space-between; flex-shrink: 0;
    }
    .rm-title { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 700; color: #fff; margin-bottom: 4px; }
    .rm-subtitle { font-size: 13px; color: rgba(255,255,255,0.35); }
    .rm-close {
      background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
      color: #fff; width: 36px; height: 36px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; font-size: 18px; flex-shrink: 0; transition: background 0.2s;
    }
    .rm-close:hover { background: rgba(255,255,255,0.16); }
    .rm-summary {
      padding: 1.2rem 1.8rem; border-bottom: 1px solid rgba(255,255,255,0.06);
      display: flex; gap: 2rem; align-items: center; flex-shrink: 0;
      background: rgba(255,255,255,0.02);
    }
    .rm-big-rating { text-align: center; }
    .rm-big-num { font-size: 3rem; font-weight: 700; color: #fff; line-height: 1; }
    .rm-big-stars { color: #f4b942; font-size: 18px; letter-spacing: 2px; margin: 4px 0; }
    .rm-big-cnt  { font-size: 11px; color: rgba(255,255,255,0.30); }
    .rm-bars { flex: 1; }
    .rm-bar-row { display: flex; align-items: center; gap: 10px; font-size: 12px; color: rgba(255,255,255,0.40); margin-bottom: 6px; }
    .rm-bar-row:last-child { margin-bottom: 0; }
    .rm-bar-row .star-n { width: 12px; text-align: right; }
    .rm-bar-track { flex: 1; height: 5px; background: rgba(255,255,255,0.08); border-radius: 99px; overflow: hidden; }
    .rm-bar-fill  { height: 100%; background: linear-gradient(90deg, #f4b942, #f97316); border-radius: 99px; transition: width 0.6s ease; }
    .rm-bar-cnt   { width: 22px; }
    .rm-list { overflow-y: auto; padding: 1.2rem 1.8rem; flex: 1; }
    .rm-list::-webkit-scrollbar { width: 5px; }
    .rm-list::-webkit-scrollbar-track { background: transparent; }
    .rm-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 99px; }
    .review-card { display: flex; gap: 14px; padding: 1.2rem 0; border-bottom: 1px solid rgba(255,255,255,0.05); animation: fadeUp 0.35s ease both; }
    .review-card:last-child { border-bottom: none; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
    .reviewer-avatar {
      width: 46px; height: 46px; border-radius: 50%; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 17px; font-weight: 700; color: #fff;
      background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
      border: 2px solid rgba(255,255,255,0.10); overflow: hidden;
    }
    .review-body { flex: 1; min-width: 0; }
    .review-meta-top { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px; margin-bottom: 6px; }
    .reviewer-name { font-size: 14px; font-weight: 600; color: #fff; }
    .reviewer-handle { font-size: 11px; color: rgba(255,255,255,0.30); margin-top: 1px; }
    .review-stars { color: #f4b942; font-size: 13px; letter-spacing: 1px; }
    .review-date { font-size: 11px; color: rgba(255,255,255,0.24); }
    .review-text-body { font-size: 14px; line-height: 1.70; color: rgba(255,255,255,0.60); word-break: break-word; }
    .rm-empty { text-align: center; padding: 3rem 1rem; color: rgba(255,255,255,0.25); font-size: 14px; }
    .rm-empty .empty-icon { font-size: 40px; margin-bottom: 12px; }
  </style>
</head>
<body>
<div class="page-overlay">

  <!-- ══ HERO ══ -->
  <div class="detail-hero">
    <?php if ($heroImage): ?>
      <img class="hero-img" id="heroImg" src="<?= htmlspecialchars($heroImage) ?>" alt="<?= htmlspecialchars($deal['title']) ?>">
    <?php else: ?>
      <div class="hero-fallback"><?= !empty($deal['emoji']) ? $deal['emoji'] : '🏔️' ?></div>
    <?php endif; ?>
    <div class="hero-grad"></div>
    <div class="hero-top">
      <a href="deals-and-packages.php" class="back-btn">&#8592;&nbsp; All Deals</a>
      <div class="hero-badges">
        <?php if (!empty($deal['category'])): ?>
          <span class="badge-cat"><?= htmlspecialchars($deal['category']) ?></span>
        <?php endif; ?>
        <?php if ($discount > 0): ?>
          <span class="badge-disc">-<?= $discount ?>% OFF</span>
        <?php endif; ?>
        <?php if ($already_booked): ?>
          <span style="background:rgba(234,179,8,0.85);color:#1a1200;font-size:10px;font-weight:700;padding:6px 13px;border-radius:8px;backdrop-filter:blur(6px);">
            🎫 BOOKED
          </span>
        <?php endif; ?>
      </div>
    </div>
    <div class="hero-bottom">
      <p class="hero-eyebrow">Deals &amp; Packages</p>
      <h1 class="hero-title"><?= htmlspecialchars($deal['title']) ?></h1>
      <div class="hero-meta">
        <?php if (!empty($deal['location'])): ?>
          <span class="hero-meta-item">📍 <?= htmlspecialchars($deal['location']) ?></span>
        <?php endif; ?>
        <?php if (!empty($deal['days'])): ?>
          <span class="hero-meta-item">📅 <?= (int)$deal['days'] ?> day<?= (int)$deal['days'] > 1 ? 's' : '' ?></span>
        <?php endif; ?>
        <?php if (!empty($deal['season'])): ?>
          <span class="hero-meta-item">🗓 <?= htmlspecialchars($deal['season']) ?></span>
        <?php endif; ?>
        <?php
        // ── FIX: Only show rating pill when there are actual reviews ──
        if ($total_reviews > 0 && $avg_rating > 0): ?>
          <span class="hero-meta-item">
            <span class="rating-clickable" onclick="openReviews()" title="See all reviews">
              <span class="star-gold">★</span>
              <?= number_format($avg_rating, 1) ?>
              <span style="color:rgba(255,255,255,0.40); font-size:11px">(<?= $total_reviews ?> reviews)</span>
              <span class="pulse-dot"></span>
            </span>
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ══ GALLERY STRIP ══ -->
  <?php if (!empty($galleryImgs)): ?>
  <div class="gallery-strip" id="galleryStrip">
    <?php foreach ($allImages as $i => $img): ?>
      <img src="<?= htmlspecialchars($img) ?>" alt="Gallery <?= $i+1 ?>" onclick="openLightbox(<?= $i ?>)">
    <?php endforeach; ?>
  </div>
  <?php elseif ($heroImage): ?>
  <div class="gallery-strip" id="galleryStrip" style="max-height:120px">
    <img src="<?= htmlspecialchars($heroImage) ?>" alt="<?= htmlspecialchars($deal['title']) ?>" onclick="openLightbox(0)">
  </div>
  <?php endif; ?>

  <!-- ══ LIGHTBOX ══ -->
  <?php if (!empty($allImages)): ?>
  <div class="lightbox" id="lightbox">
    <button class="lb-close" onclick="closeLightbox()">&#215;</button>
    <button class="lb-prev"  onclick="lbNav(-1)">&#8249;</button>
    <img id="lbImg" src="" alt=""/>
    <button class="lb-next"  onclick="lbNav(1)">&#8250;</button>
  </div>
  <?php endif; ?>

  <!-- ══ REVIEWS MODAL ══ -->
  <div class="reviews-modal-overlay" id="reviewsModal" onclick="handleModalClick(event)">
    <div class="reviews-modal">
      <div class="rm-header">
        <div>
          <h2 class="rm-title">Guest Reviews</h2>
          <p class="rm-subtitle"><?= htmlspecialchars($deal['title']) ?></p>
        </div>
        <button class="rm-close" onclick="closeReviews()">&#215;</button>
      </div>
      <?php if ($total_reviews > 0): ?>
      <div class="rm-summary">
        <div class="rm-big-rating">
          <div class="rm-big-num"><?= number_format($avg_rating, 1) ?></div>
          <div class="rm-big-stars">
            <?php $r = round($avg_rating); echo str_repeat('★', $r) . str_repeat('☆', 5-$r); ?>
          </div>
          <div class="rm-big-cnt"><?= $total_reviews ?> review<?= $total_reviews !== 1 ? 's' : '' ?></div>
        </div>
        <div class="rm-bars">
          <?php for ($s = 5; $s >= 1; $s--): ?>
            <?php $pct = $total_reviews > 0 ? round(($breakdown[$s]/$total_reviews)*100) : 0; ?>
            <div class="rm-bar-row">
              <span class="star-n"><?= $s ?></span>
              <span style="color:#f4b942;font-size:10px">★</span>
              <div class="rm-bar-track"><div class="rm-bar-fill" style="width:<?= $pct ?>%"></div></div>
              <span class="rm-bar-cnt"><?= $breakdown[$s] ?></span>
            </div>
          <?php endfor; ?>
        </div>
      </div>
      <?php endif; ?>
      <div class="rm-list">
        <?php if (empty($reviews)): ?>
          <div class="rm-empty">
            <div class="empty-icon">💬</div>
            <p>No reviews yet for this deal.</p>
            <p style="margin-top:6px;font-size:12px;color:rgba(255,255,255,0.18)">Be the first to share your experience!</p>
          </div>
        <?php else: ?>
          <?php foreach ($reviews as $idx => $rv): ?>
            <?php
              $name     = !empty($rv['full_name']) ? $rv['full_name'] : ($rv['username'] ?? 'Anonymous');
              $parts    = explode(' ', trim($name));
              $initials = strtoupper(substr($parts[0],0,1)) . (isset($parts[1]) ? strtoupper(substr($parts[1],0,1)) : '');
              $stars    = max(1, min(5, (int)$rv['rating']));
              $gradients = ['linear-gradient(135deg,#2563eb,#7c3aed)','linear-gradient(135deg,#059669,#0891b2)','linear-gradient(135deg,#dc2626,#e84393)','linear-gradient(135deg,#d97706,#65a30d)','linear-gradient(135deg,#7c3aed,#e84393)'];
              $grad     = $gradients[$idx % count($gradients)];
              $date_str = !empty($rv['created_at']) ? date('M j, Y', strtotime($rv['created_at'])) : '';
            ?>
            <div class="review-card" style="animation-delay:<?= $idx * 0.07 ?>s">
              <div class="reviewer-avatar" style="background:<?= $grad ?>"><?= htmlspecialchars($initials) ?></div>
              <div class="review-body">
                <div class="review-meta-top">
                  <div>
                    <div class="reviewer-name"><?= htmlspecialchars($name) ?></div>
                    <?php if (!empty($rv['username'])): ?><div class="reviewer-handle">@<?= htmlspecialchars($rv['username']) ?></div><?php endif; ?>
                  </div>
                  <div style="text-align:right">
                    <div class="review-stars"><?= str_repeat('★', $stars) . str_repeat('☆', 5-$stars) ?></div>
                    <?php if ($date_str): ?><div class="review-date"><?= $date_str ?></div><?php endif; ?>
                  </div>
                </div>
                <?php if (!empty($rv['review_text'])): ?>
                  <p class="review-text-body"><?= nl2br(htmlspecialchars($rv['review_text'])) ?></p>
                <?php else: ?>
                  <p class="review-text-body" style="color:rgba(255,255,255,0.20);font-style:italic">No written review provided.</p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ══ MAIN LAYOUT ══ -->
  <div class="detail-layout">
    <div>
      <div class="glass-card">
        <p class="sec-label">Overview</p>
        <div class="stat-grid">
          <?php if (!empty($deal['days'])): ?>
          <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-val"><?= (int)$deal['days'] ?></div>
            <div class="stat-lbl">Days</div>
          </div>
          <?php endif; ?>
          <?php
          // ── FIX: Stat cards for rating/reviews only when reviews exist ──
          if ($total_reviews > 0 && $avg_rating > 0): ?>
          <div class="stat-card" style="cursor:pointer" onclick="openReviews()">
            <div class="stat-icon">⭐</div>
            <div class="stat-val"><?= number_format($avg_rating, 1) ?></div>
            <div class="stat-lbl">Rating</div>
          </div>
          <div class="stat-card" style="cursor:pointer" onclick="openReviews()">
            <div class="stat-icon">💬</div>
            <div class="stat-val"><?= $total_reviews ?></div>
            <div class="stat-lbl">Reviews</div>
          </div>
          <?php endif; ?>
          <?php if (!empty($deal['location'])): ?>
          <div class="stat-card">
            <div class="stat-icon">📍</div>
            <div class="stat-val" style="font-size:0.80rem"><?= htmlspecialchars($deal['location']) ?></div>
            <div class="stat-lbl">Location</div>
          </div>
          <?php endif; ?>
        </div>
        <?php
        // ── FIX: Rating row only when reviews exist ──
        if ($total_reviews > 0 && $avg_rating > 0): ?>
        <div class="rating-row">
          <?php $r = round($avg_rating); ?>
          <span class="stars-disp"><?= str_repeat('★',$r) . str_repeat('☆', 5-$r) ?></span>
          <span class="rating-num"><?= number_format($avg_rating, 1) ?></span>
          <span class="review-cnt">(<?= $total_reviews ?> reviews)</span>
          <button class="see-reviews-link" onclick="openReviews()">See all reviews →</button>
        </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($deal['description'])): ?>
      <div class="glass-card">
        <p class="sec-label">About This Deal</p>
        <p class="desc-text"><?= nl2br(htmlspecialchars($deal['description'])) ?></p>
      </div>
      <?php endif; ?>

      <?php if (!empty($features)): ?>
      <div class="glass-card">
        <p class="sec-label">Highlights</p>
        <div class="feat-wrap">
          <?php foreach ($features as $f): if (!empty($f)): ?>
            <span class="feat-tag"><?= htmlspecialchars($f) ?></span>
          <?php endif; endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="glass-card">
        <p class="sec-label">Trip Details</p>
        <table class="info-tbl">
          <?php if (!empty($deal['category'])): ?><tr><td class="lbl">Category</td><td class="val"><?= htmlspecialchars($deal['category']) ?></td></tr><?php endif; ?>
          <?php if (!empty($deal['location'])): ?><tr><td class="lbl">Location</td><td class="val"><?= htmlspecialchars($deal['location']) ?></td></tr><?php endif; ?>
          <?php if (!empty($deal['days'])): ?><tr><td class="lbl">Duration</td><td class="val"><?= (int)$deal['days'] ?> days</td></tr><?php endif; ?>
          <?php if (!empty($deal['season'])): ?><tr><td class="lbl">Best Season</td><td class="val"><?= htmlspecialchars($deal['season']) ?></td></tr><?php endif; ?>
          <?php
          // ── FIX: Rating row in table only when reviews exist ──
          if ($total_reviews > 0 && $avg_rating > 0): ?>
          <tr>
            <td class="lbl">Rating</td>
            <td class="val">⭐ <?= number_format($avg_rating, 1) ?> / 5
              <button class="see-reviews-link" onclick="openReviews()" style="margin-left:6px">(<?= $total_reviews ?> reviews)</button>
            </td>
          </tr>
          <?php endif; ?>
          <?php if (!empty($deal['created_at'])): ?><tr><td class="lbl">Listed On</td><td class="val"><?= date('F j, Y', strtotime($deal['created_at'])) ?></td></tr><?php endif; ?>
        </table>
      </div>
    </div>

    <!-- ══ SIDEBAR ══ -->
    <div class="sidebar-wrap">
      <div class="sidebar-card">
        <div class="price-head">
          <p class="price-from-lbl">From</p>
          <div class="price-main-row">
            <span class="price-now">NPR <?= number_format((float)$deal['price']) ?></span>
            <?php if (!empty($deal['original_price']) && (float)$deal['original_price'] > (float)$deal['price']): ?>
              <span class="price-orig">NPR <?= number_format((float)$deal['original_price']) ?></span>
              <span class="disc-pill">-<?= $discount ?>%</span>
            <?php endif; ?>
          </div>
          <p class="price-pp">per person</p>
        </div>
        <div class="s-body">
          <?php if (!empty($deal['days'])): ?><div class="s-row"><span class="lbl">Duration</span><span class="val"><?= (int)$deal['days'] ?> days</span></div><?php endif; ?>
          <?php if (!empty($deal['location'])): ?><div class="s-row"><span class="lbl">Location</span><span class="val"><?= htmlspecialchars($deal['location']) ?></span></div><?php endif; ?>
          <?php if (!empty($deal['season'])): ?><div class="s-row"><span class="lbl">Season</span><span class="val"><?= htmlspecialchars($deal['season']) ?></span></div><?php endif; ?>
          <?php if (!empty($deal['category'])): ?><div class="s-row"><span class="lbl">Category</span><span class="val"><?= htmlspecialchars($deal['category']) ?></span></div><?php endif; ?>
          <?php
          // ── FIX: Rating in sidebar only when reviews exist ──
          if ($total_reviews > 0 && $avg_rating > 0): ?>
          <div class="s-row">
            <span class="lbl">Rating</span>
            <span class="val" style="cursor:pointer" onclick="openReviews()">⭐ <?= number_format($avg_rating,1) ?> / 5</span>
          </div>
          <?php endif; ?>

          <?php if ($already_booked): ?>
            <a href="ticket.php?id=<?= $existing_book_id ?>" class="booked-badge">
              <span class="bb-dot"></span>
              🎫 &nbsp; View My Booking
            </a>
            <span class="booked-sub">You already have an active booking for this deal</span>
          <?php else: ?>
            <a href="booking.php?id=<?= (int)$deal['id'] ?>" class="book-btn">Book Now</a>
          <?php endif; ?>

          <a href="save_deal.php?id=<?= (int)$deal['id'] ?>&action=add" class="wish-btn">♡ &nbsp; Save to Wishlist</a>
          <div class="share-row">
            <button class="share-btn" onclick="navigator.clipboard.writeText(window.location.href).then(()=>this.textContent='✓ Copied!')">🔗 Copy Link</button>
            <button class="share-btn" onclick="window.open('https://wa.me/?text='+encodeURIComponent(document.title+' — '+window.location.href))">💬 WhatsApp</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const heroImg = document.getElementById('heroImg');
  if (heroImg) {
    if (heroImg.complete) heroImg.classList.add('loaded');
    else heroImg.addEventListener('load', () => heroImg.classList.add('loaded'));
  }

  const lbImages = <?= json_encode($allImages) ?>;
  let lbCurrent  = 0;

  function openLightbox(index) {
    lbCurrent = index;
    document.getElementById('lbImg').src = lbImages[lbCurrent];
    document.getElementById('lightbox').classList.add('open');
  }
  function closeLightbox() { document.getElementById('lightbox').classList.remove('open'); }
  function lbNav(dir) {
    lbCurrent = (lbCurrent + dir + lbImages.length) % lbImages.length;
    document.getElementById('lbImg').src = lbImages[lbCurrent];
  }
  document.getElementById('lightbox')?.addEventListener('click', function(e) { if (e.target === this) closeLightbox(); });

  function openReviews() { document.getElementById('reviewsModal').classList.add('open'); document.body.style.overflow = 'hidden'; }
  function closeReviews() { document.getElementById('reviewsModal').classList.remove('open'); document.body.style.overflow = ''; }
  function handleModalClick(e) { if (e.target === document.getElementById('reviewsModal')) closeReviews(); }

  document.addEventListener('keydown', e => {
    const lb = document.getElementById('lightbox');
    const rm = document.getElementById('reviewsModal');
    if (rm?.classList.contains('open') && e.key === 'Escape') { closeReviews(); return; }
    if (!lb?.classList.contains('open')) return;
    if (e.key === 'ArrowRight') lbNav(1);
    if (e.key === 'ArrowLeft')  lbNav(-1);
    if (e.key === 'Escape')     closeLightbox();
  });
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>