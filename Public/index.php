<?php 

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

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
        <div class="mh-bg active" style="background-image: url('../images/pokhara_lake.png');"></div>
        <div class="mh-bg" style="background-image: url('../images/everest_trek.png');"></div>
        <div class="mh-bg" style="background-image: url('../images/chitwan_rhino.png');"></div>
        <div class="mh-bg" style="background-image: url('../images/kathmandu_night_hero.png');"></div>
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
        <div class="mh-subtitle" style="text-shadow: 0 2px 4px rgba(0,0,0,0.5); color: var(--primary-yellow);">THE ULTIMATE</div>
        <h1 class="mh-title" style="text-shadow: 0 4px 10px rgba(0,0,0,0.4); font-size: 110px;">NEPAL TRAVEL</h1>
        
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
                <img src="../images/annapurna_trek.png" alt="Annapurna">
                <div class="bento-overlay"><span class="bento-category">Mountains</span><h3 class="bento-title">Annapurna Circuit Expedition</h3></div>
            </a>
            <a href="travel-ideas.php" class="bento-item">
                <img src="../images/bhaktapur_temple.png" alt="Bhaktapur">
                <div class="bento-overlay"><span class="bento-category">Culture</span><h3 class="bento-title">Bhaktapur Heritage Walk</h3></div>
            </a>
            <a href="travel-ideas.php" class="bento-item">
                <img src="../images/food_drinks_nepal.png" alt="Food">
                <div class="bento-overlay"><span class="bento-category">Cuisine</span><h3 class="bento-title">Authentic Newari Taste</h3></div>
            </a>
            <a href="travel-ideas.php" class="bento-item wide">
                <img src="../images/chitwan_rhino.png" alt="Chitwan">
                <div class="bento-overlay"><span class="bento-category">Adventure</span><h3 class="bento-title">Chitwan National Park Safari</h3></div>
            </a>
            <a href="travel-ideas.php" class="bento-item">
                <img src="../images/lumbini_temple.png" alt="Lumbini">
                <div class="bento-overlay"><span class="bento-category">Heritage</span><h3 class="bento-title">Birthplace of Buddha</h3></div>
            </a>
            <a href="travel-ideas.php" class="bento-item">
                <img src="../images/city_excitement_nepal.png" alt="City">
                <div class="bento-overlay"><span class="bento-category">City Life</span><h3 class="bento-title">Thamel Night Market</h3></div>
            </a>
            <a href="travel-ideas.php" class="bento-item">
                <img src="../images/sarangkot_sunrise.png" alt="Sunrise">
                <div class="bento-overlay"><span class="bento-category">Nature</span><h3 class="bento-title">Sarangkot Sunrise View</h3></div>
            </a>
            <a href="travel-ideas.php" class="bento-item">
                <img src="../images/pashupatinath_aarti.png" alt="Aarti">
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
                <div style="height: 200px; background: url('../images/annapurna_trek.png') center/cover;"></div>
                <div style="padding: 25px;">
                    <span style="color: #4caf50; font-weight: 800; font-size: 11px; letter-spacing: 1px; text-transform: uppercase;">MAR - MAY</span>
                    <h4 style="margin: 10px 0; font-size: 20px; font-weight: 800; color: var(--primary-blue);">Spring Bloom</h4>
                    <p style="font-size: 14px; color: #777;">Wild rhododendrons and perfect trekking weather.</p>
                </div>
            </div>
            <div class="season-card" style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s;">
                <div style="height: 200px; background: url('../images/chitwan_rhino.png') center/cover;"></div>
                <div style="padding: 25px;">
                    <span style="color: #2196f3; font-weight: 800; font-size: 11px; letter-spacing: 1px; text-transform: uppercase;">JUN - AUG</span>
                    <h4 style="margin: 10px 0; font-size: 20px; font-weight: 800; color: var(--primary-blue);">Lush Monsoon</h4>
                    <p style="font-size: 14px; color: #777;">Green valleys, waterfalls and cultural festivals.</p>
                </div>
            </div>
            <div class="season-card" style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s;">
                <div style="height: 200px; background: url('../images/everest_trek.png') center/cover;"></div>
                <div style="padding: 25px;">
                    <span style="color: #ff9800; font-weight: 800; font-size: 11px; letter-spacing: 1px; text-transform: uppercase;">SEP - NOV</span>
                    <h4 style="margin: 10px 0; font-size: 20px; font-weight: 800; color: var(--primary-blue);">Golden Autumn</h4>
                    <p style="font-size: 14px; color: #777;">Crisp clear skies and peak trekking season.</p>
                </div>
            </div>
            <div class="season-card" style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s;">
                <div style="height: 200px; background: url('../images/kathmandu_night_hero.png') center/cover;"></div>
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
                    ['src' => '../images/annapurna_trek.png', 'title' => 'Annapurna Trek', 'user' => 'travelnepal'],
                    ['src' => '../images/chitwan_rhino.png', 'title' => 'Chitwan Rhino', 'user' => 'wildlife_tm'],
                    ['src' => '../images/pokhara_lake.png', 'title' => 'Pokhara Lake', 'user' => 'nepal_diaries'],
                    ['src' => '../images/bhaktapur_temple.png', 'title' => 'Bhaktapur', 'user' => 'heritage_lover']
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
                        $img_path = '../images/sarangkot_sunrise.png';
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
<script src="../assets/js/stories.js"></script>

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


