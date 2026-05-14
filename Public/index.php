<?php 
include 'includes/header.php'; 
require_once 'user/db.php';

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
?>

<!-- Modern Hero Section -->
<section class="malaysia-hero" style="height: 100vh; position: relative; margin-bottom: 0;">
    <div class="mh-slideshow">
        <div class="mh-bg active" style="background-image: url('images/pokhara_lake.png');"></div>
        <div class="mh-bg" style="background-image: url('images/everest_trek.png');"></div>
        <div class="mh-bg" style="background-image: url('images/chitwan_rhino.png');"></div>
        <div class="mh-bg" style="background-image: url('images/kathmandu_night_hero.png');"></div>
    </div>
    <div class="mh-overlay" style="background: linear-gradient(to bottom, rgba(0,0,0,0.4), rgba(0,0,0,0.1) 60%, rgba(255,255,255,1));"></div>
    
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
        
        <!-- Glassmorphism floating search/nav -->
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
        
        <!-- Best Time to Visit Quick Hint -->
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
    @media (max-width: 768px) {
        .fact-divider { display: none; }
    }
</style>

<!-- Bento Box Category Section -->
<section class="bento-section" id="discover">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 50px;">
            <span style="font-family: 'Montserrat', sans-serif; font-size: 14px; letter-spacing: 3px; font-weight: 800; color: var(--primary-yellow); text-transform: uppercase;">WONDERS</span>
            <h2 class="section-title" style="margin-top: 10px;">Discover Your <span class="script-font" style="font-size:48px;">Journey</span></h2>
        </div>
        
        <div class="bento-grid">
            <!-- Large Card -->
            <a href="travel-ideas.php" class="bento-item large">
                <img src="images/annapurna_trek.png" alt="Annapurna">
                <div class="bento-overlay">
                    <span class="bento-category">Mountains</span>
                    <h3 class="bento-title">Annapurna Circuit Expedition</h3>
                </div>
            </a>
            
            <!-- Standard Cards -->
            <a href="travel-ideas.php" class="bento-item">
                <img src="images/bhaktapur_temple.png" alt="Bhaktapur">
                <div class="bento-overlay">
                    <span class="bento-category">Culture</span>
                    <h3 class="bento-title">Bhaktapur Heritage Walk</h3>
                </div>
            </a>
            
            <a href="travel-ideas.php" class="bento-item">
                <img src="images/food_drinks_nepal.png" alt="Food">
                <div class="bento-overlay">
                    <span class="bento-category">Cuisine</span>
                    <h3 class="bento-title">Authentic Newari Taste</h3>
                </div>
            </a>
            
            <!-- Wide Card -->
            <a href="travel-ideas.php" class="bento-item wide">
                <img src="images/chitwan_rhino.png" alt="Chitwan">
                <div class="bento-overlay">
                    <span class="bento-category">Adventure</span>
                    <h3 class="bento-title">Chitwan National Park Safari</h3>
                </div>
            </a>
            
            <!-- Standard Cards -->
            <a href="travel-ideas.php" class="bento-item">
                <img src="images/lumbini_temple.png" alt="Lumbini">
                <div class="bento-overlay">
                    <span class="bento-category">Heritage</span>
                    <h3 class="bento-title">Birthplace of Buddha</h3>
                </div>
            </a>
            
            <a href="travel-ideas.php" class="bento-item">
                <img src="images/city_excitement_nepal.png" alt="City">
                <div class="bento-overlay">
                    <span class="bento-category">City Life</span>
                    <h3 class="bento-title">Thamel Night Market</h3>
                </div>
            </a>

            <!-- Added cards to fill grid -->
            <a href="travel-ideas.php" class="bento-item">
                <img src="images/sarangkot_sunrise.png" alt="Sunrise">
                <div class="bento-overlay">
                    <span class="bento-category">Nature</span>
                    <h3 class="bento-title">Sarangkot Sunrise View</h3>
                </div>
            </a>

            <a href="travel-ideas.php" class="bento-item">
                <img src="images/pashupatinath_aarti.png" alt="Aarti">
                <div class="bento-overlay">
                    <span class="bento-category">Spirituality</span>
                    <h3 class="bento-title">Pashupatinath Evening Aarti</h3>
                </div>
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
            <!-- Spring -->
            <div class="season-card" style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s;">
                <div style="height: 200px; background: url('images/annapurna_trek.png') center/cover;"></div>
                <div style="padding: 25px;">
                    <span style="color: #4caf50; font-weight: 800; font-size: 11px; letter-spacing: 1px; text-transform: uppercase;">MAR - MAY</span>
                    <h4 style="margin: 10px 0; font-size: 20px; font-weight: 800; color: var(--primary-blue);">Spring Bloom</h4>
                    <p style="font-size: 14px; color: #777;">Wild rhododendrons and perfect trekking weather.</p>
                </div>
            </div>
            <!-- Summer -->
            <div class="season-card" style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s;">
                <div style="height: 200px; background: url('images/chitwan_rhino.png') center/cover;"></div>
                <div style="padding: 25px;">
                    <span style="color: #2196f3; font-weight: 800; font-size: 11px; letter-spacing: 1px; text-transform: uppercase;">JUN - AUG</span>
                    <h4 style="margin: 10px 0; font-size: 20px; font-weight: 800; color: var(--primary-blue);">Lush Monsoon</h4>
                    <p style="font-size: 14px; color: #777;">Green valleys, waterfalls and cultural festivals.</p>
                </div>
            </div>
            <!-- Autumn -->
            <div class="season-card" style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: transform 0.3s;">
                <div style="height: 200px; background: url('images/everest_trek.png') center/cover;"></div>
                <div style="padding: 25px;">
                    <span style="color: #ff9800; font-weight: 800; font-size: 11px; letter-spacing: 1px; text-transform: uppercase;">SEP - NOV</span>
                    <h4 style="margin: 10px 0; font-size: 20px; font-weight: 800; color: var(--primary-blue);">Golden Autumn</h4>
                    <p style="font-size: 14px; color: #777;">Crisp clear skies and peak trekking season.</p>
                </div>
            </div>
            <!-- Winter -->
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

