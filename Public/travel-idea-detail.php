<?php 
$current_page = 'travel-idea-detail.php';
include '../includes/header.php'; 
include_once '../includes/travel-idea-details-data.php';

// Get the ID from URL parameter
$idea_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$idea = $idea_id ? getTravelIdeaById($idea_id) : null;

// If idea not found, redirect to travel-ideas page
if (!$idea) {
    header("Location: travel-ideas.php");
    exit();
}
?>

<style>
:root {
    --primary-blue: #1b3a5a;
    --primary-yellow: #f5a623;
    --text-muted: #666;
    --bg-light: #f8f9fa;
}

.hero-detail {
    background-size: cover;
    background-position: center;
    position: relative;
    height: 500px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.hero-detail::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.7) 100%);
}

.hero-detail-content {
    position: relative;
    z-index: 2;
    color: white;
    text-align: center;
    padding: 40px;
    max-width: 800px;
}

.hero-detail h1 {
    font-family: 'Playfair Display', serif;
    font-size: 48px;
    margin: 0 0 20px 0;
    color: white;
}

.hero-detail-meta {
    display: flex;
    gap: 30px;
    justify-content: center;
    font-size: 14px;
    margin-top: 20px;
}

.detail-section {
    padding: 80px 0;
}

.detail-content {
    max-width: 900px;
    margin: 0 auto;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    margin-bottom: 60px;
    background: var(--bg-light);
    padding: 40px;
    border-radius: 15px;
}

.info-item h3 {
    font-size: 12px;
    text-transform: uppercase;
    color: var(--primary-yellow);
    letter-spacing: 1px;
    margin: 0 0 10px 0;
    font-weight: 800;
}

.info-item p {
    font-size: 16px;
    color: var(--primary-blue);
    font-weight: 600;
    margin: 0;
}

.description {
    font-size: 18px;
    line-height: 1.8;
    color: var(--text-muted);
    margin-bottom: 40px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--primary-blue);
    text-decoration: none;
    margin-bottom: 30px;
    font-weight: 600;
    transition: color 0.3s;
}

.back-link:hover {
    color: var(--primary-yellow);
}

.cta-button {
    display: inline-block;
    background: var(--primary-blue);
    color: white;
    padding: 15px 40px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    margin-top: 30px;
}

.cta-button:hover {
    background: var(--primary-yellow);
    color: var(--primary-blue);
}

.province-tag {
    display: inline-block;
    background: var(--primary-yellow);
    color: white;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 20px;
}
</style>

<!-- Hero Section -->
<section class="hero-detail" style="background-image: url('<?php echo htmlspecialchars($idea['image']); ?>');">
    <div class="hero-detail-content">
        <h1><?php echo htmlspecialchars($idea['title']); ?></h1>
        <p style="font-size: 18px; margin: 10px 0 0 0;"><?php echo htmlspecialchars($idea['description']); ?></p>
    </div>
</section>

<!-- Detail Content -->
<section class="detail-section" style="background: white;">
    <div class="detail-content">
        <a href="travel-ideas.php" class="back-link">
            <span>←</span> Back to Travel Ideas
        </a>

        <div class="province-tag"><?php echo htmlspecialchars($idea['province']); ?></div>

        <!-- Key Information -->
        <div class="info-grid">
            <div class="info-item">
                <h3>Duration</h3>
                <p><?php echo htmlspecialchars($idea['duration']); ?></p>
            </div>
            <div class="info-item">
                <h3>Difficulty Level</h3>
                <p><?php echo htmlspecialchars($idea['difficulty']); ?></p>
            </div>
            <div class="info-item">
                <h3>Best Season</h3>
                <p><?php echo htmlspecialchars($idea['best_season']); ?></p>
            </div>
            <div class="info-item">
                <h3>Region</h3>
                <p><?php echo htmlspecialchars($idea['province']); ?></p>
            </div>
        </div>

        <!-- Description -->
        <div class="description">
            <?php echo htmlspecialchars($idea['content']); ?>
        </div>

        <!-- CTA Button -->
        <a href="booking.php" class="cta-button">Book This Experience</a>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
