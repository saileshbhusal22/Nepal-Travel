<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../includes/header.php';
require_once __DIR__ . '/../config/db.php';

// ── Auto-expire user deals past visible_until ──────────────────
$conn->query("
    UPDATE user_deals
    SET status = 'expired'
    WHERE status = 'approved'
      AND visible_until IS NOT NULL
      AND visible_until < NOW()
");

// Fetch the 8 latest community posts for the home page feed
$community_posts_query = "
    SELECT p.id, p.image_path, p.caption, u.username 
    FROM posts p 
    LEFT JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC 
    LIMIT 8
";
$community_posts_res = $conn->query($community_posts_query);
$community_posts = [];
if ($community_posts_res) {
    while ($row = $community_posts_res->fetch_assoc()) {
        $community_posts[] = $row;
    }
}

// ── FETCH LATEST 6 ADMIN DEALS for homepage ──
$deals_result = $conn->query("SELECT *, 'admin' AS deal_source FROM deals ORDER BY created_at DESC LIMIT 6");
$home_admin_deals = [];
if ($deals_result) {
    while ($row = $deals_result->fetch_assoc()) {
        $home_admin_deals[] = $row;
    }
}

// ── FETCH APPROVED USER-SUBMITTED DEALS (up to 6) ──
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
    LIMIT 6
");
$home_user_deals = $ud_result ? $ud_result->fetch_all(MYSQLI_ASSOC) : [];

// ── MERGE and limit to 6 total ──
$home_deals = array_slice(array_merge($home_admin_deals, $home_user_deals), 0, 6);

// ── FETCH RATINGS from deal_reviews (admin deals) ──
$homeReviewData = [];
$tableCheck = $conn->query("SHOW TABLES LIKE 'deal_reviews'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $rr = $conn->query("
        SELECT deal_id,
               ROUND(AVG(rating), 1) AS avg_rating,
               COUNT(*) AS reviews_count
        FROM deal_reviews
        GROUP BY deal_id
    ");
    if ($rr) {
        while ($row = $rr->fetch_assoc()) {
            $homeReviewData[$row['deal_id']] = $row;
        }
    }
}

// ── FETCH RATINGS from user_deal_reviews (user deals) ──
$homeUdReviewData = [];
$udTableCheck = $conn->query("SHOW TABLES LIKE 'user_deal_reviews'");
if ($udTableCheck && $udTableCheck->num_rows > 0) {
    $urr = $conn->query("
        SELECT ud_id,
               ROUND(AVG(rating), 1) AS avg_rating,
               COUNT(*) AS reviews_count
        FROM user_deal_reviews
        GROUP BY ud_id
    ");
    if ($urr) {
        while ($row = $urr->fetch_assoc()) {
            $homeUdReviewData[$row['ud_id']] = $row;
        }
    }
}
?>

<!-- Modern Hero Section -->
<section class="malaysia-hero" style="height: 100vh; position: relative; margin-bottom: 0;">
    <div class="mh-slideshow">
        <div class="mh-bg active" style="background-image: url('images/pokhara_lake.png');"></div>
        <div class="mh-bg" style="background-image: url('images/everest_trek.png');"></div>
        <div class="mh-bg" style="background-image: url('images/chitwan_rhino.png');"></div>
        <div class="mh-bg" style="background-image: url('images/kathmandu_night_hero.png');"></div>
    </div>
    <div class="mh-overlay"></div>
    
    <style>
        .mh-slideshow { position: absolute; inset: 0; z-index: 1; }
        .mh-bg { position: absolute; inset: 0; background-size: cover; background-position: center; opacity: 0; transition: opacity 1.5s ease-in-out; }
        .mh-bg.active { opacity: 1; }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const slides = document.querySelectorAll('.mh-bg');
            let current = 0;
            setInterval(() => {
                slides[current].classList.remove('active');
                current = (current + 1) % slides.length;
                slides[current].classList.add('active');
            }, 5000);
        });
    </script>
    
    <div class="mh-content" style="padding-top: 100px;">
        <div class="mh-subtitle" style="text-shadow: 0 2px 4px rgba(0,0,0,0.5);">DISCOVER</div>
        <h1 class="mh-title" style="text-shadow: 0 4px 10px rgba(0,0,0,0.4); font-size: 130px;">NEPAL</h1>
        
        <form id="heroAjaxForm" action="deals-and-packages.php" method="GET" class="box-glass" style="margin-top: 40px; padding: 20px 40px; border-radius: 50px; display: flex; gap: 30px; align-items: center;">
            <div style="text-align: left;">
                <div style="font-size: 10px; font-weight: 800; letter-spacing: 2px; color: var(--primary-yellow); margin-bottom: 5px;">WHERE TO?</div>
                <input type="text" name="q" placeholder="Search destinations..." style="background: transparent; border: none; font-size: 16px; color: white; width: 200px; outline: none; font-family: inherit;">
            </div>
            <div style="width: 1px; height: 30px; background: rgba(255,255,255,0.3);"></div>
            <div style="text-align: left;">
                <div style="font-size: 10px; font-weight: 800; letter-spacing: 2px; color: var(--primary-yellow); margin-bottom: 5px;">EXPLORE</div>
                <select name="category" style="background: transparent; border: none; font-size: 16px; color: white; outline: none; font-family: inherit; appearance: none; cursor: pointer;">
                    <option value="" style="color: black;">All Experiences</option>
                    <option value="Trekking" style="color: black;">Trekking & Adventure</option>
                    <option value="Culture" style="color: black;">Culture & Heritage</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 12px 30px; border-radius: 30px; border: none;">DISCOVER</button>
        </form>
        
        <div style="margin-top: 20px; display: flex; align-items: center; justify-content: center; gap: 10px; color: rgba(255,255,255,0.9); font-size: 13px; font-weight: 600; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
            <svg style="width: 18px; height: 18px; fill: var(--primary-yellow);" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            <span>Best Time to Visit: <span style="color: var(--primary-yellow);">Sept - Nov</span> & <span style="color: var(--primary-yellow);">Mar - May</span></span>
        </div>
    </div>
</section>

