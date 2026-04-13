<?php 
require_once '../includes/travel-idea-details-data.php';

$id = isset($_GET['id']) ? $_GET['id'] : '';
$detail = isset($travel_idea_details[$id]) ? $travel_idea_details[$id] : null;

if (!$detail) {
    header('Location: travel-ideas.php');
    exit;
}

$current_page = 'travel-ideas.php';
include '../includes/header.php'; 
?>

<link rel="stylesheet" href="../assets/css/travel-idea-detail.css">

<!-- Hero Section -->
<section class="detail-hero" style="background-image: url('<?php echo htmlspecialchars($detail['hero_image']); ?>');">
    <div class="container hero-info">
        <span class="vibe-tag"><?php echo htmlspecialchars($detail['vibe']); ?></span>
        <h1 class="detail-title"><?php echo htmlspecialchars($detail['title']); ?></h1>
        <div class="detail-meta">
            <span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/><circle cx="12" cy="10" r="3"/></svg>
                <?php echo htmlspecialchars($detail['province']); ?>
            </span>
            <span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x21="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <?php echo htmlspecialchars($detail['duration']); ?>
            </span>
        </div>
    </div>
</section>

<!-- Main Content Area -->
<section style="background: var(--bg-light); padding-bottom: 80px;">
    <div class="container" style="max-width: 1300px; display: grid; grid-template-columns: 1fr 350px; gap: 80px;">
        
        <!-- Left Article Side -->
        <main class="detail-article">
            <p class="intro-text"><?php echo htmlspecialchars($detail['intro']); ?></p>
            
            <div style="margin-bottom: 60px;">
                <h3 style="font-family: 'Playfair Display', serif; font-size: 32px; color: var(--primary-blue); margin-bottom: 30px;">Trip Highlights</h3>
                <div class="highlights-grid">
                    <?php foreach ($detail['highlights'] as $highlight): ?>
                    <div class="highlight-item">
                        <p><?php echo htmlspecialchars($highlight); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Itinerary Timeline -->
            <div class="itinerary-section">
                <h3 style="font-family: 'Playfair Display', serif; font-size: 32px; color: var(--primary-blue); margin-bottom: 40px;">The Itinerary</h3>
                
                <?php foreach ($detail['itinerary'] as $day => $info): ?>
                <div class="day-card">
                    <div class="day-number"><?php echo $day; ?></div>
                    <div class="day-content">
                        <h4><?php echo htmlspecialchars($info['title']); ?></h4>
                        
                        <?php if (isset($info['morning'])): ?>
                        <div class="activity">
                            <strong>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="m12 1v2"/><path d="m12 21v2"/><path d="m4.22 4.22 1.42 1.42"/><path d="m18.36 18.36 1.42 1.42"/><path d="m1 12h2"/><path d="m21 12h2"/><path d="m4.22 19.78 1.42-1.42"/><path d="m18.36 5.64 1.42-1.42"/></svg>
                                Morning
                            </strong>
                            <p><?php echo htmlspecialchars($info['morning']); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($info['afternoon'])): ?>
                        <div class="activity">
                            <strong>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="M20 12h2"/><path d="m19.07 4.93-1.41 1.41"/><path d="M15.947 12.65a4 4 0 0 0-5.925-4.128c.08.398.128.81.128 1.228 0 2.21-1.79 4-4 4-.42 0-.83-.05-1.228-.127a4 4 0 1 0 11.025-1z"/></svg>
                                Afternoon
                            </strong>
                            <p><?php echo htmlspecialchars($info['afternoon']); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($info['evening'])): ?>
                        <div class="activity">
                            <strong>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                                Evening
                            </strong>
                            <p><?php echo htmlspecialchars($info['evening']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="day-image">
                        <img src="<?php echo htmlspecialchars($info['img']); ?>" alt="Itinerary scene">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>

        <!-- Right Logistics Container -->
        <aside>
            <div class="info-sidebar-box">
                <h3>Good to Know</h3>
                <ul class="info-list">
                    <li>
                        <strong>Transport</strong>
                        <p><?php echo htmlspecialchars($detail['logistics']['transport']); ?></p>
                    </li>
                    <li>
                        <strong>Accommodation</strong>
                        <p><?php echo htmlspecialchars($detail['logistics']['accommodation']); ?></p>
                    </li>
                    <li>
                        <strong>Best Time to Go</strong>
                        <p><?php echo htmlspecialchars($detail['logistics']['best_time']); ?></p>
                    </li>
                    <li style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 30px;">
                        <strong>Pro Travel Tip</strong>
                        <p style="color: #d35400; font-style: italic;"><?php echo htmlspecialchars($detail['logistics']['pro_tip']); ?></p>
                    </li>
                </ul>
            </div>

            <!-- Related Suggestions -->
            <div class="related-ideas-sidebar" style="margin-top: 50px;">
                <h4 style="font-family: 'Playfair Display', serif; font-size: 22px; margin-bottom: 25px; color: var(--primary-blue);">More to Explore</h4>
                <?php 
                $shown = 0;
                foreach($travel_idea_details as $rid => $rdata): 
                    if($rid === $id || $shown >= 3) continue;
                    $shown++;
                ?>
                <a href="travel-idea-detail.php?id=<?php echo $rid; ?>" class="related-card-link" style="text-decoration: none; display: block; margin-bottom: 20px;">
                    <div class="related-card" style="display: flex; gap: 15px; align-items: center;">
                        <img src="<?php echo htmlspecialchars($rdata['hero_image']); ?>" style="width: 80px; height: 80px; border-radius: 10px; object-fit: cover;">
                        <div>
                            <span style="font-size: 10px; text-transform: uppercase; color: var(--primary-yellow); font-weight: 800; letter-spacing: 1px;"><?php echo $rdata['vibe']; ?></span>
                            <h5 style="margin: 3px 0; color: var(--primary-blue); font-size: 14px; line-height: 1.3;"><?php echo $rdata['title']; ?></h5>
                            <span style="font-size: 11px; color: #999;"><?php echo $rdata['duration']; ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 40px; text-align: center;">
                <a href="travel-ideas.php" class="premium-btn" style="width: 100%; box-sizing: border-box; display: inline-block; text-decoration: none;">Explore All Ideas</a>
            </div>
        </aside>

    </div>
</section>

<?php include '../includes/footer.php'; ?>