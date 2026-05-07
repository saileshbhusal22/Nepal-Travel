<?php include '../includes/header.php'; ?>

<!-- MAIN HERO SECTION: Landing page hero with navigation arrows and CTA -->
<!-- Background image with overlay effect -->
<section class="nepal-hero">
    <div class="mh-bg" style="background-image: url('../images/pokhara_lake.png');"></div>
    <div class="mh-overlay"></div>
    
    <!-- HERO NAVIGATION: Left/Right arrows for image carousel -->
    <button class="mh-arrow mh-left" title="Previous slide">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7"/></svg>
    </button>
    <button class="mh-arrow mh-right" title="Next slide">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5l7 7-7 7"/></svg>
    </button>

    <!-- HERO CONTENT: Tagline and call-to-action -->
    <div class="mh-content">
        <div class="mh-subtitle">WELCOME TO</div>
        <h1 class="mh-title">NEPAL</h1>
        <a href="#deals" class="btn mh-btn">LEARN MORE</a>
    </div>

    <!-- CATEGORY NAVIGATION BAR: Quick links to experience categories -->
    <div class="mh-bottom-nav">
        <div class="container" style="padding: 0;">
            <ul class="mh-cat-list">
                <!-- NATURE & ADVENTURE: Outdoor activities and trekking -->
                <li class="mh-cat-item active">
                    <a href="#nature" class="mh-cat-link">NATURE & ADVENTURE</a>
                </li>
                <!-- CULTURE & HERITAGE: Historical and cultural sites -->
                <li class="mh-cat-item"><a href="#culture" class="mh-cat-link">CULTURE & HERITAGE</a></li>
                <!-- FOOD & DRINKS: Culinary experiences -->
                <li class="mh-cat-item"><a href="#food" class="mh-cat-link">FOOD & DRINKS</a></li>
                <!-- CITY EXCITEMENT: Urban nightlife and entertainment -->
                <li class="mh-cat-item"><a href="#city" class="mh-cat-link">CITY EXCITEMENT</a></li>
                <!-- FAMILY FUN: Kid-friendly activities -->
                <li class="mh-cat-item"><a href="#family" class="mh-cat-link">FAMILY FUN</a></li>
                <!-- MOUNTAINS & TREKS: High-altitude expeditions -->
                <li class="mh-cat-item"><a href="#mountains" class="mh-cat-link">MOUNTAINS & TREKS</a></li>
                <!-- SPECIAL DEALS: Promotional packages -->
                <li class="mh-cat-item"><a href="#deals" class="mh-cat-link">DEALS & PACKAGES</a></li>
            </ul>
        </div>
    </div>
</section>


<!-- CATEGORY SHOWCASE SECTIONS -->
<!-- Each section displays curated experience cards for a specific category -->

<!-- NATURE & ADVENTURE SECTION -->
<section id="nature" class="container" style="padding-top: 60px;">
    <h2 class="section-title" style="margin-bottom: 30px;">Nature & <span class="script-font" style="font-size:40px; color:var(--primary-yellow);">Adventure</span></h2>
    <!-- Experience cards grid -->
    <div class="grid-container">
        <!-- CARD 1: Jungle Safari Experience -->
        <div class="idea-card">
            <div class="card-badge">3 DAYS 2 NIGHTS</div>
            <img src="../images/chitwan_rhino.png" alt="Chitwan" class="card-img">
            <div class="card-overlay"><span class="card-region">Terai Plains</span><h3 class="card-title">Chitwan National Park Jungle Safari</h3></div>
        </div>
        <!-- CARD 2: Lake Recreation Experience -->
        <div class="idea-card">
            <div class="card-badge">1 DAY</div>
            <img src="../images/pokhara_lake.png" alt="Pokhara Lake" class="card-img">
            <div class="card-overlay"><span class="card-region">Gandaki Zone</span><h3 class="card-title">Phewa Lake Boating River Rafting</h3></div>
        </div>
    </div>
</section>