<?php
$all_facts = [
    ['val' => '8848m', 'label' => 'Highest Peak'],
    ['val' => '125+', 'label' => 'Ethnic Groups'],
    ['val' => '10', 'label' => 'UNESCO Sites'],
    ['val' => '7000+', 'label' => 'Himalayan Peaks'],
    ['val' => '123', 'label' => 'Languages'],
    ['val' => '860+', 'label' => 'Bird Species'],
    ['val' => '6000+', 'label' => 'Rivers & Rivulets'],
    ['val' => '20%', 'label' => 'Protected Land'],
    ['val' => '1st', 'label' => 'Birthplace of Buddha'],
    ['val' => 'No #1', 'label' => 'Non-Rectangular Flag']
];
shuffle($all_facts);
$display_facts = array_slice($all_facts, 0, 4);
?>

<!-- Quick Facts Strip -->
<div class="facts-strip" style="background: var(--primary-blue); color: white; padding: 40px 0; position: relative; z-index: 10;">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
        <?php foreach($display_facts as $index => $fact): ?>
            <div style="text-align: center; flex: 1; min-width: 150px;">
                <div style="font-size: 32px; font-weight: 800; color: var(--primary-yellow);"><?php echo $fact['val']; ?></div>
                <div style="font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; margin-top: 5px;"><?php echo $fact['label']; ?></div>
            </div>
            <?php if($index < 3): ?>
                <div style="width: 1px; height: 40px; background: rgba(255,255,255,0.2);" class="fact-divider"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<style>
    @media (max-width: 768px) { .fact-divider { display: none; } }
</style>

<!-- Bento Box Category Section -->
<section class="bento-section" id="discover">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 50px;">
            <span style="font-family: 'Montserrat', sans-serif; font-size: 14px; letter-spacing: 3px; font-weight: 800; color: var(--primary-yellow); text-transform: uppercase;">WONDERS</span>
            <h2 class="section-title" style="margin-top: 10px;">Discover Your <span class="script-font" style="font-size:48px;">Journey</span></h2>
        </div>
        <div class="bento-grid">
            <a href="travel-ideas.php" class="bento-item large">
                <img src="images/annapurna_trek.png" alt="Annapurna">
                <div class="bento-overlay"><span class="bento-category">Mountains</span><h3 class="bento-title">Annapurna Circuit Expedition</h3></div>
            </a>
            <a href="travel-ideas.php" class="bento-item">
                <img src="images/bhaktapur_temple.png" alt="Bhaktapur">
                <div class="bento-overlay"><span class="bento-category">Culture</span><h3 class="bento-title">Bhaktapur Heritage Walk</h3></div>
            </a>
            <a href="travel-ideas.php" class="bento-item">
                <img src="images/food_drinks_nepal.png" alt="Food">
                <div class="bento-overlay"><span class="bento-category">Cuisine</span><h3 class="bento-title">Authentic Newari Taste</h3></div>
            </a>
            <a href="travel-ideas.php" class="bento-item wide">
                <img src="images/chitwan_rhino.png" alt="Chitwan">
                <div class="bento-overlay"><span class="bento-category">Adventure</span><h3 class="bento-title">Chitwan National Park Safari</h3></div>
            </a>
            <a href="travel-ideas.php" class="bento-item">
                <img src="images/lumbini_temple.png" alt="Lumbini">
                <div class="bento-overlay"><span class="bento-category">Heritage</span><h3 class="bento-title">Birthplace of Buddha</h3></div>
            </a>
            <a href="travel-ideas.php" class="bento-item">
                <img src="images/city_excitement_nepal.png" alt="City">
                <div class="bento-overlay"><span class="bento-category">City Life</span><h3 class="bento-title">Thamel Night Market</h3></div>
            </a>
            <a href="travel-ideas.php" class="bento-item">
                <img src="images/sarangkot_sunrise.png" alt="Sunrise">
                <div class="bento-overlay"><span class="bento-category">Nature</span><h3 class="bento-title">Sarangkot Sunrise View</h3></div>
            </a>
            <a href="travel-ideas.php" class="bento-item">
                <img src="images/pashupatinath_aarti.png" alt="Aarti">
                <div class="bento-overlay"><span class="bento-category">Spirituality</span><h3 class="bento-title">Pashupatinath Evening Aarti</h3></div>
            </a>
        </div>
    </div>
</section>


<!-- Nepal by Season Section -->
<section class="seasons-section" style="padding: 100px 0; background: #f9f9f9;">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 60px;">
            <span style="font-family: 'Montserrat', sans-serif; font-size: 14px; letter-spacing: 3px; font-weight: 800; color: var(--primary-blue); text-transform: uppercase;">EXPERIENCE</span>
            <h2 class="section-title" style="margin-top: 10px;">Nepal by <span class="script-font" style="font-size:48px;">Seasons</span></h2>
            <p style="max-width: 600px; margin: 20px auto 0; color: #666;">Every season tells a different story in the Himalayas. Choose your perfect time to explore.</p>
        </div>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 30px;">
            <div class="season-card" style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s;">
                <div style="height: 200px; background: url('images/annapurna_trek.png') center/cover;"></div>
                <div style="padding: 25px;">
                    <span style="color: #4caf50; font-weight: 800; font-size: 11px; letter-spacing: 1px; text-transform: uppercase;">MAR - MAY</span>
                    <h4 style="margin: 10px 0; font-size: 20px; font-weight: 800; color: var(--primary-blue);">Spring Bloom</h4>
                    <p style="font-size: 14px; color: #777;">Wild rhododendrons and perfect trekking weather.</p>
                </div>
            </div>
            <div class="season-card" style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s;">
                <div style="height: 200px; background: url('images/chitwan_rhino.png') center/cover;"></div>
                <div style="padding: 25px;">
                    <span style="color: #2196f3; font-weight: 800; font-size: 11px; letter-spacing: 1px; text-transform: uppercase;">JUN - AUG</span>
                    <h4 style="margin: 10px 0; font-size: 20px; font-weight: 800; color: var(--primary-blue);">Lush Monsoon</h4>
                    <p style="font-size: 14px; color: #777;">Green valleys, waterfalls and cultural festivals.</p>
                </div>
            </div>
            <div class="season-card" style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s;">
                <div style="height: 200px; background: url('images/everest_trek.png') center/cover;"></div>
                <div style="padding: 25px;">
                    <span style="color: #ff9800; font-weight: 800; font-size: 11px; letter-spacing: 1px; text-transform: uppercase;">SEP - NOV</span>
                    <h4 style="margin: 10px 0; font-size: 20px; font-weight: 800; color: var(--primary-blue);">Golden Autumn</h4>
                    <p style="font-size: 14px; color: #777;">Crisp clear skies and peak trekking season.</p>
                </div>
            </div>
            <div class="season-card" style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s;">
                <div style="height: 200px; background: url('images/kathmandu_night_hero.png') center/cover;"></div>
                <div style="padding: 25px;">
                    <span style="color: #9c27b0; font-weight: 800; font-size: 11px; letter-spacing: 1px; text-transform: uppercase;">DEC - FEB</span>
                    <h4 style="margin: 10px 0; font-size: 20px; font-weight: 800; color: var(--primary-blue);">Quiet Winter</h4>
                    <p style="font-size: 14px; color: #777;">Lower altitude tours and amazing snow views.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ DEALS & PACKAGES SECTION ══ -->
