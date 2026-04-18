<?php 
include '../includes/deals-data.php';

$saved_ids = $_SESSION['saved_deals'] ?? [];
$saved_deals = [];
foreach($saved_ids as $sid) {
    if (isset($deals[$sid])) {
        $saved_deals[] = $deals[$sid];
    }
}

$current_page = 'saved.php';
include '../includes/header.php'; 
?>

<link rel="stylesheet" href="../assets/css/styles.css">

<!-- Hero Section -->
<section class="hero-about" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('../images/hero_nepal.png');">
    <div class="container hero-about-container">
        <div class="hero-about-title title-overlap" style="margin-left:0; text-align: center; width: 100%; margin-top: 50px;">
            <h1 class="script-font">Your Personalized</h1>
            <h1 class="sans-bold" style="font-size: 60px;">Saved Deals</h1>
        </div>
    </div>
</section>

<section style="padding: 80px 0; background: #fdfbf7; min-height: 50vh;">
    <div class="container">
        
        <?php if(isset($_SESSION['message'])): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 8px; margin-bottom: 40px; text-align: center; font-weight: bold; font-family: inherit; max-width: 800px; margin: 0 auto 40px;">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($saved_deals)): ?>
            <div style="text-align: center; padding: 80px 40px; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); max-width: 600px; margin: 0 auto;">
                <h3 style="color: var(--primary-blue); font-size: 28px; font-weight: 800; margin-bottom: 20px;">No Deals Saved Yet</h3>
                <p style="color: var(--text-gray); font-size: 16px; margin-bottom: 30px;">Browse our exclusive deals and packages to save your favorites here for easy access later.</p>
                <a href="index.php#deals" class="btn" style="padding: 15px 30px;">Explore Packages</a>
            </div>
        <?php else: ?>
            <div class="deals-options-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                <?php foreach($saved_deals as $deal): ?>
                <div style="display: flex; flex-direction: column; background: white; text-decoration: none; position: relative; border: 1px solid #eee; transition: all 0.3s ease;" class="deal-card">
                    <a href="deal.php?id=<?php echo htmlspecialchars($deal['id']); ?>" style="display: block; text-decoration: none; color: inherit; flex: 1; display: flex; flex-direction: column;">
                        <div style="position: relative;">
                            <img src="<?php echo htmlspecialchars($deal['image']); ?>" alt="Deal" style="width: 100%; height: 260px; object-fit: cover; display: block;">
                            <div style="position: absolute; top: 15px; right: 15px; display: flex; gap: 5px;">
                                <span style="background: <?php echo htmlspecialchars($deal['badge_color']); ?>; color: white; padding: 6px 14px; font-size: 11px; font-weight: 800; letter-spacing: 1px; border-radius: 4px; text-transform: uppercase; box-shadow: 0 4px 10px rgba(0,0,0,0.2);"><?php echo htmlspecialchars($deal['category_badge']); ?></span>
                            </div>
                        </div>

                        <div style="padding: 30px 20px 20px; flex: 1; display: flex; flex-direction: column;">
                            <span style="color: var(--primary-yellow); font-weight: 800; font-size: 13px; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 12px; display: block;"><?php echo htmlspecialchars($deal['region']); ?></span>
                            <h3 style="color: #333; font-size: 22px; font-weight: 800; line-height: 1.4; margin: 0 0 20px 0;"><?php echo htmlspecialchars($deal['title']); ?></h3>
                            
                            <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: flex-end;">
                                <a href="save_deal.php?id=<?php echo htmlspecialchars($deal['id']); ?>&action=remove" style="padding: 10px 15px; background: #ffebee; color: #d32f2f; border-radius: 6px; font-weight: 800; font-size: 12px; text-decoration: none; text-transform: uppercase; position: relative; z-index: 10;">Remove</a>
                                <span style="font-weight: 700; font-size: 13px; color: #3a6b9c;"><?php echo htmlspecialchars($deal['price']); ?></span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
