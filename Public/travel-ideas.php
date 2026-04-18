<?php 
$current_page = 'travel-ideas.php';
include '../includes/header.php'; 

// Centralized Travel Ideas Data
include_once '../includes/travel-ideas-data.php';
?>

<style>
/* Existing styles... */
.idea-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
}
</style>

<style>
:root {
    --primary-blue: #1b3a5a;
    --primary-yellow: #f5a623;
    --text-muted: #666;
    --bg-light: #f8f9fa;
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
    padding: 12px 15px;
    margin-bottom: 8px;
    border: 1px solid #eee;
    background: white;
    text-align: left;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    color: var(--text-muted);
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

.idea-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #eee;
}

.idea-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
}

.card-img-wrapper {
    height: 250px;
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
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
}

.card-content {
    padding: 25px;
}

.card-title {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    color: var(--primary-blue);
    margin: 0 0 10px 0;
}

.card-desc {
    color: var(--text-muted);
    font-size: 14px;
    line-height: 1.6;
    margin: 0;
}

.hidden {
    display: none;
}

#clear-filters {
    font-size: 12px;
    color: #e74c3c;
    cursor: pointer;
    text-decoration: underline;
    margin-left: 10px;
}
</style>

<!-- Hero Section -->
<section class="hero-about" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('../images/hero_nepal.png'); height: 350px; display: flex; align-items: center; justify-content: center;">
    <div class="container" style="text-align: center;">
        <h1 class="script-font" style="color: var(--primary-yellow); font-size: 45px; margin-bottom: -10px; font-family: 'Great Vibes', cursive;">Inspiring</h1>
        <h1 class="sans-bold" style="color: white; font-size: 60px; text-transform: uppercase; letter-spacing: 3px; font-family: 'Playfair Display', serif;">Travel Ideas</h1>
        <p style="color: rgba(255,255,255,0.9); font-size: 18px; max-width: 600px; margin: 15px auto 0;">Explore the diverse beauty across the 7 provinces of Nepal.</p>
    </div>
</section>

<!-- Content Section -->
<section style="background: var(--bg-light); padding: 80px 0;">
    <div class="container" style="max-width: 1300px; display: grid; grid-template-columns: 280px 1fr; gap: 50px;">
        
        <!-- Sidebar -->
        <aside>
            <div style="background: white; border-radius: 15px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); position: sticky; top: 120px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="font-size: 16px; font-weight: 800; color: var(--primary-blue); letter-spacing: 1px; margin: 0;">BY PROVINCE</h3>
                    <span id="resetFilters" style="display:none; font-size: 11px; color: #888; cursor: pointer; text-decoration: underline;">RESET</span>
                </div>
                
                <div class="filter-group">
                    <button class="filter-btn active" data-filter="all">All Regions</button>
                    <button class="filter-btn" data-filter="koshi">Koshi Province</button>
                    <button class="filter-btn" data-filter="madhesh">Madhesh Province</button>
                    <button class="filter-btn" data-filter="bagmati">Bagmati Province</button>
                    <button class="filter-btn" data-filter="gandaki">Gandaki Province</button>
                    <button class="filter-btn" data-filter="lumbini">Lumbini Province</button>
                    <button class="filter-btn" data-filter="karnali">Karnali Province</button>
                    <button class="filter-btn" data-filter="sudurpashchim">Sudurpashchim Province</button>
                </div>
            </div>
        </aside>

        <!-- Grid -->
        <div>
            <div id="ideasGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px;">
                <?php foreach($travel_ideas as $idea): ?>
                <a href="travel-idea-detail.php?id=<?php echo $idea['id']; ?>" class="idea-card-link idea-card" data-category="<?php echo $idea['province_slug']; ?>">
                    <div class="card-img-wrapper">
                        <img src="<?php echo htmlspecialchars($idea['image']); ?>" alt="<?php echo htmlspecialchars($idea['title']); ?>">
                        <span class="province-badge"><?php echo htmlspecialchars($idea['province']); ?></span>
                    </div>
                    <div class="card-content">
                        <h2 class="card-title"><?php echo htmlspecialchars($idea['title']); ?></h2>
                        <p class="card-desc"><?php echo htmlspecialchars($idea['description']); ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Empty State -->
            <div id="noResults" class="hidden" style="text-align: center; padding: 100px 0;">
                <h3 style="color: var(--primary-blue);">No ideas found for this region.</h3>
                <p style="color: var(--text-muted);">Try selecting another province or clearing the filter.</p>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const cards = document.querySelectorAll('.idea-card');
    const noResults = document.getElementById('noResults');
    const resetBtn = document.getElementById('resetFilters');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            
            // Update active state
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Show/Hide Reset button
            if (filter === 'all') {
                resetBtn.style.display = 'none';
            } else {
                resetBtn.style.display = 'block';
            }

            // Filter logic
            let visibleCount = 0;
            cards.forEach(card => {
                if (filter === 'all' || card.getAttribute('data-category') === filter) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Handle no results
            if (visibleCount === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        });
    });

    resetBtn.addEventListener('click', function() {
        document.querySelector('[data-filter="all"]').click();
    });
});
</script>

<?php include '../includes/footer.php'; ?>