<section id="deals" style="padding: 80px 0; background: #ffffff; margin-top: 60px;">
    <div class="container" style="max-width: 1200px;">

        <div style="text-align: left; margin-bottom: 40px; border-bottom: 3px solid #eee; padding-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 20px;">
            <div>
                <span style="font-family: 'Montserrat', sans-serif; font-size: 14px; letter-spacing: 3px; font-weight: 800; color: var(--primary-blue); text-transform: uppercase;">DISCOVER</span>
                <h2 style="font-size: 52px; color: var(--primary-blue); font-weight: 800; margin: 10px 0 10px; line-height: 1;">New Deals & <span class="script-font" style="color: var(--primary-yellow); font-size: 64px; margin-left: -5px;">Packages</span></h2>
                <p style="color: var(--text-gray); font-size: 16px; font-weight: 500;">Freshly added offers for your next trip.</p>
            </div>
            <a href="deals-and-packages.php" style="display: inline-block; padding: 14px 32px; background: var(--primary-blue); color: white; text-decoration: none; font-weight: 800; font-family: inherit; letter-spacing: 1px; border-radius: 30px; font-size: 13px; white-space: nowrap;">VIEW ALL DEALS →</a>
        </div>

        <style>
            .home-deals-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 30px;
                margin-bottom: 60px;
            }
            @media (max-width: 900px) { .home-deals-grid { grid-template-columns: repeat(2, 1fr); } }
            @media (max-width: 580px) { .home-deals-grid { grid-template-columns: 1fr; } }

            .home-deal-card {
                display: flex;
                flex-direction: column;
                background: white;
                text-decoration: none;
                position: relative;
                border: 1px solid #eee;
                transition: all 0.3s ease;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 5px 15px rgba(0,0,0,0.05);
                color: inherit;
            }
            .home-deal-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 35px rgba(0,0,0,0.12);
                border-color: #ddd;
            }
            .home-deal-card.partner-card {
                border-color: rgba(201,162,39,0.35);
            }
            .home-deal-card.partner-card:hover {
                border-color: rgba(201,162,39,0.6);
                box-shadow: 0 15px 35px rgba(201,162,39,0.10);
            }
            .home-deal-card img {
                width: 100%;
                height: 240px;
                object-fit: cover;
                display: block;
                transition: transform 0.4s ease;
            }
            .home-deal-card:hover img { transform: scale(1.04); }
            .home-deal-img-wrap { position: relative; overflow: hidden; }
            .home-deal-badge {
                position: absolute;
                top: 14px;
                left: 14px;
                color: white;
                padding: 5px 13px;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 1px;
                border-radius: 4px;
                text-transform: uppercase;
                box-shadow: 0 3px 8px rgba(0,0,0,0.2);
            }
            .home-deal-days {
                position: absolute;
                top: 14px;
                right: 14px;
                background: white;
                color: #333;
                padding: 4px 10px;
                font-size: 10px;
                font-weight: 800;
                border-radius: 4px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .home-deal-discount {
                position: absolute;
                bottom: 14px;
                right: 14px;
                background: #e84393;
                color: white;
                padding: 4px 10px;
                font-size: 10px;
                font-weight: 800;
                border-radius: 4px;
            }
            .home-deal-partner-badge {
                position: absolute;
                bottom: 14px;
                left: 14px;
                background: rgba(201,162,39,0.88);
                color: #000;
                font-size: 9px;
                font-weight: 800;
                letter-spacing: 1.5px;
                padding: 4px 10px;
                border-radius: 20px;
                text-transform: uppercase;
            }
            .home-deal-body { padding: 22px 20px 18px; flex: 1; display: flex; flex-direction: column; }
            .home-deal-region { color: var(--primary-yellow, #f5a623); font-weight: 800; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 6px; }
            .home-deal-title { color: #222; font-size: 19px; font-weight: 800; line-height: 1.3; margin-bottom: 10px; }
            .home-deal-rating { display: flex; align-items: center; gap: 4px; margin-bottom: 14px; font-size: 13px; color: #f5a623; }
            .home-deal-rating span.count { color: #999; font-size: 12px; }
            .home-deal-partner-byline { font-size: 12px; color: #aaa; margin-bottom: 10px; }
            .home-deal-footer { margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f0; padding-top: 14px; }
            .home-deal-price { font-weight: 800; font-size: 16px; color: #285da1; }
            .home-deal-price small { display: block; font-size: 11px; color: #aaa; font-weight: 400; text-decoration: line-through; }
            .home-deal-btn { display: inline-block; padding: 10px 20px; background: #285da1; color: white; font-size: 11px; font-weight: 800; border-radius: 30px; letter-spacing: 1px; }
        </style>

        <?php
        $badge_colors = [
            'Trekking'   => '#2563eb',
            'Culture'    => '#7c3aed',
            'Leisure'    => '#059669',
            'Adventure'  => '#dc2626',
            'Nature'     => '#059669',
            'Spiritual'  => '#7c3aed',
        ];
        ?>

        <div class="home-deals-grid">
            <?php if (empty($home_deals)): ?>
                <p style="grid-column:1/-1; text-align:center; color:#999; padding: 3rem 0;">No deals available yet.</p>
            <?php endif; ?>

            <?php foreach ($home_deals as $deal):
                $isUserDeal = ($deal['deal_source'] ?? 'admin') === 'user_submitted';
                $dealId     = (int)$deal['id'];
                $cat        = $deal['category'] ?? 'General';
                $badgeClr   = $badge_colors[$cat] ?? '#285da1';
                $hasImg     = !empty($deal['image_url']) && strtoupper(trim($deal['image_url'])) !== 'NULL';

                // ── Discount ──
                $discount = 0;
                if (!empty($deal['original_price']) && (float)$deal['original_price'] > (float)$deal['price']) {
                    $discount = round((((float)$deal['original_price'] - (float)$deal['price']) / (float)$deal['original_price']) * 100);
                }

                // ── Ratings: pick correct table ──
                if (!$isUserDeal) {
                    $avgRating   = isset($homeReviewData[$dealId])   ? (float)$homeReviewData[$dealId]['avg_rating']   : 0;
                    $reviewCount = isset($homeReviewData[$dealId])   ? (int)$homeReviewData[$dealId]['reviews_count']  : 0;
                } else {
                    $avgRating   = isset($homeUdReviewData[$dealId]) ? (float)$homeUdReviewData[$dealId]['avg_rating']  : 0;
                    $reviewCount = isset($homeUdReviewData[$dealId]) ? (int)$homeUdReviewData[$dealId]['reviews_count'] : 0;
                }

                // ── Detail link ──
                $detailLink = $isUserDeal
                    ? "Ud_deal_details.php?ud={$dealId}"
                    : "deal-details.php?id={$dealId}";

                // ── Location label ──
                $locationLabel = !empty($deal['location']) ? $deal['location'] : ($deal['season'] ?? '');
            ?>
            <a href="<?= $detailLink ?>" class="home-deal-card<?= $isUserDeal ? ' partner-card' : '' ?>">
                <div class="home-deal-img-wrap">
                    <?php if ($hasImg): ?>
                        <img src="<?= htmlspecialchars($deal['image_url']) ?>" alt="<?= htmlspecialchars($deal['title']) ?>" loading="lazy">
                    <?php else: ?>
                        <div style="width:100%;height:240px;background:linear-gradient(135deg,#1a2a4a,#0d1020);display:flex;align-items:center;justify-content:center;font-size:64px;">
                            <?= !empty($deal['emoji']) ? $deal['emoji'] : '🏔️' ?>
                        </div>
                    <?php endif; ?>

                    <span class="home-deal-badge" style="background:<?= $badgeClr ?>;"><?= htmlspecialchars($cat) ?></span>

                    <?php if (!empty($deal['days'])): ?>
                        <span class="home-deal-days"><?= (int)$deal['days'] ?> Days</span>
                    <?php endif; ?>

                    <?php if ($discount > 0): ?>
                        <span class="home-deal-discount">-<?= $discount ?>% OFF</span>
                    <?php endif; ?>

                    <?php if ($isUserDeal): ?>
                        <span class="home-deal-partner-badge">Partner Listing</span>
                    <?php endif; ?>
                </div>

                <div class="home-deal-body">
                    <?php if (!empty($locationLabel)): ?>
                        <div class="home-deal-region"><?= htmlspecialchars($locationLabel) ?></div>
                    <?php endif; ?>

                    <div class="home-deal-title"><?= htmlspecialchars($deal['title']) ?></div>

                    <!-- Rating row -->
                    <div class="home-deal-rating">
                        <?php
                        $displayStars = $avgRating > 0 ? $avgRating : 0;
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= round($displayStars) ? '★' : '☆';
                        }
                        ?>
                        <?php if ($reviewCount > 0): ?>
                            <span class="count">(<?= $reviewCount ?> review<?= $reviewCount !== 1 ? 's' : '' ?>)</span>
                        <?php elseif ($avgRating === 0): ?>
                            <span class="count" style="color:#ccc; font-style:italic;">No reviews yet</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($isUserDeal && !empty($deal['submitted_by'])): ?>
                        <div class="home-deal-partner-byline">🏢 Listed by <?= htmlspecialchars($deal['submitted_by']) ?></div>
                    <?php endif; ?>

                    <div class="home-deal-footer">
                        <div class="home-deal-price">
                            <?php if (!empty($deal['original_price']) && (float)$deal['original_price'] > (float)$deal['price']): ?>
                                <small>NPR <?= number_format((float)$deal['original_price']) ?></small>
                            <?php endif; ?>
                            NPR <?= number_format((float)$deal['price']) ?>
                        </div>
                        <span class="home-deal-btn">VIEW DETAILS</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: -20px; margin-bottom: 20px;">
            <a href="deals-and-packages.php" style="display: inline-block; padding: 16px 48px; background: var(--primary-blue); color: white; text-decoration: none; font-weight: 800; font-family: inherit; letter-spacing: 2px; border-radius: 30px; font-size: 13px;">EXPLORE ALL DEALS</a>
        </div>

    </div>
</section>

<!-- ══════════════════════════════════════════════════════════
     LIVE CHAT WIDGET — shown only to logged-in non-admin users
════════════════════════════════════════════════════════════ -->
<?php
$chat_logged_in = isset($_SESSION['user_id']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin');
$chat_user_name = htmlspecialchars($_SESSION['full_name'] ?? 'Traveller');
?>

<?php if ($chat_logged_in): ?>
<style>
/* ── Widget shell ────────────────────────────────────────────── */
#nt-chat-fab {
    position: fixed; bottom: 28px; right: 28px; z-index: 9990;
    width: 60px; height: 60px; border-radius: 50%;
    background: var(--primary-blue, #285da1);
    border: none; cursor: pointer;
    box-shadow: 0 4px 20px rgba(40,93,161,0.45);
    display: flex; align-items: center; justify-content: center;
    transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.2s;
}
#nt-chat-fab:hover { transform: scale(1.1); box-shadow: 0 8px 28px rgba(40,93,161,0.55); }
#nt-chat-fab svg  { width: 28px; height: 28px; fill: #fff; transition: transform 0.3s; }
#nt-chat-fab.open svg { transform: rotate(90deg); }

#nt-chat-unread {
    position: absolute; top: -2px; right: -2px;
    background: #e84040; color: #fff;
    min-width: 20px; height: 20px; border-radius: 10px;
    font-size: 11px; font-weight: 800; padding: 0 5px;
    display: none; align-items: center; justify-content: center;
    border: 2px solid #fff; font-family: monospace;
}

#nt-chat-box {
    position: fixed; bottom: 100px; right: 28px; z-index: 9991;
    width: 360px;
    background: #fff; border-radius: 20px;
    box-shadow: 0 16px 50px rgba(0,0,0,0.18);
    display: flex; flex-direction: column; overflow: hidden;
    font-family: 'DM Sans', sans-serif;
    transform: scale(0.9) translateY(20px);
    opacity: 0; pointer-events: none;
    transition: all 0.28s cubic-bezier(0.34,1.56,0.64,1);
    max-height: 520px;
}
#nt-chat-box.open {
    transform: scale(1) translateY(0);
    opacity: 1; pointer-events: all;
}

/* ── Header ─────────────────────────────────────────────────── */
#nt-chat-hd {
    background: linear-gradient(135deg, #285da1, #1a3f72);
    padding: 16px 18px; display: flex; align-items: center; gap: 12px;
    color: #fff; flex-shrink: 0;
}
#nt-chat-hd .nt-av {
    width: 38px; height: 38px; border-radius: 50%;
    background: rgba(255,255,255,0.2);
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 15px; flex-shrink: 0;
}
#nt-chat-hd .nt-name  { font-weight: 700; font-size: 14px; }
#nt-chat-hd .nt-sub   { font-size: 11px; opacity: 0.75; margin-top: 2px; display: flex; align-items: center; gap: 5px; }
#nt-chat-hd .nt-online-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #4cdb8a; display: inline-block;
    animation: ntPulse 2s infinite;
}
@keyframes ntPulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
#nt-chat-close {
    margin-left: auto; background: rgba(255,255,255,0.15); border: none;
    color: #fff; width: 30px; height: 30px; border-radius: 50%;
    cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center;
    transition: background 0.15s; flex-shrink: 0;
}
#nt-chat-close:hover { background: rgba(255,255,255,0.3); }

/* ── Messages ───────────────────────────────────────────────── */
#nt-chat-msgs {
    flex: 1; overflow-y: auto; padding: 16px 14px;
    display: flex; flex-direction: column; gap: 10px;
    background: #f5f7fb; scroll-behavior: smooth;
    min-height: 200px;
}
#nt-chat-msgs::-webkit-scrollbar { width: 4px; }
#nt-chat-msgs::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }

.nt-bubble-wrap { display: flex; flex-direction: column; }
.nt-bubble-wrap.user { align-items: flex-end; }
.nt-bubble-wrap.admin { align-items: flex-start; }

.nt-bubble {
    max-width: 80%; padding: 10px 14px; border-radius: 16px;
    font-size: 13px; line-height: 1.5; word-break: break-word;
    position: relative; animation: ntBubble 0.2s ease;
}
@keyframes ntBubble { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:none} }
.nt-bubble.user {
    background: linear-gradient(135deg, #285da1, #1a4a8a);
    color: #fff; border-bottom-right-radius: 4px;
}
.nt-bubble.admin {
    background: #fff; color: #222;
    border: 1px solid #e4e8f0; border-bottom-left-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.nt-time {
    font-size: 10px; color: rgba(0,0,0,0.35); margin-top: 3px;
    padding: 0 4px;
}
.nt-bubble.user + .nt-time { text-align: right; }

.nt-empty {
    text-align: center; color: #aaa; font-size: 13px;
    margin: auto; padding: 30px 20px; line-height: 1.8;
}
.nt-empty-icon { font-size: 36px; margin-bottom: 10px; display: block; }

/* ── Typing indicator ──────────────────────────────────────── */
.nt-typing {
    display: flex; align-items: center; gap: 4px; padding: 10px 14px;
    background: #fff; border: 1px solid #e4e8f0;
    border-radius: 16px; border-bottom-left-radius: 4px;
    width: fit-content; display: none;
}
.nt-typing span {
    width: 6px; height: 6px; border-radius: 50%;
    background: #aaa; animation: ntDot 1.2s infinite;
}
.nt-typing span:nth-child(2){animation-delay:0.2s}
.nt-typing span:nth-child(3){animation-delay:0.4s}
@keyframes ntDot{ 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-5px)} }

/* ── Input ──────────────────────────────────────────────────── */
#nt-chat-footer {
    padding: 12px 14px; border-top: 1px solid #eaeef5;
    display: flex; gap: 8px; align-items: flex-end;
    background: #fff; flex-shrink: 0;
}
#nt-chat-input {
    flex: 1; border: 1.5px solid #e0e5ef; border-radius: 12px;
    padding: 10px 13px; font-size: 13px; font-family: inherit;
    resize: none; outline: none; max-height: 100px; overflow-y: auto;
    transition: border-color 0.2s; background: #f8fafd; line-height: 1.4;
}
#nt-chat-input:focus { border-color: #285da1; background: #fff; }
#nt-chat-input::placeholder { color: #b0b8c9; }
#nt-chat-send {
    background: linear-gradient(135deg, #285da1, #1a3f72);
    border: none; width: 40px; height: 40px; border-radius: 50%;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: transform 0.15s, box-shadow 0.15s;
    box-shadow: 0 3px 10px rgba(40,93,161,0.35);
}
#nt-chat-send:hover { transform: scale(1.08); box-shadow: 0 5px 15px rgba(40,93,161,0.5); }
#nt-chat-send:active { transform: scale(0.95); }
#nt-chat-send svg  { width: 18px; height: 18px; fill: #fff; }