<!-- Deals & Packages Snippet -->
<section class="booking-section" id="deals" style="padding: 80px 0; background: #ffffff; margin-top: 60px;">
    <div class="container" style="max-width: 1200px;">
        <!-- Header -->
        <div style="text-align: left; margin-bottom: 40px; border-bottom: 3px solid #eee; padding-bottom: 30px;">
            <span style="font-family: 'Montserrat', sans-serif; font-size: 14px; letter-spacing: 3px; font-weight: 800; color: var(--primary-blue); text-transform: uppercase;">DISCOVER</span>
            <h2 style="font-size: 52px; color: var(--primary-blue); font-weight: 800; margin: 10px 0 10px; line-height: 1;">New Deals & <span class="script-font" style="color: var(--primary-yellow); font-size: 64px; margin-left: -5px;">Packages</span></h2>
            <p style="color: var(--text-gray); font-size: 16px; font-weight: 500;">Freshly added offers for your next trip.</p>
        </div>
        
        <!-- Search Bar -->
        <form id="dealsAjaxForm" action="deals-and-packages.php" method="GET" style="display: flex; gap: 15px; margin-bottom: 50px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 11px; font-weight: 800; color: var(--primary-blue); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">CATEGORY</label>
                <select name="category" style="width: 100%; padding: 14px; border: 1px solid var(--primary-blue); border-radius: 0; font-family: inherit; font-weight: 700; color: var(--primary-blue); appearance: none; background: url('data:image/svg+xml;utf8,<svg fill=\"%231B3A5A\" viewBox=\"0 0 24 24\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M7 10l5 5 5-5z\"/></svg>') no-repeat right 10px center; background-size: 24px;">
                    <option value="">ALL</option>
                    <option value="TREKKING">TREKKING</option>
                    <option value="LEISURE">LEISURE</option>
                    <option value="CULTURE">CULTURE</option>
                </select>
            </div>
            <div style="flex: 2; min-width: 300px; display: flex; align-items: flex-end;">
                <input type="text" name="q" placeholder="Search Your Deals Here" style="flex: 1; padding: 14px; border: 1px solid #ccc; border-radius: 0; font-family: inherit; font-size: 15px; outline: none;">
                <button type="submit" style="padding: 14px 40px; background: #285da1; color: white; border: none; font-weight: 800; font-family: inherit; letter-spacing: 1px; cursor: pointer;">SEARCH</button>
            </div>
            <div style="display: flex; align-items: flex-end;">
                <a href="index.php#deals" style="display: inline-block; padding: 14px 40px; background: #eee; color: var(--primary-blue); text-decoration: none; border: none; font-weight: 800; font-family: inherit; letter-spacing: 1px; cursor: pointer;">CLEAR</a>
            </div>
        </form>

        <?php include 'includes/deals-data.php'; ?>
        
        <!-- Grid -->
        <div id="dealsGridContainer" class="deals-options-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-bottom: 60px;">
            <?php foreach($deals as $deal): ?>
            <a href="deal.php?id=<?php echo htmlspecialchars($deal['id']); ?>" style="display: flex; flex-direction: column; background: white; text-decoration: none; position: relative; border: 1px solid #eee; transition: all 0.3s ease; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                
                <div style="position: relative;">
                    <img src="<?php echo htmlspecialchars($deal['image']); ?>" style="width: 100%; height: 260px; object-fit: cover; display: block;">
                    <div style="position: absolute; top: 15px; right: 15px; display: flex; flex-direction: column; gap: 5px; align-items: flex-end;">
                        <span style="background: <?php echo htmlspecialchars($deal['badge_color']); ?>; color: white; padding: 6px 14px; font-size: 11px; font-weight: 800; letter-spacing: 1px; border-radius: 4px; text-transform: uppercase; box-shadow: 0 4px 10px rgba(0,0,0,0.2);"><?php echo htmlspecialchars($deal['category_badge']); ?></span>
                        <span style="background: white; color: #333; padding: 4px 10px; font-size: 10px; font-weight: 800; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"><?php echo htmlspecialchars($deal['duration']); ?></span>
                    </div>
                </div>

                <div style="padding: 25px 20px 20px; flex: 1; display: flex; flex-direction: column;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="color: var(--primary-yellow); font-weight: 800; font-size: 12px; letter-spacing: 1px; text-transform: uppercase;"><?php echo htmlspecialchars($deal['region']); ?></span>
                        <div style="display: flex; color: #f5a623; font-size: 12px;">
                            <?php 
                            $rating = $deal['rating'] ?? 5;
                            for($i=0; $i<5; $i++) echo $i < $rating ? '★' : '☆'; 
                            ?>
                        </div>
                    </div>
                    <h3 style="color: #333; font-size: 20px; font-weight: 800; line-height: 1.3; margin: 0 0 25px 0;"><?php echo htmlspecialchars($deal['title']); ?></h3>
                    
                    <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f0; pt: 15px; padding-top: 15px;">
                        <span style="font-weight: 800; font-size: 16px; color: #285da1;"><?php echo htmlspecialchars($deal['price']); ?></span>
                        <span style="display: inline-block; padding: 10px 20px; background: #285da1; color: white; font-size: 11px; font-weight: 800; border-radius: 30px; letter-spacing: 1px;">VIEW DETAILS</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
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
                <!-- Fallback content if no posts exist -->
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
                    // Fix consistency: replace any path that looks like a logo or placeholder with a fallback
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

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 40px;">
            <div style="padding: 40px; background: #fcfcfc; border-radius: 20px; border: 1px solid #eee; position: relative;">
                <div style="font-size: 60px; color: var(--primary-yellow); opacity: 0.2; position: absolute; top: 20px; left: 20px; line-height: 1;">“</div>
                <p style="color: #555; font-style: italic; margin-bottom: 30px; position: relative; z-index: 2;">Nepal exceeded every expectation. The Sherpa people's hospitality is as high as the mountains they live in. EBC was life-changing!</p>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: url('images/annapurna_trek.png') center/cover;"></div>
                    <div>
                        <h5 style="margin: 0; font-weight: 800; color: var(--primary-blue);">Sarah Jenkins</h5>
                        <span style="font-size: 12px; color: #999;">Australia</span>
                    </div>
                </div>
            </div>
            <div style="padding: 40px; background: #fcfcfc; border-radius: 20px; border: 1px solid #eee; position: relative;">
                <div style="font-size: 60px; color: var(--primary-yellow); opacity: 0.2; position: absolute; top: 20px; left: 20px; line-height: 1;">“</div>
                <p style="color: #555; font-style: italic; margin-bottom: 30px; position: relative; z-index: 2;">The food in Kathmandu and the peace in Pokhara — Nepal is a perfect blend of chaos and serenity. Can't wait to go back!</p>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: url('images/pokhara_lake.png') center/cover;"></div>
                    <div>
                        <h5 style="margin: 0; font-weight: 800; color: var(--primary-blue);">Marco Rossi</h5>
                        <span style="font-size: 12px; color: #999;">Italy</span>
                    </div>
                </div>
            </div>
            <div style="padding: 40px; background: #fcfcfc; border-radius: 20px; border: 1px solid #eee; position: relative;">
                <div style="font-size: 60px; color: var(--primary-yellow); opacity: 0.2; position: absolute; top: 20px; left: 20px; line-height: 1;">“</div>
                <p style="color: #555; font-style: italic; margin-bottom: 30px; position: relative; z-index: 2;">Seeing a tiger in Chitwan was the highlight of my year. Nepal is truly a wildlife lover's paradise.</p>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: url('images/chitwan_rhino.png') center/cover;"></div>
                    <div>
                        <h5 style="margin: 0; font-weight: 800; color: var(--primary-blue);">Liam O'Connell</h5>
                        <span style="font-size: 12px; color: #999;">Ireland</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Interactive Map Section -->
