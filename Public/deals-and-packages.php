<?php
require_once __DIR__ . '/../config/db.php';
include '../includes/header.php';

// ── FETCH ALL DEALS ────────────────────────────────────────────
$result = $conn->query("SELECT * FROM deals ORDER BY rating DESC");
$deals  = $result->fetch_all(MYSQLI_ASSOC);

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
    .hero-slider {
      position: relative; width: 100%;
      height: 900px; overflow: hidden; margin-bottom: 3rem;
    }
    .hero-slide {
      background-repeat: no-repeat;
      image-rendering: -webkit-optimize-contrast;
      position: absolute; inset: 0;
      background-size: cover; background-position: center;
      opacity: 0; transition: opacity 0.9s ease;
      display: flex; align-items: center; justify-content: center;
    }
    .hero-slide.active { opacity: 1; z-index: 1; }
    .hero-slide-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(to bottom, rgba(0,0,0,0.10) 0%, rgba(0,0,0,0.55) 60%, rgba(0,0,0,0.70) 100%);
    }
    .hero-slide-content {
      position: relative; z-index: 2;
      text-align: center; padding: 0 2rem; max-width: 820px;
    }
    .hero-label {
      font-size: 11px; font-weight: 700; letter-spacing: 0.22em;
      text-transform: uppercase; color: rgba(255,255,255,0.60); margin-bottom: 0.8rem;
    }
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
    .hero-dots {
      position: absolute; bottom: 18px; left: 50%; transform: translateX(-50%);
      z-index: 3; display: flex; gap: 7px;
    }
    .hero-dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: rgba(255,255,255,0.35); cursor: pointer;
      transition: background 0.2s, transform 0.2s; border: none;
    }
    .hero-dot.active { background: #c9a227; transform: scale(1.25); }

    /* ── PAGE HEADING ── */
    .page-heading { text-align: center; margin-bottom: 2.5rem; padding: 0 1.5rem; }
    .page-heading h1 {
      font-family: 'Playfair Display', serif; font-size: 2.6rem; font-weight: 700;
      color: #fff; letter-spacing: -0.01em; line-height: 1.1;
    }
    .page-heading p { color: rgba(255,255,255,0.5); font-size: 0.95rem; margin-top: 0.5rem; }

    /* ── FILTER BAR ── */
    .filter-bar {
      display: flex; justify-content: center; gap: 0.5rem;
      flex-wrap: wrap; margin-bottom: 3rem; padding: 0 1.5rem;
    }
    .filter-btn {
      background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.65);
      border: 1px solid rgba(255,255,255,0.12); padding: 8px 24px;
      border-radius: 30px; font-family: 'DM Sans', sans-serif;
      font-size: 13px; font-weight: 500; cursor: pointer;
      transition: background 0.15s, color 0.15s, border-color 0.15s;
    }
    .filter-btn:hover, .filter-btn.active {
      background: #2563eb; color: #fff; border-color: #2563eb;
    }

    /* ── CARDS GRID ── */
    .cards-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(370px, 1fr));
      gap: 2.8rem; max-width: 1320px; margin: 0 auto; padding: 0 2.5rem;
    }

    /* ── CARD ── make entire card clickable ── */
    .tour-card {
      background: #12151f; border-radius: 20px;
      border: 1px solid rgba(255,255,255,0.08);
      overflow: hidden; display: flex; flex-direction: column;
      transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
      cursor: pointer; text-decoration: none; color: inherit;
      /* <a> tag wraps card so we need block */
      display: flex;
    }
    .tour-card:hover {
      transform: translateY(-6px);
      border-color: rgba(100,150,255,0.3);
      box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    }

    /* ── PHOTO AREA ── */
    .card-photo { position: relative; overflow: hidden; }
    .card-photo.single { height: 250px; }
    .card-photo.single img {
      width: 100%; height: 100%; object-fit: cover; display: block;
      transition: transform 0.4s ease;
    }
    .tour-card:hover .card-photo.single img { transform: scale(1.04); }
    .card-icon-fallback {
      height: 250px; display: flex; align-items: center; justify-content: center;
      font-size: 80px;
      background: linear-gradient(135deg, #1a1f35 0%, #0d1020 100%);
      position: relative;
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
    .card-body {
      padding: 1.3rem 1.4rem 1.4rem; flex: 1;
      display: flex; flex-direction: column; gap: 9px;
    }
    .location-label {
      font-size: 11px; font-weight: 700; color: #7c9ef8;
      text-transform: uppercase; letter-spacing: 0.08em;
    }
    .card-title {
      font-family: 'Playfair Display', serif;
      font-size: 18px; font-weight: 600; color: #fff; line-height: 1.3;
    }
    .meta-row { display: flex; align-items: center; gap: 14px; font-size: 13px; color: rgba(255,255,255,0.45); }
    .meta-item { display: flex; align-items: center; gap: 4px; }
    .rating-row { display: flex; align-items: center; gap: 4px; font-size: 13px; color: rgba(255,255,255,0.55); }
    .star { color: #f4b942; font-size: 14px; }
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
      white-space: nowrap; pointer-events: none; /* card itself is the link */
    }

    /* ── HIDDEN / NO-RESULTS ── */
    .tour-card.hidden { display: none; }
    .no-results { text-align: center; color: rgba(255,255,255,0.4); font-size: 1rem; padding: 3rem 0; grid-column: 1 / -1; }

    @media (max-width: 768px) {
      .cards-grid { grid-template-columns: 1fr; gap: 1.6rem; padding: 0 1rem; }
    }
  </style>
</head>
<body>
<div class="page-overlay">

  <!-- HERO SLIDER -->
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

  <!-- PAGE HEADING -->
  <div class="page-heading" id="deals-section">
    <h1>Deals &amp; Packages</h1>
    <p>Handpicked treks and tours across Nepal — at the best prices</p>
  </div>

  <!-- FILTER -->
  <div class="filter-bar">
    <button class="filter-btn active" data-filter="all">All</button>
    <?php
      $categories = array_unique(array_column($deals, 'category'));
      foreach ($categories as $cat): ?>
      <button class="filter-btn" data-filter="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></button>
    <?php endforeach; ?>
  </div>

  <!-- CARDS GRID -->
  <div class="cards-grid">
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
      $hasImage = !empty($deal['image_url']) && strtoupper(trim($deal['image_url'])) !== 'NULL';
      $locationLabel = !empty($deal['location']) ? $deal['location'] : ($deal['season'] ?? '');
    ?>

    <!-- ★ ENTIRE CARD IS NOW AN <a> LINK ★ -->
    <a href="deal-details.php?id=<?= (int)$deal['id'] ?>"
       class="tour-card"
       data-category="<?= htmlspecialchars($deal['category'] ?? '') ?>"
       style="text-decoration:none;">

      <?php if ($hasImage): ?>
        <div class="card-photo single" style="position:relative">
          <img src="<?= htmlspecialchars($deal['image_url']) ?>" alt="<?= htmlspecialchars($deal['title']) ?>">
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
        <?php if (!empty($deal['rating'])): ?>
        <div class="rating-row">
          <span class="star">★</span>
          <span><?= number_format((float)$deal['rating'], 1) ?></span>
          <?php if (!empty($deal['reviews_count'])): ?>
            <span style="color:rgba(255,255,255,0.3)">(<?= number_format((int)$deal['reviews_count']) ?> reviews)</span>
          <?php endif; ?>
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
    </a><!-- /tour-card -->

    <?php endforeach; ?>
  </div>

</div><!-- /page-overlay -->

<script>
  /* ── FILTER ── */
  const filterBtns = document.querySelectorAll('.filter-btn');
  const cards      = document.querySelectorAll('.tour-card');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const filter = btn.dataset.filter;
      cards.forEach(card => {
        card.classList.toggle('hidden', filter !== 'all' && card.dataset.category !== filter);
      });
    });
  });

  /* ── HERO SLIDER ── */
  const slides  = document.querySelectorAll('.hero-slide');
  const dots    = document.querySelectorAll('.hero-dot');
  let current   = 0;
  let autoPlay  = null;

  function goToSlide(index) {
    slides[current].classList.remove('active');
    dots[current].classList.remove('active');
    current = (index + slides.length) % slides.length;
    slides[current].classList.add('active');
    dots[current].classList.add('active');
  }
  function slideHero(dir) { resetAuto(); goToSlide(current + dir); }
  function startAuto()    { autoPlay = setInterval(() => goToSlide(current + 1), 5000); }
  function resetAuto()    { clearInterval(autoPlay); startAuto(); }
  if (slides.length > 1) startAuto();

  const slider = document.getElementById('heroSlider');
  if (slider) {
    slider.addEventListener('mouseenter', () => clearInterval(autoPlay));
    slider.addEventListener('mouseleave', startAuto);
  }
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>