/* ── Chat intro banner ──────────────────────────────────────── */
.nt-intro-banner {
    background: linear-gradient(135deg, rgba(40,93,161,0.08), rgba(40,93,161,0.03));
    border-bottom: 1px solid #eaeef5;
    padding: 12px 16px; font-size: 12px; color: #666; line-height: 1.5;
    flex-shrink: 0;
}

/* ── Mobile responsive ──────────────────────────────────────── */
@media (max-width: 420px) {
    #nt-chat-box { width: calc(100vw - 20px); right: 10px; bottom: 90px; }
    #nt-chat-fab { right: 16px; bottom: 20px; }
}
</style>

<!-- FAB Button -->
<button id="nt-chat-fab" onclick="ntChatToggle()" title="Chat with Nepal Travel Support" aria-label="Open chat">
    <svg viewBox="0 0 24 24" id="nt-fab-icon-chat">
        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
    </svg>
    <span id="nt-chat-unread" aria-label="Unread messages"></span>
</button>

<!-- Chat Panel -->
<div id="nt-chat-box" role="dialog" aria-label="Live Chat">
    <!-- Header -->
    <div id="nt-chat-hd">
        <div class="nt-av">🏔️</div>
        <div>
            <div class="nt-name">Nepal Travel Support</div>
            <div class="nt-sub">
                <span class="nt-online-dot"></span>
                We typically reply within a few hours
            </div>
        </div>
        <button id="nt-chat-close" onclick="ntChatToggle()" aria-label="Close chat">✕</button>
    </div>

    <!-- Intro Banner -->
    <div class="nt-intro-banner">
        👋 Hi, <strong><?= $chat_user_name ?></strong>! Ask us anything about Nepal travel — treks, packages, visas, or bookings.
    </div>

    <!-- Messages Area -->
    <div id="nt-chat-msgs" aria-live="polite">
        <div class="nt-empty" id="nt-empty-state">
            <span class="nt-empty-icon">💬</span>
            Start the conversation — we're here to help you plan your Nepal adventure!
        </div>
    </div>

    <!-- Input Footer -->
    <div id="nt-chat-footer">
        <textarea
            id="nt-chat-input"
            rows="1"
            placeholder="Type your message…"
            aria-label="Chat message"
            onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();ntSend();}"
            oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px';"
        ></textarea>
        <button id="nt-chat-send" onclick="ntSend()" aria-label="Send message" title="Send">
            <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </button>
    </div>