<!-- ═══════════════════════════════════════════════════════════════
     LIVE CHAT WIDGET
═══════════════════════════════════════════════════════════════ -->
<style>
/* ── Chat toggle button ── */
#nt-chat-toggle {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1b3a5a, #285da1);
    border: none;
    cursor: pointer;
    box-shadow: 0 6px 24px rgba(40,93,161,0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9990;
    transition: transform 0.2s, box-shadow 0.2s;
}
#nt-chat-toggle:hover { transform: scale(1.08); box-shadow: 0 8px 30px rgba(40,93,161,0.55); }
#nt-chat-toggle svg   { width: 26px; height: 26px; fill: white; transition: opacity 0.2s; }
#nt-chat-toggle .ico-close { display: none; }
#nt-chat-toggle.open .ico-open  { display: none; }
#nt-chat-toggle.open .ico-close { display: block; }

/* Unread badge on toggle */
#nt-chat-badge {
    position: absolute;
    top: -4px; right: -4px;
    background: #e84393;
    color: white;
    font-size: 10px;
    font-weight: 800;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    border: 2px solid white;
    font-family: monospace;
}
#nt-chat-badge.show { display: flex; }

/* ── Chat window ── */
#nt-chat-window {
    position: fixed;
    bottom: 100px;
    right: 28px;
    width: 360px;
    max-width: calc(100vw - 40px);
    height: 500px;
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.18);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    z-index: 9989;
    transform: scale(0.92) translateY(20px);
    opacity: 0;
    pointer-events: none;
    transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1), opacity 0.2s;
}
#nt-chat-window.open {
    transform: scale(1) translateY(0);
    opacity: 1;
    pointer-events: all;
}

