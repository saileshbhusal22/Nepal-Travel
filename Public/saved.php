<?php
/**
 
 * Author: Bijay Sapkota
 * Group: L5CG6
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/saved_helpers.php';

$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$saved_deals = getSavedDeals($conn);
$saved_experiences = $current_user_id > 0 ? getSavedExperiencePosts($conn, $current_user_id) : [];
$total_saved = count($saved_deals) + count($saved_experiences);

$current_page = 'saved.php';
include '../includes/header.php';
?>

<link rel="stylesheet" href="../assets/css/styles.css">

<style>
.saved-page-wrap { padding: 80px 0; background: #fdfbf7; min-height: 50vh; }
.saved-section { margin-bottom: 60px; }
.saved-section h2 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    color: #1b3a5a;
    margin-bottom: 8px;
}
.saved-section .sub {
    color: #666;
    font-size: 14px;
    margin-bottom: 28px;
}
.saved-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}
.experience-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #eee;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    display: flex;
    flex-direction: column;
}
.experience-card img {
    width: 100%;
    height: 220px;
    object-fit: cover;
}
.experience-card .body { padding: 18px 20px; flex: 1; display: flex; flex-direction: column; gap: 10px; }
.experience-card .meta { font-size: 11px; font-weight: 700; color: #f5a623; text-transform: uppercase; }
.experience-card .caption { font-size: 14px; color: #444; line-height: 1.5; flex: 1; }
.experience-card .actions { display: flex; gap: 10px; margin-top: 8px; }
.experience-card .btn-view {
    flex: 1;
    text-align: center;
    padding: 10px;
    background: #1b3a5a;
    color: #fff;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
}
.experience-card .btn-remove {
    padding: 10px 14px;
    background: #ffebee;
    color: #d32f2f;
    border: none;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}
.empty-box {
    text-align: center;
    padding: 50px 30px;
    background: #fff;
    border-radius: 12px;
    border: 2px dashed #eee;
}
</style>

<section class="hero-about" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('../images/hero_nepal.png');">
    <div class="container hero-about-container">
        <div class="hero-about-title title-overlap" style="margin-left:0; text-align: center; width: 100%; margin-top: 50px;">
            <h1 class="script-font">Your Collection</h1>
            <h1 class="sans-bold" style="font-size: 52px;">Saved Items</h1>
            <p style="color: rgba(255,255,255,0.85); margin-top: 12px; font-size: 15px;">
                <?= (int)$total_saved ?> item<?= $total_saved === 1 ? '' : 's' ?> saved
            </p>
        </div>
    </div>
</section>

<section class="saved-page-wrap">
    <div class="container">

        <?php if (!empty($_SESSION['message'])): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 8px; margin-bottom: 30px; text-align: center; font-weight: bold;">
                <?= htmlspecialchars($_SESSION['message']) ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Saved Experiences -->
        <div class="saved-section">
            <h2>Saved Experiences</h2>
            <p class="sub">Posts you bookmarked from the Experience feed.</p>

            <?php if ($current_user_id <= 0): ?>
                <div class="empty-box">
                    <p style="color:#666; margin-bottom:16px;">Log in to save and view experience posts.</p>
                    <a href="/Nepal-Travel/user/login.php?redirect=Public/saved.php" class="btn" style="padding:12px 24px;">Log In</a>
                </div>
            <?php elseif (empty($saved_experiences)): ?>
                <div class="empty-box">
                    <p style="color:#666; margin-bottom:16px;">No experience posts saved yet. Tap the bookmark on any post to add it here.</p>
                    <a href="/Nepal-Travel/Public/experience.php" class="btn" style="padding:12px 24px;">Browse Experiences</a>
                </div>
            <?php else: ?>
                <div class="saved-grid">
                    <?php foreach ($saved_experiences as $post): ?>
                    <article class="experience-card" id="saved-exp-<?= (int)$post['id'] ?>">
                        <img src="<?= htmlspecialchars(formatSavedExperienceImage($post['image_path'])) ?>" alt="Experience">
                        <div class="body">
                            <div class="meta">
                                <?= htmlspecialchars($post['username'] ?? 'Traveler') ?>
                                <?php if (!empty($post['destination'])): ?>
                                    · <?= htmlspecialchars($post['destination']) ?>
                                <?php endif; ?>
                            </div>
                            <p class="caption"><?= htmlspecialchars(strlen($post['caption'] ?? '') > 120 ? substr($post['caption'], 0, 120) . '…' : ($post['caption'] ?? '')) ?></p>
                            <div class="actions">
                                <a href="/Nepal-Travel/Public/experience.php#post-card-<?= (int)$post['id'] ?>" class="btn-view">View Post</a>
                                <button type="button" class="btn-remove" data-post-id="<?= (int)$post['id'] ?>">Remove</button>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Saved Deals -->
        <div class="saved-section">
            <h2>Saved Deals &amp; Packages</h2>
            <p class="sub">Deals you added from Deals &amp; Packages or deal details pages.</p>

            <?php if (empty($saved_deals)): ?>
                <div class="empty-box">
                    <p style="color:#666; margin-bottom:16px;">No deals saved yet.</p>
                    <a href="/Nepal-Travel/Public/deals-and-packages.php" class="btn" style="padding:12px 24px;">Browse Deals</a>
                </div>
            <?php else: ?>
                <div class="deals-options-grid saved-grid">
                    <?php foreach ($saved_deals as $deal): ?>
                    <div class="deal-card" style="display:flex; flex-direction:column; background:white; border:1px solid #eee;">
                        <a href="deal-details.php?id=<?= (int)$deal['id'] ?>" style="text-decoration:none; color:inherit; display:flex; flex-direction:column; height:100%;">
                            <div style="position:relative;">
                                <img src="<?= htmlspecialchars($deal['image_url']) ?>" style="width:100%; height:220px; object-fit:cover;" alt="">
                                <div style="position:absolute; top:15px; right:15px;">
                                    <span style="background:#2563eb; color:white; padding:6px 14px; font-size:11px; font-weight:800; border-radius:4px;">
                                        <?= htmlspecialchars($deal['category'] ?? 'Deal') ?>
                                    </span>
                                </div>
                            </div>
                            <div style="padding:22px 20px; flex:1; display:flex; flex-direction:column;">
                                <span style="color: var(--primary-yellow); font-weight:800; font-size:13px; margin-bottom:10px;">
                                    <?= htmlspecialchars($deal['location'] ?? 'Nepal') ?>
                                </span>
                                <h3 style="color:#333; font-size:20px; font-weight:800; margin-bottom:16px;">
                                    <?= htmlspecialchars($deal['title']) ?>
                                </h3>
                                <div style="margin-top:auto; display:flex; justify-content:space-between; align-items:center;">
                                    <a href="save_deal.php?id=<?= (int)$deal['id'] ?>&action=remove"
                                       onclick="event.stopPropagation();"
                                       style="padding:10px 15px; background:#ffebee; color:#d32f2f; border-radius:6px; font-weight:800; font-size:12px; text-decoration:none;">
                                       Remove
                                    </a>
                                    <span style="font-weight:700; font-size:14px; color:#3a6b9c;">
                                        NPR <?= number_format((float)$deal['price']) ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($current_user_id > 0): ?>
        <p style="text-align:center; margin-top:20px;">
            <a href="/Nepal-Travel/user/dashboard.php?tab=saved" style="color:#285da1; font-weight:700; text-decoration:none;">View all saved items in your dashboard →</a>
        </p>
        <?php endif; ?>

    </div>
</section>

<script>
document.querySelectorAll('.btn-remove[data-post-id]').forEach(btn => {
    btn.addEventListener('click', async function () {
        const postId = this.dataset.postId;
        if (!postId) return;
        try {
            const res = await fetch('/Nepal-Travel/Public/api/experience/toggle_save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ post_id: postId })
            });
            const data = await res.json();
            if (data.success && data.action === 'unsaved') {
                const card = document.getElementById('saved-exp-' + postId);
                if (card) card.remove();
            }
        } catch (e) {
            console.error(e);
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