</div>

<script>
(function () {
    // ── Config ────────────────────────────────────────────────────
    const AJAX_URL      = 'chat_ajax.php';
    const POLL_MS_OPEN  = 3000;   // poll when chat is open
    const POLL_MS_BG    = 10000;  // poll when chat is closed

    // ── State ─────────────────────────────────────────────────────
    let pollTimer       = null;
    let lastMsgCount    = 0;
    let isOpen          = false;
    let isLoading       = false;

    // ── Toggle open/close ─────────────────────────────────────────
    function ntChatToggle() {
        isOpen = !isOpen;
        const box = document.getElementById('nt-chat-box');
        const fab = document.getElementById('nt-chat-fab');
        
        box.classList.toggle('open', isOpen);
        fab.classList.toggle('open', isOpen);

        if (isOpen) {
            clearInterval(pollTimer);
            ntLoad();
            pollTimer = setInterval(ntLoad, POLL_MS_OPEN);
            document.getElementById('nt-chat-unread').style.display = 'none';
            setTimeout(() => document.getElementById('nt-chat-input').focus(), 300);
        } else {
            clearInterval(pollTimer);
            pollTimer = setInterval(ntBgPoll, POLL_MS_BG);
        }
    }
    window.ntChatToggle = ntChatToggle;

    // ── Background poll (unread badge) ──────────────────────────
    async function ntBgPoll() {
        try {
            const fd = new FormData();
            fd.append('action', 'unread_count');
            
            const r = await fetch(AJAX_URL, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (!r.ok) return;
            const d = await r.json();
            
            if (d.ok && d.data && d.data.count > 0) {
                const badge = document.getElementById('nt-chat-unread');
                badge.textContent = d.data.count;
                badge.style.display = 'flex';
            }
        } catch (e) {
            console.error('Unread poll error:', e);
        }
    }

    // ── Load/fetch messages ───────────────────────────────────────
    async function ntLoad() {
        if (isLoading) return;
        isLoading = true;
        
        try {
            const fd = new FormData();
            fd.append('action', 'fetch');
            
            const r = await fetch(AJAX_URL, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (!r.ok) {
                isLoading = false;
                return;
            }
            
            const d = await r.json();
            if (d.ok && d.data && d.data.messages) {
                renderMessages(d.data.messages);
            }
        } catch (e) {
            console.error('Load messages error:', e);
        } finally {
            isLoading = false;
        }
    }

    // ── Render messages to DOM ────────────────────────────────────
    function renderMessages(msgs) {
        const container = document.getElementById('nt-chat-msgs');
        const emptyEl   = document.getElementById('nt-empty-state');

        if (!msgs || msgs.length === 0) {
            if (emptyEl) emptyEl.style.display = 'block';
            return;
        }
        
        if (emptyEl) emptyEl.style.display = 'none';

        // Only re-render if count changed
        if (msgs.length === lastMsgCount) return;
        lastMsgCount = msgs.length;

        // Clear old bubbles
        container.querySelectorAll('.nt-bubble-wrap').forEach(el => el.remove());

        // Build and insert new bubbles
        const frag = document.createDocumentFragment();
        
        msgs.forEach(m => {
            const wrap = document.createElement('div');
            wrap.className = 'nt-bubble-wrap ' + m.sender;

            const bubble = document.createElement('div');
            bubble.className = 'nt-bubble ' + m.sender;
            bubble.textContent = m.message;

            const timeEl = document.createElement('div');
            timeEl.className = 'nt-time';
            timeEl.textContent = formatTime(m.created_at);

            wrap.appendChild(bubble);
            wrap.appendChild(timeEl);
            frag.appendChild(wrap);
        });

        container.appendChild(frag);

        // Scroll to bottom
        setTimeout(() => {
            container.scrollTop = container.scrollHeight;
        }, 0);
    }

    // ── Send message ──────────────────────────────────────────────
    async function ntSend() {
        const inp = document.getElementById('nt-chat-input');
        const msg = inp.value.trim();
        
        if (!msg) return;

        // Clear input immediately
        inp.value = '';
        inp.style.height = 'auto';

        // Show optimistic bubble
        const container = document.getElementById('nt-chat-msgs');
        const emptyEl   = document.getElementById('nt-empty-state');
        
        if (emptyEl) emptyEl.style.display = 'none';

        const wrap = document.createElement('div');
        wrap.className = 'nt-bubble-wrap user';
        
        const bubble = document.createElement('div');
        bubble.className = 'nt-bubble user';
        bubble.textContent = msg;
        
        const timeEl = document.createElement('div');
        timeEl.className = 'nt-time';
        timeEl.textContent = 'just now';
        
        wrap.appendChild(bubble);
        wrap.appendChild(timeEl);
        container.appendChild(wrap);
        
        lastMsgCount++;
        container.scrollTop = container.scrollHeight;

        // Send to server
        try {
            const fd = new FormData();
            fd.append('action', 'send');
            fd.append('message', msg);
            
            const r = await fetch(AJAX_URL, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (r.ok) {
                const d = await r.json();
                if (!d.ok) {
                    console.error('Server error:', d.error);
                }
            }
        } catch (e) {
            console.error('Send error:', e);
        }

        // Reload after delay to sync timestamps
        setTimeout(ntLoad, 500);
    }
    window.ntSend = ntSend;

    // ── Format time display ───────────────────────────────────────
    function formatTime(str) {
        if (!str) return '';
        
        const d = new Date(str.replace(' ', 'T'));
        const now = new Date();
        const diff = (now - d) / 1000;
        
        if (diff < 60) return 'just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) {
            const hours = Math.floor(diff / 3600);
            return hours + 'h ago';
        }
        
        return d.toLocaleDateString([], { month: 'short', day: 'numeric' });
    }

    // ── Initialize ─────────────────────────────────────────────────
    window.addEventListener('load', () => {
        ntBgPoll();
        pollTimer = setInterval(ntBgPoll, POLL_MS_BG);
    });

    // ── Close on Escape ───────────────────────────────────────────
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && isOpen) ntChatToggle();
    });
})();
</script>
<?php endif; ?>


