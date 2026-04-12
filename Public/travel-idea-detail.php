<?php 
// Include the data file containing all travel idea details
require_once 'includes/travel-idea-details-data.php';

// Get the 'id' from URL (e.g., travel-idea-detail.php?id=everest-base-camp)
$id = isset($_GET['id']) ? $_GET['id'] : '';

// Check if the selected travel idea exists, otherwise set null
$detail = isset($travel_idea_details[$id]) ? $travel_idea_details[$id] : null;

// If no valid data found, redirect back to main page
if (!$detail) {
    header('Location: travel-ideas.php');
    exit;
}

// Set current page for navbar highlighting
$current_page = 'travel-ideas.php';

// Include header
include 'includes/header.php'; 
?>

<!-- External CSS for this page -->
<link rel="stylesheet" href="assets/css/travel-idea-detail.css">

<!-- Hero Section (Top Banner with background image) -->
<section class="detail-hero" style="background-image: url('<?php echo htmlspecialchars($detail['hero_image']); ?>');">
    <div class="container hero-info">

        <!-- Vibe tag (e.g., Adventure, Relaxation) -->
        <span class="vibe-tag"><?php echo htmlspecialchars($detail['vibe']); ?></span>

        <!-- Main title -->
        <h1 class="detail-title"><?php echo htmlspecialchars($detail['title']); ?></h1>

        <!-- Meta info (province + duration) -->
        <div class="detail-meta">

            <!-- Province -->
            <span>
                <!-- Location icon -->
                <svg width="20" height="20">...</svg>
                <?php echo htmlspecialchars($detail['province']); ?>
            </span>

            <!-- Duration -->
            <span>
                <!-- Calendar icon -->
                <svg width="20" height="20">...</svg>
                <?php echo htmlspecialchars($detail['duration']); ?>
            </span>
        </div>
    </div>
</section>

<!-- Main Content Section -->
<section style="background: var(--bg-light); padding-bottom: 80px;">
    <div class="container" style="display: grid; grid-template-columns: 1fr 350px; gap: 80px;">
        
        <!-- LEFT SIDE: Main Article -->
        <main class="detail-article">

            <!-- Intro paragraph -->
            <p class="intro-text"><?php echo htmlspecialchars($detail['intro']); ?></p>
            
            <!-- Trip Highlights Section -->
            <div style="margin-bottom: 60px;">
                <h3>Trip Highlights</h3>

                <!-- Loop through highlights array -->
                <div class="highlights-grid">
                    <?php foreach ($detail['highlights'] as $highlight): ?>
                    <div class="highlight-item">
                        <p><?php echo htmlspecialchars($highlight); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Itinerary Timeline Section -->
            <div class="itinerary-section">
                <h3>The Itinerary</h3>
                
                <!-- Loop through each day -->
                <?php foreach ($detail['itinerary'] as $day => $info): ?>
                <div class="day-card">

                    <!-- Day number -->
                    <div class="day-number"><?php echo $day; ?></div>

                    <!-- Day content -->
                    <div class="day-content">

                        <!-- Day title -->
                        <h4><?php echo htmlspecialchars($info['title']); ?></h4>
                        
                        <!-- Morning activity (if exists) -->
                        <?php if (isset($info['morning'])): ?>
                        <div class="activity">
                            <strong>
                                <!-- Morning icon -->
                                <svg width="14" height="14">...</svg>
                                Morning
                            </strong>
                            <p><?php echo htmlspecialchars($info['morning']); ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Afternoon activity -->
                        <?php if (isset($info['afternoon'])): ?>
                        <div class="activity">
                            <strong>
                                <!-- Afternoon icon -->
                                <svg width="14" height="14">...</svg>
                                Afternoon
                            </strong>
                            <p><?php echo htmlspecialchars($info['afternoon']); ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Evening activity -->
                        <?php if (isset($info['evening'])): ?>
                        <div class="activity">
                            <strong>
                                <!-- Evening icon -->
                                <svg width="14" height="14">...</svg>
                                Evening
                            </strong>
                            <p><?php echo htmlspecialchars($info['evening']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Image for each day -->
                    <div class="day-image">
                        <img src="<?php echo htmlspecialchars($info['img']); ?>" alt="Itinerary scene">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>

        <!-- RIGHT SIDE: Sidebar -->
        <aside>

            <!-- Logistics / Info Box -->
            <div class="info-sidebar-box">
                <h3>Good to Know</h3>

                <ul class="info-list">

                    <!-- Transport info -->
                    <li>
                        <strong>Transport</strong>
                        <p><?php echo htmlspecialchars($detail['logistics']['transport']); ?></p>
                    </li>

                    <!-- Accommodation info -->
                    <li>
                        <strong>Accommodation</strong>
                        <p><?php echo htmlspecialchars($detail['logistics']['accommodation']); ?></p>
                    </li>

                    <!-- Best time to visit -->
                    <li>
                        <strong>Best Time to Go</strong>
                        <p><?php echo htmlspecialchars($detail['logistics']['best_time']); ?></p>
                    </li>

                    <!-- Travel tip -->
                    <li style="margin-top: 30px;">
                        <strong>Pro Travel Tip</strong>
                        <p><?php echo htmlspecialchars($detail['logistics']['pro_tip']); ?></p>
                    </li>
                </ul>
            </div>

            <!-- Related Travel Ideas -->
            <div class="related-ideas-sidebar" style="margin-top: 50px;">
                <h4>More to Explore</h4>

                <?php 
                $shown = 0;

                // Loop through other travel ideas
                foreach($travel_idea_details as $rid => $rdata): 

                    // Skip current page and limit to 3 suggestions
                    if($rid === $id || $shown >= 3) continue;

                    $shown++;
                ?>

                <!-- Related card -->
                <a href="travel-idea-detail.php?id=<?php echo $rid; ?>" class="related-card-link">
                    <div class="related-card">

                        <!-- Image -->
                        <img src="<?php echo htmlspecialchars($rdata['hero_image']); ?>">

                        <!-- Text -->
                        <div>
                            <span><?php echo $rdata['vibe']; ?></span>
                            <h5><?php echo $rdata['title']; ?></h5>
                            <span><?php echo $rdata['duration']; ?></span>
                        </div>
                    </div>
                </a>

                <?php endforeach; ?>
            </div>
            
            <!-- Button to go back to all ideas -->
            <div style="margin-top: 40px; text-align: center;">
                <a href="travel-ideas.php" class="premium-btn">
                    Explore All Ideas
                </a>
            </div>
        </aside>

    </div>
</section>

<?php 
// Include footer
include 'includes/footer.php'; 
?>