/* Header */
.nch-header {
    background: linear-gradient(135deg, #1b3a5a, #285da1);
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}
.nch-avatar {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
    border: 2px solid rgba(255,255,255,0.25);
}
.nch-header-info { flex: 1; }
.nch-header-name { font-size: 14px; font-weight: 800; color: white; }
.nch-header-status { font-size: 11px; color: rgba(255,255,255,0.7); display: flex; align-items: center; gap: 5px; margin-top: 2px; }
.nch-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #4caf50; animation: pulse-green 2s infinite; }
@keyframes pulse-green { 0%,100%{opacity:1} 50%{opacity:0.5} }

/* Messages area */
.nch-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    background: #f7f9fc;
    scroll-behavior: smooth;
}
.nch-messages::-webkit-scrollbar { width: 4px; }
.nch-messages::-webkit-scrollbar-track { background: transparent; }
.nch-messages::-webkit-scrollbar-thumb { background: #cdd6e0; border-radius: 2px; }

/* Bubble */
.nch-bubble {
    max-width: 82%;
    padding: 10px 14px;
    border-radius: 16px;
    font-size: 13.5px;
    line-height: 1.5;
    word-break: break-word;
    animation: bubbleIn 0.2s ease;
}
@keyframes bubbleIn { from { opacity:0; transform:translateY(6px) } to { opacity:1; transform:none } }
.nch-bubble.user {
    align-self: flex-end;
    background: linear-gradient(135deg, #285da1, #1b3a5a);
    color: white;
    border-bottom-right-radius: 4px;
}
.nch-bubble.admin {
    align-self: flex-start;
    background: white;
    color: #222;
    border-bottom-left-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
}
.nch-time {
    font-size: 10px;
    opacity: 0.55;
    margin-top: 3px;
    text-align: right;
}
.nch-bubble.admin .nch-time { text-align: left; }

/* Welcome message */
.nch-welcome {
    text-align: center;
    padding: 20px 16px;
    color: #999;
    font-size: 12.5px;
    line-height: 1.6;
}
.nch-welcome strong { color: #285da1; display: block; font-size: 15px; margin-bottom: 6px; }

/* Typing indicator */
.nch-typing {
    display: none;
    align-self: flex-start;
    background: white;
    border-radius: 16px;
    border-bottom-left-radius: 4px;
    padding: 10px 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.07);
    gap: 4px;
    align-items: center;
}
.nch-typing.show { display: flex; }
.nch-typing span {
    width: 7px; height: 7px;
    background: #b0bec5;
    border-radius: 50%;
    animation: typingBounce 1.2s infinite;
}
.nch-typing span:nth-child(2) { animation-delay: 0.2s; }
.nch-typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes typingBounce { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-6px)} }