<!-- CULTURE & HERITAGE SECTION -->
<section id="culture" class="container" style="padding-top: 60px;">
    <h2 class="section-title" style="margin-bottom: 30px;">Culture & <span class="script-font" style="font-size:40px; color:var(--primary-yellow);">Heritage</span></h2>
    <!-- Heritage and historical experience cards -->
    <div class="grid-container">
        <!-- CARD 1: Heritage Walk Experience -->
        <div class="idea-card">
            <div class="card-badge">HALF DAY</div>
            <img src="../images/bhaktapur_temple.png" alt="Bhaktapur" class="card-img">
            <div class="card-overlay"><span class="card-region">Kathmandu Valley</span><h3 class="card-title">Bhaktapur Durbar Square Heritage Walk</h3></div>
        </div>
        <!-- CARD 2: Spiritual Experience -->
        <div class="idea-card">
            <div class="card-badge">2 DAYS 1 NIGHT</div>
            <img src="../images/lumbini_temple.png" alt="Lumbini" class="card-img">
            <div class="card-overlay"><span class="card-region">Lumbini Province</span><h3 class="card-title">Birthplace of Buddha Spiritual Tour</h3></div>
        </div>
    </div>
</section>

<!-- FOOD & DRINKS SECTION: Culinary Experiences -->
<section id="food" class="container" style="padding-top: 60px;">
    <h2 class="section-title" style="margin-bottom: 30px;">Food & <span class="script-font" style="font-size:40px; color:var(--primary-yellow);">Drinks</span></h2>
    <!-- Gastronomic experience cards -->
    <div class="grid-container">
        <!-- CARD 1: Authentic Cuisine Tasting -->
        <div class="idea-card">
            <div class="card-badge">1 DAY</div>
            <img src="../images/food_drinks_nepal.png" alt="Food" class="card-img">
            <div class="card-overlay"><span class="card-region">Kathmandu</span><h3 class="card-title">Authentic Newari Cuisine Tasting</h3></div>
        </div>
    </div>
</section>

<!-- CITY EXCITEMENT SECTION: Urban Experiences -->
<section id="city" class="container" style="padding-top: 60px;">
    <h2 class="section-title" style="margin-bottom: 30px;">City <span class="script-font" style="font-size:40px; color:var(--primary-yellow);">Excitement</span></h2>
    <!-- Nightlife and entertainment experience cards -->
    <div class="grid-container">
        <!-- CARD 1: Night Market and Live Music -->
        <div class="idea-card">
            <div class="card-badge">1 DAY</div>
            <img src="../images/city_excitement_nepal.png" alt="City" class="card-img">
            <div class="card-overlay"><span class="card-region">Thamel</span><h3 class="card-title">Thamel Night Market & Live Music</h3></div>
        </div>
    </div>
</section>

<!-- FAMILY FUN SECTION: Kid-Friendly Activities -->
<section id="family" class="container" style="padding-top: 60px;">
    <h2 class="section-title" style="margin-bottom: 30px;">Family <span class="script-font" style="font-size:40px; color:var(--primary-yellow);">Fun</span></h2>
    <!-- Family-oriented experience cards -->
    <div class="grid-container">
        <!-- CARD 1: Animal Sanctuary and Cultural Tour -->
        <div class="idea-card">
            <div class="card-badge">4 DAYS 3 NIGHTS</div>
            <img src="../images/family_fun_nepal.png" alt="Family" class="card-img">
            <div class="card-overlay"><span class="card-region">Chitwan</span><h3 class="card-title">Elephant Breeding Center & Village Walk</h3></div>
        </div>
    </div>
</section>

<!-- MOUNTAINS & TREKS SECTION: High-Altitude Adventures -->
<section id="mountains" class="container" style="padding-top: 60px;">
    <h2 class="section-title" style="margin-bottom: 30px;">Mountains & <span class="script-font" style="font-size:40px; color:var(--primary-yellow);">Treks</span></h2>
    <!-- Advanced trekking and expedition cards -->
    <div class="grid-container">
        <!-- CARD 1: Annapurna Circuit Trek -->
        <div class="idea-card">
            <div class="card-badge">14 DAYS 13 NIGHTS</div>
            <img src="../images/annapurna_trek.png" alt="Annapurna" class="card-img">
            <div class="card-overlay"><span class="card-region">Annapurna Region</span><h3 class="card-title">Annapurna Circuit Expedition</h3></div>
        </div>
        <!-- CARD 2: Everest Base Camp Trek -->
        <div class="idea-card">
            <div class="card-badge">12 DAYS 11 NIGHTS</div>
            <img src="../images/everest_trek.png" alt="Everest" class="card-img">
            <div class="card-overlay"><span class="card-region">Sagarmatha Zone</span><h3 class="card-title">Everest Base Camp Trekking</h3></div>
        </div>
    </div>
</section>



<link rel="stylesheet" href="assets/css/styles.css"> 
</body>
</html>

<?php include '../includes/footer.php'; ?>


