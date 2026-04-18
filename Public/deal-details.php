<?php
require_once __DIR__ . '/../config/db.php';
include '../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header("Location: deals.php"); exit; }

$stmt = $conn->prepare("SELECT * FROM deals WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$deal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$deal) { header("Location: deals-and-packages.php"); exit; }

// Features array
$features = !empty($deal['features'])
    ? array_map('trim', explode(',', $deal['features']))
    : [];

// Discount %
$discount = 0;
if (!empty($deal['original_price']) && (float)$deal['original_price'] > (float)$deal['price']) {
    $discount = round((((float)$deal['original_price'] - (float)$deal['price']) / (float)$deal['original_price']) * 100);
}

// Images — collect all non-empty gallery images
$allImages = [];
foreach (['image_url', 'image_url_2', 'image_url_3', 'image_url_4'] as $col) {
    if (!empty($deal[$col]) && strtoupper(trim($deal[$col])) !== 'NULL') {
        $allImages[] = $deal[$col];
    }
}
$heroImage   = $allImages[0] ?? null;
$galleryImgs = array_slice($allImages, 1); // extra images for gallery strip
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

    /* top bar */
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
    .badge-disc {
      background: #e84393; color: #fff;
      font-size: 11px; font-weight: 700; padding: 6px 13px; border-radius: 8px;
    }

    /* hero bottom */
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

    /* ══ PHOTO GALLERY STRIP ══ */
    .gallery-strip {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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

    /* stat grid */
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(105px, 1fr)); gap: 10px; }
    .stat-card {
      background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);
      border-radius: 12px; padding: 1rem 0.7rem; text-align: center;
    }
    .stat-icon { font-size: 20px; margin-bottom: 5px; }
    .stat-val  { font-size: 1.15rem; font-weight: 600; color: #fff; line-height: 1.2; }
    .stat-lbl  { font-size: 10px; color: rgba(255,255,255,0.32); margin-top: 3px; text-transform: uppercase; letter-spacing: 0.06em; }

    /* rating */
    .rating-row { display: flex; align-items: center; gap: 10px; margin-top: 1.3rem; }
    .stars-disp { color: #f4b942; font-size: 17px; letter-spacing: 2px; }
    .rating-num { font-size: 1.4rem; font-weight: 600; color: #fff; }
    .review-cnt { font-size: 13px; color: rgba(255,255,255,0.35); }

    /* description */
    .desc-text { font-size: 15px; line-height: 1.80; color: rgba(255,255,255,0.68); }

    /* features */
    .feat-wrap { display: flex; flex-wrap: wrap; gap: 8px; }
    .feat-tag {
      background: rgba(37,99,235,0.18); color: #a8c4f8;
      border: 1px solid rgba(100,150,255,0.18);
      font-size: 12px; padding: 6px 14px; border-radius: 6px;
    }

    /* info table */
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
      <a href="deals.php" class="back-btn">&#8592;&nbsp; All Deals</a>
      <div class="hero-badges">
        <?php if (!empty($deal['category'])): ?>
          <span class="badge-cat"><?= htmlspecialchars($deal['category']) ?></span>
        <?php endif; ?>
        <?php if ($discount > 0): ?>
          <span class="badge-disc">-<?= $discount ?>% OFF</span>
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
        <?php if (!empty($deal['rating'])): ?>
          <span class="hero-meta-item">
            <span class="star-gold">★</span>
            <?= number_format((float)$deal['rating'], 1) ?>
            <?php if (!empty($deal['reviews_count'])): ?>
              <span style="color:rgba(255,255,255,0.30)">(<?= number_format((int)$deal['reviews_count']) ?>)</span>
            <?php endif; ?>
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ══ GALLERY STRIP (extra images from DB) ══ -->
  <?php if (!empty($galleryImgs)): ?>
  <div class="gallery-strip" id="galleryStrip">
    <?php foreach ($allImages as $i => $img): ?>
      <img src="<?= htmlspecialchars($img) ?>" alt="Gallery <?= $i+1 ?>" onclick="openLightbox(<?= $i ?>)">
    <?php endforeach; ?>
  </div>
  <?php elseif ($heroImage): ?>
  <!-- Single image — show it as a clickable strip -->
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

  <!-- ══ MAIN LAYOUT ══ -->
  <div class="detail-layout">

    <!-- LEFT -->
    <div>

      <!-- Stats + rating -->
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
          <?php if (!empty($deal['rating'])): ?>
          <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-val"><?= number_format((float)$deal['rating'], 1) ?></div>
            <div class="stat-lbl">Rating</div>
          </div>
          <?php endif; ?>
          <?php if (!empty($deal['reviews_count'])): ?>
          <div class="stat-card">
            <div class="stat-icon">💬</div>
            <div class="stat-val"><?= number_format((int)$deal['reviews_count']) ?></div>
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
        <?php if (!empty($deal['rating'])): ?>
        <div class="rating-row">
          <?php $r = round((float)$deal['rating']); ?>
          <span class="stars-disp"><?= str_repeat('★', $r) . str_repeat('☆', 5 - $r) ?></span>
          <span class="rating-num"><?= number_format((float)$deal['rating'], 1) ?></span>
          <?php if (!empty($deal['reviews_count'])): ?>
            <span class="review-cnt">(<?= number_format((int)$deal['reviews_count']) ?> reviews)</span>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Description — stored in DB -->
      <?php if (!empty($deal['description'])): ?>
      <div class="glass-card">
        <p class="sec-label">About This Deal</p>
        <p class="desc-text"><?= nl2br(htmlspecialchars($deal['description'])) ?></p>
      </div>
      <?php endif; ?>

      <!-- Highlights / Features -->
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

      <!-- Trip details table -->
      <div class="glass-card">
        <p class="sec-label">Trip Details</p>
        <table class="info-tbl">
          <?php if (!empty($deal['category'])): ?>
          <tr><td class="lbl">Category</td><td class="val"><?= htmlspecialchars($deal['category']) ?></td></tr>
          <?php endif; ?>
          <?php if (!empty($deal['location'])): ?>
          <tr><td class="lbl">Location</td><td class="val"><?= htmlspecialchars($deal['location']) ?></td></tr>
          <?php endif; ?>
          <?php if (!empty($deal['days'])): ?>
          <tr><td class="lbl">Duration</td><td class="val"><?= (int)$deal['days'] ?> days</td></tr>
          <?php endif; ?>
          <?php if (!empty($deal['season'])): ?>
          <tr><td class="lbl">Best Season</td><td class="val"><?= htmlspecialchars($deal['season']) ?></td></tr>
          <?php endif; ?>
          <?php if (!empty($deal['rating'])): ?>
          <tr><td class="lbl">Rating</td><td class="val">⭐ <?= number_format((float)$deal['rating'], 1) ?> / 5</td></tr>
          <?php endif; ?>
          <?php if (!empty($deal['created_at'])): ?>
          <tr><td class="lbl">Listed On</td><td class="val"><?= date('F j, Y', strtotime($deal['created_at'])) ?></td></tr>
          <?php endif; ?>
        </table>
      </div>

    </div><!-- /left -->

    <!-- SIDEBAR -->
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
          <?php if (!empty($deal['days'])): ?>
          <div class="s-row"><span class="lbl">Duration</span><span class="val"><?= (int)$deal['days'] ?> days</span></div>
          <?php endif; ?>
          <?php if (!empty($deal['location'])): ?>
          <div class="s-row"><span class="lbl">Location</span><span class="val"><?= htmlspecialchars($deal['location']) ?></span></div>
          <?php endif; ?>
          <?php if (!empty($deal['season'])): ?>
          <div class="s-row"><span class="lbl">Season</span><span class="val"><?= htmlspecialchars($deal['season']) ?></span></div>
          <?php endif; ?>
          <?php if (!empty($deal['category'])): ?>
          <div class="s-row"><span class="lbl">Category</span><span class="val"><?= htmlspecialchars($deal['category']) ?></span></div>
          <?php endif; ?>
          <?php if (!empty($deal['rating'])): ?>
          <div class="s-row"><span class="lbl">Rating</span><span class="val">⭐ <?= number_format((float)$deal['rating'], 1) ?> / 5</span></div>
          <?php endif; ?>

          <a href="booking.php?id=<?= (int)$deal['id'] ?>" class="book-btn">Book Now</a>
          <a href="save_deal.php?id=<?= (int)$deal['id'] ?>&action=add" class="wish-btn">
  ♡ &nbsp; Save to Wishlist
</a>
          <div class="share-row">
            <button class="share-btn" onclick="navigator.clipboard.writeText(window.location.href).then(()=>this.textContent='✓ Copied!')">🔗 Copy Link</button>
            <button class="share-btn" onclick="window.open('https://wa.me/?text='+encodeURIComponent(document.title+' — '+window.location.href))">💬 WhatsApp</button>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /layout -->
</div><!-- /overlay -->

<script>
  // Ken Burns on hero
  const heroImg = document.getElementById('heroImg');
  if (heroImg) {
    if (heroImg.complete) heroImg.classList.add('loaded');
    else heroImg.addEventListener('load', () => heroImg.classList.add('loaded'));
  }

  // Lightbox
  const lbImages = <?= json_encode($allImages) ?>;
  let lbCurrent  = 0;

  function openLightbox(index) {
    lbCurrent = index;
    document.getElementById('lbImg').src = lbImages[lbCurrent];
    document.getElementById('lightbox').classList.add('open');
  }

  function closeLightbox() {
    document.getElementById('lightbox').classList.remove('open');
  }

  function lbNav(dir) {
    lbCurrent = (lbCurrent + dir + lbImages.length) % lbImages.length;
    document.getElementById('lbImg').src = lbImages[lbCurrent];
  }

  // Close lightbox on backdrop click
  document.getElementById('lightbox')?.addEventListener('click', function(e) {
    if (e.target === this) closeLightbox();
  });

  // Keyboard nav
  document.addEventListener('keydown', e => {
    if (!document.getElementById('lightbox')?.classList.contains('open')) return;
    if (e.key === 'ArrowRight') lbNav(1);
    if (e.key === 'ArrowLeft')  lbNav(-1);
    if (e.key === 'Escape')     closeLightbox();
  });
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>