<section class="interactive-map-section" id="map-section" style="margin-top: 40px; padding-top: 60px; padding-bottom: 60px;">
    <div class="container section-header" style="text-align: center;">
        <h2 class="section-title">Interactive <span class="script-font" style="font-size:40px;">Map of Nepal</span></h2>
    </div>

    <style>
        #nepal-svg-container svg {
            filter: drop-shadow(0 15px 30px rgba(0,0,0,0.12));
            width: 100%;
            height: auto;
            display: block;
        }
        #nepal-svg-container .district {
            fill: #dce8f0;
            stroke: #6b9ab8;
            stroke-width: 0.5;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        #nepal-svg-container .district:hover {
            fill: #f0a500 !important;
            stroke: #cc8400;
            stroke-width: 1;
        }
    </style>
    <div class="container map-layout">
        <div class="map-visual" id="nepal-svg-container">
            <?php include 'includes/map.php'; ?>
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

<!-- Interactive Map Enhancement Script -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const districtPaths = document.querySelectorAll('.district');
        const titleEl = document.getElementById('province-title');
        const descEl = document.getElementById('province-desc');
        
        const districtData = {
            'HUMLA': { attractions: 'Limy Valley, Hilsa, Rara Lake Link' },
            'MUGU': { attractions: 'Rara Lake (Deepest Lake), Gamgadhi' },
            'DOLPA': { attractions: 'Shey Phoksundo Lake, Upper Dolpo Trek' },
            'MUSTANG': { attractions: 'Lo Manthang, Muktinath Temple, Jomsom' },
            'KATHMANDU': { attractions: 'Pashupatinath, Boudhanath, Swayambhunath' },
            'POKHARA': { attractions: 'Phewa Lake, Sarangkot, World Peace Pagoda' },
            'CHITWAN': { attractions: 'Chitwan National Park, Elephant Breeding Center' },
            'LUMBINI': { attractions: 'Maya Devi Temple, Monastic Zone' },
            'SOLUKHUMBU': { attractions: 'Everest Base Camp, Namche Bazaar' },
            'KASKI': { attractions: 'Pokhara Valley, Annapurna Range Views' }
        };

        districtPaths.forEach(path => {
            path.addEventListener('mouseenter', (e) => {
                const id = e.target.getAttribute('id');
                const name = e.target.getAttribute('data-name') || id;
                
                // Highlight
                path.style.fill = 'var(--primary-yellow)';
                
                // Update side panel
                titleEl.innerText = name;
                if (districtData[id]) {
                    descEl.innerHTML = `<strong>Top Attractions:</strong><br>${districtData[id].attractions}`;
                } else {
                    descEl.innerText = `Experience the beautiful district of ${name}, a pristine destination waiting to be discovered.`;
                }
            });

            path.addEventListener('mouseleave', (e) => {
                path.style.fill = ''; // Restore default
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
