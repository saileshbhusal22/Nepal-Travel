<?php 
// Set current page for active navigation highlight
$current_page = 'travel-ideas.php';

// Include header file
include 'includes/header.php'; 

// Static Travel Ideas Data (Array of travel destinations)
$travel_ideas = [
    [
        'id' => 'everest-base-camp',
        'title' => 'Everest Base Camp Trek',
        'province' => 'Koshi Province',
        'province_slug' => 'koshi',
        'image' => 'images/everest_trek.png',
        'description' => 'A journey to the foot of the world\'s highest peak, offering breathtaking Himalayan views.'
    ],
    // More travel ideas...
];
?>

<style>
/* Link wrapper for each card (removes default link styles) */
.idea-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
}
</style>

<style>
/* Root variables for consistent colors */
:root {
    --primary-blue: #1b3a5a;
    --primary-yellow: #f5a623;
    --text-muted: #666;
    --bg-light: #f8f9fa;
}

/* Hero section styling */
.hero-about {
    background-size: cover;
    background-position: center;
    position: relative;
    border-bottom: 5px solid var(--primary-yellow);
}

/* Sidebar filter buttons */
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

/* Hover effect for filter buttons */
.filter-btn:hover {
    background: #f0f4f8;
    color: var(--primary-blue);
}

/* Active filter button */
.filter-btn.active {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
}

/* Card container */
.idea-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #eee;
}

/* Hover animation for cards */
.idea-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
}

/* Image wrapper */
.card-img-wrapper {
    height: 250px;
    overflow: hidden;
    position: relative;
}

/* Image styling */
.card-img-wrapper img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

/* Zoom effect on hover */
.idea-card:hover .card-img-wrapper img {
    transform: scale(1.1);
}

/* Province badge on image */
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

/* Card text content */
.card-content {
    padding: 25px;
}

/* Title style */
.card-title {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    color: var(--primary-blue);
    margin: 0 0 10px 0;
}

/* Description text */
.card-desc {
    color: var(--text-muted);
    font-size: 14px;
    line-height: 1.6;
    margin: 0;
}

/* Hidden class for empty state */
.hidden {
    display: none;
}
</style>

<!-- Hero Section (Top Banner) -->
<section class="hero-about" style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('images/hero_nepal.png'); height: 350px; display: flex; align-items: center; justify-content: center;">
    <div class="container" style="text-align: center;">
        <!-- Subtitle -->
        <h1 class="script-font" style="color: var(--primary-yellow); font-size: 45px;">Inspiring</h1>
        
        <!-- Main title -->
        <h1 class="sans-bold" style="color: white; font-size: 60px;">Travel Ideas</h1>
        
        <!-- Description -->
        <p style="color: rgba(255,255,255,0.9); font-size: 18px;">
            Explore the diverse beauty across the 7 provinces of Nepal.
        </p>
    </div>
</section>

<!-- Main Content Section -->
<section style="background: var(--bg-light); padding: 80px 0;">
    <div class="container" style="display: grid; grid-template-columns: 280px 1fr; gap: 50px;">
        
        <!-- Sidebar Filters -->
        <aside>
            <div style="background: white; padding: 30px;">
                
                <!-- Filter title -->
                <h3>BY PROVINCE</h3>
                
                <!-- Filter buttons -->
                <div class="filter-group">
                    <button class="filter-btn active" data-filter="all">All Regions</button>
                    <button class="filter-btn" data-filter="koshi">Koshi Province</button>
                    <!-- More filters -->
                </div>
            </div>
        </aside>

        <!-- Travel Ideas Grid -->
        <div>
            <div id="ideasGrid" style="display: grid; gap: 30px;">
                
                <!-- Loop through travel ideas -->
                <?php foreach($travel_ideas as $idea): ?>
                
                <!-- Each card is clickable -->
                <a href="travel-idea-detail.php?id=<?php echo $idea['id']; ?>" 
                   class="idea-card-link idea-card" 
                   data-category="<?php echo $idea['province_slug']; ?>">
                    
                    <!-- Image section -->
                    <div class="card-img-wrapper">
                        <img src="<?php echo htmlspecialchars($idea['image']); ?>">
                        
                        <!-- Province label -->
                        <span class="province-badge">
                            <?php echo htmlspecialchars($idea['province']); ?>
                        </span>
                    </div>
                    
                    <!-- Text content -->
                    <div class="card-content">
                        <h2 class="card-title">
                            <?php echo htmlspecialchars($idea['title']); ?>
                        </h2>
                        
                        <p class="card-desc">
                            <?php echo htmlspecialchars($idea['description']); ?>
                        </p>
                    </div>
                </a>
                
                <?php endforeach; ?>
            </div>
            
            <!-- Empty state (shown when no results) -->
            <div id="noResults" class="hidden">
                <h3>No ideas found for this region.</h3>
                <p>Try selecting another province.</p>
            </div>
        </div>
    </div>
</section>

<script>
// Run JS after page loads
document.addEventListener('DOMContentLoaded', function() {

    // Select elements
    const filterBtns = document.querySelectorAll('.filter-btn');
    const cards = document.querySelectorAll('.idea-card');
    const noResults = document.getElementById('noResults');
    const resetBtn = document.getElementById('resetFilters');

    // Add click event to each filter button
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {

            const filter = this.getAttribute('data-filter');

            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Show/hide reset button
            resetBtn.style.display = (filter === 'all') ? 'none' : 'block';

            let visibleCount = 0;

            // Loop through cards
            cards.forEach(card => {

                // Check if matches filter
                if (filter === 'all' || card.getAttribute('data-category') === filter) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Show "no results" message if nothing matches
            if (visibleCount === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        });
    });

    // Reset button functionality
    resetBtn.addEventListener('click', function() {
        document.querySelector('[data-filter="all"]').click();
    });
});
</script>

<?php 
// Include footer file
include 'includes/footer.php'; 
?>