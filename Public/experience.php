<?php include __DIR__ . '/../includes/header.php';  ?>

<!-- Hero Section -->
<section class="hero-refined" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('../images/annapurna_trek.png');">
    <div class="hero-content">
        <h1 class="hero-title">
            <span class="script-font">Discover</span><br>
            <span class="sans-bold">EXPERIENCES</span>
        </h1>
        <p class="hero-description" style="color: white; font-size: 16px; margin: 0 auto; max-width: 600px;">Filter and find the perfect adventure for your soul.</p>
    </div>
</section>

<!-- Main Layout: Sidebar + Grid -->
<section class="travel-ideas-section container">
    <div class="layout-grid">
        <!-- Sidebar Filter -->
        <aside class="sidebar-filter">
            <h3 class="filter-title">Filter Experiences</h3>
            
            <div class="filter-group">
                <h4>State / Region</h4>
                <select class="custom-select">
                    <option>All Regions</option>
                    <option>Everest Region</option>
                    <option>Annapurna Region</option>
                    <option>Kathmandu Valley</option>
                    <option>Terai Plains</option>
                </select>
            </div>
            
            <div class="filter-group">
                <h4>Category</h4>
                <label class="checkbox-label"><input type="checkbox"> <span>Heritage & Culture</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>Nature & Wildlife</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>Trekking & Adventure</span></label>
                <label class="checkbox-label"><input type="checkbox" checked> <span>Spiritual & Wellness</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>City Excitement</span></label>
            </div>
            
            <div class="filter-group">
                <h4>Duration</h4>
                <label class="checkbox-label"><input type="checkbox"> <span>Half Day</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>1 - 3 Days</span></label>
                <label class="checkbox-label"><input type="checkbox" checked> <span>4 - 7 Days</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>8 - 14 Days</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>15+ Days</span></label>
            </div>
            
            <div class="filter-group">
                <h4>Best Time to Visit</h4>
                <label class="checkbox-label"><input type="checkbox"> <span>January</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>February</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>March</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>April</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>May</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>June</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>July</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>August</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>September</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>October</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>November</span></label>
                <label class="checkbox-label"><input type="checkbox"> <span>December</span></label>
            </div>
            
            <button class="btn btn-primary" style="width: 100%; border-radius: 8px; padding: 12px; margin-top: 10px;">Apply Filters</button>
        </aside>

        <!-- Main Content -->
        <main class="content-grid">
            <div class="results-info">
                <p>Showing <strong>3</strong> matching experiences</p>
            </div>

            <div class="grid-container">
                <!-- Card 1 -->
                <div class="idea-card">
                    <div class="card-badge">7 DAYS 6 NIGHTS</div>
                    <img src="../images/pokhara_lake.png" alt="Pokhara" class="card-img">
                    <div class="card-overlay">
                        <span class="card-region">Gandaki Zone</span>
                        <h3 class="card-title">Ultimate Wellness & Yoga Retreat</h3>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="idea-card">
                    <div class="card-badge">5 DAYS 4 NIGHTS</div>
                    <img src="../images/bhaktapur_temple.png" alt="Heritage" class="card-img">
                    <div class="card-overlay">
                        <span class="card-region">Lumbini Province</span>
                        <h3 class="card-title">Buddhist Monastary Cultural Immersion</h3>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="idea-card">
                    <div class="card-badge">4 DAYS 3 NIGHTS</div>
                    <img src="../images/annapurna_trek.png" alt="Trek" class="card-img">
                    <div class="card-overlay">
                        <span class="card-region">Bagmati Zone</span>
                        <h3 class="card-title">Shivapuri National Park Forest Bathing</h3>
                    </div>
                </div>
            </div>
        </main>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="../assets/js/experience-filter.js"></script>
