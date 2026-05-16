<?php 
$current_page = 'travel-ideas.php';
include '../includes/header.php'; 

// Centralized Travel Ideas Data
include_once '../includes/travel-ideas-data.php';

// Helper to determine duration range for filtering
function getDurationRange($durationStr) {
    preg_match('/\d+/', $durationStr, $matches);
    $days = isset($matches[0]) ? (int)$matches[0] : 1;
    if ($days <= 3) return 'short';
    if ($days <= 7) return 'medium';
    return 'long';
}

$initialSearch = '';
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $initialSearch = trim($_GET['search']);
} elseif (isset($_GET['destination']) && trim($_GET['destination']) !== '') {
    $initialSearch = trim($_GET['destination']);
}
?>

<style>
:root {
    --primary-blue: #1b3a5a;
    --primary-yellow: #f5a623;
    --text-muted: #666;
    --bg-light: #f8f9fa;
    --card-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.hero-about {
    background-size: cover;
    background-position: center;
    position: relative;
    border-bottom: 5px solid var(--primary-yellow);
}

.filter-btn {
    display: block;
    width: 100%;
    padding: 10px 15px;
    margin-bottom: 6px;
    border: 1px solid #eee;
    background: white;
    text-align: left;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    color: var(--text-muted);
    font-size: 13px;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    background: #f0f4f8;
    color: var(--primary-blue);
}

.filter-btn.active {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
}

.search-container {
    margin-bottom: 30px;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 15px 25px 15px 50px;
    border: 1px solid #eee;
    border-radius: 30px;
    font-size: 16px;
    box-shadow: var(--card-shadow);
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-yellow);
    box-shadow: 0 10px 40px rgba(245, 166, 35, 0.1);
}

.search-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
}

.idea-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #eee;
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: inherit;
    height: 100%;
}

.idea-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
}

.card-img-wrapper {
    height: 220px;
    overflow: hidden;
    position: relative;
}

.card-img-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.idea-card:hover .card-img-wrapper img {
    transform: scale(1.1);
}

.province-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: var(--primary-yellow);
    color: white;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}

.season-badge {
    position: absolute;
    bottom: 15px;
    left: 15px;
    background: rgba(255,255,255,0.9);
    color: var(--primary-blue);
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
}