<!-- Instagram Social Feed Section -->
<section class="instagram-feed-section" style="padding: 100px 0; background: #fafafa; border-top: 1px solid #eee;">
    <div class="container">
        <div style="margin-bottom: 50px;">
            <span style="font-family: 'Montserrat', sans-serif; font-size: 14px; font-weight: 800; color: #285da1; letter-spacing: 2px; text-transform: uppercase;">COMMUNITY</span>
            <h2 style="font-family: 'Playfair Display', serif; font-size: 48px; color: #285da1; font-weight: 900; margin: 5px 0 0 0; display: flex; align-items: center; gap: 15px;">
                Truly Authentic <span class="script-font" style="color: #f5a623; font-size: 60px;">Stories</span>
                <span style="background: rgba(245, 166, 35, 0.1); color: #f5a623; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 800; letter-spacing: 1.5px; display: inline-flex; align-items: center; margin-left: 10px; height: 24px;">
                    <span class="live-indicator"></span> LIVE
                </span>
            </h2>
            <p style="color: #666; font-size: 16px; margin-top: 10px;">Real moments shared by travelers like you.</p>
        </div>

        <div id="homeCommunityGrid" class="community-feed-grid" style="margin-bottom: 60px;">
            <?php if (empty($community_posts)): ?>
                <?php 
                $fallback_images = [
                    ['src' => 'images/annapurna_trek.png', 'title' => 'Annapurna Trek', 'user' => 'travelnepal'],
                    ['src' => 'images/chitwan_rhino.png', 'title' => 'Chitwan Rhino', 'user' => 'wildlife_tm'],
                    ['src' => 'images/pokhara_lake.png', 'title' => 'Pokhara Lake', 'user' => 'nepal_diaries'],
                    ['src' => 'images/bhaktapur_temple.png', 'title' => 'Bhaktapur', 'user' => 'heritage_lover']
                ];
                foreach($fallback_images as $img): ?>
                <a href="experience.php" class="community-item">
                    <img src="<?php echo $img['src']; ?>" alt="<?php echo $img['title']; ?>">
                    <div class="insta-overlay" style="position: absolute; inset: 0; background: linear-gradient(to bottom, transparent, rgba(27, 58, 90, 0.8)); opacity: 0; transition: opacity 0.3s; display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; color: white;">
                        <span style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #f5a623; font-weight: 800;">@<?php echo $img['user']; ?></span>
                        <h4 style="font-size: 14px; margin: 5px 0 0; line-height: 1.3;"><?php echo $img['title']; ?></h4>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach($community_posts as $post): 
                    $img_path = htmlspecialchars($post['image_path']);
                    if (strpos(strtolower($img_path), 'logo') !== false || empty($img_path)) {
                        $img_path = 'images/sarangkot_sunrise.png';
                    }
                ?>
                <a href="experience.php" class="community-item">
                    <img src="<?php echo $img_path; ?>" alt="Experience">
                    <div class="insta-overlay" style="position: absolute; inset: 0; background: linear-gradient(to bottom, transparent, rgba(27, 58, 90, 0.8)); opacity: 0; transition: opacity 0.3s; display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; color: white;">
                        <span style="font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #f5a623; font-weight: 800;">@<?php echo htmlspecialchars($post['username']); ?></span>
                        <h4 style="font-size: 14px; margin: 5px 0 0; line-height: 1.3;"><?php echo mb_strimwidth(htmlspecialchars($post['caption']), 0, 50, "..."); ?></h4>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="text-align: center; display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
            <button type="button" id="loadMoreCommunityBtn" style="padding: 14px 40px; font-weight: 800; cursor: pointer; border-radius: 30px; border: 2px solid #285da1; background: transparent; color: #285da1; font-family: inherit; font-size: 14px; letter-spacing: 1px;">LOAD MORE STORIES</button>
            <a href="experience.php" style="padding: 14px 40px; font-weight: 800; cursor: pointer; border-radius: 30px; background: #285da1; color: white; text-decoration: none; font-family: inherit; font-size: 14px; letter-spacing: 1px;">EXPLORE COMMUNITY</a>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section" style="padding: 100px 0; background: white;">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 60px;">
            <span style="font-family: 'Montserrat', sans-serif; font-size: 14px; letter-spacing: 3px; font-weight: 800; color: var(--primary-yellow); text-transform: uppercase;">VOICES</span>
            <h2 class="section-title" style="margin-top: 10px;">Traveler <span class="script-font" style="font-size:48px;">Stories</span></h2>
        </div>
        <div class="stories-slider-container" style="position: relative; padding: 0 50px;">
            <div class="swiper travelerStoriesSwiper">
                <div class="swiper-wrapper" id="travelerStoriesGrid"></div>
                <div class="swiper-pagination" style="bottom: -30px;"></div>
            </div>
            <div class="swiper-button-next" style="color: var(--primary-yellow); right: 0;"></div>
            <div class="swiper-button-prev" style="color: var(--primary-yellow); left: 0;"></div>
        </div>
        <div style="text-align: center; margin-top: 80px;">
            <button id="postStoryBtn" style="padding: 16px 45px; background: var(--primary-yellow); color: #111; border: none; border-radius: 50px; font-size: 13px; font-weight: 800; letter-spacing: 2px; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 15px rgba(245, 166, 35, 0.2);">SHARE YOUR STORY</button>
        </div>
    </div>
