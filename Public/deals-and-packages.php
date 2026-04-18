<?php
require_once __DIR__ . '/../config/db.php';
include '../includes/header.php'; 

// ── FETCH ALL DEALS ────────────────────────────────────────────
$result = $conn->query("SELECT * FROM deals ORDER BY rating DESC");
$deals  = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Deals &amp; Packages | Nepal Tours</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', sans-serif;
      min-height: 100vh;
      /* ── BACKGROUND: swap this URL for your own hosted image ── */
      background-image: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1600&q=80');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
    }

    .page-overlay {
      min-height: 100vh;
      background: rgba(10, 12, 22, 0.65);
      padding: 3rem 1.5rem;
    }

    /* ── HEADING ── */
    .page-heading {
      text-align: center;
      margin-bottom: 2.5rem;
    }
    .page-heading h1 {
      font-size: 2rem;
      font-weight: 700;
      color: #fff;
      letter-spacing: -0.02em;
    }
    .page-heading p {
      color: rgba(255,255,255,0.55);
      font-size: 0.95rem;
      margin-top: 0.4rem;
    }

    /* ── FILTER BAR ── */
    .filter-bar {
      display: flex;
      justify-content: center;
      gap: 0.6rem;
      flex-wrap: wrap;
      margin-bottom: 2rem;
    }
    .filter-btn {
      background: rgba(255,255,255,0.08);
      color: rgba(255,255,255,0.75);
      border: 1px solid rgba(255,255,255,0.15);
      padding: 6px 18px;
      border-radius: 30px;
      font-size: 13px;
      cursor: pointer;
      transition: background 0.15s, color 0.15s;
    }
    .filter-btn:hover,
    .filter-btn.active {
      background: #2563eb;
      color: #fff;
      border-color: #2563eb;
    }

    /* ── GRID ── */
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 1.25rem;
      max-width: 1100px;
      margin: 0 auto;
    }

    /* ── CARD ── */
    .tour-card {
      background: rgba(24, 28, 44, 0.88);
      border-radius: 16px;
      border: 1px solid rgba(255,255,255,0.09);
      display: flex;
      flex-direction: column;
      backdrop-filter: blur(10px);
      transition: transform 0.18s, border-color 0.18s;
    }
    .tour-card:hover {
      transform: translateY(-4px);
      border-color: rgba(100,150,255,0.35);
    }

    .card-icon {
      font-size: 54px;
      text-align: center;
      padding: 1.4rem 1rem 0.4rem;
      line-height: 1;
    }

    .card-img {
      width: 100%;
      height: 160px;
      object-fit: cover;
      border-radius: 16px 16px 0 0;
    }

    .card-body {
      padding: 0 1.1rem 1.3rem;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 9px;
    }

    .card-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 8px;
    }
    .card-title {
      font-size: 15px;
      font-weight: 600;
      color: #fff;
      line-height: 1.3;
    }
    .discount-badge {
      background: #e84393;
      color: #fff;
      font-size: 11px;
      font-weight: 600;
      padding: 3px 8px;
      border-radius: 6px;
      white-space: nowrap;
      flex-shrink: 0;
    }

    .meta { display: flex; flex-direction: column; gap: 4px; }
    .meta-row {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      color: rgba(255,255,255,0.6);
    }

    .rating-row {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 12px;
      color: rgba(255,255,255,0.6);
    }
    .star { color: #f4b942; font-size: 14px; }

    .tags { display: flex; flex-wrap: wrap; gap: 6px; }
    .tag {
      background: rgba(37, 99, 235, 0.4);
      color: #a8c4f8;
      font-size: 11px;
      padding: 3px 9px;
      border-radius: 5px;
      border: 1px solid rgba(100,150,255,0.2);
    }

    .price-block { margin-top: 4px; }
    .price-original {
      font-size: 12px;
      color: rgba(255,255,255,0.38);
      text-decoration: line-through;
    }
    .price-main {
      font-size: 24px;
      font-weight: 700;
      color: #fff;
      line-height: 1.1;
    }
    .price-sub {
      font-size: 11px;
      color: rgba(255,255,255,0.4);
    }

    .book-btn {
      display: block;
      width: 100%;
      padding: 11px;
      background: #2563eb;
      color: #fff;
      font-size: 14px;
      font-weight: 600;
      border: none;
      border-radius: 9px;
      cursor: pointer;
      text-align: center;
      margin-top: 10px;
      text-decoration: none;
      transition: background 0.15s;
    }
    .book-btn:hover { background: #1d4ed8; }

    .tour-card.hidden { display: none; }

    .no-results {
      text-align: center;
      color: rgba(255,255,255,0.5);
      font-size: 1rem;
      padding: 3rem 0;
      grid-column: 1 / -1;
    }
  </style>
</head>
<body>

<div class="page-overlay">

  <div class="page-heading">
    <h1>Deals &amp; Packages</h1>
    <p>Handpicked treks and tours across Nepal — at the best prices</p>
  </div>

  <!-- Filter buttons auto-built from categories in your DB -->
  <div class="filter-bar">
    <button class="filter-btn active" data-filter="all">All</button>
    <?php
      $categories = array_unique(array_column($deals, 'category'));
      foreach ($categories as $cat):
    ?>
      <button class="filter-btn" data-filter="<?= htmlspecialchars($cat) ?>">
        <?= htmlspecialchars($cat) ?>
      </button>
    <?php endforeach; ?>
  </div>

  <!-- Cards grid -->
  <div class="cards-grid">

    <?php if (empty($deals)): ?>
      <p class="no-results">No deals found in the database.</p>
    <?php endif; ?>

    <?php foreach ($deals as $deal):

      // Calculate discount %
      $discount = 0;
      if (!empty($deal['original_price']) && (float)$deal['original_price'] > 0) {
          $discount = round((((float)$deal['original_price'] - (float)$deal['price']) / (float)$deal['original_price']) * 100);
      }

      // Features — comma-separated in DB → show first 2 as tags
      $features = [];
      if (!empty($deal['features'])) {
          $features = array_slice(array_map('trim', explode(',', $deal['features'])), 0, 2);
      }

      // Use image if available, otherwise emoji
      $hasImage = !empty($deal['image_url']) && strtoupper($deal['image_url']) !== 'NULL';
    ?>

    <div class="tour-card" data-category="<?= htmlspecialchars($deal['category'] ?? '') ?>">

      <?php if ($hasImage): ?>
        <img
          src="<?= htmlspecialchars($deal['image_url']) ?>"
          alt="<?= htmlspecialchars($deal['title']) ?>"
          class="card-img"
        />
      <?php else: ?>
        <div class="card-icon"><?= !empty($deal['emoji']) ? $deal['emoji'] : '🏔️' ?></div>
      <?php endif; ?>

      <div class="card-body">

        <div class="card-header">
          <span class="card-title"><?= htmlspecialchars($deal['title']) ?></span>
          <?php if ($discount > 0): ?>
            <span class="discount-badge">-<?= $discount ?>%</span>
          <?php endif; ?>
        </div>

        <div class="meta">
          <?php if (!empty($deal['days'])): ?>
          <div class="meta-row">
            <span>📅</span>
            <?= (int)$deal['days'] ?> day<?= (int)$deal['days'] > 1 ? 's' : '' ?>
          </div>
          <?php endif; ?>
          <?php if (!empty($deal['season'])): ?>
          <div class="meta-row">
            <span>📍</span>
            <?= htmlspecialchars($deal['season']) ?>
          </div>
          <?php endif; ?>
        </div>

        <?php if (!empty($deal['rating'])): ?>
        <div class="rating-row">
          <span class="star">★</span>
          <span><?= number_format((float)$deal['rating'], 1) ?></span>
          <?php if (!empty($deal['reviews_count'])): ?>
          <span style="color:rgba(255,255,255,0.38);">
            (<?= number_format((int)$deal['reviews_count']) ?> reviews)
          </span>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($features)): ?>
        <div class="tags">
          <?php foreach ($features as $f): ?>
            <span class="tag"><?= htmlspecialchars($f) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="price-block">
          <?php if (!empty($deal['original_price'])): ?>
          <div class="price-original">
            NPR <?= number_format((float)$deal['original_price']) ?>
          </div>
          <?php endif; ?>
          <div class="price-main">NPR <?= number_format((float)$deal['price']) ?></div>
          <div class="price-sub">per person</div>
        </div>

        <!-- Update href to your booking page -->
        <a href="booking.php?id=<?= (int)$deal['id'] ?>" class="book-btn">Book Now</a>

      </div>
    </div>

    <?php endforeach; ?>

  </div><!-- /cards-grid -->
</div><!-- /page-overlay -->

<script>
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
</script>
<?php include '../includes/footer.php'; ?>
</body>
</html>