.card-content {
    padding: 20px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.card-title {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    color: var(--primary-blue);
    margin: 0 0 8px 0;
}

.card-desc {
    color: var(--text-muted);
    font-size: 13px;
    line-height: 1.5;
    margin: 0 0 15px 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-meta {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding-top: 15px;
    border-top: 1px solid #f5f5f5;
}

.meta-item {
    font-size: 11px;
    color: #888;
    display: flex;
    align-items: center;
    gap: 5px;
}

.meta-item strong {
    color: var(--primary-blue);
}

.view-btn {
    background: var(--primary-blue);
    color: white;
    text-align: center;
    padding: 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    transition: all 0.3s ease;
    margin-top: auto;
}

.idea-card:hover .view-btn {
    background: var(--primary-yellow);
}

/* Package Card Styles */
.package-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
    border: 1px solid #eee;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}

.package-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.package-price {
    font-size: 18px;
    color: var(--primary-yellow);
    font-weight: 800;
    margin-top: 10px;
    display: block;
}

.package-cta {
    display: block;
    width: 100%;
    padding: 10px;
    margin-top: 15px;
    border: 2px solid var(--primary-blue);
    color: var(--primary-blue);
    text-align: center;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.package-card:hover .package-cta {
    background: var(--primary-blue);
    color: white;
}

.hidden {
    display: none;
}
</style>

<!-- Hero Section -->
<section class="hero-about" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('../images/hero_nepal.png'); height: 350px; display: flex; align-items: center; justify-content: center;">
    <div class="container" style="text-align: center;">
        <h1 class="script-font" style="color: var(--primary-yellow); font-size: 45px; margin-bottom: -10px; font-family: 'Great Vibes', cursive;">Inspiring</h1>
        <h1 class="sans-bold" style="color: white; font-size: 60px; text-transform: uppercase; letter-spacing: 3px; font-family: 'Playfair Display', serif;">Travel Ideas</h1>
        <p style="color: rgba(255,255,255,0.9); font-size: 18px; max-width: 600px; margin: 15px auto 0;">Explore curated journeys and legendary landscapes across the Himalayas.</p>
    </div>
</section>

<!-- Content Section -->
<section style="background: var(--bg-light); padding: 60px 0;">
    <div class="container" style="max-width: 1300px; display: grid; grid-template-columns: 280px 1fr; gap: 40px;">
        
        <!-- Sidebar -->
        <aside>
            <div style="background: white; border-radius: 15px; padding: 25px; box-shadow: var(--card-shadow); position: sticky; top: 120px;">
                <!-- Province Filter -->
                <div style="margin-bottom: 25px;">
                    <h3 style="font-size: 14px; font-weight: 800; color: var(--primary-blue); letter-spacing: 1px; margin-bottom: 15px; display: flex; justify-content: space-between;">
                        PROVINCE
                        <span id="resetProvince" style="display:none; font-size: 9px; color: var(--primary-yellow); cursor: pointer;">RESET</span>
                    </h3>
                    <div class="filter-group" id="provinceFilters">
                        <button class="filter-btn active" data-province="all">All Regions</button>
                        <button class="filter-btn" data-province="koshi">Koshi</button>
                        <button class="filter-btn" data-province="madhesh">Madhesh</button>
                        <button class="filter-btn" data-province="bagmati">Bagmati</button>
                        <button class="filter-btn" data-province="gandaki">Gandaki</button>
                        <button class="filter-btn" data-province="lumbini">Lumbini</button>
                        <button class="filter-btn" data-province="karnali">Karnali</button>
                        <button class="filter-btn" data-province="sudurpashchim">Sudurpashchim</button>
                    </div>
                </div>

                <!-- Type Filter -->
                <div style="margin-bottom: 25px;">
                    <h3 style="font-size: 14px; font-weight: 800; color: var(--primary-blue); letter-spacing: 1px; margin-bottom: 15px; display: flex; justify-content: space-between;">
                        EXPERIENCE
                        <span id="resetType" style="display:none; font-size: 9px; color: var(--primary-yellow); cursor: pointer;">RESET</span>
                    </h3>
                    <div class="filter-group" id="typeFilters">
                        <button class="filter-btn active" data-type="all">All Types</button>
                        <button class="filter-btn" data-type="Trekking">Trekking</button>
                        <button class="filter-btn" data-type="Culture">Culture</button>
                        <button class="filter-btn" data-type="Wildlife">Wildlife</button>
                        <button class="filter-btn" data-type="Pilgrimage">Pilgrimage</button>
                        <button class="filter-btn" data-type="Adventure">Adventure</button>
                    </div>
                </div>

                <!-- Duration Filter -->
                <div style="margin-bottom: 0;">
                    <h3 style="font-size: 14px; font-weight: 800; color: var(--primary-blue); letter-spacing: 1px; margin-bottom: 15px; display: flex; justify-content: space-between;">
                        DURATION
                        <span id="resetDuration" style="display:none; font-size: 9px; color: var(--primary-yellow); cursor: pointer;">RESET</span>
                    </h3>
                    <div class="filter-group" id="durationFilters">
                        <button class="filter-btn active" data-duration="all">Any Length</button>
                        <button class="filter-btn" data-duration="short">1-3 Days</button>
                        <button class="filter-btn" data-duration="medium">4-7 Days</button>
                        <button class="filter-btn" data-duration="long">8+ Days</button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Grid -->
        <div>
            <!-- Search Bar -->
            <div class="search-container">
                <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="searchInput" class="search-input" placeholder="Search destinations, activities, or regions...">
            </div>

            <div id="ideasGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;">
                <?php foreach($travel_ideas as $idea): ?>
                <?php $durRange = getDurationRange($idea['duration'] ?? '1D'); ?>
                <a href="travel-idea-detail.php?id=<?php echo $idea['id']; ?>" 
                   class="idea-card" 
                   data-province="<?php echo $idea['province_slug']; ?>"
                   data-type="<?php echo htmlspecialchars($idea['type'] ?? 'Other'); ?>"
                   data-duration="<?php echo htmlspecialchars($durRange); ?>"
                   data-title="<?php echo strtolower($idea['title']); ?>"
                   data-desc="<?php echo strtolower($idea['description']); ?>">
                    <div class="card-img-wrapper">
                        <img src="<?php echo htmlspecialchars($idea['image']); ?>" alt="<?php echo htmlspecialchars($idea['title']); ?>">
                        <span class="province-badge"><?php echo htmlspecialchars($idea['province']); ?></span>
                        <span class="season-badge">🍂 <?php echo htmlspecialchars($idea['season'] ?? 'All Seasons'); ?></span>
                    </div>
                    <div class="card-content">
                        <h2 class="card-title"><?php echo htmlspecialchars($idea['title']); ?></h2>
                        <p class="card-desc"><?php echo htmlspecialchars($idea['description']); ?></p>
                        
                        <div class="card-meta">
                            <div class="meta-item">
                                <!-- Clock Icon -->
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <strong><?php echo htmlspecialchars($idea['duration'] ?? 'TBD'); ?></strong>
                            </div>
                            <div class="meta-item">
                                <!-- Mountain Icon -->
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m8 3 4 8 5-5 5 15H2L8 3z"></path></svg>
                                <strong><?php echo htmlspecialchars($idea['difficulty'] ?? 'Unknown'); ?></strong>
                            </div>
                        </div>

                        <div class="view-btn">View Journey Detail</div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Empty State -->
            <div id="noResults" class="hidden" style="text-align: center; padding: 100px 0;">
                <h3 style="color: var(--primary-blue); font-family: 'Playfair Display', serif; font-size: 24px;">No matching journeys found</h3>
                <p style="color: var(--text-muted); margin-top: 10px;">Try adjusting your filters or search terms.</p>
            </div>
        </div>
    </div>
</section>

<!-- "You Might Also Like" Section -->
<section style="padding: 80px 0; border-top: 1px solid #eee; background: white;">
    <div class="container">
        <h2 style="font-family: 'Playfair Display', serif; font-size: 32px; color: var(--primary-blue); text-align: center; margin-bottom: 40px;">Recommended Packages</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <a href="deal.php?id=himalayan-peak-retreat" class="package-card">
                <img src="https://images.unsplash.com/photo-1544735716-392fe2489ffa?q=80&w=600" style="width: 100%; height: 200px; object-fit: cover;">
                <div style="padding: 25px;">
                    <span style="color: var(--primary-yellow); font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Luxury Discovery</span>
                    <h4 style="margin: 10px 0; color: var(--primary-blue); font-size: 20px;">Himalayan Peak Retreat</h4>
                    <div style="display: flex; gap: 15px; color: #888; font-size: 12px; margin-bottom: 5px;">
                        <span>⏳ 12 Days</span>
                        <span>🔥 All Inclusive</span>
                    </div>
                    <span class="package-price">$1,450 <small style="font-size: 12px; color: #999; font-weight: 400;">/ person</small></span>
                    <span class="package-cta">Check Availability</span>
                </div>
            </a>
            <a href="deal.php?id=wild-west-expedition" class="package-card">
                <img src="https://images.unsplash.com/photo-1623492701902-47dc207df5dc?q=80&w=600" style="width: 100%; height: 200px; object-fit: cover;">
                <div style="padding: 25px;">
                    <span style="color: var(--primary-yellow); font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Adventure Solo</span>
                    <h4 style="margin: 10px 0; color: var(--primary-blue); font-size: 20px;">Wild West Expedition</h4>
                    <div style="display: flex; gap: 15px; color: #888; font-size: 12px; margin-bottom: 5px;">
                        <span>⏳ 18 Days</span>
                        <span>🔥 Remote Trek</span>
                    </div>
                    <span class="package-price">$2,100 <small style="font-size: 12px; color: #999; font-weight: 400;">/ person</small></span>
                    <span class="package-cta">Check Availability</span>
                </div>
            </a>
            <a href="deal.php?id=ancient-valley-wonders" class="package-card">
                <img src="../images/kathmandu_night_hero.png" style="width: 100%; height: 200px; object-fit: cover;">
                <div style="padding: 25px;">
                    <span style="color: var(--primary-yellow); font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">Cultural Heritage</span>
                    <h4 style="margin: 10px 0; color: var(--primary-blue); font-size: 20px;">Ancient Valley Wonders</h4>
                    <div style="display: flex; gap: 15px; color: #888; font-size: 12px; margin-bottom: 5px;">
                        <span>⏳ 5 Days</span>
                        <span>🔥 Guided Tour</span>
                    </div>
                    <span class="package-price">$550 <small style="font-size: 12px; color: #999; font-weight: 400;">/ person</small></span>
                    <span class="package-cta">Check Availability</span>
                </div>
            </a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const provinceBtns = document.querySelectorAll('#provinceFilters .filter-btn');
    const typeBtns = document.querySelectorAll('#typeFilters .filter-btn');
    const durationBtns = document.querySelectorAll('#durationFilters .filter-btn');
    const searchInput = document.getElementById('searchInput');
    const cards = document.querySelectorAll('.idea-card');
    const noResults = document.getElementById('noResults');

    const urlParams = new URLSearchParams(window.location.search);
    const searchInputValue = (urlParams.get('destination') || urlParams.get('search') || '').trim();
    const initialSearchQuery = searchInputValue.toLowerCase();
    
    let activeProvince = 'all';
    let activeType = 'all';
    let activeDuration = 'all';
    let searchQuery = initialSearchQuery || '';

    function applyFilters() {
        let visibleCount = 0;
        
        cards.forEach(card => {
            const cardProvince = card.getAttribute('data-province');
            const cardType = card.getAttribute('data-type');
            const cardDuration = card.getAttribute('data-duration');
            const cardTitle = card.getAttribute('data-title');
            const cardDesc = card.getAttribute('data-desc');
            
            const provinceMatch = (activeProvince === 'all' || cardProvince === activeProvince);
            const typeMatch = (activeType === 'all' || cardType === activeType);
            const durationMatch = (activeDuration === 'all' || cardDuration === activeDuration);
            const searchMatch = (searchQuery === '' || cardTitle.includes(searchQuery) || cardDesc.includes(searchQuery));

            if (provinceMatch && typeMatch && durationMatch && searchMatch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        noResults.classList.toggle('hidden', visibleCount > 0);
        
        document.getElementById('resetProvince').style.display = (activeProvince === 'all' ? 'none' : 'inline');
        document.getElementById('resetType').style.display = (activeType === 'all' ? 'none' : 'inline');
        document.getElementById('resetDuration').style.display = (activeDuration === 'all' ? 'none' : 'inline');
    }

    provinceBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            activeProvince = this.getAttribute('data-province');
            provinceBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyFilters();
        });
    });

    typeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            activeType = this.getAttribute('data-type');
            typeBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyFilters();
        });
    });

    durationBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            activeDuration = this.getAttribute('data-duration');
            durationBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyFilters();
        });
    });

    searchInput.addEventListener('input', function() {
        searchQuery = this.value.toLowerCase().trim();
        applyFilters();
    });

    if (searchInputValue) {
        searchInput.value = searchInputValue;
        applyFilters();
        document.getElementById('ideasGrid').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    document.getElementById('resetProvince').addEventListener('click', () => {
        document.querySelector('[data-province="all"]').click();
    });

    document.getElementById('resetType').addEventListener('click', () => {
        document.querySelector('[data-type="all"]').click();
    });

    document.getElementById('resetDuration').addEventListener('click', () => {
        document.querySelector('[data-duration="all"]').click();
    });
});
</script>

<?php include '../includes/footer.php'; ?>
