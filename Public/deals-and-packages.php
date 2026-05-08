<?php
require_once __DIR__ . '/../config/db.php';
include '../includes/header.php';

// ── FETCH ALL DEALS ────────────────────────────────────────────
$result = $conn->query("SELECT * FROM deals ORDER BY id DESC");
if (!$result) {
    die('<p style="color:red;padding:2rem;">Deals query failed: ' . $conn->error . '</p>');
}
$deals = $result->fetch_all(MYSQLI_ASSOC);

// ── FETCH REAL RATINGS FROM deal_reviews TABLE ─────────────────
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

// ── SLIDER: top 5 deals that have an image ─────────────────────
$slider_deals = array_filter($deals, fn($d) =>
    !empty($d['image_url']) && strtoupper(trim($d['image_url'])) !== 'NULL'
);
$slider_deals = array_slice(array_values($slider_deals), 0, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Deals &amp; Packages | Nepal Tours</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      background-image: url('https://www.discovertreks.com/wp-content/uploads/2017/09/Nepal-discover-himalayan-treks.jpg');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
    }

    .page-overlay {
      min-height: 100vh;
      background: rgba(8, 10, 20, 0.25);
      padding-bottom: 5rem;
    }

    /* ── HERO SLIDER ── */
    .hero-slider { position: relative; width: 100%; height: 900px; overflow: hidden; margin-bottom: 3rem; }
    .hero-slide {
      background-repeat: no-repeat; image-rendering: -webkit-optimize-contrast;
      position: absolute; inset: 0; background-size: cover; background-position: center;
      opacity: 0; transition: opacity 0.9s ease;
      display: flex; align-items: center; justify-content: center;
    }
    .hero-slide.active { opacity: 1; z-index: 1; }
    .hero-slide-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(to bottom, rgba(0,0,0,0.10) 0%, rgba(0,0,0,0.55) 60%, rgba(0,0,0,0.70) 100%);
    }
    .hero-slide-content { position: relative; z-index: 2; text-align: center; padding: 0 2rem; max-width: 820px; }
    .hero-label { font-size: 11px; font-weight: 700; letter-spacing: 0.22em; text-transform: uppercase; color: rgba(255,255,255,0.60); margin-bottom: 0.8rem; }
    .hero-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(1.9rem, 4.5vw, 3.4rem); font-weight: 700;
      color: #fff; line-height: 1.12; margin-bottom: 1.8rem;
      text-shadow: 0 2px 24px rgba(0,0,0,0.45);
    }
    .hero-cta {
      display: inline-block; background: #c9a227; color: #fff;
      font-family: 'DM Sans', sans-serif; font-size: 11.5px; font-weight: 700;
      letter-spacing: 0.18em; text-transform: uppercase;
      padding: 14px 40px; border-radius: 4px; text-decoration: none;
      transition: background 0.18s, transform 0.15s;
    }
    .hero-cta:hover { background: #b8911f; transform: scale(1.03); }
    .hero-arrow {
      position: absolute; top: 50%; transform: translateY(-50%); z-index: 3;
      background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.20);
      color: #fff; font-size: 28px; width: 46px; height: 46px; border-radius: 50%;
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      transition: background 0.18s; line-height: 1;
    }
    .hero-arrow:hover { background: rgba(255,255,255,0.25); }
    .hero-prev { left: 20px; } .hero-next { right: 20px; }
    .hero-dots { position: absolute; bottom: 18px; left: 50%; transform: translateX(-50%); z-index: 3; display: flex; gap: 7px; }
    .hero-dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: rgba(255,255,255,0.35); cursor: pointer;
      transition: background 0.2s, transform 0.2s; border: none;
    }
    .hero-dot.active { background: #c9a227; transform: scale(1.25); }

    /* ── PAGE HEADING ── */
    .page-heading { text-align: center; margin-bottom: 2rem; padding: 0 1.5rem; }
    .page-heading h1 { font-family: 'Playfair Display', serif; font-size: 2.6rem; font-weight: 700; color: #fff; letter-spacing: -0.01em; line-height: 1.1; }
    .page-heading p { color: rgba(255,255,255,0.5); font-size: 0.95rem; margin-top: 0.5rem; }

    /* ── SEARCH BAR ── */
    .search-wrap {
      display: flex; justify-content: center;
      margin-bottom: 1.5rem; padding: 0 1.5rem;
    }
    .search-inner {
      position: relative; width: 100%; max-width: 500px;
    }
    .search-icon {
      position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
      font-size: 16px; pointer-events: none; line-height: 1;
    }
    .search-input {
      width: 100%; padding: 13px 44px 13px 44px;
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: 30px; color: #fff;
      font-family: 'DM Sans', sans-serif; font-size: 14px;
      outline: none; transition: background 0.2s, border-color 0.2s;
    }
    .search-input::placeholder { color: rgba(255,255,255,0.38); }
    .search-input:focus {
      background: rgba(255,255,255,0.13);
      border-color: rgba(100,150,255,0.50);
    }
    .search-clear {
      display: none; position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
      background: rgba(255,255,255,0.12); border: none; color: rgba(255,255,255,0.6);
      width: 22px; height: 22px; border-radius: 50%; cursor: pointer;
      font-size: 12px; align-items: center; justify-content: center;
      transition: background 0.15s;
    }
    .search-clear:hover { background: rgba(255,255,255,0.22); color: #fff; }
    .search-clear.visible { display: flex; }

    /* ── FILTER BAR ── */
    .filter-bar { display: flex; justify-content: center; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 3rem; padding: 0 1.5rem; }
    .filter-btn {
      background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.65);
      border: 1px solid rgba(255,255,255,0.12); padding: 8px 24px;
      border-radius: 30px; font-family: 'DM Sans', sans-serif;
      font-size: 13px; font-weight: 500; cursor: pointer;
      transition: background 0.15s, color 0.15s, border-color 0.15s;
    }
    .filter-btn:hover, .filter-btn.active { background: #2563eb; color: #fff; border-color: #2563eb; }

    /* ── CARDS GRID ── */
    .cards-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(370px, 1fr));
      gap: 2.8rem; max-width: 1320px; margin: 0 auto; padding: 0 2.5rem;
    }

    /* ── CARD ── */
    .tour-card {
      background: #12151f; border-radius: 20px;
      border: 1px solid rgba(255,255,255,0.08);
      overflow: hidden; display: flex; flex-direction: column;
      transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
      cursor: pointer; text-decoration: none; color: inherit;
    }
    .tour-card:hover {
      transform: translateY(-6px);
      border-color: rgba(100,150,255,0.3);
      box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    }

    /* ── PHOTO AREA ── */
    .card-photo { position: relative; overflow: hidden; }
    .card-photo.single { height: 250px; }
    .card-photo.single img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.4s ease; }
    .tour-card:hover .card-photo.single img { transform: scale(1.04); }
    .card-icon-fallback {
      height: 250px; display: flex; align-items: center; justify-content: center;
      font-size: 80px; background: linear-gradient(135deg, #1a1f35 0%, #0d1020 100%); position: relative;
    }

    /* ── BADGES ── */
    .cat-badge {
      position: absolute; top: 14px; left: 14px;
      background: rgba(92, 63, 204, 0.9); backdrop-filter: blur(6px);
      color: #fff; font-size: 10px; font-weight: 600;
      padding: 5px 13px; border-radius: 20px;
      text-transform: uppercase; letter-spacing: 0.07em; z-index: 2;
    }
    .discount-badge {
      position: absolute; top: 14px; right: 14px;
      background: #e84393; color: #fff;
      font-size: 11px; font-weight: 700; padding: 5px 11px; border-radius: 7px; z-index: 2;
    }

    /* ── CARD BODY ── */
    .card-body { padding: 1.3rem 1.4rem 1.4rem; flex: 1; display: flex; flex-direction: column; gap: 9px; }
    .location-label { font-size: 11px; font-weight: 700; color: #7c9ef8; text-transform: uppercase; letter-spacing: 0.08em; }
    .card-title { font-family: 'Playfair Display', serif; font-size: 18px; font-weight: 600; color: #fff; line-height: 1.3; }
    .meta-row { display: flex; align-items: center; gap: 14px; font-size: 13px; color: rgba(255,255,255,0.45); }
    .meta-item { display: flex; align-items: center; gap: 4px; }

    /* ── RATING ROW ── */
    .rating-row {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 13px; width: fit-content;
      padding: 5px 9px 5px 0; border-radius: 8px;
      transition: background 0.15s;
    }
    .rating-row.has-reviews { cursor: pointer; }
    .rating-row.has-reviews:hover { background: rgba(255,255,255,0.06); }
    .rating-row.has-reviews:hover .rating-count { color: #7c9ef8; text-decoration: underline; }
    .star-filled { color: #f4b942; font-size: 14px; }
    .star-empty  { color: rgba(255,255,255,0.18); font-size: 14px; }
    .rating-value { color: #fff; font-weight: 600; font-size: 13px; margin-left: 2px; }
    .rating-count { color: rgba(255,255,255,0.35); font-size: 12px; transition: color 0.15s; }
    .no-reviews   { color: rgba(255,255,255,0.25); font-size: 12px; font-style: italic; }

    .tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 2px; }
    .tag {
      background: rgba(37, 99, 235, 0.25); color: #a8c4f8;
      font-size: 11px; padding: 4px 11px; border-radius: 5px;
      border: 1px solid rgba(100,150,255,0.18);
    }

    /* ── CARD FOOTER ── */
    .card-footer {
      display: flex; align-items: flex-end; justify-content: space-between;
      margin-top: auto; padding-top: 14px;
      border-top: 1px solid rgba(255,255,255,0.06);
    }
    .price-from { font-size: 10px; font-weight: 600; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 0.07em; }
    .price-original { font-size: 12px; color: rgba(255,255,255,0.3); text-decoration: line-through; line-height: 1.2; }
    .price-main { font-size: 22px; font-weight: 700; color: #fff; line-height: 1.1; }
    .price-sub { font-size: 11px; color: rgba(255,255,255,0.35); }
    .view-btn {
      background: #2563eb; color: #fff;
      font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 600;
      border: none; border-radius: 10px; padding: 12px 22px;
      white-space: nowrap; pointer-events: none;
    }

    /* ── HIDDEN / NO-RESULTS ── */
    .tour-card.hidden { display: none; }
    .no-results {
      text-align: center; color: rgba(255,255,255,0.4);
      font-size: 1rem; padding: 3rem 0; grid-column: 1 / -1;
    }

    @media (max-width: 768px) {
      .cards-grid { grid-template-columns: 1fr; gap: 1.6rem; padding: 0 1rem; }
      .hero-slider { height: 480px; }
    }

    /* ════════════════════════════════════════════
       REVIEWS MODAL
    ════════════════════════════════════════════ */
    .modal-backdrop {
      display: none; position: fixed; inset: 0; z-index: 9999;
      background: rgba(0,0,0,0.75); backdrop-filter: blur(5px);
      align-items: center; justify-content: center; padding: 1.5rem;
    }
    .modal-backdrop.open { display: flex; }

    .modal-box {
      background: #13161f;
      border: 1px solid rgba(255,255,255,0.10);
      border-radius: 22px; width: 100%; max-width: 560px; max-height: 88vh;
      display: flex; flex-direction: column;
      box-shadow: 0 30px 80px rgba(0,0,0,0.7);
      animation: modalIn 0.22s ease;
    }
    @keyframes modalIn {
      from { opacity: 0; transform: translateY(18px) scale(0.97); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .modal-header {
      padding: 1.4rem 1.6rem 1rem;
      border-bottom: 1px solid rgba(255,255,255,0.07);
      display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
    }
    .modal-header-left { flex: 1; min-width: 0; }
    .modal-deal-title {
      font-family: 'Playfair Display', serif;
      font-size: 17px; font-weight: 600; color: #fff;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 8px;
    }
    .modal-summary { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .modal-avg-stars { display: flex; gap: 2px; }
    .modal-avg-num { font-size: 24px; font-weight: 700; color: #fff; line-height: 1; }
    .modal-avg-label { font-size: 12px; color: rgba(255,255,255,0.4); align-self: flex-end; margin-bottom: 2px; }
    .modal-close {
      background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
      color: rgba(255,255,255,0.6); font-size: 18px; line-height: 1;
      width: 34px; height: 34px; border-radius: 50%; cursor: pointer;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
      transition: background 0.15s, color 0.15s;
    }
    .modal-close:hover { background: rgba(255,80,80,0.18); color: #ff6b6b; border-color: rgba(255,80,80,0.3); }

    .modal-breakdown {
      padding: 0.9rem 1.6rem;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      display: flex; flex-direction: column; gap: 6px;
    }
    .breakdown-row { display: flex; align-items: center; gap: 9px; font-size: 12px; color: rgba(255,255,255,0.5); }
    .breakdown-label { width: 28px; text-align: right; flex-shrink: 0; font-size: 11px; }
    .breakdown-bar-bg { flex: 1; height: 5px; background: rgba(255,255,255,0.08); border-radius: 10px; overflow: hidden; }
    .breakdown-bar-fill { height: 100%; background: linear-gradient(90deg, #f4b942, #f97316); border-radius: 10px; transition: width 0.5s ease; }
    .breakdown-count { width: 18px; text-align: left; flex-shrink: 0; font-size: 11px; }

    .modal-reviews { flex: 1; overflow-y: auto; padding: 1rem 1.6rem 1.4rem; display: flex; flex-direction: column; gap: 1rem; }
    .modal-reviews::-webkit-scrollbar { width: 4px; }
    .modal-reviews::-webkit-scrollbar-track { background: transparent; }
    .modal-reviews::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 10px; }

    .review-item {
      background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);
      border-radius: 14px; padding: 1rem 1.1rem; transition: border-color 0.15s;
    }
    .review-item:hover { border-color: rgba(255,255,255,0.12); }
    .review-top { display: flex; align-items: center; justify-content: space-between; gap: 0.8rem; margin-bottom: 0.6rem; }
    .reviewer-info { display: flex; align-items: center; gap: 0.7rem; }
    .reviewer-avatar {
      width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; font-weight: 700; color: #fff; letter-spacing: 0.03em;
    }
    .reviewer-name { font-size: 14px; font-weight: 600; color: #fff; }
    .reviewer-date { font-size: 11px; color: rgba(255,255,255,0.3); margin-top: 2px; }
    .review-stars-inline { display: flex; gap: 2px; }
    .review-stars-inline .rs { font-size: 13px; }
    .review-stars-inline .rs.filled { color: #f4b942; }
    .review-stars-inline .rs.empty  { color: rgba(255,255,255,0.15); }
    .review-text { font-size: 13.5px; color: rgba(255,255,255,0.62); line-height: 1.65; }

    .modal-empty { text-align: center; padding: 3rem 1rem; color: rgba(255,255,255,0.3); font-size: 14px; }
    .modal-empty-icon { font-size: 40px; margin-bottom: 0.7rem; }

    .av-0{background:#4f46e5} .av-1{background:#0ea5e9} .av-2{background:#10b981}
    .av-3{background:#f59e0b} .av-4{background:#ec4899} .av-5{background:#8b5cf6}
    .av-6{background:#ef4444} .av-7{background:#14b8a6}
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
  <div class="filter-bar">
    <button class="filter-btn active" data-filter="all">All</button>
    <?php
      $categories = array_unique(array_column($deals, 'category'));
      foreach ($categories as $cat): if (empty($cat)) continue; ?>
      <button class="filter-btn" data-filter="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></button>
    <?php endforeach; ?>
  </div>

  <!-- ── CARDS GRID ── -->
  <div class="cards-grid" id="cardsGrid">
    <?php if (empty($deals)): ?>
      <p class="no-results">No deals found.</p>
    <?php endif; ?>

    <?php foreach ($deals as $deal):
      $discount = 0;
      if (!empty($deal['original_price']) && (float)$deal['original_price'] > 0) {
          $discount = round((((float)$deal['original_price'] - (float)$deal['price']) / (float)$deal['original_price']) * 100);
      }
      $features = [];
      if (!empty($deal['features'])) {
          $features = array_slice(array_map('trim', explode(',', $deal['features'])), 0, 2);
      }
      $hasImage      = !empty($deal['image_url']) && strtoupper(trim($deal['image_url'])) !== 'NULL';
      $locationLabel = !empty($deal['location']) ? $deal['location'] : ($deal['season'] ?? '');

      $dealId      = (int)$deal['id'];
      $avgRating   = isset($reviewData[$dealId]) ? (float)$reviewData[$dealId]['avg_rating']  : 0;
      $reviewCount = isset($reviewData[$dealId]) ? (int)$reviewData[$dealId]['reviews_count'] : 0;

      $starsHtml = '';
      for ($s = 1; $s <= 5; $s++) {
          $starsHtml .= $s <= round($avgRating)
              ? '<span class="star-filled">★</span>'
              : '<span class="star-empty">★</span>';
      }

      $dealReviews   = $reviewDetails[$dealId] ?? [];
      $reviewsJson   = json_encode($dealReviews,  JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
      $dealTitleJson = json_encode($deal['title'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

      // Build search-data attribute for JS searching
      $searchData = strtolower(implode(' ', [
          $deal['title']    ?? '',
          $deal['location'] ?? '',
          $deal['category'] ?? '',
          $deal['season']   ?? '',
          $deal['features'] ?? '',
          $deal['description'] ?? '',
      ]));
    ?>

    <a href="deal-details.php?id=<?= $dealId ?>"
       class="tour-card"
       data-category="<?= htmlspecialchars($deal['category'] ?? '') ?>"
       data-search="<?= htmlspecialchars($searchData) ?>"
       style="text-decoration:none;">

      <?php if ($hasImage): ?>
        <div class="card-photo single" style="position:relative">
          <img src="<?= htmlspecialchars($deal['image_url']) ?>" alt="<?= htmlspecialchars($deal['title']) ?>" loading="lazy">
          <span class="cat-badge"><?= htmlspecialchars($deal['category'] ?? '') ?></span>
          <?php if ($discount > 0): ?><span class="discount-badge">-<?= $discount ?>%</span><?php endif; ?>
        </div>
      <?php else: ?>
        <div class="card-icon-fallback">
          <?= !empty($deal['emoji']) ? $deal['emoji'] : '🏔️' ?>
          <span class="cat-badge"><?= htmlspecialchars($deal['category'] ?? '') ?></span>
          <?php if ($discount > 0): ?><span class="discount-badge">-<?= $discount ?>%</span><?php endif; ?>
        </div>
      <?php endif; ?>

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

        <!-- ★ CLICKABLE RATING ★ -->
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

        <?php if (!empty($features)): ?>
        <div class="tags">
          <?php foreach ($features as $f): ?><span class="tag"><?= htmlspecialchars($f) ?></span><?php endforeach; ?>
        </div>
        <?php endif; ?>

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
      </div>
    </a>

    <?php endforeach; ?>
  </div>

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
     SEARCH + FILTER (work together)
  ════════════════════════════════════ */
  let activeFilter = 'all';

  function applyFilters() {
    const query      = (document.getElementById('dealSearch').value || '').toLowerCase().trim();
    const clearBtn   = document.getElementById('searchClearBtn');
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

    // No-results message
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

  // Filter buttons
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