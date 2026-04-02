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

</body>
</html>