</section>

<!-- Story Submission Modal -->
<div id="storyModal" class="admin-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(8px);">
    <div style="background: white; width: 90%; max-width: 500px; border-radius: 24px; overflow: hidden; animation: zoomIn 0.3s ease;">
        <div style="background: var(--primary-blue); padding: 30px; text-align: center; position: relative;">
            <h3 style="color: white; font-family: 'Playfair Display', serif; margin: 0;">Share Your Voice</h3>
            <p style="color: rgba(255,255,255,0.7); font-size: 13px; margin-top: 5px;">Help others discover the magic of Nepal</p>
            <button class="closeStoryModal" style="position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.1); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer;">×</button>
        </div>
        <form id="storyForm" style="padding: 30px;" enctype="multipart/form-data">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 11px; font-weight: 800; color: #1b3a5a; margin-bottom: 8px; text-transform: uppercase;">Your Name</label>
                <input type="text" name="name" required placeholder="e.g. Sarah Jenkins" style="width: 100%; padding: 12px; border: 1px solid #eee; border-radius: 12px; font-family: inherit;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 11px; font-weight: 800; color: #1b3a5a; margin-bottom: 8px; text-transform: uppercase;">Country</label>
                <input type="text" name="country" placeholder="e.g. Australia" style="width: 100%; padding: 12px; border: 1px solid #eee; border-radius: 12px; font-family: inherit;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 11px; font-weight: 800; color: #1b3a5a; margin-bottom: 8px; text-transform: uppercase;">Your Experience (Quote)</label>
                <textarea name="quote" required placeholder="Nepal exceeded every expectation..." rows="4" style="width: 100%; padding: 12px; border: 1px solid #eee; border-radius: 12px; font-family: inherit; resize: none;"></textarea>
            </div>
            <div style="margin-bottom: 25px;">
                <label style="display: block; font-size: 11px; font-weight: 800; color: #1b3a5a; margin-bottom: 8px; text-transform: uppercase;">Profile Picture</label>
                <input type="file" name="image" accept="image/*" style="width: 100%; font-size: 12px;">
            </div>
            <button type="submit" style="width: 100%; padding: 16px; background: var(--primary-yellow); color: #111; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; transition: 0.3s;">PUBLISH STORY</button>
        </form>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="assets/js/stories.js"></script>