/* Input area */
.nch-footer {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 14px;
    border-top: 1px solid #eef0f4;
    background: white;
    flex-shrink: 0;
}
.nch-input {
    flex: 1;
    padding: 10px 14px;
    border: 1.5px solid #e0e6ef;
    border-radius: 24px;
    font-size: 13.5px;
    font-family: inherit;
    outline: none;
    resize: none;
    max-height: 100px;
    line-height: 1.4;
    transition: border-color 0.2s;
    background: #f7f9fc;
}
.nch-input:focus { border-color: #285da1; background: white; }
.nch-input::placeholder { color: #b0bec5; }
.nch-send {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #285da1, #1b3a5a);
    border: none;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: transform 0.15s, box-shadow 0.15s;
}
.nch-send:hover { transform: scale(1.08); box-shadow: 0 4px 12px rgba(40,93,161,0.35); }
.nch-send svg { width: 18px; height: 18px; fill: white; }
.nch-send:disabled { opacity: 0.45; cursor: not-allowed; transform: none; }
</style>

<!-- Chat Toggle Button -->
<button id="nt-chat-toggle" aria-label="Open chat">
    <svg class="ico-open" viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/></svg>
    <svg class="ico-close" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
    <span id="nt-chat-badge"></span>
</button>

<!-- Chat Window -->
<div id="nt-chat-window">
    <div class="nch-header">
        <div class="nch-avatar">🏔️</div>
        <div class="nch-header-info">
            <div class="nch-header-name">Nepal Travel Support</div>
            <div class="nch-header-status">
                <span class="nch-status-dot"></span>
                We're online — usually reply instantly
            </div>
        </div>
    </div>

    <div class="nch-messages" id="nch-messages">
        <div class="nch-welcome">
            <strong>👋 Welcome to Nepal Travel!</strong>
            Have a question about trekking, bookings, or Nepal in general? Ask us anything!
        </div>
        <div class="nch-typing" id="nch-typing">
            <span></span><span></span><span></span>
        </div>
    </div>

    <div class="nch-footer">
        <textarea
            id="nch-input"
            class="nch-input"
            placeholder="Type your message…"
            rows="1"
        ></textarea>
        <button class="nch-send" id="nch-send" aria-label="Send">
            <svg viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </button>
    </div>
</div>

<script>
(function () {
    const HANDLER = 'chat_handler.php'; // adjust path if needed
    const toggle  = document.getElementById('nt-chat-toggle');
    const win     = document.getElementById('nt-chat-window');
    const msgBox  = document.getElementById('nch-messages');
    const input   = document.getElementById('nch-input');
    const sendBtn = document.getElementById('nch-send');
    const badge   = document.getElementById('nt-chat-badge');
    const typing  = document.getElementById('nch-typing');

    let isOpen     = false;
    let lastMsgId  = 0;
    let pollTimer  = null;
    let unreadCnt  = 0;

    // ── Toggle open/close ──────────────────────────────────────
    toggle.addEventListener('click', () => {
        isOpen = !isOpen;
        toggle.classList.toggle('open', isOpen);
        win.classList.toggle('open', isOpen);
        if (isOpen) {
            clearUnread();
            input.focus();
            startPolling();
        } else {
            stopPolling();
        }
    });

    // ── Auto-resize textarea ───────────────────────────────────
    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 100) + 'px';
    });

    // ── Send on Enter (Shift+Enter = newline) ─────────────────
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    sendBtn.addEventListener('click', sendMessage);

    // ── Send message ───────────────────────────────────────────
    function sendMessage() {
        const txt = input.value.trim();
        if (!txt) return;

        // Optimistic render
        appendBubble('user', txt, 'Just now');
        input.value = '';
        input.style.height = 'auto';
        sendBtn.disabled = true;

        fetch(HANDLER, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=send&message=' + encodeURIComponent(txt)
        })
        .then(r => r.json())
        .then(d => {
            if (d.ok) lastMsgId = Math.max(lastMsgId, d.id);
        })
        .catch(console.error)
        .finally(() => { sendBtn.disabled = false; });
    }

    // ── Append bubble ──────────────────────────────────────────
    function appendBubble(sender, text, time) {
        // Remove welcome message if still present
        const welcome = msgBox.querySelector('.nch-welcome');
        if (welcome) welcome.remove();

        const wrap = document.createElement('div');
        wrap.style.display = 'flex';
        wrap.style.flexDirection = 'column';
        wrap.style.alignItems = sender === 'user' ? 'flex-end' : 'flex-start';

        const bbl = document.createElement('div');
        bbl.className = `nch-bubble ${sender}`;
        bbl.textContent = text;

        const t = document.createElement('div');
        t.className = 'nch-time';
        t.textContent = time || '';

        wrap.appendChild(bbl);
        wrap.appendChild(t);
        // Insert before typing indicator
        msgBox.insertBefore(wrap, typing);
        scrollToBottom();
    }

    function scrollToBottom() {
        msgBox.scrollTop = msgBox.scrollHeight;
    }

    function formatTime(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr.replace(' ', 'T'));
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // ── Polling ────────────────────────────────────────────────
    function startPolling() {
        poll();
        pollTimer = setInterval(poll, 3000);
    }
    function stopPolling() {
        clearInterval(pollTimer);
    }

    function poll() {
        fetch(`${HANDLER}?action=poll&since=${lastMsgId}`)
        .then(r => r.json())
        .then(d => {
            if (!d.ok || !d.messages.length) return;
            d.messages.forEach(m => {
                if (+m.id <= lastMsgId) return;
                lastMsgId = +m.id;
                if (m.sender === 'admin') {
                    appendBubble('admin', m.message, formatTime(m.created_at));
                    if (!isOpen) bumpUnread();
                } else {
                    // Our own message already rendered optimistically; skip
                }
            });
        })
        .catch(console.error);
    }

    // ── Unread badge ───────────────────────────────────────────
    function bumpUnread() {
        unreadCnt++;
        badge.textContent = unreadCnt > 9 ? '9+' : unreadCnt;
        badge.classList.add('show');
    }
    function clearUnread() {
        unreadCnt = 0;
        badge.classList.remove('show');
    }

    // Start polling silently even when closed so badge updates
    setTimeout(() => {
        setInterval(() => {
            if (isOpen) return; // handled by startPolling
            fetch(`${HANDLER}?action=poll&since=${lastMsgId}`)
            .then(r => r.json())
            .then(d => {
                if (!d.ok || !d.messages.length) return;
                d.messages.forEach(m => {
                    if (+m.id <= lastMsgId) return;
                    lastMsgId = +m.id;
                    if (m.sender === 'admin') bumpUnread();
                });
            })
            .catch(()=>{});
        }, 8000);
    }, 5000);
})();
</script>

<?php include '../includes/footer.php'; ?>