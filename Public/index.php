<?php 
include __DIR__ . '/../includes/header.php'; 
require_once __DIR__ . '/../user/db.php';
<?php include '../includes/header.php'; ?>

<!-- Sanskar Part -->
<!-- Travel Style Hero Section -->
<section class="nepal-hero">
    <div class="mh-bg" style="background-image: url('../images/pokhara_lake.png');"></div>
    <div class="mh-overlay"></div>
    
    <!-- Left/Right Nav Arrows -->
    <button class="mh-arrow mh-left">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7"/></svg>
    </button>
    <button class="mh-arrow mh-right">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
    </button>

    <div class="mh-content">
        <div class="mh-subtitle">WELCOME TO</div>
        <h1 class="mh-title">NEPAL</h1>
        <a href="#deals" class="btn mh-btn">LEARN MORE</a>
    </div>

    <!-- Bottom Category Bar -->
    <div class="mh-bottom-nav">
        <div class="container" style="padding: 0;">
            <ul class="mh-cat-list">
                <li class="mh-cat-item active">
                    <a href="#nature" class="mh-cat-link">NATURE & ADVENTURE</a>
                </li>
                <li class="mh-cat-item"><a href="#culture" class="mh-cat-link">CULTURE & HERITAGE</a></li>
                <li class="mh-cat-item"><a href="#food" class="mh-cat-link">FOOD & DRINKS</a></li>
                <li class="mh-cat-item"><a href="#city" class="mh-cat-link">CITY EXCITEMENT</a></li>
                <li class="mh-cat-item"><a href="#family" class="mh-cat-link">FAMILY FUN</a></li>
                <li class="mh-cat-item"><a href="#mountains" class="mh-cat-link">MOUNTAINS & TREKS</a></li>
                <li class="mh-cat-item"><a href="#deals" class="mh-cat-link">DEALS & PACKAGES</a></li>
            </ul>
        </div>
    </div>
</section>


<!-- Category Sections -->

<!-- Bijay Part -->
<!-- 1. Nature & Adventure -->
<section id="nature" class="container" style="padding-top: 60px;">
    <h2 class="section-title" style="margin-bottom: 30px;">Nature & <span class="script-font" style="font-size:40px; color:var(--primary-yellow);">Adventure</span></h2>
    <div class="grid-container">
        <!-- Card 1 -->
        <div class="idea-card">
            <div class="card-badge">3 DAYS 2 NIGHTS</div>
            <img src="../images/chitwan_rhino.png" alt="Chitwan" class="card-img">
            <div class="card-overlay"><span class="card-region">Terai Plains</span><h3 class="card-title">Chitwan National Park Jungle Safari</h3></div>
        </div>
        <!-- Card 2 -->
        <div class="idea-card">
            <div class="card-badge">1 DAY</div>
            <img src="../images/pokhara_lake.png" alt="Pokhara Lake" class="card-img">
            <div class="card-overlay"><span class="card-region">Gandaki Zone</span><h3 class="card-title">Phewa Lake Boating River Rafting</h3></div>
        </div>
    </div>
</section>

<!-- 2. Culture & Heritage -->
<section id="culture" class="container" style="padding-top: 60px;">
    <h2 class="section-title" style="margin-bottom: 30px;">Culture & <span class="script-font" style="font-size:40px; color:var(--primary-yellow);">Heritage</span></h2>
    <div class="grid-container">
        <div class="idea-card">
            <div class="card-badge">HALF DAY</div>
            <img src="../images/bhaktapur_temple.png" alt="Bhaktapur" class="card-img">
            <div class="card-overlay"><span class="card-region">Kathmandu Valley</span><h3 class="card-title">Bhaktapur Durbar Square Heritage Walk</h3></div>
        </div>
        <div class="idea-card">
            <div class="card-badge">2 DAYS 1 NIGHT</div>
            <img src="../images/lumbini_temple.png" alt="Lumbini" class="card-img">
            <div class="card-overlay"><span class="card-region">Lumbini Province</span><h3 class="card-title">Birthplace of Buddha Spiritual Tour</h3></div>
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

        <?php include __DIR__ . '/../includes/deals-data.php'; ?>
        
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
<!-- 3. Food & Drinks -->
<section id="food" class="container" style="padding-top: 60px;">
    <h2 class="section-title" style="margin-bottom: 30px;">Food & <span class="script-font" style="font-size:40px; color:var(--primary-yellow);">Drinks</span></h2>
    <div class="grid-container">
        <div class="idea-card">
            <div class="card-badge">1 DAY</div>
            <img src="../images/food_drinks_nepal.png" alt="Food" class="card-img">
            <div class="card-overlay"><span class="card-region">Kathmandu</span><h3 class="card-title">Authentic Newari Cuisine Tasting</h3></div>
        </div>


    </div>
</section>

<!-- Ramal Part -->
<!-- 4. City Excitement -->
<section id="city" class="container" style="padding-top: 60px;">
    <h2 class="section-title" style="margin-bottom: 30px;">City <span class="script-font" style="font-size:40px; color:var(--primary-yellow);">Excitement</span></h2>
    <div class="grid-container">
        <div class="idea-card">
            <div class="card-badge">1 DAY</div>
            <img src="../images/city_excitement_nepal.png" alt="City" class="card-img">
            <div class="card-overlay"><span class="card-region">Thamel</span><h3 class="card-title">Thamel Night Market & Live Music</h3></div>
        </div>
    </div>
</section>

<!-- 5. Family Fun -->
<section id="family" class="container" style="padding-top: 60px;">
    <h2 class="section-title" style="margin-bottom: 30px;">Family <span class="script-font" style="font-size:40px; color:var(--primary-yellow);">Fun</span></h2>
    <div class="grid-container">
        <div class="idea-card">
            <div class="card-badge">4 DAYS 3 NIGHTS</div>
            <img src="../images/family_fun_nepal.png" alt="Family" class="card-img">
            <div class="card-overlay"><span class="card-region">Chitwan</span><h3 class="card-title">Elephant Breeding Center & Village Walk</h3></div>
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
            <?php include __DIR__ . '/../includes/map.php'; ?>
<!-- 6. Mountains & Treks -->
<section id="mountains" class="container" style="padding-top: 60px;">
    <h2 class="section-title" style="margin-bottom: 30px;">Mountains & <span class="script-font" style="font-size:40px; color:var(--primary-yellow);">Treks</span></h2>
    <div class="grid-container">
        <div class="idea-card">
            <div class="card-badge">14 DAYS 13 NIGHTS</div>
            <img src="../images/annapurna_trek.png" alt="Annapurna" class="card-img">
            <div class="card-overlay"><span class="card-region">Annapurna Region</span><h3 class="card-title">Annapurna Circuit Expedition</h3></div>
        </div>
        <div class="idea-card">
            <div class="card-badge">12 DAYS 11 NIGHTS</div>
            <img src="../images/everest_trek.png" alt="Everest" class="card-img">
            <div class="card-overlay"><span class="card-region">Sagarmatha Zone</span><h3 class="card-title">Everest Base Camp Trekking</h3></div>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php include '../includes/footer.php'; ?>