<style>
#storyModal.active { display: flex !important; }
@keyframes zoomIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
</style>

<!-- Interactive Map Section -->
<section class="interactive-map-section" id="map-section" style="margin-top: 40px; padding-top: 60px; padding-bottom: 60px;">
    <div class="container section-header" style="text-align: center;">
        <h2 class="section-title">Interactive <span class="script-font" style="font-size:40px;">Map of Nepal</span></h2>
    </div>
    <style>
        #nepal-svg-container svg { filter: drop-shadow(0 15px 30px rgba(0,0,0,0.12)); width: 100%; height: auto; display: block; }
        #nepal-svg-container .district { fill: #dce8f0; stroke: #6b9ab8; stroke-width: 0.5; transition: all 0.3s ease; cursor: pointer; }
        #nepal-svg-container .district:hover { fill: #f0a500 !important; stroke: #cc8400; stroke-width: 1; }
    </style>
    <div class="container map-layout">
        <div class="map-visual" id="nepal-svg-container">
            <?php include __DIR__ . '/../includes/map.php'; ?>
        </div>
        <div class="map-details">
            <div class="province-card">
                <h3 id="province-title" class="province-name">Select a District</h3>
                <p id="province-desc" class="province-description">Experience the beautiful district of Nepal, a pristine destination waiting to be discovered.</p>
                <a href="travel-ideas.php" class="btn btn-outline" style="padding: 12px 30px; font-size: 12px; margin-top: 20px; display:inline-block;">Explore Region</a>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const districtPaths = document.querySelectorAll('.district');
        const titleEl = document.getElementById('province-title');
        const descEl  = document.getElementById('province-desc');
        const districtData = {
            'HUMLA':      { attractions: 'Limy Valley, Hilsa, Rara Lake Link' },
            'MUGU':       { attractions: 'Rara Lake (Deepest Lake), Gamgadhi' },
            'DOLPA':      { attractions: 'Shey Phoksundo Lake, Upper Dolpo Trek' },
            'MUSTANG':    { attractions: 'Lo Manthang, Muktinath Temple, Jomsom' },
            'KATHMANDU':  { attractions: 'Pashupatinath, Boudhanath, Swayambhunath' },
            'POKHARA':    { attractions: 'Phewa Lake, Sarangkot, World Peace Pagoda' },
            'CHITWAN':    { attractions: 'Chitwan National Park, Elephant Breeding Center' },
            'LUMBINI':    { attractions: 'Maya Devi Temple, Monastic Zone' },
            'SOLUKHUMBU': { attractions: 'Everest Base Camp, Namche Bazaar' },
            'KASKI':      { attractions: 'Pokhara Valley, Annapurna Range Views' }
        };
        districtPaths.forEach(path => {
            path.addEventListener('mouseenter', (e) => {
                const id   = e.target.getAttribute('id');
                const name = e.target.getAttribute('data-name') || id;
                path.style.fill = 'var(--primary-yellow)';
                titleEl.innerText = name;
                descEl.innerHTML = districtData[id]
                    ? `<strong>Top Attractions:</strong><br>${districtData[id].attractions}`
                    : `Experience the beautiful district of ${name}, a pristine destination waiting to be discovered.`;
            });
            path.addEventListener('mouseleave', () => { path.style.fill = ''; });
        });
    });
</script>

<?php include '../includes/footer.php'; ?>