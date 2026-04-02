

<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);

include __DIR__ . '/../includes/header.php'; 
?>

<!-- Nepal Travel Style Hero Section -->
 <link rel="stylesheet" href="../assets/css/styles.css">
<section class="Nepal-hero">
    <div class="mh-bg" style="background-image: url('../images/pokhara_lake.png');"></div>
    <div class="mh-overlay"></div>
    
    <!-- Left/Right Nav Arrows -->
    <button class="mh-arrow mh-left">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 19l-7-7 7-7"/>
        </svg>
    </button>
    <button class="mh-arrow mh-right">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7"/>
        </svg>
    </button>

    <div class="mh-content">
        <div class="mh-subtitle">WELCOME TO</div>
        <h1 class="mh-title">NEPAL</h1>
        <a href="#deals" class="mh-btn">LEARN MORE</a>
    </div>

    <!-- Bottom Category Bar -->
    <div class="mh-bottom-nav">
        <div class="container">
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

<!-- 1. Nature & Adventure -->
<section id="nature" class="container" style="padding-top: 60px;">
    <h2 class="section-title" style="margin-bottom: 30px;">Nature & <span class="script-font" style="font-size:40px; color:var(--primary-yellow);">Adventure</span></h2>
    <div class="grid-container">
        <!-- Card 1 -->
        <div class="idea-card">
            <div class="card-badge">3 DAYS 2 NIGHTS</div>
            <img src="images/chitwan_rhino.png" alt="Chitwan" class="card-img">
            <div class="card-overlay"><span class="card-region">Terai Plains</span><h3 class="card-title">Chitwan National Park Jungle Safari</h3></div>
        </div>
        <!-- Card 2 -->
        <div class="idea-card">
            <div class="card-badge">1 DAY</div>
            <img src="images/pokhara_lake.png" alt="Pokhara Lake" class="card-img">
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
            <img src="images/bhaktapur_temple.png" alt="Bhaktapur" class="card-img">
            <div class="card-overlay"><span class="card-region">Kathmandu Valley</span><h3 class="card-title">Bhaktapur Durbar Square Heritage Walk</h3></div>
        </div>
        <div class="idea-card">
            <div class="card-badge">2 DAYS 1 NIGHT</div>
            <img src="images/lumbini_temple.png" alt="Lumbini" class="card-img">
            <div class="card-overlay"><span class="card-region">Lumbini Province</span><h3 class="card-title">Birthplace of Buddha Spiritual Tour</h3></div>
        </div>
    </div>
</section>

<!-- 3. Food & Drinks -->
<section id="food" class="container" style="padding-top: 60px;">
    <h2 class="section-title" style="margin-bottom: 30px;">Food & <span class="script-font" style="font-size:40px; color:var(--primary-yellow);">Drinks</span></h2>
    <div class="grid-container">
        <div class="idea-card">
            <div class="card-badge">1 DAY</div>
            <img src="images/food_drinks_nepal.png" alt="Food" class="card-img">
            <div class="card-overlay"><span class="card-region">Kathmandu</span><h3 class="card-title">Authentic Newari Cuisine Tasting</h3></div>



</body>
</html>
