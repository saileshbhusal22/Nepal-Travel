<?php 
require_once __DIR__ . '/../config/db.php';
include '../includes/header.php'; 

// Fetch 3 random deals for the ad widget
$deals_query = $conn->query("
    SELECT id, title, price, original_price, image_url, category, location
    FROM deals 
    WHERE image_url IS NOT NULL AND image_url != 'NULL'
    ORDER BY RAND() 
    LIMIT 3
");
$ad_deals = $deals_query ? $deals_query->fetch_all(MYSQLI_ASSOC) : [];
?>

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

<!-- Deals Ad Widget -->
<div id="dealsAdWidget" class="deals-ad-widget">
    <button class="deals-ad-close" onclick="closeDealsAd()" aria-label="Close">&times;</button>
    <div class="deals-ad-header">
        <span class="deals-ad-badge">🔥 Hot Deals</span>
        <div class="deals-ad-timer" id="dealsAdTimer">
            <span id="timerSeconds">5</span>s
        </div>
    </div>
    <div class="deals-ad-carousel" id="dealsAdCarousel">
        <?php foreach ($ad_deals as $index => $deal): 
            $discount = 0;
            if (!empty($deal['original_price']) && (float)$deal['original_price'] > 0) {
                $discount = round((((float)$deal['original_price'] - (float)$deal['price']) / (float)$deal['original_price']) * 100);
            }
        ?>
        <div class="deal-ad-slide <?= $index === 0 ? 'active' : '' ?>">
            <div class="deal-ad-image" style="background-image: url('<?= htmlspecialchars($deal['image_url']) ?>')">
                <?php if ($discount > 0): ?>
                    <span class="deal-ad-discount">-<?= $discount ?>%</span>
                <?php endif; ?>
            </div>
            <div class="deal-ad-info">
                <span class="deal-ad-category"><?= htmlspecialchars($deal['category'] ?? '') ?></span>
                <h4 class="deal-ad-title"><?= htmlspecialchars($deal['title']) ?></h4>
                <?php if (!empty($deal['location'])): ?>
                    <p class="deal-ad-location">📍 <?= htmlspecialchars($deal['location']) ?></p>
                <?php endif; ?>
                <div class="deal-ad-price">
                    <?php if ($discount > 0): ?>
                        <span class="deal-ad-original">NPR <?= number_format((float)$deal['original_price']) ?></span>
                    <?php endif; ?>
                    <span class="deal-ad-current">NPR <?= number_format((float)$deal['price']) ?></span>
                </div>
                <a href="deals.php#deal-<?= $deal['id'] ?>" class="deal-ad-link">View Deal →</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if (count($ad_deals) > 1): ?>
    <div class="deal-ad-dots">
        <?php foreach ($ad_deals as $index => $deal): ?>
            <span class="deal-ad-dot <?= $index === 0 ? 'active' : '' ?>" onclick="goToDealSlide(<?= $index ?>)"></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

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

<style>
/* ═══════════════════════════════════════════════════════════
   DEALS AD WIDGET STYLES
═══════════════════════════════════════════════════════════ */
.deals-ad-widget {
    position: fixed;
    right: 20px;
    top: 50%;
    transform: translateY(-50%) translateX(0);
    width: 320px;
    background: linear-gradient(135deg, #1a1f35 0%, #0d1020 100%);
    border: 2px solid rgba(201, 162, 39, 0.3);
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6), 
                0 0 0 1px rgba(255, 255, 255, 0.05) inset;
    z-index: 9999;
    overflow: hidden;
    animation: slideInRight 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.deals-ad-widget.closing {
    animation: slideOutRight 0.4s ease forwards;
}

@keyframes slideInRight {
    from {
        transform: translateY(-50%) translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateY(-50%) translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    to {
        transform: translateY(-50%) translateX(400px);
        opacity: 0;
    }
}

.deals-ad-close {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #fff;
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    transition: all 0.2s ease;
    line-height: 1;
}

.deals-ad-close:hover {
    background: rgba(255, 80, 80, 0.8);
    border-color: rgba(255, 80, 80, 0.3);
    transform: rotate(90deg);
}

.deals-ad-header {
    background: linear-gradient(135deg, rgba(201, 162, 39, 0.2) 0%, rgba(37, 99, 235, 0.15) 100%);
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.deals-ad-badge {
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.deals-ad-timer {
    background: rgba(232, 67, 147, 0.2);
    border: 1px solid rgba(232, 67, 147, 0.4);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    color: #ff6b9d;
    font-family: 'DM Sans', monospace;
}

.deals-ad-carousel {
    position: relative;
    height: 380px;
}

.deal-ad-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.5s ease;
    pointer-events: none;
}

.deal-ad-slide.active {
    opacity: 1;
    pointer-events: auto;
}

.deal-ad-image {
    width: 100%;
    height: 180px;
    background-size: cover;
    background-position: center;
    position: relative;
}

.deal-ad-discount {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #e84393;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(232, 67, 147, 0.4);
}

.deal-ad-info {
    padding: 20px;
}

.deal-ad-category {
    display: inline-block;
    background: rgba(92, 63, 204, 0.2);
    border: 1px solid rgba(92, 63, 204, 0.4);
    color: #a78bfa;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}

.deal-ad-title {
    font-family: 'Playfair Display', serif;
    font-size: 16px;
    font-weight: 700;
    color: #fff;
    line-height: 1.3;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.deal-ad-location {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.5);
    margin-bottom: 12px;
}

.deal-ad-price {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}

.deal-ad-original {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.3);
    text-decoration: line-through;
}

.deal-ad-current {
    font-size: 20px;
    font-weight: 700;
    color: #c9a227;
}

.deal-ad-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #2563eb;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.deal-ad-link:hover {
    background: #1d4ed8;
    transform: translateX(4px);
}

.deal-ad-dots {
    position: absolute;
    bottom: 10px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 6px;
    z-index: 5;
}

.deal-ad-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    cursor: pointer;
    transition: all 0.2s ease;
}

.deal-ad-dot.active {
    background: #c9a227;
    transform: scale(1.3);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .deals-ad-widget {
        right: 10px;
        left: 10px;
        width: auto;
        max-width: 340px;
        margin: 0 auto;
    }
}

@media (max-width: 480px) {
    .deals-ad-widget {
        width: calc(100% - 20px);
        right: 10px;
        left: 10px;
        top: auto;
        bottom: 20px;
        transform: none;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateY(400px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        to {
            transform: translateY(400px);
            opacity: 0;
        }
    }
}
</style>

<script>
/* ═══════════════════════════════════════════════════════════
   DEALS AD WIDGET FUNCTIONALITY - APPEARS EVERY 20 SECONDS
═══════════════════════════════════════════════════════════ */
(function() {
    const widget = document.getElementById('dealsAdWidget');
    if (!widget) return;

    let autoCloseTimer = null;
    let countdownInterval = null;
    let reappearTimer = null;
    let currentSlide = 0;
    const slides = document.querySelectorAll('.deal-ad-slide');
    const dots = document.querySelectorAll('.deal-ad-dot');
    const AUTO_CLOSE_SECONDS = 5;
    const REAPPEAR_SECONDS = 20; // Show again after 20 seconds
    const SLIDE_INTERVAL = 3000; // 3 seconds per slide
    let slideInterval = null;

    // Start countdown timer
    function startCountdown() {
        let secondsLeft = AUTO_CLOSE_SECONDS;
        const timerEl = document.getElementById('timerSeconds');
        
        countdownInterval = setInterval(() => {
            secondsLeft--;
            if (timerEl) {
                timerEl.textContent = secondsLeft;
            }
            
            if (secondsLeft <= 0) {
                clearInterval(countdownInterval);
            }
        }, 1000);
    }

    // Reset countdown display
    function resetCountdown() {
        const timerEl = document.getElementById('timerSeconds');
        if (timerEl) {
            timerEl.textContent = AUTO_CLOSE_SECONDS;
        }
    }

    // Auto-close after 5 seconds
    function startAutoClose() {
        autoCloseTimer = setTimeout(() => {
            closeDealsAdTemporary();
        }, AUTO_CLOSE_SECONDS * 1000);
    }

    // Show the widget
    function showWidget() {
        widget.classList.remove('closing');
        widget.style.display = 'block';
        
        // Reset to first slide
        currentSlide = 0;
        slides.forEach((slide, index) => {
            slide.classList.toggle('active', index === 0);
        });
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === 0);
        });
        
        // Restart timers
        resetCountdown();
        startCountdown();
        startAutoClose();
        startSlideShow();
    }

    // Schedule next appearance
    function scheduleNextAppearance() {
        reappearTimer = setTimeout(() => {
            showWidget();
        }, REAPPEAR_SECONDS * 1000);
    }

    // Slide carousel functionality
    function goToDealSlide(index) {
        if (!slides.length) return;
        
        slides[currentSlide].classList.remove('active');
        if (dots[currentSlide]) dots[currentSlide].classList.remove('active');
        
        currentSlide = index;
        
        slides[currentSlide].classList.add('active');
        if (dots[currentSlide]) dots[currentSlide].classList.add('active');
    }

    function nextSlide() {
        const nextIndex = (currentSlide + 1) % slides.length;
        goToDealSlide(nextIndex);
    }

    function startSlideShow() {
        if (slideInterval) {
            clearInterval(slideInterval);
        }
        if (slides.length > 1) {
            slideInterval = setInterval(nextSlide, SLIDE_INTERVAL);
        }
    }

    function stopSlideShow() {
        if (slideInterval) {
            clearInterval(slideInterval);
        }
    }

    // Pause auto-close and slideshow on hover
    widget.addEventListener('mouseenter', () => {
        if (autoCloseTimer) {
            clearTimeout(autoCloseTimer);
        }
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
        stopSlideShow();
    });

    // Resume on mouse leave
    widget.addEventListener('mouseleave', () => {
        startAutoClose();
        startCountdown();
        startSlideShow();
    });

    // Initialize - show on page load
    startCountdown();
    startAutoClose();
    startSlideShow();

    // Close temporarily (will reappear)
    window.closeDealsAdTemporary = function() {
        if (autoCloseTimer) clearTimeout(autoCloseTimer);
        if (countdownInterval) clearInterval(countdownInterval);
        stopSlideShow();
        
        widget.classList.add('closing');
        setTimeout(() => {
            widget.style.display = 'none';
            scheduleNextAppearance();
        }, 400);
    };

    // Make goToDealSlide global for dot clicks
    window.goToDealSlide = goToDealSlide;
})();

// Close function for the X button
function closeDealsAd() {
    closeDealsAdTemporary();
}
</script>

<link rel="stylesheet" href="assets/css/styles.css"> 
</body>
</html>

<?php include '../includes/footer.php'; ?>